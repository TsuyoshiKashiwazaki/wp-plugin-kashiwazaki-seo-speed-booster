(function () {
	'use strict';

	// ── Tooltip ──
	function closeAllTooltips() {
		var triggers = document.querySelectorAll('.wpsb-tooltip-trigger[aria-expanded="true"]');
		for (var i = 0; i < triggers.length; i++) {
			triggers[i].setAttribute('aria-expanded', 'false');
			triggers[i].nextElementSibling.hidden = true;
		}
	}

	document.addEventListener('click', function (e) {
		var trigger = e.target.closest('.wpsb-tooltip-trigger');
		if (trigger) {
			e.preventDefault();
			var bubble = trigger.nextElementSibling;
			var isOpen = trigger.getAttribute('aria-expanded') === 'true';
			closeAllTooltips();
			if (!isOpen) {
				trigger.setAttribute('aria-expanded', 'true');
				bubble.hidden = false;
			}
			return;
		}
		if (!e.target.closest('.wpsb-tooltip-bubble')) {
			closeAllTooltips();
		}
	});

	// ── Feature Card toggle (共通) ──
	function bindFeatureCards(container) {
		var cards = container.querySelectorAll('.wpsb-feature-card');
		for (var i = 0; i < cards.length; i++) {
			(function (card) {
				var cb = card.querySelector('input[type="checkbox"]');
				if (cb) {
					cb.addEventListener('change', function () {
						card.classList.toggle('is-off', !this.checked);
					});
				}
			})(cards[i]);
		}
	}

	// ── Master Switch (一般設定タブ) ──
	var master = document.querySelector('.wpsb-master-switch');
	if (master) {
		var masterCb = master.querySelector('input[type="checkbox"]');
		var grid = document.querySelector('.wpsb-feature-grid');
		var cards = grid ? grid.querySelectorAll('.wpsb-feature-card') : [];

		function updateAllStates() {
			var masterOff = !masterCb.checked;
			master.classList.toggle('is-off', masterOff);
			if (grid) {
				grid.classList.toggle('is-master-off', masterOff);
			}
			for (var i = 0; i < cards.length; i++) {
				var cb = cards[i].querySelector('input[type="checkbox"]');
				cards[i].classList.toggle('is-off', masterOff || !cb.checked);
			}
		}

		masterCb.addEventListener('change', updateAllStates);

		for (var i = 0; i < cards.length; i++) {
			(function (card) {
				var cb = card.querySelector('input[type="checkbox"]');
				cb.addEventListener('change', function () {
					card.classList.toggle('is-off', !masterCb.checked || !this.checked);
				});
			})(cards[i]);
		}
	}

	// ── Long-term period select navigation ──
	var longtermSelect = document.querySelector('.wpsb-period-longterm');
	if (longtermSelect) {
		longtermSelect.addEventListener('change', function () {
			if (this.value) {
				location.href = this.value;
			}
		});
	}

	// ── Purge form confirmation (ダッシュボード) ──
	var purgeForm = document.getElementById('wpsb-purge-form');
	if (purgeForm) {
		purgeForm.addEventListener('submit', function (e) {
			if (!confirm(this.getAttribute('data-confirm'))) {
				e.preventDefault();
			}
		});
	}

	// ── Ranking Tab Switching ──
	var rankingTabs = document.querySelectorAll('.wpsb-ranking-tab');
	for (var t = 0; t < rankingTabs.length; t++) {
		rankingTabs[t].addEventListener('click', function () {
			var metric = this.getAttribute('data-metric');
			var container = this.closest('.wpsb-settings-card');
			var tabs = container.querySelectorAll('.wpsb-ranking-tab');
			var panels = container.querySelectorAll('.wpsb-ranking-panel');
			for (var j = 0; j < tabs.length; j++) {
				tabs[j].classList.toggle('is-active', tabs[j] === this);
			}
			for (var k = 0; k < panels.length; k++) {
				panels[k].classList.toggle('is-active', panels[k].getAttribute('data-metric') === metric);
			}
		});
	}

	// ── Chart.js Dashboard Charts ──
	if (typeof Chart !== 'undefined' && typeof wpsbChartData !== 'undefined') {
		var chartMetrics = wpsbChartData.metrics;
		var chartLabels = wpsbChartData.labels;

		Object.keys(chartMetrics).forEach(function (metric) {
			var canvas = document.getElementById('wpsb-chart-' + metric.toLowerCase());
			if (!canvas) return;

			var isCLS = metric === 'CLS';

			new Chart(canvas, {
				type: 'line',
				data: {
					labels: chartLabels,
					datasets: [{
						label: metric + (isCLS ? '' : ' (ms)'),
						data: chartMetrics[metric],
						borderColor: '#2271b1',
						backgroundColor: 'rgba(34, 113, 177, 0.08)',
						fill: true,
						tension: 0.3,
						spanGaps: true,
						pointRadius: 3,
						pointHoverRadius: 6,
						borderWidth: 2
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							callbacks: {
								label: function (ctx) {
									if (ctx.parsed.y === null) return null;
									var v = isCLS ? ctx.parsed.y.toFixed(3) : Math.round(ctx.parsed.y);
									return metric + ': ' + v + (isCLS ? '' : ' ms');
								}
							}
						}
					},
					scales: {
						x: {
							type: 'category',
							grid: { display: false },
							ticks: { maxTicksLimit: 10, maxRotation: 0 }
						},
						y: {
							beginAtZero: true,
							grid: { color: 'rgba(0,0,0,0.06)' },
							ticks: {
								callback: function (v) {
									return isCLS ? v.toFixed(2) : Math.round(v);
								}
							}
						}
					}
				}
			});
		});
	}

	// ── Feature Header + Settings Panel (サブタブ共通) ──
	var featureHeader = document.querySelector('.wpsb-feature-header');
	if (featureHeader) {
		var headerCb = featureHeader.querySelector('input[type="checkbox"]');
		var panel = document.querySelector('.wpsb-settings-panel');

		if (headerCb) {
			headerCb.addEventListener('change', function () {
				var off = !this.checked;
				featureHeader.classList.toggle('is-off', off);
				if (panel) {
					panel.classList.toggle('is-disabled', off);
				}
			});
		}

		if (panel) {
			bindFeatureCards(panel);
		}
	}
})();
