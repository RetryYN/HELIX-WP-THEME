# THEME-INV-10: ショートコード後方互換の扱いを決める

labels: investigation, compat, priority:low
depends: THEME-INV-01

## 背景（実測）
- JIN:R: 6 種。**実使用あり** — `[jinr_fukidashi]` **186 回**・`[jinr_profile]` 1・`[jinr_heading_iconbox]` 1
- SWELL: 20 種（日本語別名 `[ふきだし]` `[アイコン]` `[カスタムバナー]` `[ブログパーツ]` を含む）。**実使用 0**
- 他プラグイン由来: `[smartslider]` 1・`[contact]` 1（Contact Form 7）
- agent-neo: ショートコード 0

`[jinr_fukidashi]` 186 回はブロック `jinr-blocks/fukidashi` 186 回と同数 — **ブロックが内部でショートコードを出している**可能性が高く、要確認。

## 調査項目
1. `[jinr_fukidashi]` の 186 回がブロック由来か手書きかを本文解析で判定
2. 手書きショートコードが残る場合、中間 JSON でどう表すか（不透明ノードとして保持 / 展開 / 拒否）
3. プラグイン由来ショートコード（CF7・SmartSlider）の扱い方針を決める
4. 日本語別名の存在が示す設計意図（非技術者運用）を Graphix NEO の要求として拾うか判断

## 完了条件
- [ ] ショートコード実使用の由来が判明している
- [ ] 中間 JSON における表現方式が決まっている
- [ ] プラグイン由来の扱い方針が決まっている
