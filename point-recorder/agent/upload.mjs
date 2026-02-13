import fs from 'node:fs';
import path from 'node:path';

export async function uploadScreenshot({ url, apiKey, requestId, filePath }) {
  const buffer = fs.readFileSync(filePath);
  const fileName = path.basename(filePath);

  const form = new FormData();
  form.append('request_id', String(requestId));
  form.append('file', new Blob([buffer], { type: 'image/png' }), fileName);

  const headers = {};
  if (apiKey) headers['X-Public-Api-Key'] = apiKey;

  const resp = await fetch(url, {
    method: 'POST',
    headers,
    body: form,
  });

  const json = await resp.json().catch(() => null);
  if (!resp.ok || !json?.success) {
    const err = json?.error || json?.message || `HTTP ${resp.status}`;
    throw new Error(err);
  }

  return json.data;
}

