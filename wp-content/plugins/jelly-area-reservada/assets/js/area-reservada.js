/**
 * Pop-up da Área Reservada: abre com qualquer link para #area-reservada
 * (Login), #area-reservada-registo (Registo), #area-reservada-recuperar
 * (Esqueceu-se da palavra-passe?) ou #area-reservada-senha (Definir a
 * palavra-passe, a partir da ligação dos e-mails). Troca entre os painéis sem
 * sair do sítio e valida os formulários no browser.
 *
 * Os quatro formulários vão para o servidor pelo admin-ajax (inc/registo.php e
 * inc/sessao.php), sem recarregar a página.
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
		'#area-reservada-registo': 'registo',
		'#area-reservada-recuperar': 'recuperar',
		'#area-reservada-senha': 'senha'
	};
	var origem = null;

	// O mínimo de caracteres de uma palavra-passe: o mesmo que o servidor (JELLY_AR_SENHA_MINIMO).
	var SENHA_MINIMO = 10;

	var MENSAGENS = {
		obrigatorio: 'Preencha este campo.',
		email: 'Escreva um e-mail válido, como nome@empresa.pt.',
		telefone: 'Escreva um número de telefone válido.',
		termos: 'Tem de aceitar a Política de Privacidade.',
		senhaCurta: 'A palavra-passe tem de ter pelo menos ' + SENHA_MINIMO + ' caracteres.',
		senhaDiferente: 'As duas palavras-passe não são iguais.'
	};

	/* ---------- Abrir, fechar, trocar ---------- */

	/*
	 * A marcação de mesa tem uma âncora por evento, #area-reservada-marcar-<id>
	 * (o botão dos cartões do calendário); o evento fica em marcarEvento.
	 */
	var MARCAR = /^#area-reservada-marcar-(\d+)$/;
	var marcarEvento = null;
	/*
	 * O dia e a hora já escolhidos fora do pop-up (um horário livre na página do
	 * evento da ARU, data-ar-dia e data-ar-hora): o pop-up abre com eles, e só
	 * falta a mesa. Servem uma vez.
	 */
	var marcarEscolhido = null;

	function painelDaAncora( hash ) {
		var achado = MARCAR.exec( hash );

		if ( achado ) {
			marcarEvento = achado[ 1 ];
			return 'marcar';
		}

		return Object.prototype.hasOwnProperty.call( ANCORAS, hash ) ? ANCORAS[ hash ] : null;
	}

	function ancoraDoPainel( nome ) {
		if ( 'marcar' === nome ) {
			return '#area-reservada-marcar-' + marcarEvento;
		}

		var ancora = Object.keys( ANCORAS ).filter( function ( a ) {
			return ANCORAS[ a ] === nome;
		} )[ 0 ];

		return ancora || '#area-reservada';
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

		// A marcação pede ao servidor o que é do evento (mais abaixo).
		if ( 'marcar' === nome ) {
			carregarMarcacao( marcarEvento );
		}
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

	/*
	 * Na ARU (body.aru-corpo), a página mostra as marcações e os horários: depois
	 * de uma marcação feita no pop-up, fechar recarrega-a, para já aparecer.
	 */
	var recarregarAoFechar = false;

	function fechar() {
		if ( modal.hidden ) {
			return;
		}

		if ( recarregarAoFechar ) {
			escreverAncora( '' );
			window.location.reload();
			return;
		}

		modal.hidden = true;
		document.body.classList.remove( 'apit-ar-aberto' );
		escreverAncora( '' );
		// Fechado sem entrar, a marcação à espera do login deixa de estar.
		guardarPendente( null );
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
				marcarEscolhido = 'marcar' === nome && link.hasAttribute( 'data-ar-dia' )
					? { dia: link.getAttribute( 'data-ar-dia' ), hora: link.getAttribute( 'data-ar-hora' ) }
					: null;
				abrir( nome );
			}
			return;
		}

		if ( e.target.closest && e.target.closest( '[data-ar-fechar]' ) && modal.contains( e.target ) ) {
			fechar();
		}
	} );

	/* Links diretos e e-mails: a página abre já com o pop-up (a primeira leitura está no fim do script). */
	function lerAncora() {
		var nome = painelDaAncora( window.location.hash );

		if ( nome ) {
			abrir( nome );
		}
	}

	window.addEventListener( 'hashchange', lerAncora );

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

		// Definir a palavra-passe: o mínimo, e a repetição igual à primeira.
		if ( 'senha' === campo.name && campo.value.length < SENHA_MINIMO ) {
			return MENSAGENS.senhaCurta;
		}

		if ( 'senha2' === campo.name && campo.form.senha && campo.value !== campo.form.senha.value ) {
			return MENSAGENS.senhaDiferente;
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

			// Todos os formulários do pop-up vão para o servidor (inc/registo.php e inc/sessao.php).
			enviar( form, campos );
		} );
	} );

	/*
	 * O que cada formulário faz quando o servidor diz que correu bem: o login
	 * segue para o destino que o servidor indica; os outros mostram a sua
	 * confirmação. Depois de definir a palavra-passe, a chave sai do endereço —
	 * já não serve, e não deve ficar no histórico nem ser partilhada.
	 */
	function concluir( form, dados ) {
		var tipo = form.getAttribute( 'data-ar-form' );

		if ( 'login' === tipo ) {
			/*
			 * Entrou a meio de uma marcação: fica nesta página e volta a ela,
			 * agora com a sessão. A página recarrega (o cabeçalho e o pop-up
			 * mudam com a sessão) e a âncora abre outra vez a marcação.
			 */
			var pendente = lerPendente();

			if ( pendente ) {
				guardarPendente( null );
				window.history.replaceState( window.history.state, '', window.location.pathname + window.location.search + '#area-reservada-marcar-' + pendente );
				window.location.reload();
				return;
			}

			window.location.assign( dados.destino || window.location.href.split( '#' )[ 0 ] );
			return;
		}

		if ( 'senha' === tipo ) {
			window.history.replaceState( window.history.state, '', window.location.pathname + window.location.hash );
		}

		mostrarSucesso( form );
	}

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
			rotulo.textContent = 'Por favor aguarde';
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
					concluir( form, dados );
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

	/*
	 * "Entrar", depois de definir a palavra-passe: abre o login (pelo ouvinte
	 * das âncoras, acima) com o e-mail já escrito, e o foco na palavra-passe.
	 */
	modal.addEventListener( 'click', function ( e ) {
		var link = e.target.closest ? e.target.closest( '[data-ar-entrar-com]' ) : null;
		var login = modal.querySelector( '[data-ar-form="login"]' );

		if ( ! link || ! login ) {
			return;
		}

		window.setTimeout( function () {
			login.user_login.value = link.getAttribute( 'data-ar-entrar-com' );
			login.user_pass.focus();
		}, 0 );
	} );

	/* ---------- Marcação de mesa ---------- */

	/*
	 * O botão dos cartões do calendário abre este painel. O servidor diz o que
	 * mostrar (inc/marcacoes.php): sem sessão, o login — e a marcação fica
	 * guardada para depois de entrar; com a sessão de um associado, os dias, as
	 * horas e as mesas com lugar, ou a marcação que já tem neste evento.
	 *
	 * A escolha é em três passos — dia, hora, mesa — e cada um só mostra o que
	 * ainda tem lugar. Os lugares voltam a ser verificados no servidor.
	 */
	var painelMarcar = modal.querySelector( '[data-ar-marcar]' );
	var CHAVE_PENDENTE = 'apit-ar-marcar';

	// A marcação à espera do login. O sessionStorage pode não existir (modo privado): sem ele, só não se volta à marcação.
	function lerPendente() {
		try {
			return window.sessionStorage.getItem( CHAVE_PENDENTE );
		} catch ( e ) {
			return null;
		}
	}

	function guardarPendente( id ) {
		try {
			if ( id ) {
				window.sessionStorage.setItem( CHAVE_PENDENTE, id );
			} else {
				window.sessionStorage.removeItem( CHAVE_PENDENTE );
			}
		} catch ( e ) {}
	}

	function el( tag, classe, texto ) {
		var e = document.createElement( tag );

		if ( classe ) {
			e.className = classe;
		}
		if ( undefined !== texto ) {
			e.textContent = texto;
		}

		return e;
	}

	function plural( n, um, varios ) {
		return n + ' ' + ( 1 === n ? um : varios );
	}

	function parte( nome ) {
		return painelMarcar.querySelector( '[data-ar-marcar-' + nome + ']' );
	}

	// Mostra só uma das partes do painel: carregar, mensagem, form ou feita.
	function mostrarParte( nome ) {
		[ 'carregar', 'mensagem', 'form', 'feita' ].forEach( function ( p ) {
			parte( p ).hidden = p !== nome;
		} );
	}

	/*
	 * A marcação feita — a de agora, ou a que já existia: a faixa do estado, o
	 * cartão com a data, as horas e a mesa, e os passos do pedido.
	 */
	function mostrarFeita( evento, m, agora ) {
		var aprovada = 'aprovada' === m.estado;
		var titulo = painelMarcar.querySelector( '[data-ar-marcar-feita-titulo]' );
		var local = painelMarcar.querySelector( '[data-ar-bilhete-local]' );
		var texto = function ( seletor, valor ) {
			painelMarcar.querySelector( seletor ).textContent = valor || '';
		};

		/* A faixa do estado: confirmada a turquesa, à espera a roxo. */
		painelMarcar.querySelector( '[data-ar-marcar-estado]' ).className = 'apit-ar__marcada-estado apit-ar__marcada-estado--' + ( aprovada ? 'aprovada' : 'pendente' );
		painelMarcar.querySelector( '[data-ar-marcar-icone]' ).className = aprovada || agora ? 'fa-solid fa-check' : 'fa-solid fa-hourglass-half';
		titulo.textContent = aprovada ? 'Mesa confirmada' : ( agora ? 'Pedido enviado' : 'Pedido em análise' );
		// "e‑mail" com o hífen que não parte: no telemóvel ficava "e-" numa linha e "mail" na outra.
		texto( '[data-ar-marcar-feita-texto]', aprovada
			? 'A marcação está confirmada. Os dados seguiram também por e‑mail.'
			: 'O pedido aguarda aprovação da APIT. A confirmação segue por e‑mail.' );

		/* O cartão da marcação. */
		texto( '[data-ar-bilhete-mes]', m.mes );
		texto( '[data-ar-bilhete-dia]', m.dia );
		texto( '[data-ar-bilhete-ano]', m.ano );
		texto( '[data-ar-bilhete-semana]', m.semana );
		texto( '[data-ar-bilhete-horas]', m.horas );
		texto( '[data-ar-bilhete-mesa]', m.mesa_nome || m.mesa );
		local.hidden = ! m.mesa_local;
		local.querySelector( 'span' ).textContent = m.mesa_local || '';

		/*
		 * Os passos: o pedido está sempre feito; a aprovação é o passo atual até
		 * a APIT decidir; aprovada, os três ficam feitos, com o visto em vez do
		 * número.
		 */
		var feitos = aprovada ? 3 : 1;

		Array.prototype.forEach.call( painelMarcar.querySelectorAll( '[data-ar-passo]' ), function ( li ) {
			var n = parseInt( li.getAttribute( 'data-ar-passo' ), 10 );
			var marca = li.querySelector( '.apit-ar__passo-marca' );

			li.className = n <= feitos ? 'is-feito' : ( n === feitos + 1 ? 'is-atual' : '' );
			marca.textContent = '';

			if ( n <= feitos ) {
				marca.appendChild( el( 'i', 'fa-solid fa-check' ) );
			} else {
				marca.textContent = n;
			}
		} );

		mostrarParte( 'feita' );
		titulo.focus();
	}

	/* A escolha: os dias, as horas de um dia, as mesas de uma hora. */
	var escolha = { dias: [], dia: null, hora: null };

	function blocoAtual() {
		var dia = escolha.dias.filter( function ( d ) {
			return d.dia === escolha.dia;
		} )[ 0 ];

		return dia ? dia.blocos.filter( function ( b ) {
			return b.hora === escolha.hora;
		} )[ 0 ] : null;
	}

	function livresNoBloco( b ) {
		return b.mesas.reduce( function ( soma, m ) {
			return soma + m.livres;
		}, 0 );
	}

	// Uma opção em pílula: um radio escondido e o rótulo desenhado.
	function opcao( nome, valor, texto, extra, desligada, escolhida ) {
		var rotulo = el( 'label', 'apit-ar__opcao' + ( desligada ? ' is-desligada' : '' ) );
		var radio = el( 'input' );

		radio.type = 'radio';
		radio.name = nome;
		radio.value = valor;
		radio.disabled = !! desligada;
		radio.checked = !! escolhida;

		rotulo.appendChild( radio );
		rotulo.appendChild( el( 'span', 'apit-ar__opcao-texto', texto ) );
		if ( extra ) {
			rotulo.appendChild( el( 'small', 'apit-ar__opcao-extra', extra ) );
		}

		return rotulo;
	}

	function desenharEscolha() {
		var dias = parte( 'dias' );
		var horas = parte( 'horas' );
		var mesas = parte( 'mesas' );
		var dia = escolha.dias.filter( function ( d ) {
			return d.dia === escolha.dia;
		} )[ 0 ];
		var bloco = blocoAtual();

		dias.textContent = '';
		escolha.dias.forEach( function ( d ) {
			var livres = d.blocos.reduce( function ( s, b ) {
				return s + livresNoBloco( b );
			}, 0 );

			// É uma marcação por dia: num dia em que já marcou, o dia fica fechado (d.minha, inc/marcacoes.php).
			dias.appendChild( opcao( 'dia', d.dia, d.rotulo, d.minha ? 'Já marcado' : ( livres ? '' : 'Completo' ), d.minha || ! livres, d.dia === escolha.dia ) );
		} );

		horas.textContent = '';
		( dia ? dia.blocos : [] ).forEach( function ( b ) {
			var livres = livresNoBloco( b );

			horas.appendChild( opcao( 'hora', b.hora, b.hora, '', ! livres, b.hora === escolha.hora ) );
		} );

		mesas.textContent = '';
		parte( 'sem-hora' ).hidden = !! bloco;

		( bloco ? bloco.mesas : [] ).forEach( function ( m ) {
			var cartao = opcao(
				'mesa',
				m.id,
				m.nome,
				m.livres ? ( escolha.lugares ? plural( m.livres, 'lugar livre', 'lugares livres' ) : 'Livre' ) : ( escolha.lugares ? 'Completa' : 'Marcada' ),
				! m.livres,
				1 === bloco.mesas.filter( function ( x ) {
					return x.livres;
				} ).length && m.livres
			);

			cartao.classList.add( 'apit-ar__mesa' );
			if ( m.localizacao ) {
				cartao.insertBefore( el( 'small', 'apit-ar__mesa-local', m.localizacao ), cartao.querySelector( '.apit-ar__opcao-extra' ) );
			}
			mesas.appendChild( cartao );
		} );

		acertarResumo();
	}

	function acertarResumo() {
		var form = parte( 'form' );
		var mesa = form.querySelector( 'input[name="mesa"]:checked' );
		var bloco = blocoAtual();
		var dia = escolha.dias.filter( function ( d ) {
			return d.dia === escolha.dia;
		} )[ 0 ];

		form.querySelector( '[data-ar-marcar-enviar]' ).disabled = ! mesa;
		parte( 'resumo' ).textContent = mesa && bloco
			? dia.rotulo + ' · ' + bloco.hora + '–' + bloco.fim + ' · ' + mesa.parentNode.querySelector( '.apit-ar__opcao-texto' ).textContent
			: '';
	}

	function mostrarEscolha( dados ) {
		var form = parte( 'form' );

		escolha.dias = dados.dias || [];
		// Sem lugares por mesa, cada mesa diz só se está livre (inc/marcacoes.php).
		if ( undefined !== dados.lugares ) {
			escolha.lugares = !! dados.lugares;
		}
		form.evento.value = dados.evento.id;
		if ( dados.nonce ) {
			form._wpnonce.value = dados.nonce;
		}

		if ( ! escolha.dias.length ) {
			mostrarMensagem( 'De momento, não há horários disponíveis para marcação neste evento.' );
			return;
		}

		// O primeiro dia com lugar e sem marcação sua, se o escolhido não servir.
		var dia = escolha.dias.filter( function ( d ) {
			return d.dia === escolha.dia;
		} )[ 0 ];

		if ( ! dia || dia.minha ) {
			escolha.dia = ( escolha.dias.filter( function ( d ) {
				return ! d.minha && d.blocos.some( function ( b ) {
					return livresNoBloco( b ) > 0;
				} );
			} )[ 0 ] || escolha.dias[ 0 ] ).dia;
			escolha.hora = null;
		}

		var bloco = blocoAtual();
		if ( bloco && ! livresNoBloco( bloco ) ) {
			escolha.hora = null;
		}

		desenharEscolha();
		mostrarParte( 'form' );
	}

	function mostrarMensagem( texto ) {
		parte( 'mensagem' ).textContent = texto;
		mostrarParte( 'mensagem' );
	}

	function carregarMarcacao( id ) {
		var ajax = painelMarcar.getAttribute( 'data-ar-ajax' );

		escolha = { dias: [], dia: marcarEscolhido ? marcarEscolhido.dia : null, hora: marcarEscolhido ? marcarEscolhido.hora : null };
		marcarEscolhido = null;
		parte( 'titulo' ).textContent = 'Marcar mesa';
		parte( 'evento' ).textContent = '';
		parte( 'erro' ).hidden = true;
		mostrarParte( 'carregar' );

		fetch( ajax + '?action=jelly_ar_marcacao_dados&evento=' + encodeURIComponent( id ), { credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( dados ) {
				// Sem sessão: o login, e depois de entrar volta-se aqui.
				if ( ! dados.sessao ) {
					guardarPendente( id );
					mostrarPainel( 'login' );
					avisar( modal.querySelector( '[data-ar-form="login"]' ), 'Para marcar uma mesa, é necessário iniciar sessão na Área Reservada.', false );
					return;
				}

				if ( dados.evento ) {
					parte( 'titulo' ).textContent = dados.evento.titulo;
					parte( 'evento' ).textContent = [ dados.evento.datas, dados.evento.local ].filter( Boolean ).join( ' · ' );
				}

				if ( dados.mensagem ) {
					mostrarMensagem( dados.mensagem );
				} else if ( dados.marcacao ) {
					mostrarFeita( dados.evento, dados.marcacao, false );
				} else {
					mostrarEscolha( dados );
				}
			} )
			.catch( function ( erro ) {
				window.console.error( 'Área Reservada: a marcação não carregou', erro );
				mostrarMensagem( 'Não foi possível carregar os horários. Verifique a ligação e tente de novo.' );
			} );
	}

	if ( painelMarcar ) {
		var formMarcar = parte( 'form' );

		formMarcar.addEventListener( 'change', function ( e ) {
			var campo = e.target;

			parte( 'erro' ).hidden = true;

			if ( 'dia' === campo.name ) {
				escolha.dia = campo.value;
				escolha.hora = null;
				desenharEscolha();
			} else if ( 'hora' === campo.name ) {
				escolha.hora = campo.value;
				desenharEscolha();
			} else {
				acertarResumo();
			}
		} );

		formMarcar.addEventListener( 'submit', function ( e ) {
			var botao = formMarcar.querySelector( '[data-ar-marcar-enviar]' );
			var rotulo = botao.querySelector( '[data-ar-rotulo]' );
			var texto = rotulo.textContent;

			e.preventDefault();

			if ( botao.disabled || 'true' === botao.getAttribute( 'aria-busy' ) ) {
				return;
			}

			botao.setAttribute( 'aria-busy', 'true' );
			botao.disabled = true;
			rotulo.textContent = 'Por favor aguarde';

			function terminar() {
				botao.removeAttribute( 'aria-busy' );
				rotulo.textContent = texto;
				acertarResumo();
			}

			fetch( formMarcar.getAttribute( 'action' ), {
				method: 'POST',
				body: new FormData( formMarcar ),
				credentials: 'same-origin'
			} )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( dados ) {
					terminar();

					if ( dados.sucesso ) {
						recarregarAoFechar = document.body.classList.contains( 'aru-corpo' );
						mostrarFeita( { titulo: parte( 'titulo' ).textContent }, dados.marcacao, true );
						return;
					}

					if ( false === dados.sessao ) {
						guardarPendente( formMarcar.evento.value );
						mostrarPainel( 'login' );
						return;
					}

					// Os lugares mudaram: a escolha volta a desenhar-se com os de agora.
					if ( dados.dias ) {
						escolha.dias = dados.dias;
						mostrarEscolha( { dias: dados.dias, evento: { id: formMarcar.evento.value } } );
					}

					parte( 'erro' ).textContent = dados.mensagem || 'Não foi possível fazer o pedido.';
					parte( 'erro' ).hidden = false;
				} )
				.catch( function ( erro ) {
					window.console.error( 'Área Reservada: o pedido de marcação não chegou ao servidor', erro );
					terminar();
					parte( 'erro' ).textContent = 'Não foi possível enviar o pedido. Verifique a ligação e tente de novo.';
					parte( 'erro' ).hidden = false;
				} );
		} );
	}

	/*
	 * Links diretos e e-mails: a página abre já com o pop-up. Só no fim, com
	 * tudo o que os painéis usam já definido — a marcação também.
	 */
	lerAncora();
}() );
