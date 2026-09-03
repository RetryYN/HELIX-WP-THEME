---
layer: L1
sub_doc: technical
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Technical Requirements

| ID | 技術境界 |
| --- | --- |
| WT-TRL1-01 |WordPress FSE / theme.json v3 / Block API / PHP >= 8.1 / WP 7.1 系。子テーマは層 1（尺度）を再定義しない S3 対応範囲は WP 7.1+、PHP 8.2〜8.5、MySQL 8.4 / MariaDB 11 とし、対応表と iframed editor smoke を保持する。|
| WT-TRL1-02 |契約正本は JSON: theme.json、config/*.json（fail-fast schema 検証）、schema/*.json、openapi.yaml。面・部品・変種の追加は JSON 宣言を伴い、共通定義は fluid、device 別差分は `@mobile` / `@tablet` の上書きとして宣言する theme.json の state / dimension / gradient / typography と capability manifest は同一の宣言から生成する。|
| WT-TRL1-03 |エージェント接点は自前 REST 名前空間 + MCP（abilities）+ WP-CLI。WP コア名前空間へ相乗りしない 標準 Abilities API を主経路とし、MCP / REST / WP-CLI を同じ登録から導出する。自前 REST は標準で扱えない範囲だけにする。|
| WT-TRL1-04 |AI 判定ロジックはテーマ・プラグインの外（HELIX 側）。boundary guard を維持する AI Client / Connectors の prompt boundary guard と、テーマ・プラグインに AI 判定ロジックを持たない規律を維持する。|
| WT-TRL1-05 |実機ゲートはローカル docker WP 7.1。共通定義と device 別差分を SP / PC の両幅で検査する。実運用サイトは read-only。接続情報は環境変数 G-E1 は SP / PC、iframed editor、host capability、cache / WAF / cron の代表構成を確認する。|
| WT-TRL1-06 | 実証記録は本リポ内で完結（参照元 commit・証跡・ゲート結果）。他プロダクトへの参照・依存・書き込みを持たない |
| WT-TRL1-07 |構成はテーマ + 薄いプラグイン（PO 確認 2026-09-02）。運用中の AI はテーマファイルを触らず、WP が DB に持つ「サイトの選択」だけをプラグインの API 経由で変える。プラグインは選択を安全に DB へ入れる薄い層（manifest・dry-run・停止・rollback・ログ）とデータ（設定 JSON・投稿メタ・section ID・ゾーン・再利用パーツ・商品正本・variant / CV 定義・クロールログ・計測・ログ）だけを持ち、商品正本は名前・価格・特徴・評価・画像・外部リンクを保持し、クロールログは UA・逆引き / 公開 IP レンジ照合の判定結果と URL・時刻・ステータス・応答時間・ページ種別を保持する。表示はテーマ、判定は HELIX 側。移行の実行はハーネス（HELIX-WP-HARNESS）がサーバーファイルとテーマ情報を直接書き換えて行い、テーマ側に移行プラグインは持たない（PO 2026-09-02）。第三者プラグインとの出力重複は設定で制御し、全プラグイン互換は非対象 Update URI、署名付き SHA-256 / Sigstore 配信、選択セット、host manifest、SMTP / WAF / cache / cron の譲渡境界をハーネスとプラグインの契約へ記録する。|
| WT-TRL1-08 | HELIX 連携 API は、全一覧の差分読み取り（since / fields / ETag / Last-Modified）、batch 書き込み（全成功 / 全失敗、dry-run / rollback）、署名付き webhook による押し出し、schema version と旧版併走による互換を契約に含める。OpenAPI lint / 差分検査を CI で行い、MCP 常用パック・クローラー計測・A/B・商品正本は同契約の上に置く | API 契約の差分・batch・push・互換の欠落 0 |
| WT-TRL1-09 |MCP 常用パック・REST・WP-CLI の 3 面は、パック定義 JSON から生成された同じ能力集合を扱う。面ごとの能力ずれは契約テストで検出する ability annotations と permission を含む同じ能力集合を MCP / REST / WP-CLI に公開する。| 3 面の能力集合差分 0。契約テストの未実施 0 |
| WT-TRL1-10 |計測タグは head・body 先頭・body 末尾の 3 タグ slot に限定し、イベント名・項目・必須 ID を version 付きデータ層 JSON schema で固定する。TAG-03 の 3 カテゴリを Consent Mode v2 の 7 種へ写像する対応表を契約に含め、head slot は consent default を最初に注入する。テーマは DB 上の選択と出し分けを扱い、計測 ID・タグ断片の正本をテーマファイル・設定 JSON に置かない GA4 写像、AI 回答エンジン由来参照元、Conversion Linker、Consent Mode v2 7 種写像と head の consent default 順序を version 付き契約へ含める。| slot 外注入 0。schema 差分検査の見逃し 0。計測 ID の公開ファイル記載 0。consent default の順序違反 0 |
| WT-TRL1-11 |第三者プラグインの検出結果と現在の選択を capability manifest へ載せ、フォーム・キャッシュ・画像最適化・SEO・同意管理の領域別既定と警告を管理画面 / MCP から同じ契約で取得できる。代表構成は実運用 2 サイトのプラグイン種類に限定し、全プラグイン互換は対象外とする キャッシュ 2 モード、画像縮退、SEO / 同意 / フォームの譲渡、WAF 403、manifest の警告を capability 契約で返す。| manifest と管理画面 / MCP の差分 0。代表構成の二重出力・二重送信 0 |

PoC で成立した経路は `docs/poc/wt-poc-inventory.json` へ digest 束縛する。PoC 未検証の一般化は行わない。

## S3 反映（2026-09-03）

| 根拠 | 技術境界への反映 |
| --- | --- |
| W-01 / W-02 / W-03 / W-04 / W-05 / W-19 / W-20 / W-21 | Abilities API + MCP Adapter、公開と認可の分離、annotations、Application Passwords、AI boundary、Update URI、WP / PHP / DB 対応表、iframed editor 検証を契約化する。 |
| S-01〜S-12 | hosting capability manifest を health・MCP・ハーネスで共有し、画像、cache、loopback、raw log、容量、SMTP、WAF、cron、選択セット、security headers、hardening の受け皿を定義する。 |
| L-14 / L-16 / L-17 / W-24 / W-26 | 署名・digest・lockfile・Dependabot、資産台帳、SECURITY.md、privacy tools、wp.org 登録対象外、Text Domain / POT の配布境界を静的ゲートへ接続する。 |
