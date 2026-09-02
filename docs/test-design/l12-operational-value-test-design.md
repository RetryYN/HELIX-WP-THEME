# L12 Operational Value Test Design

| Test ID | Business requirement | measurement |
| --- | --- | --- |
| WT-OT-01 | WT-BR-01 | capability manifest の列挙率、PHP のみに存在する面・部品の件数（0 であること） |
| WT-OT-02 | WT-BR-02 | 12 種別の必須パーツ充足率、未整備 16 項目の受け皿有無 |
| WT-OT-03 | WT-BR-03 | AI 経路で JSON 外の操作を要した件数、破壊域停止件数、誤警告件数 |
| WT-OT-04 | WT-BR-04 | 取り込み台帳の証跡付き率、逆方向取り込み件数（0 であること） |
| WT-OT-05 | WT-BR-05 | theme / plugin の AI SDK import・判定ロジックの静的検出件数（0 であること） |

破壊域の境界値が PoC で未確定の間、WT-OT-03 の誤警告件数は測定不能として pass を出さない。
