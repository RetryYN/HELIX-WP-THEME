# カテゴリ一覧の同順位ソート安定化

同じ日付・更新日時・タイトルを持つ投稿の並び順を一意にし、カテゴリ一覧のページ境界で投稿が重複・欠落しないようにする。フロント側のメインカテゴリクエリで、既存の対応順序がそのまま生成される場合に `ID DESC` を第2キーとして加える。

対象は `date` / `modified` / `title` の `ASC` / `DESC` の6通り。管理画面、feed、カテゴリ以外のクエリ、サブクエリ、未対応の並び順や方向は変更しない。WP_Query の `orderby` / `order` 値も変更しない。

## 局所検証記録

手元の非公開実行記録では、WordPress 7.1.2 / PHP 8.3.33 上で、同日時の11投稿・10件ページのカテゴリ load-more を確認した。6通りのSQL順序、対象範囲と除外条件、query var保持を含む15項目が成功し、fixtureとtermの削除・不在確認も成功している。検査時に一時 MU plugin は存在せず、テーマ実装を直接検査した。

390px / 1440px の通常 load-more 操作で観測された INP はそれぞれ 24ms / 32ms。操作受付後のブラウザ内400msブロックを加えた負例では 552ms / 496ms となり、操作の遅延を検出した。通常操作は10件から11件への追加と最終ボタンの消失を確認している。

以下は当時の局所検証で使用したテーマ入力の SHA-256。

| ファイル | SHA-256 |
| --- | --- |
| `docs/research/2026-09-05-design-prototype-03/theme/helix-wt/functions.php` | `d8b14cc959d3a9efe3026c207fa410c4cf487a6af4fe25b73a56a5936148c78d` |
| `docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/category.html` | `15f2fd18bc5136760a5d3c1c6c20b6f1ae27b51a38ddaf32f052f6680b42b94e` |
| `docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/js/category.js` | `5d0e5d27ed700708c27e9afdd21acbb4b06b31ed1cfef6580ad31f39fd69ce30` |

実行記録の環境は Node.js 24.19.0、Chrome 156.0.8075.0、web-vitals 6.2.2。公開要約ではリポジトリ外依存物に対する疑似source digestを使わず、依存バージョンとテーマ入力hashを分けている。

これは保存済みの局所検証結果であり、性能予算の合格や最新ベースでの再検証を意味しない。表の `functions.php` hash は当時のPoC入力を示す履歴値として保持する。2026-09-27時点のbase `54e118a` ではHOME画像helperの `require_once` 行が追加されており、category変更を含む現在の候補ファイル全体は別hashになる。このbase上でのcategory実機再検証は未完了である。
