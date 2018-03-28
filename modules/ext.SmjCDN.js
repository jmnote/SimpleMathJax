$.getScript( '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.3/MathJax.js?config=TeX-AMS_HTML',
	function () {
		var extensions = ["tex2jax.js"];
		if( mw.config.get('wgSmjUseChem') ) {
			window.MathJax.Ajax.config.path["mhchem"] = '//cdnjs.cloudflare.com/ajax/libs/mathjax-mhchem/3.3.0';
			extensions.push("[mhchem]/mhchem.js");
		}
		window.MathJax.Hub.Config({
			showMathMenu: false,
			extensions: extensions,
			jax: ["input/TeX", "output/HTML-CSS"],
			"HTML-CSS": { scale: mw.config.get('wgSmjSize') },
			tex2jax: { inlineMath: mw.config.get('wgSmjInlineMath') }
		});
		window.MathJax.Hub.Queue(function() { 
			$(".MathJax").parent().css('opacity',1);
		});
	}
);