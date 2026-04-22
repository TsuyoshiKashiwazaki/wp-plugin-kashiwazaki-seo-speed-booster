/**
 * Kashiwazaki SEO Speed Booster - Web Vitals metrics sender
 */
(function () {
	'use strict';

	if (typeof window.wpsbMetricsConfig === 'undefined') {
		return;
	}
	if (typeof window.webVitals === 'undefined') {
		return;
	}

	var cfg = window.wpsbMetricsConfig;
	var wv  = window.webVitals;

	var rate = Math.max(0, Math.min(1, cfg.sampleRate || 0));
	if (rate < 1 && Math.random() >= rate) {
		return;
	}

	var buffer = [];
	var flushed = false;

	if (document.prerendering) {
		document.addEventListener('prerenderingchange', init, { once: true });
	} else {
		init();
	}

	function init() {
		var token = cfg.token;
		if (!token) return;

		function collect(metric) {
			buffer.push({
				name:   metric.name,
				value:  metric.value,
				url:    location.pathname,
				device: detectDevice(),
				hash:   getVisitorHash()
			});
		}

		if (wv.onLCP) wv.onLCP(collect);
		if (wv.onINP) wv.onINP(collect);
		if (wv.onCLS) wv.onCLS(collect);
		if (wv.onFCP) wv.onFCP(collect);
		if (wv.onTTFB) wv.onTTFB(collect);

		document.addEventListener('visibilitychange', function () {
			if (document.visibilityState === 'hidden') flush(token, false);
		});
		window.addEventListener('pagehide', function () { flush(token, true); });
	}

	function flush(token, final) {
		if (buffer.length === 0) return;
		if (flushed && final) return;

		var payload = JSON.stringify({
			metrics: buffer,
			token:   token
		});
		buffer = [];
		if (final) flushed = true;

		var sent = navigator.sendBeacon && navigator.sendBeacon(cfg.endpoint, new Blob([payload], { type: 'application/json' }));
		if (!sent) {
			try {
				fetch(cfg.endpoint, {
					method:      'POST',
					keepalive:   true,
					credentials: 'same-origin',
					headers:     { 'Content-Type': 'application/json', 'X-WPSB-Token': token },
					body:        payload
				});
			} catch (e) { /* noop */ }
		}
	}

	function detectDevice() {
		var ua = navigator.userAgent || '';
		if (/iPad|Tablet/i.test(ua)) return 'tablet';
		if (/Mobi|Android/i.test(ua)) return 'mobile';
		return 'desktop';
	}

	function getVisitorHash() {
		var name = 'wpsb_visitor';
		var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([a-f0-9]{32})'));
		if (m) return m[1];

		var bytes = new Uint8Array(16);
		(window.crypto && window.crypto.getRandomValues)
			? window.crypto.getRandomValues(bytes)
			: bytes.forEach(function (_, i, arr) { arr[i] = Math.floor(Math.random() * 256); });
		var hex = '';
		for (var i = 0; i < bytes.length; i++) {
			hex += ('0' + bytes[i].toString(16)).slice(-2);
		}
		var cookie = name + '=' + hex + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
		if (cfg.cookieDays > 0) {
			var expires = new Date();
			expires.setDate(expires.getDate() + cfg.cookieDays);
			cookie += '; expires=' + expires.toUTCString();
		}
		document.cookie = cookie;
		return hex;
	}
})();
