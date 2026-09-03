#!/usr/bin/env python3
"""Compare the ability set seen from the three faces (WP-CLI, REST, MCP) and write results.json."""
import json, os, sys
D = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
res = lambda n: json.load(open(os.path.join(D, 'results', n)))
pack = json.load(open(os.path.join(D, 'scripts', 'pack.json')))
names = [a['name'] for a in pack['abilities']]

cli = res('face-a-cli-abilities.json')
rest = {a['name']: a for a in res('face-b-rest-abilities-auth.json')}
mcp = {t['name']: t for t in res('face-c-mcp-wt-pack-tools-list.json')['result']['tools']}
disc = res('face-c-mcp-default-call-discover.json')['result']
disc_names = [a['name'] for a in (disc.get('structuredContent') or json.loads(disc['content'][0]['text']))['abilities']]

def mcp_name(n): return n.replace('/', '-')
def mcp_ann(a):
    ann = a['meta']['annotations']
    return {'readOnlyHint': ann.get('readonly'), 'destructiveHint': ann.get('destructive'), 'idempotentHint': ann.get('idempotent')}

out = {'pack': pack['pack'], 'abilities': {}, 'sets': {}}
for n in names:
    c = cli[n]; r = rest.get(n); m = mcp.get(mcp_name(n))
    row = {
        'cli': {'present': True, 'input_schema': c['input_schema'], 'output_schema': c['output_schema'], 'annotations': c['meta']['annotations'], 'show_in_rest': c['meta']['show_in_rest'], 'mcp_public': c['meta'].get('mcp', {}).get('public')},
        'rest': {'present': r is not None, 'input_schema': r and r.get('input_schema'), 'output_schema': r and r.get('output_schema'), 'annotations': r and r['meta']['annotations']},
        'mcp': {'present': m is not None, 'name': m and m['name'], 'inputSchema': m and m.get('inputSchema'), 'outputSchema': m and m.get('outputSchema'), 'annotations': m and m.get('annotations')},
    }
    ma = row['mcp']['annotations'] or {}
    row['match'] = {
        'name': r is not None and m is not None,
        'input_schema_cli_rest': c['input_schema'] == (r or {}).get('input_schema'),
        'input_schema_cli_mcp': (c['input_schema'] or {'type': 'object'}) == (m or {}).get('inputSchema'),
        'output_schema_cli_rest': c['output_schema'] == (r or {}).get('output_schema'),
        'output_schema_cli_mcp': c['output_schema'] == (m or {}).get('outputSchema'),
        'annotations_cli_rest': c['meta']['annotations'] == (r or {}).get('meta', {}).get('annotations'),
        'annotations_cli_mcp': {k: v for k, v in mcp_ann(c).items() if v is not None} == {k: ma.get(k) for k in mcp_ann(c) if mcp_ann(c)[k] is not None},
        'mcp_annotations_raw': ma,
    }
    out['abilities'][n] = row
out['sets'] = {
    'cli_all': sorted(cli), 'rest_auth': sorted(rest), 'mcp_wt_pack_tools': sorted(mcp),
    'mcp_default_discover': sorted(disc_names),
    'pack_in_cli': all(n in cli for n in names), 'pack_in_rest': all(n in rest for n in names),
    'pack_in_mcp_wt_pack': all(mcp_name(n) in mcp for n in names), 'pack_in_mcp_default_discover': all(n in disc_names for n in names),
}
json.dump(out, open(os.path.join(D, 'results', 'results.json'), 'w'), indent=2, ensure_ascii=False)
for n, row in out['abilities'].items():
    print(n, {k: v for k, v in row['match'].items() if k != 'mcp_annotations_raw'}, row['match']['mcp_annotations_raw'])
print(out['sets'])
