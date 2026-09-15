# WT-NFR-A11Y-02 reduced-motion

対象は配布テーマ・Core管理画面と prototype-03 の再現ソース。公開・commit・pin更新は含まない。

事前調査で side.js の無条件 smooth scroll、配布CSSのtransitionと管理画面shimmer、ロード時のみの設定判定による実行中counter停止漏れを確認した。既存カルーセルは手動のみでautoplayはない。home.jsの分単位の残り時間更新は位置・装飾のアニメーションではなく内容の更新として維持する。

要求正本: WT-NFR-A11Y-02 / WT-AC-A11Y-02A / WT-AC-A11Y-02B。設計はCSSでanimation/transition/smooth scrollを停止し、JSは操作時の設定を再判定、counterは設定変更時に最終値へ即時確定する。表示・手動ボタン・意味のある静的transformを保持する。

参考: [W3C C39](https://www.w3.org/WAI/WCAG21/Techniques/css/C39)、[W3C SCR40](https://www.w3.org/WAI/WCAG21/Techniques/client-side-script/SCR40)。

検証: ローカルChromiumで実ソースを読み込み、CSSの静止、reveal表示、counter最終値、手動scrollの即時動作と設定変更を確認。同じ判定関数へ正常・故障fixtureを直接通す。実WPへの公開状態は主張しない。
