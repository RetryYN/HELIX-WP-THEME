# 標準・計測証跡集: モーション / Core Web Vitals / WCAG 2.2 / 日本語タイポグラフィ / 画像

作成: 2026-09-05（Web リサーチ、一次資料を WebFetch で確認したもののみ記載）
証拠強度: **A** = 規格本文・一次 A/B 計測・査読論文 / **B** = ベンダー公式ガイド・実サイト計測（統制なし）/ **C** = 業界記事・二次情報・未検証

読み方: 各項目は「主張 / 強度 / 出典 / 適用パーツ / 具体ルール」。数値は出典に書かれたものだけを転記し、推定値は入れていない。

---

## 1. モーション・アニメーション

### F-01 「操作に伴うモーションを無効化できること」は WCAG 2.2 SC 2.3.3（AAA）
- 主張: 操作（スクロール等）で起動するモーションアニメーションは、機能・情報に必須でない限り無効化できなければならない。意図は前庭障害のあるユーザーの「めまい・吐き気・頭痛」防止で、Understanding には回復に「bed rest」を要する例も記載。パララックス（スクロールで装飾要素が横方向に出入り）が明示例。
- 強度: A（規格本文）
- 出典: "Understanding SC 2.3.3: Animation from Interactions", W3C WAI, WCAG 2.2 (2023) — https://www.w3.org/WAI/WCAG22/Understanding/animation-from-interactions.html
- 適用: hero パララックス、scroll-reveal、ホバー拡大、スムーススクロール
- ルール: AAA だが低コストで満たせる。技術 C39 = `@media (prefers-reduced-motion: reduce)` で transform/opacity 系モーションを止める（fade は残してよい）。

### F-02 自動で 5 秒を超えて動くものには一時停止・停止・非表示の手段が必須（SC 2.2.2, A）
- 主張: 「自動開始・5 秒超・他コンテンツと並行」の動き（動画、アニメ、スクロールティッカー等）には pause/stop/hide の手段が要る。自動更新（カルーセルの自動送り含む）には 5 秒例外がない。
- 強度: A（規格本文）
- 出典: "Understanding SC 2.2.2: Pause, Stop, Hide", W3C WAI, WCAG 2.2 — https://www.w3.org/WAI/WCAG22/Understanding/pause-stop-hide.html
- 適用: hero 背景動画、自動カルーセル、ループ GIF/Lottie、マーキー
- ルール: 背景動画・自動カルーセルには可視の一時停止ボタン（24×24 CSS px 以上、F-17 参照）を標準装備。ループ演出は 5 秒以内で止めるか制御を付ける。

### F-03 前庭機能障害は稀ではない（40 歳以上の米国成人の最大 35%）
- 主張: 大規模疫学調査で「40 歳以上の米国成人の最大 35%（約 6,900 万人）」が何らかの前庭機能障害を経験。NIDCD は成人の 4% が慢性的な平衡障害、65 歳以上の 80% がめまいを経験と報告。
- 強度: A（査読論文: Agrawal Y. et al., *Arch Intern Med* 2009;169(10):938-944）を B（VeDA の要約ページ）経由で確認
- 出典: "About Vestibular Disorders", Vestibular Disorders Association (VeDA) — https://vestibular.org/article/what-is-vestibular/about-vestibular-disorders/
- 適用: モーション方針全体の根拠
- ルール: 「モーション弱者は少数」という前提を取らない。年齢層が高い B2B/地域サービスのサイトほど reduced-motion 既定を厳しく。

### F-04 prefers-reduced-motion の実装パターン（web.dev）
- 主張: OS の「視差効果を減らす / Reduce motion」を CSS メディアクエリで検出できる。推奨は `@media (prefers-reduced-motion: reduce)` でアニメを止める、または `<link rel=stylesheet media="(prefers-reduced-motion: no-preference)">` でアニメ CSS 自体を条件読込。
- 強度: B（ベンダー公式ガイド）。**ユーザー側の有効化率は、web.dev・MDN・W3C いずれにも数値なし。信頼できる有効化率の一次データは今回見つからなかった（未確認のまま数値を書かない）。**
- 出典: "prefers-reduced-motion: Sometimes less movement is more", Thomas Steiner, web.dev (2019, 更新あり) — https://web.dev/articles/prefers-reduced-motion
- 適用: テーマの `motion` トークン層
- ルール: テーマ全体のアニメ用 CSS を 1 ファイルに分離し、`no-preference` 条件で読み込む（reduce 時は初期状態で表示 = scroll-reveal が「隠れたまま」にならない設計）。

### F-05 Material Design のモーション尺（mobile 300ms 基準、desktop 150–200ms）
- 主張: モバイル標準遷移 300ms、大規模/全画面 375ms、入場 225ms、退場 195ms、「400ms を超えると遅く感じる」。タブレットは +30%、ウェアラブルは −30%、デスクトップは 150–200ms（画面が大きく動きが速く見えるため短くする）。
- 強度: B（設計システム公式）
- 出典: "Duration & easing – Motion – Material Design (M1)", Google — https://m1.material.io/motion/duration-easing.html
- 適用: ボタン・アコーディオン・ドロワー・タブ切替
- ルール: テーマ既定 `--motion-duration-sm: 150ms / -md: 250ms / -lg: 375ms` 程度に収め、400ms 超は hero 演出以外で禁止。デスクトップは短めのトークンを使う。

### F-06 IBM Carbon のモーション尺（70 / 110 / 150 / 240 / 400 / 700ms）
- 主張: `fast-01` 70ms（ボタン・トグル）、`fast-02` 110ms（フェード）、`moderate-01` 150ms（小さな展開・短距離移動）、`moderate-02` 240ms（展開・トースト）、`slow-01` 400ms（大きな展開・重要通知）、`slow-02` 700ms（背景の暗転）。productive（控えめ・作業中）と expressive（強調・ページ開始や主要アクション）の 2 スタイル。
- 強度: B
- 出典: "Motion – Carbon Design System (v10)", IBM — https://v10.carbondesignsystem.com/guidelines/motion/overview/
- 適用: マイクロインタラクション全般、モーダル背景
- ルール: 非線形スケール（距離が倍でも時間は倍にしない）。マーケ用 hero の「expressive」は 1 ページ 1 箇所に限定し、他は productive。

### F-07 Apple HIG: モーションは任意・待たせない・Reduce Motion を尊重
- 主張: Apple HIG Motion は「モーションは補助であり必須にしない」「人を待たせない」「Reduce Motion 設定時は代替（クロスフェード等）にする」を原則とする。
- 強度: C（ページ本文を取得できず要旨のみ。ms の数値はページに無い前提で記載しない）
- 出典: "Motion", Apple Human Interface Guidelines — https://developer.apple.com/design/human-interface-guidelines/motion
- 適用: iOS Safari 比率の高い日本の SP 主戦場
- ルール: reduce 時はスライド/ズーム→クロスフェードへ置換（消すのではなく置換）。

### F-08 アニメは transform / opacity（コンポジタ専用）に限定する
- 主張: transform・opacity はコンポジタスレッドで処理でき、width/height/top/left などはレイアウト→ペイントを誘発して高コスト。`will-change` は 200ms 以内に動く可能性がある要素だけに付け、乱用しない。
- 強度: B
- 出典: "Animations and performance", web.dev — https://web.dev/articles/animations-and-performance
- 適用: scroll-reveal、ホバー、パララックス実装
- ルール: テーマ CSS で `transition-property` を transform/opacity（必要なら filter/clip-path）に限定。`top/left/margin/height` のアニメを lint で禁止。

### F-09 レイアウトを動かすアニメは CLS に計上される
- 主張: CLS の主因は「寸法なし画像」「広告/埋め込み」「動的挿入」「Web フォント swap」「レイアウトを変える CSS アニメ」。`translate` によるコンポジット済みアニメは他要素に影響せず CLS を生まない。
- 強度: B（Chrome チーム公式）
- 出典: "Optimize Cumulative Layout Shift", web.dev — https://web.dev/articles/optimize-cls
- 適用: scroll-reveal（高さ 0→auto 等）、遅延バナー、フォント
- ルール: reveal は「要素は最初から場所を確保し opacity/transform で現す」。`height: 0 → auto` 型は禁止。

### F-10 スクロール連動アニメは CSS Scroll-driven Animations でメインスレッドから外す
- 主張: Chrome の事例で、scroll イベント + JS の実装は重い JS 処理（100ms 毎に 10 億ループ）を入れると「janky and sluggish」になったが、CSS `animation-timeline: scroll()` 版は「完全に影響を受けない」。定量的な before/after は非掲載。
- 強度: B（実演比較、数値なし）
- 出典: "A case study on scroll-driven animations performance", Yuriko Hirota, Chrome for Developers (2023-07-12) — https://developer.chrome.com/blog/scroll-animation-performance-case-study
- 適用: 進捗バー、scroll-reveal、パララックス
- ルール: パララックス/reveal は CSS scroll-driven（未対応ブラウザは静的表示にフォールバック）を第一候補。JS scroll リスナー + rAF 実装は禁止か例外扱い。

### F-11 自動回転カルーセルは 1 枚目に集中し、クリック率は 1% 前後
- 主張: Notre Dame 大の計測（2012-10〜2013-01）で ND.edu の hero カルーセルをクリックした訪問者は約 1%、うち 84% が 1 枚目。静的型でも 1.7–2.3%、自動送り型で 8.8%（サイト差あり）。
- 強度: B（実サイトログ、統制なし）
- 出典: "Carousel Interaction Stats", Erik Runyon (2013) — https://erikrunyon.com/2013/01/carousel-interaction-stats/
- 適用: hero カルーセル / スライダー
- ルール: hero は 1 メッセージ固定を既定。カルーセルを使う場合は 1 枚目に最重要訴求を置く。

### F-12 Baymard: EC の 1/3 がカルーセルを使い、46% に使い勝手の問題
- 主張: 自動回転は「読む前に切り替わる」「クリックしようとした瞬間に動く」「コントロールが小さい/見つからない」「モバイルで誤操作」を起こす。推奨はデスクトップ 5–10 秒間隔・ホバーで停止・操作後は回転停止、モバイルは自動回転をしない・スワイプ対応・テキストは画像化しない。
- 強度: B（大規模 UX テストの要約記事）
- 出典: "Homepage Carousels: 46% of Sites Have Usability Issues", Baymard Institute (2019, 更新 2025) — https://baymard.com/blog/homepage-carousel
- 適用: hero カルーセル、実績ロゴスライダー
- ルール: SP では自動回転オフ。PC でも 5 秒未満の自動送りを禁止し、hover/focus で停止。

### F-13 hero の動画は LCP 要素になり得る（poster または最初のフレームで計測）
- 主張: LCP 対象は `<img>`、`<svg>` 内 `<image>`、`<video>`（poster 読込か最初のフレーム表示の早い方）、CSS `url()` 背景、テキストブロック。opacity 0、ビューポート全面を覆う要素、低エントロピー画像は除外される。margin/padding は面積に含まれない。
- 強度: B（Chrome 公式仕様解説）
- 出典: "Largest Contentful Paint (LCP)", web.dev — https://web.dev/articles/lcp
- 適用: hero 背景動画、hero 画像、WebGL キャンバス
- ルール: 動画 hero は軽量 poster を `<video poster>` で必ず指定し `preload="none"`＋`fetchpriority`/`preload` は poster 側に付ける。`<canvas>`(WebGL) は LCP 候補にならないため、見出しテキストか静止画が LCP になるよう最初に描く。

### F-14 hero 動画は「autoplay が他リソースより優先されて LCP を悪化させる」（業界記事）
- 主張: 複数の実装記事が「`<video autoplay>` は他の重要リソースより先に動画をダウンロードする」「poster 付き native `<video>` + `preload='none'` が JS プレイヤーより速い」と述べる。定量データは各記事の自社計測で一般化不可。
- 強度: C
- 出典: "How to Lazy Load and Autoplay Videos Without Killing Your Core Web Vitals", Cloudinary Blog — https://cloudinary.com/blog/lazy-load-autoplay-videos-core-web-vitals ; "Improve your LCP with Liquid's new video_tag lazy loading option", Shopify Performance — https://performance.shopify.com/blogs/blog/liquid-video_tag-lazy-loading
- 適用: hero 背景動画
- ルール: 動画 hero を採用する場合は同一 LP で「静止画 hero」との A/B を必須にし、LCP p75 と CVR を証跡化してから既定化する（この分野に一次 A/B 証跡がないため）。

---

## 2. Core Web Vitals と事業インパクト

### F-15 CWV しきい値: LCP ≤ 2.5s / INP ≤ 200ms / CLS ≤ 0.1 を 75 パーセンタイルで
- 主張: 「良好」は LCP 2.5 秒以内、INP 200ms 以下、CLS 0.1 以下。モバイル/デスクトップを分けて 75 パーセンタイルで満たすことが合格基準。
- 強度: A（Google 公式定義）
- 出典: "Web Vitals", web.dev — https://web.dev/articles/vitals
- 適用: テーマの性能ゲート
- ルール: CI の Lighthouse ではなく CrUX/RUM の p75 を判定に使う。テーマ既定テンプレで LCP 2.5s/INP 200ms/CLS 0.1 を SP で満たすことを出荷条件に。

### F-16 事業インパクト事例（Google 公式まとめ）
- 主張（すべて web.dev 記載値）:
  - （伏せ字）(伊): LCP 31% 改善 → 売上 +8%、lead/visit +15%、cart/visit +11%（A/B）
  - （伏せ字） ニュース: CLS 約 0.2 → 0 で PV/セッション +15.1%、滞在 +13.3%、直帰 −1.72pt（2021）
  - （伏せ字） 24: CWV 最適化で 訪問者あたり売上 +53.37%、CVR +33.13%、AOV +15.20%、離脱率 −35.12%（A/B、2022 公開）
  - （伏せ字） STYLE: LCP 18% 改善 → PV/セッション +9%
  - （伏せ字）: LCP 55% 改善 → 平均セッション時間 +23%
  - （伏せ字） マンガ: CLS 10 倍改善 → 読了数 2–3 倍
  - （伏せ字）: 3 指標改善 → Black Friday 売上 +6%
  - （伏せ字）: CLS 77% 削減 → 直帰 −8%
- 強度: A（（伏せ字）/（伏せ字） は A/B）、B（他は前後比較）
- 出典: "The business impact of Core Web Vitals", web.dev — https://web.dev/case-studies/vitals-business-impact ; "（伏せ字）: A 31% improvement in LCP increased sales by 8%" — https://web.dev/case-studies/vodafone ; "（伏せ字） News" — https://web.dev/case-studies/yahoo-japan-news ; "（伏せ字） 24" — https://web.dev/case-studies/rakuten
- 適用: 性能予算の根拠、PO 説明資料
- ルール: 日本事例（（伏せ字）, （伏せ字）, （伏せ字））は「CLS と LCP が PV/滞在に直結」を示す。テーマでは画像 `width/height`・`aspect-ratio` 予約を必須にして CLS を構造的に 0 に近づける。

### F-17 0.1 秒の SP 高速化で小売 CVR +8.4%（Deloitte × Google, 37 ブランド）
- 主張: 欧米 37 ブランド・4 週間の計測で、モバイル 0.1 秒改善あたり 小売 CVR +8.4%・AOV +9.2%、旅行 CVR +10.1%、ラグジュアリー PV/セッション +8.6%、リード獲得の直帰 −8.3%。
- 強度: B（相関分析、Google 委託）
- 出典: "Milliseconds Make Millions", Deloitte Digital (2020) — https://www.deloitte.com/ie/en/services/consulting/research/milliseconds-make-millions.html
- 適用: LP・お問い合わせ導線
- ルール: 「0.1 秒」を性能予算の粒度にする（ms 単位のレビュー）。

### F-18 モバイルで 3 秒超なら 53% が離脱（Google 2016）
- 主張: Think with Google "The Need for Mobile Speed"（2016-09）: 読み込みが 3 秒を超えると 53% のモバイル訪問者が離脱。3G 平均 19 秒、4G 14 秒。
- 強度: B（古い・手法詳細不明、二次記事経由で確認）
- 出典: "Google: 53% of mobile users abandon sites that take over 3 seconds to load", Marketing Dive (2016) — https://www.marketingdive.com/news/google-53-of-mobile-users-abandon-sites-that-take-over-3-seconds-to-load/426070/
- 適用: 説明資料のみ（2016 年データのため設計根拠には F-15/F-16 を優先）
- ルール: 引用時は年を明記。

### F-19 CSS scroll-driven で INP p75 −120ms（二次情報）
- 主張: ある業界記事が「JS ゼロの CSS scroll-driven animation に置換して p75 INP が 120ms 減った」と報告。出典の一次データは未確認。
- 強度: C
- 出典: "CSS Scroll-Driven Animations: Ditch Scroll JS", buildmvpfast.com (2026) — https://www.buildmvpfast.com/blog/css-scroll-driven-animations-replace-js-2026
- 適用: F-10 の補助
- ルール: 数値として採用しない。自テーマで前後計測して置き換える。

---

## 3. WCAG 2.2 AA（マーケティングテーマに直結する項目）と JIS X 8341-3

### F-20 SC 1.4.3 Contrast (Minimum) — 本文 4.5:1、大きな文字 3:1
- 主張: テキストと文字画像は 4.5:1 以上。大きな文字（18pt、または 14pt 太字。CJK は「同等サイズ」）は 3:1。ロゴ、非活性 UI、装飾は除外。14pt/18pt ≒ 18.5px/24px。
- 強度: A
- 出典: "Understanding SC 1.4.3: Contrast (Minimum)", W3C WAI — https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html
- 適用: 全テキスト、hero の画像上テキスト、薄いグレー本文
- ルール: 本文・キャプションは 4.5:1、24px 以上（太字 18.5px 以上）の見出しのみ 3:1 許容。画像上テキストはオーバーレイで担保。CJK の「同等サイズ」は規格上あいまいなので、テーマでは px 基準（24px / 太字 18.5px）で運用する。

### F-21 SC 1.4.11 Non-text Contrast — UI 部品・アイコンは隣接色と 3:1
- 主張: UI コンポーネントの識別に必要な視覚情報（枠線、フォーカスリング、アイコン、チャートの要部）は隣接色と 3:1 以上。2.999:1 は不合格（丸めない）。非活性部品・ブラウザ既定・写真/ロゴは除外。
- 強度: A
- 出典: "Understanding SC 1.4.11: Non-text Contrast", W3C WAI — https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html
- 適用: ゴーストボタン、入力欄枠線、ハンバーガー、ページネーション、SVG アイコン
- ルール: 入力枠線・アイコン・フォーカスリングのトークンは背景に対して 3:1 を CI で検査。

### F-22 SC 1.4.10 Reflow — 幅 320 CSS px で二次元スクロールなし
- 主張: 縦スクロールコンテンツは幅 320 CSS px（=1280px の 400% ズーム相当）で情報・機能を失わず横スクロール不要。表・地図・図・動画は例外だが、見出し・検索・ページネーションなど周辺要素は例外にならない。
- 強度: A
- 出典: "Understanding SC 1.4.10: Reflow", W3C WAI — https://www.w3.org/WAI/WCAG22/Understanding/reflow.html
- 適用: 全レイアウト、料金表、比較表、横スクロール実績ロゴ
- ルール: 320px で全テンプレをスクリーンショット検査。料金表は行→カード変換、または表だけ `overflow-x:auto`。

### F-23 SC 1.4.12 Text Spacing — 行間 1.5、段落 2 倍、字間 0.12em、語間 0.16em を上書きされても壊れない
- 主張: ユーザーが行間 1.5×、段落後 2×、字間 0.12em、語間 0.16em を適用しても内容・機能が失われないこと（初期値を強制するのではない）。
- 強度: A
- 出典: "Understanding SC 1.4.12: Text Spacing", W3C WAI — https://www.w3.org/WAI/WCAG22/Understanding/text-spacing.html
- 適用: 固定高さのカード、ボタン、hero キャッチ、`white-space:nowrap`、`overflow:hidden`
- ルール: テキストを含む箱の `height` を固定しない（`min-height`）。ブックマークレットで上書きテストを出荷前に実施。

### F-24 SC 2.5.8 Target Size (Minimum) — ポインタ対象は 24×24 CSS px
- 主張: 対象は最小 24×24 CSS px。例外: 24px 円が隣接対象と重ならない間隔、同一機能の代替、文中インライン、UA 既定、必須。
- 強度: A（WCAG 2.2 新規 AA）
- 出典: "Understanding SC 2.5.8: Target Size (Minimum)", W3C WAI — https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html
- 適用: カルーセルのドット、閉じるボタン、SNS アイコン、パンくず、ページネーション、フッターリンク群
- ルール: テーマのアイコンボタン最小 24px（推奨 44px）、ドットナビは hit area を拡張。

### F-25 SC 2.5.7 Dragging Movements — ドラッグ必須の操作を作らない
- 主張: ドラッグで動く機能（スライダー、ドラッグ式カルーセル、地図パン）は、ドラッグ以外の単一ポインタ操作でも達成できること。
- 強度: A（WCAG 2.2 新規 AA）
- 出典: "WCAG 2.2", W3C — https://www.w3.org/TR/WCAG22/ ; 解説: "What's new in WCAG 2.2", TetraLogical (2023) — https://tetralogical.com/blog/2023/10/05/whats-new-wcag-2.2/
- 適用: スワイプカルーセル、Before/After スライダー、料金レンジスライダー
- ルール: スワイプ式にも前後ボタンを必ず併設。Before/After には数値入力またはボタン。

### F-26 フォーカス: 2.4.7 Focus Visible（AA）+ 2.4.11 Focus Not Obscured (Minimum)（AA、2.2 新規）、2.4.13 Focus Appearance は AAA
- 主張: フォーカスは可視であること（2.4.7）、固定ヘッダー/クッキーバナー等の作者コンテンツにフォーカス要素が完全に隠れないこと（2.4.11）。2.4.13（2px 相当の周囲面積・3:1 変化）は AAA だが推奨基準として明確。
- 強度: A
- 出典: "Understanding SC 2.4.13: Focus Appearance" — https://www.w3.org/WAI/WCAG22/Understanding/focus-appearance.html ; "WCAG 2.2 Updates", Deque University — https://dequeuniversity.com/resources/wcag-2.2/
- 適用: sticky ヘッダー、固定 CTA バー、Cookie バナー、`outline:none` の慣習
- ルール: `:focus-visible` で 2px 以上・3:1 のリングを既定化。sticky 要素には `scroll-padding-top/bottom` を設定し、フォーカス要素が隠れないようにする。

### F-27 JIS X 8341-3:2016 は WCAG 2.0（ISO/IEC 40500:2012）と技術的に同一。WCAG 2.2 対応 JIS はまだ無い
- 主張: JIS X 8341-3:2016 は ISO/IEC 40500:2012 の一致規格で、WCAG 2.0 と同内容。WCAG 2.2 は ISO/IEC 化を経ないと JIS 化されず、現時点で WCAG 2.2 準拠の JIS は存在しない。
- 強度: A（WAIC / 業界解説一致）
- 出典: "アクセシビリティとは", ウェブアクセシビリティ基盤委員会 (WAIC) — https://waic.jp/knowledge/accessibility/ ; "JIS X 8341-3:2016 と WCAG 2.2 のどちらではじめるべきか", バーンワークス — https://burnworks.com/news/article/489/
- 適用: 準拠表明の文言
- ルール: 対外表記は「JIS X 8341-3:2016（WCAG 2.0）AA 準拠 + WCAG 2.2 AA の追加項目（2.4.11/2.5.7/2.5.8/3.2.6/3.3.7/3.3.8）に配慮」と二段で書く。WCAG 2.2 の AA 項目は 2.0 の上位互換なので、2.2 AA を満たせば JIS AA も満たす。

### F-28 実態: 100 万ホームページの 83.9% に低コントラスト、53.1% に alt 欠落（WebAIM Million 2026）
- 主張: 2026 年 2 月の 100 万 HP 自動検査で、平均 56.1 エラー/ページ。低コントラスト 83.9%、alt 欠落 53.1%、フォームラベル欠落 51%、空リンク 46.3%、空ボタン 30.6%。6 カテゴリでエラーの 96%。
- 強度: B（自動検査、大規模）
- 出典: "The WebAIM Million", WebAIM (2026) — https://webaim.org/projects/million/
- 適用: テーマの lint 優先順位
- ルール: コントラスト・alt・ラベル・空リンク/ボタンの 5 点を自動検査に組み込むだけで大半の失敗を防げる。

---

## 4. 日本語スクリーンタイポグラフィ

### F-29 （伏せ字）: 17px × 行間 1.6 を中心に「16px×1.8〜19px×1.4」が丁度良い、A/B で 10 秒以内離脱が最大 23% 減
- 主張: クラウドソーシング 1,080 名、文字 15–20px × 行間 1.2–2.0 の 30 条件（各 216 件）で 5 段階評価。17px×1.6 が最適域の中心。（伏せ字）ニュース SP 記事の A/B（既存 16px×1.5 vs 提案条件）で読了率は 1–3% 低下したが滞在は 5–10% 増、10 秒以内離脱は最大 23% 減。第 2 実験（17px × 行間 1.4–1.7）でリンク CTR・PV が有意増。
- 強度: A（大規模主観評価 + 実サイト A/B）
- 出典: "文字と行間の大きさは何が良い？読みやすさとKPI両立への挑戦", （伏せ字） Tech Blog (2023) — https://techblog.yahoo.co.jp/entry/2023052430423559/
- 適用: 本文（記事・サービス説明）、SP
- ルール: SP 本文既定 17px / line-height 1.6（許容 16px×1.8〜19px×1.4）。行間は文字が小さいほど広く、大きいほど狭く。

### F-30 行長: 1 行 20–29 文字が読み速度・眼球運動・主観の均衡点（山形大 小林研, IEICE 2016）
- 主張: 画面で行長 5〜40 文字を変えて読み速度と視線を計測。読速は 40 文字で最大だが 20 文字付近で上限に近づき、総合最適は 20–29 文字。
- 強度: A（査読論文、電子情報通信学会和文誌 J99-D no.1 pp.23–34）
- 出典: "読みやすい一行の長さは何文字だろうか", Kobayashi Lab, 山形大学 (2016) — https://www.kbys-lab.org/archives/1077
- 適用: 本文カラム幅、hero キャッチの折返し
- ルール: 本文の `max-width` は約 30〜40 文字幅（17px なら約 510–680px、JLReq の書籍値 40–50 字は上限側）。SP（幅 375px で 17px ≒ 20 文字）は下限に近く、17px 以上を保つと 20 文字前後になる。

### F-31 JLReq（JIS X 4051 準拠）: 行間は文字サイズの二分〜全角、書籍の行長は 40–50 字、行長は文字サイズの整数倍
- 主張: W3C「日本語組版処理の要件」は基本版面の行間を二分（0.5 文字）〜全角（1 文字）、書籍本文の行長を 40–50 字とし、行長を文字サイズの整数倍にすることを求める。
- 強度: A（W3C Note / JIS X 4051）
- 出典: "Requirements for Japanese Text Layout (jlreq)", W3C — https://www.w3.org/TR/jlreq/
- 適用: line-height トークン、カラム幅
- ルール: line-height 1.5–2.0（= 行間 0.5–1.0 文字）。F-29 の 1.6 はこの範囲内。行長は `ch`/`rem` の整数倍で設定し、行末の半端を減らす。

### F-32 印刷での日本語可読性: 12–20pt（視角 0.55–0.92°）で主観的読みやすさが最大、読速は 6pt 以上で頭打ち
- 主張: 40cm 視距離で 110 名/20 名/10 名の 3 実験。主観的読みやすさは 12pt 以上で安定し 12–20pt が最適、音読速度は 4→6pt で大きく増え 6–15pt では微増。
- 強度: A（査読論文、日本官能評価学会誌 2010;14(1-2):26-33）
- 出典: "文字の読みやすさ2：読みやすさと読みの速さの比較", 阿久津・近藤, J-STAGE (2010) — https://www.jstage.jst.go.jp/article/jjsse/14/1-2/14_26/_html/-char/ja
- 適用: 最小フォントサイズの根拠
- ルール: 「読める」と「読みやすい」は別。注記・キャプションでも「読みやすい」域（画面換算で本文 16px 以上）を既定にし、12px 台は法定表記など例外に限る。

### F-33 ゴシック体のウェイト: 線幅は文字サイズの 10–15%（正常視では 12–16%）で頭打ち、細すぎは小サイズで顕著に不利
- 主張: MNREAD-J 方式で 4 スタイル × 3 ウェイトの 12 ゴシックを行動評価。ウェイト（線幅）は特に小サイズで読みやすさに効き、効果は文字サイズの 10–15% で天井。字面を大きくすると字間が詰まり相殺される（字詰まり効果）。先行研究でセリフ無し（ゴシック）が読みやすい。
- 強度: A（査読論文、照明学会誌 101(10):474-, 2017）
- 出典: "スタイルとウェイトが日本語フォントの読みやすさに与える影響", 大西・小田, 照明学会誌 (2017) — https://www.jstage.jst.go.jp/article/jieij/101/10/101_474/_pdf
- 適用: 本文フォント選定（Noto Sans JP 等の weight）、小サイズ注記
- ルール: 本文は Regular（400）以上、Light（300）本文は禁止。小サイズ（14px 以下）はやや太めに。字面の大きい UD 系は `letter-spacing` を 0.02–0.05em 程度確保（数値はテーマ側で検証）。

### F-34 Google Fonts の CJK は unicode-range スライスで転送量約 90% 削減
- 主張: CJK フォントはラテンの 15–20 倍の文字数。Google Fonts CSS API は unicode-range で分割配信し、転送量を「約 90%」削減。`text=` パラメータで特定文字列のみなら「97.5% 節約」の例。
- 強度: B（Google 公式）
- 出典: "An API for fast, beautiful web fonts", Jimmy Mooney, web.dev (2022) — https://web.dev/articles/api-for-fast-beautiful-web-fonts
- 適用: フォント読込戦略
- ルール: Noto Sans JP 等は自前ホストの単一 WOFF2 ではなく unicode-range スライス（Google Fonts の CSS を再現）を既定。hero キャッチ用の見出しフォントは `text=` 相当のサブセットで別配信。

### F-35 フォント swap の CLS は `size-adjust` / `ascent-override` 等で抑える
- 主張: フォールバックフォントに `size-adjust`・`ascent-override`・`descent-override`・`line-gap-override` を指定し Web フォントとメトリクスを揃えると swap 時のレイアウトシフトを消せる（記事に定量値なし）。CLS ガイドは `font-display: optional` + 適切なフォールバックを推奨。
- 強度: B
- 出典: "Improved font fallbacks (size-adjust)", Adam Argyle, web.dev (2021) — https://web.dev/articles/css-size-adjust ; web.dev "Optimize CLS"（F-09）
- 適用: 日本語 Web フォント（Noto Sans JP ↔ ヒラギノ/游ゴシック/Roboto 等）
- ルール: 本文はシステムフォント既定 + Web フォントは `font-display: optional` または `swap` + メトリクス調整済みフォールバック `@font-face`。日本語はフォールバック差が大きいため hero 見出しのみ Web フォントも選択肢。

---

## 5. 画像・アイキャッチ・サムネイル

### F-36 情報を持つ写真は見られ、装飾写真は無視される（NN/g 視線計測）
- 主張: FreshBooks の社員ページでは、実在社員の顔写真に経歴文より 10% 多い注視時間（文章は面積 316% 大きいのに）。Amazon 商品ページでは注視時間の 18% が商品サムネイル、82% がテキスト（画像 0.9 固視 vs 説明 4.4 固視）。ストック写真のモデルは無視される。
- 強度: B（NN/g 視線計測、2010）
- 出典: "Photos as Web Content", Jakob Nielsen, Nielsen Norman Group (2010, 2026 再確認) — https://www.nngroup.com/articles/photos-as-web-content/
- 適用: hero 画像、スタッフ紹介、事例、商品カード
- ルール: hero に汎用ストック人物を置かない。実在の人物・実物・実画面を優先。装飾のみの画像は LCP コストに見合わないので削る。

### F-37 顔は視線を強く引き、CTA から注意を奪い得る
- 主張: 視線計測の実務報告では「人は顔を見る」「被写体の視線方向を追う」ため、顔写真は CTA の近くに置き視線を導く形で使わないと本文・CTA から注意を奪う。
- 強度: C（実務者記事、統制研究なし）
- 出典: "Eye-Tracking: Why Are We Trained to Recognize Other Human Faces?", Key Lime Interactive — https://info.keylimeinteractive.com/eye-tracking-shows-the-power-of-the-face ; "Learn How Eye Tracking Helps With Website Optimization", VWO — https://vwo.com/blog/eye-tracking-website-optimization/
- 適用: hero 構図
- ルール: 人物 hero は視線/体の向きを見出し・CTA 側へ。テーマの hero パターンに「人物は CTA 側を向く」構図オプションを用意し、A/B 前提で扱う。

### F-38 alt テキスト欠落は 2 番目に多い失敗（53.1%）— WCAG 1.1.1（A）
- 主張: F-28 の通り alt 欠落は HP の過半で発生。WCAG 1.1.1 は非テキストに代替テキスト（装飾なら空 alt）を要求。
- 強度: A（規格）+ B（実態）
- 出典: WebAIM Million（F-28）; "WCAG 2.2", W3C — https://www.w3.org/TR/WCAG22/
- 適用: アイキャッチ、実績ロゴ、アイコン
- ルール: テーマは alt 未設定画像を出力しない（装飾は `alt=""`、意味画像は編集画面で必須）。ロゴ帯は企業名を alt に。

---

## 未確認・見送り（数値を出せなかったもの）
- prefers-reduced-motion の**ユーザー側有効化率**: 一次データ（OS/ブラウザテレメトリ）は見つからず。記載しない。
- Material Design 3 の duration トークン（short/medium/long/extra-long の ms 値）: 公式ページ本文を取得できず。M1 の値（F-05）で代替。
- Apple HIG Motion の本文: 取得失敗。F-07 は要旨のみ（C）。
- Chrome の「LCP で動画の最初のフレームを計測」変更のバージョン番号: 公式ブログ URL を特定できず。web.dev/lcp の記述（F-13）で代替。
- hero 動画 / WebGL と CVR の一次 A/B 研究: 見つからず（F-14 は C）。
- scroll-reveal（fade-in on scroll）が CVR に与える影響の一次研究: 見つからず。CLS/INP への影響は F-08〜F-10 で機構面から根拠付け。

## 出典 URL 一覧
- https://www.w3.org/WAI/WCAG22/Understanding/animation-from-interactions.html
- https://www.w3.org/WAI/WCAG22/Understanding/pause-stop-hide.html
- https://vestibular.org/article/what-is-vestibular/about-vestibular-disorders/
- https://web.dev/articles/prefers-reduced-motion
- https://m1.material.io/motion/duration-easing.html
- https://v10.carbondesignsystem.com/guidelines/motion/overview/
- https://developer.apple.com/design/human-interface-guidelines/motion
- https://web.dev/articles/animations-and-performance
- https://web.dev/articles/optimize-cls
- https://developer.chrome.com/blog/scroll-animation-performance-case-study
- https://erikrunyon.com/2013/01/carousel-interaction-stats/
- https://baymard.com/blog/homepage-carousel
- https://web.dev/articles/lcp
- https://cloudinary.com/blog/lazy-load-autoplay-videos-core-web-vitals
- https://performance.shopify.com/blogs/blog/liquid-video_tag-lazy-loading
- https://web.dev/articles/vitals
- https://web.dev/case-studies/vitals-business-impact
- https://web.dev/case-studies/vodafone
- https://web.dev/case-studies/yahoo-japan-news
- https://web.dev/case-studies/rakuten
- https://www.deloitte.com/ie/en/services/consulting/research/milliseconds-make-millions.html
- https://www.marketingdive.com/news/google-53-of-mobile-users-abandon-sites-that-take-over-3-seconds-to-load/426070/
- https://www.buildmvpfast.com/blog/css-scroll-driven-animations-replace-js-2026
- https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html
- https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html
- https://www.w3.org/WAI/WCAG22/Understanding/reflow.html
- https://www.w3.org/WAI/WCAG22/Understanding/text-spacing.html
- https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html
- https://www.w3.org/WAI/WCAG22/Understanding/focus-appearance.html
- https://www.w3.org/TR/WCAG22/
- https://tetralogical.com/blog/2023/10/05/whats-new-wcag-2.2/
- https://dequeuniversity.com/resources/wcag-2.2/
- https://waic.jp/knowledge/accessibility/
- https://burnworks.com/news/article/489/
- https://webaim.org/projects/million/
- https://techblog.yahoo.co.jp/entry/2023052430423559/
- https://www.kbys-lab.org/archives/1077
- https://www.w3.org/TR/jlreq/
- https://www.jstage.jst.go.jp/article/jjsse/14/1-2/14_26/_html/-char/ja
- https://www.jstage.jst.go.jp/article/jieij/101/10/101_474/_pdf
- https://web.dev/articles/api-for-fast-beautiful-web-fonts
- https://web.dev/articles/css-size-adjust
- https://www.nngroup.com/articles/photos-as-web-content/
- https://info.keylimeinteractive.com/eye-tracking-shows-the-power-of-the-face
- https://vwo.com/blog/eye-tracking-website-optimization/
