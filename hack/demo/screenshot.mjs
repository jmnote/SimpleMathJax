#!/usr/bin/env node
// Screenshots a page of the local test wiki (see demo.sh) with a real
// headless browser, so the captured image reflects MathJax's actual
// client-side rendering — not just the raw parsed HTML.
//
// Usage: URL=... OUT=... RIGHT_CLICK=<selector> RIGHT_CLICK_OUT=... node screenshot.mjs
// (env vars, not positional args, so setting only one of them can't shift
// the other — `make screenshot OUT=...` just works)
//
// RIGHT_CLICK, when set, takes one extra screenshot (to RIGHT_CLICK_OUT)
// after right-clicking the LAST element matching that CSS selector — used
// by demo.sh's `addRightClickShot: true` field to also show MathJax's context menu
// (only visible on right-click, so the plain OUT capture can't demonstrate
// $wgSmjEnableMenu on its own). The normal OUT capture always happens
// first, unaffected by this.
import puppeteer from 'puppeteer';
import fs from 'node:fs';
import path from 'node:path';

// The container/CI environment this runs in has no CJK fonts installed, so
// Korean/Japanese/Chinese text in a demo page (including inside MathJax's
// own output) would render blank. Rather than depend on system fonts
// (apt-get, different per host), load pinned Fontsource packages and apply
// them as fallbacks. One sans (regular text) and one serif (MathJax defaults
// to a serif TeX look), so each keeps the weight it would normally have.
const DEMO_DIR = path.dirname(new URL(import.meta.url).pathname);

function localFontCss(packageName) {
	const packageDir = path.join(DEMO_DIR, 'node_modules', '@fontsource', packageName);
	let css = fs.readFileSync(path.join(packageDir, 'index.css'), 'utf8');
	return css.replace(/url\(\.\/files\/([^\)]+)\)/g, (match, filename) => {
		const font = fs.readFileSync(path.join(packageDir, 'files', filename));
		return `url(data:font/woff2;base64,${font.toString('base64')})`;
	});
}

const url = process.env.URL ?? 'http://localhost:8080/index.php/Demo';
const outfile = process.env.OUT ?? '../../docs/demo1-screenshot.png';

// --no-sandbox is needed when running as root (e.g. in CI or a dev
// container) — Chromium's sandbox refuses to start otherwise.
const browser = await puppeteer.launch(
	process.getuid?.() === 0 ? { args: ['--no-sandbox'] } : {}
);
try {
	const page = await browser.newPage();
	await page.setViewport({ width: 1024, height: 768 });
	await page.goto(url, { waitUntil: 'networkidle0' });
	// Real browsers already show CJK fine here — the OS substitutes any
	// installed CJK font for glyphs missing from whatever font is named
	// (even MathJax's own inline `font-family: MJXZERO, serif` on its
	// `<mjx-utext>` text nodes, used only for characters outside its own
	// font, and the browser default `pre, code { font-family: monospace }`
	// used by <syntaxhighlight> blocks). This sandbox just has no CJK font
	// at all, so nothing to substitute. `!important` is needed because
	// these are all higher-priority than inheriting from `body`.
	//
	// The per-script substitution CDP offers for exactly this
	// (`Page.setFontFamilies`) would avoid hand-listing every selector that
	// names its own font-family below, but it's a no-op in this headless
	// Chromium (call succeeds, page still shows tofu) — tried and reverted,
	// see git history if revisiting.
	//
	// mjx-utext is scoped narrowly, not every mjx-container descendant:
	// stretchy delimiters like \left(...\right) are sized glyphs from
	// MathJax's own font (e.g. class "TEX-S2"), not text — forcing a web
	// font onto those too breaks their metrics, so `\left(` stops growing
	// to match its contents. pre/code keep "monospace" first so Latin text
	// stays aligned, with the web font only as a per-glyph fallback for CJK.
	await page.addStyleTag({ content: localFontCss('noto-sans-kr') });
	await page.addStyleTag({ content: localFontCss('noto-serif-kr') });
	await page.addStyleTag({
		content: `
			body { font-family: 'Noto Sans KR', sans-serif !important; }
			mjx-utext { font-family: 'Noto Serif KR', serif !important; }
			pre, code { font-family: monospace, 'Noto Sans KR' !important; }
			.diff-addedline, .diff-deletedline, .diff-context,
			.mw-diff-inline-added, .mw-diff-inline-deleted,
			.mw-diff-inline-moved, .mw-diff-inline-changed,
			.mw-diff-inline-context { font-family: monospace, 'Noto Sans KR' !important; }
		`,
	});
	await page.evaluate(() => document.fonts.ready);
	// MathJax typesets asynchronously after the page loads; give it a moment.
	await page.waitForNetworkIdle({ idleTime: 500 }).catch(() => { });
	await page.screenshot({ path: outfile, fullPage: true });
	console.log(`==> Saved ${outfile}`);

	const rightClick = process.env.RIGHT_CLICK;
	if (rightClick) {
		const rightClickOutfile = process.env.RIGHT_CLICK_OUT;
		// page.click() only ever hits the first match; a demo page can have
		// several mjx-container elements (one per example), so grab all of
		// them and right-click the last one instead.
		const matches = await page.$$(rightClick);
		await matches[matches.length - 1].click({ button: 'right' });
		// The context menu renders synchronously off the click, but give
		// MathJax's own transition/animation a moment to settle.
		await new Promise((resolve) => setTimeout(resolve, 500));
		await page.screenshot({ path: rightClickOutfile, fullPage: true });
		console.log(`==> Saved ${rightClickOutfile}`);
	}
} finally {
	await browser.close();
}
