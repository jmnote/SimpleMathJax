$.getScript( mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/modules/MathJax/MathJax.js',
	function () {
		MathJax.Ajax.config.root = mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/modules/MathJax';
		var extensions = ["tex2jax.js","TeX/AMSmath.js"];
		if( mw.config.get('wgSmjUseChem') ) extensions.push("TeX/mhchem.js");
		MathJax.Hub.Config({
			showMathMenu: false,
			extensions: extensions,
			jax: ["input/TeX", "output/HTML-CSS"],
			"HTML-CSS": { scale: mw.config.get('wgSmjSize') },
			tex2jax: { inlineMath: mw.config.get('wgSmjInlineMath') }
		});
		MathJax.Hub.Queue(function() {
			$(".MathJax").parent().css('opacity',1);
		});
	}
);

