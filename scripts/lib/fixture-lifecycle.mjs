import { randomUUID } from 'node:crypto';

const optionName = 'wtcf_event_fixture_mode';
const ownerKey = '_wtcf_verifier_owner';
const lockName = '_wtcf_verifier_fixture_mode_lock';

function requireInnoDbPhp(tables) {
  return `foreach (array(${tables.join(', ')}) as $table) {
    $engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table));
    if ($wpdb->last_error !== '' || strcasecmp((string) $engine, 'InnoDB') !== 0) { throw new RuntimeException('Fixture transaction requires InnoDB'); }
}`;
}

function checkLockPhp(token, forUpdate = false) {
  return `$lock_owner = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s${forUpdate ? ' FOR UPDATE' : ''}", '${lockName}'));
if ($wpdb->last_error !== '' || $lock_owner !== '${token}') { throw new RuntimeException('Fixture lifecycle lock ownership changed'); }`;
}

function acquireLock(wp, token) {
  const result = wp(['eval', `/* fixture-mode lock-acquire */
global $wpdb;
${requireInnoDbPhp(['$wpdb->options'])}
$result = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO $wpdb->options (option_name, option_value, autoload) VALUES (%s, %s, 'off')", '${lockName}', '${token}'));
if ($result !== 1 || $wpdb->last_error !== '') { throw new RuntimeException('Fixture lifecycle lock unavailable'); }
echo 'locked';`]);
  if (result !== 'locked') throw new Error('Fixture lifecycle lock acquisition was not acknowledged');
}

function releaseLock(wp, token, required = true) {
  const result = wp(['eval', `/* fixture-mode lock-release */
global $wpdb;
$result = $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name = %s AND BINARY option_value = %s", '${lockName}', '${token}'));
if ($result === false || $wpdb->last_error !== '') { throw new RuntimeException('Fixture lifecycle lock release failed'); }
wp_cache_delete('${lockName}', 'options');
wp_cache_delete('notoptions', 'options');
echo $result === 1 ? 'released' : 'not-owned';`]);
  if (result !== 'released' && (required || result !== 'not-owned')) {
    throw new Error('Fixture lifecycle lock release was not acknowledged');
  }
}

// get_option() は DB の scalar を string として返す。JSON や CLI の文字列経由で
// PHP 型を推測せず、DB の値そのものを保存する（serialized 値も再 serialize しない）。
// https://developer.wordpress.org/reference/functions/get_option/
const snapshotPhp = `/* fixture-mode snapshot */
global $wpdb;
$row = $wpdb->get_row($wpdb->prepare("SELECT option_value, autoload FROM $wpdb->options WHERE option_name = %s", '${optionName}'), ARRAY_A);
if ($wpdb->last_error !== '') { throw new RuntimeException('Fixture mode snapshot query failed'); }
if ($row === null) {
    $snapshot = array('exists' => false, 'type' => null, 'valueBase64' => null, 'autoloadBase64' => null);
} else {
    if (!is_array($row) || !isset($row['option_value'], $row['autoload'])) { throw new RuntimeException('Invalid fixture mode row'); }
    $snapshot = array('exists' => true, 'type' => gettype(maybe_unserialize($row['option_value'])), 'valueBase64' => base64_encode($row['option_value']), 'autoloadBase64' => base64_encode($row['autoload']));
}
echo json_encode($snapshot, JSON_THROW_ON_ERROR);`;

function isBase64(value) {
  return typeof value === 'string' && Buffer.from(value, 'base64').toString('base64') === value;
}

export function snapshotFixtureMode(wp, token) {
  // CLI エラー・DB エラー・空出力を「option 不在」に読み替えない。
  const php = token ? snapshotPhp.replace('global $wpdb;', 'global $wpdb;\n' + checkLockPhp(token)) : snapshotPhp;
  const value = JSON.parse(wp(['eval', php]));
  if (!value || typeof value.exists !== 'boolean'
    || Object.keys(value).sort().join(',') !== 'autoloadBase64,exists,type,valueBase64'
    || (value.exists
      ? !['string', 'boolean', 'integer', 'double', 'array', 'object', 'NULL'].includes(value.type)
        || !isBase64(value.valueBase64) || !isBase64(value.autoloadBase64)
      : value.type !== null || value.valueBase64 !== null || value.autoloadBase64 !== null)) {
    throw new Error('Invalid fixture mode snapshot');
  }
  return Object.freeze(value);
}

function restoreFixtureMode(wp, snapshot, token) {
  const encoded = Buffer.from(JSON.stringify(snapshot)).toString('base64');
  const result = wp(['eval', `/* fixture-mode restore */
global $wpdb;
$snapshot = json_decode(base64_decode('${encoded}', true), true, 512, JSON_THROW_ON_ERROR);
if ($wpdb->query('START TRANSACTION') === false) { throw new RuntimeException('Fixture restore transaction failed'); }
try {
${checkLockPhp(token, true)}
if ($snapshot['exists']) {
    $value = base64_decode($snapshot['valueBase64'], true);
    $autoload = base64_decode($snapshot['autoloadBase64'], true);
    $result = $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), autoload = VALUES(autoload)", '${optionName}', $value, $autoload));
} else {
    $result = $wpdb->delete($wpdb->options, array('option_name' => '${optionName}'));
}
if ($result === false || $wpdb->last_error !== '') { throw new RuntimeException('Fixture mode restore query failed'); }
if ($wpdb->query('COMMIT') === false) { throw new RuntimeException('Fixture restore commit failed'); }
} catch (Throwable $error) {
    $wpdb->query('ROLLBACK');
    throw $error;
}
wp_cache_delete('${optionName}', 'options');
wp_cache_delete('alloptions', 'options');
wp_cache_delete('notoptions', 'options');
echo 'restored';`]);
  if (result !== 'restored') throw new Error('Fixture mode restore did not acknowledge success');
}

function deleteOwnedPost(wp, id, token) {
  const result = wp(['eval', `/* fixture owned-post-delete */
global $wpdb;
$fixture_id = ${id};
$fixture_owner = '${token}';
${requireInnoDbPhp(['$wpdb->posts', '$wpdb->postmeta'])}
if ($wpdb->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE') === false || $wpdb->query('START TRANSACTION') === false) { throw new RuntimeException('Fixture delete transaction failed'); }
try {
    $post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE ID = %d AND post_type = 'page' FOR UPDATE", $fixture_id));
    if ($wpdb->last_error !== '' || (string) $post_id !== (string) $fixture_id) { throw new RuntimeException('Fixture post missing'); }
    $owners = $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s FOR UPDATE", $fixture_id, '${ownerKey}'));
    if ($wpdb->last_error !== '' || $owners !== array($fixture_owner)) { throw new RuntimeException('Fixture ownership changed'); }
    $deleted = wp_delete_post($fixture_id, true);
    if (!$deleted || $wpdb->last_error !== '') { throw new RuntimeException('Owned fixture deletion failed'); }
    $remaining = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE ID = %d", $fixture_id));
    if ($wpdb->last_error !== '' || $remaining !== null) { throw new RuntimeException('Owned fixture deletion was not completed'); }
    if ($wpdb->query('COMMIT') === false) { throw new RuntimeException('Fixture delete commit failed'); }
} catch (Throwable $error) {
    $wpdb->query('ROLLBACK');
    wp_cache_delete($fixture_id, 'posts');
    wp_cache_delete($fixture_id, 'post_meta');
    throw $error;
}
wp_cache_delete($fixture_id, 'posts');
wp_cache_delete($fixture_id, 'post_meta');
echo 'deleted';`]);
  if (result !== 'deleted') throw new Error('Owned fixture deletion was not acknowledged');
}

function postIds(output) {
  if (output === '') return [];
  const ids = output.split(/\s+/);
  if (ids.some(id => !/^[1-9][0-9]*$/.test(id) || !Number.isSafeInteger(Number(id)))) {
    throw new Error('Invalid owned fixture IDs');
  }
  return ids;
}

export function createFixtureLifecycle(wp) {
  const token = randomUUID();
  let snapshot;
  let acquired = false;
  try {
    // 共有 option の snapshot より先に排他取得。残留 lock は期限切れ扱いで奪取しない。
    acquireLock(wp, token);
    acquired = true;
    snapshot = snapshotFixtureMode(wp, token);
  } catch (error) {
    // 取得コマンドの返答前に失敗した場合も、自分の UUID の lock だけを回収する。
    try { releaseLock(wp, token, acquired); } catch {
      throw new Error('Fixture initialization failed; lock release unconfirmed, manual recovery required', { cause: error });
    }
    throw error;
  }
  const ownedPosts = [];
  return {
    createPost(args, name = 'owned-fixture') {
      const post = { name, token: randomUUID(), id: undefined };
      ownedPosts.push(post);
      const ids = postIds(wp([...args, '--meta_input=' + JSON.stringify({ [ownerKey]: post.token })]));
      if (ids.length !== 1) throw new Error('Fixture creation did not return one post ID');
      post.id = ids[0];
      return Number(post.id);
    },
    async cleanup(browser, rows) {
      let failed = false;
      const attempt = async (name, action) => {
        try {
          const pass = await action();
          if (pass === false) throw new Error('Cleanup verification failed');
          rows.push({ name, pass: true, cleanup: true });
          return true;
        } catch {
          // CLI 例外には環境・接続情報が含まれるため、公開証跡には処理名だけを残す。
          rows.push({ name, pass: false, cleanup: true,
            ...(name === 'fixture-mode-lock-release' ? { manualRecoveryRequired: true } : {}) });
          failed = true;
          return false;
        }
      };
      if (browser) await attempt('browser-closed', () => browser.close());
      for (const post of ownedPosts) {
        const list = () => postIds(wp(['post', 'list', '--post_type=page', '--post_status=any', '--meta_key=' + ownerKey, '--meta_value=' + post.token, '--format=ids']));
        let ids = [];
        // 作成の返答前に CLI が失敗した場合も、今回付けた owner marker だけで回収する。
        // 返答 ID が既知でも所有権を再照合し、marker が変わった投稿は削除しない。
        await attempt(post.name + '-locate', () => {
          const owned = list();
          if (post.id && !owned.includes(post.id)) throw new Error('Fixture ownership changed');
          ids = owned;
        });
        for (const id of ids) await attempt(post.name + '-delete', () => deleteOwnedPost(wp, id, post.token));
        await attempt(post.name + '-removed', () => list().length === 0);
      }
      // close / 投稿 cleanup の成否にかかわらず復元し、復元コマンド失敗時も別コマンドで照合する。
      await attempt('fixture-mode-restore', () => restoreFixtureMode(wp, snapshot, token));
      const restored = await attempt('fixture-mode-restored', () => {
        const actual = snapshotFixtureMode(wp, token);
        return Object.keys(snapshot).every(key => actual[key] === snapshot[key]);
      });
      // 復元コマンド失敗後でも再照合が完全一致なら解放できる。未確認なら次の実行に
      // 壊れた値を初期値として渡さず、lock を保持して手動対応を要求する。
      if (restored) await attempt('fixture-mode-lock-release', () => releaseLock(wp, token));
      else {
        rows.push({ name: 'fixture-mode-lock-retained', pass: false, cleanup: true, manualRecoveryRequired: true });
        failed = true;
      }
      if (failed) process.exitCode = 1;
      return !failed;
    },
  };
}
