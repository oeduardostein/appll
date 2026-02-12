import fs from 'node:fs/promises';
import path from 'node:path';

import clipboard from 'clipboardy';
import screenshot from 'screenshot-desktop';
import { Button, Key, Point, keyboard, mouse } from '@nut-tree/nut-js';

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function toBool(value, defaultValue = false) {
  if (value == null) return defaultValue;
  const v = String(value).trim().toLowerCase();
  if (v === '1' || v === 'true' || v === 'yes' || v === 'y') return true;
  if (v === '0' || v === 'false' || v === 'no' || v === 'n') return false;
  return defaultValue;
}

function mapButton(buttonNumber) {
  // uiohook: 1=left, 2=right, 3=middle
  if (buttonNumber === 2) return Button.RIGHT;
  if (buttonNumber === 3) return Button.MIDDLE;
  return Button.LEFT;
}

function mapSpecialKey(keycode, rawcode) {
  // Tabela “best-effort”. Dependendo do SO/layout, esses códigos variam.
  const candidates = [keycode, rawcode].filter((x) => typeof x === 'number');
  for (const code of candidates) {
    if (code === 28 || code === 13) return Key.Enter;
    if (code === 15 || code === 9) return Key.Tab;
    if (code === 1 || code === 27) return Key.Escape;
    if (code === 14 || code === 8) return Key.Backspace;
    if (code === 57 || code === 32) return Key.Space;

    // setas (uiohook e VK)
    if (code === 57416 || code === 38) return Key.Up;
    if (code === 57424 || code === 40) return Key.Down;
    if (code === 57419 || code === 37) return Key.Left;
    if (code === 57421 || code === 39) return Key.Right;
  }

  return null;
}

async function pasteText(text) {
  await clipboard.write(String(text ?? ''));
  await keyboard.pressKey(Key.LeftControl, Key.V);
  await keyboard.releaseKey(Key.LeftControl, Key.V);
}

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true });
}

export async function replayTemplate({
  templatePath,
  data,
  screenshotsDir,
  maxDelayMs = 5000,
  speed = 1.0,
  replayText = false,
}) {
  const absTemplate = path.resolve(process.cwd(), templatePath);
  const raw = await fs.readFile(absTemplate, 'utf8');
  const events = JSON.parse(raw);

  if (!Array.isArray(events)) {
    throw new Error('Template inválido: esperado um array JSON.');
  }

  const outDir = path.resolve(process.cwd(), screenshotsDir || 'screenshots');
  await ensureDir(outDir);

  let lastT = null;
  let slotActive = null; // nome do slot: cpf_cgc|nome|chassi

  for (const ev of events) {
    const t = typeof ev?.t === 'number' ? ev.t : null;
    if (t != null && lastT != null) {
      const delta = Math.max(0, t - lastT);
      const scaled = speed > 0 ? delta / speed : delta;
      await sleep(Math.min(maxDelayMs, scaled));
    }
    if (t != null) lastT = t;

    const type = String(ev?.type || '');

    if (type === 'slot_begin') {
      const name = String(ev?.name || '');
      if (!name) continue;

      slotActive = name;
      const value = data?.[name] ?? '';
      await pasteText(value);
      continue;
    }

    if (type === 'screenshot') {
      const fileName = `shot_${Date.now()}.png`;
      const filePath = path.join(outDir, fileName);
      const img = await screenshot({ format: 'png' });
      await fs.writeFile(filePath, img);
      // guarda o último screenshot no payload, se quiser
      data.__last_screenshot_path = filePath;
      continue;
    }

    if (type === 'mouse_down') {
      // ao clicar, encerramos slot ativo para não ficar “pulando” caracteres indevidos
      slotActive = null;
      const x = Number(ev?.x);
      const y = Number(ev?.y);
      if (Number.isFinite(x) && Number.isFinite(y)) {
        await mouse.setPosition(new Point(x, y));
      }
      await mouse.pressButton(mapButton(ev?.button));
      continue;
    }

    if (type === 'mouse_up') {
      const x = Number(ev?.x);
      const y = Number(ev?.y);
      if (Number.isFinite(x) && Number.isFinite(y)) {
        await mouse.setPosition(new Point(x, y));
      }
      await mouse.releaseButton(mapButton(ev?.button));
      continue;
    }

    if (type === 'key_down' || type === 'key_up') {
      const key = mapSpecialKey(ev?.keycode, ev?.rawcode);
      if (!key) continue;

      // se estamos dentro de um slot, só deixamos passar teclas de navegação
      if (slotActive) {
        if (key !== Key.Tab && key !== Key.Enter && key !== Key.Escape) {
          continue;
        }
      }

      if (type === 'key_down') {
        await keyboard.pressKey(key);
      } else {
        await keyboard.releaseKey(key);
      }

      // Tab/Enter/Escape normalmente finaliza input do campo
      if (slotActive && (key === Key.Tab || key === Key.Enter || key === Key.Escape)) {
        slotActive = null;
      }
      continue;
    }

    if (type === 'key_press' && replayText) {
      if (slotActive) continue;
      const char = typeof ev?.char === 'string' ? ev.char : null;
      if (!char) continue;

      // só caracteres “digitáveis”
      if (char.length === 1) {
        await keyboard.type(char);
      }
    }
  }

  return {
    lastScreenshotPath: data.__last_screenshot_path ?? null,
  };
}

export function loadAgentConfigFromEnv(env) {
  return {
    templatePath: env.AGENT_TEMPLATE_PATH || 'recordings/template.json',
    screenshotsDir: env.AGENT_SCREENSHOTS_DIR || 'screenshots',
    pollIntervalMs: Number(env.AGENT_POLL_INTERVAL_MS || 5000),
    maxDelayMs: Number(env.AGENT_MAX_DELAY_MS || 5000),
    speed: Number(env.AGENT_SPEED || 1.0),
    replayText: toBool(env.AGENT_REPLAY_TEXT, false),
  };
}

