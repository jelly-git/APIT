/**
 * Hero background slider.
 *
 * The markup arrives with one slide already in it — the first, chosen by the
 * browser from a <picture> — and the rest as HTML inside data-restantes, split
 * into a desktop set and a mobile set. This builds only the set matching the
 * current breakpoint, so a phone never downloads the landscape images and a
 * desktop never downloads the portrait ones. They are hero-sized files; loading
 * both would cost more than the slider is worth.
 *
 * One image means no slider at all: nothing is built and no timer starts.
 */
( function () {
	'use strict';

	var TELEMOVEL = '(max-width: 768px)';

	function iniciar( slider ) {
		var restantes = {};

		try {
			restantes = JSON.parse( slider.dataset.restantes || '{}' );
		} catch ( e ) {
			// A malformed attribute leaves the first slide showing, which is
			// the same thing a one-image gallery does. Nothing to recover.
			return;
		}

		var intervalo = parseInt( slider.dataset.intervalo, 10 ) || 4000;
		var mq = window.matchMedia( TELEMOVEL );
		var primeiro = slider.querySelector( '.apit-hero__slide' );
		var temporizador = null;
		var conjuntoAtual = null;

		function parar() {
			if ( temporizador ) {
				window.clearInterval( temporizador );
				temporizador = null;
			}
		}

		function construir() {
			var nome = mq.matches ? 'mobile' : 'desktop';

			if ( nome === conjuntoAtual ) {
				return;
			}

			conjuntoAtual = nome;
			parar();

			// Back to just the server-rendered slide before adding the other
			// set, or switching breakpoint twice would stack them.
			Array.prototype.slice.call( slider.children ).forEach( function ( slide ) {
				if ( slide !== primeiro ) {
					slide.remove();
				}
			} );

			primeiro.classList.add( 'is-ativo' );

			var html = restantes[ nome ] || [];

			html.forEach( function ( img ) {
				var slide = document.createElement( 'div' );
				slide.className = 'apit-hero__slide';
				slide.innerHTML = img;
				slider.appendChild( slide );
			} );

			rodar();
		}

		function rodar() {
			var slides = slider.querySelectorAll( '.apit-hero__slide' );

			if ( slides.length < 2 ) {
				return;
			}

			/*
			 * Someone who asked for less motion gets the first image and no
			 * rotation. The slider is decoration; it is not carrying anything
			 * they would miss.
			 */
			if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				return;
			}

			// Read the position off the DOM rather than assuming zero: coming
			// back from a hidden tab has to carry on where it stopped.
			var atual = Array.prototype.findIndex.call( slides, function ( s ) {
				return s.classList.contains( 'is-ativo' );
			} );

			if ( atual < 0 ) {
				atual = 0;
				slides[ 0 ].classList.add( 'is-ativo' );
			}

			temporizador = window.setInterval( function () {
				slides[ atual ].classList.remove( 'is-ativo' );
				atual = ( atual + 1 ) % slides.length;
				slides[ atual ].classList.add( 'is-ativo' );
			}, intervalo );
		}

		if ( ! primeiro ) {
			return;
		}

		construir();

		// Safari below 14 has no addEventListener on a MediaQueryList.
		if ( mq.addEventListener ) {
			mq.addEventListener( 'change', construir );
		} else if ( mq.addListener ) {
			mq.addListener( construir );
		}

		// A background animation on a hidden tab is wasted work.
		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				parar();
			} else if ( ! temporizador ) {
				rodar();
			}
		} );
	}

	function arrancar() {
		var sliders = document.querySelectorAll( '.apit-hero__slider[data-restantes]' );
		Array.prototype.forEach.call( sliders, iniciar );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', arrancar );
	} else {
		arrancar();
	}
}() );
