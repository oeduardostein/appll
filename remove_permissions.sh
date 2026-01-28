#!/usr/bin/env bash
set -euo pipefail

find . -name '*.xml' -print0 |
  while IFS= read -r -d '' xml_file; do
    python3 - "$xml_file" <<'PYTHON'
import re
import sys

path = sys.argv[1]
with open(path, "r", encoding="utf-8") as fh:
    content = fh.read()

patterns = [
    re.compile(pattern, flags=re.MULTILINE)
    for pattern in (
        r"<uses-permission\s+android:name=\"android.permission.READ_EXTERNAL_STORAGE\"\s+android:maxSdkVersion=\"32\"\s*/>",
        r"<uses-permission\s+android:name=\"android.permission.READ_MEDIA_IMAGES\"\s*/>",
        r"<uses-permission\s+android:name=\"android.permission.READ_MEDIA_VIDEO\"\s*/>",
        r"<uses-permission\s+android:name=\"android.permission.READ_MEDIA_AUDIO\"\s*/>",
    )
]

original = content
for pattern in patterns:
    content = pattern.sub("", content)

if content != original:
    with open(path, "w", encoding="utf-8") as fh:
        fh.write(content)
PYTHON
  done
