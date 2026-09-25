# News / column purpose gap

調査日: 2026-09-26。既存要求から抜けた記事目的を再調査し、候補要求とWordPress実機PoCを対応付ける調査記録。個別のCMS構成の採択やG3承認を代替しない。

## 既存要求との差分

POの階層整理（WT-EVT-0296）は、記事目的に検索流入、ニュース系の引用・発見、教育・満足・信頼形成のコラム系を挙げている。2026-09-26時点のL3にはこれらを受け入れる独立条件がなく、WT-FR-PAGE-02は分類索引の管理規則までだった。home-newsの区間PoCも、目的別記事一覧を検証していない。この差分を WT-FR-ARTICLE-01 と3つの受入条件へ追加した。現在の正本は135要求・303 ACだが、WT-FR-ARTICLE-01はPO未承認の `candidate_inventory` である。

## 一次資料での再確認

公開企業サイトの一覧表示だけを観察し、記事本文・画像・企業固有の表現は転載していない。

| 公式ページ | 観察した仕組み | 要求候補への意味 |
| --- | --- | --- |
| [電通 News Releases](https://www.dentsu.co.jp/news/) | キーワード検索、年別アーカイブ、カテゴリ・タグによる絞り込みがある | ニュース一覧では日付と分類を軸に過去項目へ戻れる |
| [NTT-AT Topics](https://www.ntt-at.co.jp/news/) | ニュースリリース・お知らせ・コラムを同じフィードに載せ、日付・種別・カテゴリを各項目に示す | 投稿型を分けずに目的メタデータで一覧を区別できる |
| [日本総研 経営コラム](https://www.jri.co.jp/column/) | 編集目的を説明し、新着を日付・著者・領域カテゴリ付きで案内する | コラム一覧はニュースと異なる編集目的・テーマ導線を表せる |

## WordPress実機PoC

PoCはこのフォルダーの `compose.yaml`、`seed.php`、`verify.mjs` から再現できる。公式テーマPoCのWordPress 7.1.2をLoopback限定で起動し、テーマをread-onlyでマウントする。架空の標準 `post` 5件を作り、目的を「検索流入」「ニュース・発表」「コラム」に分類する。CPT、投稿URL設計、Discover保証は要求していない。

空の隔離DBから、親目的一覧、説明文、子カテゴリ導線、年絞り込み、詳細ページの分類・日付、REST上の標準 `post` 型をブラウザーで検証する。検証は390 / 768 / 1440pxで横はみ出しがないことも確認し、画像と詳細JSONをGit管理外の `local-evidence/news-column-purpose-gap/` へ出す。いずれも架空データで、外部サイトへ書き込まない。

```sh
export HELIX_ARTICLE_DB_PASSWORD="$(openssl rand -hex 24)"
export HELIX_ARTICLE_ROOT_PASSWORD="$(openssl rand -hex 24)"
docker compose -p helix-article-poc-repro-20260926 -f docs/research/2026-09-26-news-column-purpose-gap/compose.yaml up -d
docker compose -p helix-article-poc-repro-20260926 -f docs/research/2026-09-26-news-column-purpose-gap/compose.yaml exec -T -e HELIX_ARTICLE_BASE_URL=http://127.0.0.1:18112 wordpress php < docs/research/2026-09-26-news-column-purpose-gap/seed.php
HELIX_ARTICLE_BASE_URL=http://127.0.0.1:18112 node docs/research/2026-09-26-news-column-purpose-gap/verify.mjs
```

終了後、隔離環境だけを削除するとき:

```sh
docker compose -p helix-article-poc-repro-20260926 -f docs/research/2026-09-26-news-column-purpose-gap/compose.yaml down -v
```

実機PoCで3目的の表示・導線を確認し、`results/verify.json` に実行結果とPoC・テーマ入力のSHA-256を記録する。`npm run article-purpose:verify` はWordPress実機試験を再実行し、結果を生成する。正式な証跡oracleである `npm run article-purpose:proof` は、この実機試験を実行したあと、全ルート・画面幅・名前付き証拠行、および入力ハッシュを照合する。したがって受入証跡の再束縛でも、保存済み結果だけを再検査して実測済みと扱うことはない。カタログの受入証拠への登録後も、記事目的候補はPO承認やG3を意味しない。`WT-AC-ARTICLE-01A` と `WT-AC-ARTICLE-01C` は管理画面操作と負例が未検証のため部分扱いで、`WT-AC-ARTICLE-01B` のPoC範囲のみ検証済みとする。現在の `missing` 集計を実装完成へ読み替えない。

## 判定と境界

記事の目的と投稿型は別の判断である。PoCは共通投稿型に目的分類を持たせた構成で、ニュース一覧は日付・分類から詳細へ移動でき、コラム一覧は編集目的を説明してテーマ別一覧へ移動できる。CPT・URL・管理UI・目的の既定値・Discover掲載保証は未確定のまま残す。

PoCと外部調査は要求候補の根拠であり、候補の採択、全記事型の完成、製品受入、G3承認ではない。
