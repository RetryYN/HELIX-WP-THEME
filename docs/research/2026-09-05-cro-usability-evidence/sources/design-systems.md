# 公開デザインシステムが採用しているルール調査（WP テーマへ転用可能な規則）

- 調査日: 2026-09-05
- 目的: 公開デザインシステム／フレームワークが「根拠つきで」採用しているルールを抽出し、WordPress テーマの部品規則として再利用できる形にする。
- 方針: 数値はすべて出典ページに記載のもののみ。出典から取得できなかった項目は「未取得」と明記し、推定値を書かない。
- 表記: 「適用部位」は WP テーマの部品（theme.json / ブロックスタイル / パターン）を指す。
- 取得できなかった一次ソース: Material Design 3 の Web サイト（m3.material.io）は JS レンダリングで本文が取れず、代替として Material Components (GitHub) の文書を使用。Atlassian Button usage、Polaris Motion、WordPress Accessibility Handbook（色コントラスト）は 404 で未取得。

凡例: [研究] = ユーザーリサーチ・A/B テストの言及あり、[WCAG] = WCAG 等の標準を明示的に引用。

---

## 1. GOV.UK Design System

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| G-1 | 1 ページに default（primary）ボタンを複数置かない。「Pages with too many calls to action make it hard for users to know what to do next.」secondary は補助操作、warning は破壊的操作に限り「Most services should not need one」。 | ボタン（階層） | 設計方針 | https://design-system.service.gov.uk/components/button/ |
| G-2 | Start ボタンに緑を使ったところクリック率が改善した [研究]。inverse ボタンは背景に対し 4.5:1 以上のコントラスト [WCAG]。 | ボタン（色・CTA） | ユーザーリサーチ | 同上 |
| G-3 | disabled ボタンは「poor contrast and can confuse some users」なので原則使わない。使うのは research で分かりやすさが示された場合のみ。 | ボタン（状態） | 研究条件付き | 同上 |
| G-4 | ボタン文言は動作を sentence case で記述（「Start now」「Continue」「Save and continue」「Accept and send」）。 | ボタン（文言） | 設計方針 | 同上 |
| G-5 | アコーディオンは「evidence shows it helps users」がある場合だけ使う。「Accordions hide content from the user. Not all users will notice them」。全ユーザーに必要な内容には使わない。入れ子禁止。まずアコーディオンなしで試す。 | アコーディオン | 研究条件付き [研究] | https://design-system.service.gov.uk/components/accordion/ |
| G-6 | 1 セクションだけなら accordion／tabs でなく details を使う。「Do not use the details component to hide information that the majority of your users will need.」 | 開閉 UI（details） | 設計方針 | https://design-system.service.gov.uk/components/details/ |
| G-7 | エラー文は「何が起きたか＋直し方」を平易な肯定文で。「please」「sorry」「valid/invalid」を使わない。ラベルと同じ語で書く。「This field is required」のような汎用文は不可。空欄には指示文、長さ超過には説明文。 | フォーム（エラー文） | ユーザーテストで「understood what went wrong」「were able to recover」を確認 [研究] | https://design-system.service.gov.uk/components/error-message/ |
| G-8 | エラーは赤で質問・ヒントの後に表示し、フィールドに赤い枠。エラー時に入力値を消さない。ページ上部の Error summary と両方に出す。 | フォーム（エラー表示） | 設計方針 | 同上 |
| G-9 | Cookie バナーは `<body>` 直後・skip link より前に置く。`position: fixed` で固定しない（WCAG 2.2 SC 2.4.11 対応）[WCAG]。同意前に非必須 cookie を設定しない。accept／reject を明確に分け、「View cookies」リンクを置く。選択後は確認メッセージ＋hide ボタン、設定を 1 年保存。 | Cookie バナー | WCAG 2.2 | https://design-system.service.gov.uk/components/cookie-banner/ |
| G-10 | フォーカスは黄背景＋黒の太い枠の 2 色。「The yellow has a high contrast with dark backgrounds and the thick black border has a high contrast against light backgrounds.」WCAG 2.2 SC 1.4.11 Non-text contrast AA を満たす [WCAG]。 | フォーカス状態 | WCAG 2.2 | https://design-system.service.gov.uk/get-started/focus-states/ |
| G-11 | 通知バナーは h1 の直前、本文と同幅。「Use notification banners sparingly. There's evidence that people often miss them」[研究]。複数同時に出さない。バリデーションには使わない。success は緑＋見出し「Success」で色だけに頼らない。role=region、success は role=alert＋自動フォーカス。 | お知らせ／通知バナー | ユーザーリサーチ | https://design-system.service.gov.uk/components/notification-banner/ |
| G-12 | タイプスケール（大画面/小画面）: 48px/32px（heading-xl）、36/27（l）、24/21（m）、19/19（s・body）、16/16（body-s）。「Every point on the type scale uses a line height in a multiple of 5px」。切替は 640px。 | 見出しスケール・本文 | 設計方針 | https://design-system.service.gov.uk/styles/type-scale/ |
| G-13 | レスポンシブ spacing scale 0–9（大画面 0,5,10,15,20,25,30,40,50,60px／小画面は 4 以上を縮小: 15,15,20,25,30,40px）。0–3 は全画面共通。 | spacing scale | 設計方針 | https://design-system.service.gov.uk/styles/spacing/ |
| G-14 | テキスト入力は既知の長さに合わせた固定幅（郵便番号は郵便番号サイズ）。ラベルは常時表示・上配置・コロンなし。placeholder をラベル代わりに使わない（「vanishes when the user starts typing」）。整数は `inputmode="numeric"`、`type="number"` は研究で必要が示された場合以外使わない。`autocomplete` で WCAG SC 1.3.5 を満たす [WCAG]。 | フォーム（入力） | 研究＋WCAG | https://design-system.service.gov.uk/components/text-input/ |
| G-15 | テーブルは `<caption>` を見出しのように使う。列見出し `scope="col"`、行見出し `scope="row"`。数値は右揃え。レイアウト目的で使わない。小画面で文字を縮める class はデータが多い表に限る（少量なら大きい文字の方が読みやすい）。 | テーブル | 設計方針 | https://design-system.service.gov.uk/components/table/ |
| G-16 | サービスナビは現在ページに `aria-current="true"`。項目が 2 つ以上あればモバイルでメニューに折りたたむのが既定（`collapseNavigationOnMobile`）。 | ナビゲーション | 設計方針（研究は別ページ参照） | https://design-system.service.gov.uk/components/service-navigation/ |

## 2. US Web Design System (USWDS)

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| U-1 | 「Use standard buttons for actions that go a next step」「Use outline buttons for actions that happen on the current page」。重要操作には色・サイズで区別。「Lead with a verb」「Avoid using too many buttons on a page」。sentence case。 | ボタン（階層・文言） | 設計方針 | https://designsystem.digital.gov/components/button/ |
| U-2 | ボタンは tab 移動時に visible focus を出す。`<div>`/`<img>` で作らない。`type` を必ず指定。無効化は `disabled` か `aria-disabled`。 | ボタン（a11y・フォーカス） | 設計方針 | 同上 |
| U-3 | spacing unit は 8px の倍数を基本（1,2,4,8,12px の小刻み＋16〜80px を 8px 刻み、さらに card 160px / card-lg 240px / mobile 320px などの名前つき大サイズ）。 | spacing scale | 設計方針 | https://designsystem.digital.gov/design-tokens/spacing-units/ |
| U-4 | フォントサイズのテーマトークン 9 段: 3xs 13px, 2xs 14, xs 15, sm 16, md 17, lg 22, xl 32, 2xl 40, 3xl 48（21 個のシステムトークンから選抜）。 | タイポスケール | 設計方針 | https://designsystem.digital.gov/design-tokens/typesetting/font-size/ |
| U-5 | 色の「magic number」: 2 色の grade 差 40 以上で WCAG AA 大文字、50 以上で AA（通常文字）、70 以上で AAA。grade 50 の色は白・黒どちらに対しても AA [WCAG]。 | カラートークン（コントラスト） | WCAG 2.0 | https://designsystem.digital.gov/design-tokens/color/overview/ |
| U-6 | フォームは 1 列縦並び（「limited vision that makes it hard to scan from right to left」）。必須は赤い * と説明文、任意は「(optional)」。1 項目だけのフォームは印不要。HTML 順と表示順を一致させ CSS で並べ替えない。disabled は低コントラストで SR にも情報がないので避け、必要なら `aria-disabled`。 | フォーム | 設計方針（a11y 理由） | https://designsystem.digital.gov/components/form/ |
| U-7 | テーブルは `<caption>` 必須、`scope` 指定、狭幅では `usa-table--stacked` で縦積み（`data-label` で列名を補う）。ソート列は `aria-sort`。 | テーブル（レスポンシブ） | 設計方針 | https://designsystem.digital.gov/components/table/ |
| U-8 | サイト全体の告知は site alert: ページ最上部・全幅、info と emergency の 2 種。`aria-label` を付け、動的更新は緊急なら `role="alert"`、助言なら `role="status"`。「Don't visually hide alert messages」。 | お知らせバー | 設計方針 | https://designsystem.digital.gov/components/site-alert/ |
| U-9 | カードは関連コンテンツ集合の要約に使い、各カードは詳細へリンクする（「Make cards actionable」）。テーブル行の代わりに使わない。順に読ませる内容ならリストにする。見出しレベルはページのアウトラインに合わせる。カード群は `ul/li`。 | カード | 設計方針・WCAG 2.1 AA テスト結果あり | https://designsystem.digital.gov/components/card/ |
| U-10 | ヘッダーナビは需要の高いリンクほど左、組織図ではなくタスク順。ホバーのみで開くドロップダウン禁止（クリック／キーボードで展開）。現在セクションを `usa-current` で強調。skip link をヘッダー前に置く。nav が複数なら `aria-label`。 | ナビゲーション | 設計方針 | https://designsystem.digital.gov/components/header/ |
| U-11 | アコーディオンは「users will only need a few specific pieces of content」の場合のみ。大半の情報が必要なら通常の本文に。「Accordions increase cognitive load」。ヘッダー全体をクリック可能に。`aria-expanded` で状態を示す。 | アコーディオン | 設計方針・WCAG 2.1 AA テスト（21/23 pass） | https://designsystem.digital.gov/components/accordion/ |

## 3. Shopify Polaris（GitHub 上の文書）

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| P-1 | 「Don't use more than one primary button in a section」。赤ボタンは取り消し困難な操作だけ、破壊操作は確認ダイアログ。「Too many calls to action can cause confusion」。 | ボタン（階層） | 設計方針 | https://raw.githubusercontent.com/Shopify/polaris/main/polaris.shopify.com/content/components/actions/button.mdx |
| P-2 | ボタン文言は強い動詞で始め、sentence case、冠詞を省く（「Add menu item」）。「View」「Go」など冗長語を避ける。可視テキストを aria-label に含める（音声操作対応）。 | ボタン（文言・a11y） | 設計方針 | 同上 |
| P-3 | カードは「only one primary call to action per card」。見出しでカードの目的を明示。重要な次の操作はフッター、Edit など常設の任意操作は右上。 | カード | 設計方針 | https://raw.githubusercontent.com/Shopify/polaris/main/polaris.shopify.com/content/components/layout-and-structure/card.mdx |
| P-4 | バナーはページ全体向けならページヘッダー直下、セクション向けならセクション見出し直下。critical/warning は `role="alert"`、他は `role="status"`。重要情報を含まない限り閉じられるようにする。本文 1〜2 文。 | 通知バナー | 設計方針 | https://raw.githubusercontent.com/Shopify/polaris/main/polaris.shopify.com/content/components/feedback-indicators/banner.mdx |
| P-5 | スケルトンは「pages where all content loads at the same time」に使い、読み込み後のレイアウトを反映させる。読み込み後に変わる仮コンテンツは置かない（「jumpy loading experience」）。固定タイトルは実文字で表示。 | スケルトン／ローディング | 設計方針 | https://raw.githubusercontent.com/Shopify/polaris/main/polaris.shopify.com/content/components/feedback-indicators/skeleton-page.mdx |

## 4. Atlassian Design System

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| A-1 | spacing は 8px 基準。トークン名は基準に対する %（space.100=8px, 200=16px）。値: 0,2,4,6,8,12,16,20,24,32,40,48,64,80px。 | spacing scale | 設計方針 | https://atlassian.design/foundations/spacing |
| A-2 | 見出しトークン: xxlarge 32/36px, xlarge 28/32, large 24/28, medium 20/24, small 16/20, xsmall 14/20, xxsmall 12/16（font/line-height）。本文: large 16/24, 既定 14/20, small 12/16。「XXL and XL are suitable for brand and marketing content」。 | 見出し・タイポスケール | 設計方針 | https://atlassian.design/foundations/typography |

（Button usage ページは本文取得不可のため未収録。）

## 5. IBM Carbon

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| C-1 | spacing-01〜13: 2,4,8,12,16,24,32,40,48,64,80,96,160px。「using multiples of two, four, and eight」。 | spacing scale | 設計方針 | https://carbondesignsystem.com/elements/spacing/overview/ （本文取得元: raw.githubusercontent.com/carbon-design-system/carbon-website/main/src/pages/elements/spacing/overview.mdx） |
| C-2 | motion duration トークン: fast-01 70ms（ボタン・トグル）、fast-02 110ms（フェード）、moderate-01 150ms（小さな展開）、moderate-02 240ms（展開・トースト）、slow-01 400ms（大きな展開・重要通知）、slow-02 700ms（背景の暗転）。productive standard easing `cubic-bezier(0.2, 0, 0.38, 0.9)`、expressive `cubic-bezier(0.4, 0.14, 0.3, 1)`。「Make sure there is always a way to communicate similar messages statically.」 | モーション（duration・reduced motion） | 設計方針 | https://carbondesignsystem.com/elements/motion/overview/ （取得元: 同 GitHub motion/overview.mdx） |
| C-3 | primary ボタンは「only appear once per screen」（ヘッダー・モーダル・サイドパネルを除く）。secondary は primary と対で Cancel/Back 用、単独で使わない。ラベルは {verb}+{noun}、左揃え。 | ボタン（階層・文言） | 設計方針 | https://carbondesignsystem.com/components/button/usage/ （取得元: 同 GitHub components/button/usage.mdx） |
| C-4 | Large (expressive) ボタンは「16px body copy」と組み合わせ IBM.com のバナーで使用。全幅ボタンは 320px で頭打ち。 | ボタン（サイズ） | 設計方針 | 同上 |
| C-5 | 「Use a loading indicator if the expected wait time exceeds three seconds.」段階的に出る内容や全画面ロードはスピナーでなく skeleton。複数のローディング表示を同時に出さない。 | スケルトン／ローディング | 設計方針 | https://carbondesignsystem.com/components/loading/usage/ （取得元: 同 GitHub components/loading/usage.mdx） |
| C-6 | productive type set は基準 14px、expressive（Web ページ向け）は 16px。Web ページ見出しは fluid（ビューポートで段階的に変化）。 | タイポスケール | 設計方針 | https://carbondesignsystem.com/elements/typography/type-sets/ |

## 6. Material Design（Material Components 文書経由）

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| M-1 | 「Material Design spec states that touch targets should be at least 48 x 48 px.」見た目が 48x36px でも padding でタッチ領域を 48x48 にする。 | ボタン・リンク（最小ターゲット） | Material spec | https://github.com/material-components/material-components-web/blob/master/packages/mdc-touch-target/README.md |
| M-2 | ボタン強調の階梯: filled（「important, final actions that complete a flow, like Save, Join now, or Confirm」）> filled tonal > outlined（「important, but aren't the primary action」）> text（「lowest priority actions」）。 | ボタン（階層） | 設計方針 | https://github.com/material-components/material-web/blob/main/docs/components/button.md |
| M-3 | フォーカスリングは `:focus-visible` と同じ判定（キーボード操作時のみ表示）。既定幅 3px。 | フォーカス状態 | 設計方針 | https://github.com/material-components/material-web/blob/main/docs/components/focus-ring.md |
| M-4 | （M2 motion speed）小さなアニメーション 100ms、中 200–300ms、大 300ms 以上、複雑なもの 500ms、モバイルの出入り 200–300ms。 | モーション（duration） | 設計方針 | https://m2.material.io/design/motion/speed.html |

（M3 サイトの duration トークン値・48dp 記載ページは JS レンダリングのため本文未取得。）

## 7. Apple Human Interface Guidelines（Web に転用可能な部分）

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| H-1 | 「a button needs a hit region of at least 44x44 pt … to ensure that people can select it easily」。目立つボタンは 1 ビューに 1〜2 個（「Presenting too many prominent buttons increases cognitive load」）。破壊的操作に primary role を与えない。カスタムボタンには必ず押下状態を用意。 | ボタン（最小ターゲット・階層・状態） | 設計方針 | https://developer.apple.com/design/human-interface-guidelines/buttons |
| H-2 | コントラスト（WCAG AA 準拠表）: 17pt までのテキスト 4.5:1、18pt 以上 3:1、太字 3:1 [WCAG]。テキストは 200% まで拡大可能に。要素間の余白は bezel あり約 12pt、なし約 24pt。 | カラートークン・タイポ・spacing | WCAG AA | https://developer.apple.com/design/human-interface-guidelines/accessibility |
| H-3 | 「Don't add motion for the sake of adding motion」。モーションを情報伝達の唯一手段にしない。Reduce Motion 時は x/y/z の移動をフェードに置換、ブラーのアニメーションを避ける。アニメーション完了を待たせない（キャンセル可能に）。 | モーション（reduced motion） | 設計方針 | https://developer.apple.com/design/human-interface-guidelines/motion ／ 同 accessibility |
| H-4 | ボタン文言は動詞で始める（「Add to Cart」）。処理中は「Checking out…」のように状態を文言で示す。 | ボタン（文言） | 設計方針 | https://developer.apple.com/design/human-interface-guidelines/buttons |

## 8. WordPress core / theme.json 規約

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| W-1 | 既定の spacingScale は operator `*`、increment 1.5、steps 7、mediumStep 1.5、unit rem。プリセットは `--wp--preset--spacing--{step}`（10 刻み、mediumStep は常に 50、下限 10）。 | spacing scale（theme.json） | コア仕様 | https://developer.wordpress.org/themes/global-settings-and-styles/settings/spacing/ |
| W-2 | 既定 font size スラッグは small/medium/large/x-large、CSS 変数 `--wp--preset--font-size--{slug}`、クラス `.has-{slug}-font-size`。`typography.fluid: true` で `clamp()` による流体サイズ（プリセットごとに min/max 上書き可）。 | タイポスケール（theme.json） | コア仕様 | https://developer.wordpress.org/themes/global-settings-and-styles/settings/typography/ |
| W-3 | カラースラッグ `base`（背景）と `contrast`（文字）は「de facto standards」。変数は `--wp--preset--color--{slug}`。テーマは自前パレットを登録するのが常。`defaultPalette:false` でもコアの変数は生成される。 | カラートークン（theme.json） | コア慣行 | https://developer.wordpress.org/themes/global-settings-and-styles/settings/color/ |
| W-4 | `layout.contentSize` は本文幅（「45-75 characters of text per line」を目安、例 40rem）、`wideSize` はそれより大きく（例 64rem）、wide 配置の上限になる。 | レイアウト（本文幅・wide） | コア仕様＋可読性目安 | https://developer.wordpress.org/themes/global-settings-and-styles/settings/layout/ |
| W-5 | accessibility-ready テーマ要件: 意味のある見出し構造、skip to content link、キーボード操作、hover/focus 内容へのアクセス、十分なコントラスト、本文中リンクの下線、ラベル付きフォーム、ランドマーク。 | 全部品（a11y 基準） | WP テーマレビュー要件 | https://make.wordpress.org/themes/handbook/review/accessibility/required/ |

（Gutenberg の design-resources ページは Figma ツール案内のみで、部品規則の記載なし。）

## 9. Bootstrap / Tailwind

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| B-1 | ブレークポイント sm 576 / md 768 / lg 992 / xl 1200 / xxl 1400px、`min-width` によるモバイルファースト（「apply the bare minimum of styles … at the smallest breakpoint, and then layers on styles」）。 | レスポンシブ全般 | 設計方針 | https://getbootstrap.com/docs/5.3/layout/breakpoints/ |
| B-2 | バリデーション文は `.invalid-feedback` を入力直後に置き、`aria-describedby` で結び付ける。自身で「client-side custom validation styles and tooltips are not accessible」と明記しているので、色だけでなくテキストで示す。 | フォーム（エラー） | 設計方針（限界の自己申告） | https://getbootstrap.com/docs/5.3/forms/validation/ |
| T-1 | spacing 基準 `--spacing: 0.25rem`（4px）の倍数。文字: xs 0.75rem, sm 0.875, base 1, lg 1.125, xl 1.25rem（行高は base で 1.5）。ブレークポイント sm 40rem(640px) / md 48rem / lg 64rem / xl 80rem / 2xl 96rem。 | spacing・タイポ・ブレークポイント | 設計方針 | https://tailwindcss.com/docs/theme |

## 10. GoodUI（A/B テスト集）

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| GU-1 | サイト全体で「141 patterns based on 639 tests」（166 勝・284 有意でない正・146 有意でない負・43 負）。個別パターンは勝率を見て採否を決める必要がある。 | 全般（採用判断の前提） | A/B テスト集計 [研究] | https://goodui.org/patterns/ |
| GU-2 | Pattern #41 Sticky Call To Action（モバイルの追従 CTA）は複数テスト（#653, #656, #657, #665）で検証。#1 One Column Layout、#13 Fewer Form Fields、#18 Benefit Buttons、#50 Reassurances も収録。 | CTA・フォーム・LP 構成 | A/B テスト [研究] | https://goodui.org/ |

## 11. web.dev / WCAG

| # | ルール | 適用部位 | 根拠の種類 | 出典 |
|---|---|---|---|---|
| WD-1 | 「A minimum recommended touch target size is around 48 device independent pixels」、ターゲット間は約 8px 離す。 | ボタン・リンク（最小ターゲット） | ベストプラクティス | https://web.dev/articles/accessible-tap-targets |
| WD-2 | `@media (prefers-reduced-motion: reduce)` で装飾アニメーションを止め、機能的フィードバックは残す。アニメーション CSS を別ファイルにして `media` 属性で条件読み込みする案。前庭障害でめまい・吐き気が起き得る。 | モーション（reduced motion） | a11y 理由 | https://web.dev/articles/prefers-reduced-motion |
| WD-3 | line-height は単位なし（例 1.5）。行長は Bringhurst 引用で 45–75 字、66 字が理想、`max-inline-size: 66ch`。流体サイズは `clamp()`。 | タイポ（本文・行長） | タイポグラフィ文献引用 | https://web.dev/learn/design/typography |
| WD-4 | ローディング中は領域に `aria-busy` を切替え、progress と領域を `aria-describedby` で結ぶ。 | スケルトン／ローディング | a11y | https://web.dev/articles/building/a-loading-bar-component |
| WD-5 | ダークモードはトークンを `--brand-light/--brand-dark` などスキーム別に持ち、`prefers-color-scheme: dark` で汎用トークン（`--brand`, `--text1`, `--surface1`）を差し替える。明度差 40–50% でコントラストを確保。 | カラートークン（テーマ切替） | 設計方針 | https://web.dev/articles/building/a-color-scheme |
| WC-1 | WCAG 2.2 SC 2.5.8 Target Size (Minimum) AA: 「at least 24 by 24 CSS pixels」（spacing／inline 等の例外あり）。SC 2.5.5 Enhanced AAA: 「at least 44 by 44 CSS pixels」。 | ボタン・リンク（最小ターゲットの標準根拠） | WCAG 2.2 [WCAG] | https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html ／ https://www.w3.org/WAI/WCAG22/Understanding/target-size-enhanced.html |

---

## 横断まとめ（テーマ部位別に見た各システムの一致点）

- **最小ターゲット**: WCAG 最低 24px（AA）／44px（AAA）、Apple 44pt、Material・web.dev 48px。→ テーマのボタン・ナビリンクは 44〜48px 高を基準に、根拠を WCAG 2.5.5／Material で示せる。
- **primary は 1 つ**: GOV.UK・Polaris（セクションごと）・Carbon（画面ごと）・Apple（1〜2 個）が一致。secondary は primary と対で使う（Carbon）。
- **ボタン文言**: 動詞始まり＋sentence case（GOV.UK, USWDS, Polaris, Carbon, Apple）。冠詞省略（Polaris）。
- **disabled 回避**: GOV.UK・USWDS が低コントラストと SR 情報欠如を理由に原則回避。
- **spacing**: 8px 系（USWDS, Atlassian, Carbon）か 4px 系（Tailwind）。GOV.UK は 5px 系＋レスポンシブ切替。WP theme.json は 1.5 倍の幾何級数が既定。
- **アコーディオン**: GOV.UK・USWDS ともに「必要なユーザーが多い内容は隠さない」「証跡がある場合のみ」。
- **通知バナー**: GOV.UK は「people often miss them」の証跡から控えめ運用。Polaris/USWDS は role=alert/status を重要度で使い分け。
- **Cookie バナー**: GOV.UK のみ具体ルール（fixed 禁止＝WCAG 2.4.11、同意前に非必須 cookie を置かない、1 年保存）。
- **モーション**: Carbon が唯一 ms トークンを公開（70〜700ms）。Material M2 は 100〜500ms の幅。Apple・web.dev・Carbon が「静的な代替手段」「reduced motion」を必須化。
- **フォーカス**: GOV.UK は 2 色（黄＋黒）で WCAG 1.4.11、Material は `:focus-visible` 判定・3px。
- **フォーム**: 1 列（USWDS）、固定幅を入力長に合わせる・placeholder をラベルにしない（GOV.UK）、エラーは入力直後＋上部要約（GOV.UK）、`aria-describedby`（Bootstrap）。
- **テーブル**: caption・scope（GOV.UK, USWDS）、狭幅は縦積み `data-label`（USWDS）。
- **スケルトン**: 3 秒超で表示（Carbon）、レイアウトを写す・変わる仮文を置かない（Polaris）。

## 未取得・要追加確認

- Material Design 3 の duration トークン値（short/medium/long/extra-long）と 48dp の M3 原文。
- Atlassian Button usage（appearance 階層）。
- Polaris Motion トークン。
- WordPress Accessibility Handbook の色コントラスト数値ページ（URL 変更の可能性）。
- GOV.UK ナビゲーションの研究本文（GitHub discussion に分散）。
