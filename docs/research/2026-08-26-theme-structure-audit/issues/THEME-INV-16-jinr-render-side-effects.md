# THEME-INV-16: JIN:R の描画時 DB 書き込み副作用の影響範囲を確定する

labels: investigation, migration, risk, priority:medium
depends: なし

## 背景（`10-reverse-jinr.md` §3.1）
`include/load-customizer-value.php` の `jinr_customize_inline_style()` は
`wp_head` / `admin_head` にフックされる CSS 生成関数だが、その内部で
**`set_theme_mod()` を 5 箇所呼び、未設定なら既定値を DB へ書き込む**。

```php
if (jinr__theme_color() == false) { set_theme_mod('jinr__theme_color', '#407FED'); }
```

読み取り専用であるべき描画パスに書き込み副作用がある。影響が出る場面:
- 未設定サイトへの初回アクセスで設定が確定する（同時アクセス時の競合）
- **閲覧するだけのクロール・スクレイピングが設定を書き換える**
- サイト複製・移管時に「触っていないのに値が入っている」状態が発生する
- ステージング環境と本番で theme_mod が知らぬ間に乖離する

## 調査項目
1. 5 箇所すべての対象キーと既定値を特定する（読み取り）
2. 他にも描画パスから DB を書く箇所が無いか洗う（`update_option` / `update_post_meta` /
   `set_theme_mod` を `wp_head` `the_content` `render_callback` の到達範囲で走査）
3. PV カウント（`jinr_increment_views` の admin-ajax 経路）など、意図的な書き込みと切り分ける
4. 移管手順への反映 — 「移管前に theme_mod をスナップショットする」等の必要手順を定義する
5. THEME-INV-09（設定の移管）の前提条件として、どの値が「人が決めた設定」で
   どれが「副作用で入った既定値」かを判別できるか判定する

## 完了条件
- [ ] 描画パスからの DB 書き込み箇所が全列挙されている
- [ ] 「人が決めた値」と「副作用で入った値」の判別可否に結論が出ている
- [ ] 移管手順に必要な事前スナップショット工程が定義されている
