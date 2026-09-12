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
