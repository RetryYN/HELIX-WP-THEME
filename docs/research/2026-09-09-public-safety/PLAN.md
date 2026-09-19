# 公開安全性ゲートの受入検証

対象は `WT-NFR-CRED-01`。実際のcredentialや非公開名を証跡へ書かず、一時Git repositoryに合成fixtureを追加して、CI checkoutにも含まれる `scripts/public-safety-guard.sh --staged` の終了状態を測る。ローカルhook用の追跡外ファイルへは依存しない。

正例は通常ソースと、private mappingを与えたresearch文書。負例は秘密鍵形式、既知token形式、credential代入、個人絶対path、affiliate/click tracking URL、mappingなしのresearch文書、custom private mapping一致をそれぞれ1件ずつ置く。fixture repositoryは検査後に削除し、証跡には検査名・期待結果・実結果・終了codeだけを残す。

この検証は追加差分の静的検出を対象とする。Git履歴全体、画像内文字、暗号化・難読化された秘密、一般形では判別できない固有名を自動的に網羅する主張はしない。

Claude review 14のnon-blockerを反映し、submodule初期化判定は `path` 非空と `.git` directory/fileの組を括弧で束縛した。awkが常に非空pathを返すという前提に依存せず、同じ9 fixtureを再実行して証拠digestを更新した。
