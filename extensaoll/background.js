const API_URL = 'https://applldespachante.skalacode.com/api/update-token';

// Envia o JSESSIONID para a API
async function enviarToken() {
  try {
    const cookies = await chrome.cookies.getAll({ domain: 'www.e-crvsp.sp.gov.br' });
    const jsessionCookie = cookies.find(c => c.name === 'JSESSIONID');

    if (!jsessionCookie) {
      console.log('[ExtensaoLL] JSESSIONID nao encontrado');
      return;
    }

    console.log('[ExtensaoLL] Enviando token...');

    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: jsessionCookie.value })
    });

    console.log('[ExtensaoLL] Resposta:', response.status);

    // Notifica todas as abas
    const tabs = await chrome.tabs.query({ url: 'https://www.e-crvsp.sp.gov.br/*' });
    for (const tab of tabs) {
      chrome.tabs.sendMessage(tab.id, { tipo: 'token_enviado', ok: response.ok }).catch(() => {});
    }
  } catch (error) {
    console.error('[ExtensaoLL] Erro:', error);
  }
}

// Configura alarme para rodar a cada 15s
chrome.alarms.create('enviarToken', { periodInMinutes: 0.25 }); // 15s = 0.25min

chrome.alarms.onAlarm.addListener((alarm) => {
  if (alarm.name === 'enviarToken') {
    enviarToken();
  }
});

// Envia ao iniciar
enviarToken();

console.log('[ExtensaoLL] Background carregado');
