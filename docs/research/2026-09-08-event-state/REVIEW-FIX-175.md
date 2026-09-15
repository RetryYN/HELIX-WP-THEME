# #175 lab 固有値の分離

## 修正範囲

試作03のイベント時刻 fixture と外部受付例の origin を、表示名やテーマ内 literal から分離した。

- `observed_at` は専用 option `wtcf_event_fixture_mode` が文字列 `1` のときだけ使う。サイト名が `HELIX Content Lab` のままでも option を外すと実時刻へ戻る。
- 外部受付例の origin は `site-pages.json` の `handoff_origin` から受け取る。テーマコードに lab の host/portを保持しない。
- origin は `http` / `https`、hostあり、資格情報・query・fragment・配下pathなしに限定する。`handoff_path` は `/` 始まりだけを受け付け、不正な宣言ではリンク枠ごと省略する。

実装判断は WordPress 公式の [`get_option()`](https://developer.wordpress.org/reference/functions/get_option/) と [`wp_parse_url()`](https://developer.wordpress.org/reference/functions/wp_parse_url/) の契約に合わせた。

## 検証

- site pages: 406件成功。manifest所有、正常URL、ftp・資格情報・query・fragment・origin path・相対origin・相対pathの拒否、PC/SP・JS有無、外部遷移と復帰を実測。
- event state: 146件成功。専用optionを外し、labと同じサイト名でも過去期間が実時刻で「受付終了」になることを実測。
- event boundaries: 341件成功。
- form flow/kinds/steps/slots/boundaries: 合計1,090件成功。各検証がfixture optionを明示してから状態を作る。
- site quality 265件、site editor 42件、content faces 191件、content management 5件、interview lifecycle 73件、content inheritance 428件が成功。
- PHP構文検査は変更した2ファイルで成功。

受入台帳は既存の範囲と残件を維持したまま、現行の証跡hashと406行へ更新した。監査は133要求・294受入条件、未対応264・部分17・試作内確認13・stale 0。実在する第三者サービスとの契約や実送信、製品全体のfixture管理UIは今回の達成範囲に含めない。
