// Content script - roda em todos os frames do e-crvsp

// ========== AVISO VISUAL ==========
if (window === window.top) {
  const dot = document.createElement('div');
  dot.id = 'extensaoll-indicator';
  dot.innerHTML = '●';
  dot.style.cssText = 'position:fixed;bottom:15px;right:15px;font-size:20px;color:#2196F3;z-index:999999;cursor:pointer;user-select:none;';
  dot.title = 'Extensao LL - aguardando';
  document.body.appendChild(dot);

  chrome.runtime.onMessage.addListener((msg) => {
    if (msg.tipo === 'token_enviado') {
      dot.style.color = msg.ok ? '#4CAF50' : '#F44336';
      dot.innerHTML = msg.ok ? '✓' : '✗';
      dot.title = msg.ok ? 'Enviado!' : 'Erro!';
      console.log('[ExtensaoLL] Token enviado, OK:', msg.ok);
      setTimeout(() => {
        dot.style.color = '#2196F3';
        dot.innerHTML = '●';
        dot.title = 'Extensao LL - aguardando';
      }, 2000);
    }
  });

  console.log('[ExtensaoLL] Content script carregado - indicador criado');
}

// ========== FECHAR ALERTS AUTOMATICAMENTE ==========
window.alert = function() {
  console.log('[ExtensaoLL] Alert bloqueado:', arguments[0]);
  return true;
};

// Tambem intercepta confirm() e prompt()
window.confirm = function() {
  console.log('[ExtensaoLL] Confirm bloqueado, retornando true');
  return true;
};

window.prompt = function() {
  console.log('[ExtensaoLL] Prompt bloqueado');
  return null;
};

console.log('[ExtensaoLL] Alerts interceptados');

// ========== RELOAD CONDICIONAL A CADA 15s ==========
const INTERVALO_RELOAD_MS = 15000;
const TEXTO_SAUDACAO_ALVO = 'Olá, ARIANE NEVES FERREIRA DIAS!';

function encontrarSaudacaoAlvo() {
  const elementos = document.querySelectorAll('span.nome');
  for (const el of elementos) {
    const texto = (el.textContent || '').replace(/\s+/g, ' ').trim();
    if (texto === TEXTO_SAUDACAO_ALVO) {
      return true;
    }
  }
  return false;
}

function verificarSaudacaoEAtualizar() {
  if (window !== window.top) return;
  if (encontrarSaudacaoAlvo()) {
    console.log('[ExtensaoLL] Saudacao alvo encontrada. Atualizando pagina...');
    window.location.reload();
  }
}

// ========== CLIQUE EM BOTOES ==========
function clicarBotao(textoAlvo) {
  const elementos = document.querySelectorAll('button, input[type="submit"], input[type="button"], a');
  for (const el of elementos) {
    const texto = el.textContent || el.value || '';
    if (texto.trim().toLowerCase().includes(textoAlvo.toLowerCase())) {
      console.log(`[ExtensaoLL] Botao "${textoAlvo}" encontrado, clicando em 5s...`);
      setTimeout(() => el.click(), 5000);
      return true;
    }
  }
  return false;
}

// ========== PREENCHER LOGIN ==========
function preencherLogin() {
  const campoCpf = document.querySelector('input#cpf[name="codigo"]');
  if (campoCpf && !campoCpf.value) {
    campoCpf.value = '44922011811';
    campoCpf.dispatchEvent(new Event('input', { bubbles: true }));
    console.log('[ExtensaoLL] CPF preenchido, clicando Continuar em 2s...');
    setTimeout(() => {
      const btnContinuar = document.querySelector('button[onclick="login();"]');
      if (btnContinuar) btnContinuar.click();
    }, 2000);
  }
}

// ========== EXECUTAR ==========
function executarAcoes() {
  clicarBotao('Autenticar');
  clicarBotao('Encerrar sess');
  preencherLogin();
}

executarAcoes();

const observer = new MutationObserver(executarAcoes);
observer.observe(document.body, { childList: true, subtree: true });

if (window === window.top) {
  setInterval(verificarSaudacaoEAtualizar, INTERVALO_RELOAD_MS);
  console.log('[ExtensaoLL] Verificacao de saudacao ativada (15s).');
}
