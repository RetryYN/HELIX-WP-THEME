---
layer: L3
sub_doc: g3-approval-summary
status: claude_proposal
authority: docs/requirements/authority.md
iteration: 2
---

# G3 承認用 要件要約（iteration 2、Claude 案）

PO が要件 123 件の全文を読まずに G3（凍結）を判断できるようにした要約。正本は `requirements-ir.json` で、本書は生成ビューに過ぎない（statement は先頭を機械的に切り出したもの）。
HELIX の規律（RDJ-FR-007）では、直近 2 iteration で優先度が安定していることが収束条件になる。iteration 1 は問い 79 件の採否で閉じた。本書の確認が iteration 2 で、**優先度の変更がなければ収束 → compile → `specified`、PO 承認で `frozen`** となる。

## 0. PO に判断してもらうこと

| 問い | 内容 | 形 |
| --- | --- | --- |
| WT-Q-G3-01 | §2〜§3 の要件 123 件（P0 67 / P1 47 / P2 9）と非目標を、設計・実装・テストを拘束する要件として承認する。優先度に変更があれば ID と新しい優先度を指定 | 承認 / 変更あり |
| WT-Q-STYLE-01 | 開発スタイルを `V_DESIGN_SCRUM_IMPLEMENTATION`（L4 基本設計 → L5 詳細設計 + 先行テスト設計を V 字で固め、L6 実装以降を Scrum で反復）にする。採用するか | 採用 / 不採用 |

## 1. 企画との接続（WT-BR、5 件）


- **WT-BR-01** 機械可読性を維持する。面・部品・値・変種を追加しても、すべてが JSON 宣言（theme.json / config / schema / openapi）から列挙できる（判定: capability manifest の列挙率 100%。PHP にしか存在する面・部品が 0）
- **WT-BR-02** テーマA / B が示す一般想定水準に到達する。12 種別の必須パーツ充足に加え、未整備 16 項目と S3 追加 26 問（採用 24・reject 2）の受け皿を持つ（判定: `web-patterns` 12 種別で本テーマだけに欠ける必須パーツ 0。未整備項目と採用候補の受け皿有無が台帳化）
- **WT-BR-03** エージェント制御下でバリエーションを生成できる。面・部品・値・変種・テンプレ・CV の選択が JSON 経由で完結し、破壊域へ落ちない（判定: AI 経路の変更のうち JSON 外の操作を要した件数 0。破壊域停止の誤警告 0）
- **WT-BR-04** 実証済みパターンを証跡付きで記録し、他プロダクト（GRAPHIX-NEO 等）は記録を読んで採否を自分で決める。依存を作らない（判定: 記録行の証跡付き率 100%。他リポへの参照・書き込み 0）
- **WT-BR-05** AI 判定ロジックをテーマ・プラグインに持ち込まない（判定: 静的検出（判定ロジック・モデル呼び出し）0）

**非目標**: 決済・カート・会員機能と購入完了の計測（テーマ外。必要なら外部側）、CRM / MA とメッセージ配信そのもの（テーマ外）、第三者テーマの是正、外部デザインツール取り込み経路、AI 判定ロジックのテーマ内実装

## 2. family 別の全体像

| family | 件数 | P0 / P1 / P2 | 受入条件 | 要旨（P0 先頭の抜粋） |
| --- | --- | --- | --- | --- |
| SEO | 7 | 2 / 4 / 1 | 15 | SEO の要件と実装を Google 検索セントラルの公式ドキュメントに準拠させ、構造化データ（型ごとの必須 / 推奨プロパティ、FAQPa… |
| AGENT | 6 | 1 / 3 / 2 | 12 | エージェント接点の主経路は MCP の「常用パック」とする。パックは manifest（slot・ゾーン・パターン・パーツ案・variati… |
| CRAWL | 6 | 1 / 5 / 0 | 12 | クローラーを training / search / user-triggered / ads-preview の4分類で台帳化し、公式 U… |
| SELL | 5 | 3 / 2 / 0 | 10 | 商品を schema 付き JSON または専用投稿型で正本化し、名前・価格・特徴・評価・画像・リンク先（アフィリエイト / 外部ストア /… |
| SP | 5 | 4 / 1 / 0 | 10 | 面・語彙・パーツの共通宣言を 1 本の fluid 定義で持ち、専用面・並び順・表示形などの device 別差分を WordPress 7… |
| ADMIN | 4 | 3 / 1 / 0 | 10 | テーマ設定画面（WP 管理画面）を 1 つ持ち、サイト全体の既定（目次の配置方式・ページ種別表示、PR 表記のデザイン・表示制御、slot … |
| CV | 4 | 3 / 1 / 0 | 8 | サイト単位の CV 正本へ本 CV（申込・購入・問い合わせ）、マイクロ CV（資料 DL・メルマガ登録・メッセージアプリ追加・比較テーブル閲… |
| LOOK | 4 | 2 / 1 / 1 | 10 | 見た目の型は目標数を持たず、用途（サイトパターン × 面 × 目的）から必要な型を選ぶ台帳（docs/research/2026-09-05… |
| PLUGIN | 4 | 2 / 2 / 0 | 8 | 運用中の AI はテーマファイルを触らず、WordPress が DB に持つ「サイトの選択」（ヘッダー / サイドバー / hero の案… |
| TAG | 4 | 4 / 0 / 0 | 8 | 計測タグの注入面を head・body 開始直後・body 終端の 3 slot に限定し、タグ管理コンテナ / 個別断片の登録・選択を W… |
| VOCAB | 4 | 2 / 2 / 0 | 9 | 記事内語彙 14 種（囲み・ボタン・リンクカード・吹き出し・手順・記事一覧・アコーディオン・タブ・全幅・リッチメニュー・会員制限・比較表・定… |
| AB | 3 | 3 / 0 / 0 | 6 | H2 / H3 section、hero / CTA / 商品テーブルのパーツ、LP 全体を variant 単位で登録・選択し、共通の配信… |
| API | 3 | 1 / 2 / 0 | 6 | HELIX 連携 API の書き込みを batch（全成功か全失敗）とし、dry-run / rollback に対応する。投稿公開・設定変… |
| CORE | 3 | 3 / 0 / 0 | 6 | 面・部品・変種・値の尺度は theme.json / config / schema / openapi の JSON 宣言から列挙でき、P… |
| IMG | 3 | 1 / 2 / 0 | 6 | 全 subsize を WebP（対応環境は AVIF も）で生成し、GIF アニメ・短尺動画は WebM と fallback MP4、v… |
| LEGAL | 3 | 3 / 0 / 0 | 6 | PR 表記の法令要件と GPL 配布要件（ライセンス表記・第三者資産の許諾）を満たす。OFL 全文、Reserved Font Name、a… |
| MIGRATE | 3 | 0 / 2 / 1 | 6 | 移行の実行（移行元サイトの取得、サーバーファイルとテーマ情報の書き換え）はテーマの責務ではなく HELIX-WP-HARNESS の責務（P… |
| PERF | 3 | 1 / 2 / 0 | 6 | 記事・LP・一覧・商品比較で主たる測定面はサイト設定で選び（既定は SP 幅）、SP / PC の両幅を測定対象とした Lighthouse… |
| VALUE | 3 | 2 / 1 / 0 | 6 | 値を安全域（プリセット / 尺度内）・生値（警告）・破壊域（停止）の 3 域で判定し、ブロック単位・記事単位の任意 CSS（WP 7.0 の… |
| ZONE | 3 | 1 / 1 / 1 | 8 | 共有 slot 6 種（本文前・関連前後・固定ページ上下・ヘッダー内・SP 下部固定・追尾サイドバー）をテンプレ / パーツ内のパターン挿入… |
| A11Y | 2 | 0 / 2 / 0 | 4 | 域の判定・状態表示は label と icon を伴い色だけに依存しない。img alt 欠落 0、AA コントラスト、横スクロール 0。W… |
| AUDIT | 2 | 1 / 1 / 0 | 4 | HELIX 側の指摘（記事 / section / LP / バナー、種別、重さ、根拠、修正案）を Core プラグインが受け取り DB へ… |
| AUTHOR | 2 | 2 / 0 / 0 | 4 | 著者・監修者の正本（名前・経歴・資格・sameAs・画像）を設定 JSON に保持し、著者欄・監修者欄・著者アーカイブへ反映する。MCP 常… |
| BANNER | 2 | 0 / 2 / 0 | 7 | バナー正本へ PC / SP 画像、リンク先、alt、種別（自社告知 / アフィリエイト / 広告 / 商品）、有効期間、PR 表記要否を登… |
| HOST | 2 | 1 / 1 / 0 | 4 | hosting capability manifest に PHP / DB 版、画像拡張、cron 駆動、cache / CDN、WAF、… |
| LOG | 2 | 2 / 0 / 0 | 4 | ユーザー領域に保存された combined / gz のサーバー生ログを WP-Cron / WP-CLI で取り込み、cache 応答も … |
| LP | 2 | 2 / 0 / 0 | 4 | LP を投稿型（CPT、show_in_rest）として持ち、一覧・テンプレ割当・REST を固定ページから分離する。URL は固定ページが… |
| PARTS | 2 | 1 / 1 / 0 | 5 | header（ロゴ位置・ナビ形・CTA・検索・固定挙動・透過の制御パターン）/ footer / sidebar / hero の複数案を同… |
| PRIV | 2 | 1 / 1 / 0 | 4 | WordPress privacy tools の exporter / eraser と privacy policy content に… |
| SECTION | 2 | 2 / 0 / 0 | 4 | 見出し区間を一級の単位（section）にする。区間は「見出しレベル N から同レベル以上の次の見出しまで」と定義し、H2 区間の中に H3… |
| SNS | 2 | 0 / 2 / 0 | 4 | SNS profile を設定 JSON の一か所に登録し、header / footer / 著者欄と構造化データ sameAs へ反映す… |
| CLI | 1 | 1 / 0 / 0 | 2 | MCP 常用パック・REST・WP-CLI の 3 面が、パック定義 JSON から生成された同じ能力集合を扱い、ずれたら契約テストで赤にす… |
| CONSENT | 1 | 1 / 0 / 0 | 3 | 同意記録へ時刻・ポリシー版・カテゴリ・保持期間を持たせ、撤回を常設する。明示 opt-in は既定オフとし、GPC 信号を検出した広告拒否は… |
| COST | 1 | 0 / 0 / 1 | 2 | ゲート・PoC はローカル docker で完結し、無料枠制限や課金を伴う外部 API に依存しない |
| CRED | 1 | 1 / 0 / 0 | 2 | credential・実サイト固有名・第三者製品名を公開リポジトリへ書かない。接続情報は環境変数と gitignore 済み local 設… |
| ENV | 1 | 0 / 0 / 1 | 2 | Text Domain と翻訳関数を準拠させ、POT を CI で生成し、日本語・英語のソース言語を明示する。RTL は対応範囲外として記録… |
| GATE | 1 | 1 / 0 / 0 | 2 | 静的 6 ゲート（G-T1 / T1b / T2 / T3 / S1 / S2）と実機 G-E1（invalid=0）を、パターン・パーツ・… |
| INTAKE | 1 | 0 / 0 / 1 | 2 | 実証記録台帳を本テーマ内で完結する append-only の JSON Lines として持つ。1 行 = パターン ID・参照元 com… |
| MAIL | 1 | 1 / 0 / 0 | 2 | 認証 SMTP と From 整合、SPF / DKIM / DMARC の状態を検査し、未設定を警告する。wp_mail_failed を… |
| META | 1 | 0 / 1 / 0 | 3 | 投稿メタ 5 キー（sidebar / toc / share / pr / eyecatch）を登録し、テンプレ側の条件描画で記事単位の表… |
| NAV | 1 | 1 / 0 / 0 | 2 | コア Breadcrumbs ブロックを表示し、BreadcrumbList 構造化データを同じ出力元から生成する。階層は投稿・固定ページ・… |
| OBS | 1 | 0 / 1 / 0 | 2 | health / gate / 台帳 / 抽出器の出力は JSON で、HEAD と digest に束縛される |
| OSS | 1 | 1 / 0 / 0 | 2 | OFL フォントと第三者資産の全文ライセンス・Reserved Font Name の扱い・出所を JSON 台帳と readme へ機械生… |
| PAGE | 1 | 0 / 1 / 0 | 2 | 会社概要・問い合わせ・採用・プライバシー・特商法・外部送信先一覧・アクセシビリティ方針の固定ページパターンを提供し、事業者情報 JSON を… |
| PERM | 1 | 1 / 0 / 0 | 2 | 破壊域停止は権限で解除できず、write 系 API は capability と dry-run receipt を要求する。abilit… |
| REC | 1 | 1 / 0 / 0 | 2 | 構造・スタイル・値・ゾーンの変更は dry-run → apply → rollback の経路を持ち、rollback で元の diges… |
| RECO | 1 | 1 / 0 / 0 | 3 | 記事一覧に関連（カテゴリ→タグ→手動）、人気（運用者が集計方式と期間を選択）、おすすめ（手動指定・並び順）の3方式を持つ。人気の自前集計を選… |
| REL | 1 | 1 / 0 / 0 | 2 | 描画パスで set_theme_mod / update_option を呼ばない。redirect_canonical の全面停止や全ペー… |
| SEC | 1 | 1 / 0 / 0 | 2 | 未認証 REST（permission_callback __return_true）を持たず、外部 URL 取得は wp_safe_rem… |
| SYNC | 1 | 1 / 0 / 0 | 2 | template part・global styles・設定 JSON・商品正本・zone 割当をスラッグ参照・URL 非依存の選択セットと… |
| TPL | 1 | 0 / 1 / 0 | 3 | 404 と検索結果に選べる複数のテンプレ変種（人気記事・CTA・検索語提案）を持ち、404 には LP・比較記事・問い合わせへの CV 導線… |
| TYPO | 1 | 0 / 1 / 0 | 2 | 和文の既定 CSS に line-break: strict、overflow-wrap: anywhere、word-break: nor… |

合計 123 件、受入条件 262 件、テスト 123 件。kind: functional 83 / non_functional 26 / technical 14。owner: functional・non_functional は PO、technical は TL。

## 3. P0 要件の一覧（67 件）

P0 は「これが無いと企画（L1）が成立しない」もの。全文は IR を参照。

| ID | 要旨 | 受入 | 検証 evidence |
| --- | --- | --- | --- |
| WT-FR-AB-01 | H2 / H3 section、hero / CTA / 商品テーブルのパーツ、LP 全体を variant 単位で登録・選択し、共通の配信定義に device 別差分を持たせ、主たる確認面はサイト設定で選び、SP / … | 2 | variant fixture + responsive render receipt |
| WT-FR-AB-02 | A/B の impression / click / CV を variant ID・section ID・CV ID・device type 付きで WT-FR-TAG-02 の version 付きデータ層契約から … | 2 | data-layer receipt |
| WT-FR-AB-03 | A/B の承認・停止・rollback を WT-UI-10 と MCP 常用パックの双方から操作し、停止時は既定案へ即時復帰する。A/B の判定ロジックはテーマ・プラグインに置かない | 2 | admin + MCP receipt |
| WT-FR-ADMIN-01 | テーマ設定画面（WP 管理画面）を 1 つ持ち、サイト全体の既定（目次の配置方式・ページ種別表示、PR 表記のデザイン・表示制御、slot / ゾーン割当、SP 下部固定の積層、LP 種別の既定、MCP 常用パック構成）… | 4 | Playwright + schema test + manifest parity |
| WT-FR-ADMIN-03 | WT-UI-10 の差分レビュータブで dry-run の変更案と破壊域停止分を並べ、適用 / 却下を選べる。直近の変更は rollback でき、戻した事実を操作ログへ記録する | 2 | diff + rollback receipt |
| WT-FR-ADMIN-04 | WT-UI-10 の鍵管理タブで HELIX 接続用 API key を発行・失効し、鍵ごとに読み専用 / 書き込み可を設定する。鍵の値は一度だけ表示し、テーマ・プラグインの公開ファイルへ置かない。自前鍵ではなく専用ロー… | 2 | key-management receipt |
| WT-FR-AGENT-01 | エージェント接点の主経路は MCP の「常用パック」とする。パックは manifest（slot・ゾーン・パターン・パーツ案・variation・テンプレ変種・値の尺度・投稿メタ・LP 種別・hook）上の操作を用途別に… | 2 | MCP receipt + REST parity |
| WT-FR-AUDIT-01 | HELIX 側の指摘（記事 / section / LP / バナー、種別、重さ、根拠、修正案）を Core プラグインが受け取り DB へ保持し、WT-UI-10 の監査タブと記事一覧の件数バッジから対象 sectio… | 2 | audit + diff receipt |
| WT-FR-AUTHOR-01 | 著者・監修者の正本（名前・経歴・資格・sameAs・画像）を設定 JSON に保持し、著者欄・監修者欄・著者アーカイブへ反映する。MCP 常用パックから付与・更新できるが、判定は HELIX 側で行う | 2 | S3 receipt |
| WT-FR-AUTHOR-02 | 著者を Article.author の url / sameAs、監修者を reviewedBy として構造化データへ反映し、著者ページは ProfilePage と代表画像を持つ | 2 | S3 receipt |
| WT-FR-CONSENT-01 | 同意記録へ時刻・ポリシー版・カテゴリ・保持期間を持たせ、撤回を常設する。明示 opt-in は既定オフとし、GPC 信号を検出した広告拒否は日本限定構成でのみ既定化し、非対象地域では設定を明示する。同意バーは既定 OFF… | 3 | S3 receipt |
| WT-FR-CRAWL-05 | クローラーを training / search / user-triggered / ads-preview の4分類で台帳化し、公式 UA・IP endpoint・取得日時・鮮度・根拠を表示する。Google-Ext… | 2 | S3 receipt |
| WT-FR-CV-01 | サイト単位の CV 正本へ本 CV（申込・購入・問い合わせ）、マイクロ CV（資料 DL・メルマガ登録・メッセージアプリ追加・比較テーブル閲覧・電話タップ）、補助指標（スクロール深度・滞在）を複数登録し、各 ID・種別・… | 2 | CV schema + data-layer receipt |
| WT-FR-CV-02 | 資料ダウンロードをフォーム入力からメール送付または即時ダウンロードの 2 経路で提供し、完了をマイクロ CV として WT-FR-TAG-02 の version 付きデータ層契約で計測する。第三者フォームが検出された場… | 2 | download + data-layer + plugin receipt |
| WT-FR-IMG-01 | 全 subsize を WebP（対応環境は AVIF も）で生成し、GIF アニメ・短尺動画は WebM と fallback MP4、video autoplay muted loop playsinline として… | 2 | asset fixture + plugin matrix + render audit |
| WT-FR-LEGAL-02 | タグ正本に外部送信先事業者・送信情報・利用目的を必須メタとして持たせ、登録タグから外部送信先一覧ページを自動生成する | 2 | S3 receipt |
| WT-FR-LEGAL-03 | No.1・ランキング・比較の調査主体・時期・対象・方法または編集部基準を商品正本へ保持し、脚注を自動表示する。価格条件・定期購入条件・個人の感想の打消しは CTA と同視野・同サイズの block style で表示する | 2 | S3 receipt |
| WT-FR-LOG-01 | ユーザー領域に保存された combined / gz のサーバー生ログを WP-Cron / WP-CLI で取り込み、cache 応答も crawl log へ response_origin 付きで記録する。取り込み… | 2 | S3 receipt |
| WT-FR-LOOK-01 | 見た目の型は目標数を持たず、用途（サイトパターン × 面 × 目的）から必要な型を選ぶ台帳（docs/research/2026-09-05-parts-pattern-taxonomy/by-purpose.md、実サ… | 4 | G-T3 + style list |
| WT-FR-LOOK-04 | 和文フォントをゴシック・明朝・丸ゴ・手書き / デザイン系など複数系統から選べ、unicode-range 分割サブセット、size-adjust、font-display: swap、OFL 表記を同じビルド工程で扱う… | 2 | S3 receipt |
| WT-FR-LP-01 | LP を投稿型（CPT、show_in_rest）として持ち、一覧・テンプレ割当・REST を固定ページから分離する。URL は固定ページがディレクトリ階層の配下に置かれるのに対し、LP はディレクトリに依存しない構造（… | 2 | Playwright + ledger |
| WT-FR-LP-02 | LP 単独の改善が全体 CV に大きく影響するため、LP はフォーム制御（フォームの配置・項目・送信先の JSON 宣言）、デザイン面の拡張性（LP 専用の variation / block style / セクション… | 2 | data-layer receipt + Playwright |
| WT-FR-MAIL-01 | 認証 SMTP と From 整合、SPF / DKIM / DMARC の状態を検査し、未設定を警告する。wp_mail_failed を操作ログへ残し、第三者 SMTP プラグイン検出時は送信を譲る | 2 | S3 receipt |
| WT-FR-NAV-01 | コア Breadcrumbs ブロックを表示し、BreadcrumbList 構造化データを同じ出力元から生成する。階層は投稿・固定ページ・LP の構造から機械導出し、保存 HTML に固定しない。LP のディレクトリ非… | 2 | S3 receipt |
| WT-FR-PARTS-01 | header（ロゴ位置・ナビ形・CTA・検索・固定挙動・透過の制御パターン）/ footer / sidebar / hero の複数案を同一 Block Types のパターン群として持ち、共通宣言を fluid で適… | 3 | Playwright + G-S2 |
| WT-FR-RECO-01 | 記事一覧に関連（カテゴリ→タグ→手動）、人気（運用者が集計方式と期間を選択）、おすすめ（手動指定・並び順）の3方式を持つ。人気の自前集計を選ぶ場合も bot・管理者を除外し、IP を保存せず日次で集約する。外部集計の読み… | 3 | S3 receipt |
| WT-FR-SECTION-01 | 見出し区間を一級の単位（section）にする。区間は「見出しレベル N から同レベル以上の次の見出しまで」と定義し、H2 区間の中に H3 区間を持つ階層とする（H4 以下は区間にしない、PO 2026-09-02）。… | 2 | extractor fixture |
| WT-FR-SECTION-02 | H2 / H3 区間単位で次を行える: 差し替え（別の書き方の区間へ入れ替え）、リライト（区間だけを再生成し diff → apply / rollback、記事全体を再生成しない）、順序入れ替え、面の挿入（区間の前後に… | 2 | REST receipt + Playwright + data-layer receipt |
| WT-FR-SELL-01 | 商品を schema 付き JSON または専用投稿型で正本化し、名前・価格・特徴・評価・画像・リンク先（アフィリエイト / 外部ストア / 自社 EC URL）を保持する。複数記事から同じ正本を参照して一括反映し、商品… | 2 | product catalog fixture |
| WT-FR-SELL-02 | 同じ商品正本から販売系 4 つ（商品カード・ランキング・比較専用テーブル・CTA 束）とレビューを出す。商品正本のリンク先種別に応じ、アフィリエイト・外部ストアは product snippet 形、自社 EC は配送・… | 2 | render fixture + JSON-LD extract |
| WT-FR-SELL-04 | 商品・バナーの /go/<id> 経路を 302 で提供し、rel=sponsored nofollow、robots 除外、商品 ID・時刻・参照元・device のサーバー側クリック記録を行う。IP は保存せず、保持… | 2 | S3 receipt |
| WT-FR-SEO-04 | SEO の要件と実装を Google 検索セントラルの公式ドキュメントに準拠させ、構造化データ（型ごとの必須 / 推奨プロパティ、FAQPage / HowTo / SearchAction など対象外・非推奨型を出さな… | 2 | source registry |
| WT-FR-SP-01 | 面・語彙・パーツの共通宣言を 1 本の fluid 定義で持ち、専用面・並び順・表示形などの device 別差分を WordPress 7.1 の `@mobile` / `@tablet` 上書きとして宣言する。Si… | 2 | responsive fixture + theme token audit |
| WT-FR-SP-02 | SP ヘッダー（ロゴ・ハンバーガー・検索・主要 CTA の配置と選択）、ドロワー（階層・CTA・SNS）、SP 下部固定（3〜5 タブ: 電話・メッセージアプリ・資料 DL・目次・トップへ。既存 SP 下部固定 slot… | 2 | responsive render + slot audit |
| WT-FR-SYNC-01 | template part・global styles・設定 JSON・商品正本・zone 割当をスラッグ参照・URL 非依存の選択セットとして export / import し、staging dry-run から … | 2 | S3 receipt |
| WT-FR-TAG-01 | 計測タグの注入面を head・body 開始直後・body 終端の 3 slot に限定し、タグ管理コンテナ / 個別断片の登録・選択を WT-UI-10 と MCP から行える。タグ設定と計測 ID は DB の選択と… | 2 | slot audit + config scan |
| WT-FR-TAG-02 | 表示・スクロール・CTA クリック・フォーム送信・資料 DL・バナー・商品 CTA・A/B variant・section 到達・device type を一つの version 付き JSON データ層契約で定義し、必… | 2 | schema contract + data-layer receipt |
| WT-FR-TAG-03 | 同意状態を必須・計測・広告の 3 カテゴリで運用者向け表示単位としてデータ層へ出し、Consent Mode v2 の 7 種への写像表も契約に含める。head slot の注入順は consent default を最… | 2 | consent fixture + server receipt |
| WT-FR-VALUE-01 | 値を安全域（プリセット / 尺度内）・生値（警告）・破壊域（停止）の 3 域で判定し、ブロック単位・記事単位の任意 CSS（WP 7.0 のコア機能を含む）も同じ値域判定を通して任意 CSS による迂回を許さない。破壊域… | 2 | editor + gate JSON |
| WT-FR-VOCAB-01 | 記事内語彙 14 種（囲み・ボタン・リンクカード・吹き出し・手順・記事一覧・アコーディオン・タブ・全幅・リッチメニュー・会員制限・比較表・定義リスト・FAQ）を core ブロック + block style で受け、新… | 3 | vocab fixture |
| WT-FR-VOCAB-03 | PR 表記は表示内容全体から広告であることが不明瞭にならない自己基準を満たし、テーマの最小文字サイズ以上・AA コントラスト・記事上部の固定 1 箇所とする。既定は他サイトで一般的な控えめな記事上部一文とし、ボタン・バナ… | 2 | Playwright + fixture |
| WT-FR-ZONE-01 | 共有 slot 6 種（本文前・関連前後・固定ページ上下・ヘッダー内・SP 下部固定・追尾サイドバー）をテンプレ / パーツ内のパターン挿入位置として持つ。共通宣言を fluid で適用し、専用面・並び順・表示形などの … | 2 | Playwright |
| WT-NFR-CRED-01 | credential・実サイト固有名・第三者製品名を公開リポジトリへ書かない。接続情報は環境変数と gitignore 済み local 設定 | 2 | public-safety check |
| WT-NFR-CV-01 | リード情報の保存先・保持期間・consent を明示し、consent なしに保存しない。CRM / MA はテーマ内に持たず、外部連携は署名付き webhook で押し出すまでとする。同意記録を時刻・版・カテゴリ・保持… | 2 | privacy + webhook receipt |
| WT-NFR-GATE-01 | 静的 6 ゲート（G-T1 / T1b / T2 / T3 / S1 / S2）と実機 G-E1（invalid=0）を、パターン・パーツ・テンプレ・styles を触る PR の完了条件にする | 2 | gate JSON |
| WT-NFR-LEGAL-01 | PR 表記の法令要件と GPL 配布要件（ライセンス表記・第三者資産の許諾）を満たす。OFL 全文、Reserved Font Name、asset ledger、SECURITY.md、semver / CHANGEL… | 2 | Playwright + license ledger |
| WT-NFR-LOG-01 | クロール・操作・監査ログに行数 / 容量上限を設け、URL × bot × 日の集約を日次で行う。生行は短期、集約は長期とし、容量逼迫を警告してサイト停止を招かない | 2 | S3 receipt |
| WT-NFR-OSS-01 | OFL フォントと第三者資産の全文ライセンス・Reserved Font Name の扱い・出所を JSON 台帳と readme へ機械生成し、台帳外資産を静的ゲートで赤にする。SECURITY.md、semver、C… | 2 | S3 receipt |
| WT-NFR-PERF-03 | 記事・LP・一覧・商品比較で主たる測定面はサイト設定で選び（既定は SP 幅）、SP / PC の両幅を測定対象とした Lighthouse / Core Web Vitals の LCP 2.5s、INP 200ms、… | 2 | Lighthouse receipt + CI receipt |
| WT-NFR-PERM-01 | 破壊域停止は権限で解除できず、write 系 API は capability と dry-run receipt を要求する。ability 単位の認可と Application Passwords の read / … | 2 | capability test |
| WT-NFR-PRIV-02 | WordPress privacy tools の exporter / eraser と privacy policy content に、リード・同意記録・操作ログ・クロール集約の保存内容と削除・出力範囲を登録する | 2 | S3 receipt |
| WT-NFR-REC-01 | 構造・スタイル・値・ゾーンの変更は dry-run → apply → rollback の経路を持ち、rollback で元の digest に戻る | 2 | rollback receipt |
| WT-NFR-REL-01 | 描画パスで set_theme_mod / update_option を呼ばない。redirect_canonical の全面停止や全ページ session_regenerate_id のようなグローバル改変を持ち込ま… | 2 | option diff |
| WT-NFR-SEC-01 | 未認証 REST（permission_callback __return_true）を持たず、外部 URL 取得は wp_safe_remote_get 等の検証付き経路のみ。公開面に PHP Warning を出さな… | 2 | REST audit |
| WT-NFR-SEO-01 | schema.org 型と Google 必須プロパティを検査する Rich Results Test 相当の test lane を持ち、必須プロパティ欠落と非推奨型を赤として扱う。FAQPage / HowTo / … | 2 | test-lane receipt |
| WT-NFR-SP-01 | SP の操作はタップ対象 44px 以上、本文文字 16px 以上、横スクロール 0 とし、下部固定・ドロワー・同意バーは本文や CTA を隠さず、積層順を固定する。PC 側にも共通 + device 別差分の同構造と検… | 2 | responsive interaction audit |
| WT-NFR-SP-02 | 主たる測定面はサイト設定で選び、既定は SP 幅とする。代表ページ種別を Lighthouse の mobile / PC 幅と SP / PC の両幅のスクリーンショット比較で検査し、ローカル Docker の実機相当… | 2 | responsive gate + preview + measurement receipt |
| WT-NFR-TAG-01 | 計測の検査で、同意前の非発火、データ層の必須項目、Consent Mode v2 7 種への写像表、head slot の consent default 最初の注入順、3 slot 外へのスクリプト注入 0、同意なしイ… | 2 | tag gate + transfer budget |
| WT-NFR-VALUE-03 | bridge 投影・子テーマ・user global styles は同スラッグへの値差し替えだけができ、段の増減・スラッグ変更・settings 上書きはスキーマ検査で拒否する。dimension preset、min… | 2 | projection fixture |
| WT-TR-API-02 | HELIX 連携 API の書き込みを batch（全成功か全失敗）とし、dry-run / rollback に対応する。投稿公開・設定変更・クロール異常・A/B 停止は署名付き webhook で押し出す | 2 | API receipt + webhook receipt |
| WT-TR-CLI-01 | MCP 常用パック・REST・WP-CLI の 3 面が、パック定義 JSON から生成された同じ能力集合を扱い、ずれたら契約テストで赤にする。Abilities API の同じ登録から 3 面を生成し、permissi… | 2 | capability parity receipt |
| WT-TR-CORE-01 | 面・部品・変種・値の尺度は theme.json / config / schema / openapi の JSON 宣言から列挙でき、PHP にしか存在する面を作らない | 2 | manifest diff |
| WT-TR-CORE-02 | health() は起動 step と module に加え、登録済みの slot・パターン・パーツ・variation・テンプレ変種・hook を自己申告する | 2 | health JSON |
| WT-TR-CORE-03 | AI 判定ロジック（variant 生成・統計判定・リスクスコア・モデル呼び出し）をテーマ・プラグインに持ち込まず、boundary guard を維持する | 2 | static analysis |
| WT-TR-HOST-01 | hosting capability manifest に PHP / DB 版、画像拡張、cron 駆動、cache / CDN、WAF、SMTP / DMARC、対応範囲を自己申告し、health・MCP・ハーネスが… | 2 | S3 receipt |
| WT-TR-PLUGIN-01 | 運用中の AI はテーマファイルを触らず、WordPress が DB に持つ「サイトの選択」（ヘッダー / サイドバー / hero の案、variation、テンプレ変種、ナビ、尺度内の値、slot のパターン、記事… | 2 | file digest + DB fixture + theme-switch fixture |
| WT-TR-PLUGIN-03 | 第三者プラグインの capability を領域別に検出し、フォームは送信イベントと同意確認をデータ層契約へ接続、キャッシュ / CDN は A/B cookie・同意 routing・crawl 計測の不成立を警告して… | 2 | plugin capability matrix + representative fixtures |

## 4. P1 / P2 要件（56 件）

| ID | 優先度 | 要旨 |
| --- | --- | --- |
| WT-FR-ADMIN-02 | P1 | WT-UI-10 の操作ログタブで AI と人の変更を時系列に表示し、対象・差分・実行者・結果で絞り込み、CSV / JSON export できる。ログ上限・日次集約、PR 根拠… |
| WT-FR-AGENT-02 | P1 | 本文の中間 JSON 抽出器を純関数（副作用なし、render_block 非依存、参照解決に深さ上限と訪問済み集合）として持ち、CLI / REST から呼べる |
| WT-FR-AGENT-06 | P1 | カスタムパーツの自己開発経路を持つ: 編集者または AI がブロックを組んで再利用パーツとして登録（参照 + 版 + digest、manifest に出る）→ 実機ゲート（inv… |
| WT-FR-AUDIT-02 | P1 | alt・速度予算・構造化データ・見出し階層は決定論的ルール検査に限定し、CTA 密度・証拠不足・PR 表記欠落・microcopy 未選択・Discover 画像要件未達などの監査… |
| WT-FR-BANNER-01 | P1 | バナー正本へ PC / SP 画像、リンク先、alt、種別（自社告知 / アフィリエイト / 広告 / 商品）、有効期間、PR 表記要否を登録し、商品バナーは商品 ID から派生す… |
| WT-FR-BANNER-02 | P1 | バナーの impression / click を WT-FR-TAG-02 の version 付きデータ層契約で tracking 経路へ送り、CV ID・A/B varian… |
| WT-FR-CRAWL-01 | P1 | プラグイン側で UA と逆引き / 公開 IP レンジ照合により検索エンジン系・AI 系クローラーを識別し、URL・時刻・ステータス・応答時間・ページ種別を専用テーブルへ記録する。… |
| WT-FR-CRAWL-02 | P1 | 管理画面 WT-UI-11 にクローラー別来訪数推移、最終クロールが古い URL、404 / 5xx URL、新規公開記事が初めて拾われるまでの時間、llms.txt / craw… |
| WT-FR-CRAWL-03 | P1 | robots.txt と AI クローラーの許可 / 拒否を WT-UI-11 から設定し、設定 JSON へ保存する。同じデータを MCP 常用パックと REST から取得できる… |
| WT-FR-CRAWL-04 | P1 | search / ai-input / ai-train の利用許諾を1設定から Content Signals 行、aipref Content-Usage 行またはヘッダ、RS… |
| WT-FR-CV-03 | P1 | CTA ボタンに主文言と任意の microcopy を持たせ、候補から選べるようにする。microcopy は必須化せず、A/B と section の計測を CV ID で集計し… |
| WT-FR-IMG-02 | P1 | 5MB 超・一括画像処理のうちブラウザを通らない CLI / REST / MCP 経路と既存画像の再生成だけをサーバー側 WP-Cron の非同期ジョブにし、ブラウザ側との二重処… |
| WT-FR-IMG-03 | P1 | alt を必須として空の画像は公開前に警告し、AI 側が埋める導線と GIF の動画置換提案を持つ。Discover は幅 1200px 以上の代表画像、robots meta m… |
| WT-FR-LOOK-03 | P1 | variation と block style の写像対象をテーマA のプリセットに留めず、サイトパターン（コーポレート / サービス / ブランド / ポータル / 比較サイト）… |
| WT-FR-META-01 | P1 | 投稿メタ 5 キー（sidebar / toc / share / pr / eyecatch）を登録し、テンプレ側の条件描画で記事単位の表示切替を持つ。eyecatch は位置（… |
| WT-FR-MIGRATE-01 | P1 | 移行の実行（移行元サイトの取得、サーバーファイルとテーマ情報の書き換え）はテーマの責務ではなく HELIX-WP-HARNESS の責務（PO 2026-09-02）。テーマが持つ… |
| WT-FR-MIGRATE-02 | P1 | マッピングフォーマットは、ウィジェット領域 → template part / slot、コアウィジェット → コアブロック、独自ブロック → 受け皿対応表、プリセット → var… |
| WT-FR-PAGE-01 | P1 | 会社概要・問い合わせ・採用・プライバシー・特商法・外部送信先一覧・アクセシビリティ方針の固定ページパターンを提供し、事業者情報 JSON を自動充填する。自前フォームは保持せず第三… |
| WT-FR-PARTS-02 | P1 | テンプレ変種（single-2col / single-1col）と footer のカラム可変を「テンプレ名」で表し、属性で幅や余白を変えない。ナビは wp_navigation… |
| WT-FR-SELL-03 | P1 | 管理画面（WT-UI-10）に商品一覧・編集を置き、AI を介さず価格・リンクを直せる。MCP 常用パックに商品の追加・更新・記事への差し込みを載せ、商品リンクのクリック計測を W… |
| WT-FR-SELL-05 | P1 | 商品正本と本文の外部リンクを WP-Cron で HEAD 検査し、リンク切れ・期限切れ・到達不能を WT-UI-10 と MCP 常用パックへ警告する |
| WT-FR-SEO-01 | P1 | 構造化データは単一出力元（型ごとに 1 本）とし、CollectionPage（一覧）を追加する。WebSite は site name 用の name / alternateNa… |
| WT-FR-SEO-03 | P1 | title / meta description / canonical / robots / sitemap / OGP を全ページ種別で出し、hreflang は多言語構成時だ… |
| WT-FR-SEO-05 | P1 | アフィリエイトリンクと商品 CTA へ rel="sponsored" を機械付与し、リンクの種類に応じた rel 属性を同じ出力経路で保つ。出典: https://develop… |
| WT-FR-SEO-06 | P1 | 公開・更新・削除時に IndexNow へ送信し、鍵は DB に保持して公開面には検証用の鍵ファイルだけを出す。送信失敗は操作ログへ記録し、送信機構は Core プラグインの譲渡領… |
| WT-FR-SNS-01 | P1 | SNS profile を設定 JSON の一か所に登録し、header / footer / 著者欄と構造化データ sameAs へ反映する。対象 SNS と記事上下・フロート・… |
| WT-FR-SNS-02 | P1 | SNS 投稿の feed 埋め込みをブロックとして提供し、埋め込みスクリプトを遅延読込して速度予算内にする。メッセージアプリ公式アカウントの友だち追加ボタンと QR を LP のフ… |
| WT-FR-SP-03 | P1 | 共通の語彙定義に対する device 別差分を JSON で宣言し、SP では比較テーブルは横スクロール / カード、タブはアコーディオン、目次はフロートから開閉ボタン、ギャラリー… |
| WT-FR-TPL-01 | P1 | 404 と検索結果に選べる複数のテンプレ変種（人気記事・CTA・検索語提案）を持ち、404 には LP・比較記事・問い合わせへの CV 導線 slot を置ける。検索結果は noi… |
| WT-FR-TYPO-01 | P1 | 和文の既定 CSS に line-break: strict、overflow-wrap: anywhere、word-break: normal、text-autospace、t… |
| WT-FR-VALUE-02 | P1 | 生値は許容リスト方式（尺度系はプリセット参照へ、意匠値は許容リスト）へ移し、baseline 438 を段階的に 0 にする。ブロック単位・記事単位の任意 CSS も同じ値域・許容… |
| WT-FR-VOCAB-02 | P1 | 目次はテーマ内蔵とし、実体は本文の h2/h3 からレンダラが機械導出する（保存 HTML に固定しない）。編集者と AI が選べるのは (a) 配置方式: 固定埋め込み（既定は最… |
| WT-FR-VOCAB-04 | P1 | ブログカード（内部リンク）は REST を経由せず url_to_postid() 直呼びで解決し、外部 URL は検証付き HTTP のみ |
| WT-FR-ZONE-02 | P1 | ゾーン語彙 23 種を JSON schema で宣言し、creative は参照（ID）、overrides は first-match-wins の配列で持つ |
| WT-NFR-A11Y-01 | P1 | 域の判定・状態表示は label と icon を伴い色だけに依存しない。img alt 欠落 0、AA コントラスト、横スクロール 0。WCAG 2.2 AA を到達目標とし、A… |
| WT-NFR-A11Y-02 | P1 | prefers-reduced-motion を検出した場合は動きと autoplay を停止または静的表示へ縮退し、アニメーションを操作完了の必須条件にしない |
| WT-NFR-CRAWL-01 | P1 | クロールログの保持期間と間引きは既定 90 日とし、bot 判定外の個人閲覧を記録しない。対象は WP が応答したリクエストだけで、キャッシュ / CDN 応答は見えない限界をダッ… |
| WT-NFR-OBS-01 | P1 | health / gate / 台帳 / 抽出器の出力は JSON で、HEAD と digest に束縛される |
| WT-NFR-PERF-01 | P1 | 記事・LP・一覧・商品比較は主たる描画・計測面はサイト設定で選び（既定は SP 幅）、SP / PC の両幅を検査し、JS 無しで全表示が成立することを原則とし、動きは CSS /… |
| WT-NFR-PERF-02 | P1 | CSS を語彙単位で分割して使用分だけ出し、共通定義は fluid、device 別差分は必要な幅だけ出す。ビルド工程で短縮化し、critical CSS は inline、残りは… |
| WT-NFR-PRIV-01 | P1 | 計測・広告・外部コードの正本はテーマ外（HELIX / プラグイン側）。テーマ内に計測 ID・広告コードを持たない。人気の自前集計を選んだ場合に限り、IP を持たない日次集約のみを… |
| WT-TR-AGENT-05 | P1 | REST は自前名前空間で WP コアに相乗りしない。本文変換は生成時 / レンダリング時 / 表示時の 3 層で、テーマ語彙のショートコードは意図ノードへ、プラグイン語彙は不透明… |
| WT-TR-API-01 | P1 | HELIX 連携 API の全一覧読み取りに since / fields / ETag / Last-Modified を備え、差分取得を契約として保証する |
| WT-TR-API-03 | P1 | HELIX 連携 API の応答に schema version を必須化し、破壊的変更時は旧版を併走させる。OpenAPI lint / 差分検査を CI に置き、MCP 常用パ… |
| WT-TR-HOST-02 | P1 | 対応範囲を WP 7.1 以上、PHP 8.2〜8.5、MySQL 8.4 / MariaDB 11 として対応表に固定し、iframed 投稿エディタでブロック JS / CSS… |
| WT-TR-PLUGIN-02 | P1 | 第三者プラグインとの共存規約: 出力が重なる領域（JSON-LD、meta / OGP、目次、サイトマップ、llms.txt、キャッシュ、フォーム、同意管理・計測）ごとに「本テーマ… |
| WT-TR-PLUGIN-04 | P1 | 検出結果、領域ごとの既定（本テーマが出す / 検出して譲る / 設定で選ぶ）、現在の選択、警告、検査対象構成を capability manifest に載せ、WT-UI-10 と… |
| WT-FR-AGENT-03 | P2 | 主要パーツ前後の do_action 10 箇所と出力の apply_filters 10 箇所を持ち、hook 一覧を manifest に含める |
| WT-FR-AGENT-04 | P2 | 再利用パーツは参照（ID）で持ち、解決に使った版と digest を記録する。展開して保存しない |
| WT-FR-INTAKE-01 | P2 | 実証記録台帳を本テーマ内で完結する append-only の JSON Lines として持つ。1 行 = パターン ID・参照元 commit・証跡パス・ゲート結果（静的 6 … |
| WT-FR-LOOK-02 | P2 | デザインプリセット 1 個を style variation 1 本（色 8 スラッグの値差し替え）として写像し、部品別プリセットは block style で受ける。写像の対象範… |
| WT-FR-MIGRATE-03 | P2 | 移管対象の推奨はサイト固有（意味に属する 60〜80 キー）に絞り、見た目は本テーマで作り直す方針をマッピングフォーマットに明記する。互換 meta キーの固有名は公開本体に置かず… |
| WT-FR-SEO-02 | P2 | FAQ・手順の語彙と本文を残しつつ、FAQPage / HowTo の JSON-LD は既定で出力せず、任意 ON 設定も持たない。ItemList は語彙から自動生成し、本文と… |
| WT-FR-ZONE-03 | P2 | SP 下部固定領域の積層は 同意バー（位置を下部固定に選んだ場合のみ）> メニュー > シェア の順で、本文最下部 CTA と重ねない。お知らせバーはヘッダー直下 slot、ページ… |
| WT-NFR-COST-01 | P2 | ゲート・PoC はローカル docker で完結し、無料枠制限や課金を伴う外部 API に依存しない |
| WT-NFR-ENV-01 | P2 | Text Domain と翻訳関数を準拠させ、POT を CI で生成し、日本語・英語のソース言語を明示する。RTL は対応範囲外として記録する |

## 5. 検証方法の内訳（L10）

| evidence | テスト数 |
| --- | --- |
| S3 receipt | 26 |
| gate JSON | 2 |
| Playwright | 2 |
| REST audit | 2 |
| extractor fixture | 2 |
| public-safety check | 2 |
| manifest diff | 1 |
| health JSON | 1 |
| static analysis | 1 |
| file digest + DB fixture + theme-switch fixture | 1 |
| plugin matrix（7 領域）+ consent fixture + static analysis | 1 |
| schema test | 1 |
| Playwright + G-S2 | 1 |
| parts reference test | 1 |
| vocab fixture | 1 |
| render fixture + Playwright | 1 |
| Playwright + fixture | 1 |
| REST receipt + Playwright + data-layer receipt | 1 |
| G-T3 + style list | 1 |
| G-T1b JSON | 1 |
| survey inventory + gate JSON | 1 |
| REST + Playwright | 1 |
| Playwright + schema test + manifest parity | 1 |
| Playwright + ledger | 1 |
| data-layer receipt + Playwright | 1 |
| schema test + harness mapping receipt | 1 |
| conversion receipt（ハーネス側）+ format schema test | 1 |
| MCP receipt + REST parity | 1 |
| hook audit | 1 |
| digest diff | 1 |
| OpenAPI diff | 1 |
| G-E1 + ledger + cross-theme fixture | 1 |
| editor + gate JSON | 1 |
| projection fixture | 1 |
| JSON-LD extract + plugin matrix + source receipt | 1 |
| JSON-LD extract + source receipt | 1 |
| crawl JSON + source receipt | 1 |
| product catalog fixture | 1 |
| render fixture + JSON-LD extract | 1 |
| admin + MCP receipt + tracking receipt | 1 |
| source registry | 1 |
| test-lane receipt | 1 |
| rendered-link audit | 1 |
| crawl log fixture | 1 |
| dashboard fixture | 1 |
| settings + MCP / REST parity | 1 |
| retention + boundary receipt | 1 |
| ledger validation | 1 |
| option diff | 1 |
| static grep | 1 |
| capability test | 1 |
| dependency audit | 1 |
| Playwright + license ledger | 1 |
| CI artifact | 1 |
| axe / contrast | 1 |
| rollback receipt | 1 |
| responsive transfer size report | 1 |
| variant fixture + responsive render receipt | 1 |
| data-layer receipt | 1 |
| admin + MCP receipt | 1 |
| asset fixture + plugin matrix + render audit | 1 |
| async job receipt + CLI receipt | 1 |
| SEO + asset audit | 1 |
| build artifact + budget receipt | 1 |
| Lighthouse receipt + CI receipt | 1 |
| API contract fixture | 1 |
| API receipt + webhook receipt | 1 |
| OpenAPI lint + diff receipt | 1 |
| admin + export receipt | 1 |
| diff + rollback receipt | 1 |
| key-management receipt | 1 |
| capability parity receipt | 1 |
| profile + render receipt | 1 |
| render + script audit | 1 |
| CV schema + data-layer receipt | 1 |
| download + data-layer + plugin receipt | 1 |
| CTA fixture + tracking receipt | 1 |
| privacy + webhook receipt | 1 |
| banner fixture + render receipt | 1 |
| banner + data-layer receipt | 1 |
| audit + diff receipt | 1 |
| audit export + MCP receipt | 1 |
| responsive fixture + theme token audit | 1 |
| responsive render + slot audit | 1 |
| vocab contract + preview parity | 1 |
| responsive interaction audit | 1 |
| responsive gate + preview + measurement receipt | 1 |
| slot audit + config scan | 1 |
| schema contract + data-layer receipt | 1 |
| consent fixture + server receipt | 1 |
| tag gate + transfer budget | 1 |
| plugin capability matrix + representative fixtures | 1 |
| manifest parity + conflict receipt | 1 |

**弱点（Claude 所見）**: evidence が「S3 receipt」とだけ書かれたテストが 26 件ある。これは S3 反映時に oracle を仮置きしたもので、L5 の先行テスト設計で具体の evidence 種別（Playwright / schema test / static analysis 等）へ置き換える必要がある。凍結の妨げにはしないが、L5 の義務として記録する。

## 6. 開発スタイル（WT-Q-STYLE-01）

`V_DESIGN_SCRUM_IMPLEMENTATION`: 要件凍結後、L4 基本設計（責務分割: テーマ / Core プラグイン / 設定 JSON schema / MCP パック / ゲート）と L5 詳細設計 + 先行テスト設計を V 字で固め、L6 実装〜L9 結合を Scrum の反復で進める。反復ごとに L7 の Red → Green → Refactor 証跡と L8 / L9 を閉じ、L10 総合で本 IR の受入条件に照らす。要件変更は L2 へ戻し、影響する設計・テスト・証跡を digest で識別して再承認する。
代替は `V_FULL`（全層を逐次）だが、パーツ数が用途別に増減する本テーマでは反復の方が合う（Claude 所見）。

## 7. 承認後に起きること

1. WT-Q-G3-01 / WT-Q-STYLE-01 の回答を `requirement_decision_recorded` として追記（iteration 2）
2. 優先度変更なしなら `stable_priority_iterations` を true にし、compile を再実行して全件 `specified`
3. PO 承認を `freeze_recorded`（G3）として追記し、IR を `canonical` / 全件 `frozen` に。以後の変更は revision を上げ digest 検査で追跡
4. L4 基本設計へ引き渡す（各要件の design obligation を導出）
