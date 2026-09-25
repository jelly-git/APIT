/*
 * A faixa de pesquisa do cabeçalho, com resultados enquanto se escreve.
 *
 * O site tem pouco mais de cem conteúdos, pelo que a resposta chega num
 * instante e a maioria das buscas nunca precisa da página de resultados. Ela
 * existe à mesma — para listas longas, e para quem partilhe o endereço da busca.
 *
 * Espera 220ms depois da última tecla antes de perguntar ao servidor: escrever
 * "anuário" são sete teclas e seria sete pedidos, dos quais só o último
 * interessa. E cada pedido novo cancela o anterior, senão uma resposta lenta
 * podia chegar depois de uma rápida e escrever por cima dela o resultado de uma
 * palavra que já lá não está.
 */
( function () {
	'use strict';

	var faixa = document.getElementById( 'apit-pesquisa' );
	var botao = document.querySelector( '.apit-header__search-toggle' );

	if ( ! faixa || ! botao ) {
		return;
	}

	var campo     = faixa.querySelector( '.apit-pesquisa__campo' );
	var caixa     = faixa.querySelector( '.apit-pesquisa__resultados' );
	var fechar    = faixa.querySelector( '.apit-pesquisa__fechar' );
	var rota      = ( window.apitPesquisa && window.apitPesquisa.rota ) || '/wp-json/apit/v1/pesquisa';
	var temporizador = null;
	var pedido    = null;
	var ultimo    = '';

	function abrir() {
		faixa.hidden = false;
		botao.setAttribute( 'aria-expanded', 'true' );
		campo.focus();
	}

	function esconder() {
		faixa.hidden = true;
		botao.setAttribute( 'aria-expanded', 'false' );
		caixa.innerHTML = '';
		ultimo = '';
	}

	function escapar( texto ) {
		var d = document.createElement( 'div' );
		d.textContent = texto;
		return d.innerHTML;
	}

	function desenhar( dados ) {
		if ( ! dados.grupos.length ) {
			caixa.innerHTML = '<p class="apit-pesquisa__vazio">Sem resultados para “' + escapar( dados.termo ) + '”.</p>';
			return;
		}

		var html = '';

		dados.grupos.forEach( function ( grupo ) {
			html += '<div class="apit-pesquisa__grupo">';
			html += '<p class="apit-pesquisa__etiqueta">' + escapar( grupo.etiqueta ) + '</p>';
			html += '<ul class="apit-pesquisa__lista">';

			grupo.resultados.forEach( function ( r ) {
				html += '<li><a class="apit-pesquisa__item" href="' + escapar( r.url ) + '"' +
					( r.externo ? ' target="_blank" rel="noopener"' : '' ) + '>' +
					'<span class="apit-pesquisa__item-titulo">' + escapar( r.titulo ) + '</span>' +
					( r.contexto ? '<span class="apit-pesquisa__item-contexto">' + escapar( r.contexto ) + '</span>' : '' ) +
					'</a></li>';
			} );

			html += '</ul></div>';
		} );

		html += '<a class="apit-pesquisa__todos" href="' + escapar( dados.url ) + '">Ver todos os resultados</a>';

		caixa.innerHTML = html;
	}

	function procurar() {
		var termo = campo.value.trim();

		if ( termo === ultimo ) {
			return;
		}

		ultimo = termo;

		if ( termo.length < 2 ) {
			caixa.innerHTML = '';
			return;
		}

		if ( pedido ) {
			pedido.abort();
		}

		pedido = new AbortController();

		fetch( rota + '?q=' + encodeURIComponent( termo ), { signal: pedido.signal } )
			.then( function ( r ) {
				return r.ok ? r.json() : Promise.reject( r.status );
			} )
			.then( desenhar )
			.catch( function ( erro ) {
				if ( erro && erro.name === 'AbortError' ) {
					return;
				}

				/*
				 * Se a rota falhar, o formulário continua a funcionar: o Enter
				 * leva à página de resultados, que é servida pelo PHP.
				 */
				caixa.innerHTML = '<p class="apit-pesquisa__vazio">Não foi possível procurar agora. Carregue Enter para ver a página de resultados.</p>';
			} );
	}

	botao.addEventListener( 'click', function () {
		if ( faixa.hidden ) {
			abrir();
		} else {
			esconder();
		}
	} );

	fechar.addEventListener( 'click', esconder );

	campo.addEventListener( 'input', function () {
		window.clearTimeout( temporizador );
		temporizador = window.setTimeout( procurar, 220 );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && ! faixa.hidden ) {
			esconder();
			botao.focus();
		}
	} );

	// Um clique fora fecha a faixa, como fecha qualquer painel deste género.
	document.addEventListener( 'click', function ( e ) {
		if ( faixa.hidden || faixa.contains( e.target ) || botao.contains( e.target ) ) {
			return;
		}

		esconder();
	} );
}() );
