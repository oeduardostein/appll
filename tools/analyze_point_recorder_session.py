#!/usr/bin/env python3
import argparse
import json
from collections import Counter


def main() -> int:
    ap = argparse.ArgumentParser(description="Analisa um session.json do point-recorder.")
    ap.add_argument("path", nargs="?", default="point-recorder/session.json")
    args = ap.parse_args()

    with open(args.path, "r", encoding="utf-8") as f:
        events = json.load(f)

    if not isinstance(events, list):
        raise SystemExit("Arquivo não é uma lista JSON.")

    types = Counter((e.get("type") if isinstance(e, dict) else None) for e in events)
    key_events = [e for e in events if isinstance(e, dict) and e.get("type") in ("key_down", "key_up")]

    chars = []
    for e in key_events:
        ch = e.get("char")
        if isinstance(ch, str) and ch:
            chars.append(ch)

    print("events:", len(events))
    print("types:", dict(types))
    print("key_events:", len(key_events))
    print("chars_captured:", len(chars))
    if chars:
        print("typed_string (best-effort):", "".join(chars))
        print("typed_chars_sample:", chars[:80])
    else:
        print("typed_string: <none>")
        print("Obs: se você só apertou teclas especiais (setas/enter/ctrl), 'keychar' e 'char' ficam null.")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())

