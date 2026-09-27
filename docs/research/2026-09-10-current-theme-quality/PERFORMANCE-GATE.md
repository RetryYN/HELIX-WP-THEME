# 性能ゲートの範囲

受入対象は記事、LP、一覧、比較記事の4面とSP・PCの組合せ、計8条件です。各条件でLighthouse 13.5.0の3レポートと、web-vitals 6.2.2の `onINP()` による実操作計測をそろえ、すべてのrunにLCP 2500ms、INP 200ms、CLS 0.1の上限を適用します。3runすべての最大値で判定し、混在した結果から合格runだけを選びません。ブラウザ内に400msの処理停止を加える負例は、実操作の異常検知確認用として別記録し、受入値には使いません。

記事は複数の実本文ブロックとテーマ同梱アイキャッチを持つowner-tagged一時投稿、一覧は11件の一時投稿と各カードの同梱アイキャッチを使います。両方の投稿・メディアは所有者を確認してから削除し、不在を検証します。LPは既存page-lpの`#contact`アンカーを操作し、フォーム送信はしません。

従来のLighthouse CIはサイトrootのみを測定し、LCP 2500msをwarning、TBT 200msと `render-blocking-resources` の0件をblockingとしていました。新ゲートはWT-NFRL1-18 / AC03の要件に合わせ、TBTをINPの代替値として扱わず、8条件すべてで実操作後の `web-vitals.onINP()` 200msをblocking判定します。これがTBT 200ms assertionの意図的な置換です。旧LHCIのmulti-run `optimistic` はmax型assertionでrun間の最小値を選ぶため、全runを保証しません（[設定リファレンス](https://github.com/GoogleChrome/lighthouse-ci/blob/main/docs/configuration.md#aggregation-methods)）。新ゲートはpinned Lighthouseのraw JSONを直接検証し、CWVを3runすべてで判定します。

HOMEは従来のmobile設定（412×823、deviceScaleFactor 1.75）による独立した3runの回帰計測として保持し、8条件の件数に加えません。HOMEの既存LCP上限2500msとrender-blocking 0件も、各run単位でblockingのままです。Lighthouse 13.5.0では旧 `render-blocking-resources` auditが `render-blocking-insight` に置き換わったため、HOME raw reportに新auditが存在し、診断errorがなく、`details.type === 'table'` のitemsが空であることを検査します。auditやtableの欠落、不正形、diagnostic error、1件以上のitemは失敗です。detailsなしの `notApplicable` は測定済み空tableではないため許容しません。この0件条件は旧ゲートと同じroot回帰計測のHOMEだけに適用し、8つのCWV条件へ拡張しません。保存済みLighthouse 13.5.0のHOME reportでは各runに5件が記録され、この移行ルールでは失敗になります。旧LHCI/Lighthouse 12.1.0 raw reportが保存されていないため、その件数差がauditの版変更によるものか、旧optimistic集約によるものかは特定できていません。超過runをraw reportとともに失敗として保存します。

各INP操作はSP 390×844 / deviceScaleFactor 3、PC 1440×900 / deviceScaleFactor 1で実行し、通常のmotion設定を使います。実測したviewport、device scale、motion設定を証拠へ記録し、割り当て条件と異なればvalidatorが拒否します。これはLighthouse画面設定とは別の実操作計測です。LighthouseはSP/PCのform factor・画面設定を分けつつ、既定の `mobileSlow4G` throttling profileと `simulate` 方式を共通で使います。PCだけdesktop throttlingへ変更してはいません。各raw reportには適用された設定が記録されます。

性能証拠の `sourceDigests` は現行theme directory内の全ファイルを含みます。これにより `theme.json`、テーマCSS、HOME/LPのtemplate・pattern・helper、画像、JSを含めて入力を束縛します。CIは同じWordPress containerの有効theme directoryを列挙してSHA-256を取り、worktree manifestとファイル集合・digestが完全一致しなければ失敗します。局所fixture用probeのhash照合に加え、HOMEを含む全描画面で使われるtheme入力を共通の実稼働mountに対して検証します。

Lighthouse、web-vitals、chrome-launcher、Playwrightはlockfile上でexact pinします。性能jobはテーマのengine範囲に合わせNode 24.19.0を使います。Playwrightも固定し、操作probeのAPIとブラウザrevisionの再現性を保ちます。

Lighthouse insightはstable IDで検査します。存在する `notApplicable` と `informative` は診断errorがない限り許容し、欠落と `error` 診断は失敗です。TBTやTTIをINPとして扱いません。

この変更はCWV上限とHOMEの旧render-blockingゼロ件条件を定義・検査します。WT-NFRL1-17 / PERF02に残るcritical CSSのinline、残りCSSの非同期化、CSS・JavaScript・画像の転送量JSON予算は、このゲートで個別に検査していません。現行テーマ用の承認済みbyte上限値と実測checkerも未定義です。source digestによる入力照合やrender-blocking insightはcritical CSS実装要件やbyte予算の代替ではありません。したがってPERF02は未完了のままです。PERF03Bも未定義のbyte予算を含む部分は未完了であり、CWV結果だけで全体の完了を主張しません。

## VPS停止後のソース保全

このブランチは未完了の保存用Draftです。現HEADの受入合格を主張せず、マージしません。実機検査・証拠再生成は停止しています。元の作業ツリーの画像・生成証拠・私用ログはこの保存PRに含めていません。再開時にソース、実機、証拠の対応を確認してください。
