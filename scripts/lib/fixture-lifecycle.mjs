import { randomUUID } from 'node:crypto';

const optionName = 'wtcf_event_fixture_mode';
const ownerKey = '_wtcf_verifier_owner';

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

export function snapshotFixtureMode(wp) {
  // CLI エラー・DB エラー・空出力を「option 不在」に読み替えない。
  const value = JSON.parse(wp(['eval', snapshotPhp]));
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

function restoreFixtureMode(wp, snapshot) {
  const encoded = Buffer.from(JSON.stringify(snapshot)).toString('base64');
  const result = wp(['eval', `/* fixture-mode restore */
global $wpdb;
$snapshot = json_decode(base64_decode('${encoded}', true), true, 512, JSON_THROW_ON_ERROR);
if ($snapshot['exists']) {
    $value = base64_decode($snapshot['valueBase64'], true);
    $autoload = base64_decode($snapshot['autoloadBase64'], true);
    $result = $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), autoload = VALUES(autoload)", '${optionName}', $value, $autoload));
} else {
    $result = $wpdb->delete($wpdb->options, array('option_name' => '${optionName}'));
}
if ($result === false || $wpdb->last_error !== '') { throw new RuntimeException('Fixture mode restore query failed'); }
wp_cache_delete('${optionName}', 'options');
wp_cache_delete('alloptions', 'options');
wp_cache_delete('notoptions', 'options');
echo 'restored';`]);
  if (result !== 'restored') throw new Error('Fixture mode restore did not acknowledge success');
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
  const snapshot = snapshotFixtureMode(wp);
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
        } catch {
          // CLI 例外には環境・接続情報が含まれるため、公開証跡には処理名だけを残す。
          rows.push({ name, pass: false, cleanup: true });
          failed = true;
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
        for (const id of ids) await attempt(post.name + '-delete', () => { wp(['post', 'delete', id, '--force']); });
        await attempt(post.name + '-removed', () => list().length === 0);
      }
      // close / 投稿 cleanup の成否にかかわらず復元し、復元コマンド失敗時も別コマンドで照合する。
      await attempt('fixture-mode-restore', () => restoreFixtureMode(wp, snapshot));
      await attempt('fixture-mode-restored', () => {
        const actual = snapshotFixtureMode(wp);
        return Object.keys(snapshot).every(key => actual[key] === snapshot[key]);
      });
      if (failed) process.exitCode = 1;
      return !failed;
    },
  };
}
