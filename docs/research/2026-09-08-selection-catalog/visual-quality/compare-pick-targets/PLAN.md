# 比較操作をカード内で見分けやすくする

基準main: 8331d4d。390pxの初期カードは354px幅だが、比較labelは54.02px幅しかない。
目的ラベルの隣の文字と小さなcheckboxだけが操作面で、カードを比較へ追加する入口を見分けにくい。

比較labelを96px以上・44px以上の枠付き操作面へ揃え、選択時には既存greenの境界とtint背景を付ける。
目的ラベルは残して必要なら折り返す。ネイティブcheckbox、表示文言、選択上限、比較の意味と状態管理は変えない。
カード全体をクリック可能にせず、既存の比較labelだけを拡張する。

320/390/768/1440pxの共通部品・すべて一覧で、比較操作の寸法、目的ラベルとの非重なり、横overflowを検査する。
checkboxから離れたlabel内の点で切替でき、キーボードSpaceでも戻せることを確認する。
390pxのbefore/afterをソース束縛付きで撮影し、目視確認する。全候補・実機・支援技術の適合宣言はしない。

## 実測結果

390pxの共通部品・すべて一覧で、比較操作の幅は54.02px→96px（約78%増）、高さは44pxを維持。
320/390/768/1440px×2一覧×36カードの計288カードで、96×44px以上・目的ラベルと非重なり・横overflowなしを確認した。
label左端の余白クリックでチェックが入り、checkboxへフォーカスしてSpaceで解除できる。

- 新規 `compare-pick-targets-selection-catalog.spec.ts`: 4 passed。
- 390pxの共通部品・すべて一覧をbefore/after撮影し、枠・選択色・目的ラベルの折返しを目視確認した。
- 既存7種類のvisual captureを各記録のbaselineで実行し、現CSSへ再束縛した。現行候補データを使う比較撮影であり、要求の意味を変更しない。
- filter-contextのselection/filterブラウザ検証も再実行する。

フォーカス表示は既存ネイティブcheckboxを保ち、親labelへ `:has(input:focus-visible)` を適用する。
参考: https://developer.mozilla.org/en-US/docs/Web/CSS/:has

再現: `CATALOG_BASE_URL=<local server> CATALOG_BASELINE_REF=8331d4d node docs/research/2026-09-08-selection-catalog/visual-quality/compare-pick-targets/capture.mjs`。
配信バイトの一致を検査し、`CATALOG_CAPTURE_OUTPUT` で別出力先を指定できる。

既存selection/filter検証は39 passed、束縛検査はstale 0、受入証跡auditもpassした。
初回は検索から3候補比較へ進むケースでChromiumが終了した（Target crashed）。同じ比較操作の単独実行はpassし、
他のcapture終了後に同一コマンドを再実行して39件すべて通過した。初回失敗も記録し、常時安定を主張しない。
公開情報guardは実在するリポ外の非公開対応表を環境変数で注入して実行する。
