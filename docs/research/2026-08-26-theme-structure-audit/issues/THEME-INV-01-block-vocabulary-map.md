# THEME-INV-01: ブロック語彙 3 系統の対応表を確定する

labels: investigation, blocks, priority:high
depends: なし（最初に着手すべき 1 本）

> **状態: 一次完了**（2026-08-26）／レポート: `../reports/INV-01-block-vocabulary-map.md`
> 75 ブロックを意味 31 種へ正規化。両テーマに専用ブロックがある 11 組を意図語彙の第一候補と確定。
> インライン書式は「テキストの装飾レンジ」として持つと判定。
> **残**: 属性層の帰納（INV-14 の `extract-themeA-attrs.sh` 実行で閉じる）と意図語彙の命名確定。

## 背景（実測）
- テーマA: `themeA-blocks/` **25 種**（functions.php 一括登録・単一 editor バンドル・`THEMEA_VAR` 依存）
- テーマB: `themeB/` **50 種**（block.json + 自前ビルド。インライン書式・コア拡張を含む）
- agent-neo: カスタムブロック **1 種**（`agent-neo/embed`）のみ。パターン 24 で構成する方針

3 系統は名前も粒度も揃っていない（例: 吹き出しは `themeA-blocks/fukidashi` / `themeB/balloon` / `[speech_balloon]` / `[ふきだし]`）。

## 調査項目
1. 25 + 50 の全ブロックについて **attributes / innerBlocks 構造 / save 出力マークアップ**を採取する（読み取り専用）
2. 意味が一致するもの・片方にしか無いもの・粒度がずれるもの（親子ブロック分割の有無）を分類
3. 中間 JSON の**意図語彙（doc_type 共通語彙）候補**へ写像し、写像不能なものを列挙
4. インライン書式（`themeB/marker` `themeB/font-size` 等 9 種）を中間 JSON でどう表すかを別枠で検討

## 完了条件
- [ ] 75 ブロック × (attributes・出力マークアップ・使用数) の一覧が証跡付きで存在する
- [ ] 意図語彙への写像表（一致 / 部分 / 写像不能）が埋まっている
- [ ] 写像不能ブロックが理由付きで列挙され、後続イシューへ振られている
