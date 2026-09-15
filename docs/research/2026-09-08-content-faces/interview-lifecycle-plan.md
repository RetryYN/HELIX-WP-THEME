# インタビュー掲載状態の再確認

WT-AC-INTERVIEW-01B/Cについて、公開後の確認取り消しと人物参照の破損を調べる。従来の検証は確認前の固定draftと確認済みの固定publishだけであり、メタデータ更新経路の状態遷移を証明していない。

WordPressの投稿保存前フィルターとメタデータ更新・削除後のフックは異なる経路である。出所: [投稿保存前](https://developer.wordpress.org/reference/hooks/wp_insert_post_data/)、[メタデータ更新後](https://developer.wordpress.org/reference/hooks/updated_meta_type_meta/)、[メタデータ削除後](https://developer.wordpress.org/reference/hooks/deleted_meta_type_meta/)。本文の転載はせず、専用labの架空レコードで実測する。

`node scripts/verify-interview-lifecycle.mjs`は予約済みslugの重複を拒否し、その実行だけが所有する投稿を作成・削除する。他のWP検査や撮影と同時実行しない。確認済み→確認取り消し→再確認（公開操作待ち）→明示再公開→存在しない人物ID→不正なまま公開試行→文書削除を検証する。

公開可否は詳細HTTP・REST個別/一覧・サイト検索・feed・アーカイブ・埋め込みカード・表示projectionを対象とする。確認取り消しや不正参照は独立プラグインでdraftへ戻し、テーマ変更時も公開判断を維持する。再確認だけで自動公開せず、編集者が公開操作を行う。人物と発言の対応が壊れた場合に、一部発言を黙って省いて「確認済み」と表示しない。

既に配送された第三者キャッシュの消去や、外部編集サービスとの競合制御の実証は本検査の範囲外として残す。全要求カタログの完了や製品の受入完了を意味しない。

## 実測

修正前（56a3e26）は64検査中21成功・43不成立。確認取り消し後も公開状態と本文が残った。修正後は、修復して再公開してから文書削除する経路を追加した73検査が全件成功。RESTの非公開応答は401/403/404と本文不在を照合し、詳細は404を確認する。先行の固定fixture検証だけでは見つからなかったため、WT-EVT-0307でWT-FR-INTERVIEW-01 revision 3とAC-01Cへ明記した。
