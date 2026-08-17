/**
 * PinsDownload mood board.
 *
 * A basic, login-free collection feature: any downloaded image/thumbnail
 * can be saved into a local mood board, stored entirely in the visitor's
 * own browser (localStorage, no account, no server database, matching
 * the tool's no-login/no-storage design). The [pinsdownload_moodboard]
 * page renders the saved grid and offers a printable/exportable layout.
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'pd_moodboard_items';
	var MAX_ITEMS = 200;

	function readAll() {
		try {
			var raw = localStorage.getItem( STORAGE_KEY );
			var items = raw ? JSON.parse( raw ) : [];
			return Array.isArray( items ) ? items : [];
		} catch ( e ) {
			return [];
		}
	}

	function writeAll( items ) {
		try {
			localStorage.setItem( STORAGE_KEY, JSON.stringify( items.slice( 0, MAX_ITEMS ) ) );
		} catch ( e ) {
			// Storage full or unavailable (private browsing); fail silently,
			// the mood board is a bonus feature, not core functionality.
		}
	}

	function add( item ) {
		if ( ! item || ! item.image ) {
			return;
		}
		var items = readAll();
		var id = item.id || ( item.image + '|' + ( item.title || '' ) );
		if ( items.some( function ( i ) { return i.id === id; } ) ) {
			return;
		}
		items.unshift( {
			id: id,
			image: item.image,
			title: item.title || '',
			addedAt: Date.now(),
		} );
		writeAll( items );
		renderAllGrids();
	}

	function remove( id ) {
		writeAll( readAll().filter( function ( i ) {
			return i.id !== id;
		} ) );
		renderAllGrids();
	}

	function clearAll() {
		writeAll( [] );
		renderAllGrids();
	}

	function renderGrid( container ) {
		var items = readAll();
		container.innerHTML = '';

		if ( ! items.length ) {
			var empty = document.createElement( 'p' );
			empty.className = 'pd-moodboard-empty';
			empty.textContent = container.dataset.emptyText || 'Nothing saved yet. Download something and choose "Save to Moodboard."';
			container.appendChild( empty );
			return;
		}

		items.forEach( function ( item ) {
			var cell = document.createElement( 'div' );
			cell.className = 'pd-moodboard-item';

			var img = document.createElement( 'img' );
			img.src = item.image;
			img.alt = item.title;
			img.loading = 'lazy';
			cell.appendChild( img );

			var removeBtn = document.createElement( 'button' );
			removeBtn.type = 'button';
			removeBtn.className = 'pd-moodboard-item__remove';
			removeBtn.setAttribute( 'aria-label', 'Remove from moodboard' );
			removeBtn.innerHTML = '<svg class="pd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>';
			removeBtn.addEventListener( 'click', function () {
				remove( item.id );
			} );
			cell.appendChild( removeBtn );

			container.appendChild( cell );
		} );
	}

	function renderAllGrids() {
		document.querySelectorAll( '.pd-moodboard-grid' ).forEach( renderGrid );
	}

	window.PinsDownloadMoodboard = {
		add: add,
		remove: remove,
		clearAll: clearAll,
		getAll: readAll,
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		renderAllGrids();

		document.querySelectorAll( '.pd-moodboard-clear' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				if ( window.confirm( 'Remove everything from your moodboard?' ) ) {
					clearAll();
				}
			} );
		} );

		document.querySelectorAll( '.pd-moodboard-print' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				window.print();
			} );
		} );
	} );
} )();
