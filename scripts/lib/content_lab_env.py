"""Validated content-lab settings shared by Python verifiers and the lab launcher."""
from dataclasses import dataclass
import os
from pathlib import Path
import re
import tempfile
from urllib.parse import urlsplit


@dataclass(frozen=True)
class ContentLabConfig:
    base_url: str
    state_dir: Path
    network: str
    wp_container: str
    db_container: str
    wp_volume: str
    db_volume: str
    credentials_file: Path


def content_lab_config(env=None):
    values = os.environ if env is None else env
    base_url = values.get('WTCF_BASE_URL') or 'http://127.0.0.1:8098'
    parsed = urlsplit(base_url)
    try:
        port = parsed.port
    except ValueError as error:
        raise ValueError('WTCF_BASE_URL must be a loopback HTTP origin with an explicit port') from error
    if (parsed.scheme != 'http' or parsed.hostname not in {'127.0.0.1', 'localhost'}
            or parsed.username or parsed.password or parsed.path not in {'', '/'}
            or parsed.query or parsed.fragment or port is None):
        raise ValueError('WTCF_BASE_URL must be a loopback HTTP origin with an explicit port')
    if port < 1 or port > 65535:
        raise ValueError('WTCF_BASE_URL must be a loopback HTTP origin with an explicit port')

    state_dir = Path(values.get('WTCF_STATE_DIR') or Path(tempfile.gettempdir()) / 'helix-content-lab')
    wp_container = values.get('WTCF_WP_CONTAINER') or 'helix-content-wp'
    db_container = values.get('WTCF_DB_CONTAINER') or 'helix-content-db'
    names = {
        'network': values.get('WTCF_DOCKER_NETWORK') or 'helix-content-lab',
        'wp_container': wp_container,
        'db_container': db_container,
        'wp_volume': values.get('WTCF_WP_VOLUME') or wp_container,
        'db_volume': values.get('WTCF_DB_VOLUME') or db_container,
    }
    for label, value in names.items():
        if not re.fullmatch(r'[a-zA-Z0-9][a-zA-Z0-9_.-]*', value):
            raise ValueError(f'WTCF {label} must start with a letter or digit and contain only letters, digits, dot, underscore or hyphen')

    credentials_file = Path(values.get('WTCF_LAB_CREDENTIALS') or state_dir / 'credentials.json')
    return ContentLabConfig(
        base_url=f'http://{parsed.hostname}:{port}',
        state_dir=state_dir,
        credentials_file=credentials_file,
        **names,
    )
