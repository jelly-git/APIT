/**
 * The Home hero's video panel: plays the video set in the back office in a
 * dialog over the page.
 *
 * The panel is a link to the video (template-parts/hero-decor.php), so without
 * this script it still opens — in a new tab. Here the click is taken over and
 * the player goes into a native <dialog>: it sits in the top layer, so the
 * hero's overflow and stacking do not clip it, Esc closes it, and focus goes
 * back to the panel on its own when it does.
 *
 * The player is created on opening and removed on closing. Only removing it
 * stops a YouTube or Vimeo video for certain; and nothing is fetched from those
 * sites until someone actually presses play.
 */
( function () {
	'use strict';

	var link = document.querySelector( '.hero__video[data-video-src]' );
	var dialogo = document.getElementById( 'apit-video-destaque' );

	if ( ! link || ! dialogo || typeof dialogo.showModal !== 'function' ) {
		return;
	}

	var palco = dialogo.querySelector( '.apit-video-modal__palco' );
	var fechar = dialogo.querySelector( '.apit-video-modal__fechar' );

	function leitor() {
		var src = link.getAttribute( 'data-video-src' );

		if ( link.getAttribute( 'data-video-tipo' ) === 'ficheiro' ) {
			var video = document.createElement( 'video' );
			video.src = src;
			video.controls = true;
			video.autoplay = true;
			video.playsInline = true;
			return video;
		}

		var iframe = document.createElement( 'iframe' );
		iframe.src = src;
		iframe.title = dialogo.getAttribute( 'aria-label' ) || '';
		iframe.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
		iframe.allowFullscreen = true;
		return iframe;
	}

	link.addEventListener( 'click', function ( evento ) {
		// Ctrl/Cmd-click and the middle button keep their new-tab meaning.
		if ( evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.button !== 0 ) {
			return;
		}

		evento.preventDefault();

		palco.classList.toggle( 'is-vertical', link.getAttribute( 'data-video-vertical' ) === '1' );
		palco.classList.toggle( 'is-ficheiro', link.getAttribute( 'data-video-tipo' ) === 'ficheiro' );
		palco.replaceChildren( leitor() );
		dialogo.showModal();
		fechar.focus();
	} );

	/*
	 * Every way out goes through here, and the player is removed before the
	 * dialog closes. Leaving that to the dialog's `close` event was not enough:
	 * with a YouTube frame inside, Chrome closed the dialog and never fired it,
	 * and the video went on playing, unseen, behind the page.
	 */
	function fecharVideo() {
		palco.replaceChildren();

		if ( dialogo.open ) {
			dialogo.close();
		}

		// The dialog restores focus by itself only if something had it before.
		link.focus( { preventScroll: true } );
	}

	fechar.addEventListener( 'click', fecharVideo );

	// A click on the backdrop lands on the dialog itself, not on its contents.
	dialogo.addEventListener( 'click', function ( evento ) {
		if ( evento.target === dialogo ) {
			fecharVideo();
		}
	} );

	// Esc.
	dialogo.addEventListener( 'cancel', function ( evento ) {
		evento.preventDefault();
		fecharVideo();
	} );

	// Anything else that closes it — a form, the browser — still stops the video.
	dialogo.addEventListener( 'close', function () {
		palco.replaceChildren();
	} );
}() );
