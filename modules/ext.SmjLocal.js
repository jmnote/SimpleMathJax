$.getScript( mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/modules/MathJax/MathJax.js?config=TeX-AMS-MML_CHTML',
	function () {
		var extensions = ["tex2jax.js"];
		MathJax.Ajax.config.root = mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/modules/MathJax';
		if( mw.config.get('wgSmjUseChem') ) {
			MathJax.Ajax.config.path["mhchem"] = MathJax.Ajax.config.root + '/extensions/TeX';
			extensions.push("[mhchem]/mhchem.js");
		}
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

