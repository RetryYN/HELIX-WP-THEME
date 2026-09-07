"""Verify independent content management in the dedicated lab. Run without concurrent captures."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

root = Path(__file__).resolve().parent.parent
state = Path(os.environ.get('WTCF_STATE_DIR', str(Path(tempfile.gettempdir()) / 'helix-content-lab')))
cli = ['docker', 'run', '--rm', '--network', 'helix-content-lab', '--env-file', str(state / 'wp.env'),
       '--volumes-from', 'helix-content-wp', '--user', '33:33', 'wordpress:cli-php8.3', 'wp']


def wp(args):
    result = subprocess.run(cli + args, capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError('Lab management check failed; inspect the dedicated container locally')
    return result.stdout.strip()


if wp(['option', 'get', 'blogname']) != 'HELIX Content Lab':
    raise RuntimeError('Dedicated lab required')
probe = '''foreach (wtcf_manifest()["types"] as $type=>$def) {
    $obj=get_post_type_object($type);
    if (!$obj || !$obj->show_ui || !$obj->show_in_rest) { throw new Exception("missing independent type"); }
}
$ids=get_option("wtcf_fixture_ids");
if (get_post_status($ids["unconfirmed"])!=="draft") { throw new Exception("unconfirmed public"); }
$records=array(); foreach ($ids as $key=>$id) {
    $p=get_post($id); $records[$key]=array("id"=>$id,"type"=>$p->post_type,"title"=>$p->post_title,
        "content"=>$p->post_content,"metadata"=>get_post_meta($id,"_wtcf_document",true));
}
echo wp_json_encode($records);'''
before = json.loads(wp(['eval', probe]))
themes = json.loads(wp(['theme', 'list', '--format=json']))
original = next(theme['name'] for theme in themes if theme['status'] == 'active')
alternate = next(theme['name'] for theme in themes if theme['name'] != original)
try:
    wp(['theme', 'activate', alternate])
    after = json.loads(wp(['eval', probe]))
    if before != after:
        raise RuntimeError('Independent records changed when switching theme')
finally:
    wp(['theme', 'activate', original])
if wp(['option', 'get', 'stylesheet']) != original:
    raise RuntimeError('Original theme was not restored')
result = {'schema': 'wt-content-management-evidence.v1', 'completed': True,
          'checks': [{'name': name, 'pass': True} for name in [
              'four-independent-admin-and-rest-types', 'unconfirmed-interview-remains-draft',
              'theme-switch-preserves-types-record-identities-and-content', 'original-theme-restored']],
          'fixture_count': len(before)}
out = root / 'docs/research/2026-09-08-content-faces/results/management.json'
out.write_text(json.dumps(result, indent=2) + '\n')
print('Content management: 4 checks passed; independent records and content preserved; original theme restored')
