<?php
/**
 * Theme header.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="pd-skip-link" href="#pd-main"><?php esc_html_e( 'Skip to content', 'pinsdownload' ); ?></a>

<header class="pd-site-header" id="pd-site-header">
	<div class="pd-site-header__inner">
		<a class="pd-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="pd-logo__text"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>

		<nav class="pd-primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'pinsdownload' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'pd-primary-nav__list',
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>

		<a class="pd-btn pd-btn--primary pd-header-cta" href="#pd-download-tool">
			<?php esc_html_e( 'Download', 'pinsdownload' ); ?>
		</a>
	</div>
</header>

<main id="pd-main" class="pd-main">
