# 更新履歴

このファイルは [Keep a Changelog](https://keepachangelog.com/ja/1.0.0/) の形式に準拠しています。
バージョン管理は [Semantic Versioning](https://semver.org/lang/ja/) に従います。

## [1.0.1] - 2026-04-25

### 改善

- ダッシュボード: データ未収集時でも CSV インポート機能を常時表示するよう変更
- ダッシュボード: スタットカードのフッターを「平均値」「サンプル数」のバッジ表示に改善
- 除外タブ: 除外パターン・ホワイトリストのテキストエリアにプレースホルダー例を追加
- ドキュメント: ダッシュボードの機能説明を全面更新（期間フィルタ、低速 URL TOP 20、CSV インポート、一括削除、空状態の説明を追加）
- ドキュメント: 除外タブのスクリーンショットとプレースホルダー説明を更新

## [1.0.0] - 2026-04-24

### 追加

- 予測プリフェッチ (IntersectionObserver / hover 200ms / touchstart の 3 層トリガー)
- Speculation Rules API 対応 (Chromium 121+ で prerender / prefetch)
- Smart UX スピナー (遅延遷移時のスピナー表示、CLS フリー設計)
- 画像最適化 (loading="lazy" / decoding="async" / fetchpriority="high" の自動付与)
- Core Web Vitals 計測ダッシュボード (LCP / INP / CLS / FCP / TTFB の時系列表示)
- URL 除外フィルタ (glob / 正規表現でプリフェッチ対象を制御)
- CSV エクスポート (UTF-8 BOM + CSV injection 対策)
- REST API (HMAC rolling token 認証、ページキャッシュ共存)
- 匿名訪問者計測 (IP / User-Agent 非保存、匿名ハッシュのみ)

[1.0.1]: https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-speed-booster/releases/tag/v1.0.1-dev
[1.0.0]: https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-speed-booster/releases/tag/v1.0.0
