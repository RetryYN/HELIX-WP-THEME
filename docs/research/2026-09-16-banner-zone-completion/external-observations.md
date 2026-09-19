# バナー・補助配置の外部観測と要求候補

2026-09-16 に公式資料を再確認した。以下は現行要求へ自動採用するものではなく、次の要求レビューで採否と適用地域を決める候補である。FTC資料は米国向け事業者ガイダンスであり、そのまま全地域の法的要件とは扱わない。

| 観測 | 要求候補 | 今回のPoC証拠 | 未実証 |
| --- | --- | --- | --- |
| W3C WAI は、リンクやボタンとして働く画像の代替テキストに、見た目より機能・遷移先を記述するよう示す。装飾画像は空の代替テキストとする。 | 画像リンクは、遷移先または操作目的と整合する代替名を必須にする。装飾画像とは登録時に区別する。 | `contract:valid`、functional alt の正例・不一致負例 | 管理UI、翻訳後の遷移先との整合、装飾画像の登録経路 |
| Google Publisher Tag は、非同期広告によるレイアウト移動を抑えるため、CSSでslot寸法を先に確保し、PC/SPごとに適切な寸法を選ぶよう示す。 | バナーslotは画像取得前から予約寸法を持ち、画像ロード成否で周辺要素の矩形を変えない。実配信ではCLSをラボ・フィールド双方で測る。 | `geometry:*:reserved-size` と読込前後4画像 | 実広告配信、全体CLS、フィールドデータによる寸法決定 |
| FTCのnative advertisingガイドは、必要な広告開示を明確な言葉で広告の焦点近くに置き、各端末で読みやすくし、複数広告では各広告との対応を明確にするよう示す。 | PR・広告表示はcreative/見出しより前または直近に置き、PC/SPで視認可能にする。rotationでも現在表示中の各creativeへの帰属を一意にする。 | `placement:*:pr-proximity`、`rotation-attribution`、PC/SP完成画像 | 対象地域別の法務判断、実文言、支援技術での開示評価 |
| WCAG 2.2 SC 1.4.13 は、hover/focusで出る追加内容について、dismissible・hoverable・persistentを求める。 | hover/focus起点の補助UIを追加する場合、Esc等で閉じられ、追加内容へポインタを移せ、利用者が閉じるか無効になるまで保持する。初期モーダル例外とは別に検証する。 | 今回はhover/focus起点UIを採用していない | 将来のtooltip/submenu実装時のキーボード・拡大表示・持続性検査 |

## 参照

- W3C WAI, Functional Images: https://www.w3.org/WAI/tutorials/images/functional/
- W3C WAI, Images Tutorial: https://www.w3.org/WAI/tutorials/images/
- Google Publisher Tag, Minimize layout shift: https://developers.google.com/publisher-tag/guides/minimize-layout-shift
- FTC, Native Advertising: A Guide for Businesses: https://www.ftc.gov/business-guidance/resources/native-advertising-guide-businesses
- W3C WAI, Understanding SC 1.4.13: https://www.w3.org/WAI/WCAG22/Understanding/content-on-hover-or-focus
