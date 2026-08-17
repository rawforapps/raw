<?php
/**
 * Structured data + breadcrumb helpers, reused across the homepage and
 * every Tool Landing Page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pinsdownload_breadcrumb() {
	if ( is_front_page() ) {
		return;
	}
	?>
	<nav class="pd-breadcrumb pd-container" aria-label="<?php esc_attr_e( 'Breadcrumb', 'pinsdownload' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'pinsdownload' ); ?></a>
		<span aria-hidden="true"> / </span>
		<span><?php the_title(); ?></span>
	</nav>
	<?php
}

function pinsdownload_output_breadcrumb_schema() {
	if ( is_front_page() ) {
		return;
	}
	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		),
		array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => get_the_title(),
			'item'     => get_permalink(),
		),
	);

	pinsdownload_print_schema(
		array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		)
	);
}

function pinsdownload_output_softwareapplication_schema() {
	pinsdownload_print_schema(
		array(
			'@context'      => 'https://schema.org',
			'@type'         => 'SoftwareApplication',
			'name'          => get_bloginfo( 'name' ),
			'applicationCategory' => 'MultimediaApplication',
			'operatingSystem' => 'Any (web browser)',
			'url'           => home_url( '/' ),
			'offers'        => array(
				'@type' => 'Offer',
				'price' => '0',
				'priceCurrency' => 'USD',
			),
		)
	);
}

function pinsdownload_output_howto_schema( $steps ) {
	$list = array();
	foreach ( $steps as $i => $text ) {
		$list[] = array(
			'@type'    => 'HowToStep',
			'position' => $i + 1,
			'text'     => $text,
		);
	}
	pinsdownload_print_schema(
		array(
			'@context' => 'https://schema.org',
			'@type'    => 'HowTo',
			'name'     => __( 'How to Download a Pinterest Video', 'pinsdownload' ),
			'step'     => $list,
		)
	);
}

function pinsdownload_output_faqpage_schema( $qa_pairs ) {
	$entities = array();
	foreach ( $qa_pairs as $pair ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $pair[0],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $pair[1],
			),
		);
	}
	pinsdownload_print_schema(
		array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		)
	);
}

function pinsdownload_print_schema( $data ) {
	echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Parses the meta box's "Q: ... / A: ..." textarea format into
 * [ [question, answer], ... ] pairs, ready for both display and
 * pinsdownload_output_faqpage_schema().
 */
function pinsdownload_parse_faq_raw( $raw ) {
	$pairs = array();
	if ( ! $raw ) {
		return $pairs;
	}

	$lines      = preg_split( '/\r\n|\r|\n/', trim( $raw ) );
	$question   = null;
	$answer_bits = array();

	$flush = function () use ( &$question, &$answer_bits, &$pairs ) {
		if ( null !== $question ) {
			$pairs[] = array( $question, trim( implode( ' ', $answer_bits ) ) );
		}
	};

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( 0 === stripos( $line, 'Q:' ) ) {
			$flush();
			$question    = trim( substr( $line, 2 ) );
			$answer_bits = array();
		} elseif ( 0 === stripos( $line, 'A:' ) ) {
			$answer_bits[] = trim( substr( $line, 2 ) );
		} elseif ( null !== $question ) {
			$answer_bits[] = $line;
		}
	}
	$flush();

	return $pairs;
}
