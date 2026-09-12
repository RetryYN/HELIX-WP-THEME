# PR #174 レビュー対応

Claudeの最新レビュー対象は `ea0227bfa8f52455f082e37d037d734ab6a38999`。コード上のblocker 0という判定はこのHEADに限定され、今回の設定JSON修正には適用しない。正式receiptは#181の権限解釈を理由に保留。POの既存指示「このなかに入っている開発ツールとしてのHELIXは修正していいが、別リポジトリは触るのを禁止する。」を原文とCodex側の解釈を分けて#181へ共有し、再評価を依頼した。

| 指摘 | 現状 | 完了を示す次の証拠 |
| --- | --- | --- |
| [#175](https://github.com/RetryYN/HELIX-WP-THEME/issues/175) lab URL・サイト名によるfixture時刻分岐 | 未修正 | originの宣言化、表示名と独立したfixture有効化、通常環境にfixtureが適用されない実機検査 |
| [#176](https://github.com/RetryYN/HELIX-WP-THEME/issues/176) guard・宣言読込・状態装飾 | ローカル部分修正（JSON読込） | ABSPATH guard、欠落/壊れたJSON/不正なrule型でも警告なし、バッジCSS移行後の表示検査 |
| [#177](https://github.com/RetryYN/HELIX-WP-THEME/issues/177) 保護記事全件走査 | 未修正 | 権限境界を維持する設計と、保護記事数を明示した負荷比較。WordPressのpostpass cookieに記事IDがあるとは仮定しない |
| [#178](https://github.com/RetryYN/HELIX-WP-THEME/issues/178) READMEの件数 | 86dbdb8で修正・Claude確認済み | 冒頭の固定件数を除き生成JSONを正本化。旧数値は日付付きの過去記録。push後に生成物と再照合 |
| [#179](https://github.com/RetryYN/HELIX-WP-THEME/issues/179) Node宣言不足でreceipt拒否 | ea0227bで技術対応・正式receipt保留 | enginesを依存HELIXと同じ範囲へ設定しruntime authority成功。Claudeがreceipt/ACKを実際に記録することは未確認 |
| 階層メニューの空ラベル | 未検証 | navigation-submenuの空/空白名、子リンクの有無、非公開参照を保存・表示して確認 |

#179補足: 通常E2Eは `.node-version` を参照する。管理対象harness-checkへのsetup-node入力追加はconsumer検査で拒否されたため戻した。管理対象CIのNode固定は未解決。この記録後にPOから同梱開発ツールの修正許可があり、ea0227bでconsumer専用receiptを内包パッチとして追加した。別リポジトリと本体G3判定は変更していない。

フッターの今回の追加差分では、単体関数を公開表示へ接続し、標準パターンを追加した。編集時の保存HTML不一致と不正なlayout.typeを実機で再現して修正。本文の編集・保存・公開反映は8検査、空/登録/空への表示とPC/SP操作は52検査。メニュー選択・編集UIと全カタログの編集妥当性は残件であり、単体関数の旧レビューで代用しない。

#176のJSON読込: 欠落・不正ruleを除外し、正常な後続ruleを維持する。PHP単体11、実機継承428・ヘッダーナビ62・コンテンツ191・学習252・常設案内397・品質265が成功。変更コードを証跡ハッシュへ追加し、台帳21条件を再照合。古い証跡0、PoC確認13・部分17・未対応264を維持した。event-state guardとバッジCSSは残件。
