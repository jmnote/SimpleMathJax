function loadScripts(scripts, index, callback) {
	$.getScript(scripts[index], function () {
		if(index + 1 <= scripts.length - 1) {
			loadScripts(scripts, index + 1, callback);
			return;
		}
		if(callback) callback();
	});
}
loadScripts(mw.config.get('wgSmjScripts'), 0, function() {
	MathJax.Hub.Config({
		"messageStyle": "none",
		"HTML-CSS": { scale: mw.config.get('wgSmjSize') },
		"tex2jax": {
			"preview": "none",
			"inlineMath": mw.config.get('wgSmjInlineMath')
		}
	});
	MathJax.Hub.Queue(function() {
		$(".MathJax").parent().show();
	});
});