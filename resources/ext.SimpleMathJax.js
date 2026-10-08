mw.libs.smj = mw.libs.smj || {};
(function () {
var loaded = false;
var scriptLoadPromise = null;

// Defaults match extension.json. The page HTML that carries these settings
// can be cached (CDN, reverse proxy) separately from this module, so after an
// upgrade the two may come from different versions; fall back instead of
// failing to render any formula.
var defaults = {
  wgSmjCdnEnabled: true,
  wgSmjCdnVersion: '4',
  wgSmjDelimitersEnabled: false,
  wgSmjDelimitersInlineMath: [],
  wgSmjDelimitersDisplayMath: [],
  wgSmjIgnoreHtmlClass: 'mathjax_ignore|comment|diff-(context|addedline|deletedline)',
  wgSmjScale: 1,
  wgSmjExplorerEnabled: false
};

function config(name) {
  var value = mw.config.get(name);
  return value === null || value === undefined ? defaults[name] : value;
}

function ensureLoaded() {
  if (loaded) {
    return;
  }
  loaded = true;
  window.MathJax = {
    tex: {
      inlineMath: config('wgSmjDelimitersInlineMath').concat([['[math]','[/math]']]),
      displayMath: config('wgSmjDelimitersDisplayMath'),
      processEnvironments: true,
      processRefs: config('wgSmjDelimitersEnabled'),
      processEscapes: config('wgSmjDelimitersEnabled'),
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
      ignoreHtmlClass: config('wgSmjIgnoreHtmlClass'),
      processHtmlClass: "mathjax_process|smj-container",
      // Semantic enrichment drives MathJax 4's explorer: a click selects and
      // highlights a symbol. Readers can still turn it on from the menu.
      menuOptions: {
        settings: {
          enrich: config('wgSmjExplorerEnabled')
        }
      }
    },
    chtml: {
      scale: config('wgSmjScale'),
    },
    loader: {
      load: ['ui/safe','[tex]/autoload'].concat(mw.config.exists('smjPreloadChem') ? ['[tex]/mhchem'] : [])
    },
    startup: {
      elements: config('wgSmjDelimitersEnabled') ? null : ["span.smj-container"],
      pageReady: () => {
        return MathJax.startup.defaultPageReady().then(() => {
          document.querySelectorAll("span.smj-container > .MathJax").forEach((mjx) => {
            mjx.parentElement.style.opacity = 1;
          });
        });
      }
    }
  };

  var script = document.createElement('script');
  script.src = config('wgSmjCdnEnabled')
    ? 'https://cdn.jsdelivr.net/npm/mathjax@' + config('wgSmjCdnVersion') + '/tex-chtml.js'
    : mw.config.get('wgExtensionAssetsPath') + '/SimpleMathJax/resources/MathJax/tex-chtml.js';
  script.async = true;
  scriptLoadPromise = new Promise((resolve, reject) => {
    script.onload = resolve;
    script.onerror = () => {
      loaded = false;
      reject();
    };
  });
  document.head.appendChild(script);
}

mw.libs.smj.ensureLoaded = ensureLoaded;

mw.libs.smj.whenReady = function () {
  ensureLoaded();
  return scriptLoadPromise.then(() => MathJax.startup.promise);
};

var typesetQueue = Promise.resolve();
mw.libs.smj.typeset = function (elements) {
  var result = typesetQueue
    .then(() => mw.libs.smj.whenReady())
    .then(() => MathJax.typesetPromise(elements));
  typesetQueue = result.catch(() => {});
  return result;
};

mw.hook('wikipage.content').add(function ($content) {
  var alreadyLoaded = loaded;
  ensureLoaded();
  if (!alreadyLoaded) {
    return;
  }
  var $containers = $content.filter('.smj-container').add($content.find('.smj-container'));
  var delimitersEnabled = config('wgSmjDelimitersEnabled');
  if (!delimitersEnabled && !$containers.length) {
    return;
  }
  var elements = delimitersEnabled ? $content.toArray() : $containers.toArray();
  mw.libs.smj.typeset(elements).then(() => {
    $containers.children('.MathJax').parent().css('opacity', 1);
  });
});
})();
