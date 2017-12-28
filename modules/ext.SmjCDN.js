function loadScripts(scripts, index, callback) {
	$.getScript(scripts[index], function () {
		if(index + 1 <= scripts.length - 1) {
			loadScripts(scripts, index + 1, callback);
			return;
		}
		if(callback) callback();
	});
};
var SmjScripts = ['//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=TeX-AMS-MML_HTMLorMML'];
if( mw.config.get('wgSmjUseChem') )
		SmjScripts.push( '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/extensions/TeX/mhchem.js' );
loadScripts(SmjScripts, 0, function() {
	MathJax.Hub.Config({
		showMathMenu: false,
		"HTML-CSS": { scale: mw.config.get('wgSmjSize') },
		tex2jax: { inlineMath: mw.config.get('wgSmjInlineMath') }
	});
	MathJax.Hub.Queue(function() { $(".MathJax").parent().show(); });
});

