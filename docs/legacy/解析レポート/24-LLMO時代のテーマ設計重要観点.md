# LLMO時代のテーマ設計重要観点

## 1. 結論

LLMO/GEO時代のWordPressテーマは、従来SEOの延長では足りない。AGENT NEOでは「検索エンジンにindexされる」だけでなく、AI回答に引用される、AIが根拠として読み取れる、AI学習やユーザー要求取得への許可を制御できる、AI経由のCVを計測できることを製品機能にするべきである。

最重要は以下の5点。

| 優先 | 観点 | AGENT NEOでの設計 |
|---|---|---|
| P0 | Search AIとTraining AIの分離 | `crawler-access-matrix.json`を拡張し、search、ai-input、ai-trainをcrawler別/page別に制御 |
| P0 | 引用されやすい回答単位 | `answer-unit.schema.json`で質問、結論、根拠、更新日、著者、CTAをsection単位に構造化 |
| P0 | 根拠と信頼の機械可読化 | `evidence-graph.schema.json`でclaim、source、reviewer、検証日、schema.org entityを接続 |
| P0 | AI可読snapshot | JS操作なしで読めるHTML/snapshot、見出し、表、FAQ、比較、価格、CTAを公開 |
| P0 | AI経由の計測 | `llmo-visibility.schema.json`でAI crawler、AI referral、citation、AI経由CVを記録 |

## 2. Web検索で確認した前提

| ソース | 確認した前提 | 設計への意味 |
|---|---|---|
| Google AI features and your website | AI Overviews/AI ModeはSearchの一部として扱われ、Googlebotとpreview controlsで制御する | Google検索AI向けにはGooglebotを閉じすぎず、`nosnippet`/`max-snippet`/`data-nosnippet`/`noindex`をページ単位で使う |
| OpenAI Crawlers | `OAI-SearchBot`はChatGPT Search表示、`GPTBot`は学習利用の制御対象として分かれる | ChatGPT検索には出したいが学習は閉じたい、というpresetが必要 |
| Anthropic crawler docs | `ClaudeBot`、`Claude-User`、`Claude-SearchBot`が用途別に分かれる | training/search/user-requestを同じrobots設定で扱わない |
| Perplexity Crawlers | `PerplexityBot`は検索結果表示、`Perplexity-User`はユーザー要求取得で、後者はrobotsの扱いが異なる | WAF/allowlistとrobotsだけに依存しないアクセス診断が必要 |
| Cloudflare Content Signals Policy | `search`、`ai-train`、`ai-input`の意思表示ができるが、技術的強制ではない | robots.txtは意思表示、WAF/Bot管理は強制制御として分ける |
| Google helpful content/E-E-A-T | 人向けの信頼性、Who/How/Why、E-E-A-Tの概念整理が重要 | AI生成記事にも著者、監修、作成方法、根拠、更新理由を出す |
| Bing AI Performance | AI回答で引用されたURLやAI体験での可視性計測が重要になっている | LLMOは流入数だけでなく、引用、意図、CV準備度を測る |

## 3. AGENT NEOが配慮すべきLLMO設計ポイント

### 3.1 AIに引用されるページ構造

AI回答に使われやすいページは、長い記事をそのまま置くより、質問に対する短い結論、条件、根拠、例外、次の行動が機械的に取り出しやすい。

| 要素 | 実装方針 |
|---|---|
| Answer Unit | 1セクション1質問。`question`、`short_answer`、`details`、`evidence_ids`、`updated_at`を持つ |
| Claim | 数値、比較、レビュー、価格、対応範囲は`claim_id`を付ける |
| Evidence | 公式URL、一次情報、実測、顧客事例、検証日を`evidence_id`で紐付ける |
| Citation Anchor | `#answer-{id}`、`data-answer-id`、`data-claim-id`でAI/人間が同じ箇所を参照できる |
| Summary | 冒頭に3-5行の要約、本文内に比較表、FAQ、手順を置く |

### 3.2 Search/AI Input/Trainingの権利分離

LLMOでは「AIに見つけてほしい」と「AI学習に使われたくない」が同時に成立する。これをプリセット化する。

| Preset | search | ai-input | ai-train | 用途 |
|---|---|---|---|---|
| `ai_search_open_train_closed` | allow | allow | deny | 推奨初期値。AI検索と引用は狙い、学習利用は閉じる |
| `ai_search_only` | allow | deny/limited | deny | 検索露出は維持、ユーザー要求取得は制限 |
| `ai_all_open` | allow | allow | allow | 認知拡大優先の公開メディア |
| `ai_closed` | deny | deny | deny | 会員/社内/機密寄りコンテンツ |
| `custom_by_page` | page別 | page別 | page別 | 法務・ブランド方針が細かい法人 |

robots.txt、meta robots、X-Robots-Tag、Content Signals、WAF allow/denyを別レイヤーとして管理する。robots.txtは意思表示であり、全クローラへの強制制御ではない。

### 3.3 E-E-A-Tを「表示」ではなく「データ」にする

GoogleはE-E-A-T自体を単一ランキング要因とはしていないが、信頼性の評価観点として重要である。LLMOではAIも「誰が、どう作り、なぜ信頼できるか」を抽出できる必要がある。

| データ | 内容 |
|---|---|
| `who` | 著者、監修者、会社、資格、プロフィールURL |
| `how` | AI生成/人間編集/実測/取材/レビューの作成方法 |
| `why` | 読者の問題解決、販売目的、PR表記、更新理由 |
| `review` | 監修者、レビュー日、変更差分、承認状態 |
| `trust` | 会社情報、問い合わせ、返品/保証、広告/アフィリエイト開示 |

### 3.4 AI回答経由CVを前提にしたLP

AI検索では、ユーザーは比較・要約を済ませてから来る可能性が高い。LPは「最初から教育」より、「AI回答で興味を持った人が確認と申込をする」構成も必要。

| LP部品 | LLMO向け改善 |
|---|---|
| Hero | 何を解決するかを1文で明示し、対象者/非対象者を出す |
| Comparison | 競合比較は条件・根拠・更新日を明記 |
| FAQ | AIが引用しやすい短文回答 + 詳細説明 |
| Proof | 導入事例、数字、レビュー、出典、検証日を機械可読化 |
| CTA | AI経由流入用に「資料DL」「比較表を見る」「診断する」など中間CVを用意 |

### 3.5 AIに誤引用されにくいガード

AIに読まれる前提では、曖昧な比較や古い価格、PR不足はリスクになる。

| リスク | ガード |
|---|---|
| 古い情報の引用 | `valid_from`、`valid_until`、`last_verified_at`を必須化 |
| 過剰な効能表現 | `claim-risk.schema.json`で保証表現、医療/金融/YMYL表現を警告 |
| PR/広告の未表示 | affiliate/review/comparison blockに`disclosure_required`を持たせる |
| AI生成の透明性不足 | `content-origin.schema.json`でAI生成、人間編集、監修を記録 |
| 引用先のズレ | canonical、answer anchor、content hash、rendered snapshotを照合 |

## 4. 追加すべきJSON契約

| 契約 | 目的 |
|---|---|
| `llmo-profile.json` | サイト全体のLLMO方針、対象AI面、計測方針を宣言 |
| `answer-unit.schema.json` | Q&A/比較/手順/定義/FAQをAI回答単位で構造化 |
| `evidence-graph.schema.json` | claim、source、reviewer、updated_at、schema entityを接続 |
| `content-origin.schema.json` | AI生成/人間編集/監修/実測/取材の作成過程を記録 |
| `ai-visibility-policy.json` | page別のsearch、ai-input、ai-train、snippet、WAF方針を管理 |
| `ai-crawler-policy.schema.json` | OpenAI/Google/Anthropic/Perplexity/Bing/Cloudflareのbot別許可を管理 |
| `citation-anchor.schema.json` | anchor、section_id、claim_id、canonical、content_hashを管理 |
| `llmo-visibility.schema.json` | AI crawler、AI referral、citation、query intent、CVを計測 |
| `claim-risk.schema.json` | 保証表現、PR表記不足、YMYL、古い価格、根拠不足を検出 |
| `ai-answer-sitemap.xml` | AIに読ませたいanswer unit、FAQ、比較、Product、LocalBusinessを列挙 |

## 5. 個人版/法人版への落とし込み

| SKU | 強化ポイント |
|---|---|
| 個人版 | レビュー/ランキング/比較表を`answer-unit`化し、PR表記、根拠、検証日、価格更新日を必須にする |
| 個人版 | ASPクリック前にAI回答で比較される前提で、商品カードに短い結論、向く人/向かない人、代替案を入れる |
| 法人版 | 製品LPに`Problem -> Answer -> Evidence -> CTA`のAI引用向け構造を標準化する |
| 法人版 | Organization/Product/LocalBusiness/FAQ/Review/OfferをEntity Graphに統合し、営業資料DL/問い合わせへ接続する |
| 共通Core | crawler access matrix、ai visibility policy、answer sitemap、citation anchor、LLMO計測を共通機能にする |

## 6. やってはいけないこと

| 禁止 | 理由 |
|---|---|
| LLMOのためだけに薄いFAQを量産する | helpful contentではなく検索/AI操作目的に見える |
| 全AI botを一括allow/denyだけで管理する | search、training、user requestで意味が違う |
| robots.txtだけで権利制御できると説明する | 一部は意思表示であり、技術的強制にはWAF/Bot管理が必要 |
| AI生成記事を著者/監修/根拠なしで大量公開する | 信頼性、法務、ブランド、検索品質のリスクが高い |
| 価格・在庫・ランキングを更新日なしで出す | AIに古い情報を引用される |
| accordion/tabs/load-moreの中に重要情報を隠す | 非対話クローラやsnapshotで欠落しやすい |

## 7. AGENT NEOの差別化に使える売り文句

| 訴求 | 中身 |
|---|---|
| AI検索に読まれるLP | answer unit、citation anchor、Entity Graph、AI snapshotを標準搭載 |
| 学習利用は閉じ、AI検索露出は狙う | search/input/trainingを分離したcrawler policy preset |
| AI回答からのCVを測る | AI crawler log、AI referral、citation、CTA/CVを接続 |
| AIが誤引用しにくい | claim/evidence/reviewer/updated_at/content hashを持つ |
| 法人導入しやすい | PR、Privacy、WAF、robots、Content Signals、監査ログを契約化 |

## 8. L1/L2反映案

| 反映先 | 追加 |
|---|---|
| L1 | `REQ-NF-017 LLMO/AI検索最適化` |
| L1 | `ACC-NF-011`としてanswer unit、evidence graph、crawler policy、AI visibility、LLMO計測の受入条件 |
| L2 | `ADR-015 LLMOをSEO Coreの拡張ではなく独立契約にする` |
| L2 | `F-021 LLMO Governance` |
| L2 | `S-012 LLMO Visibility` |

## 9. 参照URL

- Google AI features and your website: https://developers.google.com/search/docs/appearance/ai-features
- Google robots meta/snippet controls: https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag
- Google helpful content/E-E-A-T: https://developers.google.com/search/docs/fundamentals/creating-helpful-content
- Google SEO Starter Guide: https://developers.google.com/search/docs/fundamentals/seo-starter-guide
- Google structured data: https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data
- Google sitemaps: https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview
- OpenAI crawlers: https://developers.openai.com/api/docs/bots
- Anthropic crawlers: https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler
- Perplexity crawlers: https://docs.perplexity.ai/docs/resources/perplexity-crawlers
- Cloudflare Content Signals Policy: https://blog.cloudflare.com/content-signals-policy/
- Bing AI Performance: https://blogs.bing.com/webmaster/February-2026/Introducing-AI-Performance-in-Bing-Webmaster-Tools-Public-Preview
- Bing AI search measurement: https://blogs.bing.com/webmaster/November-2025/How-AI-Search-Is-Changing%E2%80%AFthe%E2%80%AFWay%E2%80%AFConversions%E2%80%AFare-Measured
