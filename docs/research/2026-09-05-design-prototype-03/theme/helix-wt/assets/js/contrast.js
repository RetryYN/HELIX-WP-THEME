/* 自動コントラスト guard（R75 / P28）: 写真の上に文字を置く要素の画像を canvas で標本化し、
   下部（文字が載る領域）の相対輝度から data-wt-lum = dark / mid / light を付ける。CSS 側でスクリム強度を切替。
   同一オリジンでない・読めない画像は属性を付けず（= 最強スクリムの既定）安全側に倒す。
   ゲート検査: 文字色 #fff と「画像輝度 × (1 − スクリム不透明度) + 0 × 不透明度」の合成輝度からコントラスト比を計算し 4.5:1（大文字 3:1）を判定する。 */
(function(){
  var targets = document.querySelectorAll('body.wt-eyecatch-hero .wt-posthead__img, .wp-block-cover.is-style-wt-scrim, [data-wt-scrim]');
  if (!targets.length) return;
  var lum = function(r,g,b){ var f = function(c){ c/=255; return c<=0.03928? c/12.92 : Math.pow((c+0.055)/1.055,2.4); }; return 0.2126*f(r)+0.7152*f(g)+0.0722*f(b); };
  targets.forEach(function(el){
    var img = el.querySelector('img');
    if (!img) return;
    var go = function(){
      try {
        if (new URL(img.currentSrc || img.src, location.href).origin !== location.origin) return; // 他オリジンは既定（強）のまま
        var c = document.createElement('canvas'), w = 32, h = 32; c.width = w; c.height = h;
        var ctx = c.getContext('2d'); ctx.drawImage(img, 0, 0, w, h);
        var d = ctx.getImageData(0, Math.floor(h * 0.45), w, h - Math.floor(h * 0.45)).data; // 下部 55%（文字が載る領域）
        var sum = 0, n = 0;
        for (var i = 0; i < d.length; i += 4) { sum += lum(d[i], d[i+1], d[i+2]); n++; }
        var L = sum / n;
        var cls = L < 0.12 ? 'dark' : (L < 0.35 ? 'mid' : 'light');
        el.setAttribute('data-wt-lum', cls);
        el.setAttribute('data-wt-lum-value', L.toFixed(3));
      } catch (e) { /* 読めない画像 = 属性なし = 強スクリム */ }
    };
    if (img.complete && img.naturalWidth) go(); else img.addEventListener('load', go, { once: true });
  });
})();
