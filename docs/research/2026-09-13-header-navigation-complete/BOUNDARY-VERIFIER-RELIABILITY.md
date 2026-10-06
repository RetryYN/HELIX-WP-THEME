# 境界検査の操作 timeout

`verify-header-navigation-boundaries.mjs` の階層ケースにだけ設定されていたブラウザー操作 timeout を30秒にする。無効参照、scale、width-boundary ケースは明示的な短縮がなく、Playwright の標準期限を使っているため、同じsuite内の期限を揃える。隣接する header-navigation editor verifier も30秒である。これは navigation / interaction の操作期限で、境界条件、assertion、`waitUntil: 'load'`、子リンクの可用性検査は変更しない。

[WT-NFRL1-18](../../requirements/l1/nfr.md#L33) の2.5秒は LCP の Core Web Vitals 閾値であり、機能操作の期限とは別である。境界 verifier が以前2.5秒を使っていた理由は確認できておらず、根本原因も確定していない。

証拠はまだ安定性を確定しない。旧2.5秒設定での正式rebindは境界459条件中31件が失敗した（20件は `page.goto` の2.5秒timeout、10件は後続navigation error、1件は `locator.click` timeout）。30秒設定にした正式rebind再試行では、ヘッダー全体504条件・4011 assertion成功後、境界459条件・1019 assertion中29件が失敗した。不正参照360、scale27、幅境界36、scope2、cleanup3、設定復元1の各assertionは成功した。失敗はすべてJS有効のshared階層ケースで、最初の2件は `page.goto` の30秒timeout、その後27件のエラー詳細は元検査が120文字で切り捨てており、原因を特定できない。fixture cleanupと設定復元は成功したが、正式rebind全体は失敗した。

別のprivate full-editor診断は72条件/261 assertionを通過し、さらに同条件のprivate full-boundary診断は459条件/1077 assertionを失敗0で完了した。後者ではcleanupと設定復元、実行前後のWP状態、formal verifier source hashが一致した。これらは診断専用の結果で、正式proofには転用していない。成功した単発実行は30秒設定の安定性を証明せず、timeout変更の根本原因も不明のままである。登録済みproofの正式rebindは未完了である。
