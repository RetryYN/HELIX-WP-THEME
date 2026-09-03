# Survey summary

- generated: 2026-09-03T16:33:23.661Z
- results: 269 (errors: 26)
- elapsed per URL: 9516 (5991–16782) mode 5561×2 n=269 ms

## Errors

| id | pattern | kind | sp | pc |
|---|---|---|---|---|
| brand-jp-078 | brand | bot-detected | ok | ok |
| brand-other-100 | brand | bot-detected | ok | ok |
| compare-jp-141-article | compare | exception | ok | ok |
| compare-jp-141 | compare | http-403 | ok | ok |
| compare-jp-153-article | compare | exception | - | - |
| compare-jp-159-article | compare | http-403 | ok | ok |
| compare-jp-159 | compare | http-403 | ok | ok |
| compare-other-162-article | compare | http-403 | ok | ok |
| compare-other-162 | compare | http-403 | ok | ok |
| compare-other-163-article | compare | http-403 | ok | ok |
| compare-other-163 | compare | http-403 | ok | ok |
| compare-other-168-article | compare | http-403 | ok | ok |
| compare-other-168 | compare | http-403 | ok | ok |
| corporate-jp-014 | corporate | http-403 | ok | ok |
| corporate-jp-016 | corporate | http-403 | ok | ok |
| corporate-jp-018 | corporate | http-403 | ok | ok |
| corporate-other-026 | corporate | http-405 | ok | ok |
| corporate-other-033 | corporate | http-403 | ok | ok |
| motion-jp-172 | motion | exception | ok | ok |
| motion-jp-176 | motion | exception | ok | ok |
| motion-jp-179 | motion | timeout | - | ok |
| motion-jp-180 | motion | timeout | - | - |
| motion-other-201 | motion | timeout | - | ok |
| portal-jp-121 | portal | exception | - | ok |
| portal-other-130-article | portal | http-403 | ok | ok |
| portal-other-130 | portal | http-403 | ok | ok |

## pattern: __all__ (n=267)

### 数値 (median (q1–q3) mode×count)

| 指標 | SP | PC |
|---|---|---|
| 本文 font-size | 14 (13–16) mode 16×61 n=245 | 16 (14–16) mode 16×88 n=237 |
| 本文 line-height 比 | 1.6 (1.5–1.8) mode 1.5×47 n=244 | 1.6 (1.5–1.8) mode 1.5×46 n=236 |
| h1 size | 22.7 (16–32) mode 16×27 n=210 | 28 (16–40) mode 16×34 n=213 |
| h2 size | 20.6 (17.45–24.99) mode 24×34 n=236 | 24 (18–32) mode 24×22 n=230 |
| h3 size | 17.75 (16–20) mode 16×35 n=198 | 20 (16–24) mode 16×23 n=189 |
| 本文コンテナ内幅 | 342 (289–358) mode 358×23 n=245 | 544 (336–744) mode 602×4 n=237 |
| コンテナ左右 padding | 0 (0–0) mode 0×184 n=245 | 0 (0–10) mode 0×173 n=237 |
| 段落 margin-bottom | 0 (0–12.48) mode 0×168 n=245 | 0 (0–11.6) mode 0×163 n=237 |
| 見出し margin-top | 0 (0–20) mode 0×131 n=244 | 0 (0–24) mode 0×128 n=237 |
| 見出し margin-bottom | 11.85 (0–20.13) mode 0×77 n=244 | 16 (0–24) mode 0×72 n=237 |
| ヘッダー高 | 65 (55–105) mode 844×27 n=250 | 92 (70–127) mode 800×26 n=242 |
| ヒーロー高 | 679.5 (431–844) mode 844×47 n=190 | 720 (508.25–800) mode 800×55 n=176 |
| ボタン高 | 44 (35.75–52) mode 44×14 n=184 | 45.5 (35.25–56) mode 48×14 n=186 |
| ボタン padding-x | 16 (10.36–23.09) mode 16×26 n=184 | 16.81 (12–24) mode 16×25 n=186 |
| ボタン padding-y | 10 (8–13.07) mode 8×32 n=184 | 11 (8–14) mode 8×28 n=186 |
| ボタン radius | 5 (2–31.25) mode 0×43 n=184 | 8 (3–39) mode 0×31 n=186 |
| ボタン font-size | 14 (13–16) mode 16×46 n=184 | 15 (13.5–16) mode 16×63 n=186 |
| FV 内 CTA 数 | 0 (0–2) mode 0×147 n=264 | 0 (0–2) mode 0×143 n=267 |
| 画像 radius | 0 (0–0) mode 0×236 n=254 | 0 (0–0) mode 0×236 n=251 |
| 影の要素数 | 2 (0–14.25) mode 0×97 n=264 | 1 (0–12) mode 0×106 n=267 |
| ボーダー幅 | 1 (1–1) mode 1×222 n=247 | 1 (1–1) mode 1×220 n=245 |
| カード数 | 1 (0–7) mode 0×117 n=264 | 2 (0–8) mode 0×107 n=267 |
| カード padding | 18 (14–24) mode 16×21 n=147 | 20 (15–30) mode 24×21 n=160 |
| カード radius | 5 (0–10) mode 0×65 n=147 | 4 (0–10) mode 0×74 n=160 |
| セクション padding-top | 40 (20–62.4) mode 40×13 n=173 | 60 (32–100) mode 80×13 n=159 |
| セクション padding-bottom | 40 (24–64.48) mode 80×15 n=165 | 60 (32–101.52) mode 80×15 n=155 |
| セクション間隔(最大側) | 45 (24.72–66.35) mode 40×15 n=208 | 60 (32–100) mode 80×14 n=193 |
| animation 要素 | 0 (0–2) mode 0×147 n=264 | 0 (0–2) mode 0×161 n=267 |
| transition 要素 | 45 (10.75–98.75) mode 0×17 n=264 | 52 (10.5–116.5) mode 0×28 n=267 |
| @keyframes 数 | 11 (2.75–26.5) mode 0×35 n=264 | 9 (2–23) mode 0×46 n=267 |
| rAF 呼出 | 25.5 (6–116) mode 4×54 n=264 | 25 (5–110) mode 4×61 n=267 |
| IntersectionObserver 生成 | 2 (0–6) mode 0×77 n=264 | 2 (0–5) mode 0×84 n=267 |
| scroll 系 listener | 9 (4–19) mode 3×23 n=264 | 8 (3–16) mode 0×21 n=267 |
| 44px 未満タップ率 | 0.6 (0.44–0.74) mode 0.63×8 n=259 | 0.65 (0.49–0.78) mode 0.68×9 n=255 |
| FV 本文文字数 | 169.5 (83–304.75) mode 0×11 n=264 | 154 (55.5–299) mode 0×17 n=267 |
| 転送 KB | 5465 (2964.5–11847.5) mode 1×2 n=263 | 5408 (2971–12330.5) mode 1×4 n=263 |
| リクエスト数 | 139 (85–255) mode 102×6 n=263 | 141 (81.5–283.5) mode 1×4 n=263 |
| 画像総 Mpx | 7.4 (2.3–22.3) mode 0×12 n=264 | 9.1 (3.05–31.25) mode 0×22 n=267 |
| webfont 数 | 4 (1–21) mode 1×41 n=263 | 4 (1–22.5) mode 0×39 n=263 |
| webfont KB | 173 (43.5–718.5) mode 0×32 n=263 | 162 (35–716) mode 0×41 n=263 |
| LCP ms | 1520 (777–2628) mode 768×4 n=258 | 1344 (748–2340) mode 384×3 n=261 |
| DOM ノード | 1432 (858.75–2371) mode 13×2 n=264 | 1347 (805–2239.5) mode 6×2 n=267 |

### 採用率

| 部品 | SP | PC |
|---|---|---|
| sticky ヘッダー率 | 72% (n=264) | 60% (n=267) |
| SP 下部固定率 | 33% (n=264) | 22% (n=267) |
| ハンバーガー率 | 48% (n=264) | 13% (n=267) |
| 目次率 | 14% (n=264) | 13% (n=267) |
| パンくず率 | 22% (n=264) | 20% (n=267) |
| ヒーロー動画率 | 9% (n=190) | 11% (n=176) |
| 影の使用率 | 63% (n=264) | 60% (n=267) |
| scroll-driven animation | 16% (n=264) | 15% (n=267) |
| reduced-motion 対応率 | 38% (n=264) | 36% (n=267) |
| canvas 率 | 12% (n=264) | 13% (n=267) |
| WebGL 率 | 11% (n=264) | 11% (n=267) |
| autoplay 動画率 | 13% (n=264) | 12% (n=267) |
| Lottie 率 | 2% (n=264) | 2% (n=267) |
| GSAP 率 | 7% (n=264) | 6% (n=267) |
| 動きの採用率 | 89% (n=264) | 84% (n=267) |
| 横スクロール発生率 | 7% (n=264) | 7% (n=267) |
| 本文 16px 未満率 | 65% (n=245) | 44% (n=237) |
| WordPress 率 | 32% (n=264) | 31% (n=267) |

### 角丸の分布 (PC)

- ボタン: 0px×31, 4px×22, 8px×18, 2px×12, 5px×10, 80px×9, 9999px×7, 3px×6, 50px×6, 6px×5, 999px×5, 20px×4, 400px×4, 33554400px×4, 24px×3, 4.25px×2, 10px×2, 11px×2, 12px×2, 14px×2, 15px×2, 16px×2, 17.5px×2, 25px×2, 32px×2, 40px×2, 1px×1, 6.9px×1, 7px×1, 7.11px×1, 22px×1, 30px×1, 31px×1, 36px×1, 60px×1, 70px×1, 74.38px×1, 96px×1, 100px×1, 160px×1, 800px×1, 980px×1, 1000px×1, 6666px×1
- 画像: 0px×236, 8px×4, 6px×3, 5px×2, 50px×2, 100px×2, 2px×1, 4px×1
- カード: 0px×74, 8px×19, 12px×11, 16px×11, 4px×8, 10px×7, 5px×6, 3px×3, 6px×3, 20px×2, 2px×1, 7px×1, 9px×1, 11px×1, 17.71px×1, 17.78px×1, 24px×1, 25.6px×1, 30px×1, 39.38px×1, 40px×1, 48px×1, 53.33px×1, 60px×1, 133.33px×1, 9999px×1

### フォント (PC)

- 本文 family 先頭: Noto Sans JP×31, -apple-system×16, 游ゴシック体×11, sans-serif×9, Helvetica Neue×9, Times New Roman×9, Hiragino Kaku Gothic ProN×8, Inter×6, Lato×5, YuGothic×5
- 読込済 webfont: Noto Sans JP×56, icomoon×22, Inter×13, Roboto×9, swiper-icons×8, FontAwesome×7, Lato×6, Poppins×6, YakuHanJP×6, Zen Kaku Gothic New×5

### 色 (PC)

- background (n=344): top #ffffff×144, #000000×21, #fdfdfd×10, #f7f7f7×8, #f4f4f4×5, #f5f5f5×4; 色相 white×238, black×38, gray×29, orange×8, red×5, yellow×5, teal×2, cyan×1, lime×1; 彩度 gray×251, muted×43, vivid×26, moderate×24; 明度 near-white×267, dark×48, mid-dark×15, mid×8, light×6
- text (n=262): top #000000×55, #ffffff×33, #333333×30, #222222×16, #313131×4, #2b2b2b×3; 色相 gray×120, black×69, white×36, red×8, orange×4, yellow×1, cyan×1, lime×1; 彩度 gray×218, muted×25, vivid×13, moderate×6; 明度 dark×128, mid-dark×76, near-white×37, mid×16, light×5
- link (n=250): top #ffffff×55, #000000×34, #333333×21, #222222×6, #1176d4×5, #5a5a5a×3; 色相 gray×79, white×60, black×43, red×18, orange×3, cyan×2, green×2, yellow×2, teal×1; 彩度 gray×174, vivid×39, muted×19, moderate×18; 明度 dark×78, mid-dark×75, near-white×64, mid×29, light×4
- button (n=168): top #ffffff×54, #000000×11, #0075de×3, #e6e6e6×2, #333333×2, #00abeb×2; 色相 white×67, gray×22, red×14, black×12, orange×8, teal×7, lime×7, cyan×3, rose×3, yellow×2, green×2; 彩度 gray×94, vivid×56, moderate×13, muted×5; 明度 near-white×74, mid×38, mid-dark×30, dark×18, light×8
- screenTop (n=1558): top #ffffff×220, #f0f0f0×207, #e0e0e0×135, #d0d0d0×81, #c0c0c0×46, #101010×46; 色相 gray×650, white×332, red×116, black×109, cyan×82, yellow×55, orange×51, teal×18, lime×10, green×9, magenta×7, rose×5; 彩度 gray×953, vivid×309, muted×185, moderate×111; 明度 near-white×619, light×374, dark×246, mid×171, mid-dark×148
- accent (n=126): top #1176d4×5, #0000ee×3, #533afd×3, #0075de×3, #231815×2, #00abeb×2; 色相 red×30, orange×9, teal×8, lime×7, green×4, cyan×4, yellow×3, rose×3; 彩度 vivid×89, moderate×26, muted×11; 明度 mid×58, mid-dark×55, dark×8, light×5

## pattern: brand (n=35)

ids: brand-jp-069, brand-jp-070, brand-jp-071, brand-jp-072, brand-jp-073, brand-jp-074, brand-jp-075, brand-jp-076, brand-jp-077, brand-jp-078, brand-jp-079, brand-jp-080, brand-jp-081, brand-jp-082, brand-jp-083, brand-jp-084, brand-jp-085, brand-jp-086, brand-jp-087, brand-jp-088, brand-jp-089, brand-jp-090, brand-jp-091, brand-jp-092, brand-other-093, brand-other-094, brand-other-095, brand-other-096, brand-other-097, brand-other-098, brand-other-099, brand-other-100, brand-other-101, brand-other-102, brand-other-103

### 数値 (median (q1–q3) mode×count)

| 指標 | SP | PC |
|---|---|---|
| 本文 font-size | 13.26 (12–14) mode 12×11 n=34 | 14.11 (13.09–16) mode 16×9 n=34 |
| 本文 line-height 比 | 1.55 (1.43–1.75) mode 1.5×7 n=34 | 1.59 (1.45–1.8) mode 1.5×7 n=34 |
| h1 size | 24 (18–30) mode 28×5 n=25 | 24.32 (18–32) mode 32×4 n=25 |
| h2 size | 20 (12.95–24.25) mode 12×4 n=32 | 24.5 (16–32) mode 12×4 n=32 |
| h3 size | 16 (14–19.34) mode 14×4 n=24 | 16.44 (14.3–23.33) mode 16×5 n=24 |
| 本文コンテナ内幅 | 308 (224.65–343.5) mode 0×2 n=34 | 388.5 (314.75–498) mode 268×2 n=34 |
| コンテナ左右 padding | 0 (0–0) mode 0×29 n=34 | 0 (0–0) mode 0×29 n=34 |
| 段落 margin-bottom | 0 (0–3) mode 0×24 n=34 | 0 (0–4) mode 0×23 n=34 |
| 見出し margin-top | 0 (0–2) mode 0×22 n=33 | 0 (0–2) mode 0×23 n=33 |
| 見出し margin-bottom | 10 (0–24) mode 0×11 n=33 | 20 (0–32) mode 0×11 n=33 |
| ヘッダー高 | 64 (51–104) mode 844×5 n=33 | 84 (64–112) mode 800×4 n=33 |
| ヒーロー高 | 698.5 (582–844) mode 844×10 n=30 | 720 (640–800) mode 800×11 n=31 |
| ボタン高 | 42.5 (34–46) mode 44×3 n=22 | 42 (38–49) mode 33×2 n=21 |
| ボタン padding-x | 16 (10.37–21.75) mode 12×3 n=22 | 18.67 (12–24) mode 16×4 n=21 |
| ボタン padding-y | 8 (8–12) mode 8×8 n=22 | 12 (8–14.22) mode 8×5 n=21 |
| ボタン radius | 0.94 (0–12.25) mode 0×11 n=22 | 0 (0–7.11) mode 0×11 n=21 |
| ボタン font-size | 14 (12.12–14.75) mode 14×8 n=22 | 14 (12–16) mode 14×6 n=21 |
| FV 内 CTA 数 | 0 (0–2) mode 0×22 n=35 | 0 (0–1.5) mode 0×23 n=35 |
| 画像 radius | 0 (0–0) mode 0×35 n=35 | 0 (0–0) mode 0×34 n=34 |
| 影の要素数 | 1 (0–1) mode 0×17 n=35 | 0 (0–1) mode 0×19 n=35 |
| ボーダー幅 | 1 (1–1) mode 1×29 n=32 | 1 (1–1) mode 1×30 n=33 |
| カード数 | 0 (0–1) mode 0×23 n=35 | 0 (0–1) mode 0×20 n=35 |
| カード padding | 18 (0–20) mode 0×4 n=12 | 24 (15.5–40) mode 40×3 n=15 |
| カード radius | 0 (0–1) mode 0×9 n=12 | 0 (0–6) mode 0×11 n=15 |
| セクション padding-top | 60 (27.5–80) mode 16×2 n=20 | 72.89 (36.53–120) mode 40×2 n=23 |
| セクション padding-bottom | 72.4 (29–97.83) mode 20×2 n=20 | 106.23 (42.5–137.22) mode 120×3 n=22 |
| セクション間隔(最大側) | 72.5 (34.25–99.1) mode 32×2 n=24 | 120 (60–164) mode 80×2 n=25 |
| animation 要素 | 1 (0–2) mode 0×14 n=35 | 1 (0–2) mode 0×14 n=35 |
| transition 要素 | 28 (12.5–107.5) mode 24×2 n=35 | 65 (22–121) mode 0×2 n=35 |
| @keyframes 数 | 17 (9–35) mode 4×2 n=35 | 17 (8–36) mode 4×3 n=35 |
| rAF 呼出 | 207 (41–298) mode 4×2 n=35 | 212 (36–473) mode 4×2 n=35 |
| IntersectionObserver 生成 | 2 (0.5–5) mode 0×9 n=35 | 2 (0.5–5) mode 0×9 n=35 |
| scroll 系 listener | 16 (10.5–41) mode 6×2 n=35 | 19 (7–32.5) mode 7×4 n=35 |
| 44px 未満タップ率 | 0.53 (0.44–0.7) mode 0.53×4 n=35 | 0.62 (0.47–0.73) mode 0.56×4 n=35 |
| FV 本文文字数 | 121 (46.5–338.5) mode 121×2 n=35 | 152 (28.5–348.5) mode 11×3 n=35 |
| 転送 KB | 10330 (7140.5–19519) mode 1876×1 n=35 | 11925 (8253.5–18918) mode 2765×1 n=35 |
| リクエスト数 | 219 (122.5–417) mode 104×2 n=35 | 244 (139–435.5) mode 598×2 n=35 |
| 画像総 Mpx | 21.6 (6.95–51.85) mode 6.9×2 n=35 | 41.2 (14.95–73.7) mode 41.2×2 n=35 |
| webfont 数 | 8 (5–39) mode 0×3 n=35 | 9 (4.5–42) mode 0×3 n=35 |
| webfont KB | 515 (156–1732.5) mode 0×3 n=35 | 558 (158.5–1770.5) mode 0×3 n=35 |
| LCP ms | 2300 (1222–3492) mode 656×2 n=35 | 1688 (1192–2970) mode 716×1 n=35 |
| DOM ノード | 1376 (930–2336) mode 388×1 n=35 | 1421 (984.5–2444.5) mode 388×1 n=35 |

### 採用率

| 部品 | SP | PC |
|---|---|---|
| sticky ヘッダー率 | 83% (n=35) | 80% (n=35) |
| SP 下部固定率 | 34% (n=35) | 31% (n=35) |
| ハンバーガー率 | 49% (n=35) | 29% (n=35) |
| 目次率 | 0% (n=35) | 0% (n=35) |
| パンくず率 | 9% (n=35) | 9% (n=35) |
| ヒーロー動画率 | 7% (n=30) | 6% (n=31) |
| 影の使用率 | 51% (n=35) | 46% (n=35) |
| scroll-driven animation | 14% (n=35) | 14% (n=35) |
| reduced-motion 対応率 | 40% (n=35) | 40% (n=35) |
| canvas 率 | 6% (n=35) | 6% (n=35) |
| WebGL 率 | 6% (n=35) | 6% (n=35) |
| autoplay 動画率 | 26% (n=35) | 26% (n=35) |
| Lottie 率 | 3% (n=35) | 3% (n=35) |
| GSAP 率 | 6% (n=35) | 6% (n=35) |
| 動きの採用率 | 100% (n=35) | 97% (n=35) |
| 横スクロール発生率 | 6% (n=35) | 3% (n=35) |
| 本文 16px 未満率 | 82% (n=34) | 65% (n=34) |
| WordPress 率 | 11% (n=35) | 11% (n=35) |

### 角丸の分布 (PC)

- ボタン: 0px×11, 2px×4, 33554400px×2, 7.11px×1, 15px×1, 40px×1, 50px×1
- 画像: 0px×34
- カード: 0px×11, 12px×1, 16px×1, 17.78px×1, 20px×1

### フォント (PC)

- 本文 family 先頭: Noto Sans JP×3, Zen Kaku Gothic New×2, Shippori Mincho×2, Yu Gothic Pr6N M×1, 游ゴシック×1, akzidenz-grotesk×1, sans-serif×1, -apple-system×1, Poppins×1, Swis721×1
- 読込済 webfont: Noto Sans JP×6, swiper-icons×5, Inter×4, Font Awesome 5 Brands×3, Shippori Mincho×3, futura-pt×2, Instrument Sans×2, Zen Kaku Gothic New×2, yu-gothic-pr6n×2, EB Garamond×2

### 色 (PC)

- background (n=44): top #ffffff×22, #000000×3, #f7f7f7×2, #003894×2, #ececea×2, #f1f2f4×1; 色相 white×28, gray×5, black×5, orange×2, red×2; 彩度 gray×33, vivid×7, muted×3, moderate×1; 明度 near-white×32, dark×5, mid-dark×3, mid×3, light×1
- text (n=35): top #000000×8, #ffffff×6, #333333×2, #222222×1, #7b7b7b×1, #001133×1; 色相 gray×13, black×12, white×6, yellow×1, red×1; 彩度 gray×30, vivid×4, moderate×1; 明度 dark×18, mid-dark×7, near-white×6, mid×2, light×2
- link (n=35): top #000000×10, #ffffff×6, #333333×2, #555555×2, #222222×1, #001133×1; 色相 gray×12, black×12, white×7, orange×1; 彩度 gray×30, vivid×4, moderate×1; 明度 dark×15, mid-dark×11, near-white×7, mid×1, light×1
- button (n=19): top #ffffff×7, #000000×5, #e6e6e6×1, #333333×1, #323232×1, #fac83c×1; 色相 white×7, gray×6, black×5, orange×1; 彩度 gray×18, vivid×1; 明度 near-white×9, dark×6, mid-dark×3, mid×1
- screenTop (n=210): top #ffffff×28, #f0f0f0×19, #e0e0e0×13, #d0d0d0×9, #101010×6, #000000×5; 色相 gray×75, white×33, black×17, orange×17, cyan×15, red×15, yellow×13, teal×5, magenta×4, green×3; 彩度 gray×114, vivid×42, muted×40, moderate×14; 明度 near-white×59, light×57, dark×39, mid-dark×31, mid×24
- accent (n=4): top #172852×1, #fac83c×1, #003894×1, #ff9500×1; 色相 orange×2; 彩度 vivid×3, moderate×1; 明度 mid-dark×2, mid×2

## pattern: compare (n=65)

ids: compare-jp-136-article, compare-jp-136, compare-jp-137-article, compare-jp-137, compare-jp-138-article, compare-jp-138, compare-jp-139-article, compare-jp-139, compare-jp-140-article, compare-jp-140, compare-jp-141-article, compare-jp-141, compare-jp-142-article, compare-jp-142, compare-jp-143-article, compare-jp-143, compare-jp-144-article, compare-jp-144, compare-jp-145-article, compare-jp-145, compare-jp-146-article, compare-jp-146, compare-jp-147-article, compare-jp-147, compare-jp-148-article, compare-jp-148, compare-jp-149-article, compare-jp-149, compare-jp-150-article, compare-jp-150, compare-jp-151-article, compare-jp-151, compare-jp-152-article, compare-jp-152, compare-jp-153, compare-jp-154-article, compare-jp-154, compare-jp-155-article, compare-jp-155, compare-jp-156-article, compare-jp-156, compare-jp-157-article, compare-jp-157, compare-jp-158-article, compare-jp-158, compare-jp-159-article, compare-jp-159, compare-jp-160-article, compare-jp-160, compare-jp-161-article, compare-jp-161, compare-other-162-article, compare-other-162, compare-other-163-article, compare-other-163, compare-other-164-article, compare-other-164, compare-other-165-article, compare-other-165, compare-other-166-article, compare-other-166, compare-other-167-article, compare-other-167, compare-other-168-article, compare-other-168

### 数値 (median (q1–q3) mode×count)

| 指標 | SP | PC |
|---|---|---|
| 本文 font-size | 14.82 (14–16) mode 14×18 n=61 | 16 (14–16) mode 16×26 n=57 |
| 本文 line-height 比 | 1.6 (1.5–1.8) mode 1.8×17 n=61 | 1.6 (1.5–1.8) mode 1.8×17 n=57 |
| h1 size | 19.5 (16–24) mode 16×8 n=58 | 24 (16–32) mode 16×15 n=56 |
| h2 size | 20 (18–22) mode 20×15 n=63 | 24 (20.55–25.8) mode 24×14 n=58 |
| h3 size | 17.16 (16–18) mode 18×10 n=57 | 19 (16–21.98) mode 16×6 n=50 |
| 本文コンテナ内幅 | 350 (310–358) mode 358×7 n=61 | 640 (340–796) mode 602×3 n=57 |
| コンテナ左右 padding | 0 (0–0) mode 0×46 n=61 | 0 (0–16) mode 0×32 n=57 |
| 段落 margin-bottom | 0 (0–24) mode 0×32 n=61 | 0 (0–24) mode 0×32 n=57 |
| 見出し margin-top | 10 (0–48) mode 0×27 n=64 | 16 (0–55.5) mode 0×25 n=59 |
| 見出し margin-bottom | 11 (0–24) mode 0×18 n=64 | 16 (0–33) mode 0×18 n=59 |
| ヘッダー高 | 60 (51.75–75.75) mode 60×9 n=60 | 96 (72.5–124) mode 92×4 n=54 |
| ヒーロー高 | 431 (283–724) mode 844×4 n=39 | 503 (400–730) mode 250×4 n=33 |
| ボタン高 | 44 (34–48) mode 44×6 n=49 | 45 (34.75–58) mode 48×6 n=48 |
| ボタン padding-x | 16 (14–23.4) mode 16×11 n=49 | 16 (10.75–24) mode 8×9 n=48 |
| ボタン padding-y | 10 (8–12) mode 8×8 n=49 | 11 (8–12.25) mode 8×12 n=48 |
| ボタン radius | 15 (4–80) mode 80×6 n=49 | 17.5 (5–80) mode 80×7 n=48 |
| ボタン font-size | 14 (13.01–15.6) mode 14×12 n=49 | 15.02 (14–16) mode 16×16 n=48 |
| FV 内 CTA 数 | 0 (0–2) mode 0×37 n=65 | 0 (0–2) mode 0×37 n=65 |
| 画像 radius | 0 (0–0) mode 0×55 n=64 | 0 (0–0) mode 0×50 n=58 |
| 影の要素数 | 12 (1–46) mode 0×14 n=65 | 3 (0–37) mode 0×17 n=65 |
| ボーダー幅 | 1 (1–1) mode 1×57 n=63 | 1 (1–1) mode 1×56 n=60 |
| カード数 | 6 (0–35) mode 0×17 n=65 | 6 (0–33) mode 0×21 n=65 |
| カード padding | 16 (14.62–20) mode 16×13 n=48 | 17 (15.75–22.88) mode 16×11 n=44 |
| カード radius | 5 (0–8.5) mode 0×16 n=48 | 5 (0–12) mode 0×16 n=44 |
| セクション padding-top | 28 (16–32) mode 32×6 n=36 | 35 (30–53) mode 40×5 n=27 |
| セクション padding-bottom | 27.18 (19–40) mode 24×5 n=36 | 48 (31.5–74) mode 32×3 n=28 |
| セクション間隔(最大側) | 30 (24–48) mode 24×6 n=49 | 46 (29.5–61) mode 32×5 n=40 |
| animation 要素 | 0 (0–2) mode 0×41 n=65 | 0 (0–1) mode 0×47 n=65 |
| transition 要素 | 30 (6–75) mode 0×7 n=65 | 26 (2–77) mode 0×15 n=65 |
| @keyframes 数 | 14 (2–30) mode 0×13 n=65 | 12 (0–26) mode 0×17 n=65 |
| rAF 呼出 | 17 (4–28) mode 4×18 n=65 | 11 (4–32) mode 4×24 n=65 |
| IntersectionObserver 生成 | 2 (0–5) mode 0×17 n=65 | 2 (0–5) mode 0×19 n=65 |
| scroll 系 listener | 8 (3–16) mode 2×9 n=65 | 6 (2–13) mode 2×8 n=65 |
| 44px 未満タップ率 | 0.65 (0.51–0.73) mode 0.45×4 n=64 | 0.68 (0.49–0.8) mode 0.49×4 n=60 |
| FV 本文文字数 | 210 (118–312) mode 91×2 n=65 | 155 (80–268) mode 0×3 n=65 |
| 転送 KB | 3236.5 (2011.5–5966.5) mode 0×1 n=64 | 3194 (1944–5624) mode 1×2 n=65 |
| リクエスト数 | 107 (61.25–252) mode 252×3 n=64 | 103 (56–216) mode 1×2 n=65 |
| 画像総 Mpx | 5.5 (1.8–11.2) mode 0×3 n=65 | 6.3 (2.1–12.3) mode 0×9 n=65 |
| webfont 数 | 2 (1–4) mode 1×18 n=64 | 1 (0–4) mode 0×19 n=65 |
| webfont KB | 69 (8.75–173.5) mode 0×13 n=64 | 44 (0–200) mode 0×19 n=65 |
| LCP ms | 1080 (743–1888) mode 344×2 n=64 | 1050 (646–1608) mode 408×2 n=62 |
| DOM ノード | 2202 (1083–5563) mode 47×2 n=65 | 2016 (597–4766) mode 6×2 n=65 |

### 採用率

| 部品 | SP | PC |
|---|---|---|
| sticky ヘッダー率 | 51% (n=65) | 29% (n=65) |
| SP 下部固定率 | 38% (n=65) | 15% (n=65) |
| ハンバーガー率 | 52% (n=65) | 0% (n=65) |
| 目次率 | 37% (n=65) | 35% (n=65) |
| パンくず率 | 37% (n=65) | 32% (n=65) |
| ヒーロー動画率 | 5% (n=39) | 3% (n=33) |
| 影の使用率 | 78% (n=65) | 74% (n=65) |
| scroll-driven animation | 12% (n=65) | 8% (n=65) |
| reduced-motion 対応率 | 45% (n=65) | 40% (n=65) |
| canvas 率 | 0% (n=65) | 0% (n=65) |
| WebGL 率 | 0% (n=65) | 0% (n=65) |
| autoplay 動画率 | 5% (n=65) | 3% (n=65) |
| Lottie 率 | 0% (n=65) | 0% (n=65) |
| GSAP 率 | 0% (n=65) | 0% (n=65) |
| 動きの採用率 | 83% (n=65) | 74% (n=65) |
| 横スクロール発生率 | 8% (n=65) | 14% (n=65) |
| 本文 16px 未満率 | 69% (n=61) | 49% (n=57) |
| WordPress 率 | 46% (n=65) | 45% (n=65) |

### 角丸の分布 (PC)

- ボタン: 80px×7, 999px×5, 5px×4, 0px×3, 4px×3, 6px×3, 8px×3, 2px×2, 3px×2, 10px×1, 12px×1, 14px×1, 15px×1, 20px×1, 22px×1, 24px×1, 31px×1, 32px×1, 36px×1, 40px×1, 50px×1, 70px×1, 96px×1, 400px×1, 9999px×1
- 画像: 0px×50, 6px×3, 8px×2, 100px×2, 4px×1
- カード: 0px×16, 16px×6, 5px×5, 8px×5, 12px×5, 4px×3, 2px×1, 6px×1, 10px×1, 20px×1

### フォント (PC)

- 本文 family 先頭: 游ゴシック体×11, Noto Sans JP×7, Helvetica Neue×7, -apple-system×6, Times New Roman×5, Lato×5, Hiragino Kaku Gothic ProN×4, Inter×4, メイリオ×2, ui-sans-serif×2
- 読込済 webfont: icomoon×16, Noto Sans JP×10, Lato×4, Inter×4, Font Awesome 5 Free×3, FontAwesome×3, Fjalla One×3, Open Sans×3, Font Awesome 5 Brands×2, Montserrat×2

### 色 (PC)

- background (n=73): top #ffffff×32, #fdfdfd×10, #f4f4f4×5, #f5f5f5×3, #faf5eb×3, #f3f3f3×2; 色相 white×66, gray×4, cyan×1, yellow×1; 彩度 gray×62, moderate×6, vivid×5; 明度 near-white×71, mid-dark×2
- text (n=65): top #333333×17, #000000×17, #313131×4, #231815×2, #222222×2, #252525×2; 色相 gray×38, black×18, red×2, white×1, cyan×1; 彩度 gray×57, muted×6, vivid×1, moderate×1; 明度 dark×34, mid-dark×27, mid×2, near-white×1, light×1
- link (n=60): top #ffffff×8, #333333×6, #1176d4×5, #000000×3, #313131×2, #231815×2; 色相 gray×15, white×8, red×5, black×3, cyan×2, green×2; 彩度 gray×26, vivid×21, moderate×9, muted×4; 明度 mid-dark×27, mid×14, near-white×10, dark×9
- button (n=47): top #ffffff×14, #00abeb×2, #eb5e96×2, #338df4×2, #357a00×2, #0eb03a×1; 色相 white×19, red×5, rose×3, orange×3, lime×3, cyan×2, gray×2, teal×1, yellow×1, green×1, black×1; 彩度 vivid×24, gray×19, moderate×2, muted×2; 明度 near-white×20, mid×16, mid-dark×7, dark×2, light×2
- screenTop (n=373): top #ffffff×63, #f0f0f0×60, #e0e0e0×41, #d0d0d0×19, #f0ffff×18, #c0c0c0×11; 色相 gray×157, white×106, cyan×25, red×23, yellow×11, orange×9, black×6, lime×5, teal×2, rose×2, magenta×1; 彩度 gray×226, vivid×78, moderate×38, muted×31; 明度 near-white×193, light×92, mid-dark×32, mid×29, dark×27
- accent (n=58): top #1176d4×5, #231815×2, #00abeb×2, #eb5e96×2, #338df4×2, #357a00×2; 色相 red×10, rose×3, orange×3, lime×3, green×3, cyan×3, teal×1, yellow×1; 彩度 vivid×44, moderate×10, muted×4; 明度 mid×29, mid-dark×23, dark×4, light×2

## pattern: corporate (n=35)

ids: corporate-jp-000, corporate-jp-001, corporate-jp-002, corporate-jp-003, corporate-jp-004, corporate-jp-005, corporate-jp-006, corporate-jp-007, corporate-jp-008, corporate-jp-009, corporate-jp-010, corporate-jp-011, corporate-jp-012, corporate-jp-013, corporate-jp-014, corporate-jp-015, corporate-jp-016, corporate-jp-017, corporate-jp-018, corporate-jp-019, corporate-jp-020, corporate-jp-021, corporate-jp-022, corporate-jp-023, corporate-other-024, corporate-other-025, corporate-other-026, corporate-other-027, corporate-other-028, corporate-other-029, corporate-other-030, corporate-other-031, corporate-other-032, corporate-other-033, corporate-other-034

### 数値 (median (q1–q3) mode×count)

| 指標 | SP | PC |
|---|---|---|
| 本文 font-size | 14 (13.05–16) mode 16×10 n=34 | 16 (13.88–16) mode 16×11 n=31 |
| 本文 line-height 比 | 1.75 (1.5–1.8) mode 1.8×7 n=33 | 1.62 (1.5–1.8) mode 1.5×5 n=30 |
| h1 size | 17 (15–32) mode 16×5 n=30 | 24 (15.93–33) mode 16×4 n=27 |
| h2 size | 23 (16–32) mode 24×5 n=33 | 30.5 (16–48) mode 16×2 n=29 |
| h3 size | 20 (16–24) mode 16×3 n=26 | 21 (15.61–25.75) mode 16×3 n=22 |
| 本文コンテナ内幅 | 332.5 (292.5–350.75) mode 350×4 n=34 | 496 (351–678.5) mode 220×1 n=31 |
| コンテナ左右 padding | 0 (0–0) mode 0×27 n=34 | 0 (0–0) mode 0×25 n=31 |
| 段落 margin-bottom | 0 (0–0) mode 0×27 n=34 | 0 (0–0) mode 0×26 n=31 |
| 見出し margin-top | 0 (0–4) mode 0×24 n=33 | 0 (0–5) mode 0×18 n=29 |
| 見出し margin-bottom | 9.89 (0–24) mode 0×11 n=33 | 13 (0–21.33) mode 0×12 n=29 |
| ヘッダー高 | 70 (62.5–844) mode 844×9 n=35 | 93 (74.5–458) mode 800×6 n=31 |
| ヒーロー高 | 702 (577.5–844) mode 844×7 n=31 | 725.5 (657.75–800) mode 800×8 n=30 |
| ボタン高 | 48 (40–59) mode 40×3 n=26 | 42 (37–55.5) mode 32×2 n=23 |
| ボタン padding-x | 19 (12–25.5) mode 10×4 n=26 | 18 (15–26.34) mode 10×4 n=23 |
| ボタン padding-y | 11.77 (8.25–14) mode 12×4 n=26 | 11 (8–12) mode 12×5 n=23 |
| ボタン radius | 3.75 (0.25–27.5) mode 0×7 n=26 | 4 (2–425) mode 2×5 n=23 |
| ボタン font-size | 14 (13–16) mode 16×8 n=26 | 15.86 (13.51–16) mode 16×9 n=23 |
| FV 内 CTA 数 | 1 (0–2) mode 0×15 n=35 | 0 (0–3) mode 0×19 n=35 |
| 画像 radius | 0 (0–0) mode 0×32 n=35 | 0 (0–0) mode 0×33 n=35 |
| 影の要素数 | 1 (0–3.5) mode 0×16 n=35 | 0 (0–2) mode 0×20 n=35 |
| ボーダー幅 | 1 (1–1) mode 1×29 n=31 | 1 (1–1) mode 1×26 n=28 |
| カード数 | 1 (0–7) mode 0×15 n=35 | 1 (0–7.5) mode 0×14 n=35 |
| カード padding | 24 (15.75–30.04) mode 24×3 n=20 | 24 (16–38.5) mode 10×3 n=21 |
| カード radius | 0 (0–11) mode 0×12 n=20 | 0 (0–10) mode 0×12 n=21 |
| セクション padding-top | 50 (25–70) mode 40×3 n=25 | 75.41 (52.5–114.04) mode 0.1×1 n=22 |
| セクション padding-bottom | 60 (40–80) mode 40×5 n=25 | 114.05 (44–150) mode 11×1 n=21 |
| セクション間隔(最大側) | 50 (24–72) mode 60×3 n=29 | 100 (40–128.14) mode 24×2 n=25 |
| animation 要素 | 2 (0–6) mode 0×14 n=35 | 1 (0–4.5) mode 0×17 n=35 |
| transition 要素 | 62 (38–145.5) mode 0×2 n=35 | 65 (30.5–125) mode 0×5 n=35 |
| @keyframes 数 | 10 (3–22) mode 22×4 n=35 | 6 (2–19.5) mode 0×7 n=35 |
| rAF 呼出 | 59 (7–181.5) mode 4×8 n=35 | 36 (4–134.5) mode 4×11 n=35 |
| IntersectionObserver 生成 | 2 (0–9) mode 0×11 n=35 | 1 (0–8.5) mode 0×13 n=35 |
| scroll 系 listener | 10 (4.5–18.5) mode 1×4 n=35 | 9 (2–15.5) mode 0×5 n=35 |
| 44px 未満タップ率 | 0.53 (0.4–0.7) mode 0.34×2 n=35 | 0.57 (0.44–0.75) mode 0.4×3 n=32 |
| FV 本文文字数 | 138 (83.5–249.5) mode 133×2 n=35 | 145 (42.5–213) mode 0×5 n=35 |
| 転送 KB | 5648 (2834.5–13655.5) mode 796×1 n=35 | 5656 (1869.5–13237.5) mode 7×1 n=35 |
| リクエスト数 | 94 (74–137.5) mode 128×2 n=35 | 97 (61.5–135.5) mode 2×1 n=35 |
| 画像総 Mpx | 8.9 (3.1–23.95) mode 0.1×1 n=35 | 10.8 (1.45–28.5) mode 0×5 n=35 |
| webfont 数 | 10 (5–36.5) mode 6×4 n=35 | 8 (2–36.5) mode 0×5 n=35 |
| webfont KB | 549 (198.5–802) mode 0×2 n=35 | 362 (56–728.5) mode 0×5 n=35 |
| LCP ms | 1562 (1029–2555) mode 1380×2 n=34 | 1342 (656–2633) mode 1104×2 n=34 |
| DOM ノード | 1442 (669.5–1988.5) mode 227×1 n=35 | 1149 (582–1821.5) mode 22×1 n=35 |

### 採用率

| 部品 | SP | PC |
|---|---|---|
| sticky ヘッダー率 | 91% (n=35) | 74% (n=35) |
| SP 下部固定率 | 34% (n=35) | 29% (n=35) |
| ハンバーガー率 | 57% (n=35) | 17% (n=35) |
| 目次率 | 0% (n=35) | 0% (n=35) |
| パンくず率 | 14% (n=35) | 11% (n=35) |
| ヒーロー動画率 | 19% (n=31) | 23% (n=30) |
| 影の使用率 | 54% (n=35) | 43% (n=35) |
| scroll-driven animation | 23% (n=35) | 23% (n=35) |
| reduced-motion 対応率 | 37% (n=35) | 34% (n=35) |
| canvas 率 | 17% (n=35) | 17% (n=35) |
| WebGL 率 | 14% (n=35) | 14% (n=35) |
| autoplay 動画率 | 17% (n=35) | 17% (n=35) |
| Lottie 率 | 6% (n=35) | 6% (n=35) |
| GSAP 率 | 20% (n=35) | 17% (n=35) |
| 動きの採用率 | 94% (n=35) | 83% (n=35) |
| 横スクロール発生率 | 0% (n=35) | 3% (n=35) |
| 本文 16px 未満率 | 68% (n=34) | 42% (n=31) |
| WordPress 率 | 49% (n=35) | 40% (n=35) |

### 角丸の分布 (PC)

- ボタン: 2px×5, 0px×3, 4px×2, 9999px×2, 1px×1, 3px×1, 8px×1, 10px×1, 20px×1, 30px×1, 50px×1, 800px×1, 980px×1, 6666px×1, 33554400px×1
- 画像: 0px×33, 5px×1, 8px×1
- カード: 0px×12, 8px×2, 10px×2, 5px×1, 17.71px×1, 30px×1, 40px×1, 53.33px×1

### フォント (PC)

- 本文 family 先頭: Noto Sans JP×7, sans-serif×3, YuGothic×3, Zen Kaku Gothic New×1, Zen Kaku Gothic Antique×1, MFW-PA1GothicStdN-Regular×1, helvetica-neue-lt-pro×1, YakuHanJP_Noto×1, IBM Plex Sans JP×1, neue-haas-unica×1
- 読込済 webfont: Noto Sans JP×10, Zen Kaku Gothic New×2, Albert Sans×2, Work Sans×1, Crimson Text×1, Plus Jakarta Sans×1, Akshar×1, Geist×1, Zain×1, Lexend×1

### 色 (PC)

- background (n=47): top #ffffff×17, #000000×3, #f7f3eb×2, #f2efeb×2, #f2f2f2×2, #dbe0e0×1; 色相 white×27, gray×8, black×6, orange×3, yellow×1; 彩度 gray×35, muted×5, moderate×4, vivid×3; 明度 near-white×30, dark×8, mid-dark×5, light×3, mid×1
- text (n=34): top #000000×8, #ffffff×7, #2f4243×1, #514327×1, #232323×1, #272727×1; 色相 gray×10, white×9, black×9, orange×2, red×1; 彩度 gray×26, muted×5, vivid×2, moderate×1; 明度 dark×14, near-white×9, mid-dark×9, mid×2
- link (n=31): top #ffffff×13, #000000×4, #2f4243×1, #f7f3eb×1, #1d3026×1, #232323×1; 色相 white×15, gray×7, black×4, red×2, teal×1; 彩度 gray×24, muted×3, moderate×3, vivid×1; 明度 near-white×15, dark×9, mid-dark×4, mid×2, light×1
- button (n=19): top #ffffff×5, #f7f3eb×1, #009144×1, #2b2b2b×1, #e3e3e3×1, #e6e6e6×1; 色相 white×7, gray×3, red×3, black×2, teal×1, orange×1; 彩度 gray×11, vivid×6, moderate×2; 明度 near-white×8, mid×4, mid-dark×3, dark×3, light×1
- screenTop (n=210): top #ffffff×26, #f0f0f0×25, #e0e0e0×14, #d0d0d0×13, #c0c0c0×10, #101010×7; 色相 gray×86, white×34, black×16, red×15, orange×12, yellow×9, cyan×5, green×3, teal×2, magenta×1, rose×1; 彩度 gray×122, vivid×38, muted×37, moderate×13; 明度 near-white×69, light×60, mid×33, dark×32, mid-dark×16
- accent (n=10): top #1d3026×1, #009144×1, #c80421×1, #0071e3×1, #533afd×1, #ff9902×1; 色相 red×4, teal×2, orange×1; 彩度 vivid×7, moderate×2, muted×1; 明度 mid×6, mid-dark×3, dark×1

## pattern: motion (n=34)

ids: motion-jp-169, motion-jp-170, motion-jp-171, motion-jp-172, motion-jp-173, motion-jp-174, motion-jp-175, motion-jp-176, motion-jp-177, motion-jp-178, motion-jp-179, motion-jp-181, motion-jp-182, motion-jp-183, motion-jp-184, motion-jp-185, motion-jp-186, motion-jp-187, motion-jp-188, motion-jp-189, motion-jp-190, motion-jp-191, motion-jp-192, motion-other-193, motion-other-194, motion-other-195, motion-other-196, motion-other-197, motion-other-198, motion-other-199, motion-other-200, motion-other-201, motion-other-202, motion-other-203

### 数値 (median (q1–q3) mode×count)

| 指標 | SP | PC |
|---|---|---|
| 本文 font-size | 13.54 (12–14.56) mode 12×3 n=25 | 14.52 (12.49–16.45) mode 16×4 n=24 |
| 本文 line-height 比 | 1.5 (1.2–1.6) mode 1.5×5 n=25 | 1.5 (1.25–1.63) mode 1.5×6 n=24 |
| h1 size | 26 (16–32) mode 16×3 n=17 | 25.6 (16–38) mode 16×3 n=21 |
| h2 size | 19.76 (14–30.56) mode 14×2 n=19 | 25.88 (17.78–44) mode 10×1 n=21 |
| h3 size | 14.91 (11.58–21.7) mode 8.86×1 n=14 | 20.67 (13.67–26.41) mode 10.67×2 n=16 |
| 本文コンテナ内幅 | 326 (234–358) mode 390×3 n=25 | 447 (335.25–758) mode 190×1 n=24 |
| コンテナ左右 padding | 0 (0–16) mode 0×17 n=25 | 0 (0–2.06) mode 0×18 n=24 |
| 段落 margin-bottom | 0 (0–0) mode 0×20 n=25 | 0 (0–0) mode 0×21 n=24 |
| 見出し margin-top | 0 (0–0) mode 0×15 n=20 | 0 (0–8) mode 0×14 n=21 |
| 見出し margin-bottom | 0 (0–11.88) mode 0×11 n=20 | 0 (0–14) mode 0×13 n=21 |
| ヘッダー高 | 272 (59.25–844) mode 844×9 n=30 | 800 (87–800) mode 800×14 n=31 |
| ヒーロー高 | 844 (811–844) mode 844×13 n=24 | 800 (800–800) mode 800×22 n=26 |
| ボタン高 | 47 (34–52) mode 34×2 n=9 | 53.5 (36–60) mode 60×2 n=14 |
| ボタン padding-x | 16 (10–20) mode 10×2 n=9 | 16.93 (11.98–22.06) mode 10×2 n=14 |
| ボタン padding-y | 12 (9.53–15) mode 5.6×1 n=9 | 14.5 (10.24–16) mode 16×2 n=14 |
| ボタン radius | 0 (0–3) mode 0×5 n=9 | 2.5 (0–6.18) mode 0×6 n=14 |
| ボタン font-size | 14 (13.01–15) mode 14×3 n=9 | 14.32 (13.22–16) mode 12×2 n=14 |
| FV 内 CTA 数 | 0 (0–0) mode 0×28 n=32 | 0 (0–0) mode 0×29 n=34 |
| 画像 radius | 0 (0–0) mode 0×26 n=26 | 0 (0–0) mode 0×29 n=29 |
| 影の要素数 | 0 (0–0.25) mode 0×24 n=32 | 0 (0–1) mode 0×24 n=34 |
| ボーダー幅 | 1 (1–1) mode 1×20 n=24 | 1 (1–1) mode 1×20 n=26 |
| カード数 | 0 (0–0) mode 0×27 n=32 | 0 (0–1) mode 0×23 n=34 |
| カード padding | 16.64 (15.6–30) mode 14.18×1 n=5 | 16 (12.43–24) mode 16×2 n=11 |
| カード radius | 0 (0–0) mode 0×5 n=5 | 0 (0–3.5) mode 0×7 n=11 |
| セクション padding-top | 56 (16–86) mode 16×2 n=14 | 86.25 (53.33–120) mode 1×1 n=13 |
| セクション padding-bottom | 52 (34.5–64.15) mode 16×2 n=15 | 51.67 (32–116) mode 32×2 n=14 |
| セクション間隔(最大側) | 80 (58.5–135.2) mode 39×1 n=17 | 96.46 (50–135.25) mode 128×2 n=16 |
| animation 要素 | 0 (0–4.25) mode 0×19 n=32 | 0 (0–1.75) mode 0×18 n=34 |
| transition 要素 | 10 (2–41.5) mode 2×5 n=32 | 22 (5.25–52.75) mode 2×3 n=34 |
| @keyframes 数 | 7.5 (2.75–21) mode 3×4 n=32 | 6.5 (2.25–16.5) mode 2×4 n=34 |
| rAF 呼出 | 59 (26.75–144.5) mode 4×4 n=32 | 65.5 (31–130) mode 4×4 n=34 |
| IntersectionObserver 生成 | 1 (0–3) mode 0×10 n=32 | 1 (0–3) mode 0×11 n=34 |
| scroll 系 listener | 5.5 (3–19.25) mode 3×5 n=32 | 5.5 (3–17.25) mode 3×8 n=34 |
| 44px 未満タップ率 | 0.55 (0.38–0.87) mode 0×2 n=30 | 0.49 (0.36–0.75) mode 0×2 n=32 |
| FV 本文文字数 | 96 (3–202.75) mode 0×8 n=32 | 103 (5.5–184.5) mode 0×8 n=34 |
| 転送 KB | 12181.5 (6112–22276) mode 361×1 n=32 | 10524 (7298.25–22196) mode 371×1 n=30 |
| リクエスト数 | 106.5 (59–149.25) mode 51×2 n=32 | 108.5 (65.5–160.25) mode 50×2 n=30 |
| 画像総 Mpx | 12.15 (0.85–24.65) mode 0×5 n=32 | 16.15 (1.23–54.78) mode 0×6 n=34 |
| webfont 数 | 5.5 (2–21) mode 2×6 n=32 | 5.5 (2.5–20.5) mode 2×6 n=30 |
| webfont KB | 224 (98–592) mode 0×1 n=32 | 235.5 (102.5–548.75) mode 0×1 n=30 |
| LCP ms | 2916 (922–4724) mode 240×1 n=31 | 2900 (1128–4284) mode 244×1 n=33 |
| DOM ノード | 876 (432.25–1550) mode 30×1 n=32 | 972 (462.75–1727.25) mode 30×1 n=34 |

### 採用率

| 部品 | SP | PC |
|---|---|---|
| sticky ヘッダー率 | 75% (n=32) | 74% (n=34) |
| SP 下部固定率 | 22% (n=32) | 32% (n=34) |
| ハンバーガー率 | 28% (n=32) | 9% (n=34) |
| 目次率 | 0% (n=32) | 0% (n=34) |
| パンくず率 | 0% (n=32) | 0% (n=34) |
| ヒーロー動画率 | 13% (n=24) | 15% (n=26) |
| 影の使用率 | 25% (n=32) | 29% (n=34) |
| scroll-driven animation | 9% (n=32) | 9% (n=34) |
| reduced-motion 対応率 | 13% (n=32) | 12% (n=34) |
| canvas 率 | 53% (n=32) | 62% (n=34) |
| WebGL 率 | 50% (n=32) | 56% (n=34) |
| autoplay 動画率 | 22% (n=32) | 21% (n=34) |
| Lottie 率 | 3% (n=32) | 3% (n=34) |
| GSAP 率 | 9% (n=32) | 6% (n=34) |
| 動きの採用率 | 97% (n=32) | 97% (n=34) |
| 横スクロール発生率 | 16% (n=32) | 12% (n=34) |
| 本文 16px 未満率 | 84% (n=25) | 58% (n=24) |
| WordPress 率 | 6% (n=32) | 9% (n=34) |

### 角丸の分布 (PC)

- ボタン: 0px×6, 4px×2, 2px×1, 3px×1, 6.9px×1, 74.38px×1, 100px×1, 1000px×1
- 画像: 0px×29
- カード: 0px×7, 3px×1, 4px×1, 25.6px×1, 133.33px×1

### フォント (PC)

- 本文 family 先頭: Noto Sans JP×2, Times New Roman×2, Zen Old Mincho×1, Futura×1, MFW-PA1MinchoStdN-Regular×1, Yu Gothic×1, a-otf-ud-shin-go-con80-pr6n×1, PP Neue Montreal Medium×1, Noto Serif JP×1, DotGothic16×1
- 読込済 webfont: Noto Sans JP×7, Oswald×3, YakuHanJP×2, Cormorant Garamond×2, Noto Serif JP×2, M PLUS 1p×2, IBMPlexMono×2, Google Sans×1, Zen Old Mincho×1, Futura×1

### 色 (PC)

- background (n=43): top #000000×13, #ffffff×7, #111111×2, #1e1e1e×2, #18103f×2, #0094d8×2; 色相 black×19, white×9, gray×5, yellow×3, red×2, lime×1; 彩度 gray×30, muted×7, vivid×4, moderate×2; 明度 dark×24, near-white×13, light×2, mid-dark×2, mid×2
- text (n=34): top #ffffff×14, #000000×6, #0d0d0d×1, #a0a0a0×1, #282727×1, #050505×1; 色相 white×15, black×8, gray×5, orange×2, red×1, lime×1; 彩度 gray×27, muted×3, vivid×2, moderate×2; 明度 near-white×15, dark×12, mid-dark×4, mid×2, light×1
- link (n=28): top #ffffff×11, #000000×3, #0d0d0d×1, #a0a0a0×1, #282727×1, #050505×1; 色相 white×11, black×6, gray×5, yellow×2, orange×2, red×1; 彩度 gray×22, muted×4, moderate×2; 明度 near-white×12, dark×8, mid-dark×5, mid×2, light×1
- button (n=12): top #000000×3, #282828×1, #de3e21×1, #ececec×1, #d44258×1, #ff0000×1; 色相 red×4, black×3, gray×2, white×1, yellow×1; 彩度 gray×6, vivid×4, moderate×2; 明度 mid×6, dark×4, near-white×2
- screenTop (n=177): top #101010×13, #000000×12, #f0f0f0×12, #ffffff×11, #202020×8, #e0e0e0×8; 色相 gray×70, black×30, red×21, white×14, cyan×14, yellow×7, orange×4, rose×2, green×2, lime×2; 彩度 gray×106, muted×28, vivid×24, moderate×19; 明度 dark×67, mid×30, near-white×29, light×28, mid-dark×23
- accent (n=9): top #de3e21×1, #d44258×1, #ff0000×1, #a9a07f×1, #173086×1, #d41b2d×1; 色相 red×5, yellow×2; 彩度 moderate×4, vivid×4, muted×1; 明度 mid×7, mid-dark×2

## pattern: portal (n=64)

ids: portal-jp-104-article, portal-jp-104, portal-jp-105-article, portal-jp-105, portal-jp-106-article, portal-jp-106, portal-jp-107-article, portal-jp-107, portal-jp-108-article, portal-jp-108, portal-jp-109-article, portal-jp-109, portal-jp-110-article, portal-jp-110, portal-jp-111-article, portal-jp-111, portal-jp-112-article, portal-jp-112, portal-jp-113-article, portal-jp-113, portal-jp-114-article, portal-jp-114, portal-jp-115-article, portal-jp-115, portal-jp-116-article, portal-jp-116, portal-jp-117-article, portal-jp-117, portal-jp-118-article, portal-jp-118, portal-jp-119-article, portal-jp-119, portal-jp-120-article, portal-jp-120, portal-jp-121-article, portal-jp-121, portal-jp-122-article, portal-jp-122, portal-jp-123-article, portal-jp-123, portal-jp-124-article, portal-jp-124, portal-jp-125-article, portal-jp-125, portal-other-126-article, portal-other-126, portal-other-127-article, portal-other-127, portal-other-128-article, portal-other-128, portal-other-129-article, portal-other-129, portal-other-130-article, portal-other-130, portal-other-131-article, portal-other-131, portal-other-132-article, portal-other-132, portal-other-133-article, portal-other-133, portal-other-134-article, portal-other-134, portal-other-135-article, portal-other-135

### 数値 (median (q1–q3) mode×count)

| 指標 | SP | PC |
|---|---|---|
| 本文 font-size | 15.66 (13–16) mode 16×15 n=57 | 16 (14–18) mode 16×18 n=57 |
| 本文 line-height 比 | 1.6 (1.5–1.8) mode 1.5×9 n=57 | 1.63 (1.5–1.8) mode 1.5×8 n=57 |
| h1 size | 26.2 (18.38–32) mode 32×10 n=52 | 32 (20–40) mode 32×9 n=53 |
| h2 size | 21.6 (16.6–25.29) mode 24×7 n=55 | 24 (17.25–30.5) mode 16×6 n=56 |
| h3 size | 18 (16–22) mode 16×9 n=43 | 20 (18–24) mode 24×6 n=43 |
| 本文コンテナ内幅 | 346.12 (318–359) mode 358×5 n=57 | 640 (470–716) mode 700×3 n=57 |
| コンテナ左右 padding | 0 (0–15) mode 0×37 n=57 | 0 (0–0) mode 0×44 n=57 |
| 段落 margin-bottom | 0 (0–16) mode 0×35 n=57 | 0 (0–18.43) mode 0×31 n=57 |
| 見出し margin-top | 13 (0–34.35) mode 0×27 n=60 | 0 (0–40) mode 0×31 n=61 |
| 見出し margin-bottom | 14.2 (0–19.94) mode 0×19 n=60 | 16 (8–21.33) mode 0×13 n=61 |
| ヘッダー高 | 66.5 (56–93) mode 51×4 n=58 | 76.5 (66.25–111.75) mode 60×4 n=60 |
| ヒーロー高 | 368 (261–844) mode 844×6 n=34 | 416 (250–639) mode 800×4 n=25 |
| ボタン高 | 40.5 (33–48) mode 33×4 n=46 | 37 (31.5–48.25) mode 30×4 n=48 |
| ボタン padding-x | 15.5 (10–20) mode 8×11 n=46 | 15 (10–20) mode 8×9 n=48 |
| ボタン padding-y | 8 (6.13–13) mode 8×8 n=46 | 8 (5.75–12.5) mode 4×8 n=48 |
| ボタン radius | 4.13 (0.5–11) mode 0×12 n=46 | 5.5 (4–17.5) mode 4×11 n=48 |
| ボタン font-size | 14 (13–16) mode 14×11 n=46 | 14.11 (13–16) mode 16×14 n=48 |
| FV 内 CTA 数 | 0 (0–1.5) mode 0×36 n=63 | 1 (0–2) mode 0×30 n=64 |
| 画像 radius | 0 (0–0) mode 0×55 n=60 | 0 (0–0) mode 0×57 n=61 |
| 影の要素数 | 2 (0–5) mode 0×23 n=63 | 1.5 (0–6.25) mode 0×23 n=64 |
| ボーダー幅 | 1 (1–1) mode 1×57 n=63 | 1 (1–1) mode 1×59 n=64 |
| カード数 | 1 (0–3) mode 0×31 n=63 | 1 (0–4) mode 0×27 n=64 |
| カード padding | 20 (11.5–28.5) mode 8×4 n=32 | 20 (12.8–32) mode 20×5 n=37 |
| カード radius | 1.5 (0–8) mode 0×16 n=32 | 0 (0–8) mode 0×20 n=37 |
| セクション padding-top | 36 (15–55.75) mode 15×5 n=46 | 48 (30–68.71) mode 30×4 n=43 |
| セクション padding-bottom | 30 (20–45) mode 20×6 n=39 | 40 (30–63) mode 48×5 n=38 |
| セクション間隔(最大側) | 33.08 (16.4–54) mode 20×4 n=55 | 40 (25.5–64) mode 30×5 n=54 |
| animation 要素 | 0 (0–0.5) mode 0×47 n=63 | 0 (0–0) mode 0×53 n=64 |
| transition 要素 | 38 (8–88.5) mode 0×5 n=63 | 53 (7.75–115.25) mode 5×5 n=64 |
| @keyframes 数 | 6 (1.5–19) mode 0×13 n=63 | 5.5 (1–20) mode 0×15 n=64 |
| rAF 呼出 | 11 (4–37.5) mode 4×18 n=63 | 13 (4–43.25) mode 4×17 n=64 |
| IntersectionObserver 生成 | 2 (0–14) mode 0×20 n=63 | 2 (0–13.75) mode 0×21 n=64 |
| scroll 系 listener | 7 (3–13) mode 3×9 n=63 | 6.5 (2–12.25) mode 1×7 n=64 |
| 44px 未満タップ率 | 0.63 (0.47–0.76) mode 0.76×4 n=61 | 0.68 (0.57–0.82) mode 0.61×3 n=62 |
| FV 本文文字数 | 169 (100–333) mode 16×2 n=63 | 161.5 (76–382.25) mode 44×2 n=64 |
| 転送 KB | 3916 (2686–5497.5) mode 1×2 n=63 | 3845 (2690–6695.75) mode 1×2 n=64 |
| リクエスト数 | 135 (87–233.5) mode 102×3 n=63 | 165.5 (88.25–258.25) mode 1×2 n=64 |
| 画像総 Mpx | 3.9 (1.3–10.6) mode 0×4 n=63 | 5.65 (1.68–11.5) mode 0.1×3 n=64 |
| webfont 数 | 2 (1–5) mode 1×17 n=63 | 2 (1–5) mode 1×17 n=64 |
| webfont KB | 65 (18–210) mode 0×12 n=63 | 65.5 (20.75–232.75) mode 0×12 n=64 |
| LCP ms | 880 (515–1655) mode 424×2 n=60 | 940 (532–1578) mode 84×1 n=63 |
| DOM ノード | 1105 (836–1631) mode 13×2 n=63 | 1106.5 (841.25–1590) mode 13×2 n=64 |

### 採用率

| 部品 | SP | PC |
|---|---|---|
| sticky ヘッダー率 | 65% (n=63) | 52% (n=64) |
| SP 下部固定率 | 35% (n=63) | 17% (n=64) |
| ハンバーガー率 | 46% (n=63) | 16% (n=64) |
| 目次率 | 19% (n=63) | 19% (n=64) |
| パンくず率 | 29% (n=63) | 30% (n=64) |
| ヒーロー動画率 | 0% (n=34) | 0% (n=25) |
| 影の使用率 | 63% (n=63) | 64% (n=64) |
| scroll-driven animation | 24% (n=63) | 25% (n=64) |
| reduced-motion 対応率 | 35% (n=63) | 34% (n=64) |
| canvas 率 | 5% (n=63) | 5% (n=64) |
| WebGL 率 | 5% (n=63) | 5% (n=64) |
| autoplay 動画率 | 2% (n=63) | 2% (n=64) |
| Lottie 率 | 0% (n=63) | 0% (n=64) |
| GSAP 率 | 3% (n=63) | 3% (n=64) |
| 動きの採用率 | 79% (n=63) | 77% (n=64) |
| 横スクロール発生率 | 10% (n=63) | 5% (n=64) |
| 本文 16px 未満率 | 51% (n=57) | 32% (n=57) |
| WordPress 率 | 32% (n=63) | 31% (n=64) |

### 角丸の分布 (PC)

- ボタン: 4px×11, 8px×7, 5px×5, 0px×4, 3px×2, 4.25px×2, 11px×2, 17.5px×2, 20px×2, 25px×2, 50px×2, 400px×2, 6px×1, 16px×1, 24px×1, 80px×1, 33554400px×1
- 画像: 0px×57, 2px×1, 5px×1, 8px×1, 50px×1
- カード: 0px×20, 8px×6, 4px×3, 3px×2, 12px×2, 6px×1, 10px×1, 11px×1, 16px×1

### フォント (PC)

- 本文 family 先頭: -apple-system×6, sans-serif×5, ヒラギノ角ゴ ProN W3×5, Hiragino Kaku Gothic ProN×4, acumin-pro×2, Helvetica Neue×2, 游ゴシック Medium×2, メイリオ×2, Hiragino Kaku Gothic W3 JIS2004×2, Avenir Next W00×2
- 読込済 webfont: Noto Sans JP×4, icomoon×4, Special Gothic Expanded One×2, FontAwesome×2, acumin-pro×2, Dongle×2, Quicksand×2, iconfont×2, DM Sans×2, M PLUS Rounded 1c×2

### 色 (PC)

- background (n=85): top #ffffff×42, #f8f7f6×4, #eeeeee×4, #131313×3, #f7f7f7×2, #192a3f×2; 色相 white×63, gray×7, black×6, orange×2, teal×1; 彩度 gray×61, muted×17, moderate×4, vivid×3; 明度 near-white×73, dark×9, mid-dark×2, mid×1
- text (n=62): top #000000×13, #222222×8, #333333×7, #ffffff×4, #23221e×2, #000a02×2; 色相 gray×36, black×17, white×4, red×1; 彩度 gray×55, muted×4, vivid×2, moderate×1; 明度 dark×35, mid-dark×16, mid×7, near-white×4
- link (n=62): top #000000×10, #ffffff×10, #333333×8, #919191×3, #222222×3, #23221e×2; 色相 gray×26, black×12, white×10, red×8; 彩度 gray×46, vivid×11, muted×3, moderate×2; 明度 dark×25, mid-dark×19, near-white×10, mid×8
- button (n=41): top #ffffff×12, #c9fe6e×2, #4169e1×2, #f1f1f1×2, #e0e0e0×2, #0a8935×2; 色相 white×16, gray×7, lime×4, teal×3, red×2, orange×1, green×1, black×1; 彩度 gray×22, vivid×12, moderate×5, muted×2; 明度 near-white×17, mid-dark×13, light×5, mid×5, dark×1
- screenTop (n=384): top #ffffff×59, #f0f0f0×59, #e0e0e0×44, #d0d0d0×31, #c0c0c0×19, #202020×18; 色相 gray×197, white×81, black×32, red×29, yellow×13, cyan×11, teal×4, lime×3, orange×2, magenta×1; 彩度 gray×280, vivid×59, muted×30, moderate×15; 明度 near-white×160, light×108, dark×62, mid×31, mid-dark×23
- accent (n=30): top #c9fe6e×2, #1020d0×2, #0000bb×2, #19292d×2, #222266×2, #4169e1×2; 色相 red×10, lime×4, teal×3, orange×1, green×1; 彩度 vivid×21, moderate×7, muted×2; 明度 mid-dark×19, mid×7, light×2, dark×2

## pattern: service (n=34)

ids: service-jp-035, service-jp-036, service-jp-037, service-jp-038, service-jp-039, service-jp-040, service-jp-041, service-jp-042, service-jp-043, service-jp-044, service-jp-045, service-jp-046, service-jp-047, service-jp-048, service-jp-049, service-jp-050, service-jp-051, service-jp-052, service-jp-053, service-jp-054, service-jp-055, service-jp-056, service-jp-057, service-jp-058, service-jp-059, service-other-060, service-other-061, service-other-062, service-other-063, service-other-064, service-other-065, service-other-066, service-other-067, service-other-068

### 数値 (median (q1–q3) mode×count)

| 指標 | SP | PC |
|---|---|---|
| 本文 font-size | 16 (14–16) mode 16×15 n=34 | 16 (14.25–16) mode 16×20 n=34 |
| 本文 line-height 比 | 1.6 (1.5–1.75) mode 1.6×7 n=34 | 1.6 (1.5–1.79) mode 1.6×7 n=34 |
| h1 size | 32 (15.5–38.5) mode 36×3 n=28 | 44 (15–58.27) mode 56×3 n=31 |
| h2 size | 24 (22.5–31.5) mode 24×9 n=34 | 35 (23.73–44) mode 32×5 n=34 |
| h3 size | 18 (16–20) mode 16×10 n=34 | 19.86 (16.22–24) mode 24×7 n=34 |
| 本文コンテナ内幅 | 345.5 (303–358) mode 350×5 n=34 | 532.5 (333–952.5) mode 360×2 n=34 |
| コンテナ左右 padding | 0 (0–0) mode 0×28 n=34 | 0 (0–7.5) mode 0×25 n=34 |
| 段落 margin-bottom | 0 (0–0) mode 0×30 n=34 | 0 (0–0) mode 0×30 n=34 |
| 見出し margin-top | 7.5 (0–12.45) mode 0×16 n=34 | 2 (0–15) mode 0×17 n=34 |
| 見出し margin-bottom | 16 (5.5–24) mode 0×7 n=34 | 16 (10–24) mode 24×6 n=34 |
| ヘッダー高 | 64 (56–70) mode 60×4 n=34 | 88 (78–108) mode 80×3 n=33 |
| ヒーロー高 | 760.5 (552.75–844) mode 844×7 n=32 | 728 (588.5–800) mode 800×7 n=31 |
| ボタン高 | 51.5 (42.75–64.5) mode 50×3 n=32 | 52 (46–68.5) mode 48×3 n=32 |
| ボタン padding-x | 16 (12.75–22.5) mode 16×7 n=32 | 20 (16–29.99) mode 16×6 n=32 |
| ボタン padding-y | 12 (8–14.5) mode 12×6 n=32 | 12 (8–16) mode 12×6 n=32 |
| ボタン radius | 8 (6–50) mode 8×10 n=32 | 8 (4.75–52.5) mode 8×7 n=32 |
| ボタン font-size | 16 (14–16) mode 16×17 n=32 | 16 (14–16) mode 16×17 n=32 |
| FV 内 CTA 数 | 2 (0.25–3) mode 0×9 n=34 | 3 (1.25–5.75) mode 2×6 n=34 |
| 画像 radius | 0 (0–0) mode 0×33 n=34 | 0 (0–0) mode 0×33 n=34 |
| 影の要素数 | 17 (4.25–39) mode 0×3 n=34 | 22 (4.25–43.25) mode 0×3 n=34 |
| ボーダー幅 | 1 (1–1) mode 1×30 n=34 | 1 (1–1) mode 1×29 n=34 |
| カード数 | 6 (2.25–13.75) mode 0×4 n=34 | 7.5 (3–17.5) mode 3×4 n=34 |
| カード padding | 17.5 (14–24) mode 24×7 n=30 | 24 (15.75–27.5) mode 24×8 n=32 |
| カード radius | 8 (6.25–12) mode 8×9 n=30 | 8 (3–13) mode 0×8 n=32 |
| セクション padding-top | 54.05 (40–68) mode 60×5 n=32 | 80 (62.72–120) mode 80×7 n=31 |
| セクション padding-bottom | 50 (40–76.49) mode 80×6 n=30 | 80 (57.5–114) mode 80×7 n=32 |
| セクション間隔(最大側) | 60 (40–80) mode 60×5 n=34 | 80 (50–112) mode 80×7 n=33 |
| animation 要素 | 1 (0–3) mode 0×12 n=34 | 1 (0–4) mode 0×12 n=34 |
| transition 要素 | 74.5 (50.25–152) mode 61×2 n=34 | 112 (66.75–175.25) mode 29×2 n=34 |
| @keyframes 数 | 14 (4.25–37.75) mode 0×3 n=34 | 13.5 (4.25–37.5) mode 0×3 n=34 |
| rAF 呼出 | 30 (8–136) mode 4×4 n=34 | 32.5 (8.5–131.5) mode 5×4 n=34 |
| IntersectionObserver 生成 | 2 (0–4.75) mode 0×10 n=34 | 2 (0–4) mode 0×11 n=34 |
| scroll 系 listener | 14.5 (9–21.75) mode 9×5 n=34 | 13.5 (7.25–19) mode 5×3 n=34 |
| 44px 未満タップ率 | 0.58 (0.38–0.72) mode 0.26×2 n=34 | 0.66 (0.51–0.78) mode 0.66×3 n=34 |
| FV 本文文字数 | 215 (97.75–333.75) mode 202×2 n=34 | 217 (129.75–406.75) mode 171×2 n=34 |
| 転送 KB | 9457 (4995.75–16192) mode 3238×1 n=34 | 7740.5 (5368.25–15857.75) mode 2966×1 n=34 |
| リクエスト数 | 254.5 (195.25–356.5) mode 125×1 n=34 | 273 (198–317.75) mode 292×2 n=34 |
| 画像総 Mpx | 20.45 (6.05–45.85) mode 1.6×2 n=34 | 29 (9.1–49.5) mode 6.6×2 n=34 |
| webfont 数 | 25.5 (5–42) mode 5×4 n=34 | 26.5 (5–42) mode 5×6 n=34 |
| webfont KB | 747 (315.5–1013) mode 0×1 n=34 | 759.5 (272.25–1019.5) mode 0×1 n=34 |
| LCP ms | 1668 (1461–2262) mode 768×2 n=34 | 1644 (1282–2341) mode 636×1 n=34 |
| DOM ノード | 1637 (1337.25–2766.25) mode 1066×1 n=34 | 1624.5 (1302.75–2770.5) mode 1066×1 n=34 |

### 採用率

| 部品 | SP | PC |
|---|---|---|
| sticky ヘッダー率 | 94% (n=34) | 82% (n=34) |
| SP 下部固定率 | 26% (n=34) | 18% (n=34) |
| ハンバーガー率 | 56% (n=34) | 15% (n=34) |
| 目次率 | 0% (n=34) | 0% (n=34) |
| パンくず率 | 21% (n=34) | 21% (n=34) |
| ヒーロー動画率 | 13% (n=32) | 19% (n=31) |
| 影の使用率 | 91% (n=34) | 91% (n=34) |
| scroll-driven animation | 12% (n=34) | 12% (n=34) |
| reduced-motion 対応率 | 56% (n=34) | 56% (n=34) |
| canvas 率 | 9% (n=34) | 9% (n=34) |
| WebGL 率 | 6% (n=34) | 3% (n=34) |
| autoplay 動画率 | 21% (n=34) | 21% (n=34) |
| Lottie 率 | 6% (n=34) | 6% (n=34) |
| GSAP 率 | 12% (n=34) | 12% (n=34) |
| 動きの採用率 | 91% (n=34) | 91% (n=34) |
| 横スクロール発生率 | 0% (n=34) | 0% (n=34) |
| 本文 16px 未満率 | 47% (n=34) | 29% (n=34) |
| WordPress 率 | 35% (n=34) | 35% (n=34) |

### 角丸の分布 (PC)

- ボタン: 8px×7, 0px×4, 4px×4, 9999px×4, 5px×1, 6px×1, 7px×1, 12px×1, 14px×1, 16px×1, 24px×1, 32px×1, 50px×1, 60px×1, 80px×1, 160px×1, 400px×1
- 画像: 0px×33, 50px×1
- カード: 0px×8, 8px×6, 10px×3, 12px×3, 16px×3, 4px×1, 6px×1, 7px×1, 9px×1, 24px×1, 39.38px×1, 48px×1, 60px×1, 9999px×1

### フォント (PC)

- 本文 family 先頭: Noto Sans JP×11, YakuHanJP×2, Inter×2, Poppins×2, AdjustedYuGothic×1, Chatwork Sans R×1, HCo Gotham SSm×1, Roboto×1, NotoSansJP-circled-1×1, Zen Kaku Gothic New×1
- 読込済 webfont: Noto Sans JP×19, Roboto×4, Inter×4, Poppins×4, YakuHanJP×2, icomoon×2, slick×2, Montserrat×2, Font Awesome 6 Free×2, Material Symbols Outlined×2

### 色 (PC)

- background (n=52): top #ffffff×24, #08090a×2, #fcfbf8×2, #fcfcfa×2, #f4f8f9×1, #f7f5f5×1; 色相 white×45, black×2, orange×1, teal×1, red×1; 彩度 gray×30, muted×11, moderate×7, vivid×4; 明度 near-white×48, dark×2, mid-dark×1, mid×1
- text (n=32): top #333333×4, #000000×3, #222222×3, #23221f×1, #323232×1, #202226×1; 色相 gray×18, black×5, red×2, white×1; 彩度 gray×23, muted×7, vivid×2; 明度 dark×15, mid-dark×13, near-white×2, light×1, mid×1
- link (n=34): top #ffffff×7, #333333×5, #000000×4, #23221f×1, #202226×1, #fafafa×1; 色相 gray×14, white×9, black×6, red×2; 彩度 gray×26, muted×5, vivid×2, moderate×1; 明度 dark×12, near-white×10, mid-dark×9, mid×2, light×1
- button (n=30): top #ffffff×15, #2864f0×1, #ff8800×1, #1c9e47×1, #222222×1, #ffb300×1; 色相 white×17, orange×2, teal×2, gray×2, cyan×1; 彩度 gray×18, vivid×9, moderate×2, muted×1; 明度 near-white×18, mid×6, mid-dark×4, dark×2
- screenTop (n=204): top #ffffff×33, #f0f0f0×32, #e0e0e0×15, #f0f0ff×10, #f0ffff×9, #101010×5; 色相 gray×65, white×64, red×13, cyan×12, black×8, orange×7, teal×5, yellow×2, green×1; 彩度 gray×105, vivid×68, muted×19, moderate×12; 明度 near-white×109, light×29, mid×24, mid-dark×23, dark×19
- accent (n=15): top #2864f0×1, #dae3ed×1, #ff8800×1, #1c9e47×1, #007bff×1, #2d344b×1; 色相 orange×2, teal×2, red×1, cyan×1; 彩度 vivid×10, muted×3, moderate×2; 明度 mid×7, mid-dark×6, light×1, dark×1
