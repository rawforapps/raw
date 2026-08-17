/**
 * Visual/interaction layer only: scroll-reveal, FAQ accordion, sticky
 * header state. No content, no tool logic — that lives in tool.js and
 * the PHP resolver, untouched by this design pass.
 */
( function () {
	'use strict';

	/* Scroll-reveal --------------------------------------------------- */
	var revealTargets = document.querySelectorAll( '.pd-reveal' );
	if ( 'IntersectionObserver' in window && revealTargets.length ) {
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-visible' );
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
