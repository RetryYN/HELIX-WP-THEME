# 最新安定版WordPressへの追随

POの指摘: 最新版への対応確認を開発の必須範囲として扱う。ユーザーに版番号を聞くだけで検証を保留しない。

## 2026-09-09の確認

公式更新API https://api.wordpress.org/core/version-check/1.7/ のupgrade/currentと公式配布一覧 https://wordpress.org/download/releases/ のLatest releaseはともに7.1。専用実機のwp-includes/version.phpも7.1。7.2は開発版として別枠で扱い、安定版の対応宣言と混ぜない。

PoCのstyle.cssはTested up to: 7.1、開発docker-composeも7.1。CIのtest.ymlは6.9.4、theme-quality-gate.ymlは6.6が残っていたため、同じ7.1/PHP8.3イメージへ統一する。コンテナ検索のancestor指定も追随させる。バージョン指定の変更だけでCI実行成功とは扱わない。

## 完了に必要な証拠

- 最新安定版の公式配布情報を作業開始・リリース前に再確認し、実機・CI・対応宣言を照合する。
- 実機の表示、Site Editorでの編集・保存・再読込、プラグイン連携、保護コンテンツのアクセス境界を確認する。
- CIのWordPress統合テストは通常PRでskipされるため、greenだけで統合互換性を主張しない。実行経路を確認して有効なテスト結果を取得する。
- themes/agent-neo-themeのTested up toは、PoCの結果だけでは書き換えない。配布テーマ自身を7.1で初期化・有効化し、必須a11yゲートまで通した後に更新する。
- 版更新で影響したブロックの保存妥当性と既存記事の編集を検証し、破壊的変更は移行手順とともに扱う。

現時点: PR run 34257214046でWordPress 7.1の実コア版を照合し、themes/agent-neo-themeを有効化した上でa11y、i18n、RTL、静的性能ゲートが成功した。この証拠に基づき同テーマのTested up toを7.1へ更新する。PoCの代表表示・編集にも既存実測がある。全候補の編集妥当性、全プラグイン連携、既存本番データの移行を含む全面互換性は引き続き未確認であり、今回の宣言範囲へ含めない。
