<?php
/**
 * Theme footer.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>

<footer class="pd-site-footer">
	<div class="pd-container">
		<nav class="pd-footer-nav" aria-label="<?php esc_attr_e( 'Footer', 'pinsdownload' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'pd-footer-nav__list',
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>
		<p class="pd-footer-note">
			<?php esc_html_e( 'This site is not affiliated with, endorsed by, or sponsored by Pinterest. Pinterest is a trademark of Pinterest, Inc.', 'pinsdownload' ); ?>
		</p>
		<p class="pd-footer-copy">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'pinsdownload' ); ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
