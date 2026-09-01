/**
 * Calendar carousel: the prev/next arrows scroll the track by one card, and
 * disable themselves at each end.
 */
( function () {
	'use strict';

	var DURACAO = 350;

	/**
	 * Animates scrollLeft by hand rather than relying on scrollBy's "smooth"
	 * behavior, which some engines do not implement — where it is missing the
	 * scroll silently does nothing at all.
	 *
	 * requestAnimationFrame is paused in hidden and background tabs, so a timer
	 * guard drops the track at its destination if no frame ever arrives. That
	 * keeps the arrows working even where the animation cannot run.
	 */
	function animarScroll( el, destino, duracao, aoTerminar ) {
		var inicio = el.scrollLeft;
		var distancia = destino - inicio;

		if ( ! duracao || ! distancia ) {
			el.scrollLeft = destino;
			aoTerminar();
			return;
		}

		var arranque = null;
		var terminou = false;

		function passo( agora ) {
			if ( null === arranque ) {
				arranque = agora;
			}

			var t = Math.min( ( agora - arranque ) / duracao, 1 );
			// ease-out cubic
			var eased = 1 - Math.pow( 1 - t, 3 );

			el.scrollLeft = inicio + distancia * eased;

			if ( t < 1 ) {
				requestAnimationFrame( passo );
			} else {
				terminou = true;
				aoTerminar();
			}
		}

		requestAnimationFrame( passo );

		setTimeout( function () {
			if ( ! terminou ) {
				el.scrollLeft = destino;
				aoTerminar();
			}
		}, duracao + 100 );
	}

	document.querySelectorAll( '.calendario' ).forEach( function ( calendario ) {
		var track = calendario.querySelector( '.calendario__track' );
		var arrows = calendario.querySelectorAll( '.calendario__arrow' );

		if ( ! track || ! arrows.length ) {
			return;
		}

		/**
		 * One card plus one gap — read from the DOM so it follows the
		 * breakpoint currently in effect.
		 */
		function step() {
			var item = track.querySelector( '.calendario__item' );

			if ( ! item ) {
				return track.clientWidth;
			}

			var gap = parseFloat( getComputedStyle( track ).columnGap ) || 0;

			return item.getBoundingClientRect().width + gap;
		}

		function sync() {
			// A pixel of tolerance: fractional widths make the ends land just short.
			var atStart = track.scrollLeft <= 1;
			var atEnd = track.scrollLeft >= track.scrollWidth - track.clientWidth - 1;

			arrows.forEach( function ( arrow ) {
				var isPrev = 'prev' === arrow.dataset.dir;
				arrow.disabled = isPrev ? atStart : atEnd;
			} );
		}

		arrows.forEach( function ( arrow ) {
			arrow.addEventListener( 'click', function () {
				var delta = 'prev' === arrow.dataset.dir ? -step() : step();
				var maximo = track.scrollWidth - track.clientWidth;
				var destino = Math.max( 0, Math.min( maximo, track.scrollLeft + delta ) );
				var reduzir = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

				animarScroll( track, destino, reduzir ? 0 : DURACAO, sync );
			} );
		} );

		track.addEventListener( 'scroll', sync );
		window.addEventListener( 'resize', sync );
		sync();
	} );
}() );
