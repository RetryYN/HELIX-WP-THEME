# CRO（CV 率）に関する Web デザインパターンの証拠集

作成日: 2026-09-05 / 用途: WordPress マーケティングテーマ（LP・コーポレート TOP・比較/アフィリエイト媒体・SMB）の設計入力
証拠強度: **A** = 統制実験・メタ分析 / **B** = 大規模ベンチマーク・業界データ / **C** = 単一事例・意見
注記: 数値は出典に明記されたものだけを転記。出典が示さないものは「数値なし」。文脈依存・係争中のものは ⚠ を付す。
なお、本文書は Web 検索結果（要約）に基づき作成しており、出典本文を全件精読したわけではない。設計へ採用する前に、要求番号を付ける段階で出典原文の再確認を推奨する。

---

## 0. 前提：A/B テスト証拠の読み方（メタ）

### F00 A/B テストの「勝ち」の大半は偽陽性か効果ゼロ
- **主張**: 商用 A/B テストの多くは効果が真に 0 であり、早期停止（p-hacking）が偽発見率を押し上げる。個別事例の「+300%」等は割り引いて読む必要がある。
- **強度**: A（2,101 件の商用実験の回帰不連続分析）
- **出典**: "p-Hacking and False Discovery in A/B Testing", Berman, Pekelis, Scott, Van den Bulte (Wharton / Optimizely データ), 2018, https://papers.ssrn.com/sol3/papers.cfm?abstract_id=3204791
- **数値**: 約 73% の実験者が正の効果が 90% 信頼水準に達した時点で停止。約 75% の効果は真に null。optional stopping で FDR が 33%→40%。
- **適用部位**: 本文書全体の読み方／テーマ側の実験ガイド
- **具体ルール**: テーマは「勝ちパターンを焼き込む」のではなく、各パターンを ON/OFF できる設定として提供し、サイト側で検証できる形にする。C 強度の数値は要求の根拠にしない。

---

## 1. CTA（配置・数・色・コントラスト・マイクロコピー）

### F01 CTA 色は「特定色」でなく「ページ内コントラスト」が効く ⚠
- **主張**: 有名な「赤 > 緑 +21%」は、ページの主色が緑で赤が目立ったためと解釈されており、色そのものの優位ではない。
- **強度**: C（単一テスト、n≈2,000 訪問、数日間）
- **出典**: "Which CTA Button Color Converts the Best?", CXL, https://cxl.com/blog/which-color-converts-the-best/ ／ "Which Is the Best Call to Action Button Color, According to Research?", （伏せ字）, https://optinmonster.com/which-color-button-converts-best/（（伏せ字）/Performable テストの再掲）
- **数値**: 赤ボタンが緑比 +21%（元テスト）。「コントラストが色より何倍予測的」等の数値は一次出典を確認できず、採用しない。
- **適用部位**: CTA ボタン／テーマのアクセントカラー設計
- **具体ルール**: CTA 色はブランド色パレットの中で「ページ主色と補色関係・最も彩度差が大きい 1 色」を予約し、他の UI（リンク・バッジ）に流用しない。WCAG コントラスト（テキスト 4.5:1）を満たすことを前提条件にする。

### F02 一人称マイクロコピー（"my" vs "your"）
- **主張**: ボタン文言を二人称から一人称に変えると CTR が上がった。
- **強度**: C（単一 A/B テスト、3 週間）
- **出典**: Michael Aagaard (ContentVerve) のテスト。二次: "How to Build and Optimize CTA Buttons That Convert", Unbounce, https://unbounce.com/conversion-rate-optimization/cta-buttons-that-convert/
- **数値**: "Start my free 30 day trial" が "Start your…" 比で決済ページへの CTR +90%
- **適用部位**: CTA ボタン文言（LP・料金表・フォーム送信）
- **具体ルール**: ボタン文言はテーマ既定を「動詞＋得られるもの」（例:「無料で資料をもらう」）にし、「送信」「Submit」を既定にしない。日本語では一人称/二人称の差が英語ほど明確でないため ⚠ 日本語サイトでは要検証。

### F03 CTA を「上部固定」にするより、説明後（下部）が勝つ場合がある ⚠
- **主張**: 複雑・高額なオファーでは、CTA を価値説明の後（fold 下）に置いた方が CV が高かった。単純なオファーは逆。
- **強度**: C（単一事例、他要素も同時変更）
- **出典**: "Above the Fold vs. Below the Fold: How To Encourage Scrolling", CXL, https://cxl.com/blog/above-the-fold/（Aagaard の PPC LP テスト）
- **数値**: +304%（ただし CTA 位置以外も変更しており、位置単独の効果ではないと出典自身が注記）
- **適用部位**: LP のセクション順序・CTA 配置
- **具体ルール**: LP テンプレートは「ヒーロー CTA」「価値説明後 CTA」「最終 CTA」の 3 スロットを持ち、各スロットを個別に非表示にできるようにする。オファーの複雑さで選ばせる。

### F04 上部（above the fold）に注意が集中するが、fold 下も無視できない
- **主張**: 閲覧時間の過半は最初の画面に集中するが、2 画面目までで約 3/4。fold 下も 4 割強の注視を得る。
- **強度**: B（アイトラッキング、120 名、2018 再調査）
- **出典**: "Scrolling and Attention", Nielsen Norman Group, 2018, https://www.nngroup.com/articles/scrolling-and-attention/
- **数値**: 閲覧時間の 57% が fold 上、74% が最初の 2 画面（2160px まで）。2010 年調査では 80% が fold 上。
- **適用部位**: ヒーロー／ファーストビュー／SP の初期表示
- **具体ルール**: ファーストビューに「何を・誰に・次の行動（CTA）」の 3 要素を必ず収める。重要情報を 2 画面目（約 2000px）までに置き、それ以降は補足扱いにする。SP ではヒーロー画像の高さを制限し、見出しと CTA が初期表示に入るようにする。

### F05 CTA は 1 ページ 1 主目的（Sticky と組み合わせ）
- **主張**: モバイルで複数の固定要素（上部ナビ + 下部 CTA）を並存させるより、下部 CTA のみ固定のほうが良い結果を示すテストがある。
- **強度**: C（GoodUI の個別テスト。詳細な効果量は会員限定で確認できず）
- **出典**: "Pattern #41: Sticky Call To Action", GoodUI, https://goodui.org/patterns/41/
- **数値**: 数値なし（公開範囲では効果量未確認）
- **適用部位**: SP ヘッダー／SP 下部バー
- **具体ルール**: SP では「固定ヘッダー」と「固定下部 CTA」を同時に既定 ON にしない。下部 CTA を使うときはヘッダーはスクロールで隠す。

---

## 2. ヒーロー（写真 / テキスト / 動画）

### F06 背景動画・スライダーは CV を下げる傾向 ⚠
- **主張**: ヒーローの背景動画や画像スライダーは、読み込み負荷と CTA からの注意分散により CV を下げると多くの CRO 実務者が報告。統制実験のメタ分析は見当たらない。
- **強度**: C（実務者の経験則。速度面は F17–F19 の B/A 証拠で間接支持）
- **出典**: "Why do image sliders and video backgrounds kill conversions?", Studio1 Design, https://studio1design.com/why-do-image-sliders-and-video-backgrounds-kill-conversions/ ／ "High-Impact Hero Sections That Don't Hurt Page Speed", GoStellar, https://www.gostellar.app/blog/high-impact-hero-sections-that-dont-hurt-page-speed
- **数値**: 数値なし
- **適用部位**: ヒーローブロック
- **具体ルール**: 既定ヒーローは「静止画（または無画像）＋見出し＋CTA」。動画背景はオプションとし、有効化時も (a) poster 画像を LCP 要素にする、(b) SP では自動再生しない、(c) 音声なし、を強制する。カルーセル型ヒーローはテーマ既定から外す。

### F07 ヒーローは「見出しの明確さ」で勝つ事例
- **主張**: ヒーローの構成（見出し・サブ・CTA・ビジュアル）変更で大幅な CV 増を得た事例がある。
- **強度**: C（単一事例）
- **出典**: "Hero Section Test That Led To A 50% Increase In Conversions", Carrot, https://carrot.com/blog/hero-section-conversion-test/
- **数値**: +50%（出典タイトルの数値。設計差分の詳細は要精読）
- **適用部位**: ヒーロー
- **具体ルール**: ヒーロー見出しは「誰の・何が・どう良くなる」を 1 文で言い切る文字数制限（SP で 2〜3 行）をテンプレート側で促す。

---

## 3. 社会的証明（ロゴ・声・数字・レビュー）

### F08 レビューが「あるだけ」で購入確率が大きく上がる
- **主張**: レビュー 0 件→数件で購入確率が急増。高額商品ほど効果が大きい。
- **強度**: B（1 年分・約 13,500 商品・約 12 万件のレビューの観察研究）
- **出典**: "How Online Reviews Influence Sales", Spiegel Research Center, Northwestern University, 2017, https://spiegel.medill.northwestern.edu/how-online-reviews-influence-sales/
- **数値**: レビュー 5 件で購入確率が約 4 倍。レビュー表示で CV が低価格品 +190%、高価格品 +380%。
- **適用部位**: 比較媒体の商品カード／サービス紹介／料金表近傍
- **具体ルール**: 「お客様の声」ブロックは最低 3〜5 件の実名（または属性付き）証言を並べられる構造にし、1 件だけ大きく載せるデザインを既定にしない。高額サービスほど証言ブロックを CTA 直前に置く。

### F09 満点より少し低い評価のほうが信頼される
- **主張**: 平均評価が 4.2〜4.5 付近で購入確率が最大となり、5.0 に近づくと逆に下がる。
- **強度**: B（同上研究）
- **出典**: 同上 Spiegel Research Center 2017
- **数値**: 購入確率のピークは星 4.2〜4.5（出典 eBook）
- **適用部位**: レビュー集計表示／評価バッジ
- **具体ルール**: 評価表示は実数（例 4.3）で出し、丸めて「★★★★★」に見せない。否定的レビューを非表示にする機能を既定で提供しない。

### F10 評価分布バーは「クリックで絞り込み」が期待される
- **主張**: ユーザーは星の分布バーをクリックして該当評価のレビューに絞ろうとする。反応しないと不満につながる。
- **強度**: B（Baymard の大規模ユーザビリティテスト＋ベンチマーク）
- **出典**: "5 Requirements for the 'Ratings Distribution Summary'", Baymard Institute, https://baymard.com/blog/user-ratings-distribution-summary
- **数値**: 特定評価のレビューを探す被験者の 90% が分布バーをクリック。分布を持つサイトのうちフィルタ実装は 61%。
- **適用部位**: 比較媒体のレビュー集計ブロック
- **具体ルール**: レビュー分布を描画するときは各バーをフィルタリンクにする。静的画像の分布バーは不可。

### F11 トラストシール（決済/セキュリティ）は認知度で効き方が異なる
- **主張**: 同じ位置に置いても、ユーザーが「安全と感じる」シールと感じないシールがある。既知ブランドのシールが有利。
- **強度**: B（CXL のアイトラッキング＋アンケート原研究）
- **出典**: "Checkout Optimization: How Do Trust Seals Affect Security Perception? [Original Research]", CXL, 2019, https://cxl.com/research-study/checkout-optimization/ ／ "Which Site Seals Create The Most Trust?", CXL, https://cxl.com/research-study/trust-seals/ ／ "How Users Perceive Security During the Checkout Flow", Baymard, https://baymard.com/blog/perceived-security-of-payment-form
- **数値**: 数値なし（相対順位のみ。決済ブランド系シールが最も想起・注視された）
- **適用部位**: フォーム・決済・申込ブロックの周辺
- **具体ルール**: 「トラストバッジ」ブロックは任意の画像を並べるだけでなく、日本市場向けに「SSL/プライバシーマーク/決済ブランド/実績数」の枠を分けて置けるようにする。無名の自作バッジで代替しない。⚠ 日本ユーザーでの再現性は未検証。

### F12 ロゴ・実績数字は「ファーストビュー付近」に置くのが通説 ⚠
- **主張**: 顧客ロゴ・受賞・認証をヒーロー直下に置くと権威付けになる、という実務ガイドが多い。統制実験のメタ分析は見当たらない。
- **強度**: C（実務ガイド。fold 上の注視データ F04 で間接支持）
- **出典**: "Trust Signals: A Key to Consistent Page Conversions", Linear Design, https://lineardesign.com/blog/trust-signals/
- **数値**: 数値なし
- **適用部位**: ロゴ帯／数字カウンター
- **具体ルール**: ロゴ帯はヒーロー直下スロットを既定にし、ロゴは 4〜8 個・単色化オプションを提供する。数字カウンターは「出典・時点」の小文字を必須フィールドにする（景表法対策、F26 参照）。

---

## 4. 料金表

### F13 高い順（左に高額）に並べると高額プランが選ばれやすい
- **主張**: 料金表で高額プランを左（最初）に置くと、注視が早く長くなり、高額プラン選択が増える（アンカリング）。
- **強度**: B（CXL のアイトラッキング＋タスク実験、原研究）
- **出典**: "Pricing Page Optimization: How to Order Pricing Plans [Original Research]", CXL, https://cxl.com/research-study/pricing-plan-study-order/
- **数値**: 数値なし（「最初に置いた高額プランが最も多く選ばれた」との定性的結論）
- **適用部位**: 料金表ブロック
- **具体ルール**: 料金表は列順を自由に設定でき、既定は「高→低」または「推奨を中央」のどちらかを選択制にする。

### F14 「おすすめ」ハイライトは注視を集めるが選択を保証しない ⚠
- **主張**: 推奨プランの強調は注視を集めるが、選択率への影響は条件によって異なる。
- **強度**: B（CXL 原研究）
- **出典**: "The Effects of Highlighting a 'Recommended' Pricing Plan [Original Research]", CXL, https://cxl.com/research-study/pricing-page-study-highlight/
- **数値**: 数値なし
- **適用部位**: 料金表
- **具体ルール**: 「おすすめ」バッジ・色強調は 1 列のみ許可。強調列の CTA だけ主色にし、他列は二次スタイルにする。

---

## 5. フォーム（項目数・多段・インライン検証）

### F15 フォーム項目は削れるだけ削る
- **主張**: 平均チェックアウトは項目数が多く、ほとんどのサイトで既定表示の項目を大幅に減らせる。離脱要因として「手続きが長い/複雑」が上位。
- **強度**: B（Baymard の大規模ベンチマーク＋ユーザビリティテスト）
- **出典**: "Checkout Optimization: Minimize Form Fields", Baymard Institute, https://baymard.com/blog/checkout-flow-average-form-fields ／ "A Holistic View on the Current State of Checkout Usability", Baymard, https://baymard.com/blog/holistic-view-on-checkout-usability
- **数値**: 平均チェックアウトは 5.1 ステップ・11.3 項目（2024）。多くのサイトで既定表示項目を 20〜60% 削減可能。「複雑すぎる」を理由の離脱 22%。「8 項目超で 1 項目ごとに完了率 4〜6% 低下」は一次出典を確認できず、採用しない。
- **適用部位**: 問い合わせ・資料請求・申込フォーム
- **具体ルール**: テーマ同梱のフォームテンプレートは既定 3〜5 項目（名前・メール・用件＋任意 1〜2）。「会社名」「電話」「ふりがな」等は任意・折りたたみ既定。確認用メール再入力は置かない。

### F16 多段（ステップ）フォームは長いフォームで有利 ⚠
- **主張**: 質問数が多いリード獲得フォームでは、1 画面より複数ステップに分けた方が CV が高い事例が多い。短いフォームでは差が出ない/逆転もある。
- **強度**: C（事業者の自社事例・ベンダー集計。統制メタ分析なし）
- **出典**: "Why Multi-Step Lead Forms Get up to 300% More Conversions", （伏せ字）, https://ventureharbour.com/multi-step-lead-forms-get-300-conversions/ ／ "Is a Single Page Form or Multi Step Form Better for Conversion?", Zuko, https://www.zuko.io/blog/single-page-or-multi-step-form
- **数値**: （伏せ字） 自社: 導入初週 +300%、長期で 0.96%→8.1%。ベンダー集計の「+86%」「13.85% vs 4.53%」は C 強度・母集団不明。
- **適用部位**: 見積・診断・申込フォーム
- **具体ルール**: 6 項目以上のフォームは多段テンプレートを提供（進捗表示・戻る・最後に個人情報）。5 項目以下は単一画面を既定にする。

### F17 インライン（ライブ）検証はエラーを減らす
- **主張**: 入力欄を離れた時点（blur）で検証すると、送信時一括検証よりエラーが減り、完了時間は増えない。入力途中の検証はかえって苛立ちを生む。
- **強度**: A/B（Wroblewski の統制実験＋Baymard の大規模テスト）
- **出典**: "Inline Validation in Web Forms", Luke Wroblewski, A List Apart, 2009, https://alistapart.com/article/inline-validation-in-web-forms/ ／ "Usability Testing of Inline Form Validation", Baymard Institute, https://baymard.com/blog/inline-form-validation
- **数値**: エラー -22%（Wroblewski）。ライブ検証なしのサイト 31%（Baymard ベンチマーク）。
- **適用部位**: 全フォーム
- **具体ルール**: フォームコンポーネントは blur 時検証を既定 ON、入力中の赤表示は OFF。成功時のチェック表示を提供する。エラー文はフィールド直下に、具体的な直し方を書く。

### F18 単一カラム・大きめ入力欄
- **主張**: 2 カラム配置より 1 カラムのほうが良いテスト、入力欄を大きくしたテストが GoodUI に登録されている。
- **強度**: C（個別テスト、効果量は公開範囲で未確認）
- **出典**: "Pattern #123: Single Or Double Column Form Fields", GoodUI, https://goodui.org/patterns/123/ ／ "Pattern #97: Bigger Form Fields", GoodUI, https://goodui.org/patterns/97/
- **数値**: 数値なし
- **適用部位**: フォーム
- **具体ルール**: フォームは 1 カラム既定、入力欄高さは SP でタップ 44px 以上、font-size 16px 以上（iOS ズーム防止）。

---

## 6. Sticky CTA・SP 下部バー

### F19 SP 下部固定 CTA は EC/PDP で改善事例が多いが「常に勝つ」わけではない ⚠
- **主張**: モバイル画面下部の固定 CTA は親指到達性が高く、複数の A/B テストで CV 改善が報告される。一方、常に成功するわけではなく、カート/決済など行動が明確な文脈で有効。
- **強度**: C（複数の個別テスト事例。「Baymard が 5〜12% 増と報告」はベンダー記事の孫引きで一次確認できず、採用しない）
- **出典**: "A sticky CTA: guaranteed conversion uplift?", Online Dialogue, https://www.onlinedialogue.nl/en/blogs/sticky-cta-guaranteed-conversion-uplift/ ／ "Homepage Sticky CTA A/B Test: +20.4% Conversion Rate", CONVERTIBLES, https://convertibles.dev/blogs/case-studies/homepage-sticky-cta-case-study ／ "Sticky Add to Cart Button Example: Actual AB Test Results", GrowthRock, https://growthrock.co/sticky-add-to-cart-button-example/
- **数値**: 事例 1: +20.4%（95.1% 確度）。事例 2: CV +10%・直帰 -3%。母集団・期間は各記事参照。
- **適用部位**: SP 下部バー（電話・LINE・資料請求・購入）
- **具体ルール**: SP 下部バーはテーマ機能として提供し、ページ種別ごとに ON/OFF。高さは 56〜64px 以内、ボタン 2 個まで、コンテンツ末尾に同高さの余白を自動付与。フォーム入力中は自動で隠す。

---

## 7. ポップアップ（入口/離脱）とその害

### F20 侵入的インタースティシャルは Google の SP 検索評価で不利
- **主張**: SP で検索流入直後にコンテンツを覆うポップアップは、2017-01-10 以降ランキング上不利。法令由来（cookie・年齢確認）・ログイン・小さなバナーは除外。
- **強度**: B（プラットフォーム公式方針）
- **出典**: "Google confirms rolling out the mobile intrusive interstitials penalty", Search Engine Land, 2017, https://searchengineland.com/google-confirms-rolling-mobile-intrusive-interstitials-penalty-yesterday-267408 ／ "Intrusive Interstitials: Guidelines To Avoiding Google's Penalty", Smashing Magazine, 2017, https://www.smashingmagazine.com/2017/05/intrusive-interstitials-guidelines-avoid-google-penalty/
- **数値**: 数値なし
- **適用部位**: ポップアップ／モーダル／オーバーレイ機能
- **具体ルール**: テーマのポップアップ機能は SP では「検索流入の初回ページビュー直後」に全画面表示しない設定を既定にする。表示は (a) スクロール 50% 以上または滞在 N 秒後、(b) 画面の一部（下部シート）で閉じやすい、のいずれかを強制。

### F21 ポップアップは平均 CV 数%、だが頻度過多は直帰を増やす ⚠
- **主張**: 離脱意図ポップアップは平均数%の登録率を得るが、実装の下位層は 1% 未満で直帰増・ブランド毀損を伴う。1 セッション複数回表示は害。
- **強度**: B（ベンダーの大規模集計。ただしベンダー利益相反あり）
- **出典**: "Conversion Rate Optimization Statistics You Need to Know", （伏せ字）, https://optinmonster.com/conversion-rate-optimization-statistics/ ／ "50+ Popup Statistics", Crazy Egg, https://www.crazyegg.com/blog/popup-statistics/ ／ "Popup Conversion Benchmark Report 2025", Popupsmart, https://popupsmart.com/blog/popup-conversion-benchmark-report
- **数値**: 離脱意図ポップアップ平均 3.09%（（伏せ字）、約 12 億表示）。上位 10% は 8〜12%、下位四分位は 1% 未満。「2 回超の表示で直帰 +40%（（伏せ字））」は孫引きで一次確認できず参考扱い。
- **適用部位**: ポップアップ機能
- **具体ルール**: 1 セッション 1 回・閉じた後 7 日間非表示を既定。表示条件（離脱意図/スクロール/時間）を必須設定にし、「即時表示」は選択肢から外す。

---

## 8. ページ速度・Core Web Vitals

### F22 0.1 秒の改善が CV・客単価を動かす
- **主張**: モバイル速度 0.1 秒の改善で小売 CV・客単価・旅行 CV が上昇。
- **強度**: B（37 ブランド・3,000 万セッションの観察分析）
- **出典**: "Milliseconds Make Millions", Deloitte Digital（Google 委託）, 2020, https://web.dev/case-studies/milliseconds-make-millions ／ PDF: https://www.thinkwithgoogle.com/_qs/documents/9757/Milliseconds_Make_Millions_report_hQYAbZJ.pdf
- **数値**: 小売 CV +8.4%・客単価 +9.2%、旅行 CV +10.1%、リード獲得ページ直帰 -8.3%（いずれも 0.1 秒改善あたり）
- **適用部位**: テーマ全体（CSS/JS 配信・画像）
- **具体ルール**: テーマは同梱 CSS/JS を最小化し、ブロック単位で条件読込。ヒーロー画像は `fetchpriority="high"`＋幅指定、以降の画像は lazy。第三者スクリプト（計測・チャット）は遅延読込フックを用意する。

### F23 LCP 改善で売上が増えた統制 A/B テスト
- **主張**: 機能・見た目を変えず LCP のみ改善した版が売上・リード率を上げた。
- **強度**: A（同一 LP の A/B、差分は CWV 最適化のみ）
- **出典**: "（伏せ字）: A 31% improvement in LCP increased sales by 8%", web.dev（Google）, 2021, https://web.dev/case-studies/vodafone
- **数値**: LCP -31% → 売上 +8%、lead-to-visit +15%、cart-to-visit +11%
- **適用部位**: ヒーロー／LCP 要素
- **具体ルール**: LCP 要素（ヒーロー画像または見出し）を SSR で出し、render-blocking JS を排除。ヒーロー画像はリサイズ済み・WebP/AVIF を強制。

### F24 100ms 遅延で CV -7%、2 秒遅延で直帰 +103%
- **強度**: B（約 100 億訪問の RUM 集計）
- **出典**: "（伏せ字） Online Retail Performance Report: Milliseconds Are Critical", （伏せ字）（旧 SOASTA）, 2017, https://www.akamai.com/newsroom/press-release/akamai-releases-spring-2017-state-of-online-retail-performance-report
- **数値**: 100ms 遅延で CV -7%、2 秒遅延で直帰 +103%、3 秒超でモバイル訪問者の 53% が離脱
- **適用部位**: 同上
- **具体ルール**: テーマの受け入れ基準として「モバイル LCP 2.5 秒以内・INP 200ms 以内・CLS 0.1 以内（p75）」をデフォルトデモページで満たすことを必須にする。

### F25 モバイル 3 秒超で 53% が離脱
- **強度**: B（Google Analytics 集計、2016）
- **出典**: "The Need for Mobile Speed", DoubleClick / Think with Google, 2016, https://www.thinkwithgoogle.com/_qs/documents/2340/bc22e_The_Need_for_Mobile_Speed_-_FINAL_1.pdf
- **数値**: 3 秒超で 53% 離脱。5 秒以内のサイトは 19 秒サイト比で直帰 -35%、セッション時間 +70%
- **適用部位**: 同上
- **具体ルール**: F24 と同じ。

---

## 9. 緊急性・希少性（倫理・法令の限界）

### F26 偽のカウントダウン・在庫僅少はダークパターンとして規制対象
- **主張**: 期限後も価格が変わらない、リロードで復活するタイマー等は、米 FTC がダークパターンとして名指し。EU では値下げ表示に「直近 30 日の最低価格」明示義務。日本では景表法の有利誤認（二重価格・「今だけ」常態化）とステマ規制（2023-10-01）が該当。
- **強度**: B（規制当局の公式文書）
- **出典**: "Bringing Dark Patterns to Light", FTC, 2022（解説: WilmerHale, https://www.wilmerhale.com/en/insights/blogs/wilmerhale-privacy-and-cybersecurity-law/20220921-ftc-issues-dark-pattern-guidance）／ "Guidance on the interpretation and application of Article 6a of Directive 98/6/EC", European Commission, 2021, https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=oj:JOC_2021_526_R_0002 ／ "事例でわかる景品表示法（2024 年 12 月改訂版）", 消費者庁, https://www.caa.go.jp/policies/policy/representation/fair_labeling/assets/representation_cms201_250410_02.pdf ／ "景品表示法とステルスマーケティング", 消費者庁, https://www.caa.go.jp/policies/policy/representation/fair_labeling/assets/representation_cms216_200901_01.pdf
- **数値**: EU: 直近 30 日（最短）の最低価格を「prior price」として表示。他は数値なし。
- **適用部位**: カウントダウン／残席・在庫表示／二重価格／PR 表記
- **具体ルール**: (1) カウントダウンブロックは「終了日時」を必須入力とし、クライアント側で毎回リセットされるタイマーを実装しない。(2) 終了後は自動で非表示または「終了しました」に切替。(3) 二重価格ブロックは「比較対象価格の根拠（期間）」フィールドを必須にする。(4) アフィリエイト記事テンプレートは「PR/広告」表記を記事冒頭に固定表示する。

### F27 真の締切は効き、偽の締切は逆効果 ⚠
- **主張**: 本物の期限を持つタイマーは CV を上げ、偽/リセット型は下げるとの集計がある。
- **強度**: C（ベンダーの公開テスト集計 18 件、一次データ未確認）
- **出典**: "Do Countdown Timers Increase Sales? Data", LiquidBoost, https://liquidboost.app/blog/countdown-timer-conversion-data
- **数値**: 中央値: 真の締切 +9.1%、偽/リセット型 -3.2%（ベンダー集計、要検証）
- **適用部位**: 同上
- **具体ルール**: F26 のルールで技術的に「偽タイマー」を作れない構造にする。

### F28 ダークパターンは大規模に蔓延しており、第三者スクリプトで混入する
- **主張**: 1.1 万 EC サイトのクロールで多数のダークパターン（偽の希少性・緊急性・社会的証明含む）を検出。多くはサードパーティ製ウィジェット由来。
- **強度**: A（査読付き、大規模クロール）
- **出典**: "Dark Patterns at Scale: Findings from a Crawl of 11K Shopping Websites", Mathur et al., Princeton, CSCW 2019, https://arxiv.org/pdf/1907.07032
- **数値**: 約 11K サイト中 1,818 件（約 11%）でダークパターン検出（論文本文値、要確認）
- **適用部位**: 「◯人が閲覧中」「◯分前に購入」系ウィジェット
- **具体ルール**: テーマは実データに接続しない「疑似ライブ通知」ブロックを提供しない。

---

## 10. 比較表

### F29 比較表は SP で破綻しやすく、設計を誤ると離脱を招く
- **主張**: スペック比較機能は spec 駆動商材で必須だが、SP では 1〜3 列しか見えず一覧性が失われる。設計を誤ると機能ごと離脱。
- **強度**: B（Baymard の大規模ユーザビリティテスト）
- **出典**: "Product Comparison UX: Always Provide Comparison Features for Spec-Driven Industries (17% Don't)", Baymard, https://baymard.com/blog/provide-comparison-features ／ "4 Ways to Optimize the Comparison Feature for Scanning", Baymard, https://baymard.com/blog/user-friendly-comparison-tools
- **数値**: 比較機能を提供しないサイト 17%。SP 縦向きで見える列は 1 列、横向きで最大 3 列。
- **適用部位**: 比較媒体の比較表ブロック
- **具体ルール**: 比較表は (a) 先頭列（項目名）を固定・横スクロール、(b) SP では「カード縦積み」への自動切替オプション、(c) 差分のみ表示トグル、(d) 各列に CTA を持てる、を備える。

### F30 スペックの羅列は読まれない
- **主張**: 単なるスペックリストはユーザビリティが低く、意味づけ（何が良いか）を添える必要がある。
- **強度**: C（Nielsen の解説記事）
- **出典**: "Specification Lists Have Terrible Usability", Jakob Nielsen (UX Tigers), https://www.uxtigers.com/post/specification-lists
- **数値**: 数値なし
- **適用部位**: 比較表・スペック表
- **具体ルール**: 比較表の各行に「注釈（なぜ重要か）」を任意で付けられるフィールドを持つ。◯△× の記号は必ずテキスト代替を出す。

---

## 11. 404 ページ・内部リンクカード・読み順

### F31 404 は「原因＋次の行動」を示すと回復する
- **主張**: エラーは平易な言葉で、何が起きたか・どうすればよいかを示す。404 は「人気ページ」「トップ」「検索」を提示する。
- **強度**: C（NN/g ガイドライン、ヒューリスティック）
- **出典**: "Improving the Dreaded 404 Error Message", Nielsen Norman Group, https://www.nngroup.com/articles/improving-dreaded-404-error-message/ ／ "Error-Message Guidelines", NN/g, https://www.nngroup.com/articles/error-message-guidelines/
- **数値**: 数値なし
- **適用部位**: 404 テンプレート
- **具体ルール**: 404 テンプレートは「検索窓＋人気記事（PV 順）＋主要 CTA」を既定で含む。エラーコードは小さく。

### F32 2 ページ目を見た読者は再訪率が大幅に高い（回遊の価値）
- **主張**: 初回訪問で 1 ページしか見ない読者の再訪率は極めて低く、2 ページ見るだけで再訪率が数倍になる。内部リンクカード／「次の記事」の設計は再訪と CV の前段に効く。
- **強度**: B（（伏せ字） のパブリッシャー横断データ）
- **出典**: "Readers who visit 2 site pages are 2.75x likelier to return than those visiting 1", （伏せ字）, https://chartbeat.com/resources/general/increase-return-visits-news-sites/
- **数値**: 1 ページのみの初回訪問者の再訪率 8%。2 ページ閲覧で再訪 2.75 倍。
- **適用部位**: 記事末の関連カード／シリーズナビ／サイドバー
- **具体ルール**: 記事テンプレートは本文直後（コメント・広告の前）に「次に読む」1 件＋関連 3 件のカードを既定配置。シリーズ記事は上下に「前/次」と目次を出す。

### F33 fold 下は約 50% で脱落するが、残った読者は長く読む
- **主張**: パブリッシャーデータでは fold 付近で約半数が脱落する一方、fold 下は上部の約 3 倍の時間読まれる。
- **強度**: B（（伏せ字） 集計）
- **出典**: "Hot tip: Use this scroll depth heat map", （伏せ字）, https://chartbeat.com/resources/product/reader-behavior-heat-map-google-sheets/ ／ "Is There an Optimal Article Length?", （伏せ字） via RebelMouse, https://www.rebelmouse.com/optimal-article-length
- **数値**: fold で約 50% 脱落。fold 下の閲覧時間は上部の約 3 倍。0〜2,000 語では語数増で engaged time 増、4,000 語超でばらつき増。
- **適用部位**: 記事レイアウト／目次／CTA 挿入位置
- **具体ルール**: 記事内 CTA は「導入直後（fold 直前）」と「本文末」の 2 か所を既定にし、中間挿入は任意。目次はファーストビュー直下に折りたたみで置く。

---

## 12. 横断的な運用ルール（本文書からの要約）

1. C 強度の数値（+300% 等）は要求の根拠にしない。A/B 強度の項目（速度・フォーム検証・レビュー・法令）を先に要求化する。
2. 「勝ちパターン固定」ではなく「ON/OFF できるスロット」として設計し、サイト側で検証可能にする（F00）。
3. 法令由来の制約（F26・F28）は CV 効果と無関係に必須要件とする。
4. 日本語・日本市場での再現性が未検証の項目（F02・F11 等）は ⚠ を残したまま扱う。

## 未取得・要追加調査
- Kirk & Thomas 等の大規模 A/B メタ分析（2025）: 検索で特定できず、未収録。
- ヒーロー「写真 vs テキストのみ」の統制比較: 一次証拠を見つけられず、C 強度のみ。
- Baymard の 8 項目超で完了率低下の数値、（伏せ字） の直帰 +40%: 一次出典未確認のため不採用。
