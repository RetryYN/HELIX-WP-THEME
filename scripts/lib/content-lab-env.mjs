import os from 'node:os';
import path from 'node:path';

const baseUrl = process.env.WTCF_BASE_URL || 'http://127.0.0.1:8098';
let parsedUrl;
try {
  parsedUrl = new URL(baseUrl);
} catch {
  throw new Error('WTCF_BASE_URL must be a valid loopback HTTP URL');
}
if (parsedUrl.protocol !== 'http:' || !['127.0.0.1', 'localhost'].includes(parsedUrl.hostname)
  || parsedUrl.username || parsedUrl.password || parsedUrl.pathname !== '/' || parsedUrl.search || parsedUrl.hash) {
  throw new Error('WTCF_BASE_URL must be a loopback HTTP origin without credentials or a path');
}

export const contentLab = Object.freeze({
  baseUrl: parsedUrl.origin,
  stateDir: process.env.WTCF_STATE_DIR || path.join(os.tmpdir(), 'helix-content-lab'),
  network: process.env.WTCF_DOCKER_NETWORK || 'helix-content-lab',
  wpContainer: process.env.WTCF_WP_CONTAINER || 'helix-content-wp',
});

export function contentLabWpCliArgs() {
  return [
    'run', '--rm', '--network', contentLab.network,
    '--env-file', path.join(contentLab.stateDir, 'wp.env'),
    '--volumes-from', contentLab.wpContainer,
    '--user', '33:33', 'wordpress:cli-php8.3', 'wp',
  ];
}
