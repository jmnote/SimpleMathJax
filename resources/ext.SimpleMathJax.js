mw.hook( 'wikipage.content' ).add( function ( $content ) {
window.MathJax = {
  tex: {
    inlineMath: mw.config.get('wgSmjDirectMath').inlineMath.concat([['[math]','[/math]']]),
    displayMath: mw.config.get('wgSmjDirectMath').displayMath,
    processEnvironments: true,
    processRefs: mw.config.get('wgSmjDirectMath').enabled,
    processEscapes: mw.config.get('wgSmjDirectMath').enabled,
    packages: mw.config.exists('smjPreloadChem') ? {'[+]': ['autoload','mhchem']} : {'[+]': ['autoload']},
    macros: {
      AA: "{\u00c5}",
      alef: "{\\aleph}",
      alefsym: "{\\aleph}",
      Alpha: "{\\mathrm{A}}",
      and: "{\\land}",
      ang: "{\\angle}",
      Bbb: "{\\mathbb}",
      Beta: "{\\mathrm{B}}",
      bold: "{\\mathbf}",
      bull: "{\\bullet}",
      C: "{\\mathbb{C}}",
      Chi: "{\\mathrm{X}}",
      clubs: "{\\clubsuit}",
      cnums: "{\\mathbb{C}}",
      Complex: "{\\mathbb{C}}",
      coppa: "{\u03D9}",
      Coppa: "{\u03D8}",
      Dagger: "{\\ddagger}",
      Digamma: "{\u03DC}",
      darr: "{\\downarrow}",
      dArr: "{\\Downarrow}",
      Darr: "{\\Downarrow}",
      dashint: "{\\unicodeInt{x2A0D}}",
      ddashint: "{\\unicodeInt{x2A0E}}",
      diamonds: "{\\diamondsuit}",
      empty: "{\\emptyset}",
      Epsilon: "{\\mathrm{E}}",
      Eta: "{\\mathrm{H}}",
      euro: "{\u20AC}",
      exist: "{\\exists}",
      geneuro: "{\u20AC}",
      geneuronarrow: "{\u20AC}",
      geneurowide: "{\u20AC}",
      H: "{\\mathbb{H}}",
      hAar: "{\\Leftrightarrow}",
      harr: "{\\leftrightarrow}",
      Harr: "{\\Leftrightarrow}",
      hearts: "{\\heartsuit}",
      image: "{\\Im}",
      infin: "{\\infty}",
      Iota: "{\\mathrm{I}}",
      isin: "{\\in}",
      Kappa: "{\\mathrm{K}}",
      koppa: "{\u03DF}",
      Koppa: "{\u03DE}",
      lang: "{\\langle}",
      larr: "{\\leftarrow}",
      Larr: "{\\Leftarrow}",
      lArr: "{\\Leftarrow}",
      lrarr: "{\\leftrightarrow}",
      Lrarr: "{\\Leftrightarrow}",
      lrArr: "{\\Leftrightarrow}",
      Mu: "{\\mathrm{M}}",
      N: "{\\mathbb{N}}",
      natnums: "{\\mathbb{N}}",
      Nu: "{\\mathrm{N}}",
      O: "{\\emptyset}",
      oiint: "{\\unicodeInt{x222F}}",
      oiiint: "{\\unicodeInt{x2230}}",
      ointctrclockwise: "{\\unicodeInt{x2233}}",
      officialeuro: "{\u20AC}",
      Omicron: "{\\mathrm{O}}",
      or: "{\\lor}",
      P: "{\u00B6}",
      pagecolor: ["",1],
      part: "{\\partial}",
      plusmn: "{\\pm}",
      Q: "{\\mathbb{Q}}",
      R: "{\\mathbb{R}}",
      rang: "{\\rangle}",
      rarr: "{\\rightarrow}",
      Rarr: "{\\Rightarrow}",
      rArr: "{\\Rightarrow}",
      real: "{\\Re}",
      reals: "{\\mathbb{R}}",
      Reals: "{\\mathbb{R}}",
      Rho: "{\\mathrm{P}}",
      sdot: "{\\cdot}",
      sampi: "{\u03E1}",
      Sampi: "{\u03E0}",
      sect: "{\\S}",
      spades: "{\\spadesuit}",
      stigma: "{\u03DB}",
      Stigma: "{\u03DA}",
      sub: "{\\subset}",
      sube: "{\\subseteq}",
      supe: "{\\supseteq}",
      Tau: "{\\mathrm{T}}",
      textvisiblespace: "{\u2423}",
      thetasym: "{\\vartheta}",
      uarr: "{\\uparrow}",
      uArr: "{\\Uparrow}",
      Uarr: "{\\Uparrow}",
      unicodeInt: ["{\\mathop{\\vcenter{\\mathchoice{\\huge\\unicode{#1}\\,}{\\unicode{#1}}{\\unicode{#1}}{\\unicode{#1}}}\\,}\\nolimits}", 1],
      varcoppa: "{\u03D9}",
      varstigma: "{\u03DB}",
      varointclockwise: "{\\unicodeInt{x2232}}",
      vline: ["{\\smash{\\large\\lvert #1}", 0],
      weierp: "{\\wp}",
      Z: "{\\mathbb{Z}}",
      Zeta: "{\\mathrm{Z}}"
    },
    environments: {
      displaymjx: ["", ""]
    }
  },
  options: {
    ignoreHtmlClass: mw.config.get('wgSmjIgnoreHtmlClass'),
    processHtmlClass: "mathjax_process|smj-container"
  },
  chtml: {
    scale: mw.config.get('wgSmjScale'),
    displayAlign: mw.config.get('wgSmjDisplayAlign'),
    displayOverflow: mw.config.exists('smjLinebreak') ? 'linebreak' : 'overflow',
    linebreaks: {
      // getLinebreakWidth() resolves this against the container's width in
      // *unscaled* ems, while the equation's own width is measured in its
      // own (scaled) ems — so without compensating, a scale != 1 makes it
      // misjudge how much actually fits. Shrinking the percentage by the
      // same factor cancels that out.
      width: (100 / mw.config.get('wgSmjScale')) + '%'
    }
  },
  loader: {
    load: ['ui/safe','[tex]/autoload'].concat(mw.config.exists('smjPreloadChem') ? ['[tex]/mhchem'] : [])
  },
  startup: {
    elements: mw.config.get('wgSmjDirectMath').enabled ? null : ["span.smj-container"],
    pageReady: () => {
      return MathJax.startup.defaultPageReady().then(() => {
        $("span.smj-container > .MathJax").parent().css('opacity',1);
      });
    }
  }
};
(function () {
  var script = document.createElement('script');
  script.src = mw.config.get('wgSmjUseCdn')
    ? 'https://cdn.jsdelivr.net/npm/mathjax@4/tex-chtml.js'
    : mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/resources/MathJax/tex-chtml.js';
  script.async = true;
  document.head.appendChild(script);
})();
});
