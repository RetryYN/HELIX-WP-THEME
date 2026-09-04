# ユーザビリティ証跡集（WP マーケティング/メディアテーマ向け）

- 作成日: 2026-09-05
- 対象: 日本の中小事業者サイト（LP・ブログ/メディア）向け WordPress テーマの設計判断
- 収集方法: WebSearch / WebFetch で一次ソース（NN/g, Baymard, W3C, Google web.dev, 査読論文）を確認
- 証拠強度: **A** = 統制実験または大規模ベンチマーク / **B** = データを伴う専門家ヒューリスティック / **C** = 意見・解説
- 数値はソース本文で確認できたもののみ記載。確認できない場合は「数値なし」と明記
- 注意: 出典の大半は英語圏（欧米ユーザー）の調査。日本語・縦書き・CJK 固有の調査は NN/g・Baymard には見当たらず（数値なし）。日本語適用時は PoC で再検証すること

---

## 1. ナビゲーション

### 1-1. ハンバーガーメニュー（隠しナビ）は発見性・タスク時間・体感難易度を悪化させる
- **主張**: ナビを隠すと発見性が下がり、タスクは遅くなり、体感難易度が上がる。デスクトップで特に顕著
- **強度**: A（179 名、6 サイト、定量リモートテスト、2015-12 実施）
- **出典**: "Hamburger Menus and Hidden Navigation Hurt UX Metrics", NN/g, 2016, https://www.nngroup.com/articles/hamburger-menus/
- **数値**: 隠しナビ使用率 デスクトップ 27%（可視/コンボ 48–50%）、モバイル 57%（コンボ 86%）／発見性 20% 超低下／タスク時間 デスクトップ 39% 以上遅い・モバイル 15% 遅い／体感難易度 可視比 21% 増
- **適用部位**: ヘッダー、グローバルナビ（PC/SP）
- **テーマ規則**: PC 幅ではハンバーガーを使わず可視ナビを既定にする。SP では主要項目が 4 件以下なら可視（トップバー/タブバー）、5 件以上でのみハンバーガーを許可し、その場合は本文内リンク・関連リンク・フッターナビで補う

### 1-2. ハンバーガーを使うなら「Menu」ラベル付き・標準 3 本線・左上
- **主張**: アイコン自体の認知度は上がったが、隠す代償は消えない。ラベル「Menu」を添えると初心者に効く
- **強度**: B
- **出典**: "The Hamburger-Menu Icon Today: Is it Recognizable?", NN/g, 2025, https://www.nngroup.com/articles/hamburger-menu-icon-recognizability/
- **数値**: 数値なし
- **適用部位**: SP ヘッダー
- **テーマ規則**: SP ハンバーガーは 3 本線＋「メニュー」テキストラベルを既定にする。装飾変形・枠線なし。位置はヘッダー端（左上推奨、ロゴ配置と両立させる）

### 1-3. モバイルの可視ナビは 4–5 項目まで
- **主張**: トップバー/タブバーは項目が少ないときにのみ機能する。タップ領域確保のため約 5 項目が上限
- **強度**: B
- **出典**: "Basic Patterns for Mobile Navigation: A Primer", NN/g, 2015, https://www.nngroup.com/articles/mobile-navigation-patterns/
- **数値**: 数値なし（「4–5 項目」は設計指針としての記述）
- **適用部位**: SP ヘッダー、SP 固定ボトムバー
- **テーマ規則**: SP の可視ナビ（タブバー/ボトムバー）は最大 5 項目。ロゴ・検索・CTA を含めたクロームの総量を制御し、可視ナビを主役にする

### 1-4. メガメニューは階層が多いサイトで有効。ホバー起動には 0.5 秒待機と対角移動の猶予が要る
- **主張**: 二次元グルーピングで「思い出す」より「見る」を支援。ただしホバーはタッチ非対応・誤発火が問題
- **強度**: B
- **出典**: "Mega Menus Work Well for Site Navigation", NN/g, 2017(原 2009), https://www.nngroup.com/articles/mega-menus-work-well/ ／ "Timing Guidelines for Exposing Hidden Content", NN/g, 2015, https://www.nngroup.com/articles/timing-exposing-content/ ／ "Menu-Design Checklist: 17 UX Guidelines", NN/g, 2024, https://www.nngroup.com/articles/menu-design/
- **数値**: 表示前待機 0.3–0.5 秒、表示は 0.1 秒以内、離脱後 0.5 秒で閉じる／メニューが大画面全体を覆わない／サブメニューはホバーでなくクリック起動を推奨（2024 チェックリスト第 13 項）
- **適用部位**: PC グローバルナビのサブメニュー
- **テーマ規則**: サブメニューはクリック（タップ）起動を既定。ホバー起動をオプションで許す場合は 0.5 秒遅延・0.5 秒猶予・全画面を覆わない制約を実装。各項目は一度だけ表示し重複させない。中小サイトでカテゴリが少ない場合はメガメニュー自体を使わない

### 1-5. カスケード（多段）ドロップダウンは避け、グローバル項目をドロップダウンに埋めない
- **主張**: 上位カテゴリをドロップダウン内に隠すと発見性が下がる。多段カスケードは操作が難しい
- **強度**: B
- **出典**: "Dropdowns: Design Guidelines", NN/g, 2017, https://www.nngroup.com/articles/drop-down-menus/ ／ 前掲 Menu-Design Checklist 第 14 項
- **数値**: 数値なし
- **適用部位**: グローバルナビ
- **テーマ規則**: ナビ階層は 2 階層まで。3 階層以上はカテゴリ（ハブ）ページで受ける。上位カテゴリは常に可視項目として表示

### 1-6. ロゴは左上・ホームリンク。中央配置は「戻れない」失敗が増える
- **主張**: 中央ロゴだと 1 クリックでホームへ戻れない失敗が左配置の 6 倍
- **強度**: A（14 サイト、50 名）
- **出典**: "Centered Logos Hurt Website Navigation", NN/g, 2016, https://www.nngroup.com/articles/centered-logos/
- **数値**: 失敗率 6 倍（本文）。二次資料にある「4% vs 24%」は本文で確認できず → 数値なし扱い。ブランド想起は差なし（4 サイト、128 名）
- **適用部位**: ヘッダー
- **テーマ規則**: ロゴは左上固定・トップへのリンク。中央ロゴはテーマ既定にしない（デザインオプションとして許す場合は警告表示）

### 1-7. パンくずは階層（履歴ではない）を表示し、現在ページを非リンクで末尾に含める
- **主張**: パンくずは低コストで現在地を示す。SP では折り返し・極小タップを避ける
- **強度**: B（NN/g 11 指針）＋ Baymard ベンチマーク
- **出典**: "Breadcrumbs: 11 Design Guidelines for Desktop and Mobile", NN/g, 2018, https://www.nngroup.com/articles/breadcrumbs/ ／ "E-Commerce Sites Need 2 Types of Breadcrumbs", Baymard, 2013, https://baymard.com/blog/ecommerce-breadcrumbs
- **数値**: Baymard: 68% が不十分な実装、45% が 1 種類のみ、23% は未実装（EC 50 サイト）。区切りは「>」推奨
- **適用部位**: 投稿・固定ページ・カテゴリページ上部
- **テーマ規則**: 階層型パンくずを標準出力（ホーム > カテゴリ > 記事）。末尾は現在ページ・非リンク。SP では最後の 1–2 階層に短縮し折り返し禁止。1 階層しかない構造では非表示。構造化データ（BreadcrumbList）も併せて出力

### 1-8. 縦型（左サイド）ナビは広い IA に強い。視線の 80% は左半分
- **主張**: 縦リストは横並びより走査効率が高く、項目増に耐える
- **強度**: B（80% は NN/g アイトラッキングの引用。本記事内に出典リンクなし）
- **出典**: "Left-Side Vertical Navigation on Desktop", NN/g, 2021, https://www.nngroup.com/articles/vertical-nav/
- **数値**: 左半分注視 80%（本記事内に一次出典明示なし）
- **適用部位**: メディアのカテゴリ一覧サイドバー、ドキュメント型ページ
- **テーマ規則**: 記事数・カテゴリ数が多いメディア構成ではサイドバー左配置のカテゴリナビをオプション提供。ラベルは常にテキスト、アイコン単独禁止

### 1-9. ローカルナビは現在地を示し、深い階層ではパンくずに切り替える
- **主張**: ローカルナビは「あなたはここ」を示す道標。グローバルより控えめに
- **強度**: B
- **出典**: "Local Navigation Is a Valuable Orientation and Wayfinding Aid", NN/g, 2021, https://www.nngroup.com/articles/local-navigation/
- **数値**: 数値なし
- **適用部位**: 固定ページ群（サービス紹介の子ページ等）
- **テーマ規則**: 親子固定ページには現在ページを強調したローカルナビ（子ページ一覧）を自動出力。3 階層以上ではパンくずのみに

### 1-10. 検索だけに頼らせない。ナビと検索は相互補完
- **主張**: 検索優先の設計はユーザーにクエリ作成の認知負荷を課し、貧弱なサイト内検索の弱点を露呈する
- **強度**: C（専門家見解）
- **出典**: "Search Is Not Enough: Synergy Between Navigation and Search", NN/g, https://www.nngroup.com/articles/search-not-enough/
- **数値**: 数値なし
- **適用部位**: ヘッダー、404、カテゴリページ
- **テーマ規則**: 検索はナビの代替でなく補助。ナビ・カテゴリ・関連リンクを常に併設

---

## 2. ヘッダー・固定要素

### 2-1. 固定ヘッダーは「小さく・高コントラスト・動きを最小に」。部分固定も選択肢
- **主張**: 固定ヘッダーはナビ/検索への即時アクセスを与えるが、画面を食う。コンテンツ対クローム比を最大化する
- **強度**: B
- **出典**: "Sticky Headers: 5 Ways to Make Them Better", NN/g, 2021, https://www.nngroup.com/articles/sticky-headers/
- **数値**: タップ目標 1cm×1cm 以上、文字およそ 16pt、アニメーション 300–400ms。二次資料にある「ナビ時間 22% 短縮」は本文に存在せず → 数値なし
- **適用部位**: ヘッダー（PC/SP）
- **テーマ規則**: SP は「上スクロールで再表示する部分固定ヘッダー」を既定。不透明背景・本文と明確なコントラスト。ヘッダー高さは最小化（1 行）。透過/半透明固定ヘッダーは禁止

### 2-2. 長いページには「トップへ戻る」ボタン（4 画面超・右下・遅延表示）
- **主張**: 1 カラム長尺ページで上部ナビへ戻るコストを下げる
- **強度**: B（9 指針）
- **出典**: "Back-to-Top Button Design Guidelines", NN/g, 2017, https://www.nngroup.com/articles/back-to-top/
- **数値**: 4 画面分より長いページで使用／初期画面を過ぎてから表示
- **適用部位**: 記事ページ、LP
- **テーマ規則**: 4 画面超で右下に「トップへ」を遅延表示。テキストラベル併用、SP ではタップ可能サイズ、自動スクロール禁止

### 2-3. SP の固定ボトムバーは親指自然域に主 CTA を置く根拠になるが、コンテンツを覆わないこと
- **主張**: 片手持ち 49%・親指操作 75% の観察から、画面下部は到達しやすい。ただし NN/g は固定要素がコンテンツを遮らないことを求める
- **強度**: B（Hoober 観察 1,333 件は一次観察研究だが統制実験ではない）
- **出典**: "How Do Users Really Hold Mobile Devices?", Steven Hoober, UXmatters, 2013, https://www.uxmatters.com/mt/archives/2013/02/how-do-users-really-hold-mobile-devices.php ／ "The Thumb Zone: Designing For Mobile Users", Smashing Magazine, 2016, https://www.smashingmagazine.com/2016/09/the-thumb-zone-designing-for-mobile-users/
- **数値**: 片手持ち 49%、片手保持＋他方の指 36%、両手 15%、親指操作 75%。二次資料の「自然域タップ精度 96% vs 61%」は NN/g 一次ソースで確認できず → 数値なし
- **適用部位**: SP 固定ボトムバー（電話・問い合わせ・LINE 等）
- **テーマ規則**: SP の主 CTA（電話/問合せ）はボトムバーに配置可。高さは 1 行分、項目 2–3 個、コンテンツ末尾に同じ高さの余白を確保して本文・フッターを隠さない。PC では非表示

### 2-4. ファーストビュー上の注視は依然多いが、スクロールはする（57% / 74%）
- **主張**: 2018 年時点でも閲覧時間の 57% はフォールド上、74% は 2 画面以内。フォールド直後の急減は不変
- **強度**: A（120 名、13 万超注視）
- **出典**: "Scrolling and Attention", NN/g, 2018, https://www.nngroup.com/articles/scrolling-and-attention/ ／ "The Fold Manifesto", NN/g, 2015, https://www.nngroup.com/articles/page-fold-manifesto/
- **数値**: フォールド上 57%（2010 年は 80%）、2 画面以内 74%。上下差の平均 84%（NN/g 注視 57,453 件で 102%、Google 広告視認性 66% の平均）
- **適用部位**: LP ヒーロー、記事冒頭、トップページ
- **テーマ規則**: 価値提案・主 CTA・要約をファーストビューに置く。「見切れの示唆」（次セクションの頭出し）を残し、フォールド位置で区切りが完結して見える「偽の床」を作らない

### 2-5. 訪問者は 10–20 秒で離脱する。価値提案は 10 秒以内に
- **主張**: 滞在時間は負のワイブル分布。最初の 10 秒を越えると離脱率が下がる
- **強度**: A（大規模ログ分析）
- **出典**: "How Long Do Users Stay on Web Pages?", NN/g, 2011, https://www.nngroup.com/articles/how-long-do-users-stay-on-web-pages/
- **数値**: 10–20 秒で多くが離脱、平均滞在 1 分弱
- **適用部位**: LP・トップページのヒーロー
- **テーマ規則**: ヒーローは見出し 1 行で「誰に・何を」、補足 1–2 行、CTA 1 つ。スライダーで価値提案を隠さない

---

## 3. 目次・ページ内リンク

### 3-1. 長文ページには目次。本文内配置なら非固定、レール配置なら固定＋現在位置強調
- **主張**: 目次は俯瞰と直接移動を助ける。SP の「固定・折りたたみ目次」は多くが気づかなかった
- **強度**: B（ユーザー調査に基づく設計指針）
- **出典**: "Table of Contents: The Ultimate Design Guide", NN/g, 2023, https://www.nngroup.com/articles/table-of-contents/
- **数値**: 数値なし
- **適用部位**: 記事ページ目次ブロック
- **テーマ規則**: 見出し数がしきい値（例: h2 が 3 つ以上）を超えたら本文冒頭に非固定目次を自動生成。PC 2 カラム時は左/右レールに固定目次＋現在セクション強調（右レールは見落とされやすい）。SP では本文内目次かアコーディオン目次にし、固定折りたたみ目次は既定にしない。リンク文言は見出しと完全一致、外部リンクを混ぜない

### 3-2. ページ内リンクは今は受け入れられているが、他リンクと見分けられる様式に
- **主張**: 11 名中 10 名がページ内リンクに慣れていた。初回探索では無視され、特定情報を探すときに使われる
- **強度**: B（11 名の定性調査）
- **出典**: "In-Page Links for Content Navigation", NN/g, 2023, https://www.nngroup.com/articles/in-page-links-content-navigation/ ／ "Anchors OK? Re-Assessing In-Page Links", NN/g, 2017, https://www.nngroup.com/articles/in-page-links/
- **数値**: 11 名中 10 名が慣れていた
- **適用部位**: 目次、記事内アンカー
- **テーマ規則**: 目次には「目次」「このページの内容」ラベルを明示。短いページ（見出し 2 つ以下）では目次を出さない。アンカー移動先に固定ヘッダー分のスクロールマージンを設定

---

## 4. 可読性（行長・文字サイズ・行間・コントラスト）

### 4-1. 本文 1 行は 50–75 字（英字）が最適。長すぎる行は読み飛ばされる
- **主張**: 行が長いと読者は圧倒され、読まずに飛ばす。短すぎるとリズムが壊れる
- **強度**: B（Baymard 定性テスト＋タイポグラフィ研究）／A（Dyson & Haselgrove 2001: 55 字/行で理解度が 100 字/行より高い）
- **出典**: "Readability: The Optimal Line Length", Baymard, 2022, https://baymard.com/blog/line-length-readability ／ Dyson & Haselgrove, "The influence of reading speed and line length on the effectiveness of reading from screen", Int. J. Human-Computer Studies 54, 2001, https://dl.acm.org/doi/10.1006/ijhc.2001.0458 ／ WCAG 1.4.8 は 80 字以下
- **数値**: 50–75 字（英字・スペース込み）。二次資料の「80 字超は 41% 多く飛ばされた」は Baymard 本文に存在せず → 数値なし
- **適用部位**: 記事本文カラム幅、LP 段落
- **テーマ規則**: 本文コンテナは `max-width` を全角約 35–40 字相当（日本語は英字換算で 1 文字≒2 字幅とみなし PoC で計測）に固定。全幅段落を禁止。1 カラムでも本文幅を制限

### 4-2. 本文 16px 以上・行間 1.5 倍以上（WCAG 1.4.12 / NN/g 可読性）
- **主張**: 小さすぎる文字は可読性を殺す。行間 1.5、段落間 2 倍、字間 0.12、語間 0.16 を適用しても崩れないこと
- **強度**: A（W3C 規格）／B（NN/g）
- **出典**: WCAG 2.2 SC 1.4.12 Text Spacing, W3C, 2023, https://www.w3.org/TR/WCAG22/ ／ "Legibility, Readability, and Comprehension", NN/g, https://www.nngroup.com/articles/legibility-readability-comprehension/
- **数値**: 行間 ≥1.5em、段落後 ≥2em、字間 ≥0.12em、語間 ≥0.16em。16px は業界慣行で NN/g 本文に明示なし → NN/g としては数値なし
- **適用部位**: theme.json タイポグラフィ既定値
- **テーマ規則**: 本文既定 16px（SP でも縮小しない）、`line-height: 1.7`（日本語は 1.5 より広めが慣行）、段落間 1.5–2em。ユーザーの文字拡大（ブラウザ 200%）で崩れないよう `rem` 指定。理由なく 14px 未満を使わない

### 4-3. 低コントラストの文字は読めず・見つからず・信頼されない
- **主張**: ミニマル志向の薄いグレー文字は可読性・発見性・アクセシビリティを損なう。読みにくい文字は信頼も下げる
- **強度**: A（WCAG 規格）／B（NN/g）
- **出典**: WCAG 2.2 SC 1.4.3 Contrast (Minimum), W3C, https://www.w3.org/TR/WCAG22/ ／ "Low-Contrast Text Is Not the Answer", NN/g, 2015, https://www.nngroup.com/articles/low-contrast/
- **数値**: 本文 4.5:1 以上、大きな文字（18pt / 14pt 太字以上）3:1 以上
- **適用部位**: 色パレット、補足テキスト、プレースホルダー、フッター
- **テーマ規則**: パレットの文字色/背景色ペアは全て 4.5:1 以上を CI で検査。「補足」「日付」等のミュートカラーも 4.5:1 を下回らない。屋外のモバイル閲覧を想定し薄グレーを禁止

### 4-4. 見出しで「レイヤーケーキ」走査を支える。人は読まずに走査する
- **主張**: 13 年・500 名超の視線調査で「読まない・走査する」は不変。見出しと小見出しを走査する層状パターンが最も効率的
- **強度**: A（3 大規模アイトラッキング、500 名超、750 時間超）
- **出典**: "Text Scanning Patterns: Eyetracking Evidence", NN/g, 2019, https://www.nngroup.com/articles/text-scanning-patterns-eyetracking/ ／ "How People Read Online: New and Old Findings", NN/g, 2020, https://www.nngroup.com/articles/how-people-read-online/ ／ "How Chunking Helps Content Processing", NN/g, 2016, https://www.nngroup.com/articles/chunking/
- **数値**: 79% が走査・16% が逐語読み（1997 年調査、"How Users Read on the Web"）
- **適用部位**: 見出しスタイル、段落・リスト・強調のブロックスタイル
- **テーマ規則**: h2/h3 は本文と明確に対比（サイズ・太さ・余白）。短い段落・箇条書き・キーワード太字を編集ガイドとして提供。見出し先頭に情報語を置く（前置き語を避ける）

### 4-5. モバイルでも理解度は落ちないが、難しい文は遅くなる。モバイル本文は短く
- **主張**: 理解度に実用差なし（1,629 ケース）。難文はモバイルで 1 語あたり約 30ms 遅い。「迷ったら削る」
- **強度**: A（4 段階、計 286 名相当・1,629 ケース）
- **出典**: "Reading Content on Mobile Devices", NN/g, 2016, https://www.nngroup.com/articles/mobile-content/ ／ "Mobile Content: If in Doubt, Leave It Out", NN/g, 2011, https://www.nngroup.com/articles/condense-mobile-content/
- **数値**: 理解度差は約 3 ポイント（実用上差なし）、難文は約 30ms/語 遅い
- **適用部位**: SP 表示の抜粋、要約ブロック、アコーディオン
- **テーマ規則**: 記事冒頭の「要約」ブロックを標準化。背景・補足はアコーディオン/別ページへ後送りできる部品を用意

### 4-6. 瞥見（ちら見）読みは大きい文字ほど速い
- **主張**: 単語単位の瞬間判読では文字が大きいほど、通常幅ほど速い（コンデンス比 11.2% 遅い）
- **強度**: A（MIT AgeLab の統制実験、ただし単語単位で文章には直接適用不可）
- **出典**: "Typography for Glanceable Reading: Bigger Is Better", NN/g, 2017, https://www.nngroup.com/articles/glanceable-fonts/
- **数値**: 小文字は大文字より 26% 遅い、コンデンスは 11.2% 遅い（p<0.01）
- **適用部位**: ボタンラベル、ナビラベル、バッジ
- **テーマ規則**: ナビ・ボタン等の短いラベルにコンデンス書体・極小サイズを使わない。日本語ラベルは 14px 未満禁止

---

## 5. モバイルのタップ目標と親指域

### 5-1. タップ目標は物理 1cm×1cm 以上（親指 9.2mm 研究に基づく）
- **主張**: 指先幅 1.6–2cm、親指接触面 2.5cm。1cm 未満の目標は選択時間と誤タップを増やす
- **強度**: A（Parhi, Karlson & Bederson 2006, MobileHCI: 離散タスク 9.2mm、連続タスク 7.6mm）
- **出典**: "Touch Targets on Touchscreens", NN/g, 2019, https://www.nngroup.com/articles/touch-target-size/ ／ Parhi, Karlson, Bederson, "Target size study for one-handed thumb use on small touchscreen devices", MobileHCI 2006, https://dl.acm.org/doi/10.1145/1152215.1152260
- **数値**: 1cm×1cm（NN/g）、9.2mm（離散）/7.6mm（連続）（Parhi ら）
- **適用部位**: 全てのボタン・ナビ項目・パンくず・ページネーション・アコーディオン見出し
- **テーマ規則**: 全インタラクティブ要素の最小ヒット領域を 44×44 CSS px 以上に統一（AAA 相当、後述 5-2 と整合）。隣接要素間は 8px 以上

### 5-2. WCAG 2.2: 最小 24×24 CSS px（AA）、強化 44×44（AAA）。Google は 48dp＋8dp 間隔
- **主張**: 規格の下限は 24px だが、指の物理サイズから 44–48px が実用下限
- **強度**: A（W3C 規格・Google ガイド）
- **出典**: WCAG 2.2 SC 2.5.8 Target Size (Minimum) / SC 2.5.5 Target Size (Enhanced), W3C, https://www.w3.org/TR/WCAG22/ ／ "Accessible tap targets", web.dev (Google), 2020, https://web.dev/articles/accessible-tap-targets
- **数値**: 24×24 CSS px（AA、間隔等の例外あり）、44×44（AAA）、48×48dp ≒ 9mm、間隔 8dp
- **適用部位**: theme.json のスペーシング・ボタン既定
- **テーマ規則**: ボタン/リンク部品の既定高さ 48px、インラインリンクは行間で担保。Lighthouse の tap target 監査を CI に含める

### 5-3. 親指到達域: 画面下部が自然域、上部端は届きにくい
- **主張**: 片手持ち 49%。頻用操作は自然域（下部）に、稀な操作は上部へ
- **強度**: B（観察研究）
- **出典**: 2-3 と同じ（Hoober 2013 / Smashing 2016）
- **数値**: 片手持ち 49%、親指操作 75%
- **適用部位**: SP ボトムバー、フォーム送信ボタン、記事末 CTA
- **テーマ規則**: SP の主 CTA は画面下部（固定バーまたはセクション末尾）に置く。ヘッダー右上の小さなアイコン群に主要操作を集中させない

---

## 6. カルーセル / スライダー

### 6-1. 自動送りカルーセルはユーザーに無視され、目立つ情報でも見つからない
- **主張**: 98pt の大文字の割引表示でもユーザーは見つけられなかった。動くものは広告と見なされて無視される。運動障害・低リテラシー・非母語話者に不利
- **強度**: B（NN/g ユーザーテスト事例＋専門家判断）
- **出典**: "Auto-Forwarding Carousels, Accordions Annoy Users & Reduce Visibility", NN/g, 2013, https://www.nngroup.com/articles/auto-forwarding/ ／ "Carousel Usability: Effective UI for Content Overload", NN/g, https://www.nngroup.com/articles/designing-effective-carousels/
- **数値**: 事例では対象パネルの表示時間は全体の 20%。二次資料の「1% のみが 1 枚目をクリック」は NN/g 本文で確認できず → 数値なし
- **適用部位**: LP/トップのヒーロー、実績・お客様の声
- **テーマ規則**: ヒーローは静的 1 枚を既定。カルーセル部品を提供する場合、自動送りは既定オフ。ユーザー操作でのみ切替、操作後は自動送り停止

### 6-2. Baymard: トップページカルーセルの 46% に問題。静的セクションが同等に機能する
- **主張**: 慎重に設計しても静的コンテンツ配置と同等。モバイルでは自動回転を完全に避ける
- **強度**: A（大規模ベンチマーク＋定性テスト）
- **出典**: "10 UX Requirements for Homepage Carousels", Baymard, 2025 更新(原 2019), https://baymard.com/blog/homepage-carousel
- **数値**: デスクトップ上位 EC の 33% がカルーセル使用、46% に問題。自動回転する場合 5–7 秒（文字少）/10 秒（文字多）、ホバーで停止、操作後停止。モバイルは自動回転禁止・スワイプ対応・画像内テキスト禁止・1 秒以内表示
- **適用部位**: トップページ、LP
- **テーマ規則**: トップ/LP は縦積みの静的セクション（ヒーロー→特徴→実績→CTA）を既定。カルーセルは「複数キャンペーンを同時に見せたい」PO 判断があるときのみ、上記制約を満たす形で有効化

### 6-3. 横スクロール（ジグザグ含む）は見落とされる。右端の外は注視 1%
- **主張**: 横方向は認知されにくく、ジグザグ画像テキスト配置は走査効率を下げる
- **強度**: A（アイトラッキング）
- **出典**: "Scrolling and Attention", NN/g, 2018（右端外 1%）／ "Zigzag Image–Text Layouts Make Scanning Less Efficient", NN/g, https://www.nngroup.com/articles/zigzag-page-layout/ ／ "Beware Horizontal Scrolling and Mimicking Swipe on Desktop", NN/g, https://www.nngroup.com/articles/horizontal-scrolling/
- **数値**: 初期表示の右端より外への注視 1%（2018 調査）。ジグザグは数値なし
- **適用部位**: 記事一覧・実績一覧の横スクロール部品、「特徴」セクションのレイアウト
- **テーマ規則**: PC で横スクロール一覧を既定にしない。SP の横スクロールカードは「次のカードが見切れて見える」状態を必須にする。特徴セクションのジグザグ（画像左右交互）は既定にせず、整列グリッドを既定

---

## 7. アコーディオン / タブ

### 7-1. アコーディオンはモバイルでは有用、デスクトップでは「大半を開く必要がある内容」なら使わない
- **主張**: 俯瞰→詳細の順で読ませられるが、可視性を下げ操作コストを上げる
- **強度**: B
- **出典**: "Accordions on Mobile", NN/g, 2015, https://www.nngroup.com/articles/mobile-accordions/ ／ "Accordions on Desktop: When and How to Use", NN/g, 2023, https://www.nngroup.com/articles/accordions-on-desktop/
- **数値**: 数値なし
- **適用部位**: FAQ、料金詳細、仕様、SP カテゴリナビ
- **テーマ規則**: FAQ・補足情報にのみアコーディオンを使う。複数同時に開けること（自動で他を閉じない）。「すべて開く/閉じる」を提供。記事本文の主要内容を折りたたまない。展開時に画面上端へスクロールさせない（迷子防止）。SP では Back でページ離脱しないよう履歴を汚さない

### 7-2. アコーディオンの記号は下向きキャレット（∨）が最も「同じページに留まる」と伝わる
- **主張**: 136 名の定量テストで、キャレットがプラス・右矢印・記号なしを上回った。右矢印は記号なしと差がない
- **強度**: A（136 名定量）
- **出典**: "Accordion Icons: Which Signifiers Work Best?", NN/g, 2020, https://www.nngroup.com/articles/accordion-icons/
- **数値**: 136 名
- **適用部位**: アコーディオン、SP ナビのサブメニュー開閉
- **テーマ規則**: 開閉記号は下向きキャレット、開くと回転。ページ遷移するリンクは右向き矢印/キャレットで区別

### 7-3. タブは 1 行・少数・既定選択あり・比較不要な内容にのみ
- **主張**: タブは同一文脈内の切替。タブ間で情報を比較させる内容には不向き。複数行タブは空間記憶を壊す
- **強度**: B
- **出典**: "Tabs, Used Right", NN/g, 2024（原 2007）, https://www.nngroup.com/articles/tabs-used-right/
- **数値**: ラベル 1–2 語
- **適用部位**: 料金プラン切替、サービス種別切替
- **テーマ規則**: タブは最大 5 個・1 行・既定 1 つ選択・ラベル短文。SP で溢れる場合はアコーディオンへ自動変換。プラン比較（料金表）はタブにせず並列表示

---

## 8. リンクカード・関連コンテンツ

### 8-1. カードは「回遊」に強く「探索/比較」に弱い。カード全体をクリック可能にする
- **主張**: カードは詳細への入口。全体をタップ領域にすると PC/タッチ双方で使いやすくなる。特定項目探し・比較にはリストが優る
- **強度**: B
- **出典**: "Cards: UI-Component Definition", NN/g, 2016, https://www.nngroup.com/articles/cards-component/
- **数値**: 数値なし
- **適用部位**: 記事一覧カード、関連記事カード、内部リンクカード（ブログカード）
- **テーマ規則**: カードは全面クリック可能（1 カード 1 リンク、見出しを a に、疑似要素で全面化）。境界/影で塊を示す。同じ一覧内でカード高さを揃える。検索結果・アーカイブ一覧はカードでなくリスト（サムネ＋見出し＋抜粋）も選べる

### 8-2. リンクは「クリックできる」と分かる様式に。色のみで区別しない
- **主張**: 色＋下線が最も強い手がかり。2026 年注記: 下線必須ではないが WCAG 1.4.1（色のみ禁止）は守る
- **強度**: B（歴史的指針＋規格）
- **出典**: "Guidelines for Visualizing Links", NN/g, 2004（2026 編集注記）, https://www.nngroup.com/articles/guidelines-for-visualizing-links/ ／ "Beyond Blue Links", NN/g, https://www.nngroup.com/articles/clickable-elements/
- **数値**: 数値なし
- **適用部位**: 本文リンク、フッターリンク
- **テーマ規則**: 本文内リンクは既定で下線＋アクセント色。下線を外すオプション時はホバー/フォーカスで下線を出し、非リンク文字と 3:1 以上の色差を確保

### 8-3. リンクは同じタブで開く。新規タブは「同時参照が必要」な場合のみ
- **主張**: 新規タブは散らかり・迷子・Back 不能・支援技術で混乱を生む
- **強度**: B
- **出典**: "Opening Links in New Browser Windows and Tabs", NN/g, 2020, https://www.nngroup.com/articles/new-browser-windows-and-tabs/
- **数値**: 数値なし
- **適用部位**: 外部リンク、ブログカード
- **テーマ規則**: 外部リンクも既定は同一タブ。`target=_blank` を一括付与する設定を既定オンにしない。付ける場合は外部アイコン＋`rel=noopener`

### 8-4. ハンバーガーの代替として本文内リンク・関連リンク・フッターナビを常設する
- **主張**: 「ユーザーが主ナビを一度も使わない」前提で、本文内リンク・隣接する関連リンク・検索・フッターで導線を担保する
- **強度**: B
- **出典**: "Supporting Mobile Navigation in Spite of a Hamburger Menu", NN/g, 2015, https://www.nngroup.com/articles/support-mobile-navigation/
- **数値**: 数値なし
- **適用部位**: 記事末の関連記事、サイドバー、フッター
- **テーマ規則**: 記事ページには「本文直後」に関連記事（同カテゴリ 3–6 件）と CTA を固定配置。サイドバーは PC のみ。フッターには主要カテゴリ・固定ページへのリンク一覧（ファットフッター）を出力

### 8-5. 「関連リンク」型の広告/レコメンドはモバイルで最も嫌われない形式。モーダルは最も嫌われる
- **主張**: 452 名調査でモーダル・コンテンツを動かす広告・自動再生動画が最悪。関連リンク型は「モバイルで唯一安全」
- **強度**: A（452 名アンケート）
- **出典**: "The Most Hated Online Advertising Techniques", NN/g, 2017, https://www.nngroup.com/articles/most-hated-advertising-techniques/ ／ "Popups: 10 Problematic Trends and Alternatives", NN/g, 2019, https://www.nngroup.com/articles/popups/
- **数値**: 452 名。平均嫌悪スコア 5.23/7
- **適用部位**: 記事下レコメンド、CTA ポップアップ、広告枠
- **テーマ規則**: 入場時モーダル・スクロール途中の全面モーダルを既定で持たない。CTA は本文末/サイドバー/ボトムバーの静的配置。レイアウトシフトを起こす遅延読込枠は高さ予約必須

---

## 9. カテゴリ / ハブページ

### 9-1. カテゴリページはサブカテゴリを一覧の「上」に別枠で見せる。ハイブリッド型が有効
- **主張**: サブカテゴリをフィルタと分けて上部に示すと発見性が上がり、選択過多を防ぐ。検索エンジンからの着地点にもなる
- **強度**: B（NN/g EC 調査）
- **出典**: "UX Guidelines for Ecommerce Homepages, Category Pages, and Product Listing Pages", NN/g, 2018, https://www.nngroup.com/articles/ecommerce-homepages-listing-pages/ ／ "Top 10 Information Architecture (IA) Mistakes", NN/g, https://www.nngroup.com/articles/top-10-ia-mistakes/
- **数値**: 数値なし
- **適用部位**: カテゴリアーカイブ、タグアーカイブ、サービス一覧
- **テーマ規則**: カテゴリページの構成 = カテゴリ説明（H1＋リード）→ 子カテゴリ一覧（チップ/カード）→ 記事一覧。カテゴリ説明を編集可能に（SEO 着地点として）。セクション概要ページを省略して個別記事へ直リンクだけにしない

### 9-2. 一覧は無限スクロールでなくページネーション（または「もっと見る」）
- **主張**: 目的探索型では無限スクロールは戻り・比較・共有を妨げ、フッターに届かない
- **強度**: B
- **出典**: "Infinite Scrolling is Not for Every Website", NN/g, 2014, https://www.nngroup.com/articles/infinite-scrolling/
- **数値**: 数値なし
- **適用部位**: アーカイブ、検索結果
- **テーマ規則**: 一覧は番号付きページネーションを既定。「もっと見る」ボタンはオプション。無限スクロールは提供しない

### 9-3. 一覧の各項目は「名前・違いが分かる画像・要点・日付/価格」を出す
- **主張**: 一覧で判断に必要な情報（差異が分かる写真・簡潔な名前・主要属性）が無いと詳細ページ往復が増える
- **強度**: B
- **出典**: 9-1 と同じ（NN/g 2018）／ "Product List UX Best Practices", Baymard, 2025, https://baymard.com/blog/current-state-product-list-and-filtering
- **数値**: Baymard: モバイル 78%・デスクトップ 58% の一覧が「まあまあ以下」
- **適用部位**: 記事カード、サービスカード
- **テーマ規則**: 記事カードの必須要素 = サムネ・カテゴリ・タイトル・抜粋 1–2 行・日付。サービスカードは名称・一言・価格帯/対象。抜粋自動生成の文字数を SP/PC で調整

---

## 10. 404 ページ

### 10-1. 404 は「謝意＋原因の平易な説明＋復帰手段（検索・人気ページ・ホーム）」
- **主張**: 標準 404 はエラーメッセージ 3 原則（平易・具体・建設的）に反する。URL スペルチェック提案で 404 を 40% 以上減らした事例
- **強度**: B（NN/g 指針＋事例）
- **出典**: "Improving the Dreaded 404 Error Message", NN/g, 1998, https://www.nngroup.com/articles/improving-dreaded-404-error-message/ ／ "Error-Message Guidelines", NN/g, https://www.nngroup.com/articles/error-message-guidelines/
- **数値**: 404 を 40% 以上削減（Tobias Ratschiller のホテルサイト事例、NN/g 本文引用）
- **適用部位**: 404 テンプレート
- **テーマ規則**: 404 テンプレートに「ページが見つかりません（謝意）」「考えられる理由」「検索ボックス」「人気記事/主要カテゴリ」「ホームへ」を標準装備。ヘッダー/フッターは通常ページと同一。HTTP 404 を正しく返す（ソフト 404 禁止）

---

## 11. 検索

### 11-1. PC では検索ボックスを常時表示（27 文字幅）。虫眼鏡アイコンのみは SP 限定
- **主張**: アイコンのみは操作コストを増やす。入力欄は 27 文字幅で 9 割のクエリを収める
- **強度**: B
- **出典**: "The Magnifying-Glass Icon in Search Design: Pros and Cons", NN/g, 2014, https://www.nngroup.com/articles/magnifying-glass-icon/
- **数値**: 27 文字幅（英字）
- **適用部位**: ヘッダー検索
- **テーマ規則**: PC ヘッダーは入力欄を開いた状態で表示（幅は全角 14 字以上目安、PoC で確認）。SP はアイコン→展開でも可だが、ハンバーガー内に埋めない

### 11-2. SP の検索欄には送信ボタンを隣接させる
- **主張**: 送信ボタンが無いとユーザーは迷い、誤ってクリアする
- **強度**: A（Baymard ベンチマーク）
- **出典**: "Always Provide a Submit Button Adjacent to the Search Field on Mobile", Baymard, 2021, https://baymard.com/blog/mobile-search-submit-button
- **数値**: モバイル EC の 21% が送信ボタン無し
- **適用部位**: 検索フォーム部品
- **テーマ規則**: 検索フォームは入力欄＋「検索」ボタンを常にセット。`enterkeyhint="search"` を付与

### 11-3. 検索サジェストは「必ず結果が出るクエリのみ」、入力部分と補完部分を視覚区別、SP はテキストのみ
- **主張**: 0 件になるサジェストは有害。リッチ（画像付き）サジェストはあまり使われない
- **強度**: B（NN/g 調査、サジェスト選択率 23%）
- **出典**: "Site Search Suggestions", NN/g, 2018（2025 更新）, https://www.nngroup.com/articles/site-search-suggestions/ ／ "Enriched Site-Search Suggestions: Rarely Used", NN/g, 2022, https://www.nngroup.com/articles/enriched-site-search-suggestions/
- **数値**: サジェスト提示時の選択率 23%
- **適用部位**: 検索サジェスト（実装する場合）
- **テーマ規則**: サジェストは v1 では必須にしない。実装時は既存タイトル/カテゴリ由来のみ、入力文字を太字、SP はテキストのみ

---

## 12. 横断: アイコン・ボタン

### 12-1. アイコンにはテキストラベルを添える
- **主張**: 標準化されたアイコンは少なく、ラベルなしは意味の曖昧さを生む
- **強度**: B
- **出典**: "Icon Usability", NN/g, 2014, https://www.nngroup.com/articles/icon-usability/
- **数値**: 数値なし
- **適用部位**: ヘッダーアイコン群（検索・メニュー・電話）、ボトムバー、SNS
- **テーマ規則**: ヘッダー/ボトムバーのアイコンは全てラベル付き（SNS ロゴのみ例外可）。ラベルは 10–12px 未満にしない

### 12-2. ボタンは主・副・第三の階層で 1 スコープに主ボタン 1 つ。ラベルは動詞＋結果
- **主張**: 主 CTA は最も強い視覚重み、副は輪郭、第三はテキスト。「OK」「詳しく」より具体的ラベル
- **強度**: B
- **出典**: "Button States: Communicate Interaction", NN/g, https://www.nngroup.com/articles/button-states-communicate-interaction/ ／ "\"Get Started\" Stops Users", NN/g, https://www.nngroup.com/articles/get-started/
- **数値**: 数値なし
- **適用部位**: theme.json ボタンスタイル、CTA ブロック
- **テーマ規則**: ボタンスタイルは primary / secondary / tertiary の 3 種のみ。同一セクションに primary は 1 つ。ラベルは「無料で相談する」「資料をダウンロード」のように動作＋結果を示す

---

## 付記: 日本語適用時に PoC で確認すべき項目

1. 行長: 英字 50–75 字を日本語の全角字数へ換算する根拠は無い（数値なし）。SP 幅 375px・16px で約 21–22 字、PC 本文幅 640–720px で約 38–43 字となるため、読了率・スクロール深度で比較する
2. 行間: WCAG 1.4.12 の 1.5 は下限。日本語本文は 1.7–1.9 が慣行だが一次証跡なし
3. 親指域: Hoober の観察は 2013 年・欧米・当時の端末サイズ。現在の 6 インチ級端末では上部到達がさらに悪化する可能性が高いが数値なし
4. ハンバーガー vs 可視ナビ: NN/g 調査は英国 179 名。日本の中小サイトは主要導線が「電話・問い合わせ・サービス・料金・会社概要」の 5 前後になりやすく、ちょうど閾値（4 件）に当たるため、実サイトで A/B する
