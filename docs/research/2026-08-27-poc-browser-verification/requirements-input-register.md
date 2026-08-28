# 要求入力台帳 — テーマ構造監査・PoC から L1 改定へ渡す項目（2026-08-28 確定）

位置づけ: 監査 Issue（THEME-CAT / THEME-JSON / THEME-GATE）の**出力**。各 Issue はここに「証跡・確定した方針・L1 改定時に決める論点」を収録した時点で閉じる。要求 ID（WP-* stable ID）の発行は L1 改定（G0.5 通過後）で行い、ここでは発行しない。
上流: `docs/planning/drafts/L0-ai-editing-freedom-draft.md`（L0 改定案・G0.5 突合）/ 分類: `docs/design/addenda/L3-A5-poc-pattern-disposition.md`。

| # | 項目（Issue） | 証跡 | 確定した方針（本台帳で閉じる） | L1 改定時に決める論点 |
|---|---|---|---|---|
| RI-01 | 全体共有系の受け皿 6 つ（#26 THEME-CAT-01） | `shared-parts.md`、PR #39 の sidebar 5 案 + `parts/sidebar.html`、`2026-08-28-poc-conversion-and-variations/`（変換 6 領域 invalid=0） | 受け皿ごとに `theme.json.templateParts` の name/title/area を定義する。slot 系 3 つ（本文前・固定ページ上下・関連記事前後）は template part ではなく **テンプレ内のパターン挿入位置（空 group）** とし、空なら描画しない。header のナビは `wp_navigation` 参照（ref）にする | single-2col / single-1col のテンプレ変種を持つか（REQ-F-016 の扱いと連動） |
| RI-02 | フッターの領域化（#27 THEME-CAT-02） | PR #39 footer 5 案（`parts/footer.html` を触らずパターン差し替えで成立） | 「用途別に選べる」方式（同一 Block Types のパターン群）を第一形とし、既存 footer.html は残す（後方互換）。footer-credit は維持 | カラム数を GUI で可変にする要求を別に持つか |
| RI-03 | お知らせバー slot（#43 THEME-CAT-07） | `shared-parts.md`（B 14 control） | ヘッダー part 直下の空 group（パターン挿入位置）。文言・リンク・期間の状態は **投稿型**（option は使わない: RI-09） | 閉じる操作（cookie / localStorage）の要否 |
| RI-04 | SP 下部領域の規約（#44 THEME-CAT-08） | `shared-parts.md`（B 下部固定メニュー 18 control）、#29 追従シェア | template part `mobile-bottom` を新設しパターン差し替えで種類を選ぶ。積層順の既定: 同意バー > メニュー > シェア。本文最下部 CTA と重ねない | 1 つに限定するか積層を許すか |
| RI-05 | A/B 独自ブロックの受け皿（#28 THEME-CAT-03） | `catalog/themeA-blocks.md` `themeB-blocks.md`、`design-comparison.md` | core ブロック + block style を優先、新規ブロックは吹き出し / タブ / レビューの 3 つに限定。FAQ / アコーディオンは同じ `core/details` を受け皿にし差は block style。FAQPage JSON-LD は agent-neo-core の責務（REQ-NF-025）。ブログカードは **REST を経由しない実装**（`url_to_postid()` 直呼び。#15 の恒久解）。目次は core/table-of-contents 相当を新設 | 新規ブロック 3 つの要求 ID と優先度 |
| RI-06 | 追従シェア・PR 表記自動出力（#29 THEME-CAT-04） | `shared-parts.md`、法令要件（景表法ステマ規制） | PR 表記は法令要件として優先度高。判定の正本は投稿メタ（種別を持つ、提携 4 軸）、カテゴリ条件は未設定時の既定。出力は post-header part 側で編集者が消せない。位置はタイトル直下（ファーストビュー内）。追従シェアは RI-04 の規約に従う | 既定値（未設定時に出す / 出さない）。PC 左固定シェアと 2 カラムの干渉（RI-01 と連動） |
| RI-07 | テーマ A hidden 166 → style variation / block style（#30 THEME-JSON-01） | `json-mapping.md` §2、`2026-08-28-poc-conversion-and-variations/` §2（プリセット 1 → variation、G-T1b PASS） | プリセット 1 個 = variation 1 本（色 8 スラッグ）で成立。部品別 107 件は `styles.blocks.*` + block style（RI-05 に依存）。写像不能候補: アニメーション・グラデ角度・スライダー系 | 写像不能の確定（各 control の実機差分採取）を要求に含めるか |
| RI-08 | ウィジェット領域 → template part 変換（#31 THEME-JSON-02） | 同 §1（A 10 / B 20 領域はすべてブロックウィジェット、6 領域 invalid=0） | 変換手順は「領域 → group で包む → 受け皿へ」。判定基準: ブロック → そのまま / 静的 HTML → core/html / コアウィジェット → コアブロック / 独自ウィジェット → RI-05 の受け皿 | 独自ウィジェットの実運用件数（read-only 採取）を移行要求の前提にするか |
| RI-09 | 表示 ON/OFF・レイアウト選択 → 差し替え規約（#32 THEME-JSON-03） | `json-mapping.md` §3、PR #39（同一 Block Types のパターン群） | 変換先 4 分類: 有無 → slot（空なら描画しない）/ 見た目 → block style・variation / 骨格 → テンプレ変種 / 同部位別デザイン → 同 Block Types のパターン群。状態の置き場: テーマ既定 = ファイル正本、サイト固有 = `wp_template_part` / global styles（DB）、**option による不可視上書きは使わない**。適用例 20 件は難しい順で選ぶ | 適用例 20 件の作成を L2（画面）で行うか L3 で行うか |
| RI-10 | 一貫性ゲート（#34 THEME-GATE-01） | `consistency-responsibilities.md`、PR #36（G-T1/T1b/T2/T3/S1/S2、baseline 438、壊した fixture で検出確認） | 4 層一貫性は **NFR**（層 1 がトークンを所有、下位は値差し替えのみ）。ゲートは NFR の検査として CI に置く。範囲: 親 theme.json・styles/*.json・patterns/parts/templates・参照整合 | 生値の扱い（L0 改定案の安全域 / 破壊域と接続） |
| RI-11 | bridge 投影の尺度破壊（#35 THEME-JSON-04） | `consistency-responsibilities.md` §2 §5 | 投影は「同スラッグへの値差し替えのみ」。段の増減・settings 上書きは拒否（`added = incoming − current` が非空なら拒否、PR #36 の実装が参照）。優先順（コア < 親 < variation < bridge < user）は「bridge 有効時は層 1 の変更が届かない」という帰結とセットで文書化 | bridge を製品に残すか（REQ-F-025 / REQ-F-009 との整理） |
| RI-12 | main のパーツが WP 7.1 save 出力と不一致（#40 THEME-CAT-06） | `2026-08-28-poc-styles-parts-gates/` §2 | エディタ canonical 出力（`getSaveContent`）を正とする。flex/grid group に inline gap を書かない。罫線は longhand | — （実装は設計後） |
| RI-13 | 生値 438 件の置換（#41 THEME-CAT-05） | PR #36 baseline | 尺度系（padding / margin / fontSize / gap）はプリセット参照へ、意匠値（radius / letterSpacing / 線幅）は許容リスト | 許容リストの正本の置き場 |
| RI-14 | PoC 実機ゲート G-E1 / G-S3（#42 THEME-GATE-02） | `2026-08-28-poc-styles-parts-gates/scripts/`（G-E1 自動化、21 パターン invalid=0） | パターン・パーツ追加 PR の完了条件に G-E1（invalid=0）を入れる。G-S3 は option が非空なら上書き中スラッグを列挙して FAIL | G-S3 を doctor / CLI のどこに置くか |
| RI-15 | 第三者テーマ互換の meta キー（#47 THEME-JSON-06） | PR #23 残件 | 互換キーは公開本体に固有名で置かず、フィルタ / 非公開設定から注入し既定は空。移行プラグイン（REQ-F-008）の責務に寄せる | 互換読み取りを本体に残すか移行プラグインへ移すか |
| RI-16 | 実スラッグ・実パスの設定外出し（#46） | PR #23 残件 | `verify-themes.sh` は環境変数、`docker-compose.yml` は `.env` 参照、`.gitignore` は汎用化（PR #23 で実施） | — |

## 閉じ方
上表の Issue は「証跡が公開ツリー（本 PR）にあり、確定した方針が本台帳にある」ことで close する。未決の論点は L1 改定の入力として残り、Issue としては持ち越さない（L1 改定で要求 ID とともに再起票する）。
