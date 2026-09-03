# L0 企画 — 機械可読性を保ったまま、エージェント制御でバリエーションを最大化する

> 種別: L0 企画（要求の起点）。作成日: 2026-09-02。status: PO 指示の書き起こし（2026-09-02）。
> 本書は PoC 比較（本テーマ × テーマA × テーマB）の結論と PO の企画意図を要求の起点として固定する。旧要求との比較は持たない。

## 1. 比較軸と結論（PoC 証跡）

| 観点 | 本テーマ（AGENT NEO） | テーマA / テーマB | 出典 |
| --- | --- | --- | --- |
| 制御の正本 | theme.json + config JSON + JSON Schema + OpenAPI。起動状態を `health()` で自己申告。3 者で唯一「自分を機械可読に説明する」 | A: option 1,225 キー + 手書きアクセサ 707。B: 配列 540 キー。いずれも契約文書なし | `docs/research/2026-08-26-theme-structure-audit/12-mechanism-comparison.md`, `20-reverse-engineering-synthesis.md` |
| 構造編集 | Site Editor で GUI 編集できる唯一のテーマ（templates 10 / parts 5 / patterns 71 / variations 9） | classic。テンプレは PHP | `docs/design/catalog/customizability.md` |
| 表現・面の水準 | 面（ウィジェット領域 0・メニュー位置 0・投稿メタ 1）と記事内語彙（独自ブロック 1）が空 | A: 領域 10・独自ブロック 24・投稿メタ 22。B: 領域 20・独自ブロック 33・block style 33 | `docs/research/2026-08-26-theme-structure-audit/04-diff-register.md`, `docs/design/catalog/design-comparison.md` |
| 設定の写像可能性 | — | 値は A 約 94% / B 約 99% が JSON 化可。意味論（どこに出るか）は本テーマ側の受け皿設計が要る | `docs/research/2026-08-27-poc-browser-verification/json-mapping.md` |

テーマA / B は「一般に想定される水準」を示す基準であり、差分は本テーマの拡張余地を意味する。
本テーマの JSON 中間言語による機械可読性は突出しており、これは維持する土台である。

## 2. 企画（PO 2026-09-02）

機械可読性が高い状態を維持したまま、テーマA / B が持つ不足分（面・共有パーツ・記事内語彙・見た目の引き出し・記事単位の切替・設定の写像）を取り込み、
**エージェントが制御しやすい形で、どこまでバリエーション豊かに作れるか**を試す場が本テーマである。
実証できたパターンは証跡付きで記録し、GRAPHIX-NEO は根本コンセプトが違うため記録を読んで採否を自分で決めるだけとし、依存は作らない（PO 2026-09-02。統合層 `docs/plans/2026-08-28-wp-theme-and-graphix-neo-plan.md` の一方向原則と整合）。

- 維持するもの: JSON 宣言による目録化可能性、自己記述（health / capability）、契約スキーマ、AI 判定ロジックの不持込（判定は HELIX 側）。
- 拡大するもの: 面（置き場所）、共有パーツと骨格の変種、記事内語彙、見た目の引き出し、記事単位の切替、設定→JSON 写像、エージェント制御面、値の 3 域制御、構造化データ、販売構成・SEO 準拠・クローラー計測、A/B 配信・画像最適化・性能保証・差分 API・運用管理・SNS / CV / バナー・監査、SP 固有面 / 語彙、共通宣言と device 別差分の両幅編集（共通は fluid、差分は幅別 override、主たる確認面は設定で選び既定は SP、ゲートは両幅で検査）、計測タグ slot・データ層・同意連動、第三者プラグインの領域別既定・検出 manifest（PO 2026-09-03）。決定論的に決まる項目は問いにしない（PO 2026-09-03）。
- 進め方: 拡大の提案を要求候補として並べ、PO は「できることを採用するか」だけを判断する。境界値・配置先・方式の詳細は PoC 証跡で決める。

## 3. 非対象

- 課金・会員機能（実運用 2 サイトとも公開面で実使用 0。テーマ外とし必要なら第三者プラグイン、`docs/research/2026-08-26-theme-structure-audit/reports/INV-11-scope-boundary.md`）
- 第三者テーマの是正（HELIX-WP-HARNESS #198 の PO 判断）
- 外部デザインツール取り込み経路（無料枠制約で当面不採用、PO 判断 2026-08-29）
- AI 判定ロジック（variant 生成・統計判定・リスクスコア）のテーマ内実装

## 4. 要求への接続

`docs/requirements/authority.md` を入口に、L1（5 sub-doc）→ L2（discovery event / candidate projection）→ L3（precompile IR）へ接続する。
PO への問いは `candidate-projection.json` の `unresolved` にある WT-Q-* で、いずれも「X ができる。採用するか」の形をとる。
