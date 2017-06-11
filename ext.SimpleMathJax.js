MathJax.Ajax.config.root = mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/MathJax';
MathJax.Hub.Config({
	"messageStyle": "none",
	"HTML-CSS": { scale: mw.config.get('wgSimpleMathJaxSize') },
	"tex2jax": {
		"preview": "none",
		"inlineMath": [["[math]","[/math]"]]
	}
});
MathJax.Hub.Queue( function() {
	$(".MathJax").parent().show();
});
