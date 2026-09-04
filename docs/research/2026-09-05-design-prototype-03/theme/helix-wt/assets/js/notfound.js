/* 404 検索語提案変種: URL のパスから語を切り出し、検索リンクにする（サーバ側の候補は Query Loop の人気記事） */
(function(){
  var ul = document.querySelector('.wt-suggest[data-wt-from-path]');
  if (!ul) return;
  var words = decodeURIComponent(location.pathname).split(/[\/\-_.+%\s]+/).filter(function(w){ return w && w.length >= 2 && !/^\d+$/.test(w) && !/^(html?|php|index|page|category|tag)$/i.test(w); }).slice(0, 5);
  if (!words.length) { ul.closest('.wt-404__variant--suggest') && ul.remove(); return; }
  words.forEach(function(w){ var li = document.createElement('li'); var a = document.createElement('a'); a.href = '/?s=' + encodeURIComponent(w); a.textContent = '「' + w + '」で検索'; li.appendChild(a); ul.appendChild(li); });
  var input = document.querySelector('.wt-404__search input[type="search"]'); if (input && !input.value) input.value = words.join(' ');
})();
