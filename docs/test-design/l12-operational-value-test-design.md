# L12 Operational Value Test Design

| Test ID | Business requirement | measurement |
| --- | --- | --- |
| WT-OT-01 | WT-BR-01 | 取り込み台帳の行数、証跡付き率、逆方向取り込み件数（0 であること） |
| WT-OT-02 | WT-BR-02 | 構造変更の権限エラー件数、破壊域停止件数、安全域の誤警告件数 |
| WT-OT-03 | WT-BR-03 | 欠落面 D-01〜D-07 ごとの受け皿有無と実サイト移行時の欠落 0 |
| WT-OT-04 | WT-BR-04 | theme / plugin の AI SDK import・判定ロジックの静的検出 0 |
| WT-OT-05 | WT-BR-05 | PR ごとの静的 FAIL=0 と実機 invalid=0 の同一 HEAD 束縛率 |

破壊域の境界値（WT-Q-VALUE-01）が未確定の間、WT-OT-02 は測定不能として pass を出さない。
