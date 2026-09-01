# L10 System Acceptance Test Design

本書は L2 candidate から抽出した oracle inventory である。画面合意後に L3 requirement を compile する際の右腕候補であり、
現時点では L10 成果物、pair freeze、G3 到達を主張しない。

| Test ID | Requirement | positive oracle | negative/boundary oracle | evidence |
| --- | --- | --- | --- | --- |
| WT-AT-STRUCT-01 | WT-FR-STRUCT-01 | 既定パターンを別パターンへ差し替えて保存すると描画され、権限エラー・ロック警告が出ない | 構造変更を権限エラーにする旧 ACC-016 / 017 の挙動が残っていれば FAIL | Playwright / site editor |
| WT-AT-STRUCT-02 | WT-FR-STRUCT-02 | AI 経路の投入は Blueprint で宣言した領域だけを書き換える | Blueprint 外の領域を AI 経路が書き換えたら拒否される | REST receipt |
| WT-AT-VALUE-01 | WT-FR-VALUE-01 | 尺度内のプリセット選択は警告なしで保存できる | 破壊域の値（尺度最大超の余白、AA 未満のコントラスト）は保存が止まり、権限で迂回できない | editor + gate JSON |
| WT-AT-VALUE-02 | WT-FR-VALUE-02 | 規則ごとに境界値の内側の値が pass、外側の値が FAIL になる | 境界値未確定の規則は『未確定』として報告し、pass を出さない | gate JSON |
| WT-AT-STYLE-01 | WT-FR-STYLE-01 | 全バリエーションで G-T1b が PASS し、切替後も見出し尺度が単調非増加 | styles/*.json に生 px / rem / em が含まれれば G-T1b が FAIL | G-T1b / G-T3 JSON |
| WT-AT-PARTS-01 | WT-FR-PARTS-01 | 任意のパーツ案へ差し替えても全テンプレートが描画され、G-S2 が PASS | 存在しないパーツを参照するテンプレートは G-S2 が FAIL | G-S2 JSON + Playwright |
| WT-AT-ZONE-01 | WT-FR-ZONE-01 | D-01〜D-07 の各面に対応するパーツ / パターンが存在し、記事と LP で描画される | SP 下部固定領域の積層規約に反する組合せは警告される | Playwright |
| WT-AT-ZONE-02 | WT-FR-ZONE-02 | 語彙にあるゾーン ID を指定した広告が条件に一致するページだけに出る | 語彙外のゾーン ID は schema 検証で拒否される | schema test |
| WT-AT-GATE-01 | WT-FR-GATE-01 | main の全成果物で FAIL=0、生値は baseline 438 以下 | baseline を超える生値の追加は G-T2 が FAIL | gate JSON |
| WT-AT-GATE-02 | WT-FR-GATE-02 | 全 71 パターンが実機で invalid=0 | 静的検査が通るが実機で invalid になるパターンを G-E1 が検出する | G-E1 JSON |
| WT-AT-NFR-GATE-01 | WT-NFR-GATE-01 | 同一 HEAD で 2 回実行した結果の JSON が一致する | 乱数・時刻・外部 API に依存する判定が含まれれば FAIL | digest diff |
| WT-AT-AGENT-01 | WT-FR-AGENT-01 | 同一中間 JSON から生成した markup の digest が一致し、エディタで invalid=0 | schema version 不一致の中間 JSON は write 前に拒否される | adapter fixture |
| WT-AT-AGENT-02 | WT-FR-AGENT-02 | 意味 31 種の意図ノードが対応ブロックへ展開される | 未知のショートコードは破棄されず原文のまま保持される | adapter fixture |
| WT-AT-TR-AGENT-01 | WT-TR-AGENT-01 | A 群の各ルートが OpenAPI 契約と一致する | B 群のルートや外部 AI write 用エンドポイントが存在すれば FAIL | OpenAPI diff |
| WT-AT-MIGRATE-01 | WT-FR-MIGRATE-01 | スナップショット取得後に移管必須キーだけが写像され、見た目キーは対象外として台帳に残る | スナップショットなしの写像は実行前に拒否される | migration receipt |
| WT-AT-MIGRATE-02 | WT-FR-MIGRATE-02 | 代表 6 領域と色 8 スラッグの写像が invalid=0 / G-T1b PASS で成立する | アニメーション・グラデーション角度・スライダーは写像せず台帳に残る | conversion receipt |
| WT-AT-MIGRATE-03 | WT-FR-MIGRATE-03 | 互換キーが未注入なら読み取りは無効で、注入すると読み取れる | 公開本体に第三者固有名が含まれれば公開情報検査が FAIL | public-safety check |
| WT-AT-SEO-01 | WT-FR-SEO-01 | 記事・一覧・FAQ 付き記事で対応する JSON-LD が 1 本ずつ出て、canonical が 404 以外で出る | 同型の JSON-LD が複数出力元から重複して出れば FAIL | JSON-LD extract |
| WT-AT-INTAKE-01 | WT-FR-INTAKE-01 | 台帳の各行が commit・証跡パス・ゲート結果を持つ | 証跡のない行や GRAPHIX-NEO からの逆方向の行は台帳検証が拒否する | ledger validation |
| WT-AT-TR-PLATFORM-01 | WT-TR-PLATFORM-01 | docker WP 7.1 / PHP 8.3 で全テンプレートが描画され G-T1 が PASS | 子テーマや styles が尺度を再定義すれば G-S1 / G-T1b が FAIL | docker matrix |
| WT-AT-NFR-PERF-01 | WT-NFR-PERF-01 | ページ型ごとの転送量が予算内 | 予算超過のページ型があれば性能ゲートが FAIL | transfer size report |
| WT-AT-NFR-A11Y-01 | WT-NFR-A11Y-01 | 全バリエーションでコントラスト AA を満たす | AA 未満の色組合せは破壊域停止の対象になる | axe / contrast |
| WT-AT-NFR-SEC-01 | WT-NFR-SEC-01 | 全ルートが permission_callback を持ち、未認証 GET で Warning 混入が 0 | permission_callback が __return_true のルートや未検証 URL 取得があれば FAIL | REST audit |
| WT-AT-NFR-REL-01 | WT-NFR-REL-01 | 描画前後で option / theme_mods の差分が 0、末尾スラッシュ有無で正規化リダイレクトが働く | 描画中の set_theme_mod / update_option 呼び出しがあれば FAIL | option diff |
| WT-AT-NFR-PRIV-01 | WT-NFR-PRIV-01 | 計測イベントに個人識別子が含まれない | IP・UA 生値・cookie 値の保存があれば FAIL | event schema |
| WT-AT-NFR-PERM-01 | WT-NFR-PERM-01 | capability のないユーザーはテンプレート編集に到達できない | 管理者が破壊域の値を保存できれば FAIL | capability test |
| WT-AT-NFR-COST-01 | WT-NFR-COST-01 | テーマ / プラグインの依存に外部 API キーを要するものがない | 外部デザインツールの取り込み経路が復活すれば FAIL | dependency audit |
| WT-AT-NFR-LEGAL-01 | WT-NFR-LEGAL-01 | 開示フラグ付き記事で 3 層の開示が出る | 開示フラグなしの記事に開示が出れば FAIL | render fixture |
| WT-AT-NFR-OBS-01 | WT-NFR-OBS-01 | PR ごとにゲート JSON が HEAD に束縛されて残る | 証跡のない完了主張は受け付けない | CI artifact |
| WT-AT-NFR-REC-01 | WT-NFR-REC-01 | dry-run が差分だけを返し、apply 後の rollback で digest が元に戻る | rollback 情報のない apply は拒否される | rollback receipt |
| WT-AT-NFR-CRED-01 | WT-NFR-CRED-01 | 公開情報検査と伏せ字ガードが commit 前に PASS | 固有名・絶対パス・credential パターンが含まれれば検査が FAIL | public-safety check |
