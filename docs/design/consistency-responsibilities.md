# 一貫性の責務整理 — サイズ系を「統一性が必要なもの」として誰が・何を・どう守るか（2026-08-28）

前提: 本テーマの優位性は「機械が扱える正本の一元性」。一貫性はその正本の **層ごとの不変条件（invariant）** として定義し、
人でなく検査で守る。以下、PoC で実測した現状の違反を起点に整理する。

## 0. 実測した現状の違反（一貫性が"設計上は"あるが"実際には"守られていない箇所）

| # | 違反 | 実測 | 責務の所在 |
|---|---|---|---|
| V1 | **theme.json のサイズ尺度が効いていない** | v3 で `defaultFontSizes` / `defaultSpacingSizes` 未指定（=true）→ コア既定スラッグと衝突する `small/medium/large/x-large`・spacing `20–60` が捨てられ、有効なのは xx-large / xxx-large と spacing 10 のみ。エディタの選択肢も 2 段。 | 層 1（トークン）の定義ミス。修正: 両方 `false` |
| V2 | **パターンが尺度外の生値を使う** | patterns 内の生サイズ値 204 件（`fontSize` 2rem×8・1.0625rem×8・1.125rem 等は尺度に存在しない）。preset 参照 1,628 に対し生値 303（parts/templates 含む） | 層 3（部品）が層 1 を迂回 |
| V3 | **子テーマがレイアウト幅を上書き** | helix-neo の theme.json が `contentSize 760 / wideSize 1200` を再定義（親と同値だが「子が触ってよい層」を越境） | 層 2 の境界 |
| V4 | **トークンの投影経路が複数** | bridge が `wp_theme_json_data_theme` で option を注入し、さらに user global styles（wp_global_styles 投稿）も存在。優先順が明文化されていない | 層 1 の正本が 3 つ |
| V5 | **ナビが構造に埋め込み** | header part に `navigation-link` ハードコード。`wp_navigation` 投稿は存在するが未参照 | 層 2 と層 4 の混在 |
| V6 | **spacingScale と spacingSizes の併記** | 生成規則（scale）と明示配列（sizes）を両方持ち、どちらが正本か不定 | 層 1 |

## 1. 層と不変条件

| 層 | 正本（唯一） | 不変条件（守るべきこと） | 変更できる者 / 頻度 |
|---|---|---|---|
| **1 トークン**（サイズ・色・書体・角丸・影） | `themes/agent-neo-theme/theme.json` の `settings.*` プリセット | (a) 尺度は **6 段のフォント・6 段の余白・幅 2 値** のみ。(b) `defaultFontSizes:false` `defaultSpacingSizes:false` でコア既定を排除。(c) 生成規則（spacingScale）は持たない。(d) 子テーマ・bridge・user styles は **値の差し替え**（同じスラッグに別の値）だけでき、**段の追加・削除・スラッグ変更は不可** | デザイン責任者、版番号付き（theme.json の `version` とは別に CHANGELOG）。年数回 |
| **2 骨格**（テンプレ・パーツ・slot・幅） | `templates/*.html` `parts/*.html` | (a) レイアウト幅は層 1 の `contentSize/wideSize` を参照するだけで再定義しない。(b) 部品は `wp:pattern` と slot で呼び、生ブロックを直書きしない。(c) ナビ・ロゴ・サイト名はコンテンツ（`wp_navigation` / site option）を **ref** で参照。(d) 変種は「テンプレ名」で表す（single-2col / single-1col）— 属性で幅や余白を変えない | 設計レビュー経由。四半期 |
| **3 部品**（パターン・block style） | `patterns/*.php`・`register_block_style` | (a) サイズ・余白・色は **必ず preset 参照**（`var:preset|…`）。生 px/rem は禁止。(b) 見出し階層は h2>h3>h4 を層 1 の段で固定（h2=x-large, h3=large 等）。(c) variant は block style 名で表す。(d) save 出力と markup が一致（Block validation 0） | 部品追加は PR。月次 |
| **4 内容**（本文・メタ・ナビ項目） | 投稿・`wp_navigation`・post meta | (a) 本文はブロック markup のみ、インライン style 禁止。(b) メタは登録済みキー（toc/share/pr/sidebar）のみ。(c) ページ JSON が触れるのはこの層と、層 2・3 の **選択**（テンプレ名・パターン名・style 名）だけ | AI / HELIX。毎日 |

「サイズは統一性が必要」= **層 1 だけがサイズの値を持ち、層 2〜4 は名前で参照する**。ページ JSON に `fontSize: "2rem"` は書けず `size: "x-large"` しか書けない。

## 2. 優先順（同じトークンを複数の正本が持つときの解決）

```
コア既定  <  親 theme.json（正本）  <  style variation（styles/*.json: 値の差し替えのみ）
        <  bridge 投影（agent_neo_core_design_tokens: 値の差し替えのみ、段の増減不可）
        <  user global styles（wp_global_styles: 運用では無効化 or 読み取り専用）
```
- 子テーマ（helix-neo）は **層 1 を持たない**（theme.json は `styles.css` の最小限のみ）。V3 は撤去。
- bridge は投影前に **スキーマ検査**（スラッグ集合が親と同一か）を通す。段を変える投影は拒否。

## 3. 守らせ方（人の注意ではなく検査で）

| ゲート | 対象 | 判定 | 現状 |
|---|---|---|---|
| G-T1 トークン形状 | theme.json | 段数 6/6/2、`default*Sizes:false`、spacingScale なし、スラッグ集合固定 | **V1/V6 で赤** → 修正 PR |
| G-T2 生値禁止 | patterns / parts / templates | `"fontSize":"<数値>"`・`padding:"<数値>"`・`style="…px"` を検出したら赤（許容: 0 / 1px 罫線 / 100%） | **303 件で赤** → 段階移行（まず新規禁止、既存は #28 の部品整理で置換） |
| G-T3 階層 | patterns / templates | h1〜h4 が単調減少の段を参照 | h2=h3 で赤 |
| G-S1 骨格境界 | 子テーマ・bridge | 層 1 の再定義なし（子 theme.json に `settings.typography/spacing/color` が無い） | V3/V4 |
| G-S2 参照整合 | templates/parts | 参照する part / pattern / navigation ref が存在 | 緑 |
| G-P1 Block validation | patterns | エディタ挿入で invalid 0 | 緑（PR #24 後） |
| G-C1 内容 | 投稿 | インライン style・未登録メタなし | 未実装 |
| G-R1 描画 | PoC 巡回 | 12 種別 × 必須パーツ充足、PHP 警告 0、横スクロール 0 | 緑（本 77/78） |

G-T1〜T3・S1・S2 は静的（CI の Bespoke static gates に追加可）。G-R1 は今日の Web パターン検証器そのもの。

## 4. まとめ
- 「一貫性」の責務は **層 1 の所有権** に集約される。今日の実測では、その層 1 が (V1) コアに上書きされ、(V2) 部品に迂回され、(V3/V4) 別の正本に割り込まれていた。
- 直す順序: V1（1 行の修正で全ページに効く）→ V6 → V3 → V4 の優先順を明文化 → G-T2 を CI に入れて V2 を止血 → #28 の部品整理で既存 303 件を置換。

## 5. 修正後の PoC 実測（V1 修正を配備して確認）

- `defaultFontSizes:false` / `defaultSpacingSizes:false` 適用後、theme 由来のフォント段は **6/6 に復帰**。副作用として h3 が 36px → **20px** になり（`large` プリセットがコアに捨てられて fallback していたのが本来の値に戻った）、V-タイポ階層（h2=h3）も同時に解消した。
- 一方 spacing は `10=0.5 / 20=1 / 30=1.5 / 40=2.5 / 50=4rem` + `60=2.25rem`（コア既定）となり、**bridge の投影（agent_neo_core_design_tokens.spacing = 10〜50 の 5 段、40/50 は親と異なる値）が親の 6 段を置き換え、60 を落とした穴にコア既定が入っている**。V4 の「投影は値の差し替えのみ、段の増減不可」が実際に破られている実例。bridge 側（HELIX）にスキーマ検査を入れるまで、余白の統一性は保証されない。
