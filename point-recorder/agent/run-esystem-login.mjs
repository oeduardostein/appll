#!/usr/bin/env node
import 'dotenv/config';
import { replayTemplate, loadAgentConfigFromEnv } from './replay.mjs';
import { createLogger } from './logger.mjs';

const logger = createLogger(process.env);

async function main() {
  const agentCfg = loadAgentConfigFromEnv(process.env);

  if (!agentCfg.loginTemplatePath) {
    console.error('[ERRO] AGENT_LOGIN_TEMPLATE_PATH nao definido no .env');
    process.exit(1);
  }

  if (!agentCfg.loginPassword) {
    console.error('[ERRO] AGENT_LOGIN_PASSWORD nao definido no .env');
    process.exit(1);
  }

  console.log('[INFO] Iniciando login no e-System...');
  console.log(`[INFO] Template: ${agentCfg.loginTemplatePath}`);
  console.log(`[INFO] Senha: ${agentCfg.loginPassword}`);
  console.log();

  try {
    await replayTemplate({
      templatePath: agentCfg.loginTemplatePath,
      data: {
        senha: agentCfg.loginPassword,
        cpf_cgc: '00000000000', // CPF dummy, template so usa senha
      },
      screenshotsDir: agentCfg.screenshotsDir,
      maxDelayMs: agentCfg.maxDelayMs,
      speed: agentCfg.speed,
      replayText: agentCfg.replayText,
      replayVisualDebug: true, // MOSTRA ponto vermelho onde clica
      replayVisualMs: 2000, // EXIBE ponto por 2 segundos
      replayVisualDotW: 20, // PONTO maior (20x20)
      replayVisualDotH: 20,
      replayVisualShowCard: true, // Mostra card com info
      preReplayWaitMs: agentCfg.preReplayWaitMs,
      postLoginWaitMs: agentCfg.postLoginWaitMs,
      passwordInputMode: agentCfg.passwordInputMode,
      passwordTypeDelayMs: agentCfg.passwordTypeDelayMs,
      passwordBeforeEnterMs: agentCfg.passwordBeforeEnterMs,
      appExePath: agentCfg.appExePath,
      appStartWaitMs: agentCfg.appStartWaitMs,
      appKillAfterScreenshot: false, // NAO mata o e-system apos login
      stopAtScreenshot: false, // Executa tudo ate o final (incluindo cliques pos-login)
      exitAfterSenha: false,
      requireRequiredSlots: false, // NAO exige slots que o template nao usa (chassi, cpf_cgc)
      requireScreenshot: false, // NAO exige screenshot
      logger,
    });

    console.log();
    console.log('[OK] Login no e-System concluido com sucesso!');
    console.log('[OK] Os 2 cliques pos-login foram executados.');
    console.log();

  } catch (error) {
    console.error();
    console.error('[ERRO] Falha no login do e-System:');
    console.error(`[ERRO] ${error.message}`);
    console.error();
    process.exit(1);
  }
}

main().catch((err) => {
  console.error(`[FATAL] ${err.message}`);
  process.exit(1);
});
