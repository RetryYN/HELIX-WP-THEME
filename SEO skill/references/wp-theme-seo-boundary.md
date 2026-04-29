# WordPress Theme SEO Boundary

## Theme Responsibilities

- セマンティックHTML、見出し階層、パンくず、ナビゲーション、本文構造を安定して出す。
- `theme.json` でタイポグラフィ、余白、色、レイアウト、ブロック設定を管理する。
- FSEの `templates`、`parts`、`patterns` を使い、AIがセクション単位で差し替えやすい構造にする。
- `section_id`、`block_id`、`data-neo-*` 属性を使い、AI操作対象と計測対象を一致させる。
- LCP、INP、CLSを悪化させる不要JS、巨大CSS、レイアウトシフト、重いフォントを避ける。
- 構造化データは表示内容と一致させ、JSON-LDを重複や矛盾なしで出す。

## Automation SEO Or Core Plugin Responsibilities

- GSC、GA4、IndexNow、順位計測、クロール、ログ、監査ジョブを管理する。
- SEOメタ、canonical、noindex、OGP、schema設定を永続化する。
- キーワードクラスタ、検索意図、コンテンツ設計、内部リンク提案をJSONで管理する。
- A/Bテスト、CTA、イベント計測、CVファネルを実行する。
- APIキー、OAuth、非公開トークン、個人情報をテーマ側に置かない。

## Anti-Patterns

- テーマに独自SEO設定を閉じ込めて、REST APIやWP-CLIで読めない。
- AI生成本文を根拠、著者、更新日、レビュー状態なしで公開する。
- ブロックエディタのHTMLに依存しすぎて、変更差分が追跡できない。
- canonical、sitemap、hreflang、構造化データが互いに矛盾する。
- プラグイン停止時に重要メタやCTAが消える。
- 大量ページの生成だけを行い、indexability、重複、品質、内部リンクを評価しない。

## GitHub Skill Forking

GitHubは、AGENT NEO用SEOスキル群の配布、フォーク、差分レビュー、Pull Request、バージョン管理に使う。つまり、各プロジェクトや顧客ごとに `SEO skill` をフォークし、業種別・サイト種別・運用ポリシー別に拡張する前提にする。

運用ルール:
- upstreamを正本スキルリポジトリにする。
- fork側は顧客固有、業種固有、プロジェクト固有の差分だけを持つ。
- upstream同期時に、セキュリティ、Google公式仕様、WP仕様、Automation SEO契約の更新を取り込む。
- 変更はPull Requestでレビューし、スキル名、description、入力、出力、禁止事項、参照URLを必ず確認する。
- 顧客固有の秘密情報、APIキー、個人情報、非公開戦略はスキルリポジトリに入れない。

## Optional GitHub Actions Crawling

GitHubは検索エンジンのように勝手に巡回しない。定期実行したい場合は、Actionsの `schedule` でPlaywright、Lighthouse、サイトマップ検査、URL Inspection API、構造化データ検査などのジョブを動かす。

制約:
- scheduleはデフォルトブランチ上で実行される。
- cronはUTC基準で設計する。
- 高頻度巡回は避け、API制限と対象サイト負荷を管理する。
- 認証情報はGitHub Secretsに置き、ログに出さない。

