# Leafy

A full WordPress/WooCommerce e-commerce project — **Leafy**, a loose-leaf tea and tea-accessories shop. Built as a solo school CMS project (custom PHP theme, no page builders — Gutenberg only on the blog).

## Highlights

- Custom child theme (`wp-content/themes/leafy`) on top of Storefront — no page builder, hand-written PHP/CSS
- WooCommerce shop with 16 products across 6 categories, coupons, and shipping zones matched to the target market (Denmark / Europe / English-speaking countries)
- Multilingual (English default, Danish) via Polylang
- SEO via Yoast SEO, clean permalinks, fixed duplicate title tag, no duplicate-content routing issues
- Performance tuning: dequeued unused scripts/styles (jQuery Migrate, unused icon font), conditional Stripe script loading, reduced image quality where it doesn't show, YouTube videos as click-to-load facades instead of live embeds
- Accessibility fixes toward WCAG A/AA: visible keyboard-focus states, sufficient contrast, descriptive alt text, accessible notices
- Security: Wordfence (firewall, brute-force protection, 2FA), UpdraftPlus remote backups, nonce/CSRF-protected custom forms (contact form, newsletter signup)

## What's in this repo

This is the full WordPress install as it runs locally (via Local by Flywheel), minus secrets:

- `wp-content/themes/leafy/` — the actual custom code for this project (see its own README)
- `wp-content/plugins/` — WooCommerce, Yoast SEO, Polylang, Wordfence, UpdraftPlus, WooCommerce Stripe Gateway
- `wp-content/uploads/` — product and lifestyle photography
- WordPress core (`wp-admin/`, `wp-includes/`, root PHP files)

**Not included:** `wp-config.php` (database credentials), log files, upgrade/cache temp files, Wordfence logs — see `.gitignore`.

## Running locally

Requires a local WordPress environment (e.g. [Local by Flywheel](https://localwp.com/)) with MySQL/MariaDB, PHP 8.2+, and WooCommerce + the other plugins listed above installed and activated. Copy `wp-config-sample.php` to `wp-config.php` and fill in your own database credentials.
