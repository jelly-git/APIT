/**
 * Calendar carousel: arrows step one card at a time and the track can be
 * dragged with a mouse, pen or finger.
 */
( function () {
	'use strict';

	var DURACAO = 400;
	// Movement past this many pixels counts as a drag, not a click on a card.
	var LIMIAR_ARRASTO = 5;

	/**
	 * Animates scrollLeft by hand rather than relying on scrollBy's "smooth"
	 * behavior, which some engines do not implement — where it is missing the
	 * scroll does not happen at all.
	 *
	 * requestAnimationFrame is paused in hidden and background tabs, so a timer
	 * guard drops the track at its destination if no frame ever arrives.
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

		if ( ! track ) {
			return;
		}

		/**
		 * One card plus one gap — read from the DOM so it follows whichever
		 * breakpoint is in effect.
		 */
		function passoLargura() {
			var item = track.querySelector( '.calendario__item' );

			if ( ! item ) {
				return track.clientWidth;
			}

			var gap = parseFloat( getComputedStyle( track ).columnGap ) || 0;

			return item.getBoundingClientRect().width + gap;
		}

		function maximo() {
			return track.scrollWidth - track.clientWidth;
		}

		/**
		 * Nearest resting position: a card boundary, or the end of the track.
		 *
		 * The end matters because the track runs past the content column, so the
		 * remaining scroll can be shorter than a single card — rounding to a
		 * multiple of the card width alone would snap that back to zero and
		 * undo the drag.
		 */
		function posicaoMaisProxima( actual ) {
			var largura = passoLargura();
			var fim = maximo();
			var candidatos = [ fim ];

			for ( var x = 0; x < fim; x += largura ) {
				candidatos.push( x );
			}

			return candidatos.reduce( function ( melhor, c ) {
				return Math.abs( c - actual ) < Math.abs( melhor - actual ) ? c : melhor;
			}, candidatos[ 0 ] );
		}

		function sync() {
			// A pixel of tolerance: fractional widths leave the ends just short.
			var noInicio = track.scrollLeft <= 1;
			var noFim = track.scrollLeft >= maximo() - 1;

			arrows.forEach( function ( arrow ) {
				arrow.disabled = 'prev' === arrow.dataset.dir ? noInicio : noFim;
			} );
		}

		function irPara( destino ) {
			var reduzir = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			var limitado = Math.max( 0, Math.min( maximo(), destino ) );

			animarScroll( track, limitado, reduzir ? 0 : DURACAO, sync );
		}

		arrows.forEach( function ( arrow ) {
			arrow.addEventListener( 'click', function () {
				var delta = 'prev' === arrow.dataset.dir ? -passoLargura() : passoLargura();

				irPara( track.scrollLeft + delta );
			} );
		} );

		// ---- Dragging ----

		var arrastando = false;
		var arrastou = false;
		var xInicial = 0;
		var scrollInicial = 0;

		track.addEventListener( 'pointerdown', function ( e ) {
			// Left button (or touch/pen) only.
			if ( e.button && 0 !== e.button ) {
				return;
			}

			arrastando = true;
			arrastou = false;
			xInicial = e.clientX;
			scrollInicial = track.scrollLeft;
		} );

		track.addEventListener( 'pointermove', function ( e ) {
			if ( ! arrastando ) {
				return;
			}

			var delta = e.clientX - xInicial;

			if ( ! arrastou && Math.abs( delta ) > LIMIAR_ARRASTO ) {
				arrastou = true;
				track.classList.add( 'is-dragging' );
				// Keeps events coming if the pointer leaves the track.
				if ( track.setPointerCapture ) {
					track.setPointerCapture( e.pointerId );
				}
			}

			if ( arrastou ) {
				e.preventDefault();
				track.scrollLeft = scrollInicial - delta;
				sync();
			}
		} );

		function largar() {
			if ( ! arrastando ) {
				return;
			}

			arrastando = false;

			if ( ! arrastou ) {
				return;
			}

			track.classList.remove( 'is-dragging' );

			irPara( posicaoMaisProxima( track.scrollLeft ) );
		}

		track.addEventListener( 'pointerup', largar );
		track.addEventListener( 'pointercancel', largar );
		track.addEventListener( 'pointerleave', largar );

		// A drag that ends over a card must not follow its link.
		track.addEventListener( 'click', function ( e ) {
			if ( arrastou ) {
				e.preventDefault();
				e.stopPropagation();
				arrastou = false;
			}
		}, true );

		// Native image dragging would hijack the gesture.
		track.addEventListener( 'dragstart', function ( e ) {
			e.preventDefault();
		} );

		track.addEventListener( 'scroll', sync );
		window.addEventListener( 'resize', sync );
		sync();
	} );
}() );
