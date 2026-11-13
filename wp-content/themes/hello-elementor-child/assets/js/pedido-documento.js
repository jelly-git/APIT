/**
 * O pedido de um documento: abre o diálogo, envia Nome, Empresa e Email à rota
 * REST e, com a resposta, começa o download.
 *
 * Abre-se com um botão ou link que leve data-pedir="doc-123" (Anuários) ou o
 * endereço "#pedir-estatutos". A escuta é no documento, por isso funciona nos
 * links do menu e nos botões do Elementor sem lhes mexer.
 *
 * Os campos obrigatórios são verificados aqui e a mensagem vai por baixo de cada
 * um; o servidor volta a verificar tudo (inc/documentos-pedido.php).
 */
( function () {
	'use strict';

	var dialogo = document.getElementById( 'apit-pedido' );

	if ( ! dialogo || ! window.fetch ) {
		return;
	}

	var form = dialogo.querySelector( '.pedido__form' );
	var titulo = dialogo.querySelector( '.pedido__titulo' );
	var estado = dialogo.querySelector( '.pedido__estado' );
	var botao = dialogo.querySelector( '.pedido__submit' );
	var chave = form.elements.chave;
	var origem = null;

	/*
	 * O título do documento, lido do texto da página para vir já traduzido pelo
	 * Weglot: primeiro o que o próprio botão traz escondido (os da lista de
	 * Documentos), depois os do diálogo pela chave (os Estatutos), e por fim o
	 * atributo data-titulo, que o Weglot não traduz, para um botão que o use.
	 */
	function tituloDe( k, gatilho ) {
		var el = gatilho && gatilho.querySelector( '[data-pedido-titulo]' );

		if ( ! el ) {
			el = dialogo.querySelector( '[data-pedido-titulo="' + k + '"]' );
		}

		if ( el && el.textContent.trim() ) {
			return el.textContent.trim();
		}

		return ( gatilho && gatilho.getAttribute( 'data-titulo' ) ) || '';
	}

	function mostrarErros( erros ) {
		form.querySelectorAll( '[data-erro]' ).forEach( function ( el ) {
			var texto = erros && erros[ el.getAttribute( 'data-erro' ) ];

			el.textContent = texto || '';
			el.hidden = ! texto;
		} );

		form.querySelectorAll( 'input' ).forEach( function ( campo ) {
			campo.classList.toggle( 'is-erro', !! ( erros && erros[ campo.name ] ) );
		} );
	}

	function mostrarEstado( texto, erro ) {
		estado.textContent = texto || '';
		estado.hidden = ! texto;
		estado.classList.toggle( 'is-erro', !! erro );
	}

	function abrir( k, gatilho ) {
		form.reset();
		mostrarErros( null );
		mostrarEstado( '' );
		botao.disabled = false;

		chave.value = k;
		titulo.textContent = tituloDe( k, gatilho );
		origem = gatilho || null;

		if ( typeof dialogo.showModal === 'function' ) {
			dialogo.showModal();
		} else {
			dialogo.setAttribute( 'open', '' );
		}
	}

	function fechar() {
		if ( typeof dialogo.close === 'function' ) {
			dialogo.close();
		} else {
			dialogo.removeAttribute( 'open' );
		}

		if ( origem && origem.focus ) {
			origem.focus();
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var a = e.target.closest ? e.target.closest( '[data-pedir], a[href*="#pedir-"]' ) : null;

		if ( ! a ) {
			return;
		}

		var k = a.getAttribute( 'data-pedir' );

		if ( ! k ) {
			k = ( a.getAttribute( 'href' ) || '' ).split( '#pedir-' )[ 1 ] || '';
		}

		if ( ! k ) {
			return;
		}

		e.preventDefault();

		abrir( k, a );
	} );

	dialogo.querySelector( '[data-fechar]' ).addEventListener( 'click', fechar );

	// Um clique no fundo (fora do formulário) também fecha.
	dialogo.addEventListener( 'click', function ( e ) {
		if ( e.target === dialogo ) {
			fechar();
		}
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();

		var erros = {};
		var dados = {
			chave: chave.value,
			nome: form.elements.nome.value.trim(),
			empresa: form.elements.empresa.value.trim(),
			email: form.elements.email.value.trim(),
			consentimento: form.elements.consentimento.checked ? '1' : '',
			apit_site: form.elements.apit_site.value
		};

		if ( ! dados.nome ) {
			erros.nome = 'Indique o seu nome.';
		}

		if ( ! dados.empresa ) {
			erros.empresa = 'Indique a sua empresa.';
		}

		if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( dados.email ) ) {
			erros.email = 'Indique um email válido.';
		}

		if ( ! dados.consentimento ) {
			erros.consentimento = 'É preciso aceitar a Política de Privacidade.';
		}

		mostrarErros( erros );
		mostrarEstado( '' );

		if ( Object.keys( erros ).length ) {
			var primeiro = form.querySelector( 'input.is-erro' );

			if ( primeiro ) {
				primeiro.focus();
			}

			return;
		}

		botao.disabled = true;

		fetch( form.getAttribute( 'data-rota' ), {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
			body: JSON.stringify( dados )
		} )
			.then( function ( r ) {
				return r.json().then( function ( json ) {
					return { ok: r.ok, json: json };
				} );
			} )
			.then( function ( r ) {
				var json = r.json || {};

				if ( ! r.ok || ! json.ok ) {
					mostrarErros( json.erros );
					mostrarEstado( json.mensagem || 'Não foi possível concluir o pedido. Tente mais tarde.', true );
					botao.disabled = false;

					return;
				}

				mostrarEstado( json.mensagem );

				// O link tem Content-Disposition: attachment, por isso a página não sai daqui.
				window.location.href = json.url;

				window.setTimeout( fechar, 2500 );
			} )
			.catch( function () {
				mostrarEstado( 'Não foi possível concluir o pedido. Tente mais tarde.', true );
				botao.disabled = false;
			} );
	} );
}() );
