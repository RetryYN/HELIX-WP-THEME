# THEME-INV-16 レポート — テーマA の描画時 DB 書き込み副作用

- 対象イシュー: `issues/THEME-INV-16-themeA-render-side-effects.md`
- 状態: **①・⑤完了 / ②横断走査は未了 / ④手順化済み**
- 調査日: 2026-08-26
- 手段: ホスティング SSH 読み取り専用
- 一次証跡: `evidence/re-themeA-boot.txt`（L255-L376）・`evidence/re-themeA-accessors.txt`

## 1. 結論（確定した事実）

`wp_head` / `admin_head` にフックされた CSS 生成関数 `themeA_customize_inline_style()` は、
その内部で **`set_theme_mod()` を 5 回**呼び、値が未設定なら**既定色をその場で DB に書き込む**。

描画パス（読み取りであるべき経路）に永続化の副作用がある。

## 2. 証跡 — 5 箇所の全列挙

`include/load-customizer-value.php`。関数冒頭 40 行のうちに 5 箇所すべてが集中している。
（`evidence/re-themeA-boot.txt` L264-L295 に採取済み）

| # | 条件 | 書き込むキー | 既定値 | 証跡行 |
|---|---|---|---|---|
| 1 | `themeA__theme_color() == false` | `themeA__theme_color` | `#407FED` | re-themeA-boot.txt L264-266 |
| 2 | `themeA__header_bg_color() == false` | `themeA__header_bg_color` | `#407FED` | L274-276 |
| 3 | `themeA__header_menu_color() == false` | `themeA__header_menu_color` | `#22327a` | L281-283 |
| 4 | `themeA__text_color() == false` | `themeA__text_color` | `#555555` | L288-290 |
| 5 | `themeA__bg_color() == false` | `themeA__bg_color` | `#f7faff` | L293-295 |

```php
function themeA_customize_inline_style()
{
	$sp          = 'max-width: 551px';
	…
	$bg_image                 = themeA__bg_image();
	if (themeA__theme_color() == false) {
		set_theme_mod('themeA__theme_color', '#407FED');
	}
	$theme_color              = themeA__theme_color();
	…
	if (themeA__header_bg_color() == false) {
		set_theme_mod('themeA__header_bg_color', '#407FED');
	}
	…
	if (themeA__header_menu_color() == false) {
		set_theme_mod('themeA__header_menu_color', '#22327a');
	}
	…
	if (themeA__text_color() == false) {
		set_theme_mod('themeA__text_color', '#555555');
	}
	…
	if (themeA__bg_color() == false) {
		set_theme_mod('themeA__bg_color', '#f7faff');
	}
```

フック登録（同ファイル末尾、`evidence/re-themeA-boot.txt` の関数リスト部）:

```
2:    function themeA_customize_inline_style()
2099: add_action('wp_head',    'themeA_customize_inline_style');
2100: add_action('admin_head', 'themeA_customize_inline_style');
```

**関数は 2,098 行 1 本**。`wp_head` と `admin_head` の両方に同じ関数が刺さっているため、
フロント表示でも管理画面表示でも同じ書き込みが発火しうる。

## 3. 挙動の含意

`set_theme_mod()` は `theme_mods_{stylesheet}` オプションの更新（`update_option`）であり、
`autoload=yes` のオプション 1 件への書き込みになる。

| 場面 | 起きること |
|---|---|
| 新規インストール直後の初回アクセス | 5 色が DB に確定する。**人が設定していない値が「設定済み」になる** |
| 匿名クローラの巡回 | 同上。**閲覧だけで状態が変わる** |
| 同時アクセス | 同一オプションへの並行 `update_option`。最後の書き込みが勝つ（実害は小さいが冪等ではない） |
| ページキャッシュ有効時 | キャッシュヒット時は PHP 非到達 → **書き込みが起きるかはキャッシュ状態に依存**（非決定的） |
| サイト複製・移管 | 複製先で初回アクセスが走ると、移管元と無関係に既定色が入る |
| ステージング / 本番の比較 | 触っていないのに `theme_mods` が乖離しうる |

**移管作業（THEME-INV-09）への直接の影響**:
`theme_mod` に値があっても、それが「人が決めた設定」なのか「副作用で入った既定値」なのか、
**値だけでは判別できない**。判別には次のいずれかが要る。

- 既定値と一致するかで推定する（`#407FED` `#22327a` `#555555` `#f7faff` の 4 色）。
  ただし人が同じ色を選んだ場合と区別できない。
- 移管前にスナップショットを取り、以後の差分で判断する。
- リビジョン履歴が無いため事後の遡及は不可。

→ **移管手順に「作業前の `theme_mods` スナップショット」を必須工程として入れる**必要がある。

## 4. 未了項目（追加のサーバー読み取りが要る）

- [ ] **②描画パスからの他の書き込みの洗い出し**
      `update_option` / `update_post_meta` / `update_term_meta` / `set_theme_mod` を
      `wp_head` / `the_content` / `render_callback` / `template_redirect` の到達範囲で走査する。
      特に `include/custom-functions.php`（5,214 行）と `include/themeA-setting.php`（2,692 行）。
      現時点で判明している出現ファイル数（`evidence/re-themeA-accessors.txt`）:
      `class-themeA-demo-import-control.php` / `custom-functions.php` / `load-customizer-value.php`。
- [ ] **③意図的な書き込みとの切り分け**
      PV カウント（`themeA_increment_views`）は `admin-ajax.php` 経由の設計上の書き込み。
      `template_redirect` のセッション開始（有料記事）も同様に意図的。これらは対象外として除く。
- [ ] **④移管手順への反映** — スナップショット工程の定義
- [ ] **⑤判別可否の最終結論** — ②の結果を待って確定

## 5. 本番サイトの実測（2026-08-27・read-only）

現在の `theme_mods_themeA` を読み出し、対象5キーをコード既定値と照合した。
**5キーすべてが既定値と異なった**ため、本サイトでは副作用で入った既定値ではなく、
人が決めた設定として識別できる。既定値と同値のサイトでは依然として判別不能なので、
一般の移管手順では事前スナップショットを維持する。

```
# 実施したread-only確認の再現形
wp option get theme_mods_themeA --format=json --path=~/site-A.example/public_html
```

## 6. 証跡ファイル

| 内容 | 場所 |
|---|---|
| `themeA_customize_inline_style()` 冒頭と 5 箇所の `set_theme_mod` | `evidence/re-themeA-boot.txt` L255-L376 |
| フック登録行（2099・2100） | 同上（関数リスト部） |
| `set_theme_mod` 出現ファイル一覧 | `evidence/re-themeA-accessors.txt` |

## 2026-08-27 実測の反映

`evidence/theme-mods-and-ini-raw.txt` で `theme_mods_themeA` の 235 キーを確認し、
描画時副作用の対象 5 キーをコード既定値と照合した。

| 実測項目 | 結果 |
|---|---|
| 副作用対象 5 キーの現在値と既定値 | 5 キーすべて異なる |
| 判定 | 本サイトではすべて人が決めた設定 |
| `theme_mods_themeA` の値の型 | str 210 / bool 23 / int 1 / dict 1 |

よって本サイトでは、§4.1 の一般的な「値だけでは判別不能」という問題は発生しない。
ただし既定値と一致するサイトではなお判別不能なので、移管前スナップショットは維持する。
235 キー全体の同種分類と、描画副作用への対処採用は PO 判断に残る。
