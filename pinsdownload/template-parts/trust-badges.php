<?php
/**
 * Reusable trust badges block. Used on the homepage and on every Tool
 * Landing Page. Links are wired to the real domain already; they show
 * "no data yet" until the live site has been crawled for a few weeks,
 * that's normal and not something to fix in code.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="pd-band pd-band--plain pd-trust-badges">
	<div class="pd-container pd-section pd-reveal">
		<h2><?php esc_html_e( 'Trust Badges', 'pinsdownload' ); ?></h2>
		<p>
			<?php esc_html_e( 'Check our current reputation:', 'pinsdownload' ); ?>
		</p>
		<div class="pd-trust-badges__list">
			<a class="pd-trust-badge" href="https://transparencyreport.google.com/safe-browsing/search?url=pinsdownload.org" rel="nofollow noopener" target="_blank">
				<?php pinsdownload_icon_e( 'shield' ); ?>Google Safe Browsing
			</a>
			<a class="pd-trust-badge" href="https://safeweb.norton.com/report?url=pinsdownload.org" rel="nofollow noopener" target="_blank">
				<?php pinsdownload_icon_e( 'shield' ); ?>Norton Safe Web
			</a>
			<a class="pd-trust-badge" href="https://sitecheck.sucuri.net/results/pinsdownload.org" rel="nofollow noopener" target="_blank">
				<?php pinsdownload_icon_e( 'shield' ); ?>Sucuri Scanner
			</a>
		</div>
	</div>
</section>
