<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * PinsDownload — Editable Homepage Sections
 * WPCode PHP Snippet (or paste into an mu-plugin / regular plugin file)
 *
 * Pairs with front-page.php. NOT a separate admin screen — the
 * content lives directly on your existing homepage Page (the one
 * assigned under Settings -> Reading -> "Your homepage displays",
 * the same one you already open to edit), so there's nowhere new to
 * go hunt for it. Structure that Page's content in the normal block
 * editor using Heading (H2) markers, in any order, with whatever
 * content you want underneath each one. Every marker below is
 * editable — nothing on the homepage is restricted to a handful of
 * sections anymore, only the Header, the Hero heading/tool, and the
 * Footer stay fixed (see "what's still fixed" at the bottom of this
 * comment, and why):
 *
 *   ## Feature Strip
 *   ## Quick Steps
 *   ## How to Use
 *   ## Images
 *   ## Works and Doesnt
 *   ## Features
 *   ## Content Types
 *   ## Explanations
 *   ## Comparison
 *   ## Devices
 *   ## Safety
 *   ## Trust Badges
 *   ## Testimonials
 *   ## Whats New
 *   ## Guides
 *   ## FAQ
 *   ## Quick Answers
 *   ## Other Tools
 *   ## Final CTA
 *
 * front-page.php reads that one Page's content, splits it at these
 * marker headings, and drops each chunk into its own already-styled
 * section — so the surrounding design/spacing/background never
 * moves, only what's written under each marker is editable. The
 * marker headings themselves are never displayed (they exist only to
 * tell this code where one zone ends and the next begins) —
 * front-page.php prints its own visible heading for each section.
 *
 * First time this runs, if your homepage Page is completely empty
 * (as WordPress creates it by default), it fills in every marker
 * with the current homepage copy as a starting point, so you're
 * editing real content on day one instead of a blank page. It will
 * NEVER overwrite content again after that first fill — safe to
 * clear a section on purpose.
 *
 * What's still fixed in front-page.php, and why: the site header,
 * the Hero's eyebrow/H1/subtitle, the downloader tool itself
 * (id="pdl-tool" — it's a working form + PHP logic, not text
 * content), the FAQ accordion's open/close mechanics (its Q&A text
 * IS editable, via the FAQ zone above), the final CTA's
 * scroll-to-tool button, and the footer. Those are either
 * functionality or the one H1 a page should have, not copy that
 * needs regular editing.
 */

/* Maps the slug front-page.php passes in to the exact H2 marker text
   readers type on the homepage Page. */
function pd_zone_map() {
	return array(
		'pd-feature-strip'  => 'Feature Strip',
		'pd-quick-steps'    => 'Quick Steps',
		'pd-how-to-use'     => 'How to Use',
		'pd-images'         => 'Images',
		'pd-works-doesnt'   => 'Works and Doesnt',
		'pd-features'       => 'Features',
		'pd-content-types'  => 'Content Types',
		'pd-explanations'   => 'Explanations',
		'pd-comparison'     => 'Comparison',
		'pd-devices'        => 'Devices',
		'pd-safety'         => 'Safety',
		'pd-trust-badges'   => 'Trust Badges',
		'pd-testimonials'   => 'Testimonials',
		'pd-whats-new'      => 'Whats New',
		'pd-guides'         => 'Guides',
		'pd-faq'            => 'FAQ',
		'pd-quick-answers'  => 'Quick Answers',
		'pd-other-tools'    => 'Other Tools',
		'pd-final-cta'      => 'Final CTA',
	);
}

function pd_zone_marker_text( $slug ) {
	$map = pd_zone_map();
	return isset( $map[ $slug ] ) ? $map[ $slug ] : false;
}

function pd_all_zone_markers() {
	return array_values( pd_zone_map() );
}

/* =========================================================
   FIND THE HOMEPAGE PAGE
========================================================= */

function pd_get_front_page_post() {
	$front_id = (int) get_option( 'page_on_front' );
	if ( ! $front_id ) {
		return null;
	}
	$post = get_post( $front_id );
	return ( $post && $post->post_status !== 'trash' ) ? $post : null;
}

/* =========================================================
   SPLIT THE PAGE'S CONTENT AT THE H2 MARKERS
========================================================= */

/**
 * Returns the parsed blocks that sit under the given zone's H2
 * marker heading, up to (not including) the next marker heading or
 * the end of the content. The marker heading block itself is never
 * included in the result — front-page.php supplies its own visible
 * heading for each section.
 */
function pd_get_zone_blocks( $slug ) {
	$marker = pd_zone_marker_text( $slug );
	$post   = pd_get_front_page_post();

	if ( ! $marker || ! $post || trim( $post->post_content ) === '' ) {
		return array();
	}

	$blocks     = parse_blocks( $post->post_content );
	$markers    = pd_all_zone_markers();
	$collecting = false;
	$out        = array();

	foreach ( $blocks as $block ) {
		if ( ! empty( $block['blockName'] ) && $block['blockName'] === 'core/heading' ) {
			$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
			$text  = trim( wp_strip_all_tags( render_block( $block ) ) );

			if ( $level === 2 ) {
				$is_this_marker  = ( strcasecmp( $text, $marker ) === 0 );
				$is_other_marker = false;
				foreach ( $markers as $m ) {
					if ( strcasecmp( $text, $m ) === 0 && strcasecmp( $text, $marker ) !== 0 ) {
						$is_other_marker = true;
						break;
					}
				}

				if ( $is_this_marker ) {
					$collecting = true;
					continue;
				}
				if ( $is_other_marker ) {
					if ( $collecting ) {
						break;
					}
					continue;
				}
			}
		}

		if ( $collecting ) {
			$out[] = $block;
		}
	}

	return $out;
}

/* =========================================================
   RENDER HELPERS — called from front-page.php
========================================================= */

function pd_zone_missing_notice( $slug, $extra = '' ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$front_id = (int) get_option( 'page_on_front' );
	if ( ! $front_id ) {
		echo '<p class="pd-zone-missing">No static homepage is set. In wp-admin go to <strong>Settings &rarr; Reading</strong> and set "Your homepage displays" to a static page, then reload.</p>';
		return;
	}
	$marker   = pd_zone_marker_text( $slug );
	$edit_url = get_edit_post_link( $front_id, 'raw' );
	echo '<p class="pd-zone-missing">Nothing here yet. Edit <a href="' . esc_url( $edit_url ) . '">your homepage page</a> and add a Heading block reading exactly "' . esc_html( $marker ) . '", then put content underneath it.' . ( $extra ? ' ' . esc_html( $extra ) : '' ) . ' (Only visible to logged-in editors.)</p>';
}

/**
 * Renders a zone's Gutenberg content as-is (headings, paragraphs,
 * images, galleries, columns, tables...) wrapped in a class
 * front-page.php's CSS styles to match the rest of the design.
 */
function pd_render_content_zone( $slug ) {
	$blocks = pd_get_zone_blocks( $slug );

	if ( empty( $blocks ) ) {
		pd_zone_missing_notice( $slug );
		return;
	}

	echo '<div class="pd-gutenberg-zone">';
	foreach ( $blocks as $block ) {
		echo render_block( $block ); // phpcs:ignore -- core block rendering, handles its own escaping/kses.
	}
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
	$blocks    = pd_get_zone_blocks( $slug );
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
 * front-page.php doesn't need to know where content came from.
 */
function pd_render_faq_zone( $slug = 'pd-faq' ) {
	$pairs = pd_get_faq_pairs( $slug );

	if ( empty( $pairs ) ) {
		pd_zone_missing_notice( $slug, 'Under it, add a Heading block (the question) followed by a Paragraph block (the answer), repeated for each item.' );
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

/* =========================================================
   SEED THE HOMEPAGE PAGE — once, only while it's still empty.
========================================================= */

add_action( 'init', 'pd_maybe_seed_front_page_content', 20 );

function pd_maybe_seed_front_page_content() {
	$post = pd_get_front_page_post();
	if ( ! $post ) {
		return;
	}
	if ( trim( $post->post_content ) !== '' ) {
		return;
	}
	if ( get_post_meta( $post->ID, '_pd_seeded', true ) ) {
		return;
	}

	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => pd_default_homepage_markup(),
		)
	);
	update_post_meta( $post->ID, '_pd_seeded', 1 );
}

function pd_default_homepage_markup() {

	$h2 = function ( $text ) {
		return "<!-- wp:heading -->\n<h2>" . $text . "</h2>\n<!-- /wp:heading -->\n\n";
	};
	$b3 = function ( $text ) {
		return "<!-- wp:heading {\"level\":3} -->\n<h3>" . $text . "</h3>\n<!-- /wp:heading -->\n\n";
	};
	$p = function ( $text ) {
		return "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->\n\n";
	};
	$list = function ( $items, $ordered = false ) {
		$attrs = $ordered ? ' {"ordered":true}' : '';
		$tag   = $ordered ? 'ol' : 'ul';
		$html  = "<!-- wp:list$attrs -->\n<$tag>";
		foreach ( $items as $item ) {
			$html .= '<li>' . $item . '</li>';
		}
		$html .= "</$tag>\n<!-- /wp:list -->\n\n";
		return $html;
	};
	$image_hint = function () {
		return "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>Add an Image block here for the screenshot (use the block inserter's \"+\").</em></p>\n<!-- /wp:paragraph -->\n\n";
	};
	$col = function ( $title, $text ) {
		return "<!-- wp:column -->\n<div class=\"wp-block-column\">\n<!-- wp:heading {\"level\":3} -->\n<h3>$title</h3>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>$text</p>\n<!-- /wp:paragraph -->\n</div>\n<!-- /wp:column -->\n\n";
	};
	$columns_open  = "<!-- wp:columns -->\n<div class=\"wp-block-columns\">\n";
	$columns_close = "</div>\n<!-- /wp:columns -->\n\n";

	$out = '';

	/* ---------- Feature Strip ---------- */
	$out .= $h2( 'Feature Strip' );
	$out .= $p( 'HD &middot; 2K &middot; 4K quality &middot; No watermark &middot; MP4, JPG, PNG, GIF supported &middot; Works on phone, tablet, and computer' );

	/* ---------- Quick Steps ---------- */
	$out .= $h2( 'Quick Steps' );
	$out .= $columns_open;
	$out .= $col( 'Open Pinterest', 'Open Pinterest and find the video you want.' );
	$out .= $col( 'Copy Link', 'Tap the share icon and choose &quot;Copy Link.&quot;' );
	$out .= $col( 'Paste &amp; Download', 'Paste the link above and tap Download.' );
	$out .= $columns_close;
	$out .= $p( 'Your video saves straight to your device. No app, no sign up.' );
	$out .= $image_hint();

	/* ---------- How to Use ---------- */
	$out .= $h2( 'How to Use' );
	$out .= $b3( 'Downloading From the Pinterest App' );
	$out .= $p( 'You can download Pinterest videos straight from the Pinterest app without leaving it open. Copy the link, come back here, and paste it.' );
	$out .= $list( array(
		'Open the Pinterest app and find your pin.',
		'Tap the three dots (&bull;&bull;&bull;) on the pin.',
		'Tap Copy Link.',
		'Come back here, paste the link, and tap Download.',
		'Your file saves to your Photos or Downloads folder.',
	), true );
	$out .= $image_hint();
	$out .= $b3( 'Downloading on a Computer' );
	$out .= $p( 'You can also download Pinterest videos on a computer, using any browser.' );
	$out .= $list( array(
		'Open Pinterest.com in your browser.',
		'Click the pin, then copy the link from your address bar.',
		'Paste it above and click Download.',
		"The file lands in your computer's Downloads folder.",
	), true );
	$out .= $image_hint();
	$out .= $b3( 'How to Download Pinterest Videos on iPhone' );
	$out .= $p( "Yes, PinsDownload works on iPhone. You don't need an app, just Safari and a Pinterest link." );
	$out .= $list( array(
		'Open the Pinterest app on your iPhone.',
		'Tap the share icon on the video, then Copy Link.',
		'Open Safari and go to pinsdownload.org.',
		'Paste the link and tap Download.',
		'Save the video to your Photos app when it finishes.',
	), true );
	$out .= $image_hint();
	$out .= $b3( 'How to Download Pinterest Videos on Android' );
	$out .= $p( 'Yes, PinsDownload works on Android too, right inside Chrome.' );
	$out .= $list( array(
		'Open the Pinterest app and find your video.',
		'Tap Share, then Copy Link.',
		'Open Chrome and visit pinsdownload.org.',
		'Paste the link and tap Download.',
		'The video saves to your Gallery or Downloads folder.',
	), true );
	$out .= $image_hint();

	/* ---------- Images ---------- */
	$out .= $h2( 'Images' );
	$out .= $p( 'Add screenshots or product photos below with the Image or Gallery block.' );
	$out .= "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>Click the \"+\" inserter and add an Image or Gallery block.</em></p>\n<!-- /wp:paragraph -->\n\n";

	/* ---------- Works and Doesnt ---------- */
	$out .= $h2( 'Works and Doesnt' );
	$out .= $b3( 'What Works' );
	$out .= $list( array(
		'Public pins and pin.it links',
		'Videos, images, GIFs, stories, carousels',
		'Public boards and profiles',
		'Idea Pins and Ideas pages',
	) );
	$out .= $b3( "What Doesn't Work" );
	$out .= $list( array(
		'Private or login-only pins',
		'Deleted or removed pins',
		'Invitation-only boards',
		"Content you don't have rights to save",
	) );

	/* ---------- Features ---------- */
	$out .= $h2( 'Features' );
	$out .= $columns_open;
	$out .= $col( 'Free, always.', 'No hidden charges, no daily limit.' );
	$out .= $col( 'No watermark.', 'Your download looks exactly like the original.' );
	$out .= $col( 'No login.', 'We never ask for your Pinterest password.' );
	$out .= $columns_close;
	$out .= $columns_open;
	$out .= $col( 'Original quality.', 'Videos and images save in the same resolution Pinterest gives us.' );
	$out .= $col( 'Works everywhere.', 'Phone, tablet, or computer, any browser.' );
	$out .= $col( 'Honest about ads.', 'A small number of ads keep this tool free. They never sit on top of or look like the Download button.' );
	$out .= $columns_close;

	/* ---------- Content Types ---------- */
	$out .= $h2( 'Content Types' );
	$out .= $list( array(
		'<strong>Images and photos</strong> &mdash; save any pin in full resolution.',
		'<strong>GIFs</strong> &mdash; download animated pins without losing the loop.',
		"<strong>Reels and short videos</strong> &mdash; grab Pinterest's short-form clips.",
		'<strong>Stories and Idea Pins</strong> &mdash; save every slide of a multi-page pin.',
		'<strong>Carousels</strong> &mdash; download every image or video in a multi-item pin.',
		'<strong>Boards</strong> &mdash; save up to 100 pins from a public board at once, or grab the whole thing as a ZIP file.',
		'<strong>Profiles</strong> &mdash; download every public pin from a Pinterest profile.',
		'<strong>Ideas pages</strong> &mdash; save content straight from a Pinterest Ideas collection.',
		'<strong>Answers pages</strong> &mdash; download pins shared on a Pinterest Answers page.',
		'<strong>Shared pin links</strong> &mdash; paste any multi-pin share link and download everything in it.',
	) );

	/* ---------- Explanations ---------- */
	$out .= $h2( 'Explanations' );
	$out .= $b3( 'What Is a Pinterest Video Downloader?' );
	$out .= $p( "A Pinterest video downloader is a free online tool that saves Pinterest videos, images, and GIFs to your device. Pinterest doesn't let you download videos directly from its app or website, so this tool reads the pin's link and gives you a direct file to save. You don't need an account, and nothing is stored on our end after your download finishes." );
	$out .= $b3( 'What People Use It For' );
	$out .= $p( 'People download Pinterest content for all kinds of projects: home d&eacute;cor ideas, recipes and food photography, fashion inspiration, DIY and craft projects, wedding planning, travel photos, fitness routines, study notes and aesthetics, art references, and mood boards.' );
	$out .= $b3( 'Is It Legal to Download Pinterest Videos?' );
	$out .= $p( "Downloading a Pinterest video for personal, offline use is generally fine. Reposting, selling, or reusing someone else's video without permission is not. Pinterest content belongs to the person who posted it, so always ask before using it publicly." );

	/* ---------- Comparison ---------- */
	$out .= $h2( 'Comparison' );
	$rows = array(
		array( '', 'PinsDownload', 'Typical Free Downloaders', 'Downloader Apps' ),
		array( 'Quality', 'Up to 4K', 'Often capped at 720p', 'Sometimes compressed' ),
		array( 'Watermark', 'None', 'Usually none', 'Often adds app logo' ),
		array( 'Login needed', 'No', 'No', 'Often yes' ),
		array( 'Speed', 'Seconds', 'Slow, ad-heavy', 'Medium' ),
		array( 'Bulk/ZIP download', 'Yes, up to 100 pins', 'Rare', 'Rare' ),
		array( 'Privacy', 'Nothing stored', 'Varies', 'Often collects data' ),
	);
	$table  = '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr>';
	foreach ( $rows[0] as $cell ) {
		$table .= '<th>' . $cell . '</th>';
	}
	$table .= '</tr></thead><tbody>';
	for ( $i = 1; $i < count( $rows ); $i++ ) {
		$table .= '<tr>';
		foreach ( $rows[ $i ] as $cell ) {
			$table .= '<td>' . $cell . '</td>';
		}
		$table .= '</tr>';
	}
	$table .= "</tbody></table></figure>\n<!-- /wp:table -->\n\n";
	$out   .= $table;
	$out   .= "<!-- wp:paragraph -->\n<p><em>REVIEW REQUIRED — section marked DUMMY in the source copy. Every row must be true and tested before this goes live.</em></p>\n<!-- /wp:paragraph -->\n\n";

	/* ---------- Devices ---------- */
	$out .= $h2( 'Devices' );
	$out .= $list( array(
		'<strong>Android</strong> &mdash; Chrome, Firefox',
		'<strong>iPhone / iPad</strong> &mdash; Safari, Chrome',
		'<strong>Windows</strong> &mdash; Chrome, Edge',
		'<strong>Mac</strong> &mdash; Safari, Chrome',
		'<strong>Linux</strong> &mdash; Firefox, Chrome',
	) );

	/* ---------- Safety ---------- */
	$out .= $h2( 'Safety' );
	$out .= $p( 'Yes. We never ask for your Pinterest username or password. You paste a public link, we fetch the file, and nothing you download is stored on our servers afterward. We use standard analytics to see which pages are useful, the same as most websites, but your download history stays private.' );

	/* ---------- Trust Badges ---------- */
	$out .= $h2( 'Trust Badges' );
	$out .= $p( 'Links already point at the real domain. They will show &quot;no data yet&quot; until pinsdownload.org has been live and crawled for a few weeks &mdash; that is expected, not a bug.' );
	$out .= $list( array(
		'<a href="https://transparencyreport.google.com/safe-browsing/search?url=pinsdownload.org">Google Safe Browsing</a> &mdash; verification pending',
		'<a href="https://safeweb.norton.com/report?url=pinsdownload.org">Norton Safe Web</a> &mdash; verification pending',
		'<a href="https://sitecheck.sucuri.net/results/pinsdownload.org">Sucuri Scanner</a> &mdash; verification pending',
	) );

	/* ---------- Testimonials — left empty on purpose, no fake reviews ---------- */
	$out .= $h2( 'Testimonials' );
	$out .= "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>No reviews published yet. Add real ones as Paragraph or Quote blocks once you have them &mdash; never fabricate names, ratings, or quotes here.</em></p>\n<!-- /wp:paragraph -->\n\n";

	/* ---------- Whats New ---------- */
	$out .= $h2( 'Whats New' );
	$out .= $p( '<strong>Aug 2026 &mdash; Launched:</strong> PinsDownload is live, with video, image, GIF, story, carousel, board, and profile downloads all working from day one.' );

	/* ---------- Guides ---------- */
	$out .= $h2( 'Guides' );
	$out .= $list( array(
		'How to Download a Full Pinterest Board (guide not published yet)',
		'Is Downloading Pinterest Content Legal? (guide not published yet)',
		'PinsDownload vs Other Downloaders (guide not published yet)',
	) );

	/* ---------- FAQ ---------- */
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
	$out .= $h2( 'FAQ' );
	foreach ( $faq_pairs as $pair ) {
		$out .= $b3( $pair[0] );
		$out .= $p( $pair[1] );
	}

	/* ---------- Quick Answers ---------- */
	$out .= $h2( 'Quick Answers' );
	$qa_pairs = array(
		array( 'Can I download Pinterest GIFs?', "Yes, paste the GIF's link the same way as a video." ),
		array( 'Where do my downloads go?', "Your device's default Downloads folder, unless you choose another location." ),
		array( 'Does this cost anything?', "No, it's free with no limits on single downloads." ),
		array( 'Is this the same as a "pin saver"?', 'Yes. PinsDownload works as a Pinterest saver too, paste any pin link and save it the same way.' ),
	);
	foreach ( $qa_pairs as $pair ) {
		$out .= $b3( $pair[0] );
		$out .= $p( $pair[1] );
	}

	/* ---------- Other Tools ---------- */
	$out .= $h2( 'Other Tools' );
	$out .= $list( array(
		'Pinterest Video Downloader (you&rsquo;re here)',
		'Pinterest Image Downloader (page not published yet)',
		'Pinterest GIF Downloader (page not published yet)',
		'Pinterest Story Downloader (page not published yet)',
		'Pinterest Board Downloader (page not published yet)',
	) );

	/* ---------- Final CTA ---------- */
	$out .= $h2( 'Final CTA' );
	$out .= $p( 'Paste a Pinterest link above and get your file in seconds.' );

	return $out;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
} );
