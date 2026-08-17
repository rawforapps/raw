/**
 * Register the service worker at the site root so it can control the
 * whole site (see inc/pwa-root-files.php for why root matters here).
 */
if ( 'serviceWorker' in navigator ) {
	window.addEventListener( 'load', function () {
		navigator.serviceWorker.register( '/sw.js', { scope: '/' } ).catch( function () {
			// Non-fatal: the tool works fully without the service worker,
			// this only affects offline shell caching and installability.
		} );
	} );
}
