# #176 イベント表示修正

直接includeがWordPress外でも継続することを再現し、ABSPATH guardで停止させた。PHP構文検査と、直接include後の処理が実行されないことを確認した。

バッジのinline styleをevent-state.cssへ移し、イベントページで標準enqueueする。状態表示・POST可否の既存121検査に、PC/SP・JS有無・6状態の24表示検査を追加。145検査が成功した。追加行は外部CSSのlink、inline style不在、computed displayとborderを検査する。境界341検査も成功し、所有fixtureは両検査で撤去確認済み。両コマンドは--strictで実行した。

証跡にはCSS・PHP・検証コードのhashを含める。旧検証行がすべて維持され、全行成功と現行source hash一致を確認してイベント受入台帳を更新した。業務予約・残席更新・実送信の未対応範囲は変わらない。#175のサイト名依存fixture時刻は別の残件。
