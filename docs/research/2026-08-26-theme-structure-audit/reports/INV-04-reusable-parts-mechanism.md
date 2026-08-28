# THEME-INV-04 レポート — 再利用パーツ機構の抽象化契約

- 対象イシュー: `issues/THEME-INV-04-reusable-parts-mechanism.md`
- 状態: **① テーマB 側完了 / テーマA 側は所在未特定 / ③④ 判定完了**
- 調査日: 2026-08-26
- 手段: ホスティング SSH 読み取り専用
- 一次証跡: `evidence/probe4-raw.txt`（`lib/post_type.php` 全文）・
  `evidence/re-themeB-blocks.txt`（`Pre_Parse_Blocks` の参照解決）・
  `evidence/re-themeB-detail.txt`（`Theme_Data` の DB 定義）・`evidence/probe2-raw.txt`

## 1. テーマB — CPT による正本化（完全に読了）

### 1.1 定義（`lib/post_type.php`・証跡 `evidence/probe4-raw.txt`）

```php
register_post_type( 'blog_parts', [
	'labels'          => [ 'name' => $parts_name, 'singular_name' => $parts_name ],
	'public'          => false,          // 単体で公開しない
	'show_ui'         => true,
	'show_in_menu'    => true,
	'capability_type' => [ 'blog_part', 'blog_parts' ],
	'map_meta_cap'    => true,
	'has_archive'     => false,
	'show_in_rest'    => true,           // ← ブロックエディター対応＝REST から扱える
	'supports'        => [ 'title', 'editor' ],
] );
```

3 つの CPT すべてに共通する設計:

| CPT | public | supports | show_in_rest | 無効化 |
|---|---|---|---|---|
| `lp` | true（検索除外） | title / editor / thumbnail / author / revisions / custom-fields | ○ | `remove_lp` |
| `blog_parts` | **false** | title / editor | ○ | `remove_blog_parts` |
| `ad_tag` | **false** | title / editor | ○ | `remove_ad_tag` |

- **`public => false` + `show_in_rest => true`** の組み合わせが要点。
  単体の URL では公開されないが、**REST からは読み書きできる**。
- `capability_type` を専用化（`[ 'blog_part', 'blog_parts' ]`）+ `map_meta_cap`。
  コード内コメントに「寄稿者(contributor) には新規追加の権限をなくしておく（変に増やされないように）」とある。
- `remove_*` オプションで**機能ごと無効化できる**。

### 1.2 参照形

保存側は通常の `post_content`（ブロック列）。参照側はブロック属性に ID を持つ:

| 参照元 | 属性 | 証跡 |
|---|---|---|
| `themeB/blog-parts` | `attrs.partsID` | `evidence/re-themeB-blocks.txt`（`check_parsed_block`） |
| `core/block`（同期パターン） | `attrs.ref` | 同上 |
| ターム単位の表示 | term_meta `themeB_term_meta_display_parts` | 同上（`Pre_Parse_Blocks::init`） |
| ショートコード | `[blog_parts]` / `[ad_tag]` | `evidence/theme-features-raw.txt` |

### 1.3 解決の実装

`Pre_Parse_Blocks::check_parsed_block()` が参照を辿って**中身を再帰的に展開**する:

```php
$parts_id = 0;
if ( 'themeB/blog-parts' === $block_name ) {
	$parts_id = $block['attrs']['partsID'] ?? 0;
} elseif ( 'core/block' === $block_name ) {
	$parts_id = $block['attrs']['ref'] ?? 0;
}
$parts = $parts_id ? get_post( $parts_id ) : '';
if ( $parts ) {
	self::parse_content( $parts->post_content );   // ← 再帰
}
```

**再帰の深さ制限が無い**点に注意。循環参照（A がB を、B が A を参照）で無限ループになりうる。
中間 JSON の解決器には**深さ上限と訪問済み集合**を入れる必要がある。

### 1.4 実データ

`blog_parts` / `ad_tag` / `lp` いずれも**実データ 0 件**（`evidence/usage-raw.txt` の post_type 集計:
site-B は post 16 / attachment 9 のみ）。本文中の `themeB/blog-parts` は 2 回だけ出現するが、
参照先が存在しない（＝壊れた参照か、下書きパーツを参照）可能性がある。

## 2. テーマA — CPT なし・所在は未特定

- `register_post_type` は **0 件**（`evidence/theme-features-raw.txt` / `probe2-raw.txt` で確認）。
- 再利用パーツは**番号スロット型のテーマオプション**に格納されている（PoC-2 の知見。
  `<local-poc-evidence>` の 2026-08-21 調査で「テーマA = 番号スロット型 shortcode（1〜10 上限・
  実体は theme options）」と記録済み）。
- **本調査ではその実装箇所を特定できていない。** `include/themeA-setting.php`（2,692 行）か
  `include/custom-functions.php`（5,214 行）にあると推定。

**確定していること**: テーマA には**再利用パーツを REST で一覧・取得する経路が無い**。
オプション値として埋まっているため、取り出すには WP-CLI か DB アクセスが要る
（`reports/INV-08-agent-interface-gap.md` §3 の経路 B/C）。

## 3. ③ 中間 JSON における表現方式 — 判定

`reports/INV-15-themeB-pipeline-transfer.md` §5 から引き継いだ論点。

| 方式 | 中間 JSON | 長所 | 短所 |
|---|---|---|---|
| 参照（ID） | `{ "type": "parts_ref", "id": 123 }` | パーツ更新が全記事へ波及。正本が 1 つ | レンダリングに外部状態（DB）参照が要る |
| 展開（実体埋め込み） | パーツの中身をその場に展開 | 単体で完結・決定論を保ちやすい | 更新が波及しない。差分が巨大化 |

### 判定: **参照で持つ。ただし「解決に使った版」を必ず記録する。**

```json
{
  "type": "parts_ref",
  "parts_id": 123,
  "resolved": {
    "rev": 45,
    "digest": "sha256:…",
    "at": "2026-08-26T00:00:00Z"
  }
}
```

**根拠**:
1. 決定論の要件は「**同じ入力 → 同じ出力**」。参照先の版を**入力の一部として固定**すれば、
   参照方式でも決定論は満たせる。展開する必要はない。
2. 再利用パーツの存在意義は「1 箇所直せば全部直る」ことにある。展開するとこれが失われ、
   機構ごと無意味になる。
3. 版と digest を持てば、「参照先が変わったので再レンダリングが要る」を機械的に検知できる。
   これは regeneration（改善候補キュー）の入力にもなる。

### 付随して決める規約

| 論点 | 規約 |
|---|---|
| 参照先が消えた | `{ "type": "parts_ref", "parts_id": 123, "state": "missing" }` を明示。レンダラは注記を出して続行（fail-close ではない） |
| 循環参照 | 解決器に**深さ上限（既定 5）と訪問済み集合**を持たせる。超えたら `state: "cycle"` |
| 下書きパーツの参照 | `state: "unpublished"`。公開前ゲート（internal-link/validation 相当）で検出 |
| 参照先の版が進んだ | `resolved.rev` と現在の rev を比較。差があれば再レンダリング対象キューへ |

## 4. ④ per-site アダプタの最小インタフェース

3 テーマで実装が違うため、契約だけ共通化して実装をアダプタに落とす。

```
PartsAdapter
  list()                       → [{ id, title, updated_at, rev }]
  get(id)                      → { id, title, content_ir, rev, digest, state }
  resolve(ref)                 → 上記 get の結果 + state（missing / cycle / unpublished / ok）
  capabilities()               → { writable: bool, has_revisions: bool, transport: 'rest'|'cli'|'db' }
```

| サイト | 実装 | `transport` | `writable` | `has_revisions` |
|---|---|---|---|---|
| テーマB | CPT `blog_parts`（`show_in_rest`） | `rest` | ○ | ○（post revisions） |
| テーマA | 番号スロット型 theme options | `cli` or `db` | △（スキーマなしのため非推奨） | **×** |
| Graphix NEO | 未定（CPT 方式を推奨） | `rest` | ○ | ○ |

**設計上の推奨**: Graphix NEO は **テーマB 方式（`public=false` + `show_in_rest=true` の CPT）を採る**。
理由は本レポート §1.1 のとおり、「単体公開しないが機械操作できる」という要件に
WordPress の標準機構がそのまま合致するため。番号スロット方式（テーマA）は
上限がハードコードされ REST 経路も無いので採らない。

`ad_tag` も同じ形（CPT）で持つのが筋だが、これは INV-03（広告ゾーン）と合わせて確定する。

## 5. 未了項目

- [ ] **テーマA の番号スロット実装の特定** — `themeA-setting.php` / `custom-functions.php` の精読。
      オプションキー名・上限・保存形式（HTML 生文字列か）を確定する
- [ ] テーマB の `themeB/blog-parts` 2 箇所の参照先が実在するかの確認（壊れた参照の可能性）
- [ ] `lp` CPT の扱い（INV-03 の LP 機構と合流）
- [ ] `themeB_term_meta_display_parts` によるターム単位の表示の要否判断

## 6. 証跡ファイル

| 内容 | 場所 |
|---|---|
| `lib/post_type.php` 全文（3 CPT の定義） | `evidence/probe4-raw.txt` |
| `Pre_Parse_Blocks` の参照解決（partsID / ref / term_meta） | `evidence/re-themeB-blocks.txt` |
| `Theme_Data` の DB 名定義・独自テーブル | `evidence/re-themeB-detail.txt` |
| テーマA に CPT が無いことの確認 | `evidence/theme-features-raw.txt` / `evidence/probe2-raw.txt` |
| 投稿タイプ別の実データ件数 | `evidence/usage-raw.txt` |
