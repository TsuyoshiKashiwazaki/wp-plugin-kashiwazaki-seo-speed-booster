# Kashiwazaki SEO Speed Booster

![Version](https://img.shields.io/badge/version-1.0.1-blue.svg)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)

Core Web Vitals (LCP / INP / CLS / FCP / TTFB) 改善のための軽量 WordPress プラグイン。商用 CDN を使わずクライアントサイド JS で完結する高速化機能を提供します。

## 機能

| 機能 | 概要 |
|---|---|
| 予測プリフェッチ | IntersectionObserver / hover 200ms / touchstart の 3 層トリガー、MutationObserver 対応 |
| Speculation Rules API | 対応ブラウザで prerender 発動 (prerender のみ独立 OFF 可) |
| Smart UX スピナー | 遅い遷移時のみスピナーを表示 |
| LCP 画像最適化 | PHP サーバー側で `fetchpriority="high"` を HTML 生成時に付与 |
| Web Vitals 計測 | LCP/INP/CLS/FCP/TTFB をダッシュボード可視化 (自前 SVG) |
| URL 別ランキング | LCP が遅い URL TOP 20 を表示して改善対象を特定 |
| CSV エクスポート | UTF-8 BOM + RFC 4180 + CSV injection 対策済み |

## 要件

- WordPress 6.0+
- PHP 8.1+
- Chrome / Edge / Firefox / Safari の最新 2 バージョン

## インストール

```bash
cd wp-content/plugins/
git clone https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-speed-booster.git kashiwazaki-seo-speed-booster
wp plugin activate kashiwazaki-seo-speed-booster
```

有効化後、管理画面の左メニュー「Kashiwazaki SEO Speed Booster」から各機能を調整してください。

## 設計上のポイント

### REST API 認証 (nonce ではなく HMAC rolling token)

通常の WordPress プラグインは `wp_create_nonce` + `X-WP-Nonce` で REST を保護しますが、ページキャッシュ環境では古い HTML と共に古い nonce が長時間配信され、計測通信が頻繁に失敗します。

本プラグインは以下で代替:

1. **Origin / Referer 検証** (両欠落は reject)
2. **HMAC rolling token**: `GET /wp-json/wpsb/v1/token` 経由で 1 時間バケットの HMAC-SHA256 (64 hex) を発行、`X-WPSB-Token` ヘッダで POST。バケット境界救済のため現+前 1 バケットを両方検証
3. **payload サイズ上限 1KB** + **値域チェック** (LCP ≤ 60000ms 等)
4. **レート制限**: `anonymous_hash` 単独キー、1 分 20 リクエスト、transient TTL 90 秒

### プライバシー

- 匿名ハッシュ: `bin2hex(random_bytes(16))` の 32 hex、`wpsb_visitor` Cookie (SameSite=Lax, 180 日)
- IP アドレス・User-Agent は保存しない (デバイス種別のみ)
- URL 正規化: `/author/{name}/` → `/author/:author/`、連続 3 桁以上の数字を `:id` に置換
- `wpsb_normalize_path` フィルタで正規化ロジックをカスタマイズ可能

### 拡張性

次のフックを提供:

| フック | 種別 | 用途 |
|---|---|---|
| `wpsb_before_init` / `wpsb_after_init` | action | 初期化前後で他プラグインの介入 |
| `wpsb_exclude_url` | filter | 除外判定のカスタマイズ |
| `wpsb_prefetch_enabled` | filter | リクエスト単位で prefetch 機能 ON/OFF |
| `wpsb_metrics_data` | filter | 計測データ DB 記録前の加工 |
| `wpsb_normalize_path` | filter | url_path 正規化の完全差し替え |

## 改善ループの使い方 (A/B テストなしの運用)

本プラグインは訪問者をランダム 2 群に分ける A/B テストを実装しません。代わりに:

1. **導入前後比較**: ダッシュボードに「導入日マーカー」 を表示。導入前後の LCP / INP / CLS の推移を可視化
2. **URL 別ランキング**: LCP が遅いページ TOP 20 → 改善対象を特定
3. **機能トグル**: 「一般設定」タブで機能ごとに ON/OFF 可能。怪しい機能は切って 1 週間様子を見る
4. **外部ツール併用 (推奨)**:
   - Google Search Console / CrUX で長期トレンドの客観確認
   - PageSpeed Insights で個別ページの診断

大規模サイト (1 日 1 万 PV 超) で統計的 A/B が必要な場合は、Phase 11+ の拡張として検討。

## テスト

```bash
# PHPUnit (URL matcher / path normalizer)
phpunit -c phpunit.xml.dist
```

結果: 13 tests, 28 assertions, all pass。

## ライセンス

GPL-2.0-or-later。

## 作者

Contencial (https://contencial.co.jp/)
