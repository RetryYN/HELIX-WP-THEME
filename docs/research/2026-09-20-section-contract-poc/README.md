# 見出し区間の契約 PoC（SECTION-01/02）

専用の架空記事１件から「区間と階層」「区間の変更」「表示と挿入」の３候補を生成する。既存記事は変更しない。4 ACはpartialで、G3完了・本番API・WordPress実装を主張しない。

## 境界と安定ID

H2/H3を見出しレベルNから次の同レベル以上までの[start, end)として抽出する。H2の中にH3を持ち、H4以下はその内部。H3なしのH2と記事末尾も同じ規則。IDはfixtureに保存したstableIdを使用し、見出し文言/順番から生成しない。H3 IDは `h2-prepare/h3-space` の親子形式。同じ親の中での移動を対象とし、親をまたぐ移動/ID移行は未実装。孤立H3・重複ID・ID欠損は拒否する。

`intermediate.json` と画面内JSONに境界、block keys、ID、親ID、section registryを出す。既存テーマのsection-registryはページ用部品の登録であり、本PoCの見出し区間registryとの接続は未検証。

## 区間操作

書き直し/差し替えは固定のローカル例文を使う。AI再生成/対象選定を行わない。選択区間の段落だけを差分にし、見出しと子区間のIDを保持する。H2選択では配下のH3本文もその区間に含む。適用は元状態が一致する場合だけ可能で、区間外blockの差分と古いproposalは拒否する。直近状態のsnapshotへrollbackする。画面の再読込で初期化し、rewrite historyの投稿メタ保存は未実装。

同階層の次の区間との入れ替え、折りたたみ、非表示、目次だけの除外を同じモデルで扱う。共通の「2番目のH2の後」slotを、投稿側の「選択区間の前」へ上書きできる。これは１fixture内の規則解決であり、全記事への保存/反映・Block Editor/MCPの両経路の等価性は未検証。

## 計測候補

IntersectionObserverで見出し全体が画面に入るとreach、連続500msでdwellをローカルCustomEventへ出す。非表示タブ/画面外への移動で保留timerを止める。sectionId / variantId / goalCvId / deviceType / versionとcontentVersionを含む。versionはこのPoCの候補schemaであり、TAG-02の正式データ層・tracking経路との統合は未接続。閾値は検査用で、閲読・読了・実際の滞在品質の判定ではない。外部送信・永続保存・cookie・credential・AIなし。

## 実測と残件

01A/Bは境界/安定ID、02A/Bは区間差分/rollback・順序/表示・slot・イベントへ対応する。PC1440/SP390/320、JS有無、keyboard、代表200% root文字（ブラウザズームではない）を検査。6つの負の摂動をそれぞれ専用テストで検出し、sourceを復元した後にdigestを照合する。

WP 7.2実機（未提供境界）、既存sections API/registry、Block Editor/MCP、全記事/投稿メタ保存・権限、検索snippet制御（max-snippet/nosnippet/data-nosnippet）、rewrite history、TAG-02/本番tracking、全ブラウザ/実スクリーンリーダーは未検証。

`node scripts/build-section-contract-poc.mjs` で静的HTML/JSON、`node scripts/verify-section-contract-poc.mjs` でE2E・摂動・PC/SP６画像・verification・partial admission候補を生成する。静的配信は `node scripts/product-surfaces-server.mjs` の共通研究用localhostサーバーを使用する。

参考：[MDN section](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/section)、[見出し](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/Heading_Elements)、[Intersection Observer](https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API)。H2/H3のみの区間化と安定IDは本テーマの要求契約であり、HTML標準が自動的に保証するものではない。
