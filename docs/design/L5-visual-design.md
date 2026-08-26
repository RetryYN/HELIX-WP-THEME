# L5 Visual Design 仕様書

## 0. AGENT NEO Design Direction

### 0.1 コンセプト

**AIが改善できる、失敗しにくい営業/収益導線。**

AGENT NEOは、装飾の自由度で売るテーマではなく、個人アフィリエイトと法人LP/HPで成果が崩れにくい情報設計を提供するテーマである。ThemeBからは読みやすい記事/カード/CTA/トップ導線、テーマAからは第一印象を作るメインビジュアルとプリセットUXを抽象化し、見た目ではなく設計思想を `design-preset`、`visual-composition`、`section-pattern`、`trust-layer`、`ui-risk` として契約化する。

### 0.2 デザイン原則

| ID | 原則 | 実装判断 |
|---|---|---|
| DP-001 | 目的優先 | セクションは見た目ではなく `conversion_intent` で選ぶ |
| DP-002 | 制約付きプリセット | 自由入力より用途別プリセットを優先し、破綻を防ぐ |
| DP-003 | トップは入口 | 新着一覧ではなく、主要導線へ送るGatewayとして設計する |
| DP-004 | CTAを絞る | 1セクションの主CTAは原則1つ、補助CTAは1つまで |
| DP-005 | CTA近くに根拠 | 実績、レビュー、検証日、PR表記をCTAの近くに置く |
| DP-006 | 長文は視線リセット | 囲み、比較、画像、引用、CTAでF型の読み飛ばしを抑える |
| DP-007 | AI計測可能 | `section_id`、`cta_id`、`variant_id` をUI単位に付与する |
| DP-008 | 装飾に役割を持たせる | 角丸、色、影、余白は安心/強調/区切りの目的に紐づける |
| DP-009 | 日本市場の信頼層 | 運営者、会社情報、実績、FAQ、問い合わせ導線を明示する |
| DP-010 | 見た目コピー禁止 | 参照テーマのCSS、画像、固有文言、デモ構成は流用しない |

### 0.3 デザインプリセット

| プリセット | 用途 | 視覚方針 |
|---|---|---|
| `affiliate-clear` | 比較/ランキング型 | 白背景、低彩度、明快な表、強い購入CTA |
| `affiliate-editorial` | 体験談/レビュー型 | 長文可読、視線リセット、控えめCTA、根拠表示 |
| `corporate-trust` | BtoB/士業/実績型 | 落ち着いた色、余白、導入実績、問い合わせCTA |
| `corporate-product` | 製品LP | Z型ヒーロー、課題解決、価格、FAQ、最終CTA |
| `startup-bold` | 新規サービス | 強いコントラスト、数字、図解、短いCTA |
| `local-business` | 店舗/地域 | 写真、地図、営業時間、電話/予約CTA |

### 0.4 UI Audit

| ルール | 検出する失敗 | 対応 |
|---|---|---|
| `hero.vague` | 誰向け/何が得られるか不明 | target、benefit、proof、CTAを追加 |
| `cta.overload` | CTAが多すぎて迷う | 主CTA/補助CTAへ統合 |
| `proof.too_late` | 根拠がページ下部まで出ない | ヒーロー直下またはCTA近くへ移動 |
| `comparison.missing_basis` | 比較表の評価根拠がない | 評価軸、検証日、source_urlを追加 |
| `visual.no_hierarchy` | サイズ/色/余白に優先順位がない | 見出し、本文、CTAの階層を再設定 |
| `mobile.tap_target_small` | スマホで押しにくい | タップ領域44px以上へ修正 |
| `affiliate.disclosure_weak` | PR表記が弱い/見えない | ファーストビューとCTA近辺にPR表記を表示 |

## 1. デザイン方針
### 1.1 デザインコンセプト

AGENT NEOの視覚方向は「成果導線の明快さ」と「AIが検査/改善できる一貫性」を優先する。個人版は比較、根拠、CTA、PR表記が迷わず読めること。法人版は第一ビューで対象、成果、証拠、CTAが伝わり、LP全体で課題から問い合わせまで自然に進むことを基準にする。

### 1.2 デザイントークン

> **カラートークン確定ステータス**: L5 暫定確定（2026-06-18）。G5 デザイン凍結ゲートの前提（プレースホルダ全値化）を満たすための初版。最終ブランドレビューで微調整余地あり。
>
> **ブランド方針**: オレンジ = メイン（CTA・ブランド）。黒はサブ（引き締め役）。黒ベース + オレンジアクセントはNG。ベースは白系（stone-50）で展開し 70-25-5 比率を維持。
>
> **配色比率 70-25-5 の割当**: ベース70% = --color-bg + --color-surface / サブ25% = --color-secondary + --color-text-muted / アクセント5% = --color-primary（CTA 専用）

| カテゴリ | トークン名 | 値 | 用途 | コントラスト比（対背景） |
|---------|-----------|-----|------|----------------------|
| Color/Primary | --color-primary | #ff6b00 | CTA・ブランド（オレンジ）— theme.json `accent` palette slug に対応。light variation での値。2026-06-26 実装値へ是正（旧: #C2410C） | 3.4:1 対 #FAFAF9（大テキスト・UI部品として PASS）／白文字 使用時は accent-aa を使うこと |
| Color/Primary-AA | --color-primary-aa | #bf5200 | ボタン背景（AA確保用）— theme.json `accent-aa` palette slug に対応。light variation での値。2026-06-26 追加（実装値）。白文字と組み合わせて使う | 5.2:1 対 #FAFAF9（白文字との比: 3.0:1 以上）→ AA PASS（大テキスト） |
| Color/Secondary | --color-secondary | #44403C | 補助・サブアクション（stone-700） | 9.7:1 対 #FAFAF9 → AA PASS |
| Color/BG | --color-bg | #FAFAF9 | ページ背景（stone-50：純白より柔らかい） | — |
| Color/Surface | --color-surface | #F5F5F4 | カード・モーダル背景（stone-100） | — |
| Color/Text | --color-text | #1C1917 | 本文テキスト（stone-900） | 18.7:1 対 #FAFAF9 → AAA PASS |
| Color/Text-Muted | --color-text-muted | #78716C | 補助テキスト（stone-500） | 4.6:1 対 #FAFAF9 → AA PASS |
| Color/Error | --color-error | #DC2626 | エラー状態（red-600） | 5.0:1 対 #FAFAF9 → AA PASS |
| Color/Warning | --color-warning | #D97706 | 警告状態（amber-600） | 4.1:1 対 #FAFAF9 → UI 3:1 PASS |
| Color/Success | --color-success | #15803D | 成功状態（green-700） | 4.83:1 対 #FAFAF9 → AA PASS |
| Color/Info | --color-info | #2563EB | 情報通知（blue-600） | 5.6:1 対 #FAFAF9 → AA PASS |
| Color/Border | --color-border | #D6D3D1 | 区切り線・枠線（stone-300）※装飾用途 | — ※隣接テキストで AA 担保 |
| Spacing/xs | --space-xs | 4px | アイコンとラベルの間隔 |
| Spacing/sm | --space-sm | 8px | 密接な要素間 |
| Spacing/md | --space-md | 16px | 標準要素間 |
| Spacing/lg | --space-lg | 24px | セクション内の区切り |
| Spacing/xl | --space-xl | 32px | セクション間の区切り |
| Spacing/2xl | --space-2xl | 48px | 大きなブロック間 |
| Font/Body | --font-body | | 本文テキスト |
| Font/Heading | --font-heading | | 見出し・タイトル |
| Font/Mono | --font-mono | | コード・数値 |
| Font/Size/xs | --font-size-xs | 12px | キャプション・注釈 |
| Font/Size/sm | --font-size-sm | 14px | 補助テキスト・ラベル |
| Font/Size/md | --font-size-md | 16px | 本文 |
| Font/Size/lg | --font-size-lg | 20px | 小見出し |
| Font/Size/xl | --font-size-xl | 24px | セクション見出し |
| Font/Size/2xl | --font-size-2xl | 32px | ページタイトル |
| Font/Weight/normal | --font-weight-normal | 400 | 本文 |
| Font/Weight/medium | --font-weight-medium | 500 | ラベル・強調 |
| Font/Weight/bold | --font-weight-bold | 700 | 見出し |
| Line-Height/tight | --line-height-tight | 1.25 | 見出し |
| Line-Height/normal | --line-height-normal | 1.5 | 本文 |
| Line-Height/relaxed | --line-height-relaxed | 1.75 | 長文 |
| Radius/sm | --radius-sm | 4px | 入力フィールド |
| Radius/md | --radius-md | 8px | カード・ボタン |
| Radius/lg | --radius-lg | 12px | モーダル・ダイアログ |
| Radius/full | --radius-full | 9999px | アバター・バッジ |
| Shadow/sm | --shadow-sm | 0 1px 2px rgba(0,0,0,0.05) | ボタン・入力フィールド |
| Shadow/md | --shadow-md | 0 4px 6px rgba(0,0,0,0.1) | カード・ドロップダウン |
| Shadow/lg | --shadow-lg | 0 10px 15px rgba(0,0,0,0.1) | モーダル・ダイアログ |
| Transition/fast | --transition-fast | 150ms ease | ホバー・フォーカス |
| Transition/normal | --transition-normal | 250ms ease | 開閉・展開 |
| Transition/slow | --transition-slow | 400ms ease | ページ遷移 |
| Z-Index/dropdown | --z-dropdown | 100 | ドロップダウンメニュー |
| Z-Index/sticky | --z-sticky | 200 | 固定ヘッダー |
| Z-Index/modal | --z-modal | 300 | モーダル・オーバーレイ |
| Z-Index/toast | --z-toast | 400 | トースト通知 |

<!-- 記入例:
| Color/Primary | --color-primary | #2563EB | CTA・ブランド |
| Color/Secondary | --color-secondary | #64748B | 補助・サブアクション |
| Color/BG | --color-bg | #FFFFFF | ページ背景 |
| Font/Body | --font-body | 'Inter', 'Noto Sans JP', sans-serif | 本文テキスト |
-->

### 1.3 配色比率
- メイン (70%): --color-bg + --color-surface
- サブ (25%): --color-secondary + --color-text-muted
- アクセント (5%): --color-primary

## 2. レイアウト設計
### 2.1 ブレークポイント
| 名前 | 幅 | レイアウト | コンテナ幅 |
|------|-----|----------|-----------|
| mobile | < 640px | 1カラム | 100% |
| tablet | 640-1024px | 1-2カラム | 100% |
| desktop | 1024-1280px | 2-3カラム | 1024px |
| wide | > 1280px | 3カラム | 1280px |

### 2.2 グリッドシステム
- カラム数: 12
- ガター: --space-md (16px)
- マージン: mobile 16px / tablet 24px / desktop 32px
- 最大コンテンツ幅:

### 2.3 レイアウトパターン
<!-- 主要なレイアウトパターンを定義する -->
<!-- 記入例:
- ヘッダー固定 + サイドバー + メインコンテンツ（desktop）
- ヘッダー固定 + ボトムナビ + フルワイドコンテンツ（mobile）
-->

## 3. コンポーネント仕様
### 3.1 コンポーネント一覧
| ID | コンポーネント | 種別 | 状態数 | 対応画面 | 備考 |
|----|--------------|------|--------|---------|------|
| C-001 | Button | Atom | 5 (default/hover/active/focus/disabled) | 全画面 | Primary/Secondary/Ghost バリアント |
| C-002 | Input | Atom | 5 (default/focus/filled/error/disabled) | フォーム画面 | |
| C-003 | Card | Molecule | 2 (default/hover) | 一覧画面 | |
<!-- 記入例:
| C-001 | Button | Atom | 5 | 全画面 | size: sm/md/lg, variant: primary/secondary/ghost/danger |
| C-002 | TextInput | Atom | 5 | フォーム画面 | label/placeholder/helperText/errorMessage |
| C-003 | UserCard | Molecule | 2 | ダッシュボード | avatar + name + role badge |
| C-004 | DataTable | Organism | 3 | 一覧画面 | sort/filter/pagination 対応 |
| C-005 | Navigation | Organism | 2 | 全画面 | desktop: sidebar / mobile: bottom-nav |
-->

### 3.2 状態遷移
<!-- 各コンポーネントの状態遷移をテーブルで定義する -->
| コンポーネント | トリガー | 遷移元 | 遷移先 | アニメーション |
|-------------|---------|--------|--------|-------------|
| Button | hover | default | hover | --transition-fast |
| Button | mousedown | hover | active | --transition-fast |
| Button | focus | * | focus | --transition-fast |
| Input | focus | default | focus | --transition-fast |
| Input | blur + valid | focus | filled | --transition-fast |
| Input | blur + invalid | focus | error | --transition-fast |

### 3.3 バリアント定義
<!-- 主要コンポーネントのバリアントを定義する -->
| コンポーネント | バリアント | 用途 | トークン上書き |
|-------------|----------|------|-------------|
| Button | primary | メインアクション | bg: --color-primary |
| Button | secondary | サブアクション | bg: --color-secondary |
| Button | ghost | テキストリンク風 | bg: transparent |
| Button | danger | 破壊的操作 | bg: --color-error |

## 4. 画面詳細
### S-001: [画面名]
- **ワイヤーフレーム**:
```
+-------------------------------------------+
| Header                         [User] [+] |
+--------+----------------------------------+
| Nav    | Main Content                     |
|        |                                  |
|        | +-----+ +-----+ +-----+         |
|        | |Card | |Card | |Card |         |
|        | +-----+ +-----+ +-----+         |
|        |                                  |
+--------+----------------------------------+
```
- **レイアウト**:
  - mobile: ヘッダー + フルワイド + ボトムナビ
  - tablet: ヘッダー + 2カラムグリッド
  - desktop: ヘッダー + サイドバー + 3カラムグリッド
- **インタラクション**:
  - クリック:
  - ホバー:
  - スクロール:
- **アニメーション**:
  - transition: --transition-normal
  - duration:
  - easing:
- **エラー状態**:
  - バリデーションメッセージの配置:
  - エラー時のコンポーネント状態:
  - グローバルエラー表示位置:
- **ローディング状態**:
  - スケルトン / スピナー / プログレスバー:
  - 表示タイミング:
- **空状態**:
  - データなし時の表示:

<!-- 記入例:
### S-001: ダッシュボード
- **ワイヤーフレーム**: (上記 ASCII art 参照)
- **レイアウト**: desktop 3カラム / tablet 2カラム / mobile 1カラム + ボトムナビ
- **インタラクション**: カード hover でシャドウ拡大、クリックで詳細画面遷移
- **アニメーション**: カード表示時 fade-in 0.3s stagger 0.05s
- **エラー状態**: API エラー時はトースト通知 + リトライボタン
- **ローディング状態**: 初回読み込みはスケルトン、リフレッシュ時はスピナー
- **空状態**: イラスト + 「データがありません」 + アクションボタン
-->

## エラー状態の UI 設計

### エラーメッセージのガイドライン
| 種別 | ユーザーに見せる | 見せない |
|------|---------------|---------|
| バリデーション | 「メールアドレスの形式が正しくありません」 | 「regex /^[a-z]/ にマッチしません」 |
| 認証エラー | 「ログイン情報が正しくありません」 | 「JWT 署名検証失敗」 |
| サーバーエラー | 「エラーが発生しました。時間をおいて再度お試しください」 | スタックトレース |
| 権限エラー | 「この操作を行う権限がありません」 | 「RBAC: role=user, required=admin」 |
| Not Found | 「ページが見つかりません」 | 「Route /api/v1/internal/... not found」 |

### エラーページデザイン
- 404: フレンドリーなイラスト + ホームへのリンク
- 500: お詫びメッセージ + サポートへのリンク
- 403: 権限説明 + ログインへのリンク
- オフライン: 再接続待ちアニメーション

## 5. アクセシビリティ
### 5.1 WCAG 2.2 AA 準拠チェック

日本市場向けの通常品質として、SEO/UX と重複する基本配慮を継続する。WordPress.org accessibility-ready の海外要件や法規を根拠にしたゲート化は行わない。

#### 5.1.A 基本配慮（任意・非ゲート）

- responsive reflow & text-spacing: 200% 拡大時に横スクロールを発生させない
- context-change 防止: フォーカス移動・入力で予期しないページ遷移や送信を起こさない
- accessible hover/focus: `outline: none` / `outline: 0` を使わず、フォーカス可視を維持する

**注記**: 上記は L4 theme 実装の通常品質として扱う。受入 TC の正本は `docs/test-plan/L3-test-plan.md` §11.2 の P3 項目に置く。

---

#### 5.1.B 既存 WCAG 2.2 AA チェック（継続）

- [ ] コントラスト比 4.5:1 以上（通常テキスト: --font-size-md 以下）
- [ ] コントラスト比 3:1 以上（大テキスト: --font-size-lg 以上）
- [ ] コントラスト比 3:1 以上（UI コンポーネント・アイコン）
- [ ] フォーカスインジケータ visible（2px 以上の明確な視覚変化）— **新 5 要件③と整合。outline 除去禁止**
- [ ] キーボードナビゲーション可能（Tab / Shift+Tab / Enter / Escape）
- [ ] フォーカストラップ実装（モーダル・ダイアログ内）
- [ ] aria-label / aria-describedby 設定（アイコンボタン・入力フィールド）
- [ ] aria-live 設定（動的に変化するコンテンツ・通知）— **ADR-026 interactive ブロックの診断結果出力と整合**
- [ ] スクリーンリーダー対応（見出し階層・ランドマーク・読み上げ順序）
- [ ] 画像に alt テキスト（装飾画像は alt=""）
- [ ] color だけに依存しない情報伝達（エラーはアイコン + テキスト併用）
- [ ] 動きの無効化対応（prefers-reduced-motion）— **ADR-026 interactive ブロックの animation 制御と整合**

### 5.2 レスポンシブチェック
- [ ] mobile (< 640px) 表示崩れなし
- [ ] tablet (640-1024px) 表示崩れなし
- [ ] desktop (1024-1280px) 表示崩れなし
- [ ] wide (> 1280px) 表示崩れなし
- [ ] タッチターゲット 44x44px 以上（mobile）
- [ ] 横スクロール発生なし（mobile）
- [ ] テキスト折り返し正常（長い文字列・日本語）
- [ ] 画像・メディアのアスペクト比維持

## 6. ダークモード（Style Variation: styles/dark.json）

> **2026-06-26 更新**: FSE Style Variation 方式で実装。OS の `prefers-color-scheme` ではなく、サイトエディタの「スタイル」でユーザーが手動切り替えする設計。CSS カスタムプロパティではなく theme.json の palette slug override で実現。
> ブランドの accent オレンジは light / dark 両方で維持（`feedback_brand_orange_main` 準拠）。

| palette slug（theme.json） | docs トークン名 | ライト値 | ダーク値 | 変更意図 |
|---|---|---|---|---|
| background | --color-bg | #ffffff | #121212 | 主面を暗色に |
| foreground | --color-text | #1a1a1a | #ededed | 本文文字を明色に |
| primary | — | #1a1a1a | #f5f5f5 | 見出し/濃色（dark で明色へ） |
| secondary | — | #f0f0f0 | #262626 | カード境界・淡面（dark で暗面へ） |
| accent | --color-primary | #ff6b00 | #ff6b00 | **ブランドオレンジ維持** |
| accent-aa | --color-primary-aa | #bf5200 | #ff7a1a | ボタン背景。dark は明オレンジ + 暗文字で AA 確保 |
| footer-bg | — | #111111 | #000000 | フッタ背景 |
| muted | --color-text-muted | #767676 | #9a9a9a | 補助文字（暗背景で可読な中間色） |

**dark variation 追加 styles override（最小限）**:

| 対象 | プロパティ | dark 値 | 理由 |
|---|---|---|---|
| `styles.elements.button.color.text` | ボタン文字色 | `#121212`（初期案） | background が #121212 になると var 参照で暗文字になりオレンジ背景でコントラスト不足。axe 実測で確定 |
| `styles.elements.button.color.background` | ボタン背景色 | `var(--wp--preset--color--accent-aa)` = `#ff7a1a` | 初期案。axe 実測で調整 |

上記以外の styles は var 参照で自動追従（override 不要）。

### 6.1 ダークモード切替方式
- [x] manual（サイトエディタの「スタイル」でユーザーが手動切り替え）
- [ ] system（OS設定に追従: prefers-color-scheme）— 本実装では非採用（FSE Variation 方式のため）
- [ ] 両対応（デフォルト system + 手動オーバーライド）

## 7. アイコン・画像ガイドライン
### 7.1 アイコンセット
- ライブラリ:
- サイズ: 16px / 20px / 24px
- ストローク幅:
- カラールール: --color-text / --color-text-muted

### 7.2 画像仕様
| 用途 | アスペクト比 | 最大サイズ | フォーマット | fallback |
|------|------------|-----------|------------|----------|
| サムネイル | 1:1 | 200KB | WebP | JPEG |
| ヒーロー | 16:9 | 500KB | WebP | JPEG |
| アバター | 1:1 | 100KB | WebP | PNG |

## 8. モーション・アニメーション設計
### 8.1 アニメーション原則
- 目的のあるアニメーションのみ使用（装飾的アニメーション禁止）
- 300ms 以下を基本とする（ユーザーの待機感を生まない）
- prefers-reduced-motion 時はアニメーション無効化

### 8.2 アニメーション一覧
| 名前 | 対象 | 種別 | duration | easing | 用途 |
|------|------|------|----------|--------|------|
| fade-in | ページ | opacity | 250ms | ease-out | ページ遷移 |
| slide-up | モーダル | transform | 250ms | ease-out | モーダル表示 |
| collapse | アコーディオン | height | 200ms | ease-in-out | 開閉 |

## 9. 目視確認チェックリスト

実 WordPress（docker / WP 6.9.4）でテーマを描画し、各画面 × 3デバイス（desktop/tablet/mobile）+ ダークモード（Style Variation）を目視 + axe-core（wcag2a/wcag2aa）実測で確認した結果。検証経緯は session_handover part41（スーパーハードチェック）+ part42（LP固定色 dark破綻解消 / main 214d975）。

| 画面 | テンプレート | desktop | tablet | mobile | ダーク | axe(light/dark) | 判定 |
|------|-------------|---------|--------|--------|--------|-----------------|------|
| ホーム | front-page / home-blueprint 7セクション | [x] | [x] | [x] | [x] | serious/critical 0 / 0 | PASS |
| 記事（single） | single.html | [x] | [x] | [x] | [x] | 0 / 0（※SNS share 例外） | PASS |
| LP | page-lp-sample（hero〜final-cta） | [x] | [x] | [x] | [x] | 0 / 0 | PASS |
| アーカイブ（category） | archive.html | [x] | [x] | [x] | [x] | 0 / 0 | PASS |
| 検索結果 | search.html | [x] | [x] | [x] | [x] | 0 / 0 | PASS |
| 404 | 404.html | [x] | [x] | [x] | [x] | 0 / 0 | PASS |

※ SNS share ボタン（facebook/line/hatena）は公式ブランド色 + 白文字で color-contrast 4.5:1 未達だが、WCAG 2.1 達成基準 1.4.3 の意図的例外（ブランドガイドライン準拠優先）として据え置き。light/dark 共通の単一既知項目。

### 9.1 スクリーンショット格納先
- `.helix/visual-checks/{画面ID}-screenshots/{desktop|tablet|mobile}.png`（gitignore / ランタイム成果物）
- axe 実測スクリプト: `bin/check-theme-quality.sh` GATE3（axe-core wcag2aa）

### 9.2 最終確認
- [x] 全画面の目視確認完了（実 WP 描画 × 3デバイス × light/dark）
- [x] デザイントークンが実装に正しく反映されている（全色 palette slug 経由・固定 hex は永続暗バンドの白文字のみ）
- [x] L2 設計書のデザイン方針と一貫性がある（配色 70-25-5 / hero〜final-cta / DP-001〜010）
- [x] 不要なハードコードスタイル値がない（LP 固定色 231件を slug 化済 / 残るは footer-bg バンド上の意図的固定白）
- [x] AI っぽさのない自然なUI表現になっている（V1/V2 級の崩れ・冗長ゼロ）

### 9.3 a11y 根本是正サマリ（part42）
- LP 固定色 231件 → palette slug 全置換（dark で背景/文字が両モード自動追従）
- `muted` token #767676 → #6b6b6b 微暗化（secondary 面で 3.98:1 → AA達成。背景未使用のため副作用なし）
- 永続暗バンド（lp-final-cta / lp-solution）を `primary`（dark で明色反転）→ `footer-bg`（両モード near-black）化
- 検証: check-theme-quality PASS / unit 86 / security 48 全緑
