# i18n / RTL 境界の受入計画

対象は `WT-NFR-ENV-01`。テーマの全 PHP 翻訳関数呼び出しから POT を決定論的に生成し、Text Domain、POT とソースの一致、日本語・英語のソース言語方針、RTL 非対象の明示を同じ検証器で拘束する。

`npm run i18n:pot` は POT を更新し、`npm run i18n:verify` は更新せず差分を失敗にする。CI は後者だけを実行する。RTL 向け logical properties は防御策として維持するが、RTL の表示品質を検証済み・対応済みとは宣言しない。

本段階では日本語・英語がソース文字列として混在することとPOTへの収録を検査する。翻訳済みPO/MOの提供はまだ宣言せず、release判断へ残す。

負例は Text Domain の不一致、POT のソース差分、ソース言語方針の欠落、RTL 境界の欠落をそれぞれ失敗として確認する。
