<<<<<<< HEAD
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
=======
(window.RLQ=window.RLQ||[]).push(function() {
	MathJax.Ajax.config.root = mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/modules/MathJax';
	MathJax.Hub.Config({
		"messageStyle": "none",
		"HTML-CSS": { scale: mw.config.get('wgSimpleMathJaxSize') },
		"tex2jax": {
			"preview": "none",
			"inlineMath": mw.config.get('wgSimpleMathJaxInlineMath')
		}
	});
	MathJax.Hub.Queue( function() { $(".MathJax").parent().show(); });
});
>>>>>>> 82c6b5ce1ffe3e251bb9cef0e709f1161c8fe49b
