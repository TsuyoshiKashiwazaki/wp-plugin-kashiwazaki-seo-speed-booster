=== Kashiwazaki SEO Speed Booster ===
Contributors: contencial
Tags: performance, core web vitals, speed, prefetch, speculation rules, web vitals
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Core Web Vitals (LCP / INP / CLS / FCP / TTFB) 改善のための軽量 WordPress プラグイン。

== Description ==

Kashiwazaki SEO Speed Booster は、商用 CDN を使わずにクライアントサイド JS で完結する高速化機能を提供する WordPress プラグインです。

= 主な機能 =

* **予測プリフェッチ**: IntersectionObserver (ビューポート) / mouseover 200ms / touchstart の 3 層トリガーで次のページを先読み
* **Speculation Rules API**: 対応ブラウザ (Chromium 系) で prerender を発動し、遷移時の描画まで事前完了
* **Smart UX スピナー**: 遅いページ遷移時のみスピナーを表示して体感的ストレスを緩和
* **LCP 画像最適化 (PHP サーバー側)**: LCP 候補画像に `fetchpriority="high"` を HTML 生成時に付与、preload scanner のタイミングで有効化
* **Core Web Vitals 計測**: LCP / INP / CLS / FCP / TTFB を収集し、管理画面で時系列推移 + URL 別ランキングを表示
* **prerender 副作用対策**: 計測 JS は `document.prerendering` 中は beacon を送信せず、activation 後に送信

= 設計上の特徴 =

* 外部依存は Google 製 `web-vitals` (MIT) のみ。ダッシュボードグラフも自前 SVG で描画
* REST API は WordPress nonce を使わず、HMAC rolling token + Origin/Referer 検証でページキャッシュ共存
* 訪問者識別は `bin2hex(random_bytes(16))` の完全匿名ハッシュ。IP/User-Agent は一切保存しない
* URL 別ランキングは user-specific path (`/members/{id}/` 等) を `:id` / `:user` にマスク

= スコープ外 (本 MVP では未実装) =

* A/B テスト機能 (代わりに導入前後比較 + URL ランキング + 機能トグル観察を提供)
* 画像形式変換 CDN (WordPress コアの WebP/AVIF に委ねる)
* サーバーサイド HTML 最適化 (TTFB / Critical CSS 等)
* マルチサイト対応の動作保証

= EC サイト (WooCommerce 等) での利用 =

カート・チェックアウト・マイアカウントの URL は誤プリフェッチすると購入フロー破壊等の事故の原因になります。「除外 URL」タブから URL パターンを手動で追加してください:

* `*/cart/*`
* `*/checkout/*`
* `*/my-account/*`

※ プラグイン側では特定 EC プラグインの URL を自動検出しません。多言語サイトや permalink カスタマイズに対応するため、設定は利用者側で行う方針です。

== Installation ==

1. `/wp-content/plugins/kashiwazaki-seo-speed-booster` にプラグインをアップロード
2. WordPress 管理画面の「プラグイン」で有効化
3. 管理画面の左メニュー「Kashiwazaki SEO Speed Booster」から各機能を切り替え・調整
4. フロントエンドを数回訪問後、「計測・ダッシュボード」タブで推移を確認

== Frequently Asked Questions ==

= Google Analytics と併用できますか? =

はい、併用できます。ただし prerender を有効化した場合、実遷移前にページ内 JS が発火して PV / CV データが水増しされる可能性があります。metrics.js は `document.prerendering` を監視して抑制していますが、GA / GTM / 広告タグ等も同様に対応する必要があります。副作用を避けたい場合は「Speculation Rules」タブで prerender だけを OFF にして prefetch のみ有効化してください。

= ページキャッシュプラグイン (WP Super Cache 等) と共存できますか? =

はい。認証は WordPress nonce ではなく HMAC rolling token を使うため、静的 HTML が長時間配信されても計測通信は機能します。

= 計測データはどれくらい保持されますか? =

デフォルトで無期限保持です。DB 圧迫が気になる場合は「計測・ダッシュボード」タブの「手動パージ」で 90/180/365 日より古いデータを削除できます。

= データは外部に送信されますか? =

いいえ。計測データはあなたの WordPress サイトの DB にのみ保存されます。外部サーバー・トラッキング・Analytics への送信はありません。IP アドレス・User-Agent も保存しません (デバイス種別のみ記録)。

== Changelog ==

= 1.0.1 =
* ダッシュボード: データ未収集時でも CSV インポート機能を常時表示
* ダッシュボード: スタットカードのフッターをバッジ表示に改善
* 除外タブ: プレースホルダーにパターン例を追加
* ドキュメント: ダッシュボード・除外タブの説明とスクリーンショットを更新

= 1.0.0 =
* 初版リリース。予測プリフェッチ・Speculation Rules・Smart UX スピナー・画像最適化・Web Vitals 計測ダッシュボード・URL 除外フィルタを実装。

== Screenshots ==

1. 計測ダッシュボード (LCP / INP / CLS 時系列 + URL 別ランキング)
2. 予測プリフェッチ設定
3. 除外 URL パターン

== Upgrade Notice ==

= 1.0.1 =
ダッシュボード UX 改善、除外タブにプレースホルダー追加。

= 1.0.0 =
初版。
