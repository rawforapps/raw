/**
 * PinsDownload tool frontend.
 *
 * Handles every .pd-tool instance on the page (homepage or a Tool
 * Landing Page) with the same logic: auto-fetch on paste, live progress,
 * a quality selector for single items, checkbox selection + ZIP for
 * bulk collections, and a confirmation state with a repeat-download
 * shortcut. This is the one frontend for every content type; only the
 * server response shape changes what gets rendered.
 */
( function () {
	'use strict';

	var cfg = window.PinsDownloadConfig || {};
	var deferredInstallPrompt = null;

	// Small set of inline SVGs for JS-driven state changes (never a text
	// glyph/emoji — those render inconsistently across platforms and
	// can't be styled with the rest of the icon system's stroke tokens).
	var ICON_SVG = {
		check: '<svg class="pd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>',
	};

	window.addEventListener( 'beforeinstallprompt', function ( e ) {
		e.preventDefault();
		deferredInstallPrompt = e;
	} );

	function maybePromptInstall() {
		if ( ! deferredInstallPrompt ) {
			return;
		}
		if ( localStorage.getItem( 'pd_install_prompted' ) ) {
			return;
		}
		localStorage.setItem( 'pd_install_prompted', '1' );
		deferredInstallPrompt.prompt();
		deferredInstallPrompt = null;
	}

	function isLikelyPinterestUrl( value ) {
		return /pinterest\.[a-z.]+\/|pin\.it\//i.test( value );
	}

	function formatBytes( bytes ) {
		if ( ! bytes ) {
			return '';
		}
		var units = [ 'B', 'KB', 'MB', 'GB' ];
		var i = 0;
		var n = bytes;
		while ( n >= 1024 && i < units.length - 1 ) {
			n = n / 1024;
			i++;
		}
		return n.toFixed( n >= 10 || i === 0 ? 0 : 1 ) + ' ' + units[ i ];
	}

	function guessExtension( mediaType, url ) {
		var m = /\.(mp4|jpg|jpeg|png|gif|webp)(\?|$)/i.exec( url || '' );
		if ( m ) {
			return m[1].toLowerCase();
		}
		if ( 'video' === mediaType ) {
			return 'mp4';
		}
		if ( 'gif' === mediaType ) {
			return 'gif';
		}
		return 'jpg';
	}

	function slugFilename( title, suffix, ext ) {
		var base = ( title || 'pinsdownload' )
			.toString()
			.toLowerCase()
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /(^-|-$)/g, '' )
			.slice( 0, 60 );
		return ( base || 'pinsdownload' ) + ( suffix ? '-' + suffix : '' ) + '.' + ext;
	}

	function streamUrlFor( mediaUrl, filename ) {
		var params = new URLSearchParams();
		params.set( 'pinsdownload_stream', '1' );
		params.set( 'url', mediaUrl );
		params.set( 'filename', filename );
		params.set( '_wpnonce', cfg.streamNonce || '' );
		return cfg.streamBase + '?' + params.toString();
	}

	function triggerDownload( href ) {
		var a = document.createElement( 'a' );
		a.href = href;
		a.rel = 'noopener';
		document.body.appendChild( a );
		a.click();
		a.remove();
	}

	function PdTool( root ) {
		this.root = root;
		this.type = root.dataset.type || 'general';
		this.form = root.querySelector( '.pd-tool__form' );
		this.input = root.querySelector( '.pd-tool__input' );
		this.submit = root.querySelector( '.pd-tool__submit' );
		this.progress = root.querySelector( '.pd-tool__progress' );
		this.progressBar = root.querySelector( '.pd-tool__progress-bar' );
		this.progressLabel = root.querySelector( '.pd-tool__progress-label' );
		this.status = root.querySelector( '.pd-tool__status' );
		this.results = root.querySelector( '.pd-tool__results' );

		this.tplSingle = root.parentNode.querySelector( '.pd-tpl-single' );
		this.tplMultiItem = root.parentNode.querySelector( '.pd-tpl-multi-item' );
		this.tplDone = root.parentNode.querySelector( '.pd-tpl-done' );

		this.progressTimer = null;
		this.lastUrl = '';

		this.bind();
	}

	PdTool.prototype.bind = function () {
		var self = this;

		this.form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			self.start( self.input.value );
		} );

		this.input.addEventListener( 'paste', function () {
			setTimeout( function () {
				var value = self.input.value.trim();
				if ( isLikelyPinterestUrl( value ) ) {
					self.start( value );
				}
			}, 30 );
		} );
	};

	PdTool.prototype.setStatus = function ( message, isError ) {
		this.status.textContent = message || '';
		this.status.classList.toggle( 'pd-tool__status--error', !! isError );
	};

	PdTool.prototype.startProgress = function () {
		var self = this;
		var pct = 0;
		this.progress.hidden = false;
		this.progressBar.style.width = '0%';
		this.progressLabel.textContent = '0%';

		if ( this.submit ) {
			this.submit.classList.add( 'is-loading' );
			this.submit.disabled = true;
		}

		this.progressTimer = setInterval( function () {
			pct = Math.min( pct + Math.random() * 18, 90 );
			self.progressBar.style.width = pct + '%';
			self.progressLabel.textContent = Math.round( pct ) + '%';
		}, 220 );
	};

	PdTool.prototype.finishProgress = function () {
		clearInterval( this.progressTimer );
		this.progressBar.style.width = '100%';
		this.progressLabel.textContent = '100%';

		if ( this.submit ) {
			this.submit.classList.remove( 'is-loading' );
			this.submit.disabled = false;
		}

		var self = this;
		setTimeout( function () {
			self.progress.hidden = true;
		}, 300 );
	};

	PdTool.prototype.start = function ( url ) {
		var self = this;
		url = ( url || '' ).trim();
		if ( ! url ) {
			return;
		}
		this.lastUrl = url;
		this.results.hidden = true;
		this.results.innerHTML = '';
		this.setStatus( cfg.strings.fetching );
		this.startProgress();

		fetch( cfg.restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce,
			},
			body: JSON.stringify( { url: url } ),
		} )
			.then( function ( res ) {
				return res.json().then( function ( data ) {
					return { status: res.status, data: data };
				} );
			} )
			.then( function ( wrapped ) {
				self.finishProgress();
				self.handleResponse( wrapped.data );
			} )
			.catch( function () {
				self.finishProgress();
				self.setStatus( cfg.strings.failed, true );
			} );
	};

	PdTool.prototype.handleResponse = function ( data ) {
		if ( ! data || ! data.ok ) {
			var key = data && data.error ? data.error : 'failed';
			var map = {
				private: cfg.strings.private,
				deleted: cfg.strings.gone,
				invalid_url: cfg.strings.failed,
				unsupported: cfg.strings.failed,
				fetch_failed: cfg.strings.failed,
				rate_limited: cfg.strings.rateLimited,
			};
			this.setStatus( map[ key ] || cfg.strings.failed, true );
			return;
		}

		this.setStatus( '' );
		this.results.hidden = false;

		var isBulk = [ 'carousel', 'story', 'board', 'profile', 'ideas', 'answers' ].indexOf( data.type ) !== -1;
		if ( isBulk ) {
			this.renderMulti( data );
		} else {
			this.renderSingle( data );
		}
	};

	PdTool.prototype.renderSingle = function ( data ) {
		var self = this;
		var item = data.items[0];
		var node = this.tplSingle.content.cloneNode( true );
		var thumb = node.querySelector( '.pd-result__thumb' );
		var titleEl = node.querySelector( '.pd-result__title' );
		var select = node.querySelector( '.pd-result__quality' );
		var btn = node.querySelector( '.pd-result__download' );
		var saveBtn = node.querySelector( '.pd-result__save-moodboard' );

		thumb.src = item.thumbnail || '';
		thumb.alt = data.title || '';
		titleEl.textContent = data.title || '';

		item.resolutions.forEach( function ( res, i ) {
			var opt = document.createElement( 'option' );
			opt.value = String( i );
			var sizeLabel = res.filesize ? ' (' + formatBytes( res.filesize ) + ')' : '';
			opt.textContent = res.label + sizeLabel;
			select.appendChild( opt );
		} );

		btn.addEventListener( 'click', function () {
			var res = item.resolutions[ Number( select.value ) ];
			if ( ! res ) {
				return;
			}
			var ext = guessExtension( item.media_type, res.url );
			var filename = slugFilename( data.title, res.label, ext );
			triggerDownload( streamUrlFor( res.url, filename ) );
			self.showDone();
		} );

		if ( saveBtn && window.PinsDownloadMoodboard ) {
			saveBtn.addEventListener( 'click', function () {
				window.PinsDownloadMoodboard.add( { image: item.thumbnail, title: data.title } );
				saveBtn.textContent = cfg.strings.savedToMoodboard || 'Saved to Moodboard';
				saveBtn.disabled = true;
			} );
		}

		this.results.innerHTML = '';
		this.results.appendChild( node );
	};

	PdTool.prototype.renderMulti = function ( data ) {
		var self = this;
		var wrap = document.createElement( 'div' );
		wrap.className = 'pd-result pd-result--multi';

		var grid = document.createElement( 'div' );
		grid.className = 'pd-multi-grid';

		data.items.forEach( function ( item, index ) {
			var node = self.tplMultiItem.content.cloneNode( true );
			var checkbox = node.querySelector( '.pd-multi-item__check' );
			var thumb = node.querySelector( '.pd-multi-item__thumb' );
			var meta = node.querySelector( '.pd-multi-item__meta' );
			var saveBtn = node.querySelector( '.pd-multi-item__save-moodboard' );

			checkbox.dataset.index = String( index );
			thumb.src = item.thumbnail || '';
			thumb.alt = item.pin_title || data.title || '';
			meta.textContent = item.media_type + ( item.resolutions[0] ? ' · ' + item.resolutions[0].label : '' );

			if ( saveBtn && window.PinsDownloadMoodboard ) {
				saveBtn.addEventListener( 'click', function () {
					window.PinsDownloadMoodboard.add( { image: item.thumbnail, title: item.pin_title || data.title } );
					saveBtn.innerHTML = ICON_SVG.check;
					saveBtn.disabled = true;
				} );
			}

			grid.appendChild( node );
		} );

		wrap.appendChild( grid );

		var actions = document.createElement( 'div' );
		actions.className = 'pd-multi-actions';

		var downloadSelected = document.createElement( 'button' );
		downloadSelected.type = 'button';
		downloadSelected.className = 'pd-btn pd-btn--primary';
		downloadSelected.textContent = 'Download selected';
		actions.appendChild( downloadSelected );

		if ( data.items.length > ( cfg.zipAfter || 5 ) ) {
			var zipBtn = document.createElement( 'button' );
			zipBtn.type = 'button';
			zipBtn.className = 'pd-btn pd-btn--ghost';
			zipBtn.textContent = 'Download all as ZIP';
			zipBtn.addEventListener( 'click', function () {
				self.downloadAsZip( data, grid );
			} );
			actions.appendChild( zipBtn );
		}

		downloadSelected.addEventListener( 'click', function () {
			self.downloadSelectedIndividually( data, grid );
		} );

		wrap.appendChild( actions );

		this.results.innerHTML = '';
		this.results.appendChild( wrap );
	};

	PdTool.prototype.getSelectedIndexes = function ( grid ) {
		return Array.prototype.slice
			.call( grid.querySelectorAll( '.pd-multi-item__check:checked' ) )
			.map( function ( el ) {
				return Number( el.dataset.index );
			} );
	};

	PdTool.prototype.downloadSelectedIndividually = function ( data, grid ) {
		var self = this;
		var indexes = this.getSelectedIndexes( grid );
		indexes.forEach( function ( i, order ) {
			var item = data.items[ i ];
			var res = item.resolutions[0];
			if ( ! res ) {
				return;
			}
			var ext = guessExtension( item.media_type, res.url );
			var filename = slugFilename( item.pin_title || data.title, String( order + 1 ), ext );
			setTimeout( function () {
				triggerDownload( streamUrlFor( res.url, filename ) );
			}, order * 250 );
		} );
		this.showDone();
	};

	PdTool.prototype.downloadAsZip = function ( data, grid ) {
		var self = this;
		var indexes = this.getSelectedIndexes( grid );
		if ( ! indexes.length || typeof JSZip === 'undefined' ) {
			return;
		}

		this.setStatus( cfg.strings.zipping );
		this.startProgress();

		var zip = new JSZip();
		var done = 0;

		var fetchOne = function ( i ) {
			var item = data.items[ i ];
			var res = item.resolutions[0];
			if ( ! res ) {
				return Promise.resolve();
			}
			var ext = guessExtension( item.media_type, res.url );
			var filename = slugFilename( item.pin_title || data.title, String( i + 1 ), ext );
			return fetch( streamUrlFor( res.url, filename ) )
				.then( function ( r ) {
					return r.blob();
				} )
				.then( function ( blob ) {
					zip.file( filename, blob );
					done++;
				} )
				.catch( function () {
					done++;
				} );
		};

		Promise.all( indexes.map( fetchOne ) ).then( function () {
			zip.generateAsync( { type: 'blob' } ).then( function ( blob ) {
				self.finishProgress();
				var url = URL.createObjectURL( blob );
				var filename = slugFilename( data.title || 'pinsdownload', 'collection', 'zip' );
				var a = document.createElement( 'a' );
				a.href = url;
				a.download = filename;
				document.body.appendChild( a );
				a.click();
				a.remove();
				setTimeout( function () {
					URL.revokeObjectURL( url );
				}, 4000 );
				self.showDone();
			} );
		} );
	};

	PdTool.prototype.showDone = function () {
		this.setStatus( cfg.strings.saved );
		maybePromptInstall();

		var self = this;
		var node = this.tplDone.content.cloneNode( true );
		var again = node.querySelector( '.pd-done__again' );
		again.addEventListener( 'click', function () {
			self.start( self.lastUrl );
		} );

		var existing = this.root.querySelector( '.pd-done' );
		if ( existing ) {
			existing.remove();
		}
		this.root.appendChild( node );
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.pd-tool' ).forEach( function ( el ) {
			new PdTool( el );
		} );
	} );
} )();
