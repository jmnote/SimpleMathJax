#!/usr/bin/env node
import puppeteer from 'puppeteer';
import GIFEncoder from 'gif-encoder-2';
import sharp from 'sharp';
import fs from 'node:fs';
import path from 'node:path';

const SCRIPT_DIR = path.dirname(new URL(import.meta.url).pathname);
const BASE = process.env.URL ?? 'http://localhost:8080';
const OUT_DIR = process.env.OUT_DIR ?? '../../docs/animations';

function readViewport(content) {
	const match = content.match(/^viewport:\s*\n\s+width:\s*(\d+)\s*\n\s+height:\s*(\d+)\s*$/m);
	return match ?
		{ width: Number(match[1]), height: Number(match[2]) } :
		{ width: 1280, height: 900 };
}

function readDocs(content) {
	const lines = content.split('\n');
	const docs = [];
	let i = 0;
	while (i < lines.length) {
		const nameMatch = lines[i].match(/^\s*-\s*name:\s*(.+?)\s*$/);
		if (!nameMatch) {
			i++;
			continue;
		}
		const name = nameMatch[1];
		i++;
		let content = '';
		while (i < lines.length && !/^\s*-\s*name:\s*/.test(lines[i])) {
			if (!/^\s*content:\s*\|\s*$/.test(lines[i])) {
				i++;
				continue;
			}
			i++;
			const blockLines = [];
			let blockIndent = null;
			while (i < lines.length) {
				const line = lines[i];
				if (line.trim() === '') {
					blockLines.push('');
					i++;
					continue;
				}
				const thisIndent = line.match(/^\s*/)[0].length;
				if (blockIndent === null) {
					blockIndent = thisIndent;
				} else if (thisIndent < blockIndent) {
					break;
				}
				blockLines.push(line.slice(blockIndent));
				i++;
			}
			content = blockLines.join('\n');
		}
		docs.push({ name, content });
	}
	return docs;
}

function parseContent(content) {
	const tagRe = /<(math|chem)(?:\s+display="(default|inline|block)")?>([\s\S]*?)<\/\1>/g;
	const segments = [];
	let lastIndex = 0;
	let match;
	while ((match = tagRe.exec(content)) !== null) {
		segments.push({ text: content.slice(lastIndex, match.index) });
		const [, tag, display, formula] = match;
		segments.push({ toolClass: tag === 'math' ? 'smjMath' : 'smjChem', formula, display: display ?? 'default' });
		lastIndex = tagRe.lastIndex;
	}
	segments.push({ text: content.slice(lastIndex) });
	return segments;
}

function localFontCss(packageName) {
	const packageDir = path.join(SCRIPT_DIR, 'node_modules', '@fontsource', packageName);
	const css = fs.readFileSync(path.join(packageDir, 'index.css'), 'utf8');
	return css.replace(/url\(\.\/files\/([^)]+)\)/g, (match, filename) => {
		const font = fs.readFileSync(path.join(packageDir, 'files', filename));
		return `url(data:font/woff2;base64,${font.toString('base64')})`;
	});
}

async function injectCjkFonts(page) {
	await page.addStyleTag({ content: localFontCss('noto-sans-kr') });
	await page.addStyleTag({ content: localFontCss('noto-serif-kr') });
	await page.addStyleTag({
		content: `
			body, .oo-ui-inputWidget-input, .ve-ui-linearContextItem-body { font-family: 'Noto Sans KR', sans-serif !important; }
			mjx-utext { font-family: 'Noto Serif KR', serif !important; }
		`
	});
	await page.evaluate(() => document.fonts.ready);
}

async function recordDoc(browser, name, segments, viewport, outDir) {
	const pageTitle = `VisualEditorDemo-${name}`;
	const { width: WIDTH, height: HEIGHT } = viewport;
	const encoder = new GIFEncoder(WIDTH, HEIGHT, 'octree');
	encoder.start();
	encoder.setRepeat(0);
	encoder.setQuality(15);

	async function addFrame(page, holdMs) {
		const png = await page.screenshot({ type: 'png' });
		const { data } = await sharp(png)
			.ensureAlpha()
			.raw()
			.toBuffer({ resolveWithObject: true });
		encoder.setDelay(holdMs);
		encoder.addFrame(data);
	}

	const page = await browser.newPage();
	await page.setViewport(viewport);

	await page.goto(`${BASE}/index.php?title=Special:UserLogin&returnto=Main+Page`, { waitUntil: 'networkidle0' });
	await page.type('#wpName1', 'Admin');
	await page.type('#wpPassword1', 'demo12345678');
	await Promise.all([
		page.click('#wpLoginAttempt'),
		page.waitForNavigation({ waitUntil: 'networkidle0' })
	]);
	const csrf = await page.evaluate(async () => {
		const res = await fetch('/api.php?action=query&meta=tokens&format=json', { credentials: 'same-origin' });
		return (await res.json()).query.tokens.csrftoken;
	});
	await page.evaluate(async (title, token) => {
		await fetch('/api.php', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ action: 'edit', title, text: '', token, format: 'json' })
		});
	}, pageTitle, csrf);

	await page.goto(`${BASE}/index.php?title=${encodeURIComponent(pageTitle)}&veaction=edit`, { waitUntil: 'networkidle0' });
	await page.waitForSelector('.ve-ui-toolbar', { timeout: 30000 });
	await injectCjkFonts(page);
	await new Promise((r) => setTimeout(r, 2000));
	for (const b of await page.$$('.oo-ui-buttonElement-button')) {
		const text = await page.evaluate((el) => el.textContent, b);
		if (text && text.trim() === 'Start editing') {
			await b.click();
			await new Promise((r) => setTimeout(r, 1500));
			break;
		}
	}
	await page.click('.ve-ce-documentNode').catch(() => { });
	await new Promise((r) => setTimeout(r, 500));
	await addFrame(page, 1200);

	async function clickButtonByText(text) {
		for (const b of await page.$$('.oo-ui-buttonElement-button')) {
			const t = await page.evaluate((el) => el.textContent, b);
			if (t && t.trim() === text) {
				await b.click();
				return true;
			}
		}
		return false;
	}

	async function openInsertMenu(toolClass) {
		for (const el of await page.$$('.oo-ui-popupToolGroup-handle')) {
			const text = await page.evaluate((e) => e.textContent.trim(), el);
			if (text === 'Insert') {
				await el.click();
				break;
			}
		}
		await new Promise((r) => setTimeout(r, 500));
		const alreadyVisible = await page.evaluate((cls) => {
			const el = document.querySelector(`.oo-ui-tool-name-${cls} .oo-ui-tool-link`);
			if (!el) {
				return false;
			}
			const rect = el.getBoundingClientRect();
			return rect.width > 0 && rect.height > 0;
		}, toolClass);
		if (!alreadyVisible) {
			for (const el of await page.$$('.oo-ui-tool-name-more-fewer .oo-ui-tool-link')) {
				if (await el.isIntersectingViewport().catch(() => false)) {
					await el.click();
					break;
				}
			}
			await new Promise((r) => setTimeout(r, 500));
		}
	}

	async function typeFormulaWithFrames(text, chunkSize) {
		for (let i = 0; i < text.length; i += chunkSize) {
			await page.keyboard.type(text.slice(i, i + chunkSize));
			await addFrame(page, 120);
		}
	}

	async function typeMultiline(text) {
		for (const part of text.split(/(\n+)/)) {
			if (/^\n\n+$/.test(part)) {
				await page.keyboard.press('Enter');
			} else if (part && part !== '\n') {
				await page.keyboard.type(part);
			}
		}
	}

	async function clickDisplayButton(display) {
		const label = { default: 'Default', inline: 'Inline', block: 'Block' }[display] ?? 'Default';
		for (let attempt = 0; attempt < 5; attempt++) {
			for (const b of await page.$$('.oo-ui-buttonOptionWidget')) {
				const text = await page.evaluate((el) => el.textContent.trim(), b);
				if (text !== label) {
					continue;
				}
				try {
					await b.click();
					return;
				} catch {
					await new Promise((r) => setTimeout(r, 300));
				}
			}
		}
	}

	async function insertFormula(toolClass, formulaText, display) {
		await openInsertMenu(toolClass);
		await addFrame(page, 900);
		const tool = await page.waitForSelector(`.oo-ui-tool-name-${toolClass} .oo-ui-tool-link`, { timeout: 10000, visible: true });
		await tool.click();
		await new Promise((r) => setTimeout(r, 800));
		await addFrame(page, 900);
		await page.waitForSelector('.ve-ui-smjFormulaDialog-content textarea', { timeout: 10000 });
		await typeFormulaWithFrames(formulaText, 4);
		if (display && display !== 'default') {
			await clickDisplayButton(display);
			await new Promise((r) => setTimeout(r, 300));
		}
		await new Promise((r) => setTimeout(r, 1500));
		await addFrame(page, 2000);
		await clickButtonByText('Insert');
		await new Promise((r) => setTimeout(r, 1200));
		await addFrame(page, 1800);
	}

	for (const segment of segments) {
		if ('toolClass' in segment) {
			console.log(`[${name}] Inserting ${segment.formula}`);
			await insertFormula(segment.toolClass, segment.formula, segment.display);
			await page.keyboard.press('ArrowRight');
			await new Promise((r) => setTimeout(r, 200));
		} else if (segment.text) {
			await typeMultiline(segment.text);
			await addFrame(page, 1200);
		}
	}

	for (const el of await page.$$('.oo-ui-tool-name-showSave .oo-ui-tool-link')) {
		await el.click();
		break;
	}
	await new Promise((r) => setTimeout(r, 1200));
	await addFrame(page, 1200);
	for (let i = 0; i < 5 && /veaction=edit/.test(page.url()); i++) {
		await clickButtonByText('Save page') || await clickButtonByText('Save changes');
		await new Promise((r) => setTimeout(r, 1200));
	}
	await page.reload({ waitUntil: 'load', timeout: 60000 } ).catch(() => {});
	await injectCjkFonts(page);
	await page.waitForFunction(
		() => Array.from(document.querySelectorAll('span.smj-container'))
			.some((el) => getComputedStyle(el).opacity === '1'),
		{ timeout: 20000 }
	).catch(() => { });
	await addFrame(page, 5000);

	await page.close();

	encoder.finish();
	const outPath = path.resolve(SCRIPT_DIR, outDir, `animation-${name}.gif`);
	fs.mkdirSync(path.dirname(outPath), { recursive: true });
	fs.writeFileSync(outPath, encoder.out.getData());
	console.log(`==> Saved ${outPath}`);
}

const yamlContent = fs.readFileSync(path.join(SCRIPT_DIR, 'demo-animations.yaml'), 'utf8');
const viewport = readViewport(yamlContent);
const docs = readDocs(yamlContent);
const browser = await puppeteer.launch(
	process.getuid?.() === 0 ? { args: ['--no-sandbox'] } : {}
);
try {
	for (const { name, content } of docs) {
		await recordDoc(browser, name, parseContent(content), viewport, OUT_DIR);
	}
} finally {
	await browser.close();
}
