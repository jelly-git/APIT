/**
 * Back-office da Área Reservada: pesquisa e tamanho da página na lista de
 * utilizadores, edição dos dados do registo, a confirmação das ações de peso e
 * o menu lateral no telemóvel.
 *
 * Nesta fase nada é gravado: aprovar, rejeitar, suspender e guardar mudam só
 * o ecrã, para se ver como fica.
 */
( function () {
	'use strict';

	var raiz = document.querySelector( '[data-jar]' );

	if ( ! raiz ) {
		return;
	}

	var NOMES = {
		ativo: 'Ativo',
		suspenso: 'Suspenso',
		rejeitado: 'Rejeitado',
		publicado: 'Publicado',
		rascunho: 'Rascunho'
	};

	function etiqueta( estado ) {
		var el = document.createElement( 'span' );
		el.className = 'jar-estado jar-estado--' + estado;
		el.textContent = NOMES[ estado ] || estado;
		return el;
	}

	/* ---------- Pesquisa e tamanho da página ---------- */

	/*
	 * A pesquisa é feita no servidor, em todas as páginas; aqui só se envia o
	 * formulário sozinho, meio segundo depois de se parar de escrever, e o
	 * campo volta com o foco e o cursor no fim, para se continuar a escrever.
	 */
	Array.prototype.forEach.call( raiz.querySelectorAll( 'form[data-jar-pesquisa]' ), function ( form ) {
		var campo = form.querySelector( 'input[type="search"]' );
		var inicial = campo.value;
		var espera = null;

		function enviar() {
			var valor = campo.value.trim();

			// Uma letra só não chega para pesquisar; apagar tudo, sim.
			if ( valor === inicial.trim() || ( valor.length === 1 ) ) {
				return;
			}
			form.submit();
		}

		campo.addEventListener( 'input', function () {
			clearTimeout( espera );
			espera = setTimeout( enviar, 500 );
		} );

		// O X do campo de pesquisa limpa e mostra logo todos.
		campo.addEventListener( 'search', enviar );

		if ( inicial ) {
			campo.focus();
			campo.setSelectionRange( inicial.length, inicial.length );
		}
	} );

	Array.prototype.forEach.call( raiz.querySelectorAll( 'form[data-jar-auto] select' ), function ( select ) {
		select.addEventListener( 'change', function () {
			select.form.submit();
		} );
	} );

	/* ---------- Mudar o estado, na lista ou no perfil ---------- */

	function aplicarEstado( origem, estado ) {
		if ( ! estado ) {
			return;
		}

		var linha = origem.closest( '[data-jar-linha]' );

		if ( linha ) {
			var celula = linha.querySelector( '[data-jar-estado]' );
			celula.innerHTML = '';
			celula.appendChild( etiqueta( estado ) );
			Array.prototype.forEach.call( linha.querySelectorAll( '[data-jar-decidir], [data-jar-confirmar]' ), function ( b ) {
				b.remove();
			} );
			return;
		}

		var perfil = raiz.querySelector( '[data-jar-estado-perfil]' );

		if ( perfil ) {
			perfil.innerHTML = '';
			perfil.appendChild( etiqueta( estado ) );
			origem.hidden = true;
		}
	}

	// Aprovar na lista não pede confirmação: é o que se faz todos os dias.
	raiz.addEventListener( 'click', function ( e ) {
		var botao = e.target.closest( '[data-jar-decidir]' );

		if ( botao ) {
			aplicarEstado( botao, botao.getAttribute( 'data-jar-decidir' ) );
		}
	} );

	/* ---------- Confirmação ---------- */

	var caixa = raiz.querySelector( '[data-jar-confirmacao]' );
	var pendente = null;

	function fecharConfirmacao() {
		caixa.hidden = true;
		if ( pendente ) {
			pendente.focus();
		}
		pendente = null;
	}

	if ( caixa ) {
		var sim = caixa.querySelector( '[data-jar-confirmar-sim]' );

		raiz.addEventListener( 'click', function ( e ) {
			var botao = e.target.closest( '[data-jar-confirmar]' );

			if ( ! botao ) {
				return;
			}

			pendente = botao;
			caixa.querySelector( '#jar-confirmar-titulo' ).textContent = botao.getAttribute( 'data-titulo' );
			caixa.querySelector( '#jar-confirmar-texto' ).textContent = botao.getAttribute( 'data-texto' );
			sim.textContent = botao.getAttribute( 'data-sim' );
			caixa.hidden = false;

			// O foco começa no Cancelar: um Enter distraído não suspende ninguém.
			caixa.querySelector( '.jar-confirmar__acoes [data-jar-confirmar-nao]' ).focus();
		} );

		Array.prototype.forEach.call( caixa.querySelectorAll( '[data-jar-confirmar-nao]' ), function ( el ) {
			el.addEventListener( 'click', fecharConfirmacao );
		} );

		sim.addEventListener( 'click', function () {
			var botao = pendente;
			var form = botao.getAttribute( 'data-jar-form' );

			pendente = null;
			caixa.hidden = true;

			// Uma ação que já grava (apagar um documento) envia o seu formulário.
			if ( form && document.getElementById( form ) ) {
				document.getElementById( form ).submit();
				return;
			}

			aplicarEstado( botao, botao.getAttribute( 'data-resultado' ) );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( caixa.hidden ) {
				return;
			}

			if ( 'Escape' === e.key ) {
				fecharConfirmacao();
			} else if ( 'Tab' === e.key ) {
				// Só dois botões: o Tab anda entre eles.
				var botoes = caixa.querySelectorAll( '.jar-confirmar__acoes button' );
				var outro = document.activeElement === botoes[ 0 ] ? botoes[ 1 ] : botoes[ 0 ];
				e.preventDefault();
				outro.focus();
			}
		} );
	}

	/* ---------- Editar: um cartão de dados, ou uma linha de uma tabela ---------- */

	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-editavel]' ), function ( cartao ) {
		var editar = cartao.querySelector( '[data-jar-editar]' );
		var leitura = cartao.querySelector( '[data-jar-leitura]' );
		var form = cartao.querySelector( '[data-jar-edicao]' );

		function modo( editando ) {
			leitura.hidden = editando;
			form.hidden = ! editando;
			editar.hidden = editando;
			cartao.classList.toggle( 'is-a-editar', editando );
		}

		function abrir() {
			modo( true );
			form.querySelector( 'input:not([type="hidden"]), select, textarea' ).focus();
		}

		editar.addEventListener( 'click', abrir );

		// Voltou com um erro do servidor: a edição reabre, para se corrigir.
		if ( cartao.hasAttribute( 'data-jar-abrir' ) ) {
			abrir();
		}

		// Cancelar devolve os campos ao que estava antes de se começar a editar.
		cartao.querySelector( '[data-jar-cancelar]' ).addEventListener( 'click', function () {
			form.reset();
			modo( false );
			editar.focus();
		} );

		form.addEventListener( 'submit', function ( e ) {
			if ( ! form.reportValidity() ) {
				e.preventDefault();
				return;
			}

			// Um formulário que grava de verdade segue para o servidor.
			if ( form.hasAttribute( 'data-jar-gravar' ) ) {
				return;
			}

			e.preventDefault();

			Array.prototype.forEach.call( form.elements, function ( campo ) {
				var valor = campo.name && leitura.querySelector( '[data-jar-valor="' + campo.name + '"]' );

				if ( valor ) {
					// De uma lista de escolha mostra-se o nome da opção, não o valor.
					var texto = 'SELECT' === campo.tagName && campo.value ? campo.options[ campo.selectedIndex ].text : campo.value.trim();
					valor.textContent = texto || '—';
					// O novo valor passa a ser o de partida de um próximo Cancelar.
					campo.defaultValue = campo.value;
				}
			} );

			modo( false );
			editar.focus();
		} );
	} );

	/* ---------- Cores de uma categoria de eventos ---------- */

	// A amostra ao lado das duas cores mostra o gradiente à medida que se escolhem.
	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-cores]' ), function ( grupo ) {
		var amostra = grupo.querySelector( '[data-jar-amostra]' );

		function pintar() {
			Array.prototype.forEach.call( grupo.querySelectorAll( '[data-jar-cor]' ), function ( cor ) {
				amostra.style.setProperty( '--jar-cat-' + cor.getAttribute( 'data-jar-cor' ), cor.value );
			} );
		}

		grupo.addEventListener( 'input', pintar );

		// O Cancelar repõe as cores; a amostra volta com elas.
		var form = grupo.closest( 'form' );
		if ( form ) {
			form.addEventListener( 'reset', function () {
				setTimeout( pintar );
			} );
		}
	} );

	/* ---------- Opções que mostram mais campos ---------- */

	/*
	 * Uma caixa com data-jar-revela="<id>" mostra esse bloco quando marcada e
	 * esconde-o quando não. O Cancelar da edição repõe a caixa, e o bloco
	 * acompanha-a.
	 */
	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-revela]' ), function ( caixa ) {
		var bloco = document.getElementById( caixa.getAttribute( 'data-jar-revela' ) );

		if ( ! bloco ) {
			return;
		}

		function acertar() {
			bloco.hidden = ! caixa.checked;
		}

		caixa.addEventListener( 'change', acertar );

		if ( caixa.form ) {
			caixa.form.addEventListener( 'reset', function () {
				setTimeout( acertar );
			} );
		}

		acertar();
	} );

	/* ---------- Datas de início e fim ---------- */

	// O fim não pode ser antes do início: o seletor do fim começa onde o início está.
	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-inicio]' ), function ( inicio ) {
		var fim = inicio.form && inicio.form.querySelector( '[data-jar-fim]' );

		if ( ! fim ) {
			return;
		}

		function acertar() {
			fim.min = inicio.value;
		}

		inicio.addEventListener( 'change', acertar );
		acertar();
	} );

	/* ---------- Zona de largar um ficheiro ---------- */

	/*
	 * A zona é o <label> do campo de ficheiro: carregar nela já abre o seletor.
	 * Aqui junta-se o arrastar e largar, e o nome do ficheiro escolhido.
	 */
	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-largar]' ), function ( zona ) {
		var campo = zona.querySelector( 'input[type="file"]' );
		var nome = zona.querySelector( '[data-jar-largar-nome]' );
		var form = zona.closest( 'form' );
		var maximo = form ? parseInt( form.getAttribute( 'data-jar-maximo' ), 10 ) : 0;
		var original = nome.textContent;

		/*
		 * Um ficheiro maior do que o servidor aceita é recusado já aqui, em vez
		 * de se esperar pelo envio inteiro para ouvir o mesmo.
		 */
		function mostrar() {
			var ficheiro = campo.files[ 0 ];

			zona.classList.remove( 'is-escolhido', 'is-erro' );
			campo.setCustomValidity( '' );

			if ( ! ficheiro ) {
				nome.textContent = original;
				return;
			}

			if ( maximo && ficheiro.size > maximo ) {
				var mb = Math.round( maximo / 1048576 );
				campo.setCustomValidity( 'O ficheiro é maior do que ' + mb + ' MB.' );
				nome.textContent = ficheiro.name + ' — maior do que ' + mb + ' MB';
				zona.classList.add( 'is-erro' );
				return;
			}

			nome.textContent = ficheiro.name;
			zona.classList.add( 'is-escolhido' );
		}

		campo.addEventListener( 'change', mostrar );

		// O Cancelar da edição limpa o campo; a zona volta ao texto de partida.
		if ( form ) {
			form.addEventListener( 'reset', function () {
				setTimeout( mostrar );
			} );
		}

		[ 'dragenter', 'dragover' ].forEach( function ( tipo ) {
			zona.addEventListener( tipo, function ( e ) {
				e.preventDefault();
				zona.classList.add( 'is-por-cima' );
			} );
		} );

		[ 'dragleave', 'drop' ].forEach( function ( tipo ) {
			zona.addEventListener( tipo, function () {
				zona.classList.remove( 'is-por-cima' );
			} );
		} );

		zona.addEventListener( 'drop', function ( e ) {
			e.preventDefault();
			if ( e.dataTransfer.files.length ) {
				campo.files = e.dataTransfer.files;
				mostrar();
			}
		} );
	} );

	/* ---------- Menu lateral no telemóvel ---------- */

	var abrir = raiz.querySelector( '[data-jar-menu]' );

	if ( abrir ) {
		abrir.addEventListener( 'click', function () {
			var aberto = raiz.classList.toggle( 'is-menu-aberto' );
			abrir.setAttribute( 'aria-expanded', aberto ? 'true' : 'false' );
		} );
	}
}() );
