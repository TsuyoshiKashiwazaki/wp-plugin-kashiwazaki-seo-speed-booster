/**
 * Kashiwazaki SEO Speed Booster - Prefetch + Smart UX
 */
(function () {
	'use strict';

	if (typeof window.wpsbConfig === 'undefined') {
		return;
	}

	var cfg = window.wpsbConfig;

	// prerender ガード: prerender 中はプリフェッチ・スピナーを発火させない。
	if (document.prerendering) {
		document.addEventListener('prerenderingchange', init, { once: true });
	} else {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', init, { once: true });
		} else {
			init();
		}
	}

	function init() {
		var prefetchedUrls = new Set();
		var speculationActive = cfg.speculationActive && HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules');

		if (cfg.prefetch && cfg.prefetch.enabled && !speculationActive) {
			setupPrefetch(prefetchedUrls);
		}

		if (cfg.smartUx && cfg.smartUx.enabled) {
			setupSmartUx();
		}
	}

	function setupPrefetch(prefetchedUrls) {
		var hoverDelay = cfg.prefetch.hoverDelayMs != null ? cfg.prefetch.hoverDelayMs : 200;
		var hoverTimers = new WeakMap();
		var maxPrefetch = 6;
		var io = null;
		var mo = null;
		var currentUrl = new URL(location.href, cfg.origin);
		currentUrl.hash = '';
		currentUrl = currentUrl.href;

		function shouldPrefetch(link) {
			if (!link || !link.href) return false;

			var rawHref = link.getAttribute('href') || '';
			if (!rawHref || rawHref === '#') return false;

			if (/^(mailto:|tel:|javascript:|#)/i.test(rawHref)) return false;

			var href;
			try {
				var urlObj = new URL(link.href, cfg.origin);
				if (urlObj.origin !== cfg.origin) return false;
				urlObj.hash = '';
				href = urlObj.href;
			} catch (e) {
				return false;
			}

			if (href === currentUrl) return false;
			if (prefetchedUrls.size >= maxPrefetch) return false;

			var rel = (link.getAttribute('rel') || '').toLowerCase();
			if (rel.indexOf('nofollow') !== -1 || rel.indexOf('external') !== -1) return false;
			if (link.hasAttribute('data-wpsb-no-prefetch') || link.hasAttribute('data-no-prefetch')) return false;

			var qs = urlObj.search;
			var queryBlockers = ['add-to-cart', 'remove_item', '_wpnonce', 'action=logout', 'action=delete'];
			for (var i = 0; i < queryBlockers.length; i++) {
				if (qs.indexOf(queryBlockers[i]) !== -1) return false;
			}

			var path = urlObj.pathname + urlObj.search;
			if (cfg.excludePatterns && matchesAny(path, cfg.excludePatterns)) return false;
			if (cfg.includePatterns && cfg.includePatterns.length > 0 && !matchesAny(path, cfg.includePatterns)) return false;

			if (prefetchedUrls.has(href)) return false;
			return true;
		}

		function doPrefetch(link) {
			if (!shouldPrefetch(link)) return;
			var urlObj = new URL(link.href, cfg.origin);
			urlObj.hash = '';
			var href = urlObj.href;
			var el = document.createElement('link');
			el.rel = 'prefetch';
			el.href = href;
			document.head.appendChild(el);
			prefetchedUrls.add(href);
			if (prefetchedUrls.size >= maxPrefetch) {
				if (io) { io.disconnect(); }
				if (mo) { mo.disconnect(); }
			}
		}

		// Viewport
		if (cfg.prefetch.viewport && 'IntersectionObserver' in window) {
			io = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						io.unobserve(entry.target);
						doPrefetch(entry.target);
					}
				});
			}, { rootMargin: '200px' });
			observeAll(io);
		}

		// Hover
		if (cfg.prefetch.hover) {
			document.addEventListener('mouseover', function (ev) {
				var a = ev.target.closest && ev.target.closest('a');
				if (!a) return;
				if (hoverTimers.has(a)) return;
				var t = setTimeout(function () { doPrefetch(a); hoverTimers.delete(a); }, hoverDelay);
				hoverTimers.set(a, t);
			});
			document.addEventListener('mouseout', function (ev) {
				var a = ev.target.closest && ev.target.closest('a');
				if (!a) return;
				if (a.contains(ev.relatedTarget)) return;
				var t = hoverTimers.get(a);
				if (t) { clearTimeout(t); hoverTimers.delete(a); }
			});
		}

		// Touch
		if (cfg.prefetch.touch) {
			var touchLink = null;
			document.addEventListener('touchstart', function (ev) {
				var a = ev.target.closest && ev.target.closest('a');
				touchLink = a || null;
			}, { passive: true });
			document.addEventListener('touchmove', function () {
				touchLink = null;
			}, { passive: true });
			document.addEventListener('touchcancel', function () {
				touchLink = null;
			}, { passive: true });
			document.addEventListener('touchend', function () {
				if (touchLink) { doPrefetch(touchLink); touchLink = null; }
			}, { passive: true });
		}

		if ('MutationObserver' in window && 'IntersectionObserver' in window && cfg.prefetch.viewport) {
			var pendingNodes = [];
			var flushPending = debounce(function () {
				if (prefetchedUrls.size >= maxPrefetch) { pendingNodes = []; return; }
				for (var k = 0; k < pendingNodes.length; k++) {
					var node = pendingNodes[k];
					if (node.tagName === 'A' && node.hasAttribute('href')) {
						io.observe(node);
					}
					if (node.querySelectorAll) {
						var anchors = node.querySelectorAll('a[href]');
						for (var n = 0; n < anchors.length; n++) { io.observe(anchors[n]); }
					}
				}
				pendingNodes = [];
			}, 100, 500);

			mo = new MutationObserver(function (mutations) {
				if (prefetchedUrls.size >= maxPrefetch) { mo.disconnect(); return; }
				for (var i = 0; i < mutations.length; i++) {
					var added = mutations[i].addedNodes;
					for (var j = 0; j < added.length; j++) {
						if (added[j].nodeType === 1) pendingNodes.push(added[j]);
					}
				}
				flushPending();
			});
			mo.observe(document.body, { childList: true, subtree: true });
		}
	}

	function observeAll(io) {
		var links = document.querySelectorAll('a[href]');
		for (var i = 0; i < links.length; i++) {
			io.observe(links[i]);
		}
	}

	function matchesAny(path, patterns) {
		for (var i = 0; i < patterns.length; i++) {
			if (matchesPattern(path, patterns[i])) return true;
		}
		return false;
	}

	function matchesPattern(path, pattern) {
		if (!pattern) return false;
		if (/^\/.+\/[imsu]*$/.test(pattern)) {
			var lastSlash = pattern.lastIndexOf('/');
			var body = pattern.slice(1, lastSlash);
			var flags = pattern.slice(lastSlash + 1);
			if (flags !== '' || /[\\^$\[()|+{]/.test(body)) {
				try {
					return new RegExp(body, flags).test(path);
				} catch (e) {
					return false;
				}
			}
		}
		// glob
		var re = pattern.replace(/[.+^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*').replace(/\?/g, '.');
		return new RegExp('^' + re + '$').test(path);
	}

	function debounce(fn, ms, maxWait) {
		var t, burstStart = 0;
		return function () {
			var now = Date.now();
			clearTimeout(t);
			if (!burstStart) burstStart = now;
			if (maxWait && (now - burstStart) >= maxWait) {
				burstStart = 0;
				fn();
			} else {
				t = setTimeout(function () {
					burstStart = 0;
					fn();
				}, ms);
			}
		};
	}

	// Smart UX spinner
	function setupSmartUx() {
		var threshold = cfg.smartUx.thresholdMs != null ? Number(cfg.smartUx.thresholdMs) : 200;
		var spinner = null;
		var timer = null;
		var failsafe = null;
		var MAX_DISPLAY = 10000;

		function createSpinner() {
			if (spinner) return spinner;
			spinner = document.createElement('div');
			spinner.className = 'wpsb-spinner-overlay';
			var bgColor = cfg.smartUx.bgColor != null ? cfg.smartUx.bgColor : '#ffffff';
			var bgOpacity = cfg.smartUx.bgOpacity != null ? Number(cfg.smartUx.bgOpacity) : 0.8;
			spinner.style.backgroundColor = hexToRgba(bgColor, bgOpacity);

			var inner = document.createElement('div');
			inner.className = 'wpsb-spinner-inner';
			if (cfg.smartUx.logoUrl) {
				var img = document.createElement('img');
				img.src = cfg.smartUx.logoUrl;
				img.alt = '';
				inner.appendChild(img);
			} else {
				inner.innerHTML = '<svg class="wpsb-spinner-svg" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke-width="4"/></svg>';
			}
			spinner.appendChild(inner);
			spinner.style.display = 'none';
			document.body.appendChild(spinner);
			return spinner;
		}

		function hexToRgba(hex, opacity) {
			var h = (typeof hex === 'string' ? hex : '').replace('#', '');
			if (h.length === 3) h = h.split('').map(function (c) { return c + c; }).join('');
			if (h.length !== 6 || /[^0-9a-fA-F]/.test(h)) return 'rgba(255,255,255,' + opacity + ')';
			var r = parseInt(h.slice(0, 2), 16);
			var g = parseInt(h.slice(2, 4), 16);
			var b = parseInt(h.slice(4, 6), 16);
			return 'rgba(' + r + ',' + g + ',' + b + ',' + opacity + ')';
		}

		function show() {
			var el = createSpinner();
			el.style.display = 'flex';
			clearTimeout(failsafe);
			failsafe = setTimeout(hide, MAX_DISPLAY);
		}
		function hide() {
			clearTimeout(failsafe);
			if (spinner) spinner.style.display = 'none';
		}

		document.addEventListener('click', function (ev) {
			var a = ev.target.closest && ev.target.closest('a');
			if (!a) return;
			if (ev.defaultPrevented) return;
			if (ev.button !== 0) return;
			if (ev.ctrlKey || ev.metaKey || ev.shiftKey || ev.altKey) return;
			if (a.target && a.target !== '_self') return;
			if (a.hasAttribute('download')) return;

			var rawHref = a.getAttribute('href') || '';
			if (!rawHref || rawHref === '#' || rawHref.charAt(0) === '#') return;

			try {
				var urlObj = new URL(a.href, cfg.origin);
				if (urlObj.origin !== cfg.origin) return;
				if (urlObj.pathname === location.pathname && urlObj.search === location.search) return;
			} catch (e) {
				return;
			}

			clearTimeout(timer);
			timer = setTimeout(show, threshold);
		});

		window.addEventListener('pageshow', function () {
			clearTimeout(timer);
			hide();
		});
		window.addEventListener('popstate', function () {
			clearTimeout(timer);
			hide();
		});
		window.addEventListener('hashchange', function () {
			clearTimeout(timer);
			hide();
		});
	}
})();
