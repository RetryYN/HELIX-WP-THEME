# LP デザイン構成 観察台帳（区分統一版）

**収集件数**: 39 件（既存 24 件を再分類・統合 + 新規 15 件）
**区分**: A 企業向けSaaS/BtoB=8 / B 個人向けサービス=14 / C 資料DL・比較媒体=5 / D イベント・セミナー・キャンペーン=8 / E EC・単品商品=4
**fetched 内訳**: full=34 / partial=5（reason は observations.json 各件に記載） / failed=0（取得失敗は下記「除外」参照）

---

## 1. 区分 × 7 パーツ 出現率（各区分内の件数・%、"型あり"の件数）

| 区分(n) | interview_card | review | external_rating | download | embedded_form | line_cta | float_button |
|---|---|---|---|---|---|---|---|
| A(8) | 3件 37.5% | 1件 12.5% | 5件 62.5% | 5件 62.5% | 5件 62.5% | 0件 0% | 2件 25.0% |
| B(14) | 4件 28.6% | 4件 28.6% | 5件 35.7% | 3件 21.4% | 1件 7.1% | 3件 21.4% | 8件 57.1% |
| C(5) | 0件 0% | 2件 40.0% | 3件 60.0% | 5件 100% | 2件 40.0% | 0件 0% | 1件 20.0% |
| D(8) | 0件 0% | 0件 0% | 0件 0% | 0件 0% | 6件 75.0% | 0件 0% | 1件 12.5% |
| E(4) | 0件 0% | 2件 50.0% | 1件 25.0% | 0件 0% | 0件 0% | 0件 0% | 1件 25.0% |

---

## 2. 区分別セクション順の多数派（上位3、件数と実際の並び）

- **A**（n=8、多くが個別構成で一致は少ない）: `hero→features→case-interview→review→form`（1件）／`hero→steps→steps→faq`（1件）／`hero→steps`（1件）。他5件は上記いずれとも異なる個別構成。
- **B**（n=14）: `hero→campaigns→promotion_grid`（1件）／`hero→campaigns→cards`（1件）／`hero→benefits_3→features→terms`（1件）。14件中一致した並びは無く、個別構成が大半。
- **C**（n=5）: `hero→value→form`（2件、最多）／`hero→value→download`（1件）／`hero→compare→review→download→footer`（1件）。
- **D**（n=8）: `hero→overview→speaker→form`（1件）／`hero→features→problem→compare→steps→compare`（1件）／`hero→steps→faq→footer`（1件）。8件中7件が異なる個別構成。
- **E**（n=4）: `hero→problem→features→review`（1件）／`hero→offer→form→features→footer`（1件）／`hero→pricing→problem→features→review→faq→form→footer`（1件）。

---

## 3. 7 パーツの型内訳（全39件中、型が付いた件数のみ集計）

- **interview_card**: summary-card 4 / logo-only 2 / link-card 1
- **review**: quote+photo 6 / stars+count 2 / satisfaction-number 1
- **external_rating**: certification 5 / client-logos 3 / award-badge 3 / ranking 2 / media-logos 1
- **download**: button-to-form 8 / form-inline 3 / popup 2
- **embedded_form**: external 7 / inline-N-fields（件数不明の記録含む）4 / inline-13-fields 1 / inline-9-fields 1 / inline-40-fields 1
- **line_cta**: button 2 / qr 1
- **float_button**: sp-bottom-bar 11 / corner-round 2

---

## 4. 観察された多数派の型（事実のみ、7パーツ各1行）

- interview_card: 型が付いた9件中 summary-card が最多（4件）。
- review: 型が付いた9件中 quote+photo が最多（6件）。
- external_rating: 型が付いた14件中 certification が最多（5件）。
- download: 型が付いた13件中 button-to-form が最多（8件）。
- embedded_form: 型が付いた14件中 external が最多（7件）。
- line_cta: 型が付いた3件のうち button が最多（2件）。
- float_button: 型が付いた13件中 sp-bottom-bar が最多（11件）。

---

## 5. 収集方法・除外・限界

**収集方法**: WebSearch で自然検索結果および LP ギャラリー系サイト（LP ギャラリー系・比較メディア系）の一覧から候補 URL を収集し、WebFetch で本文を取得・構造を記述。フォーム送信・ログイン・購入・広告枠クリックは一切行っていない。

**除外した URL とその理由**（件数と理由）:
- 1件: 証明書エラーで取得不可（サプリ定期購入ページ、"certificate has expired"）— サンプルに含めず。
- 2〜3件: LP ギャラリーサイトは個々のデザインが自社ホスティングのスクリーンショット表示で、実サイトへの外部リンクが一覧ページ上に出ておらず、実LP本体の取得に使えなかったため、検索結果から実企業ドメインを別途特定する方式に切り替えた。
- 1件: セミナー一覧ハブページ（一覧のみで単一LP構造を持たないもの）は観察対象から除外。

**限界**:
- L06〜L25（前セッション由来の24件中20件）は mapping.json に元 URL・固有名の記録が引き継がれておらず、今回のパスでは実URLへの再アクセス・再検証ができていない。再分類（A〜E区分）は当時の site_pattern ラベルと sections_order の記述内容から行った推定であり、実ページの再確認ではない。
- JS 描画中心のページ（動的に読み込まれるフォームや比較カードなど）は WebFetch のHTML→Markdown変換で構造の一部（フィールド数など）が判定できず、"inline-N-fields"（件数不明）や partial 扱いとした件がある。
- D 区分（イベント・展示会・ウェビナー・キャンペーン）は「来場登録／申込は外部フォームへの誘導のみ」という構成が多く、embedded_form の型判定が「external」に偏っている。これは観察結果であり、この構成が望ましいという評価ではない。
- 区分ごとの n が 4〜14 件と幅があり、特に E（n=4）・C（n=5）は下限ぎりぎりのため、型内訳の割合は少数からの算出である点に注意。

---

## 保存パス

- observations.json: 39 件（内 partial 5 件、reason 記載）
- mapping.json: site-Lxx ↔ 実名・URL 対応表（scratchpad 内のみ、公開しない）
- summary.md: 本ファイル
