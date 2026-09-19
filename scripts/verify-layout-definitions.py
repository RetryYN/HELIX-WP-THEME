"""Compare PoC block layout attributes with the dedicated WordPress runtime."""
import hashlib
import json
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
THEME = ROOT / 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt'

def php(code):
    return subprocess.check_output(['docker', 'exec', 'helix-content-wp', 'php', '-r',
                                   'require "/var/www/html/wp-load.php"; ' + code], text=True).strip()

assert php('echo get_option("blogname");') == 'HELIX Content Lab'
supported = json.loads(php('echo json_encode(array_keys(wp_get_layout_definitions()));'))
rows = []
files = [Path(__file__).resolve()]
for file in sorted(THEME.rglob('*')):
    if file.suffix not in ('.php', '.html'):
        continue
    files.append(file)
    for index, match in enumerate(re.finditer(r'<!-- wp:([\w/-]+)\s+(\{.*?\})\s*/?-->', file.read_text())):
        attrs = json.loads(match[2])
        layout = attrs.get('layout', {})
        if 'type' not in layout:
            continue
        rows.append({'name': f'{file.relative_to(ROOT)}:block-{index}:{match[1]}',
                     'type': layout['type'], 'pass': layout['type'] in supported})
assert rows
result = {'completed': True, 'supported': supported,
          'sourceDigests': {str(f.relative_to(ROOT)): hashlib.sha256(f.read_bytes()).hexdigest() for f in files},
          'rows': rows,
          'limitation': 'ソース内のブロックコメントに明示されたlayout.typeの対応検査。PHP展開、保存HTML妥当性、全ブロックの編集・表示は別検証。'}
(ROOT / 'docs/research/2026-09-09-footer-data/layout-definitions.json').write_text(
    json.dumps(result, ensure_ascii=False, indent=2) + '\n')
print(json.dumps({'completed': True, 'checks': len(rows), 'failed': sum(not r['pass'] for r in rows)}))
raise SystemExit(0 if all(r['pass'] for r in rows) else 1)
