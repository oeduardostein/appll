#!/usr/bin/env python3
import argparse
import base64
import json
import os
import re
import sys
from typing import Any, Dict, Optional, Tuple

import requests


SENSITIVE_HEADERS = {"authorization", "cookie", "set-cookie", "proxy-authorization"}


def _redact_headers(headers: Dict[str, str], show_secrets: bool) -> Dict[str, str]:
    if show_secrets:
        return dict(headers)
    redacted: Dict[str, str] = {}
    for name, value in headers.items():
        if name.lower() in SENSITIVE_HEADERS and value:
            redacted[name] = "[REDACTED]"
        else:
            redacted[name] = value
    return redacted


def _har_headers_to_dict(har_headers: Any) -> Dict[str, str]:
    out: Dict[str, str] = {}
    for h in har_headers or []:
        name = h.get("name")
        value = h.get("value")
        if not name:
            continue
        if value is None:
            value = ""
        out[name] = value
    return out


def _strip_hop_by_hop(headers: Dict[str, str]) -> Dict[str, str]:
    hop_by_hop = {
        "connection",
        "host",
        "content-length",
        "accept-encoding",
        "proxy-connection",
        "transfer-encoding",
        "upgrade",
    }
    return {k: v for k, v in headers.items() if k.lower() not in hop_by_hop}


def _find_entry(
    har: Dict[str, Any],
    entry_index: Optional[int],
    url_regex: Optional[str],
) -> Tuple[int, Dict[str, Any]]:
    entries = har.get("log", {}).get("entries", []) or []
    if entry_index is not None:
        if entry_index < 0 or entry_index >= len(entries):
            raise SystemExit(f"--entry-index fora do range: 0..{len(entries) - 1}")
        return entry_index, entries[entry_index]

    pattern = re.compile(url_regex or r"ChkRetorno2HTML", re.IGNORECASE)
    for idx, entry in enumerate(entries):
        url = (entry.get("request", {}) or {}).get("url") or ""
        if pattern.search(url):
            return idx, entry

    raise SystemExit("Nenhuma entry encontrada (tente --url-regex ou --entry-index).")


def _decode_har_response_body(entry: Dict[str, Any]) -> Optional[str]:
    content = (entry.get("response", {}) or {}).get("content", {}) or {}
    text = content.get("text")
    if not text:
        return None

    encoding = content.get("encoding")
    if encoding == "base64":
        try:
            return base64.b64decode(text).decode("utf-8", errors="replace")
        except Exception:
            return base64.b64decode(text).decode("latin-1", errors="replace")
    return text


def _maybe_parse_json(text: str) -> Any:
    try:
        return json.loads(text)
    except Exception:
        return None


def main() -> int:
    ap = argparse.ArgumentParser(
        description=(
            "Analisa um HAR e (opcionalmente) faz replay da requisição de consulta "
            "(ex.: ChkRetorno2HTML), decodificando o retorno."
        )
    )
    ap.add_argument("--har", default="e-system/teste.har", help="Caminho do arquivo .har")
    ap.add_argument("--entry-index", type=int, default=None, help="Índice exato da entry no HAR")
    ap.add_argument("--url-regex", default=None, help="Regex para escolher a entry (default: ChkRetorno2HTML)")
    ap.add_argument(
        "--authorization",
        default=None,
        help=(
            "Header Authorization. Se omitido, usa $AUTHORIZATION. "
            "Evite depender do valor que estiver dentro do HAR."
        ),
    )
    ap.add_argument(
        "--override-param2",
        default=None,
        help="Substitui payload._parameters[2] (ex.: '|94DFAAP16TB015294').",
    )
    ap.add_argument("--show-secrets", action="store_true", help="Mostra headers sensíveis (cuidado).")
    ap.add_argument("--replay", action="store_true", help="Faz replay HTTP da entry selecionada.")
    ap.add_argument("--timeout", type=float, default=60.0, help="Timeout (segundos) para o replay.")
    args = ap.parse_args()

    with open(args.har, "rb") as f:
        har = json.load(f)

    idx, entry = _find_entry(har, args.entry_index, args.url_regex)
    req = entry.get("request", {}) or {}

    method = req.get("method") or "GET"
    url = req.get("url") or ""
    headers = _har_headers_to_dict(req.get("headers"))
    headers = _strip_hop_by_hop(headers)

    if args.authorization is None:
        args.authorization = os.environ.get("AUTHORIZATION")
    if args.authorization:
        headers["Authorization"] = args.authorization

    post_data = (req.get("postData", {}) or {}).get("text") or ""
    if args.override_param2 and post_data:
        try:
            payload = json.loads(post_data)
            params = payload.get("_parameters")
            if isinstance(params, list) and len(params) >= 3:
                params[2] = args.override_param2
                post_data = json.dumps(payload, ensure_ascii=False, separators=(",", ":"))
        except Exception:
            pass

    print(f"Entry: {idx}")
    print(f"Request: {method} {url}")
    print("Headers:", json.dumps(_redact_headers(headers, args.show_secrets), ensure_ascii=False, indent=2))

    decoded_har_body = _decode_har_response_body(entry)
    if decoded_har_body is not None:
        parsed = _maybe_parse_json(decoded_har_body)
        if parsed is not None:
            print("HAR response (decoded JSON):", json.dumps(parsed, ensure_ascii=False))
        else:
            print("HAR response (decoded):", decoded_har_body[:5000])

    if not args.replay:
        return 0

    if method.upper() != "POST":
        print("Replay só implementado para POST neste script. Use --no-replay ou escolha outra entry.")
        return 2

    mime = (req.get("postData", {}) or {}).get("mimeType")
    if mime and "content-type" not in {k.lower() for k in headers.keys()}:
        headers["Content-Type"] = mime

    resp = requests.request(
        method=method.upper(),
        url=url,
        headers=headers,
        data=post_data.encode("utf-8") if isinstance(post_data, str) else post_data,
        timeout=args.timeout,
    )

    print(f"HTTP status: {resp.status_code}")
    content_type = resp.headers.get("content-type", "")
    print(f"Content-Type: {content_type}")

    try:
        print("Replay response JSON:", json.dumps(resp.json(), ensure_ascii=False))
        return 0
    except Exception:
        body = resp.text
        # fallback: algumas APIs respondem base64; tenta decodificar
        try:
            decoded = base64.b64decode(body).decode("utf-8", errors="replace")
            parsed = _maybe_parse_json(decoded)
            if parsed is not None:
                print("Replay response (base64->JSON):", json.dumps(parsed, ensure_ascii=False))
                return 0
            print("Replay response (base64 decoded):", decoded[:5000])
            return 0
        except Exception:
            print("Replay response (text):", body[:5000])
            return 0


if __name__ == "__main__":
    raise SystemExit(main())
