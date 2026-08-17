/**
 * PinsDownload service worker.
 *
 * Caches only the UI shell (home page document, theme CSS/JS, icons) so
 * the app opens instantly and works offline for browsing. Actual
 * Pinterest fetches and downloads always require a live connection and
 * are never cached here, that would be pointless (media URLs expire)
 * and would risk serving stale content.
 */

var CACHE_NAME = 'pinsdownload-shell-v1';
var SHELL_URLS = [ '/' ];

self.addEventListener( 'install', function ( event ) {
	event.waitUntil(
		caches.open( CACHE_NAME ).then( function ( cache ) {
			return cache.addAll( SHELL_URLS ).catch( function () {
				// Best-effort: don't fail install if one shell URL 404s
				// on a fresh install with no content yet.
			} );
		} )
	);
	self.skipWaiting();
} );

self.addEventListener( 'activate', function ( event ) {
	event.waitUntil(
		caches.keys().then( function ( keys ) {
			return Promise.all(
				keys
					.filter( function ( key ) {
						return key !== CACHE_NAME;
					} )
					.map( function ( key ) {
						return caches.delete( key );
					} )
			);
		} )
	);
	self.clients.claim();
} );

self.addEventListener( 'fetch', function ( event ) {
	var url = new URL( event.request.url );

	// Never touch API calls, streamed downloads, or cross-origin
	// requests (Pinterest's own CDN). Only the UI shell is cacheable.
	if (
		event.request.method !== 'GET' ||
		url.origin !== self.location.origin ||
		url.pathname.indexOf( '/wp-json/' ) === 0 ||
		url.searchParams.has( 'pinsdownload_stream' )
	) {
		return;
	}

	event.respondWith(
		caches.match( event.request ).then( function ( cached ) {
			var network = fetch( event.request )
				.then( function ( response ) {
					if ( response && response.ok ) {
						var clone = response.clone();
						caches.open( CACHE_NAME ).then( function ( cache ) {
							cache.put( event.request, clone );
						} );
					}
					return response;
				} )
				.catch( function () {
					return cached;
				} );
			return cached || network;
		} )
	);
} );
