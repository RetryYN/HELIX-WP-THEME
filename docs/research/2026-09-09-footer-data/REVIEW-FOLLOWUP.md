# PR #174 レビュー対応

Claudeのレビュー対象は `a5eae4452184639a6b8d11ae322cba49b10da924`。blocker 0という判定はこのHEADに限定され、追加の未コミット差分には適用しない。正式なreceiptは未発行。Node設定を `f8e6124` としてpushし、CI成功後に `github pr-notify` で再依頼済み。追加のカタログ差分はそのHEADには含まれない。

| 指摘 | 現状 | 完了を示す次の証拠 |
| --- | --- | --- |
| [#175](https://github.com/RetryYN/HELIX-WP-THEME/issues/175) lab URL・サイト名によるfixture時刻分岐 | 未修正 | originの宣言化、表示名と独立したfixture有効化、通常環境にfixtureが適用されない実機検査 |
| [#176](https://github.com/RetryYN/HELIX-WP-THEME/issues/176) guard・宣言読込・状態装飾 | 未修正 | ABSPATH guard、欠落/壊れたJSON/不正なrule型でも警告なし、バッジCSS移行後の表示検査 |
| [#177](https://github.com/RetryYN/HELIX-WP-THEME/issues/177) 保護記事全件走査 | 未修正 | 権限境界を維持する設計と、保護記事数を明示した負荷比較。WordPressのpostpass cookieに記事IDがあるとは仮定しない |
| [#178](https://github.com/RetryYN/HELIX-WP-THEME/issues/178) READMEの件数 | ローカル修正済み | 冒頭の固定件数を除き生成JSONを正本化。旧数値は日付付きの過去記録。push後に生成物と再照合 |
| [#179](https://github.com/RetryYN/HELIX-WP-THEME/issues/179) Node宣言不足でreceipt拒否 | ローカル部分修正 | enginesを依存HELIXと同じ範囲へ設定しruntime authority成功。Claudeがreceipt/ACKを実際に記録することは未確認 |
| 階層メニューの空ラベル | 未検証 | navigation-submenuの空/空白名、子リンクの有無、非公開参照を保存・表示して確認 |

#179補足: 通常E2Eは `.node-version` を参照する。管理対象harness-checkへのsetup-node入力追加はconsumer検査で拒否されたため戻した。管理対象CIのNode固定は未解決で、HELIX本体の規則をこのリポジトリから変更しない。

フッターの今回の追加差分では、単体関数を公開表示へ接続し、標準パターンを追加した。編集時の保存HTML不一致と不正なlayout.typeを実機で再現して修正。本文の編集・保存・公開反映は8検査、空/登録/空への表示とPC/SP操作は52検査。メニュー選択・編集UIと全カタログの編集妥当性は残件であり、単体関数の旧レビューで代用しない。
