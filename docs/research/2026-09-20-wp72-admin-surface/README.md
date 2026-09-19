# WordPress 7.2 管理・編集面の再調査

## 結論

WordPress 7.2 のロードマップを現行の管理画面要求と照合し、設定画面の保存契約だけでは表現しきれない二つの不足を要求候補へ反映する。

- DataViews / DataForm のサーバー登録フィールド・アクション、階層表示、一括操作、フィールド検証を設定 UI の契約に含める。
- dry-run、適用、rollback の失敗時に、診断可能なエラーとコピー可能な識別情報を残し、再試行・復旧の文脈を失わない。

## 公開一次資料

- [Roadmap to 7.2](https://make.wordpress.org/core/2026/09/18/roadmap-to-7-2/)（2026-09-20確認）
  - DataViews / DataForm / Fields のサーバー登録、階層と一括操作、エラー表示とコピー操作を挙げている。
  - Global Styles のフォーム要素と入力状態、HTML API の安全性強化、Secrets API、sudo mode は関連する境界として扱う。
- [DataViews, DataForm, et al. in WordPress 7.0](https://make.wordpress.org/core/2026/03/04/dataviews-dataform-et-al-in-wordpress-7-0/)（2026-09-20確認）
  - フィールドの pattern / minLength / maxLength / min / max 検証、一覧の groupBy、編集フォームの validity を整理している。
- [WCAG 2.2](https://www.w3.org/TR/WCAG22/)（2026-09-20確認）
  - reflow、text spacing、focus visible、エラー理解と操作順を管理面にも適用する根拠とする。

## 境界

7.2 の未リリース API を現行実装済みとは扱わない。テーマは表示・入力状態・設定 JSON 契約・フォールバックを担い、DataViews / DataForm の登録実装、保存処理、権限、秘密情報管理は Core プラグインまたは WordPress 本体の責務として接続点だけを要求する。7.2 未提供環境では既存の設定 JSON / manifest 契約へ縮退する。

## 要求化

`WT-FR-ADMIN-01` に DataViews / DataForm 互換のフィールド・アクション登録と検証境界を追加し、`WT-AC-ADMIN-01E` を候補化した。`WT-FR-ADMIN-03` には失敗時の診断情報・コピー・再試行・rollback 文脈を追加し、`WT-AC-ADMIN-03C` を候補化した。いずれも候補状態のままで、G3 承認・実装完了・7.2 の提供時期は主張しない。
