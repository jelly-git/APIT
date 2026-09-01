/**
 * Full-screen mobile menu: the burger opens it, the X and Escape close it.
 */
( function () {
	'use strict';

	var burger = document.querySelector( '.apit-header__burger' );
	var painel = document.getElementById( 'apit-menu-mobile' );

	if ( ! burger || ! painel ) {
		return;
	}

	var fechar = painel.querySelector( '.apit-menu-mobile__fechar' );

	function abrir() {
		painel.hidden = false;
		burger.setAttribute( 'aria-expanded', 'true' );
		// Stops the page behind the panel from scrolling.
		document.body.classList.add( 'apit-menu-aberto' );

		// Move focus into the panel so keyboard and screen readers follow it.
		( fechar || painel ).focus();
	}

	function fecharMenu() {
		painel.hidden = true;
		burger.setAttribute( 'aria-expanded', 'false' );
		document.body.classList.remove( 'apit-menu-aberto' );
		burger.focus();
	}

	burger.addEventListener( 'click', abrir );

	if ( fechar ) {
		fechar.addEventListener( 'click', fecharMenu );
	}

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && ! painel.hidden ) {
			fecharMenu();
		}
	} );

	/*
	 * Closing on a link click matters for same-page anchors, where no navigation
	 * happens and the panel would otherwise stay over the content.
	 */
	painel.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( 'a' ) ) {
			fecharMenu();
		}
	} );

	/*
	 * Above the breakpoint the panel must never be left open — a rotation or a
	 * resize would otherwise trap the page under it.
	 */
	window.addEventListener( 'resize', function () {
		if ( ! painel.hidden && window.innerWidth > 1024 ) {
			fecharMenu();
		}
	} );
}() );
