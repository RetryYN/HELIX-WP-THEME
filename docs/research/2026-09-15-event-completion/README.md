# イベントの完成構成を比較する

既存のセミナー2構成・カンファレンス・地域イベント・キャンペーンを、heroからfooterまでの完成画面としてPC/SPで比較する。既定設定や要求の上限は変更しない。完成構成は選定用のPoCであり、予約・応募・外部送信・全管理UIの完成を意味しない。

## 視覚と選択導線

- 開催情報を項目と値の対応が崩れない配置にし、日時・会場・対象・料金を読み取りやすくする。
- 写真heroの文字を不透明パネルで保護する。見出しは共通hero尺度を使い、長文は隠さず折り返す。
- 登壇者は写真、名前、肩書き、説明を左から読み進める配置とし、写真なし・説明量の差でも同じ行のカードを揃える。
- タイムテーブルの時刻列を確保する。SPの長文と単独プロフィールを1列で読む。
- 固定CTAを押した後の申込区間と、footer末尾の導線が見える余白を確保する。
- カタログで完成5候補を最初に示し、用途・申込方式・会場案内・区間順・共通部品の所属を比較表に表示する。部品と受付状態の候補へ戻れる。

## 要求と証拠境界

主対象は `WT-FR-EVENT-01` / `WT-AC-EVENT-01A/B`。`WT-FR-PARTS-03` / `WT-AC-PARTS-03A/B`、`WT-FR-FORM-01` / `WT-AC-FORM-01A/B`、`WT-FR-LOOK-01` / `WT-AC-LOOK-01C/D/E`、`WT-NFR-SP-01` / `WT-AC-NFR-SP-01A/B`へevent範囲の証拠を追加する。全体ACを自動昇格しない。

`choices.json`は5構成と選択理由。`verification.json`は全event宣言を各1回以上、PC1440/SP375・JS有無で検査する。全直積ではない。完成5構成ではDOMの区間順と比較メタデータを照合し、長文見出し・写真なし登壇者・長い紹介文・カード行高さ・横溢れ・固定footerを検査する。

受付前・受付開始・満席・締切は時刻と定員を持つ専用fixtureで4状態を読み、状態ラベル、フォーム有無、申込区間への到達を検査する。既存 `verify-event-state.mjs` と `verify-event-boundaries.mjs` のPOST拒否・時刻入力境界を置き換えるものではない。

before画像は現在の本文とパターンを使い、`event-completion.css`のHTTP応答だけを空にする。同内容・同幅のCSS比較であり、旧コミット全体の比較ではない。full-page画像の固定CTAは撮影viewportの下端位置へ1回描かれる。実際の固定位置はfooter到達の寸法検査で判定する。

## 実行

専用HELIX Content Labが起動した状態で、他のDB変更を伴う検証と直列実行する。

```sh
node scripts/verify-home-pattern-reuse.mjs
node scripts/verify-event-completion.mjs --baseline --finished-only
node scripts/verify-event-completion.mjs
node scripts/build-selection-catalog.mjs
npx playwright test tests/e2e/selection-catalog.spec.ts tests/e2e/home-selection-catalog.spec.ts tests/e2e/event-selection-catalog.spec.ts --workers=1
```

event検証器はblognameと予約slug不在を確認し、作成IDにUUID所有markerを付ける。fixture mode optionは存在有無を含めてsnapshotし、finallyで復元。作成IDの所有を再確認して削除し、予約slug不在を検査する。強制終了時のlockが残った場合は自動再作成せず停止する。記録PIDが終了したこと、所有IDとoption snapshotを確認したうえで回収する。

カタログ生成器は完成画像hash・source digest・必須検査行を照合する。既存HOMEなどの証拠が古いと生成を止める。既存証拠を再実行せずdigestだけ付け替えない。

## 入力と未実証

既存 `2026-09-05-parts-pattern-taxonomy/home-event-recapture` と `home-event-recapture-v2` の観察を使う。5構成は既存の選択肢であり、新たな外部サイトの統計調査を完了したとは扱わない。再利用モデルは[WordPress Patterns](https://developer.wordpress.org/themes/patterns/)（2026-09-15確認）とも照合した。公開一次情報の再確認で見つけた複数日・現地＋配信・別登録・一覧絞り込みは [EVENT 追調査と要求候補](external-observations.md) に分離し、現バッチの達成範囲へ混ぜない。

全style variationのコントラスト、全Site Editor設定の保存・再入場、設定JSON/MCP往復、任意第三者template、利用者の画像や埋込み、実地図接続・実申込、全sidebar組合せ、地域TZ/DST、残席閾値・取消／中止の業務契約は未完了。reduced-motionを指定した表示検査は行うが、全animation/timerの静的監査は既存専用verifierの再実行が必要。

## 親検収への引継ぎ

通常固定ページでのHOME再利用は45検査成功（写真上パネルのbefore/after、PC/SP×JS有無、固定footer補償、非HOME2面非波及）。EVENTの比較基準は206検査成功、同条件10画像を保存した。PHP構文は変更2ファイル成功、event.phpのWordPress-Core PHPCSはerrors 0 / warnings 0、POT整合・翻訳境界14行成功。

`functions.php`等の変更により既存43 ACがstaleとなり、カタログ生成器は停止した。`regression-pending.json`は未解消一覧である。親の独立回帰検証後にのみ生成器を実行し、既存2組と追加EVENTのカタログE2Eを実行する。現時点の生成済みカタログ件数を増えたものとして報告しない。

最終EVENT検証は **2,004/2,004 PASS**。beforeは **206/206 PASS**。終了後に予約slug2件が不在、event lock不在、fixture modeが開始前の文字列`1`へ復元されていることをCLIで再確認した。DBレーンは解放済み。カタログ最終生成とE2Eは既存43 ACの独立回帰再取得後に実行する。
