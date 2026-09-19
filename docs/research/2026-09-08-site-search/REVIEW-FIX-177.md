# #177 保護記事検索cache

## 修正

パスワードcookieを持つ検索が毎回すべての保護記事を照合していたため、解除済み記事IDをWordPress transientへ保存する。cache keyはcookie hashのSHA-256と保護記事更新versionから作り、cookie値と記事パスワード自体は保存しない。保護記事の追加・パスワード変更・削除でversionを更新し、古い判定結果を使わない。

WordPress標準の[`post_password_required()`](https://developer.wordpress.org/reference/functions/post_password_required/)はcookie内のpassword hashを各記事の`post_password`と照合する。cookieには記事IDが無いため、cold cacheの初回走査は残る。改善対象は同じcookie/versionで繰り返す検索であり、検索レスポンスの`no-store`と公開・権限条件は維持する。

## 実測

| 保護記事 | cold検索 | warm検索中央値 | cold password照合 | warm password照合 | warm追加DB query |
| ---: | ---: | ---: | ---: | ---: | ---: |
| 50 | 440.8ms | 122.4ms | 50 | 0 | 0 |
| 400 | 1034.3ms | 121.6ms | 400 | 0 | 0 |

`verify-site-search-performance.mjs`の17件が成功。保護記事数を8倍にしてもwarm応答中央値は増えなかった。既存の検索検証は表示105、日本語74、入力297、境界62、権限335、password 319の計1,192件が成功し、記事側のpassword変更後に既存cookieのcacheが失効することも確認した。

これはローカル専用labのPoCであり、本番SLAではない。cold cache missの全件照合、transient evict後の再走査、persistent object cacheや第三者検索・認可拡張との共存は未検証として残す。

## 2026-09-20 entitlement delivery matrix

購読の付与・失効を変更した直後に、同じセッションから有料本文を再取得する条件を追加確認した。`scripts/verify-site-search-entitlements.mjs` は、匿名・未購入・期限切れ・別商品購入・買い切り・購読・編集者・管理者を対象に、検索HTML、`wp/v2/search`、検索feed、直接本文、`wt_paid` REST、paid feedを照合する。編集者・管理者が読める非公開記事はHTML/feedの期待値を権限内として保持し、パスワード保護本文はどの配信経路にも混入しないことを別に確認する。

202検査が成功し、買い切りは付与→失効→再付与→再失効、購読は期限切れ→付与→期限切れを各変更直後に検証した。結果と実行時のsource digestは `results/entitlements.json` に保存している。これはローカルのHELIX Content Labでの認可行列であり、persistent object cacheや第三者検索・認可・キャッシュ拡張の共存を実証したものではない。
