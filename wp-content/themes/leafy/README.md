# Leafy

Custom WordPress/WooCommerce child theme (built on Storefront) for **Leafy** — a loose-leaf tea and tea-accessories e-commerce shop.

Built as a solo school CMS project (custom PHP theme, no page builders — Gutenberg only on the blog), covering:

- Custom WooCommerce shop/product/cart/checkout/account styling
- Multilingual (English/Danish) via Polylang
- Performance tuning (reduced transferred page weight — dequeued unused scripts/styles, conditional Stripe loading, image-quality tuning, YouTube facade embeds)
- Accessibility fixes (WCAG A/AA — visible focus states, contrast, semantic alt text, accessible notices)
- SEO (Yoast SEO, clean permalinks, fixed duplicate title tag, hreflang via Polylang)
- Security (Wordfence, remote backups via UpdraftPlus, CSRF-protected custom forms)

## Structure

- `functions.php` — theme setup, custom shortcodes (homepage sections, contact form, video facade, photo grids), performance/accessibility/SEO hooks, custom SVG icon set
- `style.css` — all theme styling (design tokens, header/nav, shop/product/cart/checkout/account, responsive layout)
- `woocommerce/` — WooCommerce template overrides

## Stack

WordPress + WooCommerce, Storefront parent theme, Polylang, Yoast SEO, WooCommerce Stripe Gateway, Wordfence, UpdraftPlus.
