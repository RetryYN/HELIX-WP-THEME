# ゾーン語彙・上書き順 PoC

`WT-FR-ZONE-02` のうち、意味ゾーンを固定したschemaと、`overrides`を配列順に評価する契約を純粋なローカルモデルで確認する。creative本体をzone設定へ埋め込まず、ID参照だけを許可する。

`node scripts/verify-zone-overrides-poc.mjs` で12件を実行する。未定義zone、壊れたoverrides、creative参照欠落は拒否し、複数条件が一致しても先頭規則だけを返す。

これは実WP 7.2のREST/CPT、権限、Site Editor、MCP/CLI、永続化、全23面の実登録、creative公開描画を接続したものではない。これらは受入候補の残件として保持する。
