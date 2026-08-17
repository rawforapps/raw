<?php
/**
 * Homepage. Every section below mirrors the finalized PinsDownload
 * homepage copy, section for section. This file is the model every
 * Tool Landing Page (page-templates/template-tool-landing.php) is a
 * thinner version of.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<!-- 1. Hero -->
<section class="pd-hero pd-container">
	<h1><?php esc_html_e( 'Pinterest Video Downloader', 'pinsdownload' ); ?></h1>
	<p class="pd-hero__subtitle">
		<?php esc_html_e( 'Download Pinterest videos, images, GIFs, and stories in one click. Free, fast, and no account needed.', 'pinsdownload' ); ?>
	</p>
	<?php echo do_shortcode( '[pinsdownload_tool type="general"]' ); ?>
</section>

<!-- 2. Supported Formats & Quality Strip -->
<div class="pd-strip">
	<?php esc_html_e( 'HD · 2K · 4K quality · No watermark · MP4, JPG, PNG, GIF supported · Works on phone, tablet, and computer', 'pinsdownload' ); ?>
</div>

<?php do_action( 'pinsdownload_ad_slot', 'homepage_after_strip' ); ?>

<!-- 3. How to Download a Pinterest Video -->
<section class="pd-section pd-container" id="how-to">
	<h2><?php esc_html_e( 'How to Download a Pinterest Video', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( 'A Pinterest video downloader works in three steps. Open Pinterest and find the video you want. Tap the share icon and choose "Copy Link." Paste the link above and tap Download.', 'pinsdownload' ); ?></p>
	<ol class="pd-steps">
		<li><?php esc_html_e( 'Open Pinterest and find the video you want.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Tap the share icon and choose "Copy Link."', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Paste the link above and tap Download.', 'pinsdownload' ); ?></li>
	</ol>
	<p><?php esc_html_e( 'Your video saves straight to your device. No app, no sign up.', 'pinsdownload' ); ?></p>
</section>

<!-- 4. Downloading From the Pinterest App -->
<section class="pd-section pd-container pd-bg-soft">
	<h2><?php esc_html_e( 'Downloading From the Pinterest App', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( 'You can download Pinterest videos straight from the Pinterest app without leaving it open. Copy the link, come back here, and paste it.', 'pinsdownload' ); ?></p>
	<ol class="pd-steps">
		<li><?php esc_html_e( 'Open the Pinterest app and find your pin.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Tap the three dots (•••) on the pin.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Tap Copy Link.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Come back here, paste the link, and tap Download.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Your file saves to your Photos or Downloads folder.', 'pinsdownload' ); ?></li>
	</ol>
</section>

<!-- 5. Downloading on a Computer -->
<section class="pd-section pd-container">
	<h2><?php esc_html_e( 'Downloading on a Computer', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( 'You can also download Pinterest videos on a computer, using any browser.', 'pinsdownload' ); ?></p>
	<ol class="pd-steps">
		<li><?php esc_html_e( 'Open Pinterest.com in your browser.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Click the pin, then copy the link from your address bar.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Paste it above and click Download.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( "The file lands in your computer's Downloads folder.", 'pinsdownload' ); ?></li>
	</ol>
</section>

<!-- 6. iPhone Guide -->
<section class="pd-section pd-container pd-bg-soft">
	<h2><?php esc_html_e( 'How to Download Pinterest Videos on iPhone', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( "Yes, PinsDownload works on iPhone. You don't need an app, just Safari and a Pinterest link.", 'pinsdownload' ); ?></p>
	<ol class="pd-steps">
		<li><?php esc_html_e( 'Open the Pinterest app on your iPhone.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Tap the share icon on the video, then Copy Link.', 'pinsdownload' ); ?></li>
		<li><?php echo esc_html( sprintf( /* translators: %s: site domain */ __( 'Open Safari and go to %s.', 'pinsdownload' ), 'pinsdownload.org' ) ); ?></li>
		<li><?php esc_html_e( 'Paste the link and tap Download.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Save the video to your Photos app when it finishes.', 'pinsdownload' ); ?></li>
	</ol>
</section>

<!-- 7. Android Guide -->
<section class="pd-section pd-container">
	<h2><?php esc_html_e( 'How to Download Pinterest Videos on Android', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( 'Yes, PinsDownload works on Android too, right inside Chrome.', 'pinsdownload' ); ?></p>
	<ol class="pd-steps">
		<li><?php esc_html_e( 'Open the Pinterest app and find your video.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'Tap Share, then Copy Link.', 'pinsdownload' ); ?></li>
		<li><?php echo esc_html( sprintf( __( 'Open Chrome and visit %s.', 'pinsdownload' ), 'pinsdownload.org' ) ); ?></li>
		<li><?php esc_html_e( 'Paste the link and tap Download.', 'pinsdownload' ); ?></li>
		<li><?php esc_html_e( 'The video saves to your Gallery or Downloads folder.', 'pinsdownload' ); ?></li>
	</ol>
</section>

<!-- 8. What Works / What Doesn't -->
<?php get_template_part( 'template-parts/works-doesnt' ); ?>

<!-- 9. Why People Use This Tool -->
<section class="pd-section pd-container">
	<h2><?php esc_html_e( 'Why People Use This Tool', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( "PinsDownload is built to be simple, honest, and free. Here's what that means in practice.", 'pinsdownload' ); ?></p>
	<ul class="pd-features">
		<li><strong><?php esc_html_e( 'Free, always.', 'pinsdownload' ); ?></strong> <?php esc_html_e( 'No hidden charges, no daily limit.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'No watermark.', 'pinsdownload' ); ?></strong> <?php esc_html_e( 'Your download looks exactly like the original.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'No login.', 'pinsdownload' ); ?></strong> <?php esc_html_e( 'We never ask for your Pinterest password.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Original quality.', 'pinsdownload' ); ?></strong> <?php esc_html_e( 'Videos and images save in the same resolution Pinterest gives us.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Works everywhere.', 'pinsdownload' ); ?></strong> <?php esc_html_e( 'Phone, tablet, or computer, any browser.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Honest about ads.', 'pinsdownload' ); ?></strong> <?php esc_html_e( 'A small number of ads keep this tool free. They never sit on top of or look like the Download button.', 'pinsdownload' ); ?></li>
	</ul>
</section>

<!-- 10. What Else You Can Download -->
<section class="pd-section pd-container pd-bg-soft">
	<h2><?php esc_html_e( 'What Else You Can Download From Pinterest', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( "PinsDownload isn't only for videos. It handles every kind of Pinterest content.", 'pinsdownload' ); ?></p>
	<ul class="pd-features">
		<li><strong><?php esc_html_e( 'Images and photos', 'pinsdownload' ); ?></strong> — <?php esc_html_e( 'save any pin in full resolution.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'GIFs', 'pinsdownload' ); ?></strong> — <?php esc_html_e( 'download animated pins without losing the loop.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Reels and short videos', 'pinsdownload' ); ?></strong> — <?php esc_html_e( "grab Pinterest's short-form clips.", 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Stories and Idea Pins', 'pinsdownload' ); ?></strong> — <?php esc_html_e( 'save every slide of a multi-page pin.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Carousels', 'pinsdownload' ); ?></strong> — <?php esc_html_e( 'download every image or video in a multi-item pin.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Boards', 'pinsdownload' ); ?></strong> — <?php esc_html_e( 'save up to 100 pins from a public board at once, or grab the whole thing as a ZIP file.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Profiles', 'pinsdownload' ); ?></strong> — <?php esc_html_e( 'download every public pin from a Pinterest profile.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Ideas pages', 'pinsdownload' ); ?></strong> — <?php esc_html_e( 'save content straight from a Pinterest Ideas collection.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Answers pages', 'pinsdownload' ); ?></strong> — <?php esc_html_e( 'download pins shared on a Pinterest Answers page.', 'pinsdownload' ); ?></li>
		<li><strong><?php esc_html_e( 'Shared pin links', 'pinsdownload' ); ?></strong> — <?php esc_html_e( 'paste any multi-pin share link and download everything in it.', 'pinsdownload' ); ?></li>
	</ul>
</section>

<!-- 11. What Is a Pinterest Video Downloader -->
<section class="pd-section pd-container">
	<h2><?php esc_html_e( 'What Is a Pinterest Video Downloader?', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( "A Pinterest video downloader is a free online tool that saves Pinterest videos, images, and GIFs to your device. Pinterest doesn't let you download videos directly from its app or website, so this tool reads the pin's link and gives you a direct file to save. You don't need an account, and nothing is stored on our end after your download finishes.", 'pinsdownload' ); ?></p>
</section>

<!-- 12. What People Use It For -->
<section class="pd-section pd-container pd-bg-soft">
	<h2><?php esc_html_e( 'What People Use It For', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( 'People download Pinterest content for all kinds of projects. Here are the most common ones.', 'pinsdownload' ); ?></p>
	<p><?php esc_html_e( 'Home décor ideas · Recipes and food photography · Fashion inspiration · DIY and craft projects · Wedding planning · Travel photos · Fitness routines · Study notes and aesthetics · Art references · Mood boards', 'pinsdownload' ); ?></p>
</section>

<!-- 13. Comparison Table (DUMMY: verify every claim before launch) -->
<!-- DUMMY: test each row against the live tool before publishing. -->
<section class="pd-section pd-container">
	<h2><?php esc_html_e( 'How This Compares to Other Downloaders', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( 'PinsDownload is built to beat the basics that most Pinterest downloaders get wrong.', 'pinsdownload' ); ?></p>
	<div class="pd-table">
		<table>
			<thead>
				<tr>
					<th></th>
					<th><?php esc_html_e( 'PinsDownload', 'pinsdownload' ); ?></th>
					<th><?php esc_html_e( 'Typical Free Downloaders', 'pinsdownload' ); ?></th>
					<th><?php esc_html_e( 'Downloader Apps', 'pinsdownload' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr><td><?php esc_html_e( 'Quality', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Up to 4K', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Often capped at 720p', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Sometimes compressed', 'pinsdownload' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Watermark', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'None', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Usually none', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Often adds app logo', 'pinsdownload' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Login needed', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'No', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'No', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Often yes', 'pinsdownload' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Speed', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Seconds', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Slow, ad-heavy', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Medium', 'pinsdownload' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Bulk/ZIP download', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Yes, up to 100 pins', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Rare', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Rare', 'pinsdownload' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Privacy', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Nothing stored', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Varies', 'pinsdownload' ); ?></td><td><?php esc_html_e( 'Often collects data', 'pinsdownload' ); ?></td></tr>
			</tbody>
		</table>
	</div>
	<p><em><?php esc_html_e( 'Every row in the PinsDownload column must be true and tested before this goes live.', 'pinsdownload' ); ?></em></p>
</section>

<!-- 14. Works on Every Device -->
<section class="pd-section pd-container pd-bg-soft">
	<h2><?php esc_html_e( 'Works on Every Device', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( 'PinsDownload runs in a browser, so it works on almost anything with an internet connection.', 'pinsdownload' ); ?></p>
	<ul class="pd-devices">
		<li><strong><?php esc_html_e( 'Android', 'pinsdownload' ); ?></strong> — Chrome, Firefox</li>
		<li><strong><?php esc_html_e( 'iPhone / iPad', 'pinsdownload' ); ?></strong> — Safari, Chrome</li>
		<li><strong><?php esc_html_e( 'Windows', 'pinsdownload' ); ?></strong> — Chrome, Edge</li>
		<li><strong><?php esc_html_e( 'Mac', 'pinsdownload' ); ?></strong> — Safari, Chrome</li>
		<li><strong><?php esc_html_e( 'Linux', 'pinsdownload' ); ?></strong> — Firefox, Chrome</li>
	</ul>
</section>

<!-- 15. Is This Safe to Use -->
<section class="pd-section pd-container">
	<h2><?php esc_html_e( 'Is This Safe to Use?', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( 'Yes. We never ask for your Pinterest username or password. You paste a public link, we fetch the file, and nothing you download is stored on our servers afterward. We use standard analytics to see which pages are useful, the same as most websites, but your download history stays private.', 'pinsdownload' ); ?></p>
</section>

<!-- 16. Trust Badges (DUMMY until domain has scan history) -->
<!-- DUMMY: shows "no data yet" until the live domain has been crawled for a few weeks. Normal, don't remove. -->
<section class="pd-section pd-container pd-bg-soft pd-trust-badges">
	<h2><?php esc_html_e( 'Trust Badges', 'pinsdownload' ); ?></h2>
	<p>
		<?php esc_html_e( 'Check our current reputation:', 'pinsdownload' ); ?>
		<a href="https://transparencyreport.google.com/safe-browsing/search?url=pinsdownload.org" rel="nofollow noopener" target="_blank">Google Safe Browsing</a>
		<a href="https://safeweb.norton.com/report?url=pinsdownload.org" rel="nofollow noopener" target="_blank">Norton Safe Web</a>
		<a href="https://sitecheck.sucuri.net/results/pinsdownload.org" rel="nofollow noopener" target="_blank">Sucuri Scanner</a>
	</p>
</section>

<!-- 17. Is It Legal -->
<section class="pd-section pd-container">
	<h2><?php esc_html_e( 'Is It Legal to Download Pinterest Videos?', 'pinsdownload' ); ?></h2>
	<p><?php esc_html_e( 'Downloading a Pinterest video for personal, offline use is generally fine. Reposting, selling, or reusing someone else\'s video without permission is not. Pinterest content belongs to the person who posted it, so always ask before using it publicly.', 'pinsdownload' ); ?></p>
</section>

<!-- 18. Testimonials (DUMMY: replace with real reviews or delete before launch) -->
<!-- DUMMY: replace with 5-10 real reviews, or remove this section entirely before going live. -->
<section class="pd-section pd-container pd-bg-soft">
	<h2><?php esc_html_e( 'What Users Say', 'pinsdownload' ); ?></h2>
	<div class="pd-testimonials">
		<div class="pd-testimonial">
			<div class="pd-testimonial__stars">★★★★★</div>
			<strong><?php esc_html_e( 'Good tool', 'pinsdownload' ); ?></strong>
			<p>"<?php esc_html_e( 'Barely any ads, and it saved my pins in seconds. Exactly what I was looking for.', 'pinsdownload' ); ?>"</p>
			<cite>— J. Ahmed, Aug 2026</cite>
		</div>
		<div class="pd-testimonial">
			<div class="pd-testimonial__stars">★★★★★</div>
			<strong><?php esc_html_e( 'Simple and fast', 'pinsdownload' ); ?></strong>
			<p>"<?php esc_html_e( 'Just paste the link and it works. No sign up, no confusing steps.', 'pinsdownload' ); ?>"</p>
			<cite>— M. Khan, Jul 2026</cite>
		</div>
		<div class="pd-testimonial">
			<div class="pd-testimonial__stars">★★★★☆</div>
			<strong><?php esc_html_e( 'Does what it says', 'pinsdownload' ); ?></strong>
			<p>"<?php esc_html_e( 'Downloaded a whole board for a mood board project, saved me a lot of time.', 'pinsdownload' ); ?>"</p>
			<cite>— S. Rehman, Jul 2026</cite>
		</div>
	</div>
</section>

<!-- 19. What's New -->
<section class="pd-section pd-container">
	<h2><?php esc_html_e( "What's New", 'pinsdownload' ); ?></h2>
	<p><strong>Aug 2026 — <?php esc_html_e( 'Launched:', 'pinsdownload' ); ?></strong> <?php esc_html_e( 'PinsDownload is live, with video, image, GIF, story, carousel, board, and profile downloads all working from day one.', 'pinsdownload' ); ?></p>
</section>

<!-- 20. Guides & Tips (hidden until posts exist) -->
<?php
$guides = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 3, 'post_status' => 'publish' ) );
if ( $guides ) :
	?>
	<section class="pd-section pd-container pd-bg-soft">
		<h2><?php esc_html_e( 'Guides & Tips', 'pinsdownload' ); ?></h2>
		<p>
			<?php
			$links = array();
			foreach ( $guides as $guide ) {
				$links[] = '<a href="' . esc_url( get_permalink( $guide ) ) . '">' . esc_html( get_the_title( $guide ) ) . '</a>';
			}
			echo wp_kses_post( implode( ' · ', $links ) );
			?>
		</p>
	</section>
	<?php
endif;
?>

<!-- 21. FAQ -->
<?php
$faq = array(
	array( __( 'Is PinsDownload safe to use?', 'pinsdownload' ), __( "Yes. We never ask for your Pinterest login, and we don't store the files you download.", 'pinsdownload' ) ),
	array( __( 'Is it legal to download Pinterest videos?', 'pinsdownload' ), __( 'Downloading for personal use is fine. Reposting someone else\'s work without permission is not.', 'pinsdownload' ) ),
	array( __( 'Do I need to log in to my Pinterest account?', 'pinsdownload' ), __( 'No. PinsDownload only works with public links, so no login is needed.', 'pinsdownload' ) ),
	array( __( 'What video and image formats are supported?', 'pinsdownload' ), __( 'Videos save as MP4. Images save as JPG or PNG. GIFs keep their original animation.', 'pinsdownload' ) ),
	array( __( 'Does this tool save my downloaded content?', 'pinsdownload' ), __( 'No. We fetch the file and send it straight to you. Nothing is kept on our servers.', 'pinsdownload' ) ),
	array( __( 'Can I download Pinterest videos without a watermark?', 'pinsdownload' ), __( 'Yes. Every download matches the original Pinterest file, with no watermark added.', 'pinsdownload' ) ),
	array( __( 'Is there a limit on how many videos I can download?', 'pinsdownload' ), __( 'No daily limit for single pins. Board and profile downloads are capped at 100 pins per request.', 'pinsdownload' ) ),
	array( __( 'Does this work on iPhone and Android?', 'pinsdownload' ), __( 'Yes. PinsDownload runs in your browser, so it works on iPhone, Android, and desktop.', 'pinsdownload' ) ),
	array( __( 'Can I download a full Pinterest board or profile?', 'pinsdownload' ), __( 'Yes. Paste the board or profile link, then choose to download items one by one or all at once as a ZIP.', 'pinsdownload' ) ),
	array( __( 'Can I download private or deleted pins?', 'pinsdownload' ), __( 'No. PinsDownload only works with public, active pins.', 'pinsdownload' ) ),
	array( __( 'What should I do if a download fails?', 'pinsdownload' ), __( 'Check that the link is public and still active. If it still fails, try copying the link again from Pinterest.', 'pinsdownload' ) ),
	array( __( 'Will the video lose quality after downloading?', 'pinsdownload' ), __( 'No. PinsDownload saves the file at the same resolution Pinterest provides, with no extra compression.', 'pinsdownload' ) ),
);
?>
<section class="pd-section pd-container pd-faq" id="faq">
	<h2><?php esc_html_e( 'Frequently Asked Questions', 'pinsdownload' ); ?></h2>
	<?php foreach ( $faq as $pair ) : ?>
		<details>
			<summary><?php echo esc_html( $pair[0] ); ?></summary>
			<p><?php echo esc_html( $pair[1] ); ?></p>
		</details>
	<?php endforeach; ?>
</section>

<!-- 22. Quick Answers -->
<section class="pd-section pd-container pd-bg-soft">
	<h2><?php esc_html_e( 'Quick Answers', 'pinsdownload' ); ?></h2>
	<p><strong><?php esc_html_e( 'Can I download Pinterest GIFs?', 'pinsdownload' ); ?></strong> <?php esc_html_e( "Yes, paste the GIF's link the same way as a video.", 'pinsdownload' ); ?></p>
	<p><strong><?php esc_html_e( 'Where do my downloads go?', 'pinsdownload' ); ?></strong> <?php esc_html_e( "Your device's default Downloads folder, unless you choose another location.", 'pinsdownload' ); ?></p>
	<p><strong><?php esc_html_e( 'Does this cost anything?', 'pinsdownload' ); ?></strong> <?php esc_html_e( "No, it's free with no limits on single downloads.", 'pinsdownload' ); ?></p>
</section>

<!-- 23. Other Tools (hidden until landing pages exist) -->
<?php
$landing_pages = get_posts(
	array(
		'post_type'      => 'page',
		'posts_per_page' => 20,
		'post_status'    => 'publish',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-templates/template-tool-landing.php',
	)
);
if ( $landing_pages ) :
	?>
	<section class="pd-section pd-container">
		<h2><?php esc_html_e( 'Other Tools', 'pinsdownload' ); ?></h2>
		<p>
			<?php
			$links = array();
			foreach ( $landing_pages as $lp ) {
				$links[] = '<a href="' . esc_url( get_permalink( $lp ) ) . '">' . esc_html( get_the_title( $lp ) ) . '</a>';
			}
			echo wp_kses_post( implode( ' · ', $links ) );
			?>
		</p>
	</section>
<?php endif; ?>

<?php
pinsdownload_output_softwareapplication_schema();
pinsdownload_output_howto_schema(
	array(
		__( 'Open Pinterest and find the video you want.', 'pinsdownload' ),
		__( 'Tap the share icon and choose "Copy Link."', 'pinsdownload' ),
		__( 'Paste the link above and tap Download.', 'pinsdownload' ),
	)
);
pinsdownload_output_faqpage_schema( $faq );

get_footer();
