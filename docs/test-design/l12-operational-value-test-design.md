# L12 Operational Value Test Design

| Test ID | Business requirement | measurement |
| --- | --- | --- |
| WT-OT-01 | WT-BR-01 | capability manifest の列挙率、PHP のみに存在する面・部品の件数（0 であること） |
| WT-OT-02 | WT-BR-02 | 12 種別の必須パーツ充足率、未整備 16 項目と 2026-09-03 採用 25 件の受け皿有無 |
| WT-OT-03 | WT-BR-03 | AI 経路で JSON 外の操作を要した件数、variant / CV ID の欠落件数、破壊域停止件数、誤警告件数 |
| WT-OT-04 | WT-BR-04 | 実証記録の証跡付き率、他リポへの参照・書き込み件数（0 であること） |
| WT-OT-05 | WT-BR-05 | theme / plugin の AI SDK import・判定ロジックの静的検出件数（0 であること） |
| WT-OT-06 | WT-BR-03 | 商品正本から販売系語彙への反映率、商品 CTA クリックの計測経路欠落率、購入完了をテーマが扱った件数（0 であること） |
| WT-OT-07 | WT-BR-01 | bot 識別済みクロールの記録件数、クローラー別推移・古い URL・404 / 5xx・初回捕捉時間・AI クローラーアクセスの確認可能率、llms.txt の既定出力の効果実証用アクセス時系列の追跡可能率、個人閲覧の記録件数（0 であること） |
| WT-OT-08 | WT-BR-03 | A/B の登録・cookie 固定配信・variant ID 付き impression / click / CV、停止後の既定案復帰、承認 / rollback の記録率 |
| WT-OT-09 | WT-BR-02 | ブラウザ側を第一経路とする WebP / WebM の生成・配信率、非ブラウザ経路の非同期画像処理の完了率、alt・Discover 警告の検出率、画像の性能予算超過件数、二重処理件数（0 であること） |
| WT-OT-10 | WT-BR-03 | サイト設定で選んだ主たる確認面（既定 SP）と PC の両幅における JS 無し表示の成立率、使用分 CSS のみの出力率、Lighthouse / Core Web Vitals の 4 ページ種別・両幅測定率と blocking gate 通過率 |
| WT-OT-11 | WT-BR-01 | API の差分読み取り・batch・署名付き push・schema version / 旧版併走の契約検査率、OpenAPI 差分の見逃し件数 |
| WT-OT-12 | WT-BR-03 | MCP 常用パック・REST・WP-CLI の能力集合一致率と、ずれを契約テストで赤にした件数 |
| WT-OT-13 | WT-BR-03 | 操作ログの絞り込み / export 率、差分レビューの適用・却下・rollback の記録率、鍵の一度だけ表示・失効率 |
| WT-OT-14 | WT-BR-03 | SNS profile の一元反映率、share / feed / LP CTA の表示率、埋め込みスクリプトの遅延率、資格情報の公開件数（0） |
| WT-OT-15 | WT-BR-03 | CV 定義の ID / 到達条件の一致率、資料 DL 完了のマイクロ CV 計測率、任意 microcopy 未選択をエラーにした件数（0） |
| WT-OT-16 | WT-BR-03 | バナー正本・ゾーン割当・impression / click の CV ID・variant 接続率、期限切れ・リンク切れ・計測ゼロ警告率、監査の適用経路遵守率 |
| WT-OT-17 | WT-BR-02 | 共通 + device 別差分の面・語彙・パーツ、主たる確認面（既定 SP）、SP / PC 両幅プレビューの要求カバレッジと theme.json 尺度の宣言一致率 |
| WT-OT-18 | WT-BR-03 | SP ヘッダー・ドロワー・下部固定・専用広告面の device 別選択率、SP / PC 語彙挙動の JSON 宣言・検査率、重い面の slot 条件描画率、未宣言面の出力件数（0 であること） |
| WT-OT-19 | WT-BR-03 | SP / PC の 44px / 16px / 横スクロール 0・固定面の本文 / CTA 被覆 0、Lighthouse mobile / PC 幅、両幅 CWV、device type 別 A/B / CV 集計の実施率 |
| WT-OT-20 | WT-BR-01 | head・body 開始直後・body 終端のタグ slot 充足率、version 付きデータ層契約の必須 ID 充足率、3 カテゴリから Consent Mode v2 7 種への写像表と consent default 最初の注入順の検査率、UI / MCP / DB 選択の一致率 |
| WT-OT-21 | WT-BR-03 | 同意前の計測 / 広告タグ非発火率、同意なしイベント保存件数（0）、slot 外スクリプト件数（0）、Consent Mode v2 7 種への写像・consent default 注入順の違反件数（0）、タグ転送の性能予算内率 |
| WT-OT-22 | WT-BR-01 | 第三者プラグインの検出結果・領域別既定・現在の選択・警告を capability manifest と WT-UI-10 / MCP で一致して表示できる率 |
| WT-OT-23 | WT-BR-03 | 代表 2 サイト構成におけるフォーム二重送信、JSON-LD / meta / OGP 重複、画像生成重複、同意バー重複、キャッシュ警告前の A/B 配信の件数（0） |

破壊域の境界値が PoC で未確定の間、WT-OT-03 の誤警告件数は測定不能として pass を出さない。
