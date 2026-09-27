import os from 'node:os';
import path from 'node:path';

const baseUrl = process.env.WTCF_BASE_URL || 'http://127.0.0.1:8098';
let parsedUrl;
try {
  parsedUrl = new URL(baseUrl);
} catch {
  throw new Error('WTCF_BASE_URL must be a valid loopback HTTP URL');
}
const explicitPort = /^http:\/\/(127\.0\.0\.1|localhost):([0-9]{1,5})\/?$/i.exec(baseUrl);
const port = explicitPort ? Number(explicitPort[2]) : NaN;
if (parsedUrl.protocol !== 'http:' || !['127.0.0.1', 'localhost'].includes(parsedUrl.hostname)
  || parsedUrl.username || parsedUrl.password || parsedUrl.pathname !== '/' || parsedUrl.search || parsedUrl.hash
  || !explicitPort || !Number.isInteger(port) || port < 1 || port > 65535) {
  throw new Error('WTCF_BASE_URL must be a loopback HTTP origin with an explicit port');
}

const stateDir = process.env.WTCF_STATE_DIR || path.join(os.tmpdir(), 'helix-content-lab');
const dockerNames = {
  network: process.env.WTCF_DOCKER_NETWORK || 'helix-content-lab',
  wpContainer: process.env.WTCF_WP_CONTAINER || 'helix-content-wp',
  dbContainer: process.env.WTCF_DB_CONTAINER || 'helix-content-db',
  wpVolume: process.env.WTCF_WP_VOLUME || process.env.WTCF_WP_CONTAINER || 'helix-content-wp',
  dbVolume: process.env.WTCF_DB_VOLUME || process.env.WTCF_DB_CONTAINER || 'helix-content-db',
};
for (const [label, value] of Object.entries(dockerNames)) {
  if (!/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/.test(value)) {
    throw new Error(`WTCF ${label} must start with a letter or digit and contain only letters, digits, dot, underscore or hyphen`);
  }
}

export const contentLab = Object.freeze({
  baseUrl: `http://${parsedUrl.hostname}:${port}`,
  stateDir,
  ...dockerNames,
  credentialsFile: process.env.WTCF_LAB_CREDENTIALS || path.join(stateDir, 'credentials.json'),
});

export function contentLabWpCliArgs() {
  return [
    'run', '--rm', '--network', contentLab.network,
    '--env-file', path.join(contentLab.stateDir, 'wp.env'),
    '--volumes-from', contentLab.wpContainer,
    '--user', '33:33', 'wordpress:cli-php8.3', 'wp',
  ];
}
