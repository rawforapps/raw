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
<section class="pd-section pd-container pd-bg-soft pd-trust-badges">
	<h2><?php esc_html_e( 'Trust Badges', 'pinsdownload' ); ?></h2>
	<p>
		<?php esc_html_e( 'Check our current reputation:', 'pinsdownload' ); ?>
		<a href="https://transparencyreport.google.com/safe-browsing/search?url=pinsdownload.org" rel="nofollow noopener" target="_blank">Google Safe Browsing</a>
		<a href="https://safeweb.norton.com/report?url=pinsdownload.org" rel="nofollow noopener" target="_blank">Norton Safe Web</a>
		<a href="https://sitecheck.sucuri.net/results/pinsdownload.org" rel="nofollow noopener" target="_blank">Sucuri Scanner</a>
	</p>
</section>
