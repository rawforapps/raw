<?php
/**
 * Reusable "What This Tool Can and Can't Download" table.
 * Used on the homepage and on every Tool Landing Page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="pd-section pd-works-doesnt">
	<div class="pd-container">
		<h2><?php esc_html_e( 'What This Tool Can and Can\'t Download', 'pinsdownload' ); ?></h2>
		<p><?php esc_html_e( 'PinsDownload works with any public Pinterest link. It can\'t open anything that needs a Pinterest login.', 'pinsdownload' ); ?></p>
		<div class="pd-table">
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Works', 'pinsdownload' ); ?></th>
						<th><?php esc_html_e( "Doesn't Work", 'pinsdownload' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Public pins and pin.it links', 'pinsdownload' ); ?></td>
						<td><?php esc_html_e( 'Private or login-only pins', 'pinsdownload' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Videos, images, GIFs, stories, carousels', 'pinsdownload' ); ?></td>
						<td><?php esc_html_e( 'Deleted or removed pins', 'pinsdownload' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Public boards and profiles', 'pinsdownload' ); ?></td>
						<td><?php esc_html_e( 'Invitation-only boards', 'pinsdownload' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Idea Pins and Ideas pages', 'pinsdownload' ); ?></td>
						<td><?php esc_html_e( "Content you don't have rights to save", 'pinsdownload' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</section>
