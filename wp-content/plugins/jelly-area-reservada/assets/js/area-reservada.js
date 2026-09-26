/**
 * Pop-up da Área Reservada: abre com qualquer link para #area-reservada (Login)
 * ou #area-reservada-registo (Registo), troca entre os dois sem sair do sítio e
 * valida os formulários no browser.
 *
 * O Registo envia o pedido para a Área Reservada (inc/registo.php). O Login
 * ainda não entra: uma submissão válida mostra o aviso de que vem aí.
 */
( function () {
	'use strict';

	var modal = document.getElementById( 'apit-ar' );

	if ( ! modal ) {
		return;
	}

	var dialogo = modal.querySelector( '.apit-ar__dialogo' );
	var paineis = modal.querySelectorAll( '[data-ar-painel]' );
	var ANCORAS = {
		'#area-reservada': 'login',
		'#area-reservada-registo': 'registo'
	};
	var origem = null;

	var MENSAGENS = {
		obrigatorio: 'Preencha este campo.',
		email: 'Escreva um e-mail válido, como nome@empresa.pt.',
		telefone: 'Escreva um número de telefone válido.',
		termos: 'Tem de aceitar a Política de Privacidade.'
	};

	/* ---------- Abrir, fechar, trocar ---------- */

	function painelDaAncora( hash ) {
		return Object.prototype.hasOwnProperty.call( ANCORAS, hash ) ? ANCORAS[ hash ] : null;
	}

	function ancoraDoPainel( nome ) {
		return 'registo' === nome ? '#area-reservada-registo' : '#area-reservada';
	}

	// A âncora acompanha o painel, para que o endereço se possa partilhar, sem
	// encher o histórico: o botão Anterior continua a levar à página de antes.
	function escreverAncora( hash ) {
		var url = window.location.pathname + window.location.search + ( hash || '' );
		window.history.replaceState( window.history.state, '', url );
	}

	function mostrarPainel( nome ) {
		Array.prototype.forEach.call( paineis, function ( painel ) {
			painel.hidden = painel.getAttribute( 'data-ar-painel' ) !== nome;
		} );
		dialogo.setAttribute( 'aria-labelledby', 'apit-ar-titulo-' + nome );
		escreverAncora( ancoraDoPainel( nome ) );

		var primeiro = modal.querySelector( '[data-ar-painel="' + nome + '"] input' );
		( primeiro || dialogo ).focus();
	}

	function abrir( nome ) {
		if ( modal.hidden ) {
			/*
			 * Quem tinha o foco, para lho devolver ao fechar. Se o pop-up veio do
			 * menu móvel, esse já se fechou e pôs o foco no hambúrguer — que é
			 * para onde deve voltar, porque o link de onde se partiu desapareceu.
			 */
			origem = document.activeElement && document.activeElement !== document.body ? document.activeElement : null;
			modal.hidden = false;
			document.body.classList.add( 'apit-ar-aberto' );
		}

		mostrarPainel( nome );
	}

	function fechar() {
		if ( modal.hidden ) {
			return;
		}

		modal.hidden = true;
		document.body.classList.remove( 'apit-ar-aberto' );
		escreverAncora( '' );
		Array.prototype.forEach.call( modal.querySelectorAll( '[data-ar-form]' ), repor );

		if ( origem && document.contains( origem ) ) {
			origem.focus();
		}
		origem = null;
	}

	/*
	 * Um só ouvinte para a página inteira: apanha os links do cabeçalho, do
	 * Elementor, dos campos ACF e os de dentro do próprio pop-up, que trocam de
	 * painel. `href` é lido como está escrito, e só a parte da âncora conta —
	 * "/sobre-apit/#area-reservada" também abre aqui.
	 */
	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest ? e.target.closest( 'a[href*="#area-reservada"]' ) : null;

		if ( link ) {
			var href = link.getAttribute( 'href' );
			var nome = painelDaAncora( href.slice( href.indexOf( '#' ) ) );

			if ( nome ) {
				e.preventDefault();
				abrir( nome );
			}
			return;
		}

		if ( e.target.closest && e.target.closest( '[data-ar-fechar]' ) && modal.contains( e.target ) ) {
			fechar();
		}
	} );

	/* Links diretos e e-mails: a página abre já com o pop-up. */
	function lerAncora() {
		var nome = painelDaAncora( window.location.hash );

		if ( nome ) {
			abrir( nome );
		}
	}

	window.addEventListener( 'hashchange', lerAncora );
	lerAncora();

	/* ---------- Teclado: Escape fecha, Tab não sai do pop-up ---------- */

	var FOCAVEIS = 'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])';

	document.addEventListener( 'keydown', function ( e ) {
		if ( modal.hidden ) {
			return;
		}

		if ( 'Escape' === e.key ) {
			fechar();
			return;
		}

		if ( 'Tab' !== e.key ) {
			return;
		}

		var lista = Array.prototype.filter.call( dialogo.querySelectorAll( FOCAVEIS ), function ( el ) {
			return el.offsetParent !== null;
		} );

		if ( ! lista.length ) {
			return;
		}

		var primeiro = lista[ 0 ];
		var ultimo = lista[ lista.length - 1 ];

		if ( e.shiftKey && ( document.activeElement === primeiro || document.activeElement === dialogo ) ) {
			e.preventDefault();
			ultimo.focus();
		} else if ( ! e.shiftKey && document.activeElement === ultimo ) {
			e.preventDefault();
			primeiro.focus();
		}
	} );

	/* ---------- Mostrar a palavra-passe ---------- */

	Array.prototype.forEach.call( modal.querySelectorAll( '[data-ar-ver-senha]' ), function ( botao ) {
		botao.addEventListener( 'click', function () {
			var campo = botao.parentNode.querySelector( 'input' );
			var visivel = 'password' === campo.type;

			campo.type = visivel ? 'text' : 'password';
			botao.setAttribute( 'aria-pressed', visivel ? 'true' : 'false' );
			botao.setAttribute( 'aria-label', visivel ? 'Esconder palavra-passe' : 'Mostrar palavra-passe' );
			botao.firstElementChild.className = visivel ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
		} );
	} );

	/* ---------- Validação ---------- */

	function erroDoCampo( campo ) {
		var valor = 'checkbox' === campo.type ? campo.checked : campo.value.trim();

		if ( 'termos' === campo.name ) {
			return valor ? '' : MENSAGENS.termos;
		}

		if ( campo.required && ! valor ) {
			return MENSAGENS.obrigatorio;
		}

		if ( ! valor ) {
			return '';
		}

		if ( 'email' === campo.type && ! /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test( valor ) ) {
			return MENSAGENS.email;
		}

		if ( 'tel' === campo.type && ! /^\+?[\d\s().-]{9,20}$/.test( valor ) ) {
			return MENSAGENS.telefone;
		}

		return '';
	}

	// A mensagem fica por baixo do campo e presa a ele por aria-describedby,
	// para o leitor de ecrã a ler quando o foco lá chega.
	function marcar( campo, mensagem ) {
		var bloco = campo.closest( '.apit-ar__campo' );
		var id = campo.id + '-erro';
		var erro = document.getElementById( id );

		if ( ! mensagem ) {
			bloco.classList.remove( 'is-erro' );
			campo.removeAttribute( 'aria-invalid' );
			campo.removeAttribute( 'aria-describedby' );
			if ( erro ) {
				erro.remove();
			}
			return;
		}

		if ( ! erro ) {
			erro = document.createElement( 'p' );
			erro.className = 'apit-ar__erro';
			erro.id = id;
			bloco.appendChild( erro );
		}

		erro.textContent = mensagem;
		bloco.classList.add( 'is-erro' );
		campo.setAttribute( 'aria-invalid', 'true' );
		campo.setAttribute( 'aria-describedby', id );
	}

	function repor( form ) {
		form.reset();
		form.hidden = false;
		Array.prototype.forEach.call( form.elements, function ( campo ) {
			if ( campo.name && campo.closest( '.apit-ar__campo' ) ) {
				marcar( campo, '' );
			}
		} );

		var aviso = form.querySelector( '[data-ar-aviso]' );
		var sucesso = form.parentNode.querySelector( '[data-ar-sucesso]' );

		if ( aviso ) {
			aviso.hidden = true;
		}
		if ( sucesso ) {
			sucesso.hidden = true;
			form.closest( '[data-ar-painel]' ).classList.remove( 'is-enviado' );
		}
	}

	Array.prototype.forEach.call( modal.querySelectorAll( '[data-ar-form]' ), function ( form ) {
		var campos = Array.prototype.filter.call( form.elements, function ( campo ) {
			return campo.name && campo.closest( '.apit-ar__campo' );
		} );

		// Depois do primeiro erro, cada campo corrige-se à medida que se escreve.
		campos.forEach( function ( campo ) {
			campo.addEventListener( 'input', function () {
				if ( campo.hasAttribute( 'aria-invalid' ) ) {
					marcar( campo, erroDoCampo( campo ) );
				}
			} );
			campo.addEventListener( 'change', function () {
				if ( campo.hasAttribute( 'aria-invalid' ) ) {
					marcar( campo, erroDoCampo( campo ) );
				}
			} );
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			var primeiroErro = null;

			campos.forEach( function ( campo ) {
				var mensagem = erroDoCampo( campo );
				marcar( campo, mensagem );
				if ( mensagem && ! primeiroErro ) {
					primeiroErro = campo;
				}
			} );

			if ( primeiroErro ) {
				primeiroErro.focus();
				return;
			}

			if ( 'registo' === form.getAttribute( 'data-ar-form' ) ) {
				enviar( form, campos );
				return;
			}

			// O Login ainda não tem contas contra as quais entrar.
			avisar( form, 'A área reservada estará disponível em breve.', false );
		} );
	} );

	function avisar( form, texto, erro ) {
		var aviso = form.querySelector( '[data-ar-aviso]' );

		if ( ! aviso ) {
			return;
		}

		aviso.textContent = texto;
		aviso.classList.toggle( 'is-erro', !! erro );
		aviso.hidden = ! texto;
	}

	function mostrarSucesso( form ) {
		var sucesso = form.parentNode.querySelector( '[data-ar-sucesso]' );
		var email = sucesso.querySelector( '[data-ar-sucesso-email]' );
		var foco = sucesso.querySelector( '[data-ar-sucesso-foco]' );

		// O e-mail para onde vai a mensagem, para a pessoa saber onde a procurar.
		if ( email && form.email ) {
			email.textContent = form.email.value.trim();
		}

		form.hidden = true;
		sucesso.hidden = false;
		form.closest( '[data-ar-painel]' ).classList.add( 'is-enviado' );

		// O foco no título, que o leitor de ecrã lê primeiro.
		( foco || sucesso ).focus();
	}

	/*
	 * O Registo vai para o servidor (inc/registo.php), que responde em JSON:
	 * { sucesso }, { erros: { campo: mensagem } } ou { mensagem }. Os erros dos
	 * campos ficam por baixo de cada um, como os do browser; uma mensagem geral,
	 * por baixo do botão.
	 */
	function enviar( form, campos ) {
		var botao = form.querySelector( '[type="submit"]' );
		var rotulo = botao.querySelector( '[data-ar-rotulo]' );
		var texto = rotulo ? rotulo.textContent : '';
		var FALHOU = 'Não foi possível enviar o pedido. Verifique a ligação e tente de novo.';

		if ( 'true' === botao.getAttribute( 'aria-busy' ) ) {
			return;
		}

		botao.setAttribute( 'aria-busy', 'true' );
		botao.disabled = true;
		if ( rotulo ) {
			rotulo.textContent = 'A enviar…';
		}
		avisar( form, '', false );

		function terminar() {
			botao.removeAttribute( 'aria-busy' );
			botao.disabled = false;
			if ( rotulo ) {
				rotulo.textContent = texto;
			}
		}

		/*
		 * O endereço lê-se do atributo: o formulário tem um campo chamado
		 * "action" (o do WordPress), e form.action devolve esse campo, não o
		 * endereço — o pedido ia para "[object HTMLInputElement]".
		 */
		var destino = form.getAttribute( 'action' );

		fetch( destino, {
			method: 'POST',
			body: new FormData( form ),
			credentials: 'same-origin'
		} )
			.then( function ( resposta ) {
				/*
				 * Uma resposta que não é JSON é um erro do servidor (uma página de
				 * erro, um aviso do PHP antes do JSON): a mensagem diz o código, e
				 * o princípio da resposta fica na consola, para se ver o que foi.
				 */
				return resposta.clone().json().catch( function () {
					return resposta.text().then( function ( corpo ) {
						window.console.error( 'Área Reservada: resposta inesperada do registo (' + resposta.status + ')', corpo.slice( 0, 500 ) );

						return { mensagem: 'O servidor não conseguiu tratar o pedido (erro ' + resposta.status + '). Tente de novo daqui a pouco.' };
					} );
				} );
			} )
			.then( function ( dados ) {
				terminar();

				if ( dados && dados.sucesso ) {
					mostrarSucesso( form );
					return;
				}

				if ( dados && dados.erros ) {
					var primeiro = null;

					campos.forEach( function ( campo ) {
						var mensagem = dados.erros[ campo.name ] || '';
						marcar( campo, mensagem );
						if ( mensagem && ! primeiro ) {
							primeiro = campo;
						}
					} );

					if ( primeiro ) {
						primeiro.focus();
						return;
					}
				}

				avisar( form, ( dados && dados.mensagem ) || FALHOU, true );
			} )
			// A ligação falhou, ou o browser não deixou ler a resposta: o motivo fica na consola.
			.catch( function ( erro ) {
				window.console.error( 'Área Reservada: o registo não chegou ao servidor', destino, erro );
				terminar();
				avisar( form, FALHOU, true );
			} );
	}

	// Links que ainda não levam a lado nenhum, como a recuperação da palavra-passe.
	Array.prototype.forEach.call( modal.querySelectorAll( '[data-ar-em-breve]' ), function ( link ) {
		link.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			var form = modal.querySelector( '[data-ar-form="login"]' );

			if ( form ) {
				avisar( form, 'A recuperação da palavra-passe estará disponível em breve.', false );
			}
		} );
	} );
}() );
