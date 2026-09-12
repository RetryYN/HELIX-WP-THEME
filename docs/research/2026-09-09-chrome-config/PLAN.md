# 表示設定JSONの不正入力対応（#176の一部）

## 再現と要求

PHPの独立プロセス検査で11入力を実行し、欠落ファイル、post_type欠落、文字列rule、空post_type、不正ruleと正常ruleの混在の5入力が失敗した。欠落・不正な設定を表示面へ適用せず、正常な後続ruleは引き続き使えることを要求する。

## 修正

読込前に通常ファイル・読取可否を確認し、読込失敗とJSONの非配列を空設定として扱う。face名、rule、post_type、任意blockの型と空文字を確認し、不正なruleを除外する。空設定もリクエスト内でキャッシュする。

PHP公式の[file_get_contents](https://www.php.net/manual/en/function.file-get-contents.php)の失敗時falseと、[json_decode](https://www.php.net/manual/en/function.json-decode.php)の戻り値を確認した。WordPressの通常表示へ渡す前の入力境界で検査する。

## 証拠と残件

baseline.jsonは修正前5失敗、fixed.jsonは修正後11成功。各JSONに検証スクリプトと対象コードのSHA-256を持つ。PHP構文検査も成功。独立プロセス内のWordPress関数はstubであり、WordPress実機検査の代用にはしない。

実機の共通パーツ継承検査は428件すべて成功。`../2026-09-08-content-faces/results/inheritance/verify.json`のcompleted、全行pass、全source hashと現行コードの一致を確認した。設定復元と検査中のソース不変も成功。ヘッダーナビ62、コンテンツ191、学習252、常設案内397、品質265も成功。4検証スクリプトに設定読込コードのhashを追加し、証拠台帳21条件を実測行・全source hashで再照合した。カタログ再生成後の古い証跡は0。#176のevent-state直アクセス防止とevent badge CSS整理は未対応であり、Issue全体を修正済みとはしない。
