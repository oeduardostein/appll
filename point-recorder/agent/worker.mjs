import 'dotenv/config';

import process from 'node:process';

import { createPool } from './db.mjs';
import { loadAgentConfigFromEnv, replayTemplate } from './replay.mjs';
import { analyzeScreenshot } from './vision.mjs';

async function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function claimNextPending(connection) {
  // Trava o runner_state para garantir execução única
  const [stateRows] = await connection.query(
    'SELECT * FROM placas_zero_km_runner_state WHERE id = 1 FOR UPDATE'
  );
  if (!stateRows?.length) {
    throw new Error('Runner state não encontrado (placas_zero_km_runner_state id=1). Rode as migrations.');
  }

  const state = stateRows[0];
  if (Number(state.is_running) === 1) {
    const hb = state.last_heartbeat_at ? new Date(state.last_heartbeat_at) : null;
    const stale = hb ? Date.now() - hb.getTime() > 10 * 60 * 1000 : false;
    if (!stale) {
      await connection.query('UPDATE placas_zero_km_runner_state SET last_heartbeat_at = NOW() WHERE id = 1');
      return null;
    }

    await connection.query(
      'UPDATE placas_zero_km_runner_state SET is_running = 0, current_request_id = NULL, last_heartbeat_at = NOW() WHERE id = 1'
    );
  }

  const [pendingRows] = await connection.query(
    "SELECT * FROM placas_zero_km_requests WHERE status = 'pending' ORDER BY id ASC LIMIT 1 FOR UPDATE"
  );
  if (!pendingRows?.length) {
    await connection.query('UPDATE placas_zero_km_runner_state SET last_heartbeat_at = NOW() WHERE id = 1');
    return null;
  }

  const req = pendingRows[0];
  await connection.query(
    "UPDATE placas_zero_km_requests SET status = 'running', attempts = attempts + 1, started_at = NOW(), updated_at = NOW() WHERE id = :id",
    { id: req.id }
  );
  await connection.query(
    'UPDATE placas_zero_km_runner_state SET is_running = 1, current_request_id = :id, last_heartbeat_at = NOW(), updated_at = NOW() WHERE id = 1',
    { id: req.id }
  );
  await connection.query(
    "UPDATE placas_zero_km_batches SET status = 'running', updated_at = NOW() WHERE id = :batchId",
    { batchId: req.batch_id }
  );

  return req;
}

async function releaseRunner(connection, requestId) {
  await connection.query(
    'UPDATE placas_zero_km_runner_state SET is_running = 0, current_request_id = NULL, last_heartbeat_at = NOW(), updated_at = NOW() WHERE id = 1 AND current_request_id = :id',
    { id: requestId }
  );
}

async function refreshBatchCounters(connection, batchId) {
  const [[totalRow]] = await connection.query(
    'SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId',
    { batchId }
  );
  const [[okRow]] = await connection.query(
    "SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId AND status = 'succeeded'",
    { batchId }
  );
  const [[failRow]] = await connection.query(
    "SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId AND status = 'failed'",
    { batchId }
  );
  const [[runRow]] = await connection.query(
    "SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId AND status = 'running'",
    { batchId }
  );
  const [[pendRow]] = await connection.query(
    "SELECT COUNT(*) as c FROM placas_zero_km_requests WHERE batch_id = :batchId AND status = 'pending'",
    { batchId }
  );

  const total = Number(totalRow?.c || 0);
  const succeeded = Number(okRow?.c || 0);
  const failed = Number(failRow?.c || 0);
  const running = Number(runRow?.c || 0);
  const pending = Number(pendRow?.c || 0);
  const processed = succeeded + failed;

  const status = pending === 0 && running === 0 ? 'completed' : running > 0 ? 'running' : 'pending';

  await connection.query(
    'UPDATE placas_zero_km_batches SET status = :status, total = :total, processed = :processed, succeeded = :succeeded, failed = :failed, updated_at = NOW() WHERE id = :batchId',
    { status, total, processed, succeeded, failed, batchId }
  );
}

async function processOne(pool, agentCfg) {
  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();
    const req = await claimNextPending(connection);
    await connection.commit();

    if (!req) return false;

    let heartbeatTimer = null;
    const startHeartbeat = () => {
      if (heartbeatTimer) return;
      heartbeatTimer = setInterval(async () => {
        try {
          await connection.query(
            'UPDATE placas_zero_km_runner_state SET last_heartbeat_at = NOW(), updated_at = NOW() WHERE id = 1 AND current_request_id = :id',
            { id: req.id }
          );
        } catch {
          // ignore
        }
      }, 30_000);
    };
    const stopHeartbeat = () => {
      if (heartbeatTimer) clearInterval(heartbeatTimer);
      heartbeatTimer = null;
    };

    const data = {
      cpf_cgc: req.cpf_cgc,
      nome: req.nome ?? '',
      chassi: req.chassi,
    };

    let result;
    let analysis = null;
    try {
      startHeartbeat();
      result = await replayTemplate({
        templatePath: agentCfg.templatePath,
        data,
        screenshotsDir: agentCfg.screenshotsDir,
        maxDelayMs: agentCfg.maxDelayMs,
        speed: agentCfg.speed,
        replayText: agentCfg.replayText,
      });

      const screenshotPath = result?.lastScreenshotPath ?? null;
      if (agentCfg.ocrEnabled && screenshotPath) {
        analysis = await analyzeScreenshot(screenshotPath, { lang: agentCfg.ocrLang });
      }

      const outcome =
        analysis?.errorMessage && (!analysis?.plates || analysis.plates.length === 0)
          ? 'error'
          : analysis?.plates && analysis.plates.length
            ? 'plates'
            : 'unknown';

      const status =
        outcome === 'error'
          ? 'failed'
          : 'succeeded';

      await connection.beginTransaction();
      await connection.query(
        "UPDATE placas_zero_km_requests SET status=:status, response_error=:err, response_payload=:payload, finished_at=NOW(), updated_at=NOW() WHERE id=:id",
        {
          id: req.id,
          status,
          err: outcome === 'error' ? (analysis?.errorMessage || 'Erro detectado no modal') : null,
          payload: JSON.stringify({
            success: true,
            data: {
              screenshot_path: result?.lastScreenshotPath ?? null,
              ocr: analysis
                ? {
                    text: analysis.rawText,
                    normalized_text: analysis.normalizedText,
                    plates: analysis.plates,
                    error_message: analysis.errorMessage,
                  }
                : null,
              outcome,
            },
          }),
        }
      );
      await releaseRunner(connection, req.id);
      await refreshBatchCounters(connection, req.batch_id);
      await connection.commit();
    } catch (err) {
      await connection.beginTransaction();
      await connection.query(
        "UPDATE placas_zero_km_requests SET status='failed', response_error=:err, response_payload=NULL, finished_at=NOW(), updated_at=NOW() WHERE id=:id",
        { id: req.id, err: String(err?.message || err) }
      );
      await releaseRunner(connection, req.id);
      await refreshBatchCounters(connection, req.batch_id);
      await connection.commit();
    } finally {
      stopHeartbeat();
    }

    return true;
  } finally {
    connection.release();
  }
}

async function main() {
  const agentCfg = loadAgentConfigFromEnv(process.env);
  const pool = await createPool(process.env);

  console.log('[agent] worker iniciado');
  console.log(`[agent] template: ${agentCfg.templatePath}`);
  console.log(`[agent] screenshotsDir: ${agentCfg.screenshotsDir}`);

  // Loop: tenta processar 1 por vez; se não tiver, dorme e tenta de novo.
  for (;;) {
    const did = await processOne(pool, agentCfg);
    if (!did) {
      await sleep(agentCfg.pollIntervalMs);
    } else {
      // processou 1 item; tenta o próximo “logo em seguida”
      await sleep(250);
    }
  }
}

main().catch((err) => {
  console.error('[agent] erro fatal:', err);
  process.exit(1);
});
