# 鍵管理の表示契約 PoC（WT-FR-ADMIN-04）

読み取り用・書き込み用・失効後の３候補。実鍵は発行しない。認証に使えない連番のデモ文字列だけで、一度限り表示・個別失効・権限分離を静的/ローカルに比較する。２ACはpartialであり、G3完了・実API実装を主張しない。

## 境界

Application Passwords既存APIを、読み取り用/書き込み用の専用ユーザーと専用ロールに分けて薄く包む想定。独自認証や独自の実鍵生成ではない。鍵単体に独立した任意scopeがあるとは主張せず、実ユーザーのcapabilityとREST permission checksが必要な境界として残す。

本PoCはその表示契約のみ。実Application Passwords API、実ロール/権限、hashed保存、実鍵の作成時だけの返却、MCP、本番WP、外部APIは未接続/未検証。WP 7.2未提供境界のため実機互換は主張しない。実鍵・秘密・運用ユーザー情報をfixtureに持ち込まない。

## 状態

- 発行結果の無効なデモ値は、発行直後のDOMだけに表示する。接続レジストリはID/権限/候補ロール/状態のみで、値を保持しない。
- 表示を閉じる・接続切替・失効・pagehideで値の欄を空にする。同じ接続の再表示は常に拒否する。表示中の重複発行も止める。
- 発行後の権限はレコードに固定。次の発行用ピッカーやDOMの権限表示を変えても、既存レコードの権限は変わらない。
- 読み取り用はwrite拒否、書き込み用はread/write許可。失効後はどちらも拒否し、他の接続には影響しない。すべてローカル判定である。
- 発行/失効の失敗を一度だけ注入できる。発行失敗は値/レコードを作らず、失効失敗は有効状態を維持して再試行する。
- ページメモリ以外の保存なし。localStorage/sessionStorage/cookie・外部送信・console・操作ログへデモ値を記録しない。再読込でレコードも初期化。セキュアなメモリ消去や実鍵の安全性を検証した主張ではない。
- 公開画像は表示値を消した後にだけ生成する。操作ログとカタログには値を含めない。クリップボードAPIを呼ばず、「値を選択」はテキスト選択のみ。

## ACとの対応

| AC | 代表実測 | 未検証 |
|---|---|---|
| 04A | 発行成功/失敗・一度表示・読み取り/書き込み・個別失効・失効失敗の再試行 | 実APIの発行/失効・専用ユーザー/ロール・保存・WP7.2 |
| 04B | 再表示迂回拒否・失効後の拒否・権限表示の改変でも分離・値の非保存/非送信 | 実鍵漏洩/ロール/REST permission checks・hashed保存・全環境 |

PC1440/SP390/320、JS有無、keyboard、200% root文字を検査する。200%はroot font-size 32pxでありブラウザズームではない。実スクリーンリーダー・全ブラウザは未検証。JS無効時は方針と権限の閲覧のみ。

## 再現と根拠

`node scripts/build-admin-keys-poc.mjs` でHTML生成、`node scripts/verify-admin-keys-poc.mjs` でE2E・６画像・候補・partial admission入力を再生成。`node scripts/utility-poc-server.mjs` の静的配信を使用し、`/docs/research/2026-09-20-admin-keys-poc/readonly.html` を開く。utility処理APIは呼ばない。

要求正本は WT-FR-ADMIN-04 revision 1 と WT-AC-ADMIN-04A/B。既存admin-settings/admin-changes PoCの生成/証跡契約を踏襲する。

[Application Passwords公式](https://developer.wordpress.org/advanced-administration/security/application-passwords/)は、ユーザーに結び付く認証、作成時の一度限り表示、hashed保存、個別失効を説明している。[REST API公式](https://developer.wordpress.org/rest-api/reference/application-passwords/)の作成/削除契約は実接続の候補入力であり、本PoCの実機検証ではない。
