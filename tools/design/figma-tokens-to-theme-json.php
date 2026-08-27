<?php
/**
 * figma-tokens-to-theme-json.php
 *
 * Figma プラグイン（10up figma-to-wordpress-theme-json-exporter / Theme.json generator 等）が
 * 書き出した theme.json 断片（settings のみ）を、親テーマの theme.json へ「値の差し替えのみ」で取り込む。
 *
 * 層 1（トークン）の不変条件（docs/design/consistency-responsibilities.md §1〜2）:
 *   - 取り込めるのは同じスラッグへの値の差し替えだけ。スラッグの追加・削除・改名は拒否する。
 *   - 対象は settings.color.palette / settings.typography.fontSizes / settings.spacing.spacingSizes /
 *     settings.typography.fontFamilies / settings.shadow.presets / settings.layout。それ以外の settings は無視する。
 *   - styles は取り込まない（見出し階層などは G-T3 の対象で、テーマ側が持つ）。
 *
 * 使い方:
 *   php tools/design/figma-tokens-to-theme-json.php <exported.json> [--write] [--allow-scale-change]
 *     省略時は dry-run（差分表示のみ）。--write で themes/agent-neo-theme/theme.json を更新。
 *     --allow-scale-change はスラッグ集合の変更を許す（設計判断・CHANGELOG 必須）。
 * 終了コード: 0=取り込み可（または書き込み済み） 1=拒否
 */

declare(strict_types=1);

$args = array_slice($argv, 1);
$input = null;
$write = false;
$allow_scale_change = false;
foreach ($args as $a) {
	if ($a === '--write') { $write = true; continue; }
	if ($a === '--allow-scale-change') { $allow_scale_change = true; continue; }
	$input = $a;
}
if ($input === null || ! is_file($input)) {
	fwrite(STDERR, "usage: php tools/design/figma-tokens-to-theme-json.php <exported.json> [--write] [--allow-scale-change]\n");
	exit(1);
}

$theme_json_path = dirname(__DIR__, 2) . '/themes/agent-neo-theme/theme.json';
$theme = json_decode((string) file_get_contents($theme_json_path), true);
$export = json_decode((string) file_get_contents($input), true);
if (! is_array($theme) || ! is_array($export)) {
	fwrite(STDERR, "JSON の読み込みに失敗しました\n");
	exit(1);
}
$ex_settings = $export['settings'] ?? $export; // settings 直下だけのファイルにも対応

$errors = [];
$changes = [];

/**
 * スラッグ配列を「同じスラッグへの値差し替えのみ」でマージする。
 *
 * @param array  $current  親 theme.json の配列（slug/size|color|fontFamily）。
 * @param array  $incoming Figma 書き出し側。
 * @param string $value_key 値のキー名。
 * @param string $label     表示用ラベル。
 */
$merge_presets = function (array $current, array $incoming, string $value_key, string $label) use (&$errors, &$changes, $allow_scale_change): array {
	$cur_by_slug = [];
	foreach ($current as $i => $item) { $cur_by_slug[$item['slug']] = $i; }
	$in_by_slug = [];
	foreach ($incoming as $item) {
		if (! isset($item['slug'])) { continue; }
		// Figma 側書き出し器はグループ名を接頭辞として付ける（color-primary / space-10 / font-size-small）。
		// 親 theme.json のスラッグは接頭辞なしなので、既知接頭辞は剥がして照合する（接頭辞なしはそのまま）。
		$slug = preg_replace('/^(color|space|spacing|font-size|size|shadow|elevation)-/', '', $item['slug']);
		if ($slug !== $item['slug'] && ! isset($cur_by_slug[$slug]) && isset($cur_by_slug[$item['slug']])) { $slug = $item['slug']; }
		$item['slug'] = $slug;
		if (isset($item['name'])) { $item['name'] = preg_replace('/^(Color|Space|Spacing|Font Size|Size|Shadow|Elevation) /', '', $item['name']); }
		$in_by_slug[$slug] = $item;
	}

	// 書き出し側に無いスラッグは「触らない」（部分更新を許す）。書き出し側にしか無いスラッグは段の追加なので拒否。
	$added = array_diff(array_keys($in_by_slug), array_keys($cur_by_slug));
	if ($added && ! $allow_scale_change) {
		$errors[] = sprintf('%s: 親 theme.json に無いスラッグが含まれる（%s）。段の追加は --allow-scale-change と設計判断（CHANGELOG）が必要',
			$label, implode(',', $added));
		return $current;
	}
	$removed = [];
	foreach ($in_by_slug as $slug => $item) {
		if (! isset($cur_by_slug[$slug])) {
			if ($allow_scale_change) { $current[] = $item; $changes[] = "$label +$slug"; }
			continue;
		}
		$i = $cur_by_slug[$slug];
		// 単位表現は親 theme.json の慣習に合わせる（親が rem・書き出しが px なら 16px=1rem で換算）。
		$cur_v = $current[$i][$value_key] ?? null;
		$new_v = $item[$value_key] ?? null;
		if (is_string($cur_v) && is_string($new_v) && preg_match('/^-?[0-9.]+rem$/', $cur_v) && preg_match('/^(-?[0-9.]+)px$/', $new_v, $m)) {
			$new_v = rtrim(rtrim(sprintf('%.4F', (float) $m[1] / 16), '0'), '.') . 'rem';
			$item[$value_key] = $new_v;
		}
		if ($cur_v !== $new_v) {
			$changes[] = sprintf('%s %s: %s → %s', $label, $slug, $current[$i][$value_key] ?? '-', $item[$value_key] ?? '-');
			$current[$i][$value_key] = $item[$value_key];
		}
		if (isset($item['name'])) { $current[$i]['name'] = $item['name']; }
	}
	return $current;
};

if (isset($ex_settings['color']['palette'])) {
	$theme['settings']['color']['palette'] = $merge_presets($theme['settings']['color']['palette'] ?? [], $ex_settings['color']['palette'], 'color', 'color.palette');
}
if (isset($ex_settings['typography']['fontSizes'])) {
	$theme['settings']['typography']['fontSizes'] = $merge_presets($theme['settings']['typography']['fontSizes'] ?? [], $ex_settings['typography']['fontSizes'], 'size', 'typography.fontSizes');
}
if (isset($ex_settings['typography']['fontFamilies'])) {
	$theme['settings']['typography']['fontFamilies'] = $merge_presets($theme['settings']['typography']['fontFamilies'] ?? [], $ex_settings['typography']['fontFamilies'], 'fontFamily', 'typography.fontFamilies');
}
if (isset($ex_settings['spacing']['spacingSizes'])) {
	$theme['settings']['spacing']['spacingSizes'] = $merge_presets($theme['settings']['spacing']['spacingSizes'] ?? [], $ex_settings['spacing']['spacingSizes'], 'size', 'spacing.spacingSizes');
}
if (isset($ex_settings['shadow']['presets'])) {
	$theme['settings']['shadow']['presets'] = $merge_presets($theme['settings']['shadow']['presets'] ?? [], $ex_settings['shadow']['presets'], 'shadow', 'shadow.presets');
}
foreach (['contentSize', 'wideSize'] as $k) {
	if (isset($ex_settings['layout'][$k]) && ($theme['settings']['layout'][$k] ?? null) !== $ex_settings['layout'][$k]) {
		$changes[] = sprintf('layout.%s: %s → %s', $k, $theme['settings']['layout'][$k] ?? '-', $ex_settings['layout'][$k]);
		$theme['settings']['layout'][$k] = $ex_settings['layout'][$k];
	}
}

// 層 1 の必須フラグは取り込み側の有無に関わらず維持する。
$theme['settings']['typography']['defaultFontSizes'] = false;
$theme['settings']['spacing']['defaultSpacingSizes'] = false;
unset($theme['settings']['spacing']['spacingScale']);

if ($errors) {
	fwrite(STDERR, "拒否:\n  - " . implode("\n  - ", $errors) . "\n");
	exit(1);
}
if (! $changes) {
	echo "差分なし（取り込むトークンは親 theme.json と同一）\n";
	exit(0);
}
echo ($write ? "書き込み:\n" : "dry-run（--write で反映）:\n") . '  - ' . implode("\n  - ", $changes) . "\n";
if ($write) {
	$json = json_encode($theme, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	// 既存ファイルは 2 スペースインデントのため合わせる。
	$json = preg_replace_callback('/^( {4})+/m', fn($m) => str_repeat('  ', strlen($m[0]) / 4), (string) $json);
	file_put_contents($theme_json_path, $json . "\n");
	echo "更新: $theme_json_path\n";
}
exit(0);
