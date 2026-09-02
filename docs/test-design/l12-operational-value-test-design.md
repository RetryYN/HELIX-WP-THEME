# L12 Operational Value Test Design

| Test ID | Business requirement | measurement |
| --- | --- | --- |
| WT-OT-01 | WT-BR-01 | capability manifest の列挙率、PHP のみに存在する面・部品の件数（0 であること） |
| WT-OT-02 | WT-BR-02 | 12 種別の必須パーツ充足率、未整備 16 項目と 2026-09-03 採用 10 件の受け皿有無 |
| WT-OT-03 | WT-BR-03 | AI 経路で JSON 外の操作を要した件数、variant / CV ID の欠落件数、破壊域停止件数、誤警告件数 |
| WT-OT-04 | WT-BR-04 | 実証記録の証跡付き率、他リポへの参照・書き込み件数（0 であること） |
| WT-OT-05 | WT-BR-05 | theme / plugin の AI SDK import・判定ロジックの静的検出件数（0 であること） |
| WT-OT-06 | WT-BR-03 | 商品正本から販売系語彙への反映率、商品 CTA クリックの計測経路欠落率、購入完了をテーマが扱った件数（0 であること） |
| WT-OT-07 | WT-BR-01 | bot 識別済みクロールの記録件数、クローラー別推移・古い URL・404 / 5xx・初回捕捉時間・AI クローラーアクセスの確認可能率、個人閲覧の記録件数（0 であること） |
| WT-OT-08 | WT-BR-03 | A/B の登録・cookie 固定配信・variant ID 付き impression / click / CV、停止後の既定案復帰、承認 / rollback の記録率 |
| WT-OT-09 | WT-BR-02 | WebP / WebM の生成・配信率、非同期画像処理の完了率、alt・Discover 警告の検出率、画像の性能予算超過件数 |
| WT-OT-10 | WT-BR-03 | JS 無し表示の成立率、使用分 CSS のみの出力率、Lighthouse / Core Web Vitals の 4 ページ種別測定率と blocking gate 通過率 |
| WT-OT-11 | WT-BR-01 | API の差分読み取り・batch・署名付き push・schema version / 旧版併走の契約検査率、OpenAPI 差分の見逃し件数 |
| WT-OT-12 | WT-BR-03 | MCP 常用パック・REST・WP-CLI の能力集合一致率と、ずれを契約テストで赤にした件数 |
| WT-OT-13 | WT-BR-03 | 操作ログの絞り込み / export 率、差分レビューの適用・却下・rollback の記録率、鍵の一度だけ表示・失効率 |
| WT-OT-14 | WT-BR-03 | SNS profile の一元反映率、share / feed / LP CTA の表示率、埋め込みスクリプトの遅延率、資格情報の公開件数（0） |
| WT-OT-15 | WT-BR-03 | CV 定義の ID / 到達条件の一致率、資料 DL 完了のマイクロ CV 計測率、任意 microcopy 未選択をエラーにした件数（0） |
| WT-OT-16 | WT-BR-03 | バナー正本・ゾーン割当・impression / click の CV ID・variant 接続率、期限切れ・リンク切れ・計測ゼロ警告率、監査の適用経路遵守率 |

破壊域の境界値が PoC で未確定の間、WT-OT-03 の誤警告件数は測定不能として pass を出さない。
