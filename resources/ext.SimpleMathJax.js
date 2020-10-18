console.log( 'SmjExtraInlineMath', mw.config.get('SmjExtraInlineMath') );
window.MathJax = {
  tex: {
    inlineMath: mw.config.get('wgSmjExtraInlineMath').concat([['[math]','[/math]']]),
    displayMath: mw.config.get('wgSmjDisplayMath'),
    packages: mw.config.get('wgSmjUseChem') ? {'[+]': ['mhchem']} : {}
  },
  chtml: {
    scale: mw.config.get('wgSmjScale')
  },
  loader: {
    load: mw.config.get('wgSmjUseChem') ? ['[tex]/mhchem'] : []
  },
  startup: {
    pageReady: () => {
      return MathJax.startup.defaultPageReady().then(() => {
        $(".MathJax").parent().css('opacity',1);
      });
    }
  }
};
(function () {
  var script = document.createElement('script');
  script.src = mw.config.get('wgSmjUseCdn') ? 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js'
    : mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/resources/MathJax/es5/tex-chtml.js';
  script.async = true;
  document.head.appendChild(script);
})();
