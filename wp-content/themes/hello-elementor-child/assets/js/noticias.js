/**
 * Notícias — filtro e paginação sem recarregar a página.
 *
 * Progressive enhancement: o PHP imprime links verdadeiros para ?categoria= e
 * ?pg=, e isto intercepta-os. Sem JavaScript, com o script em erro ou com o
 * pedido a falhar, o clique segue o link e o servidor devolve exactamente a
 * mesma lista — a página nunca fica dependente disto para funcionar.
 *
 * O servidor devolve o mesmo template que a página imprime, pelo que não há
 * aqui uma segunda versão do cartão para manter a par.
 */
( function () {
	'use strict';

	var raiz = document.querySelector( '[data-noticias]' );

	if ( ! raiz || ! window.fetch || ! window.history.pushState ) {
		return;
	}

	var conteudo = raiz.querySelector( '[data-noticias-conteudo]' );
	var endereco = raiz.getAttribute( 'data-ajax-url' );
	var pagina = raiz.getAttribute( 'data-pagina' );
	var pedidoEmCurso = null;

	var SELECTOR = '.noticias-filtros__item, .noticias-paginacao a.page-numbers';

	/**
	 * O endereço do pedido, a partir do link em que se carregou: os parâmetros
	 * que interessam já lá estão, escritos pelo PHP.
	 */
	function enderecoDoPedido( href ) {
		var origem = new URL( href, window.location.href );
		var query = new URLSearchParams( { action: 'apit_noticias', pagina: pagina } );
		var categoria = origem.searchParams.get( 'categoria' );
		var pg = origem.searchParams.get( 'pg' );

		if ( categoria ) {
			query.set( 'categoria', categoria );
		}

		if ( pg ) {
			query.set( 'pg', pg );
		}

		return endereco + ( endereco.indexOf( '?' ) === -1 ? '?' : '&' ) + query.toString();
	}

	/**
	 * Devolve o foco a quem o tinha.
	 *
	 * A substituição do HTML destrói o elemento carregado e o foco cairia no
	 * body — quem navega por teclado perdia o lugar. O link com o mesmo href
	 * volta a existir, a não ser que tenha passado a ser o seleccionado: nesse
	 * caso vai para o que ficou activo — o filtro ou o número da página, consoante
	 * o sítio de onde se carregou, que é onde a pessoa está.
	 */
	function devolverFoco( href, daPaginacao ) {
		var reserva = daPaginacao
			? '.noticias-paginacao .current'
			: '.noticias-filtros__item.is-ativo';

		var alvo = conteudo.querySelector( '[href="' + href + '"]' ) ||
			conteudo.querySelector( reserva );

		if ( ! alvo ) {
			return;
		}

		if ( ! alvo.hasAttribute( 'href' ) ) {
			alvo.setAttribute( 'tabindex', '-1' );
		}

		alvo.focus( { preventScroll: true } );
	}

	/**
	 * Onde está a barra de filtros dentro da janela, agora.
	 *
	 * É o ponto de referência para a página não fugir: a altura do que está
	 * acima dela muda a cada troca — o destaque aparece numa categoria e
	 * desaparece noutra — e sem isto o conteúdo desliza por baixo dos olhos de
	 * quem carregou. Medido a partir da barra e não do topo da secção porque a
	 * barra é o que a pessoa está a olhar quando carrega num filtro.
	 */
	function ancora() {
		var barra = conteudo.querySelector( '.noticias-filtros' ) || conteudo;

		return barra.getBoundingClientRect().top;
	}

	/**
	 * Repõe a barra de filtros à altura onde estava antes da troca.
	 *
	 * `behavior: instant` porque o tema declara `scroll-behavior: smooth` no
	 * html, e um scrollBy normal herda-o: a correcção via-se a acontecer, o que
	 * é precisamente o safanão que ela existe para evitar. Isto não é uma
	 * deslocação — é desfazer uma que não devia ter acontecido.
	 */
	function manterNoSitio( antes ) {
		var desvio = ancora() - antes;

		if ( Math.abs( desvio ) > 1 ) {
			window.scrollBy( { top: desvio, behavior: 'instant' } );
		}
	}

	/**
	 * Ao mudar de página da lista — e só aí — traz a grelha para o ecrã se ela
	 * ficou acima da janela, que é o caso de quem carrega no "seguinte" a partir
	 * do fundo da página. Num filtro não se mexe: a pessoa fica onde estava.
	 */
	function reenquadrar() {
		var grelha = conteudo.querySelector( '.noticias-arquivo__grelha' );

		if ( ! grelha || grelha.getBoundingClientRect().top >= 0 ) {
			return;
		}

		grelha.scrollIntoView( { behavior: 'smooth', block: 'start' } );
	}

	/**
	 * Espera que as imagens do HTML que vem a caminho estejam carregadas.
	 *
	 * Sem isto via-se um flash: o HTML entra no DOM, as capas dos cartões são
	 * `background-image` e o browser só as vai buscar depois de as pintar —
	 * pelo que durante um instante cada cartão mostra a cor de fundo em vez da
	 * imagem. Carregadas antes da troca, entram já prontas da cache.
	 *
	 * Um `decode()` que falhe não trava nada: a imagem pode estar em falta, e
	 * isso é um cartão sem capa, não uma lista que não aparece. O mesmo vale
	 * para o tempo — ao fim de 1,5s a lista entra com o que houver, porque
	 * esperar mais é pior do que o flash que isto evita.
	 */
	function comAsImagensProntas( html ) {
		var caixa = document.createElement( 'div' );
		caixa.innerHTML = html;

		var enderecos = [];

		Array.prototype.forEach.call( caixa.querySelectorAll( '[style*="background-image"]' ), function ( elemento ) {
			var achado = /url\(['"]?(.*?)['"]?\)/.exec( elemento.getAttribute( 'style' ) );

			if ( achado ) {
				enderecos.push( achado[ 1 ] );
			}
		} );

		Array.prototype.forEach.call( caixa.querySelectorAll( 'img[src]' ), function ( imagem ) {
			enderecos.push( imagem.getAttribute( 'src' ) );
		} );

		if ( ! enderecos.length ) {
			return Promise.resolve( html );
		}

		var carregadas = Promise.all( enderecos.map( function ( endereco ) {
			var imagem = new Image();
			imagem.src = endereco;

			return imagem.decode ? imagem.decode().catch( function () {} ) : Promise.resolve();
		} ) );

		var limite = new Promise( function ( resolve ) {
			window.setTimeout( resolve, 1500 );
		} );

		return Promise.race( [ carregadas, limite ] ).then( function () {
			return html;
		} );
	}

	function carregar( href, registarNoHistorico, daPaginacao ) {
		var pedido = window.fetch( enderecoDoPedido( href ), {
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		} );

		pedidoEmCurso = pedido;
		raiz.classList.add( 'is-a-carregar' );
		conteudo.setAttribute( 'aria-busy', 'true' );

		pedido
			.then( function ( resposta ) {
				if ( ! resposta.ok ) {
					throw new Error( resposta.status );
				}

				return resposta.text();
			} )
			.then( comAsImagensProntas )
			.then( function ( html ) {
				// Um clique mais recente já está a caminho: esta resposta é
				// velha e escrevê-la mostraria a categoria errada.
				if ( pedidoEmCurso !== pedido ) {
					return;
				}

				var antes = ancora();

				conteudo.innerHTML = html;
				manterNoSitio( antes );

				if ( registarNoHistorico ) {
					window.history.pushState( { noticias: true }, '', href );
				}

				devolverFoco( href, daPaginacao );

				if ( daPaginacao ) {
					reenquadrar();
				}
			} )
			.catch( function () {
				// Sem rede, ou o servidor a responder com erro: segue o link
				// como se o script não existisse.
				window.location.href = href;
			} )
			.then( function () {
				if ( pedidoEmCurso !== pedido ) {
					return;
				}

				pedidoEmCurso = null;
				raiz.classList.remove( 'is-a-carregar' );
				conteudo.removeAttribute( 'aria-busy' );
			} );
	}

	/*
	 * Delegado na secção, e não ligado a cada botão: os botões são substituídos
	 * a cada pedido, e ligá-los um a um obrigaria a voltar a ligá-los sempre.
	 */
	raiz.addEventListener( 'click', function ( evento ) {
		// Deixa passar o que o browser trata melhor: abrir noutro separador,
		// gravar, ou o botão do meio.
		if ( evento.defaultPrevented || evento.button !== 0 ||
			evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey ) {
			return;
		}

		var link = evento.target.closest( SELECTOR );

		if ( ! link || ! link.hasAttribute( 'href' ) ) {
			return;
		}

		evento.preventDefault();
		carregar( link.href, true, !! link.closest( '.noticias-paginacao' ) );
	} );

	/*
	 * Os botões de recuar e avançar do browser. O estado não guarda HTML — pede
	 * outra vez, que é o que garante que a lista corresponde ao endereço mesmo
	 * depois de uma notícia ter sido publicada entretanto.
	 */
	window.addEventListener( 'popstate', function () {
		carregar( window.location.href, false );
	} );
}() );
