# 静的6ゲート・G-E1 契約 PoC

`WT-NFR-GATE-01` の既存証跡を、カタログの受入候補として再現可能な契約へ束ねる。対象は `G-T1 / G-T1b / G-T2 / G-T3 / G-S1 / G-S2` の静的検査と、WordPress 7.1 の 71 パターンを対象にした `G-E1 invalid=0` の実機記録である。

静的検査は `bin/check-design-consistency.sh` をそのまま実行し、出力に6ゲート、`FAIL=0`、実測の `WARN=1` があることを確認する。契約値と各出力 marker の欠落・改変を個別の負例で拒否する。G-E1 は `docs/research/2026-08-29-ge1-local/editor-validate-2026-08-29.json` の71行を再集計し、invalidブロックが0であることを確認する。認証情報、接続先、外部送信は証跡へ書かない。

これは部分証跡である。G-E1 は過去のローカル Docker WP 7.1 での実行記録で、現行HEADの再実行 receipt ではない。required check、G-E1、独立レビュー receipt を同じ current HEAD へ束縛する完了運用は残件として開く。静的 PASS だけで実機結果を完了扱いしない。
