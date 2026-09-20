# ADMIN-01 representative PoC plan

1. 現行５ACと管理面候補要求・既存controllerの境界を確認する。
2. schema文書を中心に３層・ピッカー・移送・診断の静的表示契約を作る。
3. PC/SP、JS有無、keyboard、入力境界と失敗時の保持をブラウザ実測する。
4. ５ACをpartial登録し、３候補と再現可能な画像をカタログへ追加する。
5. npm test / catalog E2E / 公開安全性 / CI / 独立レビューで照合する。

実WP管理面、WP7.2、保存、権限、外部API、全P01–P33は実装対象外。要求正本と統合層は変更しない。
