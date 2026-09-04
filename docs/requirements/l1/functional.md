---
layer: L1
sub_doc: functional
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Functional Requirements

| ID | ユーザー視点の要求（拡大の提案） | 下流 family |
| --- | --- | --- |
| WT-FRL1-01 |置き場所（面）を選べる: 記事内広告・CV・関連前後・固定ページ上下・ヘッダー内・SP 下部固定・追尾サイドバーなど、テーマA/B にあって本テーマに無い面を slot として持つ。ゾーン割当に広告・バナーの面積上限（値は PoC で確定）を宣言し、初回表示のモーダルは同意バーを除き禁止する（LP は LP 単位設定で例外可）。共通宣言を fluid で適用し、device 別差分（専用面・並び順・表示形）は個別編集できる。主たる確認面はサイト設定で選び、既定は SP 幅とし、SP / PC の両幅を検査する S3 では広告面積上限と初回表示モーダル禁止を G-E1 で確認し、LP は個別設定で扱う。| ZONE |
| WT-FRL1-02 |共有パーツと骨格を選べる: header / footer / sidebar / hero の複数案、テンプレ変種を GUI と AI の双方から差し替えられる。LP は投稿型で持ち（イベント / 比較特設を含む種別、ディレクトリ非依存 URL）、フォーム制御・デザイン拡張・イベント計測を備え、LP / section の目標 CV ID と A/B variant ID を選べる。パーツの共通宣言を fluid で適用し、device 別差分を Site Editor と AI の双方から個別編集できる。主たる確認面はサイト設定で選び、既定は SP 幅とし、SP / PC の両幅を検査する 固定ページ群と選択セットの template part / global styles / 設定 JSON は URL 非依存のスラッグ参照で移送する。| PARTS / LP |
| WT-FRL1-03 |記事内語彙で書ける: 囲み・ボタン・リンクカード・吹き出し・手順・比較表・定義リスト・FAQ・タブなど実使用上位の語彙で記事を組める。販売系 4 つ（商品カード・ランキング・比較専用テーブル・CTA 束）を加えた新規ブロック 6 つ + 空き 1 枠（上限 7）に限定し、本文中の手組み比較表とは別語彙にする。商品テーブルは variant ID と CV ID を計測へ渡す。目次と PR 表記が自動で出る。語彙の共通定義を fluid で適用し、SP / PC の device 別の出方・順序・表示形を個別編集・検査できる 新規ブロックは 6 種 + 空き 1 枠（上限 7）とし、コア Tabs、pros-cons / タイムライン、料金表 variant、先頭列固定の比較表を受け皿にする。| VOCAB |
| WT-FRL1-04 |見た目の引き出しがある: 見出し階層・見出し / ボタンの block style・最小限の動き・レスポンシブ段・style variation でテーマA/B 並みの表現に届き、さらにサイトパターン（コーポレート / サービス / ブランド / ポータル / 比較）の品質水準まで広げる（大量調査が前提）。型数は目標にせず、用途（サイトパターン × 面 × 目的）から必要な型を選ぶ台帳を入力にする。カード高さの自動統一、ヘッダーの制御パターン、見出しの 1 行化と補助文の抑制、第三者目次プラグイン以上の目次、選べる 404 と CV 導線を含む（PO 2026-09-05）。theme.json の共通尺度は fluid で定義し、device 別の差分を個別に編集・検査する pseudo / custom state と dimension preset、minWidth / gradient は theme.json の宣言・安全域で扱い、Baseline Newly available 未満は CSS fallback とする。| LOOK |
| WT-FRL1-05 | 記事単位で切り替えられる: サイドバー・目次・シェア・PR を投稿ごとに ON/OFF でき、アイキャッチの位置と有無をサイト既定 + 記事単位で選べる（PO 2026-09-05） | META |
| WT-FRL1-06 | 既存サイトの設定を写せる: カスタマイザ・設定画面・ウィジェット・プリセット・独自ブロックの写像先を、取得項目定義とマッピングフォーマットとしてテーマが公開する。移行の実行はハーネスの責務 | MIGRATE |
| WT-FRL1-07 |エージェントが JSON で全部を操作できる: 面・部品・値・変種・テンプレの選択、中間 JSON の抽出、再利用パーツの参照が、設定で束ねた MCP 常用パック（主経路）と REST / CLI（従属経路）から行える MCP 常用パックは Abilities API の登録から導出し、操作 annotations、権限、destructive の dry-run receipt を同一 manifest で扱う。| AGENT |
| WT-FRL1-08 |値は 3 域で制御される: 安全域は自由、生値は警告、破壊域は停止する。境界値は PoC で決める dimension preset、minWidth、gradient と和文タイポを安全域の値として宣言し、生値・破壊域の境界は PoC で確定する。| VALUE |
| WT-FRL1-09 |構造化データと AI 向け出力が単一出力元から出る: CollectionPage を加え、WebSite は site name 用の name / alternateName / url のみとし SearchAction は出さない。FAQ / 手順は本文語彙として残し、FAQPage / HowTo は JSON-LD にせず、ItemList は語彙から自動生成する。SEO の要件と実装は Google 検索セントラルの公式ドキュメントに準拠し、必須プロパティ・非推奨型・title / meta / canonical / robots / sitemap / hreflang（多言語構成時のみ自己参照・相互参照）・Core Web Vitals・モバイル・リンク rel 属性を機械検査する。llms.txt・crawl-map・LLMO summary の既定出力は維持するが、Google の AI 機能への効果や他 AI 事業者の読込を断定せず、アクセス計測で実証する。SEO の主たる確認面はサイト設定で選び、既定は SP 幅とし、SP / PC の両幅を確認対象にする（出典: https://developers.google.com/search/docs/crawling-indexing、参照日: 2026-09-03） 廃止型の監視、Organization / favicon / 公開日・更新日、robots 200 fallback、自己 canonical、初期 HTML の主コンテンツを一つの SEO 検査へ含める。| SEO |
| WT-FRL1-10 | 実証済みパターンを証跡付きの記録台帳に残せる（他プロダクトは記録を読んで採否を自分で決める。依存なし） | INTAKE |
| WT-FRL1-11 |管理画面から普通に設定できる: サイト全体の既定（目次・PR 表記・slot / ゾーン・SP 下部・LP 種別・MCP パック）を AI を介さずテーマ設定画面で設定でき、正本は schema 付き設定 JSON 1 本で AI と共有する。設定 UI は 3 層（サイト既定セット / パーツ単位 / 記事単位）+ 選択セット + 視覚ピッカーとする（PO 2026-09-05） 設定画面に著者・監修者、レコメンド、フォント、読み戻し、リンク切れ警告、hosting capability、選択セットのタブを追加する。| ADMIN |
| WT-FRL1-12 | 見出し区間（H2 / H3）単位で制御できる: 安定 ID を持つ階層 section を単位に、差し替え・リライト・順序入れ替え・面の挿入・表示制御・計測を人（エディタ）と AI（MCP パック）の双方から行える。section の variant ID と目標 CV ID を計測へ渡す | SECTION |
| WT-FRL1-13 |売るための構成ができる: 商品正本から商品カード・ランキング・比較専用テーブル・CTA 束・レビューを同じ正本で出し、Product / Offer / AggregateRating / ItemList 構造化データ、複数記事参照、一括反映、商品リンクのクリック計測、AI を介さない商品編集、MCP 常用パックへの追加・更新・差し込みを行える。決済・カート・会員と購入完了の計測はテーマ外 外部 EC API クライアントや認証情報はテーマ・Core プラグインに置かず、商品正本の取得元・時刻・鮮度を表示する。リダイレクト経路とリンク切れ検査も同じ正本に接続する。| SELL |
| WT-FRL1-14 |クローラー計測ダッシュボードを持てる: bot の来訪を専用ログへ記録し、クローラー別推移・古い URL・404 / 5xx・新規公開記事の初回捕捉時間・llms.txt / crawl-map への AI クローラーアクセス有無を管理画面で確認し、robots.txt と AI クローラーの許可 / 拒否を同画面から設定できる。Search Console 突合と順位計測はテーマ外 クローラー台帳は用途別 4 分類、公式 UA / IP エンドポイント / 参照日 / 鮮度、未検証 UA、非 robots 準拠と遅延を区別して表示する。| CRAWL |
| WT-FRL1-15 |A/B の配信・計測・停止ができる: H2 / H3 section、hero / CTA / 商品テーブルのパーツ、LP 全体を variant 単位で選べ、Core プラグイン API の DB 選択と cookie 固定割当で配信する。bot には既定案のみを返し、impression / click / CV は variant ID 付きで追跡し、承認・停止・rollback は WT-UI-10 と MCP 常用パックから行う。共通の配信定義に device 別差分を持たせ、主たる確認面はサイト設定で選び、既定は SP 幅とし、SP / PC の両幅で配信と計測を検査する A/B は同一 URL + cookie を既定とし、cookie 無しは既定案、cache 対応の二方式、prerender 除外、bfcache 配慮、標準イベント写像を検査する。| AB |
| WT-FRL1-16 |画像・短尺動画を高速に最適化できる: WebP / WebM を生成・配信し、picture / srcset、hero の高優先度、その他の遅延読込、非同期一括処理、管理画面・CLI・MCP の再生成 dry-run、alt 警告、GIF 置換提案、Discover 代表画像要件を扱う。商品画像・バナー画像も同じ経路にする GD / Imagick の WebP / AVIF 能力を検出し、未対応時は WebP へ縮退して警告する。画像再生成では IPTC / XMP を引き継ぐ。| IMG |
| WT-FRL1-17 |管理画面で運用の裏方を扱える: 操作ログの絞り込みと CSV / JSON export、dry-run 差分の適用 / 却下、破壊域停止分の確認、直近変更の rollback と記録、HELIX 接続用 API key の発行・失効と読み専用 / 書き込み可の権限を WT-UI-10 のタブで操作する 操作・クロール・監査ログの上限、日次集約、SMTP 失敗、PR 根拠、privacy tools、選択セットの適用結果を運用タブから確認する。| ADMIN |
| WT-FRL1-18 |SNS 連携を持てる: SNS profile を設定 JSON の一か所から header / footer / 著者欄 / sameAs へ反映し、対象と配置を選べる share、クリック計測、遅延読込の feed 埋め込み、メッセージアプリ公式アカウント追加ボタン・QR の LP CTA を提供する。配信と資格情報はテーマ外の境界を保つ 著者・監修者正本を著者欄、監修者欄、著者アーカイブと Article / ProfilePage の構造化データへ反映する。| SNS |
| WT-FRL1-19 |複数の CV を設計・選択できる: 本 CV・マイクロ CV・補助指標を ID 付き正本へ登録し、資料 DL の 2 経路と完了計測、記事・LP・section の目標選択、A/B・section の CV ID 集計、CTA の主文言 + 任意 microcopy の候補選択を提供する。microcopy は必須化しない。共通の CV 表示定義に CTA の device 別差分を持たせ、SP / PC の両幅から個別編集・検査できる フォームの利用目的・ポリシーリンク・同意を宣言し、メール送信は認証 SMTP の状態と同意記録を通して扱う。| CV |
| WT-FRL1-20 |バナーを登録・管理・配置・計測できる: PC / SP 画像、リンク、alt、種別、有効期間、PR 要否を正本化し、商品 ID 派生とゾーン割当、固定 / ローテーション、impression / click・CV ID・variant の追跡、期限切れ等の警告を WT-UI-10 と MCP 常用パックで扱う お知らせバーは BANNER-01 から派生し端末側記憶を使う。リダイレクト経路はバナーにも適用し、広告面積上限を検査する。| BANNER |
| WT-FRL1-21 |HELIX 側の監査結果を管理画面で見て直せる: 記事・section・LP・バナーの指摘を受け取り、件数バッジ・対象へのリンク・適用 / 却下 / 保留を表示し、適用は dry-run → 差分レビューを通す。決定論的に検査できる項目だけをローカル検査し、その他の判断は受け取った結果を表示する 監査結果は受け取って表示し、ランキング根拠・打消し表示・健康表現の規則をテーマ内の AI 判定にしない。| AUDIT |
| WT-FRL1-22 |共通と device 別差分を両幅で編集できる: 面・語彙・パーツの共通宣言を 1 本の fluid 定義で持ち、専用面・並び順・表示形などの device 別差分を `@mobile` / `@tablet` の上書きとして宣言する。Site Editor と AI の双方から SP / PC を個別編集でき、主たる確認面はサイト設定で選ぶ（既定は SP 幅）。ゲートは両幅で検査する SP / PC の両幅で reduced-motion、和文タイポ、APG の状態・キーボード契約を検査する。| SP |
| WT-FRL1-23 |SP 固有の面を選べる: SP ヘッダー（ロゴ・ハンバーガー・検索・主要 CTA の配置と選択）、ドロワーメニュー（階層・CTA・SNS）、SP 下部固定メニュー（3〜5 タブ: 電話・メッセージアプリ・資料 DL・目次・トップへ。既存 SP 下部固定 slot の一部）、SP 専用広告面（本文中の SP だけに出す slot と SP のみのバナー）を device 別差分として扱う。PC 側にも同じ構造の差分と検査を持つ SP 専用の重い面はサーバー側 slot 条件描画とし、固定要素がフォーカスを隠さない。| SP |
| WT-FRL1-24 |device 別の語彙挙動を JSON で宣言できる: SP では比較テーブル（横スクロール / カード化）、タブ（アコーディオン化）、目次（フロート→開閉ボタン）、画像ギャラリー（スワイプ）、CTA（全幅化・追従）を定義し、PC 側にも同じ項目の差分・個別編集・検査を持つ。語彙選択時に各 device の出方を決め、SP / PC プレビューを WT-UI-10 と MCP から取得できる タブはコア Tabs + block style、SP はアコーディオンとして扱い、横スクロールやカード化でも全本文を初期 HTML に含める。| SP |
| WT-FRL1-25 |計測タグの置き場と出し分けを選べる: head・body 先頭・body 末尾の 3 か所をタグ slot とし、タグ管理コンテナや個別タグ断片を WT-UI-10 と MCP から登録する。正本は HELIX 側にも置け、テーマ側は置き場とページ種別・LP 単位・同意状態別の出し分けだけを扱う。テーマファイル・設定 JSON に計測 ID を書かず、DB 上の選択として扱う 外部送信先・送信情報・利用目的をタグ正本に必須化し、公表ページを自動生成する。Conversion Linker、server-side tagging、cookie 方針はテーマ外の選択を参照する。| TAG |
| WT-FRL1-26 |計測データ層を 1 本の version 付き JSON schema で固定できる: 表示・スクロール・CTA クリック・フォーム送信・資料 DL・バナー・商品 CTA・A/B variant・section 到達・端末種別のイベント名と項目を定め、CV ID・variant ID・section ID・端末種別を必須にする。外部タグはこの契約だけを読む GA4 推奨イベントと items 写像を version 付きで定義し、AI 回答エンジン由来の参照元を device type と同列の必須項目にする。| TAG |
| WT-FRL1-27 |同意状態に連動して計測を制御できる: 同意バーで必須・計測・広告のカテゴリごとの状態を保持し、同意前は該当タグを発火させず、同意後に遅延発火し、同意信号をデータ層へ載せる。サーバー側 tracking 受信も同意なしのイベントを保存しない。第三者の同意管理・計測プラグイン検出時は WT-TR-PLUGIN-02 の「検出して譲る」を既定とする。同意バーは新規ブロックではなく既存パーツとして扱い、既定 OFF、選択は ON/OFF と位置（先頭非固定 / 下部固定）の 2 つに限定する（PO 2026-09-05） Consent Mode v2 の 7 種写像、GPC の条件付き広告拒否、撤回常設、同意版・時刻・カテゴリ記録を同意契約へ接続する。| TAG |

SP 固有面のテーマA / B 実使用パーツの再読は、要求の根拠や PoC 証跡を追加せず、後続の PoC 課題として扱う。

L1 の ID はユーザー要求であり、L3 の system requirement ID とは区別する。
各行はテーマA / B との PoC 比較で本テーマに不足すると分かった面・語彙・引き出しを、機械可読性を保つ形で取り込む提案である
（出典: `docs/research/2026-08-26-theme-structure-audit/04-diff-register.md`、`docs/design/catalog/customizability.md`、`docs/research/2026-08-27-poc-browser-verification/theme-comparison.md`）。

## S3 反映（2026-09-03）

以下は `docs/research/2026-09-03-external-gap-research.md` §3 の採用項目を、既存 ID への追記または新しい要求 ID として反映した要求文である。テーマは表示・受信・宣言・検査結果の提示に限定し、AI 判定は HELIX 側に置く。

| 根拠 | 要求文への反映 |
| --- | --- |
| W-03 / W-05 | 各 ability の readonly / destructive / idempotent / openWorld annotations、permission、destructive の dry-run receipt、AI Client boundary guard、Connectors の有無を manifest に載せる。 |
| W-07 / W-09 / G-22 / L-12 / T-06 | pseudo / custom state、dimension preset、minWidth、gradient は宣言済み安全域で扱い、Baseline Newly available 未満を fallback とし、reduced-motion と和文タイポ CSS を theme.json / block style から出力する。 |
| W-17 / G-15 / G-20 / G-21 / G-23 / G-24 / E-11 | A/B は同一 URL + cookie、cookie 無しは既定案、A/B と LP は speculative loading から除外し prerendering を待つ。bfcache と固定版監査、標準イベント写像、Conversion Linker / server-side tagging の選択、AI 回答エンジン由来参照元を計測契約へ反映する。 |
| G-01 / G-03 / G-04 / G-06 / G-08 / G-09 / G-10 / G-16 / G-17 | 廃止型監視、Person 著者、Organization 拡張、root 単位の site name / favicon、日付・sitemap、Google-Extended 分離、公式 IP レンジの最終版継続、robots 200 fallback、自己 canonical、初期 HTML の主コンテンツを SEO 検査へ反映する。 |
| G-25 / G-26 / E-15 / E-16 / L-21 | アフィリエイト用 merchant feed、ProductGroup、AI 側収益分配・ライセンス市場、DSA / EU AI Act / EAA の国内運用はテーマの要求対象外と明記する。 |
| E-01 / E-02 / E-03 / E-04 / E-05 / E-08 / E-12 | クローラーを training / search / user-triggered / ads-preview の 4 分類に分け、IP endpoint と鮮度、control token、非 robots 準拠の遅延、UA 一致のみの未検証、RFC 9309 の一元生成、Google 準拠の検索クローラー を台帳へ反映する。 |
| S-02 / S-03 / S-04 / S-05 / S-06 / S-07 / S-08 / S-09 / S-10 / S-11 / S-12 | 画像能力、A/B cache 二方式、loopback cache 検査、生ログと cache 応答、ログ上限・日次集約、認証 SMTP、WAF / xmlrpc、3 種の非同期駆動、選択セット、応答ヘッダ、health hardening を既存の運用・技術要求へ反映する。 |
| L-01 / L-02 / L-03 / L-04 / L-05 / L-06 / L-07 / L-08 / W-24 / L-18 / L-19 / L-13 / L-16 / L-15 / L-17 / W-26 | 外部送信公表、ランキング根拠、PR 根拠ログ、同視野・同サイズの打消し、健康表現の HELIX 監査、カテゴリ同意・撤回・オプトイン・フォーム宣言、privacy tools、画像 metadata、section 履歴、OFL 資産台帳、SECURITY.md、wp.org 登録対象外、i18n の配布規律を要求へ反映する。 |
| J-01 / J-02 / J-03 / J-04 / J-05 / J-06 / J-07 / J-08 / J-10 / J-11 / J-12 / J-14 / J-15 | 著者・監修者正本、Breadcrumbs、関連・人気・おすすめ、外部 EC 非依存、302 リダイレクト、IndexNow、AI 利用許諾信号、snippet 制御、広告面積・モーダル、WCAG 2.2 AA、和文フォント、コア Tabs、販売系・共通 UI 8 部品を反映する。J-09 と J-13 は要求を変更しない。 |

## S4 反映（2026-09-05）

デザイン試作 02 のカタログ、パーツ別パターン台帳（`docs/research/2026-09-05-parts-pattern-taxonomy/`）、CV / ユーザビリティ証跡ルール集（`docs/research/2026-09-05-cro-usability-evidence/`）への PO 反応を反映した。採用 4 件（WT-Q-META-02 / WT-Q-CONSENT-02 / WT-Q-ADMIN-04 / WT-Q-LOOK-05）は上表へ追記済み。問い 3 件（WT-Q-LOOK-06 動き・奥行き・空間・脱テキスト感 + 自動コントラスト guard、WT-Q-PARTS-03 footer サイトマップ・カテゴリ ミニ HOME・一覧表示型・バナー / 問い合わせ枠・メディア枠、WT-Q-EVID-01 既定値 P01〜P33）は `discovery/candidate-projection.json` の `unresolved` にあり、採用後に反映する。
ダーク variation は WT-Q-LOOK-04 の reject を維持する。
