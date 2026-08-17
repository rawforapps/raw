/**
 * Visual/interaction layer only: content-block enrichment, scroll-reveal,
 * FAQ accordion, sticky header state. No copy is read, written, or
 * altered anywhere in this file — it only rearranges existing DOM nodes
 * and adds empty image-slot placeholders around them.
 */
( function () {
	'use strict';

	/* Content-block auto-enrichment --------------------------------------
	 * Applies only to .pd-enrich-content, which is what every current AND
	 * future Tool Landing Page's the_content() renders into
	 * (page-templates/template-tool-landing.php) — deliberately NOT the
	 * same class ordinary pages use (page.php/single.php's
	 * .pd-article__content), so legal/about pages never get split into
	 * zig-zag image blocks. Splits the flat H2-delimited content into
	 * alternating, image-slotted sections so a new landing page gets the
	 * same variety as the homepage without any per-page markup work. */
	var ICON_PATHS = {
		video: '<path d="M15 10l4.55-2.28A1 1 0 0 1 21 8.62v6.76a1 1 0 0 1-1.45.9L15 14"/><rect x="3" y="6" width="12" height="12" rx="2"/>',
		image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
		gif: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 9v6M11 9v6M11 12h2.5M17 9h-2.5v6M17 12h-2"/>',
		everywhere: '<rect x="4" y="2" width="10" height="16" rx="2"/><rect x="15" y="7" width="6" height="14" rx="1.5"/><path d="M9 15h.01"/>',
		carousel: '<rect x="2" y="7" width="14" height="12" rx="2"/><path d="M8 3h12a2 2 0 0 1 2 2v12"/>',
		sparkle: '<path d="M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3z"/>',
		profile: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
		ideas: '<path d="M9 18h6M10 22h4M12 2a6 6 0 0 0-4 10.5c.6.6 1 1.4 1 2.5h6c0-1.1.4-1.9 1-2.5A6 6 0 0 0 12 2z"/>',
		photo: '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M21 16l-5.2-5.2a1.5 1.5 0 0 0-2.1 0L5 19"/>',
	};

	function svg( name ) {
		var d = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
		d.setAttribute( 'viewBox', '0 0 24 24' );
		d.setAttribute( 'fill', 'none' );
		d.setAttribute( 'stroke', 'currentColor' );
		d.setAttribute( 'stroke-width', '2' );
		d.setAttribute( 'stroke-linecap', 'round' );
		d.setAttribute( 'stroke-linejoin', 'round' );
		d.setAttribute( 'class', 'pd-icon' );
		d.setAttribute( 'aria-hidden', 'true' );
		d.innerHTML = ICON_PATHS[ name ] || ICON_PATHS.photo;
		return d;
	}

	function iconForHeading( text ) {
		var t = text.toLowerCase();
		if ( t.indexOf( 'gif' ) !== -1 ) return 'gif';
		if ( t.indexOf( 'video' ) !== -1 ) return 'video';
		if ( t.indexOf( 'image' ) !== -1 || t.indexOf( 'photo' ) !== -1 ) return 'image';
		if ( t.indexOf( 'iphone' ) !== -1 || t.indexOf( 'android' ) !== -1 || t.indexOf( 'pc' ) !== -1 || t.indexOf( 'computer' ) !== -1 ) return 'everywhere';
		if ( t.indexOf( 'else' ) !== -1 ) return 'carousel';
		if ( t.indexOf( 'what is' ) !== -1 ) return 'sparkle';
		if ( t.indexOf( 'people' ) !== -1 || t.indexOf( 'use' ) !== -1 ) return 'profile';
		if ( t.indexOf( 'why' ) !== -1 || t.indexOf( 'different' ) !== -1 ) return 'ideas';
		return 'photo';
	}

	function buildImageSlot( label, iconName ) {
		var slot = document.createElement( 'div' );
		slot.className = 'pd-img-slot pd-img-slot--wide';

		var iconWrap = document.createElement( 'span' );
		iconWrap.className = 'pd-img-slot__icon';
		iconWrap.appendChild( svg( iconName ) );
		slot.appendChild( iconWrap );

		var labelEl = document.createElement( 'span' );
		labelEl.className = 'pd-img-slot__label';
		labelEl.textContent = label;
		slot.appendChild( labelEl );

		return slot;
	}

	function enrichContentBlocks() {
		document.querySelectorAll( '.pd-enrich-content' ).forEach( function ( container ) {
			var nodes = Array.prototype.slice.call( container.childNodes );
			var groups = [];
			var current = null;

			nodes.forEach( function ( node ) {
				if ( node.nodeType === 1 && node.tagName === 'H2' ) {
					current = { heading: node, rest: [] };
					groups.push( current );
				} else if ( current ) {
					current.rest.push( node );
				}
				// Anything before the first H2 is left exactly where it is.
			} );

			if ( groups.length < 1 ) {
				return;
			}

			groups.forEach( function ( group, index ) {
				var block = document.createElement( 'div' );
				block.className = 'pd-content-block pd-reveal';

				var split = document.createElement( 'div' );
				split.className = 'pd-split' + ( index % 2 === 1 ? ' pd-split--reverse' : '' );

				var textCol = document.createElement( 'div' );
				textCol.className = 'pd-split__text';
				textCol.appendChild( group.heading );
				group.rest.forEach( function ( node ) {
					textCol.appendChild( node );
				} );

				var headingText = group.heading.textContent.trim();
				var mediaCol = document.createElement( 'div' );
				mediaCol.className = 'pd-split__media';
				mediaCol.appendChild( buildImageSlot( 'Add an image for "' + headingText + '"', iconForHeading( headingText ) ) );

				split.appendChild( textCol );
				split.appendChild( mediaCol );
				block.appendChild( split );

				container.appendChild( block );
			} );
		} );
	}

	enrichContentBlocks();

	/* Staggered child entrance ------------------------------------------
	 * Applied via inline styles only (never a CSS class) so it can never
	 * conflict with existing hover/transition rules on .pd-grid-card,
	 * .pd-features li, etc. regardless of stylesheet source order. */
	var reducedMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function staggerChildren( section ) {
		if ( reducedMotion ) {
			return;
		}
		var items = section.querySelectorAll( '.pd-grid-card, .pd-features > li, .pd-tags > li, .pd-testimonial' );
		items.forEach( function ( el, i ) {
			var delay = Math.min( i, 10 ) * 40;
			el.style.opacity = '0';
			el.style.transform = 'translateY(14px)';
			el.style.transition = 'opacity 0.4s ease ' + delay + 'ms, transform 0.4s ease ' + delay + 'ms';
			// Next frame, so the browser registers the "from" state above
			// before we set the "to" state (otherwise no transition plays).
			requestAnimationFrame( function () {
				requestAnimationFrame( function () {
					el.style.opacity = '';
					el.style.transform = '';
				} );
			} );
			// Clear the inline transition once the reveal animation is
			// done, so it never lingers and overrides the element's own
			// hover-transition rules (e.g. .pd-grid-card:hover) later.
			setTimeout( function () {
				el.style.transition = '';
			}, delay + 450 );
		} );
	}

	/* Scroll-reveal --------------------------------------------------- */
	var revealTargets = document.querySelectorAll( '.pd-reveal' );
	if ( 'IntersectionObserver' in window && revealTargets.length ) {
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-visible' );
						staggerChildren( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
		);
		revealTargets.forEach( function ( el ) {
			observer.observe( el );
		} );
	} else {
		revealTargets.forEach( function ( el ) {
			el.classList.add( 'is-visible' );
		} );
	}

	/* FAQ accordion ----------------------------------------------------- */
	document.querySelectorAll( '.pd-faq-item' ).forEach( function ( item ) {
		var question = item.querySelector( '.pd-faq-q' );
		var answer = item.querySelector( '.pd-faq-a' );
		if ( ! question || ! answer ) {
			return;
		}
		question.setAttribute( 'aria-expanded', 'false' );
		question.addEventListener( 'click', function () {
			var isOpen = item.classList.contains( 'is-open' );

			// Accordion behavior: close any sibling that's open.
			var parent = item.closest( '.pd-faq' );
			if ( parent ) {
				parent.querySelectorAll( '.pd-faq-item.is-open' ).forEach( function ( openItem ) {
					if ( openItem !== item ) {
						openItem.classList.remove( 'is-open' );
						openItem.querySelector( '.pd-faq-a' ).style.maxHeight = null;
						openItem.querySelector( '.pd-faq-q' ).setAttribute( 'aria-expanded', 'false' );
					}
				} );
			}

			if ( isOpen ) {
				item.classList.remove( 'is-open' );
				answer.style.maxHeight = null;
				question.setAttribute( 'aria-expanded', 'false' );
			} else {
				item.classList.add( 'is-open' );
				answer.style.maxHeight = answer.scrollHeight + 'px';
				question.setAttribute( 'aria-expanded', 'true' );
			}
		} );
	} );

	/* Sticky header ------------------------------------------------------ */
	var header = document.getElementById( 'pd-site-header' );
	if ( header ) {
		var toggleHeader = function () {
			header.classList.toggle( 'is-scrolled', window.scrollY > 80 );
		};
		toggleHeader();
		window.addEventListener( 'scroll', toggleHeader, { passive: true } );
	}
} )();
