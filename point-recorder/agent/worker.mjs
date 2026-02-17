import 'dotenv/config';

import process from 'node:process';
import fs from 'node:fs/promises';
import path from 'node:path';

import { createPool, getMysqlConfigFromEnv } from './db.mjs';
import { createLogger } from './logger.mjs';
import { loadAgentConfigFromEnv, replayTemplate } from './replay.mjs';
import { analyzeScreenshot } from './vision.mjs';
import { uploadScreenshot } from './upload.mjs';

const logger = createLogger(process.env);

process.on('unhandledRejection', (reason) => {
  logger?.error?.('agent.unhandled_rejection', { error: String(reason) });
});
process.on('uncaughtException', (err) => {
  logger?.error?.('agent.uncaught_exception', { error: String(err?.message || err) });
});

async function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function templateContainsSlot(templatePath, slotName) {
  if (!templatePath || !slotName) return false;
  const absTemplatePath = path.resolve(process.cwd(), templatePath);
  const raw = await fs.readFile(absTemplatePath, 'utf8');
  const parsed = JSON.parse(raw);
  if (!Array.isArray(parsed)) return false;
  return parsed.some((event) => event?.type === 'slot_begin' && event?.name === slotName);
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
    const hbAgeMs = hb ? Date.now() - hb.getTime() : null;
    let stale = hb ? hbAgeMs > 10 * 60 * 1000 : true;
    let reason = stale ? 'heartbeat_timeout' : null;

    let currentStatus = null;
    if (state.current_request_id) {
      const [[row]] = await connection.query(
        'SELECT status FROM placas_zero_km_requests WHERE id = :id LIMIT 1',
        { id: state.current_request_id }
      );
      currentStatus = row?.status ?? null;
      if (currentStatus && currentStatus !== 'running') {
        stale = true;
        reason = 'request_not_running';
      }
    } else {
      stale = true;
      reason = 'no_current_request';
    }

    if (!stale) {
      logger?.debug?.('runner.busy', {
        current_request_id: state.current_request_id,
        hbAgeSec: hbAgeMs != null ? Math.round(hbAgeMs / 1000) : null,
        status: currentStatus,
      });
      return null;
    }

    await connection.query(
      'UPDATE placas_zero_km_runner_state SET is_running = 0, current_request_id = NULL, last_heartbeat_at = NOW() WHERE id = 1'
    );
    logger?.warn?.('runner.stale_reset', {
      prev_request_id: state.current_request_id,
      reason,
      hbAgeSec: hbAgeMs != null ? Math.round(hbAgeMs / 1000) : null,
      status: currentStatus,
    });
  }

  const [pendingRows] = await connection.query(
    "SELECT * FROM placas_zero_km_requests WHERE status = 'pending' ORDER BY id ASC LIMIT 1 FOR UPDATE"
  );
  if (!pendingRows?.length) {
    await connection.query('UPDATE placas_zero_km_runner_state SET last_heartbeat_at = NOW() WHERE id = 1');
    logger?.debug?.('queue.empty');
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

  logger?.info?.('queue.claimed', { request_id: req.id, batch_id: req.batch_id });
  return req;
}

async function releaseRunner(connection, requestId) {
  await connection.query(
    'UPDATE placas_zero_km_runner_state SET is_running = 0, current_request_id = NULL, last_heartbeat_at = NOW(), updated_at = NOW() WHERE id = 1 AND current_request_id = :id',
    { id: requestId }
  );
  logger?.info?.('runner.released', { request_id: requestId });
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
  const finalizeFailure = async (reqId, batchId, errorMessage) => {
    let conn;
    try {
      conn = await pool.getConnection();
      await conn.beginTransaction();
      await conn.query(
        "UPDATE placas_zero_km_requests SET status='failed', response_error=:err, response_payload=NULL, finished_at=NOW(), updated_at=NOW() WHERE id=:id",
        { id: reqId, err: errorMessage }
      );
      await releaseRunner(conn, reqId);
      await refreshBatchCounters(conn, batchId);
      await conn.commit();
      logger?.info?.('request.failed.cleanup', { request_id: reqId });
    } catch (err) {
      logger?.error?.('request.failed.cleanup_error', { request_id: reqId, error: String(err?.message || err) });
    } finally {
      if (conn) conn.release();
    }
  };
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
      senha: agentCfg.loginPassword ?? '',
    };

    let result;
    let analysis = null;
    let uploadResult = null;
    try {
      logger?.info?.('replay.begin', { request_id: req.id });
      startHeartbeat();
      const shouldRunSeparateLogin = Boolean(agentCfg.loginTemplatePath);
      const templatePaths = Array.isArray(agentCfg.templatePaths) && agentCfg.templatePaths.length
        ? agentCfg.templatePaths
        : [agentCfg.templatePath];
      const multipleMainTemplates = templatePaths.length > 1;

      if (shouldRunSeparateLogin) {
        logger?.info?.('replay.login.begin', {
          request_id: req.id,
          templatePath: agentCfg.loginTemplatePath,
        });
        await replayTemplate({
          templatePath: agentCfg.loginTemplatePath,
          data,
          screenshotsDir: agentCfg.screenshotsDir,
          maxDelayMs: agentCfg.maxDelayMs,
          speed: agentCfg.speed,
          replayText: agentCfg.replayText,
          preReplayWaitMs: 0,
          postLoginWaitMs: agentCfg.postLoginWaitMs,
          cropWidth: 0,
          cropHeight: 0,
          stopAtScreenshot: false,
          requireRequiredSlots: false,
          requireScreenshot: false,
          warnPasswordSlotMissing: false,
          logger,
        });
        logger?.info?.('replay.login.done', { request_id: req.id });
        if (agentCfg.betweenTemplatesWaitMs > 0) {
          logger?.info?.('replay.between_wait', {
            request_id: req.id,
            waitMs: agentCfg.betweenTemplatesWaitMs,
          });
          await sleep(agentCfg.betweenTemplatesWaitMs);
        }
      }

      result = { lastScreenshotPath: null };
      for (let i = 0; i < templatePaths.length; i += 1) {
        const templatePath = templatePaths[i];
        const isLastTemplate = i === templatePaths.length - 1;
        const templateHasSenha = await templateContainsSlot(templatePath, 'senha');

        if (shouldRunSeparateLogin && templateHasSenha) {
          logger?.info?.('replay.main.trim_login_section', {
            request_id: req.id,
            startAfterSlotName: 'senha',
            templatePath,
          });
        }

        logger?.info?.('replay.main.step', {
          request_id: req.id,
          step: i + 1,
          totalSteps: templatePaths.length,
          templatePath,
        });

        const stepResult = await replayTemplate({
          templatePath,
          data,
          screenshotsDir: agentCfg.screenshotsDir,
          maxDelayMs: agentCfg.maxDelayMs,
          speed: agentCfg.speed,
          replayText: agentCfg.replayText,
          preReplayWaitMs: i === 0 ? agentCfg.preReplayWaitMs : 0,
          postLoginWaitMs: agentCfg.postLoginWaitMs,
          cropWidth: agentCfg.screenshotCropW,
          cropHeight: agentCfg.screenshotCropH,
          stopAtScreenshot: isLastTemplate ? agentCfg.stopAtScreenshot : false,
          startAfterSlotName: shouldRunSeparateLogin && templateHasSenha ? 'senha' : '',
          requireRequiredSlots: multipleMainTemplates ? false : true,
          requireScreenshot: isLastTemplate,
          warnPasswordSlotMissing: !agentCfg.loginTemplatePath && !multipleMainTemplates && isLastTemplate,
          logger,
        });

        if (stepResult?.lastScreenshotPath) {
          result = stepResult;
        }
      }

      const screenshotPath = result?.lastScreenshotPath ?? null;
      logger?.info?.('replay.screenshot', { request_id: req.id, screenshotPath });
      if (agentCfg.uploadEnabled && agentCfg.uploadUrl && screenshotPath) {
        uploadResult = await uploadScreenshot({
          url: agentCfg.uploadUrl,
          apiKey: agentCfg.uploadApiKey,
          requestId: req.id,
          filePath: screenshotPath,
          logger,
        });
      }

      if (agentCfg.ocrEnabled && screenshotPath) {
        analysis = await analyzeScreenshot(screenshotPath, { lang: agentCfg.ocrLang, logger });
      }

      const outcome = analysis
        ? (analysis?.errorMessage && (!analysis?.plates || analysis.plates.length === 0)
          ? 'error'
          : analysis?.plates && analysis.plates.length
            ? 'plates'
            : 'unknown')
        : (uploadResult ? 'uploaded' : 'unknown');

      const status =
        outcome === 'error'
          ? 'failed'
          : (uploadResult || analysis)
            ? 'succeeded'
            : 'failed';

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
              screenshot_url: uploadResult?.screenshot_url ?? null,
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
      logger?.info?.('request.done', {
        request_id: req.id,
        status,
        outcome,
      });
    } catch (err) {
      const errMsg = String(err?.message || err);
      try {
        await connection.beginTransaction();
        await connection.query(
          "UPDATE placas_zero_km_requests SET status='failed', response_error=:err, response_payload=NULL, finished_at=NOW(), updated_at=NOW() WHERE id=:id",
          { id: req.id, err: errMsg }
        );
        await releaseRunner(connection, req.id);
        await refreshBatchCounters(connection, req.batch_id);
        await connection.commit();
      } catch (innerErr) {
        logger?.error?.('request.failed.db_error', {
          request_id: req.id,
          error: String(innerErr?.message || innerErr),
        });
        await finalizeFailure(req.id, req.batch_id, errMsg);
      }
      logger?.error?.('request.failed', {
        request_id: req.id,
        error: errMsg,
      });
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
  const dbCfg = getMysqlConfigFromEnv(process.env);
  const pool = await createPool(process.env);

  logger?.info?.('agent.start', {
    template: agentCfg.templatePath,
    templates: agentCfg.templatePaths,
    loginTemplate: agentCfg.loginTemplatePath || null,
    screenshotsDir: agentCfg.screenshotsDir,
    dbHost: dbCfg.host,
    dbPort: dbCfg.port,
    dbDatabase: dbCfg.database,
    dbUser: dbCfg.user,
  });

  // Loop: tenta processar 1 por vez; se não tiver, dorme e tenta de novo.
  for (;;) {
    try {
      const did = await processOne(pool, agentCfg);
      if (!did) {
        await sleep(agentCfg.pollIntervalMs);
      } else {
        // processou 1 item; tenta o próximo “logo em seguida”
        await sleep(250);
      }
    } catch (err) {
      logger?.error?.('agent.loop_error', { error: String(err?.message || err) });
      await sleep(agentCfg.pollIntervalMs);
    }
  }
}

main().catch((err) => {
  logger?.error?.('agent.fatal', { error: String(err?.message || err) });
  process.exit(1);
});
