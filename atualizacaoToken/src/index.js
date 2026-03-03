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
const DEFAULT_CHROME_USER_DATA_DIR = CHROME_PROFILE_DIR;
const CHROME_USER_DATA_DIR =
  String(process.env.CHROME_USER_DATA_DIR || DEFAULT_CHROME_USER_DATA_DIR).trim() ||
  DEFAULT_CHROME_USER_DATA_DIR;
const CHROME_PROFILE_DIRECTORY = String(
  process.env.CHROME_PROFILE_NAME || (process.platform === 'win32' ? 'Default' : '')
).trim();
const CHROME_PROFILE_DISPLAY_NAME = String(
  process.env.CHROME_PROFILE_DISPLAY_NAME || (process.platform === 'win32' ? 'LL DESPACHANTE' : '')
).trim();
const CHROME_SOURCE_USER_DATA_DIR = String(
  process.env.CHROME_SOURCE_USER_DATA_DIR || getWindowsChromeDefaultUserDataDir()
).trim();
const CHROME_CHANNEL = String(process.env.CHROME_CHANNEL || 'chrome').trim();
const CHROME_EXECUTABLE_PATH = String(process.env.CHROME_EXECUTABLE_PATH || '').trim();
const CHROME_LAUNCH_TIMEOUT_MS = parsePositiveInt(process.env.CHROME_LAUNCH_TIMEOUT_MS, 60000);
const TARGET_NAV_TIMEOUT_MS = parsePositiveInt(process.env.TARGET_NAV_TIMEOUT_MS, 45000);
const TARGET_NAV_ATTEMPTS = parsePositiveInt(process.env.TARGET_NAV_ATTEMPTS, 3);
const CHROME_FORCE_SOFTWARE_RENDER =
  (process.env.CHROME_FORCE_SOFTWARE_RENDER || '1') !== '0';
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
const BLOCK_EXTERNAL_PAGES = (process.env.BLOCK_EXTERNAL_PAGES || '1') !== '0';
const ALLOW_EXTENSION_INSTALL_FLOW = (process.env.ALLOW_EXTENSION_INSTALL_FLOW || '1') !== '0';
const BLOCK_WEBSTORE_REQUESTS = (process.env.BLOCK_WEBSTORE_REQUESTS || '0') !== '0';
const EXTRA_ALLOWED_PAGE_HOSTS = parseHostsSet(process.env.EXTRA_ALLOWED_PAGE_HOSTS || '');
const CLOSE_GOOGLE_AUX_WINDOWS = (process.env.CLOSE_GOOGLE_AUX_WINDOWS || '1') !== '0';
const GOOGLE_AUX_WINDOW_POLL_MS = parsePositiveInt(process.env.GOOGLE_AUX_WINDOW_POLL_MS, 1500);
const EXTENSION_POPUP_ROBOT_FALLBACK =
  (process.env.EXTENSION_POPUP_ROBOT_FALLBACK || '1') !== '0';
const EXTENSION_POPUP_AUTO_CLOSE =
  (process.env.EXTENSION_POPUP_AUTO_CLOSE || '0') !== '0';
const BLOCK_EXTENSION_INSTALL_PROMPTS =
  (process.env.BLOCK_EXTENSION_INSTALL_PROMPTS || '0') !== '0';
const TARGET_HOSTS = buildTargetHosts(TARGET_URL);
const CHROME_LAUNCH_OPTS = {
  headless: false,
  chromiumSandbox: false,
  args: [
    '--no-sandbox',
    '--disable-setuid-sandbox',
    '--disable-dev-shm-usage',
    '--new-window',
    '--disable-sync',
    '--disable-background-networking',
    '--no-first-run',
    '--no-default-browser-check',
    '--disable-session-crashed-bubble',
    '--hide-crash-restore-bubble',
    '--start-maximized',
    '--disable-features=BlockInsecurePrivateNetworkRequests,PrivateNetworkAccessChecks'
  ]
};
if (CHROME_FORCE_SOFTWARE_RENDER) {
  CHROME_LAUNCH_OPTS.args.push(
    '--disable-gpu',
    '--disable-gpu-compositing',
    '--use-angle=swiftshader'
  );
}

ensureDir(RECORDINGS_DIR);
ensureDir(CHROME_PROFILE_DIR);
ensureDir(CHROME_USER_DATA_DIR);
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

function parseHostsSet(value) {
  return new Set(
    String(value || '')
      .split(',')
      .map((item) => item.trim().toLowerCase())
      .filter(Boolean)
  );
}

function normalizePathForCompare(rawPath) {
  return String(rawPath || '')
    .replace(/\//g, '\\')
    .replace(/\\+$/, '')
    .toLowerCase();
}

function getWindowsChromeDefaultUserDataDir() {
  const localAppData = process.env.LOCALAPPDATA || '';
  if (!localAppData) return '';
  return path.join(localAppData, 'Google', 'Chrome', 'User Data');
}

function isDefaultWindowsChromeUserDataDir(dirPath) {
  if (process.platform !== 'win32') return false;
  const expected = normalizePathForCompare(getWindowsChromeDefaultUserDataDir());
  const current = normalizePathForCompare(dirPath);
  return Boolean(expected) && current === expected;
}

function isDefaultUserDataDirDevToolsError(error) {
  const text = String((error && error.message) || '').toLowerCase();
  return text.includes('devtools remote debugging requires a non-default data directory');
}

function normalizeComparableText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase();
}

function compactComparableText(value) {
  return normalizeComparableText(value).replace(/[^a-z0-9]/g, '');
}

function readJsonFileSafe(filePath) {
  try {
    if (!fs.existsSync(filePath)) return null;
    return JSON.parse(fs.readFileSync(filePath, 'utf8'));
  } catch (err) {
    return null;
  }
}

function findProfileDirectoryByDisplayName(userDataDir, displayName) {
  if (!displayName) return '';
  const localStatePath = path.join(userDataDir, 'Local State');
  const localState = readJsonFileSafe(localStatePath);
  const infoCache = localState && localState.profile && localState.profile.info_cache;
  if (!infoCache || typeof infoCache !== 'object') return '';

  const desired = normalizeComparableText(displayName);
  const desiredCompact = compactComparableText(displayName);
  const entries = Object.entries(infoCache);
  for (const [profileDir, profileInfo] of entries) {
    const profileName = normalizeComparableText(profileInfo && profileInfo.name);
    if (profileName === desired) {
      return profileDir;
    }
  }
  for (const [profileDir, profileInfo] of entries) {
    const profileName = normalizeComparableText(profileInfo && profileInfo.name);
    if (profileName && profileName.includes(desired)) {
      return profileDir;
    }
  }

  // Tenta casar abreviacoes: ex. "LL DESPACHANTE" -> "LLdesp"
  let bestDir = '';
  let bestScore = -1;
  for (const [profileDir, profileInfo] of entries) {
    const profileCompact = compactComparableText(profileInfo && profileInfo.name);
    if (!profileCompact || !desiredCompact) continue;
    if (!(profileCompact.includes(desiredCompact) || desiredCompact.includes(profileCompact))) {
      continue;
    }
    const overlap = Math.min(profileCompact.length, desiredCompact.length);
    if (overlap > bestScore) {
      bestScore = overlap;
      bestDir = profileDir;
    }
  }
  if (bestDir) {
    return bestDir;
  }

  return '';
}

function listProfileDisplayNames(userDataDir) {
  const localStatePath = path.join(userDataDir, 'Local State');
  const localState = readJsonFileSafe(localStatePath);
  const infoCache = localState && localState.profile && localState.profile.info_cache;
  if (!infoCache || typeof infoCache !== 'object') return [];
  return Object.entries(infoCache).map(([profileDir, profileInfo]) => ({
    dir: profileDir,
    name: String((profileInfo && profileInfo.name) || '').trim()
  }));
}

function mergeLocalStateProfileEntry(sourceUserDataDir, targetUserDataDir, profileDirectory) {
  const sourceLocalStatePath = path.join(sourceUserDataDir, 'Local State');
  const targetLocalStatePath = path.join(targetUserDataDir, 'Local State');
  const sourceLocalState = readJsonFileSafe(sourceLocalStatePath);
  if (!sourceLocalState || !sourceLocalState.profile || !sourceLocalState.profile.info_cache) return;
  const sourceEntry = sourceLocalState.profile.info_cache[profileDirectory];
  if (!sourceEntry) return;

  const targetLocalState = readJsonFileSafe(targetLocalStatePath) || {};
  if (!targetLocalState.profile || typeof targetLocalState.profile !== 'object') {
    targetLocalState.profile = {};
  }
  if (
    !targetLocalState.profile.info_cache ||
    typeof targetLocalState.profile.info_cache !== 'object'
  ) {
    targetLocalState.profile.info_cache = {};
  }

  targetLocalState.profile.info_cache[profileDirectory] = sourceEntry;
  targetLocalState.profile.last_used = profileDirectory;
  fs.writeFileSync(targetLocalStatePath, JSON.stringify(targetLocalState, null, 2));
}

function ensureProfileDirectoryAvailable(targetUserDataDir, profileDirectory) {
  if (!profileDirectory) return '';
  const targetProfilePath = path.join(targetUserDataDir, profileDirectory);
  if (fs.existsSync(targetProfilePath)) {
    return profileDirectory;
  }
  return '';
}

function ensureProfileByDisplayName(targetUserDataDir, displayName, sourceUserDataDir) {
  if (!displayName) return '';

  const existing = findProfileDirectoryByDisplayName(targetUserDataDir, displayName);
  const existingAvailable = ensureProfileDirectoryAvailable(targetUserDataDir, existing);
  if (existingAvailable) {
    return existingAvailable;
  }

  const sourceProfileDir = findProfileDirectoryByDisplayName(sourceUserDataDir, displayName);
  if (!sourceProfileDir) {
    const knownProfiles = listProfileDisplayNames(sourceUserDataDir)
      .map((item) => `${item.dir}=>${item.name}`)
      .join(', ');
    console.warn(
      `Perfil "${displayName}" nao encontrado em ${sourceUserDataDir}. Perfis disponiveis: ${
        knownProfiles || '(nenhum encontrado)'
      }. Usando perfil configurado.`
    );
    return '';
  }

  const sourceProfilePath = path.join(sourceUserDataDir, sourceProfileDir);
  const targetProfilePath = path.join(targetUserDataDir, sourceProfileDir);
  ensureDir(targetUserDataDir);

  if (!fs.existsSync(sourceProfilePath)) {
    console.warn(`Diretorio do perfil origem nao encontrado: ${sourceProfilePath}`);
    return '';
  }

  if (!fs.existsSync(targetProfilePath)) {
    console.log(
      `Copiando perfil "${displayName}" (${sourceProfileDir}) para ambiente de automacao...`
    );
    try {
      fs.cpSync(sourceProfilePath, targetProfilePath, { recursive: true });
    } catch (err) {
      console.warn(
        `Falha ao copiar perfil "${displayName}". Feche o Chrome manual e tente novamente. Detalhe: ${err.message}`
      );
      return '';
    }
  }

  try {
    mergeLocalStateProfileEntry(sourceUserDataDir, targetUserDataDir, sourceProfileDir);
  } catch (err) {
    console.warn(`Falha ao atualizar Local State do perfil importado: ${err.message}`);
  }

  return sourceProfileDir;
}

function resolveProfileDirectoryForUserDataDir(userDataDir) {
  if (CHROME_PROFILE_DISPLAY_NAME) {
    const resolvedByName = ensureProfileByDisplayName(
      userDataDir,
      CHROME_PROFILE_DISPLAY_NAME,
      CHROME_SOURCE_USER_DATA_DIR
    );
    if (resolvedByName) {
      return resolvedByName;
    }
  }
  return CHROME_PROFILE_DIRECTORY;
}

function buildTargetHosts(url) {
  try {
    const host = new URL(url).hostname.toLowerCase();
    const set = new Set([host]);
    if (host.startsWith('www.')) {
      set.add(host.slice(4));
    } else {
      set.add(`www.${host}`);
    }
    return set;
  } catch (err) {
    console.warn(`TARGET_URL invalida para calculo de host permitido: ${err.message}`);
    return new Set();
  }
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

function withTimeout(promise, timeoutMs, label) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => {
      reject(new Error(`${label} excedeu ${timeoutMs}ms.`));
    }, timeoutMs);

    Promise.resolve(promise)
      .then((value) => {
        clearTimeout(timer);
        resolve(value);
      })
      .catch((error) => {
        clearTimeout(timer);
        reject(error);
      });
  });
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

function hostMatchesAllowList(hostname, allowList) {
  if (!hostname) return false;
  if (allowList.has(hostname)) return true;
  for (const allowed of allowList) {
    if (hostname.endsWith(`.${allowed}`)) return true;
  }
  return false;
}

function isAllowedTopLevelPageUrl(rawUrl) {
  const normalized = String(rawUrl || '').trim().toLowerCase();
  if (!normalized) return true;
  if (
    normalized === 'about:blank' ||
    normalized.startsWith('about:') ||
    normalized.startsWith('data:') ||
    normalized.startsWith('blob:') ||
    normalized.startsWith('chrome-error://')
  ) {
    return true;
  }

  if (
    normalized.startsWith('chrome://') ||
    normalized.startsWith('edge://') ||
    normalized.startsWith('chrome-extension://')
  ) {
    return false;
  }

  let host;
  try {
    host = new URL(rawUrl).hostname.toLowerCase();
  } catch (err) {
    return false;
  }

  if (
    ALLOW_EXTENSION_INSTALL_FLOW &&
    (host === 'chrome.google.com' || host === 'chromewebstore.google.com')
  ) {
    return true;
  }

  if (hostMatchesAllowList(host, TARGET_HOSTS)) return true;
  if (hostMatchesAllowList(host, EXTRA_ALLOWED_PAGE_HOSTS)) return true;
  return false;
}

function isBlockedInstallRequestUrl(rawUrl) {
  const url = String(rawUrl || '').toLowerCase();
  return (
    url.includes('chrome.google.com/webstore') ||
    url.includes('chromewebstore.google.com') ||
    url.includes('clients2.google.com/service/update2/crx')
  );
}

async function installContextGuards(context) {
  if (!BLOCK_EXTERNAL_PAGES) return;

  const closeIfDisallowed = async (page, reason) => {
    const currentUrl = page.url();
    if (isAllowedTopLevelPageUrl(currentUrl)) return;
    try {
      console.warn(
        `Pagina externa bloqueada (${reason}): ${currentUrl || '(url vazia)'}. Fechando automaticamente.`
      );
      await page.close({ runBeforeUnload: false });
    } catch (err) {
      console.warn(`Falha ao fechar pagina externa: ${err.message}`);
    }
  };

  const attachGuard = (page) => {
    void closeIfDisallowed(page, 'abertura');
    page.on('framenavigated', (frame) => {
      if (frame !== page.mainFrame()) return;
      void closeIfDisallowed(page, 'navegacao');
    });
  };

  context.pages().forEach(attachGuard);
  context.on('page', attachGuard);

  await context.route('**/*', async (route) => {
    const requestUrl = route.request().url();
    if (BLOCK_WEBSTORE_REQUESTS && isBlockedInstallRequestUrl(requestUrl)) {
      console.warn(`Requisicao de extensao/WebStore bloqueada: ${requestUrl}`);
      await route.abort();
      return;
    }
    await route.continue();
  });
}

async function installExtensionPromptBlocker(context) {
  if (!BLOCK_EXTENSION_INSTALL_PROMPTS) return;

  await context.addInitScript(() => {
    const blockReason = 'Instalacao de extensao bloqueada pela automacao.';

    function installStub(...args) {
      const failureCallback = args.find((value) => typeof value === 'function');
      if (failureCallback) {
        setTimeout(() => {
          try {
            failureCallback(blockReason);
          } catch (err) {
            // ignora callback quebrado da pagina
          }
        }, 0);
      }
      return undefined;
    }

    function patchChromeWebstoreInstall() {
      const chromeApi = window.chrome || (window.chrome = {});
      const webstoreApi = chromeApi.webstore || (chromeApi.webstore = {});
      try {
        Object.defineProperty(webstoreApi, 'install', {
          configurable: true,
          writable: true,
          value: installStub
        });
      } catch (err) {
        webstoreApi.install = installStub;
      }
    }

    patchChromeWebstoreInstall();
    setInterval(patchChromeWebstoreInstall, 1000);
  });
}

async function launchBrowserContext() {
  const launchOptions = {
    ...CHROME_LAUNCH_OPTS,
    args: [...CHROME_LAUNCH_OPTS.args],
    viewport: null,
    timeout: CHROME_LAUNCH_TIMEOUT_MS
  };

  if (CHROME_EXECUTABLE_PATH) {
    launchOptions.executablePath = CHROME_EXECUTABLE_PATH;
  } else if (CHROME_CHANNEL) {
    launchOptions.channel = CHROME_CHANNEL;
  }

  const browserDescriptor = CHROME_EXECUTABLE_PATH
    ? `executablePath=${CHROME_EXECUTABLE_PATH}`
    : `channel=${CHROME_CHANNEL || 'chromium_padrao'}`;
  let userDataDirInUse = CHROME_USER_DATA_DIR;
  let profileDirectoryInUse = resolveProfileDirectoryForUserDataDir(userDataDirInUse);
  launchOptions.args = CHROME_LAUNCH_OPTS.args.filter(
    (arg) => !String(arg).startsWith('--profile-directory=')
  );
  if (profileDirectoryInUse) {
    launchOptions.args.push(`--profile-directory=${profileDirectoryInUse}`);
  }
  console.log(
    `Iniciando navegador (${browserDescriptor}, userDataDir=${userDataDirInUse}${
      profileDirectoryInUse ? `, profile=${profileDirectoryInUse}` : ''
    }).`
  );

  let context;
  let launchError = null;
  try {
    context = await chromium.launchPersistentContext(userDataDirInUse, launchOptions);
  } catch (err) {
    launchError = err;
    const canFallbackToIsolatedProfile =
      isDefaultWindowsChromeUserDataDir(userDataDirInUse) || isDefaultUserDataDirDevToolsError(err);
    if (!canFallbackToIsolatedProfile) {
      throw new Error(
        `Falha ao iniciar Chrome (${browserDescriptor}, profile=${profileDirectoryInUse || 'padrao'}). ` +
          `Detalhe: ${err.message}`
      );
    }

    userDataDirInUse = CHROME_PROFILE_DIR;
    ensureDir(userDataDirInUse);
    profileDirectoryInUse = resolveProfileDirectoryForUserDataDir(userDataDirInUse);
    launchOptions.args = CHROME_LAUNCH_OPTS.args.filter(
      (arg) => !String(arg).startsWith('--profile-directory=')
    );
    if (profileDirectoryInUse) {
      launchOptions.args.push(`--profile-directory=${profileDirectoryInUse}`);
    }
    console.warn(
      'Chrome bloqueou o perfil padrao para automacao. Tentando novamente com perfil isolado: ' +
        userDataDirInUse +
        (profileDirectoryInUse ? ` (profile=${profileDirectoryInUse})` : '')
    );
    try {
      context = await chromium.launchPersistentContext(userDataDirInUse, launchOptions);
    } catch (fallbackErr) {
      throw new Error(
        `Falha ao iniciar Chrome (${browserDescriptor}) no perfil padrao e no perfil isolado. ` +
          `Erro padrao: ${err.message}. Erro isolado: ${fallbackErr.message}`
      );
    }
  }

  if (launchError && userDataDirInUse === CHROME_PROFILE_DIR) {
    console.log('Navegador iniciado com perfil isolado de automacao.');
  }
  console.log('Navegador iniciado. Aplicando guardas de contexto...');
  await installExtensionPromptBlocker(context);
  await installContextGuards(context);
  try {
    await getOrCreateContextPage(context, { timeoutMs: 5000 });
  } catch (err) {
    console.warn(`Falha ao preparar segunda aba na inicializacao: ${err.message}`);
  }
  return context;
}

function isBlankLikeUrl(rawUrl) {
  const normalized = String(rawUrl || '').trim().toLowerCase();
  if (!normalized) return true;
  return normalized === 'about:blank' || normalized.startsWith('about:');
}

async function navigateToTarget(initialPage, context) {
  const waitForTarget = (page) =>
    page.waitForURL(
      (url) => {
        try {
          const host = url.hostname.toLowerCase();
          return hostMatchesAllowList(host, TARGET_HOSTS);
        } catch (err) {
          return false;
        }
      },
      { timeout: TARGET_NAV_TIMEOUT_MS }
    );

  const maxAttempts = Math.max(1, TARGET_NAV_ATTEMPTS);
  let page = initialPage;
  let lastError = null;

  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    console.log(`Navegando para ${TARGET_URL} (tentativa ${attempt}/${maxAttempts})...`);

    try {
      await page.bringToFront();
    } catch (err) {
      console.warn(`Falha ao trazer aba para frente: ${err.message}`);
    }

    try {
      await page.goto(TARGET_URL, { waitUntil: 'domcontentloaded', timeout: TARGET_NAV_TIMEOUT_MS });
      await waitForTarget(page);
      if (isBlankLikeUrl(page.url())) {
        throw new Error('A pagina permaneceu em branco apos o goto.');
      }
      console.log(`Navegacao concluida em: ${page.url()}`);
      return page;
    } catch (firstErr) {
      lastError = firstErr;
      console.warn(`Navegacao inicial falhou (${firstErr.message}). Tentando forcar location.href...`);
      try {
        await page.evaluate((url) => {
          window.location.href = url;
        }, TARGET_URL);
        await waitForTarget(page);
        if (isBlankLikeUrl(page.url())) {
          throw new Error('A pagina permaneceu em branco apos location.href.');
        }
        console.log(`Navegacao concluida em: ${page.url()}`);
        return page;
      } catch (secondErr) {
        lastError = secondErr;
        console.warn(`Falha na tentativa ${attempt}: ${secondErr.message}`);
      }
    }

    if (attempt < maxAttempts) {
      page = await getOrCreateContextPage(context, { forceNew: true });
    }
  }

  const currentUrl = page && !page.isClosed() ? page.url() : '(aba indisponivel)';
  throw new Error(
    `Nao foi possivel navegar para ${TARGET_URL} apos ${maxAttempts} tentativa(s). ` +
      `Ultima URL: ${currentUrl}. Detalhe: ${lastError ? lastError.message : 'sem detalhes'}`
  );
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

async function closeGoogleAuxWindows() {
  const script = `
$targets = Get-Process -ErrorAction SilentlyContinue | Where-Object {
  $_.MainWindowHandle -ne 0 -and
  $_.ProcessName -match '^Google' -and
  $_.ProcessName -notmatch '^(chrome|chromium)$'
}
foreach ($p in $targets) {
  try {
    if (-not $p.CloseMainWindow()) {
      Stop-Process -Id $p.Id -Force -ErrorAction Stop
    } else {
      Start-Sleep -Milliseconds 300
      if (-not $p.HasExited) {
        Stop-Process -Id $p.Id -Force -ErrorAction Stop
      }
    }
    Write-Output "$($p.ProcessName):$($p.Id)"
  } catch {
    # ignora falhas individuais
  }
}
`;
  const output = await runPowerShell(script);
  if (!output) return [];
  return output
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean);
}

function startGoogleAuxWindowMonitor() {
  if (!CLOSE_GOOGLE_AUX_WINDOWS) {
    return () => {};
  }

  if (process.platform !== 'win32') {
    console.log('Monitor de janelas Google desativado: recurso disponivel apenas no Windows.');
    return () => {};
  }

  let stopped = false;
  let isHandling = false;

  const tick = async () => {
    if (stopped || isHandling) return;
    isHandling = true;
    try {
      const closed = await closeGoogleAuxWindows();
      if (closed.length > 0) {
        console.log(`Janelas Google auxiliares fechadas: ${closed.join(', ')}`);
      }
    } catch (err) {
      console.warn(`Falha ao fechar janelas Google auxiliares: ${err.message}`);
    } finally {
      isHandling = false;
    }
  };

  const interval = setInterval(() => {
    void tick();
  }, GOOGLE_AUX_WINDOW_POLL_MS);

  if (typeof interval.unref === 'function') {
    interval.unref();
  }

  console.log(`Monitor de janelas Google ativo (varredura: ${GOOGLE_AUX_WINDOW_POLL_MS}ms).`);
  void tick();

  return () => {
    stopped = true;
    clearInterval(interval);
  };
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

async function tryOpenNewTabByKeyboard(context, basePage) {
  const shortcut = process.platform === 'darwin' ? 'Meta+T' : 'Control+T';
  try {
    await basePage.bringToFront();
  } catch (err) {
    console.warn(`Falha ao focar aba base para atalho de nova aba: ${err.message}`);
  }

  let waitNewPage = context.waitForEvent('page', { timeout: 4000 }).catch(() => null);
  try {
    await basePage.keyboard.press(shortcut);
  } catch (err) {
    console.warn(`Falha ao abrir nova aba via Playwright (${shortcut}): ${err.message}`);
  }
  let created = await waitNewPage;
  if (created) return created;

  if (EXTENSION_POPUP_ROBOT_FALLBACK) {
    const modifier = process.platform === 'darwin' ? 'command' : 'control';
    waitNewPage = context.waitForEvent('page', { timeout: 4000 }).catch(() => null);
    try {
      robot.keyTap('t', modifier);
    } catch (err) {
      console.warn(`Falha ao abrir nova aba via RobotJS (${modifier}+t): ${err.message}`);
    }
    created = await waitNewPage;
  }
  return created;
}

async function getOrCreateContextPage(context, options = {}) {
  const forceNew = options.forceNew === true;
  const timeoutMs = parsePositiveInt(options.timeoutMs, 8000);
  const openPages = () => context.pages().filter((candidate) => !candidate.isClosed());

  if (!forceNew) {
    const pages = openPages();
    if (pages.length >= 2) {
      const secondTab = pages[1];
      try {
        await secondTab.bringToFront();
      } catch (err) {
        console.warn(`Falha ao trazer a segunda aba para frente: ${err.message}`);
      }
      console.log('Usando segunda aba de automacao.');
      return secondTab;
    }
  }

  const currentPages = openPages();
  const basePage = currentPages[0] || null;
  let newPage = null;
  try {
    newPage = await withTimeout(context.newPage(), timeoutMs, 'Abertura de nova aba');
  } catch (err) {
    console.warn(`Falha ao abrir nova aba via API: ${err.message}`);
  }

  if (!newPage && basePage) {
    newPage = await tryOpenNewTabByKeyboard(context, basePage);
  }

  if (!newPage) {
    if (basePage) {
      try {
        await basePage.bringToFront();
      } catch (err) {
        console.warn(`Falha ao trazer aba base para frente: ${err.message}`);
      }
      console.warn('Seguindo com aba base por falta de segunda aba.');
      return basePage;
    }
    throw new Error('Nenhuma aba disponivel para automacao.');
  }

  try {
    await newPage.bringToFront();
  } catch (err) {
    console.warn(`Falha ao trazer a nova aba para frente: ${err.message}`);
  }
  console.log('Aba de automacao criada. Executando fluxo nela.');
  return newPage;
}

async function recordOnceWithBrowser() {
  console.log(`Abrindo navegador em ${TARGET_URL} para iniciar a gravação...`);
  const context = await launchBrowserContext();
  let page = await getOrCreateContextPage(context);
  page = await navigateToTarget(page, context);
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

async function digitarNoCampoComPlaywright(page, selector, valor, delayMs = 90) {
  const frame = await getFrameFromSelector(page, CALIBRATION_FRAME_SELECTOR);
  const target = frame || page;
  const locator = target.locator(selector).first();
  await locator.waitFor({ state: 'visible', timeout: 15000 });
  await locator.click({ timeout: 5000 });
  await locator.fill('');
  await locator.type(String(valor), { delay: delayMs });
  await page.keyboard.press('Tab');
  await delay(150);
}

async function digitarCPFNoCampo(page) {
  await digitarNoCampoComPlaywright(page, '#cpf', '44922011811', 85);
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
  await digitarNoCampoComPlaywright(page, '#senha', '220775Ari*', 95);
}


async function clicarEntrar(page) {
  const selector =
    '#conteudo > div.container.container-home > div > div.col-sm-3 > div > div.panel-body > div:nth-child(3) > table > tbody > tr > td > button';
  const frame = await getFrameFromSelector(page, CALIBRATION_FRAME_SELECTOR);
  const target = frame || page;
  await target.waitForSelector(selector, { state: 'visible', timeout: 15000 });
  await target.click(selector);
}

async function pressionarEnterParaFecharPopupExtensao(page) {
  if (!EXTENSION_POPUP_AUTO_CLOSE) {
    return;
  }

  try {
    await page.bringToFront();
  } catch (err) {
    console.warn(`Falha ao trazer aba para frente antes do Enter: ${err.message}`);
  }

  try {
    await page.evaluate(() => window.focus());
  } catch (err) {
    console.warn(`Falha ao focar pagina antes do Enter: ${err.message}`);
  }

  await delay(300);
  try {
    await page.keyboard.press('Escape', { delay: 60 });
  } catch (err) {
    console.warn(`Falha ao enviar Esc via Playwright: ${err.message}`);
  }

  if (EXTENSION_POPUP_ROBOT_FALLBACK) {
    robot.keyTap('escape');
  }

  await delay(250);
  try {
    await page.keyboard.press('Enter', { delay: 80 });
  } catch (err) {
    console.warn(`Falha ao enviar Enter via Playwright: ${err.message}`);
  }

  if (EXTENSION_POPUP_ROBOT_FALLBACK) {
    robot.keyTap('enter');
  }
  await delay(300);
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
  page = await navigateToTarget(page, context);
  await focusAndCalibrate(page);
  await startAutoClickNotification(page, CALIBRATION_FRAME_SELECTOR);
  await delay(START_DELAY_MS);
  console.log('Etapa: aguardo inicial + CPF');
  await delay(3000);
  await digitarCPFNoCampo(page);
  console.log('Etapa: fechamento preventivo de popup da extensao');
  await pressionarEnterParaFecharPopupExtensao(page);
  console.log('Etapa: clique em continuar');
  await clicarContinuar(page);
  console.log('Etapa: aguardo pos-continuar');
  await delay(5000);
  await pressionarEnterParaFecharPopupExtensao(page);
  console.log('Etapa: aguardo + senha');
  await delay(3000);
  await digitarSenhaNoCampo(page);
  console.log('Etapa: clique em entrar');
  await clicarEntrar(page);
  console.log('Etapa: Enter para fechar popup da extensao');
  await pressionarEnterParaFecharPopupExtensao(page);
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
    const context = await launchBrowserContext();
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
  const stopGoogleAuxWindowMonitor = startGoogleAuxWindowMonitor();
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
    stopGoogleAuxWindowMonitor();
    stopSdkDesktopWindowMonitor();
  }
}

main().catch((err) => {
  console.error('Erro na execução:', err);
  process.exit(1);
});
