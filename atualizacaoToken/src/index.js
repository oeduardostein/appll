const fs = require('fs');
const path = require('path');
const readline = require('readline');
const { execFile } = require('child_process');
const { chromium } = require('playwright');
const { uIOhook, UiohookKey } = require('uiohook-napi');
const robot = require('robotjs');

const TARGET_URL = process.env.TARGET_URL || 'https://www.e-crvsp.sp.gov.br/';
const REPLAY_INTERVAL_MINUTES = Number(process.env.REPLAY_INTERVAL_MINUTES || 10);
const REPLAY_INTERVAL_MS = REPLAY_INTERVAL_MINUTES * 60 * 1000;
const RECORDINGS_DIR = path.join(__dirname, '..', 'recordings');
const RECORDING_FILE = path.join(RECORDINGS_DIR, 'sequence.json');
const CHROME_PROFILE_DIR = path.join(__dirname, '..', 'chrome-profile');
const START_DELAY_MS = 1500;
const REPLAY_X_OFFSET = 0;
const REPLAY_Y_OFFSET = 0;
const REPLAY_PERCENT_OFFSET = 0;
const CALIBRATION_SELECTOR = process.env.CALIBRATION_SELECTOR || '#menu_principal > div > div.navbar-collapse.collapse > ul > li:nth-child(1) > a';
const CALIBRATION_FRAME_SELECTOR = process.env.CALIBRATION_FRAME_SELECTOR || 'frame#frameMain';
const OPTIONAL_DIALOG_FRAME_SELECTOR = process.env.OPTIONAL_DIALOG_FRAME_SELECTOR || 'iframe#GB_frame';
const TOKEN_UPDATE_URL =
  process.env.TOKEN_UPDATE_URL || 'https://applldespachante.skalacode.com/api/update-token';
const SDK_MONITOR_ENABLED = (process.env.SDK_MONITOR_ENABLED || '1') !== '0';
const SDK_MONITOR_PROCESS_NAME = normalizeProcessName(
  process.env.SDK_MONITOR_PROCESS_NAME || 'sdk-desktop.exe'
);
const SDK_MONITOR_PIN = process.env.SDK_MONITOR_PIN || '1234';
const SDK_MONITOR_POLL_MS = parsePositiveInt(process.env.SDK_MONITOR_POLL_MS, 1000);
const SDK_MONITOR_TYPE_DELAY_MS = parsePositiveInt(process.env.SDK_MONITOR_TYPE_DELAY_MS, 2000);
const SDK_MONITOR_COOLDOWN_MS = parsePositiveInt(process.env.SDK_MONITOR_COOLDOWN_MS, 10000);
const TEMPO_RESTANTE_REFRESH_MS = parsePositiveInt(process.env.TEMPO_RESTANTE_REFRESH_MS, 10000);
const TEMPO_RESTANTE_INITIAL_WAIT_MS = parsePositiveInt(
  process.env.TEMPO_RESTANTE_INITIAL_WAIT_MS,
  20000
);
const TEMPO_RESTANTE_POLL_MS = parsePositiveInt(process.env.TEMPO_RESTANTE_POLL_MS, 1000);
const CHROME_LAUNCH_OPTS = {
  headless: false,
  chromiumSandbox: false,
  args: [
    '--no-sandbox',
    '--disable-setuid-sandbox',
    '--disable-dev-shm-usage',
    '--start-maximized',
    '--disable-features=BlockInsecurePrivateNetworkRequests,PrivateNetworkAccessChecks'
  ]
};

ensureDir(RECORDINGS_DIR);
ensureDir(CHROME_PROFILE_DIR);
robot.setMouseDelay(0);
robot.setKeyboardDelay(0);

// Key codes based on iohook scancodes.
const KEY_MAP = {
  1: 'escape',
  2: '1',
  3: '2',
  4: '3',
  5: '4',
  6: '5',
  7: '6',
  8: '7',
  9: '8',
  10: '9',
  11: '0',
  12: '-',
  13: '=',
  14: 'backspace',
  15: 'tab',
  16: 'q',
  17: 'w',
  18: 'e',
  19: 'r',
  20: 't',
  21: 'y',
  22: 'u',
  23: 'i',
  24: 'o',
  25: 'p',
  26: '[',
  27: ']',
  28: 'enter',
  30: 'a',
  31: 's',
  32: 'd',
  33: 'f',
  34: 'g',
  35: 'h',
  36: 'j',
  37: 'k',
  38: 'l',
  39: ';',
  40: '\'',
  41: '`',
  42: 'shift',
  43: '\\',
  44: 'z',
  45: 'x',
  46: 'c',
  47: 'v',
  48: 'b',
  49: 'n',
  50: 'm',
  51: ',',
  52: '.',
  53: '/',
  54: 'shift',
  55: '*',
  56: 'alt',
  57: 'space',
  58: 'capslock',
  59: 'f1',
  60: 'f2',
  61: 'f3',
  62: 'f4',
  63: 'f5',
  64: 'f6',
  65: 'f7',
  66: 'f8',
  67: 'f9',
  68: 'f10',
  87: 'f11',
  88: 'f12',
  91: 'command',
  92: 'command',
  93: 'command',
  3655: 'command',
  3657: 'numlock',
  3658: 'scrolllock',
  3666: 'insert',
  3667: 'printscreen',
  3675: 'command',
  3676: 'command',
  57416: 'up',
  57419: 'left',
  57421: 'right',
  57424: 'down',
  57426: 'pageup',
  57427: 'delete',
  57428: 'end',
  57429: 'pagedown',
  57430: 'insert',
  57434: 'home',
  3613: 'control',
  29: 'control',
  3640: 'alt',
  3639: 'alt'
};

function ensureDir(dir) {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
}

function parsePositiveInt(value, fallback) {
  const parsed = Number(value);
  if (Number.isFinite(parsed) && parsed > 0) return parsed;
  return fallback;
}

function normalizeProcessName(value) {
  const raw = String(value || '').trim();
  if (!raw) return 'sdk-desktop';
  return raw.replace(/\.exe$/i, '');
}

function toPowerShellLiteral(value) {
  return `'${String(value).replace(/'/g, "''")}'`;
}

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function runPowerShell(script) {
  return new Promise((resolve, reject) => {
    execFile(
      'powershell.exe',
      ['-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass', '-Command', script],
      { windowsHide: true, timeout: 5000, maxBuffer: 1024 * 1024 },
      (error, stdout, stderr) => {
        if (error) {
          const details = stderr ? ` ${String(stderr).trim()}` : '';
          reject(new Error(`PowerShell retornou erro.${details}`.trim()));
          return;
        }
        resolve(String(stdout || '').trim());
      }
    );
  });
}

async function focusSdkDesktopWindow() {
  const script = `
$processName = ${toPowerShellLiteral(SDK_MONITOR_PROCESS_NAME)}
$target = Get-Process -Name $processName -ErrorAction SilentlyContinue | Where-Object { $_.MainWindowHandle -ne 0 } | Select-Object -First 1
if (-not $target) { return }
$shell = New-Object -ComObject WScript.Shell
$activated = $shell.AppActivate($target.Id)
if ($activated) { Write-Output $target.Id }
`;
  const output = await runPowerShell(script);
  if (!output) return null;
  const pid = Number(output);
  if (!Number.isInteger(pid) || pid <= 0) return null;
  return pid;
}

function startSdkDesktopWindowMonitor() {
  if (!SDK_MONITOR_ENABLED) {
    return () => {};
  }

  if (process.platform !== 'win32') {
    console.log('Monitor do sdk-desktop desativado: recurso disponivel apenas no Windows.');
    return () => {};
  }

  let stopped = false;
  let isHandling = false;
  let lastHandledPid = null;
  let lastHandledAt = 0;

  const tick = async () => {
    if (stopped || isHandling) return;
    isHandling = true;
    try {
      const pid = await focusSdkDesktopWindow();
      if (!pid) return;

      const now = Date.now();
      const isCooldownActive = pid === lastHandledPid && now - lastHandledAt < SDK_MONITOR_COOLDOWN_MS;
      if (isCooldownActive) return;

      console.log(
        `Janela do ${SDK_MONITOR_PROCESS_NAME}.exe detectada (PID ${pid}). Preenchendo PIN em ${SDK_MONITOR_TYPE_DELAY_MS}ms...`
      );
      await delay(SDK_MONITOR_TYPE_DELAY_MS);
      if (stopped) return;

      robot.typeString(SDK_MONITOR_PIN);
      robot.keyTap('enter');
      lastHandledPid = pid;
      lastHandledAt = Date.now();
    } catch (err) {
      console.warn(`Falha no monitor do sdk-desktop: ${err.message}`);
    } finally {
      isHandling = false;
    }
  };

  const interval = setInterval(() => {
    void tick();
  }, SDK_MONITOR_POLL_MS);

  if (typeof interval.unref === 'function') {
    interval.unref();
  }

  console.log(
    `Monitor do sdk-desktop ativo (processo: ${SDK_MONITOR_PROCESS_NAME}.exe, varredura: ${SDK_MONITOR_POLL_MS}ms).`
  );
  void tick();

  return () => {
    stopped = true;
    clearInterval(interval);
  };
}

function postJson(url, payload) {
  return new Promise((resolve, reject) => {
    const target = new URL(url);
    const data = JSON.stringify(payload);
    const req = require('https').request(
      {
        hostname: target.hostname,
        path: target.pathname + target.search,
        method: 'POST',
        port: target.port || 443,
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(data)
        }
      },
      (res) => {
        let body = '';
        res.on('data', (chunk) => {
          body += chunk;
        });
        res.on('end', () => {
          resolve({ status: res.statusCode, body });
        });
      }
    );
    req.on('error', reject);
    req.write(data);
    req.end();
  });
}

function extractSessionToken(cookies) {
  const php = cookies.find((cookie) => cookie.name === 'PHPSESSID');
  if (php && php.value) return php.value;
  const jsession = cookies.find((cookie) => cookie.name === 'JSESSIONID');
  if (jsession && jsession.value) return jsession.value;
  return null;
}

async function getBrowserViewportBounds(page) {
  return page.evaluate(() => {
    const dpr = window.devicePixelRatio || 1;
    const innerLeft = window.screenX + (window.outerWidth - window.innerWidth) / 2;
    const innerTop = window.screenY + (window.outerHeight - window.innerHeight);
    return {
      left: Math.round(innerLeft * dpr),
      top: Math.round(innerTop * dpr),
      right: Math.round((innerLeft + window.innerWidth) * dpr),
      bottom: Math.round((innerTop + window.innerHeight) * dpr)
    };
  });
}

function waitForEnter(prompt) {
  return new Promise((resolve) => {
    const rl = readline.createInterface({
      input: process.stdin,
      output: process.stdout
    });
    console.log(prompt);
    rl.on('line', () => {
      rl.close();
      resolve();
    });
  });
}

async function captureCalibrationPointManual(label) {
  await waitForEnter(`Calibracao (${label}): posicione o mouse no ponto de referencia e pressione Enter.`);
  const pos = robot.getMousePos();
  console.log(`Calibracao manual registrada em (${pos.x}, ${pos.y}).`);
  return pos;
}

async function captureCalibrationPointAuto(page, label, selector, frameSelector) {
  const target = frameSelector
    ? page.frameLocator(frameSelector).locator(selector)
    : page.locator(selector);
  await target.waitFor({ state: 'visible', timeout: 5000 });
  const box = await target.boundingBox();
  if (!box) {
    throw new Error('Bounding box indisponivel para o seletor.');
  }
  const x = Math.round(box.x + box.width / 2);
  const y = Math.round(box.y + box.height / 2);
  await page.mouse.move(x, y);
  await delay(200);
  const pos = robot.getMousePos();
  console.log(`Calibracao (${label}) via "${selector}" em (${pos.x}, ${pos.y}).`);
  return pos;
}

async function getCalibrationPoint(page, label, selector, frameSelector) {
  try {
    return await captureCalibrationPointAuto(page, label, selector, frameSelector);
  } catch (err) {
    console.warn(`Falha na calibracao automatica (${label}): ${err.message}`);
    return await captureCalibrationPointManual(label);
  }
}

async function getFrameFromSelector(page, frameSelector) {
  if (!frameSelector) return null;
  const frameElement = await page.$(frameSelector);
  if (!frameElement) return null;
  return frameElement.contentFrame();
}

async function startAutoClickNotification(page, frameSelector) {
  const inject = async (target) => {
    await target.evaluate(() => {
      if (window.__autoClickBtnNotificacao) return;
      window.__autoClickBtnNotificacao = true;
      setInterval(() => {
        const btn = document.querySelector('#btn_notificacao');
        if (btn) {
          btn.click();
          const confirmBtn = document.querySelector(
            '#notificacoes > div > div > div.modal-footer > div > div.col-sm-4 > button'
          );
          if (confirmBtn) confirmBtn.click();
        }
      }, 1000);
    });
  };

  try {
    await inject(page);
  } catch (err) {
    console.warn(`Falha ao injetar autoclick no top frame: ${err.message}`);
  }

  try {
    const frame = await getFrameFromSelector(page, frameSelector);
    if (frame) {
      await inject(frame);
    }
  } catch (err) {
    console.warn(`Falha ao injetar autoclick no frame: ${err.message}`);
  }
}

async function setupDomClickListener(page, frameSelector, pushEvent) {
  await page.exposeBinding('recordDomClick', (source, payload) => {
    pushEvent('domclick', payload);
  });
  const frame = await getFrameFromSelector(page, frameSelector);
  const target = frame || page;
  await target.evaluate((frameSelectorValue) => {
    function getXPath(node) {
      if (!node || node.nodeType !== Node.ELEMENT_NODE) return '';
      if (node.id) return `//*[@id="${node.id}"]`;
      const parts = [];
      let current = node;
      while (current && current.nodeType === Node.ELEMENT_NODE) {
        let index = 1;
        let sibling = current.previousElementSibling;
        while (sibling) {
          if (sibling.nodeName === current.nodeName) index += 1;
          sibling = sibling.previousElementSibling;
        }
        parts.unshift(`${current.nodeName.toLowerCase()}[${index}]`);
        current = current.parentElement;
      }
      return '/' + parts.join('/');
    }

    document.addEventListener(
      'click',
      (event) => {
        if (!event.isTrusted) return;
        const xpath = getXPath(event.target);
        window.recordDomClick({
          xpath,
          frameSelector: frameSelectorValue || null,
          clientX: event.clientX,
          clientY: event.clientY
        });
      },
      true
    );
  }, frame ? frameSelector : null);
}

async function computeReplayOffset(page, recordedCalibration) {
  if (!recordedCalibration) {
    return { x: 0, y: 0 };
  }
  const selector = recordedCalibration.selector || CALIBRATION_SELECTOR;
  const frameSelector = recordedCalibration.frameSelector || CALIBRATION_FRAME_SELECTOR;
  const current = await getCalibrationPoint(page, 'replay', selector, frameSelector);
  const offset = {
    x: current.x - recordedCalibration.x,
    y: current.y - recordedCalibration.y
  };
  // Offset calculado desativado no log para reduzir ruido.
  return offset;
}

function buttonName(buttonCode) {
  const buttons = {
    1: 'left',
    2: 'right',
    3: 'middle'
  };
  return buttons[buttonCode] || 'left';
}

function keyCodeToRobotKey(keycode) {
  const key = KEY_MAP[keycode];
  if (key === 'right_shift' || key === 'shift') return 'shift';
  if (key === 'command') return 'command';
  if (key === 'control') return 'control';
  if (key === 'alt') return 'alt';
  return key;
}

let replayPercentOffsetCache = null;
function getReplayPercentOffset() {
  if (replayPercentOffsetCache) return replayPercentOffsetCache;
  const size = robot.getScreenSize();
  replayPercentOffsetCache = {
    x: Math.round(size.width * REPLAY_PERCENT_OFFSET),
    y: Math.round(size.height * REPLAY_PERCENT_OFFSET)
  };
  return replayPercentOffsetCache;
}

function getAdjustedCoords(event, offset = { x: 0, y: 0 }) {
  const percentOffset = getReplayPercentOffset();
  return {
    x: Math.max(0, event.x + offset.x - REPLAY_X_OFFSET - percentOffset.x),
    y: Math.max(0, event.y + offset.y - REPLAY_Y_OFFSET - percentOffset.y)
  };
}

async function focusAndCalibrate(page) {
  try {
    await page.bringToFront();
  } catch (err) {
    console.warn('Nao foi possivel trazer a janela para frente:', err.message);
  }
  try {
    await page.evaluate(() => window.focus());
  } catch (err) {
    console.warn('Nao foi possivel focar a pagina:', err.message);
  }
  await delay(300);
  robot.moveMouse(5, 5);
  robot.mouseClick();
  await delay(300);
}

async function getOrCreateContextPage(context) {
  const pages = context.pages();
  if (pages.length > 0) {
    const primary = pages[0];
    for (let i = 1; i < pages.length; i++) {
      try {
        await pages[i].close();
      } catch (err) {
        console.warn(`Falha ao fechar aba extra: ${err.message}`);
      }
    }
    return primary;
  }
  return context.newPage();
}

async function recordOnceWithBrowser() {
  console.log(`Abrindo navegador em ${TARGET_URL} para iniciar a gravação...`);
  const context = await chromium.launchPersistentContext(CHROME_PROFILE_DIR, {
    ...CHROME_LAUNCH_OPTS,
    viewport: null
  });
  const page = await getOrCreateContextPage(context);
  await page.goto(TARGET_URL);
  await focusAndCalibrate(page);
  await startAutoClickNotification(page, CALIBRATION_FRAME_SELECTOR);
  const calibrationPoint = await getCalibrationPoint(
    page,
    'gravacao',
    CALIBRATION_SELECTOR,
    CALIBRATION_FRAME_SELECTOR
  );
  const viewportBounds = await getBrowserViewportBounds(page);
  let domListenerReady = false;
  await delay(START_DELAY_MS);
  const events = await captureEvents({
    isInsideBrowser: (x, y) =>
      domListenerReady &&
      viewportBounds &&
      x >= viewportBounds.left &&
      x <= viewportBounds.right &&
      y >= viewportBounds.top &&
      y <= viewportBounds.bottom,
    registerDomListener: async (pushEvent) => {
      await setupDomClickListener(page, CALIBRATION_FRAME_SELECTOR, pushEvent);
      domListenerReady = true;
    }
  });
  await context.close();
  return {
    events,
    calibration: {
      ...calibrationPoint,
      selector: CALIBRATION_SELECTOR,
      frameSelector: CALIBRATION_FRAME_SELECTOR
    }
  };
}

function captureEvents(options = {}) {
  return new Promise((resolve) => {
    const events = [];
    const startedAt = Date.now();
    let stopped = false;
    let rl;
    const isInsideBrowser = options.isInsideBrowser || null;
    const registerDomListener = options.registerDomListener || null;

    const stop = () => {
      if (stopped) return;
      stopped = true;
    uIOhook.removeAllListeners();
    try {
        uIOhook.stop();
    } catch (err) {
        console.warn('Erro ao parar iohook:', err.message);
    }
      if (rl) rl.close();
      resolve(events);
    };

    const pushEvent = (type, payload) => {
      if (stopped) return;
      events.push({
        type,
        t: Date.now() - startedAt,
        ...payload
      });
    };

    if (registerDomListener) {
        Promise.resolve()
            .then(() => registerDomListener(pushEvent))
            .catch((err) => {
                console.warn(`Falha ao registrar listener de DOM: ${err.message}`);
            });
    }

    uIOhook.on('mousemove', (event) => {
        pushEvent('mousemove', { x: event.x, y: event.y });
    });

    uIOhook.on('mousewheel', (event) => {
        pushEvent('mousewheel', {
            amount: event.amount,
            rotation: event.rotation,
            direction: event.direction
        });
    });

    uIOhook.on('mousedown', (event) => {
        if (isInsideBrowser && isInsideBrowser(event.x, event.y)) return;
        pushEvent('mousedown', { x: event.x, y: event.y, button: buttonName(event.button) });
    });

    uIOhook.on('mouseup', (event) => {
        if (isInsideBrowser && isInsideBrowser(event.x, event.y)) return;
        pushEvent('mouseup', { x: event.x, y: event.y, button: buttonName(event.button) });
    });

    uIOhook.on('keydown', (event) => {
        pushEvent('keydown', {
            key: keyCodeToRobotKey(event.keycode),
            keycode: event.keycode,
            rawcode: event.rawcode
        });
    });

    uIOhook.on('keyup', (event) => {
        pushEvent('keyup', {
            key: keyCodeToRobotKey(event.keycode),
            keycode: event.keycode,
            rawcode: event.rawcode
        });
    });
    if (typeof uIOhook.registerShortcut === 'function') {
        try {
            // Ctrl + Shift + S to stop recording (best-effort on Linux/Windows).
            uIOhook.registerShortcut([UiohookKey.Ctrl, UiohookKey.Shift, UiohookKey.S], stop);
        } catch (err) {
            console.warn('Atalho Ctrl+Shift+S nao pode ser registrado, use Enter no terminal para parar.', err.message);
        }
    } else {
        console.warn('Atalho Ctrl+Shift+S nao disponivel nesta versao, use Enter no terminal para parar.');
    }

    rl = readline.createInterface({
      input: process.stdin,
      output: process.stdout
    });

    console.log('Gravando cliques e teclas globais...');
    console.log('Pressione Ctrl+Shift+S ou Enter neste terminal para parar.');

    rl.on('line', () => {
        console.log('Encerrando gravação...');
        stop();
    });

    uIOhook.start();
  });
}

function saveRecording(events, calibration) {
  const payload = {
    recordedAt: new Date().toISOString(),
    targetUrl: TARGET_URL,
    intervalMinutes: REPLAY_INTERVAL_MINUTES,
    calibration,
    events
  };
  fs.writeFileSync(RECORDING_FILE, JSON.stringify(payload, null, 2));
  console.log(`Gravação salva em ${RECORDING_FILE}`);
}

function loadRecording() {
  if (!fs.existsSync(RECORDING_FILE)) {
    throw new Error(`Nenhuma gravação encontrada em ${RECORDING_FILE}. Rode "npm start" ou "npm run record" primeiro.`);
  }
  const raw = fs.readFileSync(RECORDING_FILE, 'utf8');
  const parsed = JSON.parse(raw);
  if (!parsed.events || !Array.isArray(parsed.events)) {
    throw new Error('Arquivo de gravação inválido.');
  }
  return { events: parsed.events, calibration: parsed.calibration || null };
}

async function replayEvents(page, events, offset, options = {}) {
  const hasDomClicks = events.some((event) => event.type === 'domclick');
  const isInsideBrowser = hasDomClicks ? options.isInsideBrowser : null;
  let previous = 0;
  for (const event of events) {
    const waitFor = Math.max(0, event.t - previous);
    if (waitFor) {
      await delay(waitFor);
    }
    previous = event.t;
    if (event.type === 'domclick') {
      await performDomClick(page, event);
    } else {
      if (
        isInsideBrowser &&
        (event.type === 'mousemove' || event.type === 'mousedown' || event.type === 'mouseup')
      ) {
        const adjusted = getAdjustedCoords(event, offset);
        if (isInsideBrowser(adjusted.x, adjusted.y)) {
          continue;
        }
      }
      performEvent(event, offset);
    }
  }
}

function performEvent(event, offset = { x: 0, y: 0 }) {
  const adjusted = getAdjustedCoords(event, offset);
  const adjustedX = adjusted.x;
  const adjustedY = adjusted.y;
  switch (event.type) {
    case 'mousemove':
      robot.moveMouse(adjustedX, adjustedY);
      break;
    case 'mousedown':
      robot.moveMouse(adjustedX, adjustedY);
      robot.mouseToggle('down', event.button || 'left');
      break;
    case 'mouseup':
      robot.mouseToggle('up', event.button || 'left');
      break;
    case 'mousewheel': {
      const direction = event.direction && event.direction < 0 ? -1 : 1;
      const delta = (event.rotation || event.amount || 0) * direction;
      robot.scrollMouse(0, delta);
      break;
    }
    case 'keydown':
      if (event.key) {
        robot.keyToggle(event.key, 'down');
      } else {
        console.warn(`Ignorando tecla sem mapeamento (keycode ${event.keycode}, raw ${event.rawcode}).`);
      }
      break;
    case 'keyup':
      if (event.key) {
        robot.keyToggle(event.key, 'up');
      }
      break;
    default:
      console.warn(`Evento desconhecido ignorado: ${event.type}`);
  }
}

async function performDomClick(page, event) {
  if (!event.xpath) {
    console.warn('Clique DOM ignorado: xpath ausente.');
    return;
  }
  const frameSelector = event.frameSelector || CALIBRATION_FRAME_SELECTOR;
  const locator = frameSelector
    ? page.frameLocator(frameSelector).locator(`xpath=${event.xpath}`)
    : page.locator(`xpath=${event.xpath}`);
  try {
    await locator.first().scrollIntoViewIfNeeded();
    await locator.first().click();
  } catch (err) {
    console.warn(`Falha ao clicar no xpath "${event.xpath}": ${err.message}`);
  }
}

async function digitarCPFNoCampo(page) {
  const frame = await getFrameFromSelector(page, CALIBRATION_FRAME_SELECTOR);
  const target = frame || page;
  await target.waitForSelector('#cpf', { state: 'visible', timeout: 15000 });
  await target.evaluate(() => {
    return new Promise((resolve) => {
      const campo = document.querySelector('#cpf');
      if (!campo) {
        console.error("Campo com ID 'cpf' nao encontrado.");
        resolve();
        return;
      }

      const valor = '44922011811';
      campo.value = '';

      let i = 0;
      const intervalo = setInterval(() => {
        if (i < valor.length) {
          const char = valor[i];
          campo.value += char;

          const eventoKeyDown = new KeyboardEvent('keydown', {
            key: char,
            code: `Digit${char}`,
            keyCode: char.charCodeAt(0),
            which: char.charCodeAt(0),
            bubbles: true,
            cancelable: true
          });
          campo.dispatchEvent(eventoKeyDown);

          const eventoInput = new Event('input', { bubbles: true });
          campo.dispatchEvent(eventoInput);

          const eventoKeyUp = new KeyboardEvent('keyup', {
            key: char,
            code: `Digit${char}`,
            keyCode: char.charCodeAt(0),
            which: char.charCodeAt(0),
            bubbles: true,
            cancelable: true
          });
          campo.dispatchEvent(eventoKeyUp);

          i++;
        } else {
          clearInterval(intervalo);
          const eventoChange = new Event('change', { bubbles: true });
          campo.dispatchEvent(eventoChange);
          resolve();
        }
      }, 50);
    });
  });
}

async function clicarContinuar(page) {
  const selector =
    '#conteudo > div.container.container-home > div > div.col-sm-3 > div > div.panel-body > button';
  const frame = await getFrameFromSelector(page, CALIBRATION_FRAME_SELECTOR);
  const target = frame || page;
  await target.waitForSelector(selector, { state: 'visible', timeout: 15000 });
  await target.click(selector);
}

async function digitarSenhaNoCampo(page) {
  const frame = await getFrameFromSelector(page, CALIBRATION_FRAME_SELECTOR);
  const target = frame || page;
  await target.waitForSelector('#senha', { state: 'visible', timeout: 15000 });
  await target.evaluate(() => {
    return new Promise((resolve) => {
      const campo = document.querySelector('#senha');
      if (!campo) {
        console.error("Campo com ID 'senha' nao encontrado.");
        resolve();
        return;
      }

      const valor = '220775Ari*';
      campo.value = '';

      let i = 0;
      const intervalo = setInterval(() => {
        if (i < valor.length) {
          const char = valor[i];
          campo.value += char;

          const eventoKeyDown = new KeyboardEvent('keydown', {
            key: char,
            code: `Key${char.toUpperCase()}`,
            keyCode: char.charCodeAt(0),
            which: char.charCodeAt(0),
            bubbles: true,
            cancelable: true
          });
          campo.dispatchEvent(eventoKeyDown);

          const eventoInput = new Event('input', { bubbles: true });
          campo.dispatchEvent(eventoInput);

          const eventoKeyUp = new KeyboardEvent('keyup', {
            key: char,
            code: `Key${char.toUpperCase()}`,
            keyCode: char.charCodeAt(0),
            which: char.charCodeAt(0),
            bubbles: true,
            cancelable: true
          });
          campo.dispatchEvent(eventoKeyUp);

          i++;
        } else {
          clearInterval(intervalo);
          const eventoChange = new Event('change', { bubbles: true });
          campo.dispatchEvent(eventoChange);
          resolve();
        }
      }, 50);
    });
  });
}


async function clicarEntrar(page) {
  const selector =
    '#conteudo > div.container.container-home > div > div.col-sm-3 > div > div.panel-body > div:nth-child(3) > table > tbody > tr > td > button';
  const frame = await getFrameFromSelector(page, CALIBRATION_FRAME_SELECTOR);
  const target = frame || page;
  await target.waitForSelector(selector, { state: 'visible', timeout: 15000 });
  await target.click(selector);
}

async function clicarSeExistir(page, selector, timeoutMs) {
  const deadline = Date.now() + timeoutMs;
  while (Date.now() < deadline) {
    const frames = page.frames();
    for (const frame of frames) {
      try {
        const locator = frame.locator(selector).first();
        if (await locator.isVisible()) {
          await locator.click();
          return true;
        }
      } catch (err) {
        // ignore and try next frame
      }
    }
    await delay(200);
  }
  return false;
}

async function aguardarEClicarSign(page) {
  const timeoutMs = 30000;
  const selector = '#sign';
  const deadline = Date.now() + timeoutMs;

  while (Date.now() < deadline) {
    const frames = page.frames();
    for (const frame of frames) {
      try {
        const locator = frame.locator(selector).first();
        if (await locator.isVisible()) {
          await locator.click();
          return true;
        }
      } catch (err) {
        // ignore and try next frame
      }
    }
    await delay(500);
  }

  console.warn('Nao foi possivel localizar o botao #sign em nenhum frame.');
  return false;
}

async function contemTempoRestanteEmFrames(page) {
  const frames = page.frames();
  for (const frame of frames) {
    try {
      const found = await frame.evaluate(() => {
        function normalizeText(value) {
          return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
        }
        const body = document.body;
        const rawText = body && body.innerText ? body.innerText : '';
        const text = normalizeText(rawText);
        const hasTempoRestanteText = text.includes('tempo restante');
        const hasSessaoElement = Boolean(document.querySelector('#sessao'));
        return hasTempoRestanteText || hasSessaoElement;
      });
      if (found) return true;
    } catch (err) {
      // ignore frame errors
    }
  }
  return false;
}

async function aguardarTempoRestante(page, timeoutMs, pollMs) {
  const deadline = Date.now() + timeoutMs;
  while (Date.now() < deadline) {
    const found = await contemTempoRestanteEmFrames(page);
    if (found) return true;
    await delay(pollMs);
  }
  return false;
}

async function monitorarTempoRestante(page) {
  const foundInitially = await aguardarTempoRestante(
    page,
    TEMPO_RESTANTE_INITIAL_WAIT_MS,
    TEMPO_RESTANTE_POLL_MS
  );
  if (!foundInitially) {
    console.log(
      `Texto "Tempo restante" nao encontrado apos ${Math.round(
        TEMPO_RESTANTE_INITIAL_WAIT_MS / 1000
      )}s. Seguindo para o proximo ciclo.`
    );
    return false;
  }

  while (true) {
    const found = await contemTempoRestanteEmFrames(page);
    if (!found) {
      console.log('Texto "Tempo restante" nao encontrado. Seguindo para o proximo ciclo.');
      return false;
    }
    console.log(
      `Texto "Tempo restante" encontrado. Atualizando pagina em ${Math.round(
        TEMPO_RESTANTE_REFRESH_MS / 1000
      )}s...`
    );
    await delay(TEMPO_RESTANTE_REFRESH_MS);
    try {
      await page.reload({ waitUntil: 'domcontentloaded' });
      await startAutoClickNotification(page, CALIBRATION_FRAME_SELECTOR);
      await delay(1500);
    } catch (err) {
      console.warn(`Falha ao atualizar pagina: ${err.message}`);
    }
  }
}

async function runAutomationFlow(page, context) {
  await page.goto(TARGET_URL);
  await focusAndCalibrate(page);
  await startAutoClickNotification(page, CALIBRATION_FRAME_SELECTOR);
  await delay(START_DELAY_MS);
  console.log('Etapa: aguardo inicial + CPF');
  await delay(3000);
  await digitarCPFNoCampo(page);
  console.log('Etapa: clique em continuar');
  await clicarContinuar(page);
  console.log('Etapa: aguardo pos-continuar');
  await delay(5000);
  console.log('Etapa: aguardo + senha');
  await delay(3000);
  await digitarSenhaNoCampo(page);
  console.log('Etapa: clique em entrar');
  await clicarEntrar(page);
  console.log('Etapa: aguardo pos-entrar');
  await delay(5000);
  console.log('Etapa: clique opcional');
  const clicked = await clicarSeExistir(
    page,
    'body > form > table > tbody > tr:nth-child(3) > td.texto > table > tbody > tr:nth-child(3) > td:nth-child(2) > table > tbody > tr > td:nth-child(1) > a',
    2000
  );
  if (!clicked) {
    await clicarSeExistir(
      page,
      'a.bt_continuar',
      2000
    );
  }
  console.log('Etapa: aguardo + clique em #sign');
  await delay(5000);
  await aguardarEClicarSign(page);
  await delay(15000);
  try {
    const cookies = await context.cookies();
    const token = extractSessionToken(cookies);
    if (token) {
      const response = await postJson(TOKEN_UPDATE_URL, { token });
      console.log(`Token enviado (${response.status}).`);
    } else {
      console.warn('Nao foi encontrado PHPSESSID/JSESSIONID nos cookies.');
    }
  } catch (err) {
    console.warn(`Falha ao enviar token: ${err.message}`);
  }
  console.log('Automacao concluida.');
}

async function automationLoop() {
  while (true) {
    console.log('Abrindo navegador para executar automacao...');
    const context = await chromium.launchPersistentContext(CHROME_PROFILE_DIR, {
      ...CHROME_LAUNCH_OPTS,
      viewport: null
    });
    const page = await getOrCreateContextPage(context);
    try {
      await runAutomationFlow(page, context);
      const found = await monitorarTempoRestante(page);
      if (!found) {
        await context.close();
        console.log('Navegador fechado para iniciar novo ciclo.');
        continue;
      }
    } catch (err) {
      console.warn(`Falha no ciclo: ${err.message}`);
    }
    console.log(`Aguardando ${REPLAY_INTERVAL_MINUTES} minutos antes do proximo ciclo...`);
    await delay(REPLAY_INTERVAL_MS);
    await context.close();
    console.log('Navegador fechado para iniciar novo ciclo.');
  }
}

async function main() {
  const stopSdkDesktopWindowMonitor = startSdkDesktopWindowMonitor();
  try {
    const mode = (process.argv[2] || 'auto').toLowerCase();
    if (mode === 'record') {
      const { events, calibration } = await recordOnceWithBrowser();
      saveRecording(events, calibration);
      return;
    }

    if (mode === 'play') {
      const { events, calibration } = loadRecording();
      await playbackLoop(events, calibration);
      return;
    }

    await automationLoop();
  } finally {
    stopSdkDesktopWindowMonitor();
  }
}

main().catch((err) => {
  console.error('Erro na execução:', err);
  process.exit(1);
});
