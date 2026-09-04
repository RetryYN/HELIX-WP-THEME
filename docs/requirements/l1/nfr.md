---
layer: L1
sub_doc: nfr
status: g1_approved
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Non-functional Requirements

| ID | 品質要求 | 測定方向 |
| --- | --- | --- |
| WT-NFRL1-01 | 4 層一貫性（トークン→骨格→部品→内容）を静的ゲートで守る。層 1 だけが尺度を持ち、下位は名前で参照する | G-T1 / T1b / T2 / T3 / S1 / S2 FAIL=0 |
| WT-NFRL1-02 | 静的検査で通っても実機で壊れる事例があるため、パターン・パーツ変更は実機ゲート（Block validation）を完了条件にする | G-E1 invalid=0 / 全パターン |
| WT-NFRL1-03 | 描画に副作用を持たない: 描画パスで DB へ書かない。同じ入力は同じ出力になる（決定論） | 描画時 option / theme_mod write 0。同一入力の出力 digest 一致 |
| WT-NFRL1-04 |未認証 REST を持たない。外部 URL 取得は検証付き HTTP 経由のみ。PHP Warning を公開面へ出さない 公開と実行許可を分離し、ability の permission callback、公開ファイル非配置、応答ヘッダ境界を検査する。| 未認証ルート 0。SSRF 形の関数呼び出し 0。公開面 Warning 0 |
| WT-NFRL1-05 |アクセシビリティ: 域の判定・状態表示は色だけに依存しない。img alt 欠落 0。AA コントラスト WCAG 2.2 AA 到達目標、APG 契約、reduced-motion、24px 下限と 44px 上限目標を含める。| axe gate 違反 0 |
| WT-NFRL1-06 |性能予算: web-vitals-budget を維持する。面・語彙を増やしても CSS は使用分だけ読む。ページ種別ごとの予算は JSON で列挙し、主たる測定面はサイト設定で選ぶ（既定は SP 幅）。SP / PC の両幅を性能検査の対象とする Baseline fallback、和文フォント、bfcache、Lighthouse の固定版を速度・互換ゲートへ含める。| 予算超過 0。未使用ブロック CSS の読み込み 0 |
| WT-NFRL1-07 |観測性: health / gate / 台帳の出力は JSON で機械可読。証跡は HEAD と digest に束縛する hosting capability、クローラー台帳、ログ集約、外部送信公表を JSON と digest で自己申告する。| JSON 出力率 100%。digest 不一致 0 |
| WT-NFRL1-08 |credential・実サイト固有名・第三者製品名を公開リポジトリへ置かない。接続情報は環境変数 OFL 全文・資産台帳・SECURITY.md・lockfile・署名・i18n を配布ゲートで検査する。| public-safety check OK |
| WT-NFRL1-09 |復旧: 変更は dry-run → apply → rollback の経路を持ち、失敗時に元へ戻る 選択セットとログを dry-run → apply → rollback の対象へ含める。| rollback 成功率 100% |
| WT-NFRL1-10 |法令: PR 表記（景表法ステマ規制）は編集者が消せない位置に出る PR の根拠・打消し表示を同視野・同サイズで検査し、編集者が消せない規律を保つ。| 対象記事の表記欠落 0 |
| WT-NFRL1-11 | プライバシー: 計測・広告タグの正本はテーマ外（HELIX / プラグイン側）。人気の自前集計を選んだ場合に限り、IP を持たない日次集約のみを「人の閲覧を記録しない」原則の例外とする。 | テーマ内の計測 ID 0。自前集計で IP 保存 0、日次集約以外の人の閲覧記録 0 |
| WT-NFRL1-12 | 権限で破壊域停止を迂回できない | 迂回経路 0 |
| WT-NFRL1-13 | コスト: ゲートと PoC はローカル docker で完結し、有料・無料枠制限のある外部 API に依存しない | 外部 API 依存のゲート 0 |
| WT-NFRL1-14 |SEO 準拠検査は test lane で走り、Google 公式ドキュメントの出典 URL と参照日を改定時に更新する 廃止型・schema・robots・favicon・日付・canonical の公式出典と参照日を監視する。| SEO 準拠検査の未実施 0。出典 URL / 参照日未更新 0 |
| WT-NFRL1-15 |クロールログは既定 90 日で保持・間引きし、bot 判定外の個人閲覧を記録しない。WP が応答したリクエストだけを対象とし、キャッシュ / CDN 応答は見えない限界を明記する WP 応答の直接ログと cache / CDN 由来の応答を区別し、取り込み時に人の行と IP を捨て、短期生行・長期集約と容量上限を守る。| 保持期間・間引きの逸脱 0。bot 判定外の個人閲覧の記録 0。対象・限界の未明記 0 |
| WT-NFRL1-16 | JS 無しで記事・LP・一覧・商品比較の全表示が成立する。動きは CSS / HTML 標準機能を優先し、必要な JS と第三者スクリプトは遅延注入する。共通の描画定義に device 別差分を持たせ、主たる描画・計測面はサイト設定で選ぶ（既定は SP 幅）。SP / PC の両幅を検査する | JS 無し表示の欠落 0。遅延対象外の不要 JS 0 |
| WT-NFRL1-17 |CSS を語彙単位で分割し、使用分だけ出す。共通 CSS は fluid で適用し、device 別差分も必要な幅だけ出す。ビルドで短縮化し、critical CSS は inline、残りは非同期、フォントは自己ホスト + `font-display: swap` とする。転送量予算は SP / PC の両幅で JSON 検査する 和文フォントの自己ホスト・サブセット・速度予算、未対応 CSS の fallback、タグ転送量を検査する。| 未使用 CSS 0。CSS / JS / 画像転送量の JSON 予算超過 0 |
| WT-NFRL1-18 |記事・LP・一覧・商品比較で Lighthouse / Core Web Vitals を測り、LCP 2.5s / INP 200ms / CLS 0.1 の閾値割れを CI blocking gate とする。主たる測定面はサイト設定で選び（既定は SP 幅）、SP / PC の両幅を測定対象とする bfcache 条件と Lighthouse major / insight ID の固定を CI に含める。| 閾値割れの CI 通過 0。4 ページ種別・両幅の測定漏れ 0 |
| WT-NFRL1-19 |リード情報は保存先・保持期間・consent を明示して扱い、テーマ内に CRM / MA を持たない。外部連携は署名付き webhook までとする 同意記録の時刻・版・カテゴリ、撤回、明示 opt-in、GPC の対象条件、認証 SMTP を検査する。| consent なしの保存 0。保持期間逸脱 0。テーマ内 CRM / MA 実装 0 |
| WT-NFRL1-20 |SP 操作性を固定する: タップ領域 44px 以上、テキスト 16px 以上、横スクロール発生 0、固定要素が本文と CTA を隠さない。ドロワーと SP 下部固定バーの積層順を宣言し、PC 側にも共通 + device 別差分の同構造と検査を持つ 固定要素が focus を隠さず、スワイプ等に代替操作を持ち、APG のキーボード契約を満たすことを検査する。| SP 幅の操作性違反 0。本文・CTA の被覆 0。積層順の不一致 0 |
| WT-NFRL1-21 | device 別差分を含む両幅を検査・計測する: 主たる確認面はサイト設定で選び（既定は SP 幅）、Lighthouse の mobile / PC 幅監査と代表ページ種別の両幅スクリーンショット比較をローカル docker の実機ゲートで行う。SP / PC プレビューを管理画面と MCP から取得でき、tracking 経路は端末種別を持ち、A/B・CV を端末種別で集計できる | 両幅の監査・スクリーンショット比較の未実施 0。設定した確認面以外の測定欠落 0。端末種別欠落 0 |
| WT-NFRL1-22 |同意前非発火と注入面を保証する: 同意前はカテゴリ該当タグを発火させず、tracking 受信は同意状態を検証して同意なしイベントを保存しない。タグ slot 以外の script 注入を許さず、タグ転送量を速度予算へ含める Consent Mode v2 7 種への写像、同意 default の先頭注入、外部送信契約、Conversion Linker の配置境界を検査する。| 同意前発火 0。同意なし保存 0。slot 外 script 注入 0。タグ転送量の予算未計上 0 |

## 出典

- `WT-NFRL1-01` / `02`: `docs/design/consistency-responsibilities.md`、`docs/research/2026-08-29-ge1-local/README.md`（静的検査で通っても実機で壊れる事例）
- `WT-NFRL1-03` / `04`: 第三者テーマ監査で観測した欠陥（描画時 DB write、未認証 REST / SSRF、グローバル改変）を本テーマで再発させない教訓（`docs/research/2026-08-26-theme-structure-audit/20-reverse-engineering-synthesis.md` 第 2 部）。第三者テーマ自体の是正は本リポの要求ではない
- 数値（invalid=0、baseline 438）は 2026-08-29 時点の実測
- `WT-NFRL1-14` の SEO 準拠先: https://developers.google.com/search/docs/crawling-indexing（参照日: 2026-09-03）。構造化データの一般指針: https://developers.google.com/search/docs/appearance/structured-data/intro（参照日: 2026-09-03）
- `WT-NFRL1-16`〜`19`: PO 採用 `WT-Q-PERF-01` / `WT-Q-CV-01`（2026-09-03）。
- `WT-NFRL1-20`〜`22`: PO 採用 `WT-Q-SP-01` / `WT-Q-SP-02` / `WT-Q-SP-03` / `WT-Q-TAG-01` / `WT-Q-TAG-02`（2026-09-03）。SP 幅のテーマA / B 実使用パーツ再読は後続 PoC 課題であり、本書は新規証跡を主張しない。

## S3 反映（2026-09-03）

S3 の非機能要求は、公開面の安全性・可用性・性能・プライバシーを検査可能な文へ落とす。第三者サービスの判定や AI の採否判断はテーマ内で行わない。

| 根拠 | 非機能要求への反映 |
| --- | --- |
| W-02 / W-03 / W-04 / W-05 | 公開フラグと認可を分離し、全 ability の permission、annotations、dry-run receipt、Application Passwords の read / write 分離、AI Client 境界を確認する。 |
| W-17 / G-20 / G-21 / G-22 / T-06 / L-09 / L-10 / L-11 / L-12 / L-13 | speculative loading、bfcache、監査版、Baseline fallback、和文タイポ、WCAG 2.2 AA、APG、target size、reduced-motion、OFL を同じ revision のゲートへ束縛する。 |
| E-01〜E-12 / S-05〜S-12 | 用途別台帳、IP・鮮度、非準拠・未検証の区別、生ログの IP 廃棄、容量上限、SMTP / WAF / cron / cache の警告を JSON で観測する。 |
| L-01〜L-08 / W-24 / L-13 / L-15〜L-19 / W-26 | 外部送信、同意、opt-in、privacy tools、資産ライセンス、SECURITY.md、画像 metadata、section 履歴、i18n、対象外境界の欠落を release gate で赤にする。 |
