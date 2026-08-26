# THEME-INV-13: テーマA の未認証 REST エンドポイント 2 本の到達性と対処を確定する

labels: security, investigation, priority:high, po-decision
depends: なし（本番稼働サイトの話なので他に先行する）

> **状態: 一次完了 / PO 承認待ち**（2026-08-26）／レポート: `../reports/INV-13-themeA-rest-endpoints.md`
> ルート登録とコールバック本体を全文採取。**新事実**: `post_by_url` はブログカード（実使用 330）が
> `rest_do_request()` で**内部ディスパッチ**しており、`rest_endpoints` での除去は描画を壊す。
> **サーバ層で HTTP 経由のみ遮断すれば内部呼び出しは通る**。対処案 4 つを比較済み。
> **残**: 到達性の実証（自サイトへの HTTP GET・**PO 承認が要る**）とベンダー報告の要否。

## 背景（コード実測 — `10-reverse-themeA.md` §7）
`include/custom-functions.php` が `rest_api_init` で 2 本のルートを登録している。

| ルート | permission_callback | 実装 |
|---|---|---|
| `themeA/post_by_url` | `__return_true` | `url_to_postid()` → タイトル・サムネ・カテゴリを返す |
| `themeA/external_url` | `__return_true` | クエリ `url` を検証せず `file_get_contents()` し、og: メタを正規表現抽出して返す |

`themeA/external_url` は**未認証で任意 URL をサーバーに取得させられる形**（SSRF）。
内部ネットワークやクラウドメタデータへの到達、ポートスキャン、内部サービスの応答の外部漏洩に繋がりうる。
`themeA/post_by_url` も未認証で、非公開・下書き記事の存在推定に使える余地がある。

本調査はファイル読み取りのみで、**実際のリクエスト送出による到達性検証は行っていない**。

## 調査項目
1. site-A.example で当該ルートが実際に応答するか（**PO 承認の上で**自サイトに対してのみ確認）
2. サーバー側で外向き通信・内部宛通信が実際に可能か（XServer の制約で緩和されている可能性）
3. 既存の防御層の有無（`cloudsecure-wp-security` は site-B 側にのみ導入。topic-A には無い）
4. 対処案の比較:
   - WAF / セキュリティプラグインでルート遮断（テーマ更新で消えない）
   - 子テーマから `rest_endpoints` フィルタでルート除去
   - テーマ改変（更新で消える・非推奨）
5. ブロック `themeA-blocks/blogcard` の外部リンクモードがこのルートに依存しているか
   （依存する場合、遮断すると編集画面の機能が落ちる → 影響範囲の確認）

## 完了条件
- [ ] 到達性が証跡付きで確定している（自サイトのみ・PO 承認済み）
- [ ] 影響を受ける編集機能が特定されている
- [ ] 対処案が影響と手間つきで比較され、PO 判断へ上申されている

## 注記
テーマベンダー（ベンダーA）への報告要否も PO 判断事項。本イシューでは事実確認までを行う。
