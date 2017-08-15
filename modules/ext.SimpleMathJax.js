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
