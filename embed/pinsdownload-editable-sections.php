<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * PinsDownload — Editable Homepage Sections
 * WPCode PHP Snippet (or paste into an mu-plugin / regular plugin file)
 *
 * Pairs with front-page.php. Registers a small, non-public custom
 * post type ("Homepage Sections") that shows up in wp-admin with the
 * normal Gutenberg block editor — full support for images, headings,
 * paragraphs, lists, galleries, columns. front-page.php pulls each
 * section's content in by a fixed slug and renders it inside its own
 * already-styled section wrapper, so the surrounding design/spacing/
 * background never moves — only what's INSIDE each zone is editable.
 *
 * Five zones are created (once, automatically, the first time this
 * runs) and pre-filled with the current homepage copy as a starting
 * point:
 *   pd-how-to-use    -> "How to Use" (App / Computer / iPhone / Android guides)
 *   pd-images        -> "Images" (empty gallery area, add screenshots here)
 *   pd-explanations  -> "Explanations" (what is it / what it's for / legal)
 *   pd-features      -> "Features" (the 6-card "why people use it" grid)
 *   pd-faq           -> "FAQ" (Heading = question, Paragraph = answer, repeated)
 *
 * Everything else on the homepage (hero + tool, feature strip, works/
 * doesn't, comparison table, devices, trust badges, testimonials,
 * timeline, quick answers, other tools, final CTA) stays hard-coded
 * in front-page.php on purpose — those are structured, styled
 * components (accordions, card grids, tables) where free-form
 * editing would risk breaking the layout, not places that need
 * frequent copy edits.
 */

/* =========================================================
   REGISTER THE POST TYPE
========================================================= */

add_action( 'init', 'pd_register_section_post_type' );

function pd_register_section_post_type() {
	register_post_type(
		'pd_section',
		array(
			'label'        => __( 'Homepage Sections', 'pinsdownload' ),
			'labels'       => array(
				'name'          => __( 'Homepage Sections', 'pinsdownload' ),
				'singular_name' => __( 'Homepage Section', 'pinsdownload' ),
				'edit_item'     => __( 'Edit Homepage Section', 'pinsdownload' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-layout',
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'has_archive'  => false,
			'rewrite'      => false,
			'map_meta_cap' => true,
		)
	);
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
} );

/* =========================================================
   SEED DEFAULT CONTENT — runs once, ever. If you delete a
   section on purpose afterward, it will NOT come back.
========================================================= */

add_action( 'init', 'pd_seed_default_sections', 20 );

function pd_seed_default_sections() {

	if ( get_option( 'pd_sections_seeded' ) ) {
		return;
	}

	$sections = pd_default_section_seeds();

	foreach ( $sections as $slug => $data ) {
		$existing = get_page_by_path( $slug, OBJECT, 'pd_section' );
		if ( $existing ) {
			continue;
		}

		wp_insert_post(
			array(
				'post_type'    => 'pd_section',
				'post_title'   => $data['title'],
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_content' => $data['content'],
			)
		);
	}

	update_option( 'pd_sections_seeded', 1 );
}

function pd_default_section_seeds() {

	$b = function ( $level, $text ) {
		return "<!-- wp:heading {\"level\":$level} -->\n<h$level>" . $text . "</h$level>\n<!-- /wp:heading -->\n\n";
	};
	$p = function ( $text ) {
		return "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->\n\n";
	};
	$list = function ( $items ) {
		$html = "<!-- wp:list {\"ordered\":true} -->\n<ol>";
		foreach ( $items as $item ) {
			$html .= '<li>' . $item . '</li>';
		}
		$html .= "</ol>\n<!-- /wp:list -->\n\n";
		return $html;
	};
	$image_hint = function () {
		return "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>Add an Image block here for the screenshot (use the block inserter's \"+\").</em></p>\n<!-- /wp:paragraph -->\n\n";
	};

	$how_to_use  = $b( 3, 'Downloading From the Pinterest App' );
	$how_to_use .= $p( "You can download Pinterest videos straight from the Pinterest app without leaving it open. Copy the link, come back here, and paste it." );
	$how_to_use .= $list( array(
		'Open the Pinterest app and find your pin.',
		'Tap the three dots (&bull;&bull;&bull;) on the pin.',
		'Tap Copy Link.',
		'Come back here, paste the link, and tap Download.',
		'Your file saves to your Photos or Downloads folder.',
	) );
	$how_to_use .= $image_hint();
	$how_to_use .= $b( 3, 'Downloading on a Computer' );
	$how_to_use .= $p( 'You can also download Pinterest videos on a computer, using any browser.' );
	$how_to_use .= $list( array(
		'Open Pinterest.com in your browser.',
		'Click the pin, then copy the link from your address bar.',
		'Paste it above and click Download.',
		"The file lands in your computer's Downloads folder.",
	) );
	$how_to_use .= $image_hint();
	$how_to_use .= $b( 3, 'How to Download Pinterest Videos on iPhone' );
	$how_to_use .= $p( "Yes, PinsDownload works on iPhone. You don't need an app, just Safari and a Pinterest link." );
	$how_to_use .= $list( array(
		'Open the Pinterest app on your iPhone.',
		'Tap the share icon on the video, then Copy Link.',
		'Open Safari and go to pinsdownload.org.',
		'Paste the link and tap Download.',
		'Save the video to your Photos app when it finishes.',
	) );
	$how_to_use .= $image_hint();
	$how_to_use .= $b( 3, 'How to Download Pinterest Videos on Android' );
	$how_to_use .= $p( 'Yes, PinsDownload works on Android too, right inside Chrome.' );
	$how_to_use .= $list( array(
		'Open the Pinterest app and find your video.',
		'Tap Share, then Copy Link.',
		'Open Chrome and visit pinsdownload.org.',
		'Paste the link and tap Download.',
		'The video saves to your Gallery or Downloads folder.',
	) );
	$how_to_use .= $image_hint();

	$images  = $p( 'Add screenshots or product photos below with the Image or Gallery block.' );
	$images .= "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>Click the \"+\" inserter and add an Image or Gallery block.</em></p>\n<!-- /wp:paragraph -->\n\n";

	$explanations  = $b( 3, 'What Is a Pinterest Video Downloader?' );
	$explanations .= $p( "A Pinterest video downloader is a free online tool that saves Pinterest videos, images, and GIFs to your device. Pinterest doesn't let you download videos directly from its app or website, so this tool reads the pin's link and gives you a direct file to save. You don't need an account, and nothing is stored on our end after your download finishes." );
	$explanations .= $b( 3, 'What People Use It For' );
	$explanations .= $p( 'People download Pinterest content for all kinds of projects: home d&eacute;cor ideas, recipes and food photography, fashion inspiration, DIY and craft projects, wedding planning, travel photos, fitness routines, study notes and aesthetics, art references, and mood boards.' );
	$explanations .= $b( 3, 'Is It Legal to Download Pinterest Videos?' );
	$explanations .= $p( "Downloading a Pinterest video for personal, offline use is generally fine. Reposting, selling, or reusing someone else's video without permission is not. Pinterest content belongs to the person who posted it, so always ask before using it publicly." );

	$feature_col = function ( $title, $text ) {
		return "<!-- wp:column -->\n<div class=\"wp-block-column\">\n<!-- wp:heading {\"level\":3} -->\n<h3>$title</h3>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>$text</p>\n<!-- /wp:paragraph -->\n</div>\n<!-- /wp:column -->\n\n";
	};

	$features  = "<!-- wp:columns -->\n<div class=\"wp-block-columns\">\n";
	$features .= $feature_col( 'Free, always.', 'No hidden charges, no daily limit.' );
	$features .= $feature_col( 'No watermark.', 'Your download looks exactly like the original.' );
	$features .= $feature_col( 'No login.', 'We never ask for your Pinterest password.' );
	$features .= "</div>\n<!-- /wp:columns -->\n\n";
	$features .= "<!-- wp:columns -->\n<div class=\"wp-block-columns\">\n";
	$features .= $feature_col( 'Original quality.', 'Videos and images save in the same resolution Pinterest gives us.' );
	$features .= $feature_col( 'Works everywhere.', 'Phone, tablet, or computer, any browser.' );
	$features .= $feature_col( 'Honest about ads.', 'A small number of ads keep this tool free. They never sit on top of or look like the Download button.' );
	$features .= "</div>\n<!-- /wp:columns -->\n\n";

	$faq_pairs = array(
		array( 'Is PinsDownload safe to use?', "Yes. We never ask for your Pinterest login, and we don't store the files you download." ),
		array( 'Is it legal to download Pinterest videos?', "Downloading for personal use is fine. Reposting someone else's work without permission is not." ),
		array( 'Do I need to log in to my Pinterest account?', 'No. PinsDownload only works with public links, so no login is needed.' ),
		array( 'What video and image formats are supported?', 'Videos save as MP4. Images save as JPG or PNG. GIFs keep their original animation.' ),
		array( 'Does this tool save my downloaded content?', 'No. We fetch the file and send it straight to you. Nothing is kept on our servers.' ),
		array( 'Can I download Pinterest videos without a watermark?', 'Yes. Every download matches the original Pinterest file, with no watermark added.' ),
		array( 'Is there a limit on how many videos I can download?', 'No daily limit for single pins. Board and profile downloads are capped at 100 pins per request.' ),
		array( 'Does this work on iPhone and Android?', 'Yes. PinsDownload runs in your browser, so it works on iPhone, Android, and desktop.' ),
		array( 'Can I download a full Pinterest board or profile?', 'Yes. Paste the board or profile link, then choose to download items one by one or all at once as a ZIP.' ),
		array( 'Can I download private or deleted pins?', 'No. PinsDownload only works with public, active pins.' ),
		array( 'What should I do if a download fails?', 'Check that the link is public and still active. If it still fails, try copying the link again from Pinterest.' ),
		array( 'Will the video lose quality after downloading?', 'No. PinsDownload saves the file at the same resolution Pinterest provides, with no extra compression.' ),
	);
	$faq = '';
	foreach ( $faq_pairs as $pair ) {
		$faq .= $b( 3, $pair[0] );
		$faq .= $p( $pair[1] );
	}

	return array(
		'pd-how-to-use'   => array( 'title' => 'How to Use', 'content' => $how_to_use ),
		'pd-images'       => array( 'title' => 'Images', 'content' => $images ),
		'pd-explanations' => array( 'title' => 'Explanations', 'content' => $explanations ),
		'pd-features'     => array( 'title' => 'Features', 'content' => $features ),
		'pd-faq'          => array( 'title' => 'FAQ', 'content' => $faq ),
	);
}

/* =========================================================
   FETCH + RENDER HELPERS — called from front-page.php
========================================================= */

function pd_get_section_post( $slug ) {
	return get_page_by_path( $slug, OBJECT, 'pd_section' );
}

/**
 * Renders a zone's Gutenberg content as-is (headings, paragraphs,
 * images, galleries, columns...) wrapped in a class front-page.php's
 * CSS styles to match the rest of the design.
 */
function pd_render_content_zone( $slug ) {
	$post = pd_get_section_post( $slug );

	if ( ! $post || trim( $post->post_content ) === '' ) {
		if ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="pd-zone-missing">Nothing here yet — edit <strong>Homepage Sections &rarr; ' . esc_html( $slug ) . '</strong> in wp-admin to add content. (Only visible to logged-in editors.)</p>';
		}
		return;
	}

	echo '<div class="pd-gutenberg-zone">';
	echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore -- core filter, handles its own escaping/kses.
	echo '</div>';
}

/**
 * Returns [[question, answer], ...] by reading Heading+Paragraph
 * pairs out of the FAQ zone's blocks (in order: every core/heading
 * starts a new question, the next core/paragraph is its answer).
 * Used both to render the accordion and to build FAQPage schema, so
 * the two can never drift out of sync.
 */
function pd_get_faq_pairs( $slug = 'pd-faq' ) {
	$post = pd_get_section_post( $slug );
	if ( ! $post ) {
		return array();
	}

	$blocks    = parse_blocks( $post->post_content );
	$pairs     = array();
	$pending_q = null;

	foreach ( $blocks as $block ) {
		if ( empty( $block['blockName'] ) ) {
			continue;
		}
		if ( $block['blockName'] === 'core/heading' ) {
			$pending_q = trim( wp_strip_all_tags( render_block( $block ) ) );
		} elseif ( $block['blockName'] === 'core/paragraph' && $pending_q !== null && $pending_q !== '' ) {
			$answer = trim( wp_strip_all_tags( render_block( $block ) ) );
			if ( $answer !== '' ) {
				$pairs[] = array( $pending_q, $answer );
			}
			$pending_q = null;
		}
	}

	return $pairs;
}

/**
 * Renders the FAQ zone using the exact same .pd-faq-item accordion
 * markup as the rest of the design, so the accordion CSS/JS in
 * front-page.php doesn't need to know content came from a CPT.
 */
function pd_render_faq_zone( $slug = 'pd-faq' ) {
	$pairs = pd_get_faq_pairs( $slug );

	if ( empty( $pairs ) ) {
		if ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="pd-zone-missing">No FAQ items found. In wp-admin, edit <strong>Homepage Sections &rarr; FAQ</strong> and add a Heading block (the question) followed by a Paragraph block (the answer), repeated for each item.</p>';
		}
		return;
	}
	?>
	<div class="pd-faq-list" id="pd-faq">
		<?php foreach ( $pairs as $pair ) : ?>
			<div class="pd-faq-item">
				<button type="button" class="pd-faq-q" aria-expanded="false">
					<span><?php echo esc_html( $pair[0] ); ?></span>
					<?php
					if ( function_exists( 'pd_icon' ) ) {
						pd_icon( 'chevron' );
					} else {
						echo '<svg class="pd-i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
					}
					?>
				</button>
				<div class="pd-faq-a"><div><p><?php echo esc_html( $pair[1] ); ?></p></div></div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}
