# L10 System Acceptance Test Design

本書は L2 candidate から抽出した oracle inventory である。画面合意後に L3 requirement を compile する際の右腕候補であり、
現時点では L10 成果物、pair freeze、G3 到達を主張しない。

| Test ID | Requirement | positive oracle | negative/boundary oracle | evidence |
| --- | --- | --- | --- | --- |
| WT-AT-CORE-01 | WT-TR-CORE-01 | 新しい slot / パターン / variation を追加すると capability manifest に現れる | JSON 宣言なしに PHP で登録された面・部品が検出されたら FAIL | manifest diff |
| WT-AT-CORE-02 | WT-TR-CORE-02 | health の出力がテーマの登録内容と一致する | 登録内容と health の差分が 1 件でもあれば FAIL | health JSON |
| WT-AT-CORE-03 | WT-TR-CORE-03 | 静的検出で AI SDK import・判定ロジックが 0 | 検出 1 件で FAIL | static analysis |
| WT-AT-GATE-01 | WT-NFR-GATE-01 | PR の HEAD で静的 FAIL=0 かつ G-E1 invalid=0 の receipt が同一 HEAD に束縛される | 静的 PASS のみで merge された変更が実機 invalid を生めば FAIL | gate JSON |
| WT-AT-TR-PLUGIN-01 | WT-TR-PLUGIN-01 | テーマ切替後もデータと API が残り、プラグイン無効でも表示は崩れない | テーマ切替でデータ消失、プラグインに表示 / 判定、プラグイン無効で公開面破損なら FAIL | theme-switch fixture + static analysis |
| WT-AT-TR-PLUGIN-02 | WT-TR-PLUGIN-02 | 重なる 7 領域それぞれで第三者プラグイン有効時に出力が 1 系統になり設定で切替可、フォームは LP と接続、移行機能は移行プラグイン無効で消える | いずれかの領域で同型出力 2 本、切替不可、または本体に移行コード・互換固有名なら FAIL | plugin matrix（7 領域）+ JSON-LD extract |
| WT-AT-ZONE-01 | WT-FR-ZONE-01 | 各 slot にパターンを置くと該当位置に描画され、空なら DOM に残らない | 空 slot が空要素や領域見出しを出せば FAIL | Playwright |
| WT-AT-ZONE-02 | WT-FR-ZONE-02 | schema に無いゾーン ID は拒否され、overrides は最初に一致した規則だけが適用される | 複数規則が同時適用される、または未定義ゾーンが通れば FAIL | schema test |
| WT-AT-ZONE-03 | WT-FR-ZONE-03 | 3 要素を同時に有効化しても規約の順で積層し、CTA と重ならない | 重なりや順序違反があれば FAIL | Playwright |
| WT-AT-PARTS-01 | WT-FR-PARTS-01 | 差し替え後の骨格差分（テンプレ・パーツ参照）が表示され、保存後に公開面へ反映される | 差し替えで参照欠落（G-S2）や invalid が出れば FAIL | Playwright + G-S2 |
| WT-AT-PARTS-02 | WT-FR-PARTS-02 | wp_navigation を更新すると header に反映され、変種切替で contentSize / wideSize が再定義されない | header にハードコードされた navigation-link が残る、または変種が層 1 を再定義すれば FAIL | parts reference test |
| WT-AT-VOCAB-01 | WT-FR-VOCAB-01 | 14 語彙それぞれに受け皿（core + style / 新規ブロック）が対応表で 1 対 1 に決まり、実使用上位 7 種が描画される | 受け皿のない語彙、または 4 つ目の新規ブロックがあれば FAIL | vocab fixture |
| WT-AT-VOCAB-02 | WT-FR-VOCAB-02 | h2 を持つ記事で目次が選択した配置方式（埋め込み / フロート / 開閉）で出て、ページ種別設定と投稿メタで表示・非表示が切り替わり、見出しを変えると目次が追従する | 目次本文が保存 HTML に固定される（h2 変更で不整合）、または非表示にしたページ種別で目次が出れば FAIL | render fixture + Playwright |
| WT-AT-VOCAB-03 | WT-FR-VOCAB-03 | 広告パーツまたはアフィリエイトリンクを含む記事にだけ控えめな PR 表記がファーストビュー内に自動で出て、含まない記事には出ない。表示デザインと表示ページ制御が選べる | 対象記事で表記が欠落する、対象外の記事に出る、または本文編集で消せれば FAIL | Playwright + fixture |
| WT-AT-VOCAB-04 | WT-FR-VOCAB-04 | 内部リンクカードが REST 呼び出しなしで描画される | 未認証 REST または未検証 file_get_contents が経路にあれば FAIL | REST audit |
| WT-AT-SECTION-01 | WT-FR-SECTION-01 | H2 / H3 が混在する記事で階層 section と親子 ID が中間 JSON に出て、見出し文言を変えても ID が変わらない | 見出し変更で ID がずれる、H4 が区間になる、または H3 の無い H2 区間で境界が壊れれば FAIL | extractor fixture |
| WT-AT-SECTION-02 | WT-FR-SECTION-02 | H3 区間だけのリライトで diff が区間に閉じ rollback で戻る。順序入れ替え・折りたたみ・非表示・目次出し分けがエディタと MCP で同結果。区間規則が全記事に入り投稿単位で上書き可。区間の到達・滞在イベントが記録される | 区間外が書き換わる、規則が投稿単位で上書きできない、またはテーマ内に区間選定の判定ロジックが入れば FAIL | REST receipt + Playwright + tracking receipt |
| WT-AT-LOOK-01 | WT-FR-LOOK-01 | 見出し尺度が単調非増加（G-T3 PASS）で、style と variant が block style として列挙される | 生値や !important で実現された装飾があれば FAIL | G-T3 + style list |
| WT-AT-LOOK-02 | WT-FR-LOOK-02 | 写像した variation がスラッグ集合を変えず G-T1b PASS | 段の増減や新スラッグを伴う variation があれば FAIL | G-T1b JSON |
| WT-AT-LOOK-03 | WT-FR-LOOK-03 | サイトパターンごとに調査証跡（対象数・採取項目・分布）が digest 束縛され、そこから導出した variation / block style が G-T1b / G-T3 PASS | 調査証跡のないサイトパターンの variation が要求または実装に入れば FAIL | survey inventory + gate JSON |
| WT-AT-META-01 | WT-FR-META-01 | メタを OFF にした記事だけで該当パーツが消え、REST から同じキーを読み書きできる | 未登録メタや option で表示が変わる経路があれば FAIL | REST + Playwright |
| WT-AT-ADMIN-01 | WT-FR-ADMIN-01 | 管理画面だけで各既定を変更・保存でき、保存値が schema 検証を通り、manifest と MCP パックから同じ値が読め、export → import で同一 digest に戻る | schema 外の値が保存できる、設定画面と manifest の値が食い違う、または設定 JSON 以外の option に状態が散れば FAIL | Playwright + schema test + manifest parity |
| WT-AT-LP-01 | WT-FR-LP-01 | 決定した方式で LP がヘッダーなしで組め、移行台帳に方式差（URL 構造・CPT 有無）が記録される | 方式差が台帳に無いまま移行されれば FAIL | Playwright + ledger |
| WT-AT-LP-02 | WT-FR-LP-02 | LP でフォームを JSON 宣言で配置でき、表示・スクロール・CTA クリック・送信のイベントが tracking 経路に記録され、LP 専用 variation / パターンが選べる | 計測イベントが欠落する、またはテーマ内に最適化・判定ロジックが入れば FAIL | tracking receipt + Playwright |
| WT-AT-MIGRATE-01 | WT-FR-MIGRATE-01 | 取得項目定義とマッピングフォーマットが JSON schema を持ち、サンプル control（A 400 / B 400）の各行がフォーマット上で 4 分類のどれかまたは理由付き写像不能に落ちる | フォーマットに無い写像を変換器が行う、または分類も写像不能理由も無い行があれば FAIL | schema test + mapping receipt |
| WT-AT-MIGRATE-02 | WT-FR-MIGRATE-02 | 代表 6 領域の変換が invalid=0 で、独自ウィジェット・写像不能候補が台帳に件数付きで残る | 変換で invalid が出る、または写像不能が黙って落ちれば FAIL | conversion receipt |
| WT-AT-MIGRATE-03 | WT-FR-MIGRATE-03 | 移管一覧が意味キーだけで構成され、公開リポに第三者固有名が無い | 見た目キーの機械移送や固有名の混入があれば FAIL | public-safety check |
| WT-AT-AGENT-01 | WT-FR-AGENT-01 | 設定で定義した常用パックが MCP の ability として列挙され、1 回の呼び出しで manifest 上の作業単位が dry-run 差分付きで完結し、apply 結果が dry-run と一致する | manifest 外の指定が通る、apply が dry-run と異なる、または REST と MCP で語彙や結果が食い違えば FAIL | MCP receipt + REST parity |
| WT-AT-AGENT-02 | WT-FR-AGENT-02 | 同じ本文から同じ JSON が出て、循環参照で停止する | enqueue 等の副作用や無限ループがあれば FAIL | extractor fixture |
| WT-AT-AGENT-03 | WT-FR-AGENT-03 | hook 一覧が manifest と実装で一致する | manifest に無い hook、または manifest にあるが発火しない hook があれば FAIL | hook audit |
| WT-AT-AGENT-04 | WT-FR-AGENT-04 | 参照先を更新すると利用側の digest 記録が差分として検出できる | 展開保存された複製があれば FAIL | digest diff |
| WT-AT-AGENT-05 | WT-TR-AGENT-05 | OpenAPI の全 path が自前名前空間で、変換層ごとに「入力が同じなら出力が同じ」が成り立つ | wp/v2 配下の自前ルート、または層をまたぐ副作用があれば FAIL | OpenAPI diff |
| WT-AT-AGENT-06 | WT-FR-AGENT-06 | サイト内で作ったパーツが manifest に出て、ゲート通過後にテーマパターンとして登録され、台帳に版・digest・ゲート結果が残る。別テーマ（同トークン契約）で invalid 0 で描画される | 生値を含むパーツが昇格する、または台帳なしでテーマに入れば FAIL | G-E1 + ledger + cross-theme fixture |
| WT-AT-VALUE-01 | WT-FR-VALUE-01 | 破壊域の入力が停止し、どの規則にどの値がどの境界で触れたかが表示され、権限による解除手段が無い | 破壊域が保存できる、または安全域が誤って止まれば FAIL | editor + gate JSON |
| WT-AT-VALUE-02 | WT-FR-VALUE-02 | 許容リスト外の生値が G-T2 で FAIL になり、件数が baseline から単調減少する | 許容リストに実値を足して通す変更があれば FAIL | gate JSON |
| WT-AT-VALUE-03 | WT-NFR-VALUE-03 | 段を増減する投影が拒否され、親の 6 段が維持される | 投影で 60 が落ちてコア既定が入る（PoC 実測）状態が再現すれば FAIL | projection fixture |
| WT-AT-SEO-01 | WT-FR-SEO-01 | 一覧ページに CollectionPage、全ページに 1 graph の JSON-LD が出る | 同型の二重出力や @type 空のスクリプトがあれば FAIL | JSON-LD extract |
| WT-AT-SEO-02 | WT-FR-SEO-02 | FAQ 語彙を含む記事に FAQPage が出て、項目数が本文と一致する | 本文に無い項目が JSON-LD に出れば FAIL | JSON-LD extract |
| WT-AT-SEO-03 | WT-FR-SEO-03 | 12 種別すべてで canonical・description・OGP が出て、AI 向け出力の digest が HTML と整合する | front / archive / search で欠落すれば FAIL | crawl JSON |
| WT-AT-INTAKE-01 | WT-FR-INTAKE-01 | 台帳 1 行から証跡と参照元 commit へ辿れ、ゲート結果が同一 HEAD に束縛される。台帳は本リポ内のパスだけを参照する | 証跡なしの行、他リポの状態に依存する項目、または他プロダクトの成果物を本テーマへ取り込む行があれば FAIL | ledger validation |
| WT-AT-SEC-01 | WT-NFR-SEC-01 | REST 監査で未認証ルート 0、SSRF 形 0、公開面 Warning 0 | 1 件でもあれば FAIL | REST audit |
| WT-AT-REL-01 | WT-NFR-REL-01 | 閲覧・クロール前後で option / theme_mod の diff が 0。正規化リダイレクトが動く | 描画時 write または正規化停止があれば FAIL | option diff |
| WT-AT-PRIV-01 | WT-NFR-PRIV-01 | テーマ・プラグインに計測 ID・広告 HTML の生値が 0 | 検出 1 件で FAIL | static grep |
| WT-AT-PERM-01 | WT-NFR-PERM-01 | 管理者権限でも破壊域が保存できず、receipt なしの apply が拒否される | 迂回経路 1 件で FAIL | capability test |
| WT-AT-COST-01 | WT-NFR-COST-01 | CI と実機ゲートが外部 API なしで green | 外部 API 呼び出しを含むゲートがあれば FAIL | dependency audit |
| WT-AT-LEGAL-01 | WT-NFR-LEGAL-01 | 対象記事の表記欠落 0、同梱資産のライセンス台帳あり | 欠落 1 件で FAIL | Playwright + license ledger |
| WT-AT-OBS-01 | WT-NFR-OBS-01 | 各出力に HEAD と digest があり再実行で一致する | 不一致 1 件で FAIL | CI artifact |
| WT-AT-A11Y-01 | WT-NFR-A11Y-01 | axe gate 違反 0、mobile 横スクロール 0 | 違反 1 件で FAIL | axe / contrast |
| WT-AT-REC-01 | WT-NFR-REC-01 | apply 後の rollback で変更前 digest と一致する | 不一致 1 件で FAIL | rollback receipt |
| WT-AT-CRED-01 | WT-NFR-CRED-01 | public-safety check OK | 検出 1 件で FAIL | public-safety check |
| WT-AT-PERF-01 | WT-NFR-PERF-01 | 予算超過 0、未使用ブロック CSS 0 | 超過 1 件で FAIL | transfer size report |
