$.getScript( mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/modules/MathJax/MathJax.js?config=TeX-AMS-MML_CHTML',
	function () {
		var extensions = ["tex2jax.js"];
		if( mw.config.get('wgSmjUseChem') ) extensions.push("TeX/mhchem3/mhchem.js");
		MathJax.Ajax.config.root = mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/modules/MathJax';
		MathJax.Hub.Config({
			showMathMenu: false,
			extensions: extensions,
			jax: ["input/TeX", "output/HTML-CSS"],
			"HTML-CSS": { scale: mw.config.get('wgSmjSize') },
			tex2jax: { inlineMath: mw.config.get('wgSmjInlineMath') }
		});
		MathJax.Hub.Queue(function() { $(".MathJax").parent().show(); });
	}
);

