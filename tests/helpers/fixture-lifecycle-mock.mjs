const base64 = value => Buffer.from(value).toString('base64');
export const absentOption = { exists: false, type: null, valueBase64: null, autoloadBase64: null };
export const option = (raw, type = 'string', autoload = 'auto-off') => ({
  exists: true, type, valueBase64: base64(raw), autoloadBase64: base64(autoload),
});

export function mockWp(initial = absentOption, config = {}) {
  let state = structuredClone(initial);
  let nextId = 100;
  const posts = new Map(Object.entries(config.existingPosts || {}).map(([slug, id]) => [String(id), { slug }]));
  const calls = [];
  const stages = [];
  const counts = new Map();
  const stage = (name, args) => {
    stages.push(name);
    const count = (counts.get(name) || 0) + 1;
    counts.set(name, count);
    if (config.fail?.(name, args, count)) throw new Error('Injected ' + name + ' failure');
  };
  const wp = args => {
    calls.push([...args]);
    if (args[0] === 'eval') {
      if (args[1].includes('/* fixture-mode snapshot */')) {
        stage('snapshot', args);
        return config.snapshotOutput ?? JSON.stringify(state);
      }
      if (args[1].includes('/* fixture-mode restore */')) {
        stage('restore', args);
        const encoded = /base64_decode\('([A-Za-z0-9+/=]+)', true\)/.exec(args[1])[1];
        if (!config.restoreNoop) state = JSON.parse(Buffer.from(encoded, 'base64').toString());
        stage('restore-after', args);
        return config.restoreOutput ?? 'restored';
      }
      return 'fixture-nonce';
    }
    if (args[0] === 'option') {
      if (args[1] === 'get' && args[2] === 'blogname') return 'HELIX Content Lab';
      if (args[1] === 'update' && args[2] === 'wtcf_event_fixture_mode') {
        stage('enable', args);
        state = option(args[3], 'string', 'auto');
        stage('enable-after', args);
        return 'Success';
      }
      if (args[1] === 'delete' && args[2] === 'wtcf_event_fixture_mode') {
        stage('disable', args);
        state = structuredClone(absentOption);
        return 'Success';
      }
    }
    if (args[0] === 'post' && args[1] === 'list') {
      const name = args.find(arg => arg.startsWith('--name='))?.slice(7);
      const token = args.find(arg => arg.startsWith('--meta_value='))?.slice(13);
      stage(token ? 'list-owned' : 'list-reserved', args);
      return [...posts.entries()].filter(([, post]) => token ? post.token === token : post.slug === name).map(([id]) => id).join(' ');
    }
    if (args[0] === 'post' && args[1] === 'create') {
      stage('create', args);
      const slug = args.find(arg => arg.startsWith('--post_name='))?.slice(12);
      const meta = JSON.parse(args.find(arg => arg.startsWith('--meta_input='))?.slice(13) || '{}');
      const id = String(nextId++);
      posts.set(id, { slug, token: meta._wtcf_verifier_owner });
      stage('create-after', args);
      return config.createOutput ?? id;
    }
    if (args[0] === 'post' && args[1] === 'meta') {
      stage('meta', args);
      const post = posts.get(args[3]);
      if (post && args[4] === '_wtcf_event_fixture') post.fixture = JSON.parse(args[5]);
      return 'Success';
    }
    if (args[0] === 'post' && args[1] === 'delete') {
      stage('delete', args);
      if (!config.deleteNoop) posts.delete(args[2]);
      stage('delete-after', args);
      return 'Success';
    }
    throw new Error('Unexpected WP command: ' + args.slice(0, 2).join(' '));
  };
  return { wp, calls, stages, posts, get option() { return structuredClone(state); } };
}
