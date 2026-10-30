<?php
/**
 * Leafy child theme functions.
 */

define( 'LEAFY_GA4_MEASUREMENT_ID', '' ); // e.g. 'G-XXXXXXXXXX' - fill in once you create a GA4 property.

/* -------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'leafy-google-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Jost:wght@400;500;600;700&display=swap', array(), null );
	wp_enqueue_style( 'storefront-style', get_template_directory_uri() . '/style.css' );
}, 20 );

// Storefront auto-enqueues the child theme's style.css itself as
// 'storefront-child-style' - loading it a second time under our own
// handle ('leafy-style') just duplicated the download AND, since it was
// queued before 'storefront-woocommerce-style', let WooCommerce's CSS
// override ours on every matching selector (cart count, footer links,
// etc). Make the one Storefront already loads depend on WooCommerce's
// stylesheet instead, so it's always the last word in the cascade.
add_action( 'wp_enqueue_scripts', function () {
	global $wp_styles;
	if ( isset( $wp_styles->registered['storefront-child-style'] ) ) {
		$wp_styles->registered['storefront-child-style']->deps[] = 'storefront-woocommerce-style';
	}
}, 99 );

/* -------------------------------------------------------------------
 * Performance: trim default WP/WooCommerce overhead that loads on
 * every page regardless of whether it's used.
 * ---------------------------------------------------------------- */
// Emoji detection script + styles (saves 2 requests + ~15KB on every page).
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

// WooCommerce order-attribution tracking (sourcebuster.js) - marketing
// attribution analytics, not needed for this build; ~190KB on every page.
add_filter( 'woocommerce_order_attribution_hooked_fields', '__return_false' );
remove_action( 'wp_enqueue_scripts', array( 'Automattic\WooCommerce\Internal\Orders\OrderAttributionController', 'enqueue_scripts' ) );
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_script( 'sourcebuster-js' );
	wp_dequeue_script( 'wc-order-attribution' );
}, 100 );

// Product gallery zoom/slider/lightbox (PhotoSwipe + FlexSlider, ~70KB) only
// earn their keep with multiple images - our products ship with one each.
add_action( 'wp_loaded', function () {
	remove_theme_support( 'wc-product-gallery-zoom' );
	remove_theme_support( 'wc-product-gallery-slider' );
	remove_theme_support( 'wc-product-gallery-lightbox' );
} );

// Stripe's JS (~1.2MB) otherwise preloads on every product/cart page "just
// in case" a payment button is shown there, even with express checkout off.
// Payment still works fully on the checkout page itself.
add_filter( 'wc_stripe_load_scripts_on_product_page_when_prbs_disabled', '__return_false' );
add_filter( 'wc_stripe_load_scripts_on_cart_page_when_prbs_disabled', '__return_false' );

// Real product/lifestyle photography compresses much better than the
// WordPress default (82%) without a visible quality difference at the
// sizes these actually render at - this alone saves ~25% per thumbnail.
add_filter( 'wp_editor_set_quality', function ( $quality, $context ) {
	return 60;
}, 10, 2 );

// Dashicons only needed in wp-admin, not on the public site.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_admin_bar_showing() ) {
		wp_dequeue_style( 'dashicons' );
	}
}, 100 );

// Storefront's own default Google Font (Source Sans Pro, ~45KB) is never
// used - Leafy loads its own Playfair Display / Jost instead.
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'storefront-fonts' );
	// 'storefront-woocommerce-style' lists 'storefront-icons' as a hard
	// dependency. Deregistering it outright breaks that dependency chain -
	// WP silently refuses to print storefront-woocommerce-style at all if
	// one of its deps doesn't exist. Re-register it as an empty placeholder
	// (no src) instead: the dependency still resolves, but nothing loads.
	wp_deregister_style( 'storefront-icons' );
	wp_register_style( 'storefront-icons', false );
	// jQuery Migrate patches long-deprecated jQuery APIs our code never
	// calls; safe to drop on a site with no legacy-plugin dependencies.
	// 'jquery' depends on it by default, so re-register without that dep
	// rather than dequeuing it directly (same dependency-chain issue as
	// the icons stylesheet above).
	wp_deregister_script( 'jquery' );
	wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.min.js' ), array(), null, true );
	wp_dequeue_style( 'storefront-gutenberg-blocks' );
}, 100 );

/* -------------------------------------------------------------------
 * Brand mark: a teacup-and-leaf icon, as a "Leafy" wordmark lockup in
 * the header and a circular badge in the footer. Colors match the
 * theme's existing CSS custom properties (--leafy-green-dark/gold/cream).
 * ---------------------------------------------------------------- */
function leafy_mark_icon( $green = '#2f4a3c', $gold = '#c9a86a', $cream = '#faf6ee', $size = 44 ) {
	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
		<path d="M32 31 C21 24 22 9 41 3 C44 17 41 27 32 31 Z" fill="' . esc_attr( $gold ) . '"></path>
		<path d="M32 31 C34 23 37 13 41 3" fill="none" stroke="' . esc_attr( $green ) . '" stroke-width="1.6" stroke-linecap="round"></path>
		<path d="M13 34 H51 A19 19 0 0 1 13 34 Z" fill="' . esc_attr( $green ) . '"></path>
		<path d="M50 38 h3 a5 5 0 0 1 0 10 h-5" fill="none" stroke="' . esc_attr( $green ) . '" stroke-width="3" stroke-linecap="round"></path>
		<path d="M10 59 H54" stroke="' . esc_attr( $green ) . '" stroke-width="3" stroke-linecap="round"></path>
	</svg>';
}

function leafy_badge_svg( $green = '#2f4a3c', $gold = '#c9a86a', $cream = '#faf6ee', $size = 120 ) {
	ob_start();
	?>
	<svg width="<?php echo (int) $size; ?>" height="<?php echo (int) $size; ?>" viewBox="0 0 200 200" role="img" aria-label="Leafy">
		<defs>
			<path id="leafyArcTop" d="M30 100 A70 70 0 0 1 170 100"></path>
			<path id="leafyArcBottom" d="M24 100 A76 76 0 0 0 176 100"></path>
		</defs>
		<circle cx="100" cy="100" r="96" fill="<?php echo esc_attr( $green ); ?>"></circle>
		<circle cx="100" cy="100" r="86" fill="none" stroke="<?php echo esc_attr( $gold ); ?>" stroke-width="1.5"></circle>
		<text font-family="'Playfair Display', serif" font-weight="700" font-size="17" letter-spacing="5" fill="<?php echo esc_attr( $cream ); ?>"><textPath href="#leafyArcTop" startOffset="50%" text-anchor="middle">LEAFY</textPath></text>
		<text font-family="'Jost', sans-serif" font-weight="500" font-size="11" letter-spacing="3" fill="<?php echo esc_attr( $gold ); ?>"><textPath href="#leafyArcBottom" startOffset="50%" text-anchor="middle">LOOSE LEAF TEA</textPath></text>
		<g transform="translate(68 66) scale(1)">
			<path d="M32 31 C21 24 22 9 41 3 C44 17 41 27 32 31 Z" fill="<?php echo esc_attr( $gold ); ?>"></path>
			<path d="M32 31 C34 23 37 13 41 3" fill="none" stroke="<?php echo esc_attr( $green ); ?>" stroke-width="1.6" stroke-linecap="round"></path>
			<path d="M13 34 H51 A19 19 0 0 1 13 34 Z" fill="<?php echo esc_attr( $cream ); ?>"></path>
			<path d="M50 38 h3 a5 5 0 0 1 0 10 h-5" fill="none" stroke="<?php echo esc_attr( $cream ); ?>" stroke-width="3" stroke-linecap="round"></path>
			<path d="M10 59 H54" stroke="<?php echo esc_attr( $cream ); ?>" stroke-width="3" stroke-linecap="round"></path>
		</g>
	</svg>
	<?php
	return ob_get_clean();
}

// Pluggable override: Storefront only defines this if it isn't already
// defined, and the child theme's functions.php loads before the parent's,
// so this definition wins cleanly (no hook-timing workaround needed).
function storefront_site_title_or_logo( $echo = true ) {
	$tag  = is_home() ? 'h1' : 'div';
	$html = '<' . esc_attr( $tag ) . ' class="beta site-title leafy-site-title">';
	$html .= '<a href="' . esc_url( home_url( '/' ) ) . '" rel="home">';
	$html .= leafy_mark_icon();
	$html .= '<span class="leafy-wordmark">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
	$html .= '</a></' . esc_attr( $tag ) . '>';

	if ( '' !== get_bloginfo( 'description' ) ) {
		$html .= '<p class="site-description">' . esc_html( get_bloginfo( 'description', 'display' ) ) . '</p>';
	}

	if ( ! $echo ) {
		return $html;
	}
	echo $html; // phpcs:ignore -- already escaped above.
}

/* -------------------------------------------------------------------
 * Account icon in the header, next to the cart - same spot on every
 * page (hooked just before storefront_header_cart at 60).
 * ---------------------------------------------------------------- */
add_action( 'storefront_header', 'leafy_header_account', 59 );
function leafy_header_account() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$account_url = get_permalink( wc_get_page_id( 'myaccount' ) );
	if ( ! $account_url ) {
		return;
	}
	$label = is_user_logged_in() ? __( 'My Account', 'leafy' ) : __( 'Log In', 'leafy' );
	echo '<ul id="site-header-account" class="site-header-account menu">';
	echo '<li><a href="' . esc_url( $account_url ) . '" class="header-account-link" aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '">' . leafy_icon( 'user' ) . '</a></li>';
	echo '</ul>';
}

/* -------------------------------------------------------------------
 * Language switcher in the top bar (Polylang)
 * ---------------------------------------------------------------- */
add_action( 'storefront_header', 'leafy_top_bar', 1 );
function leafy_top_bar() {
	echo '<div class="leafy-top-bar">';
	echo esc_html__( 'Free shipping in Denmark over 500 DKK', 'leafy' ) . ' &middot; ';
	echo '<a href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">' . esc_html__( 'Shop loose leaf tea', 'leafy' ) . '</a>';

	if ( function_exists( 'pll_the_languages' ) ) {
		$languages = pll_the_languages( array( 'raw' => 1 ) );
		if ( ! empty( $languages ) ) {
			echo '<span class="leafy-lang-switcher">';
			foreach ( $languages as $lang ) {
				$class = $lang['current_lang'] ? 'active' : '';
				echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $lang['url'] ) . '">' . esc_html( strtoupper( $lang['slug'] ) ) . '</a>';
			}
			echo '</span>';
		}
	}
	echo '</div>';
}

/* -------------------------------------------------------------------
 * GA4 snippet (digital marketing - analytics)
 * ---------------------------------------------------------------- */
add_action( 'wp_head', function () {
	if ( empty( LEAFY_GA4_MEASUREMENT_ID ) ) {
		return;
	}
	$id = esc_js( LEAFY_GA4_MEASUREMENT_ID );
	echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$id}\"></script>\n";
	echo "<script>\nwindow.dataLayer = window.dataLayer || [];\nfunction gtag(){dataLayer.push(arguments);}\ngtag('js', new Date());\ngtag('config', '{$id}');\n</script>\n";
}, 1 );

/* -------------------------------------------------------------------
 * Avoid a duplicate <title> tag: Yoast SEO renders its own, but the
 * core title-tag output (from Storefront's theme support) can still
 * fire at the same wp_head priority depending on plugin load order.
 * ---------------------------------------------------------------- */
add_action( 'wp_head', function () {
	remove_action( 'wp_head', '_wp_render_title_tag', 1 );
	remove_action( 'wp_head', '_block_template_render_title_tag', 1 );
	remove_action( 'wp_head', 'gutenberg_render_title_tag', 1 );
}, 0 );

/* -------------------------------------------------------------------
 * Open Graph fallback site name (Yoast handles most of this; this is
 * just a safety net for default site meta).
 * ---------------------------------------------------------------- */
add_action( 'wp_head', function () {
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
}, 2 );

/* -------------------------------------------------------------------
 * Testimonials (admin-manageable: name, quote, photo via featured image)
 * ---------------------------------------------------------------- */
add_action( 'init', function () {
	register_post_type( 'leafy_testimonial', array(
		'label'        => 'Testimonials',
		'labels'       => array(
			'name'          => 'Testimonials',
			'singular_name' => 'Testimonial',
			'add_new_item'  => 'Add New Testimonial',
			'edit_item'     => 'Edit Testimonial',
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'menu_icon'    => 'dashicons-format-quote',
		'supports'     => array( 'title', 'editor', 'thumbnail' ),
		'menu_position' => 25,
	) );
} );

add_filter( 'post_type_labels_leafy_testimonial', function ( $labels ) {
	$labels->title = 'Customer name';
	return $labels;
} );

add_action( 'admin_init', function () {
	add_post_type_support( 'leafy_testimonial', 'thumbnail' );
} );

function leafy_render_testimonials() {
	$testimonials = get_posts( array(
		'post_type'      => 'leafy_testimonial',
		'posts_per_page' => 6,
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
	) );

	if ( empty( $testimonials ) ) {
		return;
	}
	ob_start();
	?>
	<section class="leafy-section leafy-testimonials">
		<div class="leafy-section-title">
			<h2><?php esc_html_e( 'What Our Customers Say', 'leafy' ); ?></h2>
			<p><?php esc_html_e( 'Real words from real tea drinkers.', 'leafy' ); ?></p>
		</div>
		<div class="leafy-testimonials-grid">
			<?php foreach ( $testimonials as $t ) : ?>
				<figure class="leafy-testimonial-card">
					<?php if ( has_post_thumbnail( $t->ID ) ) : ?>
						<?php echo get_the_post_thumbnail( $t->ID, 'thumbnail', array( 'alt' => esc_attr( get_the_title( $t->ID) ), 'loading' => 'lazy' ) ); ?>
					<?php endif; ?>
					<blockquote>&ldquo;<?php echo esc_html( wp_strip_all_tags( $t->post_content ) ); ?>&rdquo;</blockquote>
					<figcaption><?php echo esc_html( get_the_title( $t->ID ) ); ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------
 * Newsletter signup -> custom post type "leafy_subscriber"
 * ---------------------------------------------------------------- */
add_action( 'init', function () {
	register_post_type( 'leafy_subscriber', array(
		'label'        => 'Newsletter Subscribers',
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'menu_icon'    => 'dashicons-email-alt',
		'supports'     => array( 'title' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap' => true,
	) );
} );

add_action( 'admin_post_leafy_newsletter_signup', 'leafy_handle_newsletter_signup' );
add_action( 'admin_post_nopriv_leafy_newsletter_signup', 'leafy_handle_newsletter_signup' );
function leafy_handle_newsletter_signup() {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	if ( ! isset( $_POST['leafy_newsletter_nonce'] ) || ! wp_verify_nonce( $_POST['leafy_newsletter_nonce'], 'leafy_newsletter' ) ) {
		wp_safe_redirect( add_query_arg( 'leafy_newsletter', 'error', $redirect ) );
		exit;
	}

	$email = isset( $_POST['leafy_email'] ) ? sanitize_email( wp_unslash( $_POST['leafy_email'] ) ) : '';

	if ( empty( $email ) || ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'leafy_newsletter', 'invalid', $redirect ) );
		exit;
	}

	$existing = get_page_by_title( $email, OBJECT, 'leafy_subscriber' );
	if ( ! $existing ) {
		wp_insert_post( array(
			'post_type'   => 'leafy_subscriber',
			'post_title'  => $email,
			'post_status' => 'publish',
		) );
	}

	wp_safe_redirect( add_query_arg( 'leafy_newsletter', 'success', $redirect ) );
	exit;
}

/* -------------------------------------------------------------------
 * Simple contact form shortcode
 * ---------------------------------------------------------------- */
add_shortcode( 'leafy_contact_form', function () {
	ob_start();
	$status = isset( $_GET['leafy_contact'] ) ? sanitize_text_field( $_GET['leafy_contact'] ) : '';
	if ( 'success' === $status ) {
		echo '<p class="leafy-form-notice woocommerce-message">' . esc_html__( 'Thanks for reaching out - we will reply within 1-2 business days.', 'leafy' ) . '</p>';
	}
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="leafy-contact-form">
		<input type="hidden" name="action" value="leafy_contact_submit">
		<?php wp_nonce_field( 'leafy_contact', 'leafy_contact_nonce' ); ?>
		<p><label><?php esc_html_e( 'Name', 'leafy' ); ?><br><input type="text" name="leafy_name" required style="width:100%;padding:.6em;border-radius:8px;border:1px solid #eee;"></label></p>
		<p><label><?php esc_html_e( 'Email', 'leafy' ); ?><br><input type="email" name="leafy_email" required style="width:100%;padding:.6em;border-radius:8px;border:1px solid #eee;"></label></p>
		<p><label><?php esc_html_e( 'Message', 'leafy' ); ?><br><textarea name="leafy_message" rows="5" required style="width:100%;padding:.6em;border-radius:8px;border:1px solid #eee;"></textarea></label></p>
		<p><button type="submit" class="button"><?php esc_html_e( 'Send Message', 'leafy' ); ?></button></p>
	</form>
	<?php
	return ob_get_clean();
} );

add_action( 'admin_post_leafy_contact_submit', 'leafy_handle_contact_submit' );
add_action( 'admin_post_nopriv_leafy_contact_submit', 'leafy_handle_contact_submit' );
function leafy_handle_contact_submit() {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	if ( ! isset( $_POST['leafy_contact_nonce'] ) || ! wp_verify_nonce( $_POST['leafy_contact_nonce'], 'leafy_contact' ) ) {
		wp_safe_redirect( $redirect );
		exit;
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['leafy_name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['leafy_email'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['leafy_message'] ?? '' ) );

	if ( $name && is_email( $email ) && $message ) {
		wp_mail(
			get_option( 'admin_email' ),
			'New contact message from ' . $name,
			$message . "\n\nFrom: {$name} <{$email}>",
			array( 'Reply-To: ' . $email )
		);
	}

	wp_safe_redirect( add_query_arg( 'leafy_contact', 'success', $redirect ) );
	exit;
}

/* -------------------------------------------------------------------
 * Responsive, privacy-friendly YouTube embed shortcode.
 * Usage: [leafy_video id="VIDEO_ID" title="Accessible title"]
 * ---------------------------------------------------------------- */
add_shortcode( 'leafy_video', function ( $atts ) {
	$atts = shortcode_atts( array(
		'id'    => '',
		'title' => 'Video',
	), $atts, 'leafy_video' );

	if ( empty( $atts['id'] ) ) {
		return '';
	}

	// Facade pattern: load only a static thumbnail + play button until clicked,
	// so the YouTube player (several MB of JS/CSS) never loads unless requested.
	return sprintf(
		'<button type="button" class="leafy-video-facade" data-video-id="%1$s" aria-label="%2$s">' .
		'<img src="https://i.ytimg.com/vi/%1$s/maxresdefault.jpg" alt="%2$s" loading="lazy" width="1280" height="720">' .
		'<span class="leafy-video-play" aria-hidden="true">&#9658;</span>' .
		'</button>',
		esc_attr( $atts['id'] ),
		esc_attr( $atts['title'] )
	);
} );

add_action( 'wp_footer', function () {
	?>
	<script>
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.leafy-video-facade');
		if (!btn) return;
		var id = btn.getAttribute('data-video-id');
		var title = btn.getAttribute('aria-label') || 'Video';
		var wrap = document.createElement('div');
		wrap.className = 'leafy-video-wrap';
		var iframe = document.createElement('iframe');
		iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1';
		iframe.title = title;
		iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
		iframe.setAttribute('allowfullscreen', '');
		iframe.style.border = '0';
		wrap.appendChild(iframe);
		btn.replaceWith(wrap);
	});
	</script>
	<?php
}, 100 );

/* -------------------------------------------------------------------
 * Photo grid shortcode - [leafy_photo_grid ids="12,13,14"]
 * ---------------------------------------------------------------- */
add_shortcode( 'leafy_photo_grid', function ( $atts ) {
	$atts = shortcode_atts( array( 'ids' => '' ), $atts, 'leafy_photo_grid' );
	$ids = array_filter( array_map( 'intval', explode( ',', $atts['ids'] ) ) );
	if ( empty( $ids ) ) {
		return '';
	}
	$out = '<div class="leafy-photo-grid">';
	foreach ( $ids as $id ) {
		$out .= wp_get_attachment_image( $id, 'medium', false, array( 'loading' => 'lazy' ) );
	}
	$out .= '</div>';
	return $out;
} );

/* -------------------------------------------------------------------
 * Hand-drawn line icon set (replaces emoji, which render inconsistently
 * across browsers/OSes and read as a generic/templated choice).
 * ---------------------------------------------------------------- */
function leafy_icon( $name ) {
	$icons = array(
		'leaf'     => '<path d="M4 20c0-9 5-15 15-16-1 10-7 15-16 16z"/><path d="M6.5 17.5c3-4 6-7 10.5-11.5"/>',
		'teacup'   => '<path d="M4 9.5h12v4.5a5.8 5.8 0 0 1-5.8 5.8h-.4A5.8 5.8 0 0 1 4 14V9.5z"/><path d="M16 10.3h1.6a2.3 2.3 0 0 1 0 4.6H16"/><path d="M4 20h12"/><path d="M8.3 4c-.7.8-.7 1.6 0 2.4M11.7 4c-.7.8-.7 1.6 0 2.4"/>',
		'berries'  => '<circle cx="8.6" cy="14.2" r="4"/><circle cx="15.2" cy="10" r="3"/><path d="M13.8 5.3c1 0 1.9.7 2.1 1.9"/><path d="M7.7 9.9c.3-1.5 1.3-2.4 2.8-2.8"/>',
		'sprig'    => '<path d="M12 21V6"/><path d="M12 9.5c0-2.6-2-4.2-5-4.2.2 2.9 2.2 4.4 5 4.2z"/><path d="M12 13.5c0-2.6 2-4.2 5-4.2-.2 2.9-2.2 4.4-5 4.2z"/><path d="M12 17.3c0-2.1-1.6-3.3-3.8-3.3.1 2.3 1.7 3.6 3.8 3.3z"/>',
		'gift'     => '<rect x="4" y="9.5" width="16" height="10.5" rx="1"/><path d="M4 13.3h16"/><path d="M12 9.5V20"/><path d="M12 9.5c-1.3-2.8-3.1-3.8-4.5-3-1 .6-1 1.8 0 2.4.9.5 2.6.7 4.5.6z"/><path d="M12 9.5c1.3-2.8 3.1-3.8 4.5-3 1 .6 1 1.8 0 2.4-.9.5-2.6.7-4.5.6z"/>',
		'mug'      => '<path d="M5 9.5h11v6.3A4.2 4.2 0 0 1 11.8 20H9.2A4.2 4.2 0 0 1 5 15.8V9.5z"/><path d="M16 11.3h1.7a2 2 0 0 1 0 4H16"/><circle cx="10.5" cy="5.8" r="1.3"/><path d="M10.5 3.3v-1"/>',
		'globe'    => '<circle cx="12" cy="12" r="8.2"/><path d="M3.8 12h16.4"/><path d="M12 3.8c2.5 2.2 3.8 5 3.8 8.2s-1.3 6-3.8 8.2c-2.5-2.2-3.8-5-3.8-8.2s1.3-6 3.8-8.2z"/>',
		'cycle'    => '<path d="M4.5 12a7.5 7.5 0 0 1 12.8-5.3"/><path d="M19.5 12a7.5 7.5 0 0 1-12.8 5.3"/><path d="M16.3 3.8l1 2.9-2.9 1"/><path d="M7.7 20.2l-1-2.9 2.9-1"/>',
		'shield'   => '<path d="M12 3.3l6.5 2.8v4.6c0 4.6-2.8 7.9-6.5 9.3-3.7-1.4-6.5-4.7-6.5-9.3V6.1L12 3.3z"/><path d="M8.8 12.1l2.1 2.1 4-4.5"/>',
		'envelope' => '<rect x="3.3" y="6" width="17.4" height="12" rx="1.6"/><path d="M4 7l8 6 8-6"/>',
		'user'     => '<circle cx="12" cy="8.2" r="3.7"/><path d="M4.5 20c.9-4 3.8-6.2 7.5-6.2s6.6 2.2 7.5 6.2"/>',
	);
	if ( ! isset( $icons[ $name ] ) ) { return ''; }
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}

function leafy_social_icon( $name ) {
	$icons = array(
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="4.2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="17.1" cy="6.9" r="1.1" fill="currentColor" stroke="none"/>',
		'facebook'  => '<path d="M14 21v-7.2h2.4l.4-3h-2.8V8.8c0-.9.2-1.5 1.5-1.5h1.4V4.6c-.2 0-1.1-.1-2.1-.1-2.1 0-3.5 1.3-3.5 3.6v2.7H8.8v3H11V21h3z" fill="currentColor" stroke="none"/>',
		'pinterest' => '<path d="M12 3a9 9 0 0 0-3.3 17.4c0-1 0-1.9.3-2.7l1.4-5.9s-.3-.7-.3-1.6c0-1.5.9-2.6 2-2.6.9 0 1.4.7 1.4 1.6 0 1-.6 2.4-1 3.7-.3 1.1.5 2 1.6 2 1.9 0 3.2-2.4 3.2-5.3 0-2.2-1.5-3.8-4.2-3.8-3 0-4.9 2.3-4.9 4.8 0 .9.3 1.5.7 2 .1.2.2.3.1.5l-.2.9c-.1.2-.2.3-.5.2-1.2-.5-1.8-1.9-1.8-3.4 0-2.6 2.2-5.6 6.5-5.6 3.5 0 5.8 2.5 5.8 5.2 0 3.6-1.9 6.2-4.8 6.2-1 0-1.9-.5-2.2-1.1l-.6 2.4c-.2.9-.7 1.9-1.1 2.6.8.2 1.6.4 2.5.4A9 9 0 0 0 12 3z" fill="currentColor" stroke="none"/>',
	);
	if ( ! isset( $icons[ $name ] ) ) { return ''; }
	return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}

/* -------------------------------------------------------------------
 * Homepage shortcode
 * ---------------------------------------------------------------- */
add_shortcode( 'leafy_homepage', 'leafy_render_homepage' );
function leafy_render_homepage() {
	ob_start();
	$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
	?>

	<section class="leafy-hero">
		<div class="leafy-hero-inner">
			<h1><?php esc_html_e( 'Loose Leaf Tea, Properly Brewed', 'leafy' ); ?></h1>
			<p><?php esc_html_e( 'Whole-leaf green, black, fruit and herbal teas, hand-packed in Copenhagen and shipped across Denmark, Europe and beyond.', 'leafy' ); ?></p>
			<a class="button" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop the Collection', 'leafy' ); ?></a>
		</div>
	</section>

	<section class="leafy-section leafy-brand-story">
		<div class="leafy-section-title">
			<h2><?php esc_html_e( 'Our Story, in Brief', 'leafy' ); ?></h2>
			<p><?php esc_html_e( 'From sourcing to your cup - a quick look at how Leafy works.', 'leafy' ); ?></p>
		</div>
		<?php echo do_shortcode( '[leafy_video id="ZYc9HyNCr1I" title="How to make loose leaf tea"]' ); ?>
		<?php echo do_shortcode( '[leafy_photo_grid ids="141,142,143"]' ); ?>
	</section>

	<section class="leafy-section leafy-categories">
		<div class="leafy-section-title">
			<h2><?php esc_html_e( 'Shop by Category', 'leafy' ); ?></h2>
			<p><?php esc_html_e( 'Six ways into the Leafy range - from everyday blends to gifting.', 'leafy' ); ?></p>
		</div>
		<div class="leafy-categories-grid">
			<?php
			$cats = array(
				'green-tea'   => array( 'leaf', 'cat-green', __( 'Green Tea', 'leafy' ) ),
				'black-tea'   => array( 'teacup', 'cat-black', __( 'Black Tea', 'leafy' ) ),
				'fruit-tea'   => array( 'berries', 'cat-fruit', __( 'Fruit Tea', 'leafy' ) ),
				'herbal-tea'  => array( 'sprig', 'cat-herbal', __( 'Herbal Tea', 'leafy' ) ),
				'gift-sets'   => array( 'gift', 'cat-gift', __( 'Gift Sets', 'leafy' ) ),
				'accessories' => array( 'mug', 'cat-accessories', __( 'Accessories', 'leafy' ) ),
			);
			foreach ( $cats as $slug => $data ) {
				list( $icon, $accent, $label ) = $data;
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				if ( ! $term ) { continue; }
				$link = get_term_link( $term );
				echo '<a class="leafy-category-card ' . esc_attr( $accent ) . '" href="' . esc_url( $link ) . '">';
				echo '<span class="icon-badge">' . leafy_icon( $icon ) . '</span>';
				echo '<span class="name">' . esc_html( $label ) . '</span>';
				echo '</a>';
			}
			?>
		</div>
	</section>

	<div class="leafy-values-bar">
		<div class="leafy-values-grid">
			<div class="leafy-value-item"><span class="icon-badge"><?php echo leafy_icon( 'globe' ); ?></span><strong><?php esc_html_e( 'Ships to DK, EU & Worldwide', 'leafy' ); ?></strong><span><?php esc_html_e( 'From Copenhagen to your door', 'leafy' ); ?></span></div>
			<div class="leafy-value-item"><span class="icon-badge"><?php echo leafy_icon( 'cycle' ); ?></span><strong><?php esc_html_e( 'Less Plastic', 'leafy' ); ?></strong><span><?php esc_html_e( 'Loose leaf, minimal packaging', 'leafy' ); ?></span></div>
			<div class="leafy-value-item"><span class="icon-badge"><?php echo leafy_icon( 'shield' ); ?></span><strong><?php esc_html_e( 'Secure Checkout', 'leafy' ); ?></strong><span><?php esc_html_e( 'Payments protected by Stripe', 'leafy' ); ?></span></div>
			<div class="leafy-value-item"><span class="icon-badge"><?php echo leafy_icon( 'envelope' ); ?></span><strong><?php esc_html_e( '10% Off First Order', 'leafy' ); ?></strong><span><?php esc_html_e( 'Join the newsletter below', 'leafy' ); ?></span></div>
		</div>
	</div>

	<section class="leafy-section leafy-featured">
		<div class="leafy-section-title">
			<h2><?php esc_html_e( 'Customer Favourites', 'leafy' ); ?></h2>
			<p><?php esc_html_e( 'A few of the blends people keep coming back for.', 'leafy' ); ?></p>
		</div>
		<?php echo do_shortcode( '[products limit="4" columns="4" orderby="date" order="desc"]' ); ?>
	</section>

	<?php echo leafy_render_testimonials(); ?>

	<section class="leafy-section">
		<div class="leafy-newsletter" id="newsletter">
			<?php
			$ns = isset( $_GET['leafy_newsletter'] ) ? sanitize_text_field( $_GET['leafy_newsletter'] ) : '';
			if ( 'success' === $ns ) :
			?>
				<h2><?php esc_html_e( "You're In!", 'leafy' ); ?></h2>
				<p><?php esc_html_e( 'Use code WELCOME10 at checkout for 10% off your first order.', 'leafy' ); ?></p>
			<?php else : ?>
				<h2><?php esc_html_e( 'Get 10% Off Your First Order', 'leafy' ); ?></h2>
				<p><?php esc_html_e( 'Join the Leafy newsletter for brewing tips, new blends and subscriber-only offers.', 'leafy' ); ?></p>
				<form class="leafy-newsletter-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="leafy_newsletter_signup">
					<?php wp_nonce_field( 'leafy_newsletter', 'leafy_newsletter_nonce' ); ?>
					<label for="leafy-newsletter-email" class="sr-only"><?php esc_html_e( 'Email address', 'leafy' ); ?></label>
					<input type="email" id="leafy-newsletter-email" name="leafy_email" placeholder="<?php esc_attr_e( 'you@example.com', 'leafy' ); ?>" required>
					<button type="submit" class="button alt"><?php esc_html_e( 'Subscribe', 'leafy' ); ?></button>
				</form>
				<p class="coupon-note"><?php esc_html_e( 'Coupon code: WELCOME10', 'leafy' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="leafy-section leafy-blog-preview">
		<div class="leafy-section-title">
			<h2><?php esc_html_e( 'From the Journal', 'leafy' ); ?></h2>
			<p><?php esc_html_e( 'Brewing guides, tea notes and gifting ideas.', 'leafy' ); ?></p>
		</div>
		<div class="leafy-blog-grid">
			<?php
			$recent = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 3 ) );
			while ( $recent->have_posts() ) : $recent->the_post();
				?>
				<article class="leafy-blog-card">
					<div class="blog-card-body">
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
						<a class="read-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read More →', 'leafy' ); ?></a>
					</div>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>

	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------
 * Danish homepage shortcode (translated landing page; product catalog
 * and blog stay English-first for now - see project notes).
 * ---------------------------------------------------------------- */
add_shortcode( 'leafy_homepage_da', 'leafy_render_homepage_da' );
function leafy_render_homepage_da() {
	ob_start();
	$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
	?>

	<section class="leafy-hero">
		<div class="leafy-hero-inner">
			<h1>Løsvægtte, brygget rigtigt</h1>
			<p>Helbladet grøn, sort, frugt- og urtete, pakket i hånden i København og sendt til Danmark, Europa og videre.</p>
			<a class="button" href="<?php echo esc_url( $shop_url ); ?>">Se sortimentet</a>
		</div>
	</section>

	<section class="leafy-section leafy-brand-story">
		<div class="leafy-section-title">
			<h2>Vores historie kort fortalt</h2>
			<p>Fra indkøb til din kop - et hurtigt kig på, hvordan Leafy arbejder.</p>
		</div>
		<?php echo do_shortcode( '[leafy_video id="ZYc9HyNCr1I" title="How to make loose leaf tea"]' ); ?>
		<?php echo do_shortcode( '[leafy_photo_grid ids="141,142,143"]' ); ?>
	</section>

	<section class="leafy-section leafy-categories">
		<div class="leafy-section-title">
			<h2>Shop efter kategori</h2>
			<p>Seks veje ind i Leafy-sortimentet - fra hverdagsblandinger til gaver.</p>
		</div>
		<div class="leafy-categories-grid">
			<?php
			$cats = array(
				'green-tea'   => array( 'leaf', 'cat-green', 'Grøn te' ),
				'black-tea'   => array( 'teacup', 'cat-black', 'Sort te' ),
				'fruit-tea'   => array( 'berries', 'cat-fruit', 'Frugtte' ),
				'herbal-tea'  => array( 'sprig', 'cat-herbal', 'Urtete' ),
				'gift-sets'   => array( 'gift', 'cat-gift', 'Gavesæt' ),
				'accessories' => array( 'mug', 'cat-accessories', 'Tilbehør' ),
			);
			foreach ( $cats as $slug => $data ) {
				list( $icon, $accent, $label ) = $data;
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				if ( ! $term ) { continue; }
				$link = get_term_link( $term );
				echo '<a class="leafy-category-card ' . esc_attr( $accent ) . '" href="' . esc_url( $link ) . '">';
				echo '<span class="icon-badge">' . leafy_icon( $icon ) . '</span>';
				echo '<span class="name">' . esc_html( $label ) . '</span>';
				echo '</a>';
			}
			?>
		</div>
	</section>

	<div class="leafy-values-bar">
		<div class="leafy-values-grid">
			<div class="leafy-value-item"><span class="icon-badge"><?php echo leafy_icon( 'globe' ); ?></span><strong>Sender til DK, EU og hele verden</strong><span>Fra København til din dør</span></div>
			<div class="leafy-value-item"><span class="icon-badge"><?php echo leafy_icon( 'cycle' ); ?></span><strong>Mindre plastik</strong><span>Løsvægt, minimal emballage</span></div>
			<div class="leafy-value-item"><span class="icon-badge"><?php echo leafy_icon( 'shield' ); ?></span><strong>Sikker betaling</strong><span>Betalinger beskyttet af Stripe</span></div>
			<div class="leafy-value-item"><span class="icon-badge"><?php echo leafy_icon( 'envelope' ); ?></span><strong>10% rabat på din første ordre</strong><span>Tilmeld dig nyhedsbrevet nedenfor</span></div>
		</div>
	</div>

	<section class="leafy-section leafy-featured">
		<div class="leafy-section-title">
			<h2>Kundefavoritter</h2>
			<p>Nogle af de blandinger, folk vender tilbage til igen og igen.</p>
		</div>
		<?php echo do_shortcode( '[products limit="4" columns="4" orderby="date" order="desc"]' ); ?>
	</section>

	<?php echo leafy_render_testimonials(); ?>

	<section class="leafy-section">
		<div class="leafy-newsletter" id="newsletter">
			<?php
			$ns = isset( $_GET['leafy_newsletter'] ) ? sanitize_text_field( $_GET['leafy_newsletter'] ) : '';
			if ( 'success' === $ns ) :
			?>
				<h2>Du er tilmeldt!</h2>
				<p>Brug koden WELCOME10 ved kassen for 10% rabat på din første ordre.</p>
			<?php else : ?>
				<h2>Få 10% rabat på din første ordre</h2>
				<p>Tilmeld dig Leafy-nyhedsbrevet for bryggetips, nye blandinger og eksklusive tilbud.</p>
				<form class="leafy-newsletter-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="leafy_newsletter_signup">
					<?php wp_nonce_field( 'leafy_newsletter', 'leafy_newsletter_nonce' ); ?>
					<label for="leafy-newsletter-email-da" class="sr-only">E-mailadresse</label>
					<input type="email" id="leafy-newsletter-email-da" name="leafy_email" placeholder="dig@eksempel.dk" required>
					<button type="submit" class="button alt">Tilmeld</button>
				</form>
				<p class="coupon-note">Rabatkode: WELCOME10</p>
			<?php endif; ?>
		</div>
	</section>

	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------
 * Small helper: find a published page by its exact title (cached).
 * ---------------------------------------------------------------- */
function leafy_find_page( $title ) {
	static $cache = array();
	if ( isset( $cache[ $title ] ) ) {
		return $cache[ $title ];
	}
	$query = new WP_Query( array(
		'post_type'      => 'page',
		'title'          => $title,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
	) );
	$cache[ $title ] = $query->have_posts() ? $query->posts[0] : null;
	return $cache[ $title ];
}

/* -------------------------------------------------------------------
 * Custom footer (replaces default Storefront footer widgets/credit)
 * ---------------------------------------------------------------- */
add_action( 'storefront_footer', function () {
	remove_action( 'storefront_footer', 'storefront_footer_widgets', 10 );
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
}, 0 );
add_action( 'storefront_footer', 'leafy_custom_footer', 10 );
function leafy_custom_footer() {
	$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
	?>
	<div class="leafy-footer-widgets">
		<div>
			<div class="leafy-footer-badge"><?php echo leafy_badge_svg( '#ffffff', '#c9a86a', '#2f4a3c', 88 ); ?></div>
			<p><?php esc_html_e( 'Whole-leaf tea and brewing accessories, hand-packed in Copenhagen.', 'leafy' ); ?></p>
			<div class="leafy-footer-social">
				<a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><?php echo leafy_social_icon( 'instagram' ); ?></a>
				<a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook"><?php echo leafy_social_icon( 'facebook' ); ?></a>
				<a href="https://pinterest.com" target="_blank" rel="noopener" aria-label="Pinterest"><?php echo leafy_social_icon( 'pinterest' ); ?></a>
			</div>
		</div>
		<div>
			<h3><?php esc_html_e( 'Shop', 'leafy' ); ?></h3>
			<p>
				<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'All Teas', 'leafy' ); ?></a><br>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'cart' ) ) ); ?>"><?php esc_html_e( 'Cart', 'leafy' ); ?></a><br>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>"><?php esc_html_e( 'My Account', 'leafy' ); ?></a>
			</p>
		</div>
		<div>
			<h3><?php esc_html_e( 'Company', 'leafy' ); ?></h3>
			<p>
				<?php $about = leafy_find_page( 'About Us' ); if ( $about ) : ?><a href="<?php echo esc_url( get_permalink( $about ) ); ?>"><?php esc_html_e( 'About Us', 'leafy' ); ?></a><br><?php endif; ?>
				<?php $contact = leafy_find_page( 'Contact' ); if ( $contact ) : ?><a href="<?php echo esc_url( get_permalink( $contact ) ); ?>"><?php esc_html_e( 'Contact', 'leafy' ); ?></a><br><?php endif; ?>
				<?php $priv = leafy_find_page( 'Privacy Policy' ); if ( $priv ) : ?><a href="<?php echo esc_url( get_permalink( $priv ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'leafy' ); ?></a><br><?php endif; ?>
				<?php $ret = leafy_find_page( 'Refund and Returns Policy' ); if ( $ret ) : ?><a href="<?php echo esc_url( get_permalink( $ret ) ); ?>"><?php esc_html_e( 'Returns Policy', 'leafy' ); ?></a><?php endif; ?>
			</p>
		</div>
		<div>
			<h3><?php esc_html_e( 'Newsletter', 'leafy' ); ?></h3>
			<p><?php esc_html_e( '10% off your first order when you subscribe.', 'leafy' ); ?></p>
			<a class="button alt" href="<?php echo esc_url( home_url( '/#newsletter' ) ); ?>"><?php esc_html_e( 'Subscribe', 'leafy' ); ?></a>
			<p class="leafy-payment-icons"><?php esc_html_e( 'Secure payment via Stripe', 'leafy' ); ?></p>
		</div>
	</div>
	<div class="leafy-footer-bottom">
		&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?> &middot; <?php esc_html_e( 'All rights reserved.', 'leafy' ); ?>
	</div>
	<?php
}

/* -------------------------------------------------------------------
 * Homepage: hide the default page title + sidebar (the hero section
 * in the [leafy_homepage] shortcode already serves that purpose).
 * ---------------------------------------------------------------- */
add_action( 'storefront_page', function () {
	if ( is_front_page() ) {
		remove_action( 'storefront_page', 'storefront_page_header', 10 );
	}
}, 0 );

// No sidebar anywhere - keeps every page content centered in a single
// column instead of pushed left with an empty-looking gap on the right.
add_action( 'storefront_sidebar', function () {
	remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
}, 0 );
add_filter( 'storefront_sidebar_layout', '__return_false' );
add_filter( 'storefront_is_full_width', '__return_true' );

/* -------------------------------------------------------------------
 * Shop archive header banner
 * ---------------------------------------------------------------- */
add_action( 'woocommerce_before_main_content', 'leafy_shop_header_banner', 5 );

// Storefront shows the sort/filter bar AND pagination both above and
// below the product grid by default. Keep sorting only at the top and
// pagination only at the bottom (centered via CSS below).
add_action( 'woocommerce_before_shop_loop', function () {
	remove_action( 'woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30 );
}, 0 );
add_action( 'woocommerce_after_shop_loop', function () {
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
}, 0 );
add_action( 'woocommerce_shop_loop_header', function () {
	remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
}, 0 );
function leafy_shop_header_banner() {
	if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
		return;
	}
	echo '<div class="leafy-shop-header"><h1>';
	woocommerce_page_title();
	echo '</h1><p>' . esc_html__( 'Whole-leaf tea, sourced with care.', 'leafy' ) . '</p></div>';
}
// Breadcrumbs removed site-wide (WooCommerce pages and everywhere else
// Storefront would print one) - the main nav + page titles already
// orient visitors without it.
add_action( 'storefront_before_content', function () {
	remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
}, 0 );
add_action( 'woocommerce_before_shop_loop', function () {
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
}, 0 );
add_action( 'woocommerce_before_main_content', 'woocommerce_output_all_notices', 6 );

/* -------------------------------------------------------------------
 * Checkout: order confirmation page value-prop message + GA4 purchase
 * event placeholder (digital marketing - conversion tracking)
 * ---------------------------------------------------------------- */
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	if ( empty( LEAFY_GA4_MEASUREMENT_ID ) || ! $order_id ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) { return; }
	?>
	<script>
	window.dataLayer = window.dataLayer || [];
	dataLayer.push({
		event: 'purchase',
		transaction_id: '<?php echo esc_js( $order->get_order_number() ); ?>',
		value: <?php echo esc_js( $order->get_total() ); ?>,
		currency: '<?php echo esc_js( $order->get_currency() ); ?>'
	});
	</script>
	<?php
} );
