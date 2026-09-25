/*
 * Mostra o botão depois de a página descer, e leva-a de volta ao início.
 *
 * O limiar é um ecrã e meio: antes disso o cabeçalho ainda está à mão e o botão
 * seria só mais uma coisa por cima do conteúdo.
 *
 * O `scroll` dispara dezenas de vezes por segundo, pelo que a leitura é adiada
 * para o quadro seguinte — assim o browser mede uma vez por desenho e não uma
 * vez por evento.
 *
 * A subida respeita quem pediu menos movimento no sistema: nesse caso salta em
 * vez de deslizar. E o foco vai para o cabeçalho, senão quem navega por teclado
 * subia a página e continuava com o foco no fim dela.
 */
( function () {
	'use strict';

	var botao = document.getElementById( 'apit-topo' );

	if ( ! botao ) {
		return;
	}

	var LIMIAR = Math.round( window.innerHeight * 1.5 );
	var agendado = false;

	function actualizar() {
		agendado = false;

		var passou = window.scrollY > LIMIAR;

		if ( passou === ! botao.hidden ) {
			return;
		}

		botao.hidden = ! passou;
	}

	function aoRolar() {
		if ( agendado ) {
			return;
		}

		agendado = true;
		window.requestAnimationFrame( actualizar );
	}

	window.addEventListener( 'scroll', aoRolar, { passive: true } );
	window.addEventListener( 'resize', function () {
		LIMIAR = Math.round( window.innerHeight * 1.5 );
		aoRolar();
	}, { passive: true } );

	botao.addEventListener( 'click', function () {
		var suave = ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		window.scrollTo( {
			top: 0,
			behavior: suave ? 'smooth' : 'auto',
		} );

		var cabecalho = document.querySelector( '.apit-header a, .apit-header button' );

		if ( cabecalho ) {
			cabecalho.focus( { preventScroll: true } );
		}
	} );

	actualizar();
}() );
