# パンくずの同一正本 PoC（WT-FR-NAV-01）

P0でカタログ候補0・AC2件missingだった位置把握の契約を、投稿・固定ページ・LPの３候補で検査する。旧AGENTNEOの実装・既存記事は変更しない。2 ACはpartialで、G3完了を主張しない。

## 正本と表示

`model.mjs` の架空サイト階層（名前・親ID・パス）を唯一の正本とし、親をたどって同じ配列から可視パンくずとBreadcrumbListを生成する。生成HTMLはJS無効時も読める再現artifactであり、手書き階層の保存正本ではない。文言/親/パスの変更をページ内で試せる。循環・欠損参照・空ラベル・サイト外/曖昧なパス・１階層だけの出力は拒否し、前の表示とJSON-LDを保持する。

リンクには省略しない可視ラベル、ナビゲーションの名前、現在ページのaria-currentを持たせる。区切りは支援技術から隠す。SPと200% root文字では折り返し、全ラベルとリンクを保つ。JS有効時のリンククリックは説明表示だけ。JS無効時のhrefは予約ドメインの例示URLであり、実サイト導線として検証していない。

## LPと未接続境界

LP `/desk-session/` は `/services/consult/` を典型導線の親に持つ。URLのディレクトリから階層を推測しない。サイト設定が選ぶ典型導線を別正本にする設計候補であり、実WPのURL解決・複数導線・固定ページ/投稿の保存モデルとの統合は未検証。

WPコアBreadcrumbsブロックの表示を再現した主張ではない。WP 7.2実機（未提供境界）、コアブロックへの接続、実投稿/固定ページ階層、保存/権限、SEOプラグインとのJSON-LD所有権、Rich Results/URL Inspection/検索表示適格性、全ブラウザ/実スクリーンリーダーは未検証。AI・credential・外部API・保存なし。

## 実測

| AC | ローカル検査 |
|---|---|
| NAV-01A | 投稿/固定ページ/LPの可視文字・順序・hrefとJSON-LDを直接照合。名前/親/URL変更、PC1440/SP390/320、JS有無、keyboard、200% root文字 |
| NAV-01B | 祖先正本の変更反証、循環/欠損/空名/１階層/不正URLの拒否、LP課題の表示、可視文字乖離/JSON順番乖離/祖先ラベル固定の3摂動検出 |

30ブラウザ検査と3摂動。摂動ごとに専用１テストの失敗を確認し、finallyでsource復元、sourceDigestsで復元後の同一性を検査する。200%はroot font-size 16→32pxでありブラウザズームではない。

`node scripts/build-breadcrumb-poc.mjs` で静的HTML、`node scripts/verify-breadcrumb-poc.mjs` でE2E・摂動・PC/SP６画像・verification・partial admission入力を生成する。共通研究用localhostサーバーは `node scripts/product-surfaces-server.mjs`。

## 一次資料

最新の [構造化データ再確認](../2026-09-20-google-structured-data-refresh/README.md) と `refresh.json` をverificationのsource digestに束縛する。[Google Breadcrumb](https://developers.google.com/search/docs/appearance/structured-data/breadcrumb) の典型導線/順序/ListItemと、[WAI-ARIA APG Breadcrumb](https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/) のnavigation/current-pageを参照した（2026-09-20）。正しいローカル出力は検索表示の保証ではない。
