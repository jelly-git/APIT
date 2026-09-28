/**
 * Back-office da Área Reservada: pesquisa e tamanho da página na lista de
 * utilizadores, edição dos dados do registo, a confirmação das ações de peso e
 * o menu lateral no telemóvel.
 *
 * Nos utilizadores reais, aprovar, rejeitar, suspender e reativar enviam um
 * formulário ao servidor (inc/utilizadores.php); nos de exemplo, e ao guardar
 * os dados do registo, mudam ainda só o ecrã.
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
	 * Os horários das mesas: as horas de início e de fim (data-jar-hora) vão ao
	 * passo do intervalo (data-jar-intervalo) — com 30 minutos, 10:00, 10:30…
	 * Trocar o intervalo refaz as listas; uma hora que deixe de caber passa à
	 * anterior que cabe. O mesmo passo que o servidor verifica
	 * (jelly_ar_passo_horas(), inc/mesas-dados.php): 45 minutos vai de 15 em 15.
	 */
	/*
	 * Gravar os horários sem dias escolhidos não avança: aparece o alerta. Com
	 * dias por escolher, pergunta-se antes, dizendo quais — pode ser de
	 * propósito (um dia de montagem), mas não deve passar sem se ver. A
	 * confirmação envia o formulário pelo submit(), que não volta a passar por
	 * aqui.
	 */
	var horarios = raiz.querySelector( '[data-jar-horarios]' );

	if ( horarios ) {
		var nenhum = horarios.querySelector( '[data-jar-horarios-nenhum]' );
		var perguntar = horarios.querySelector( '[data-jar-horarios-confirmar]' );
		var caixasDias = horarios.querySelectorAll( '[data-jar-dia]' );

		// Escolher um dia tira o alerta.
		Array.prototype.forEach.call( caixasDias, function ( c ) {
			c.addEventListener( 'change', function () {
				nenhum.hidden = true;
			} );
		} );

		horarios.addEventListener( 'submit', function ( e ) {
			var faltam = Array.prototype.filter.call( caixasDias, function ( c ) {
				return ! c.checked;
			} ).map( function ( c ) {
				return c.getAttribute( 'data-jar-dia' );
			} );

			if ( faltam.length === caixasDias.length ) {
				e.preventDefault();
				nenhum.hidden = false;
				nenhum.scrollIntoView( { block: 'nearest' } );
				return;
			}

			if ( faltam.length ) {
				e.preventDefault();
				perguntar.setAttribute(
					'data-texto',
					( 1 === faltam.length ? faltam[ 0 ] + ' não tem horário' : faltam.slice( 0, -1 ).join( ', ' ) + ' e ' + faltam[ faltam.length - 1 ] + ' não têm horário' ) +
						': os associados não podem marcar nesses dias. Os outros dias ficam gravados.'
				);
				perguntar.click();
			}
		} );
	}

	var intervaloHoras = raiz.querySelector( '[data-jar-intervalo]' );

	if ( intervaloHoras ) {
		var passoDe = function ( intervalo ) {
			return 45 === intervalo ? 15 : Math.max( 15, intervalo );
		};
		var hhmm = function ( m ) {
			return ( '0' + Math.floor( m / 60 ) ).slice( -2 ) + ':' + ( '0' + ( m % 60 ) ).slice( -2 );
		};

		intervaloHoras.addEventListener( 'change', function () {
			var passo = passoDe( parseInt( intervaloHoras.value, 10 ) );

			Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-hora]' ), function ( select ) {
				var partes = select.value.split( ':' );
				var atual = parseInt( partes[ 0 ], 10 ) * 60 + parseInt( partes[ 1 ], 10 );
				var escolhida = hhmm( Math.floor( atual / passo ) * passo );

				select.innerHTML = '';
				for ( var m = 0; m < 24 * 60; m += passo ) {
					select.appendChild( new Option( hhmm( m ), hhmm( m ), false, hhmm( m ) === escolhida ) );
				}
			} );
		} );
	}

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

	/* ---------- Procura dentro de uma lista de escolha ---------- */

	/*
	 * Um campo com data-jar-filtrar="<id da lista>" esconde, enquanto se
	 * escreve, as opções da lista cujo data-jar-filtrar-texto não tem o que se
	 * procura — sem distinguir maiúsculas nem acentos. Só esconde: as caixas
	 * marcadas continuam marcadas e vão com o formulário.
	 */
	function semAcentos( texto ) {
		return texto.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).toLowerCase();
	}

	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-filtrar]' ), function ( campo ) {
		var lista = document.getElementById( campo.getAttribute( 'data-jar-filtrar' ) );

		if ( ! lista ) {
			return;
		}

		var opcoes = lista.querySelectorAll( '[data-jar-filtrar-texto]' );
		var nada = lista.querySelector( '[data-jar-filtrar-nada]' );

		function filtrar() {
			var procura = semAcentos( campo.value.trim() );
			var vistas = 0;

			Array.prototype.forEach.call( opcoes, function ( opcao ) {
				var mostra = ! procura || -1 !== semAcentos( opcao.getAttribute( 'data-jar-filtrar-texto' ) ).indexOf( procura );

				opcao.hidden = ! mostra;
				vistas += mostra ? 1 : 0;
			} );

			if ( nada ) {
				nada.hidden = vistas > 0;
			}
		}

		campo.addEventListener( 'input', filtrar );

		// O Enter na procura não grava o formulário à volta.
		campo.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key ) {
				e.preventDefault();
			}
		} );

		// Cancelar faz reset ao formulário, que limpa a procura: a lista volta inteira.
		if ( campo.form ) {
			campo.form.addEventListener( 'reset', function () {
				window.setTimeout( filtrar, 0 );
			} );
		}
	} );

	/* ---------- A janela de um bloco da grelha ---------- */

	/*
	 * Um clique num bloco (data-jar-bloco) abre a janela com as marcações dele
	 * e os associados que se podem marcar (templates/admin/mesas.php). Os
	 * dados vêm do #jar-grelha-dados. Aqui só se ajuda a escolher — os lugares
	 * livres, quem já está marcado a essa hora; as regras voltam a ser
	 * verificadas no servidor (inc/mesas-dados.php).
	 */
	var janela = raiz.querySelector( '[data-jar-bloco-janela]' );
	var dadosGrelha = document.getElementById( 'jar-grelha-dados' );

	if ( janela && dadosGrelha ) {
		var grelha = JSON.parse( dadosGrelha.textContent );
		var caixaJanela = janela.querySelector( '.jar-janela__caixa' );
		var em = function ( seletor ) {
			return janela.querySelector( seletor );
		};
		var partes = {
			marcacoes: em( '[data-jar-bloco-marcacoes]' ),
			marcar: em( '[data-jar-bloco-marcar]' )
		};
		var lista = em( '[data-jar-bloco-lista]' );
		var destino = em( '[data-jar-bloco-destino]' );
		var mover = em( '[data-jar-bloco-mover]' );
		var remover = em( '[data-jar-bloco-remover]' );
		var confirmar = em( '[data-jar-bloco-confirmar]' );
		var escolhidos = em( '[data-jar-bloco-escolhidos]' );
		var procura = em( '#jar-associados-procura' );
		var associados = janela.querySelectorAll( '[data-jar-associado]' );
		var origem = null;
		var livres = 0;

		var marcacoesDe = function ( mesa, dia, hora ) {
			var porDia = ( grelha.blocos[ mesa ] || {} )[ dia ] || {};

			return porDia[ hora ] || [];
		};

		var mesaDe = function ( id ) {
			return grelha.mesas.filter( function ( m ) {
				return m.id === id;
			} )[ 0 ];
		};

		var plural = function ( n, um, varios ) {
			return n + ' ' + ( 1 === n ? um : varios );
		};

		// "10:00" + 30 → "10:30".
		var somar = function ( hora, minutos ) {
			var p = hora.split( ':' );
			var t = parseInt( p[ 0 ], 10 ) * 60 + parseInt( p[ 1 ], 10 ) + minutos;

			return ( '0' + Math.floor( t / 60 ) ).slice( -2 ) + ':' + ( '0' + ( t % 60 ) ).slice( -2 );
		};

		// "Ana Silva" → "AS", como o jelly_ar_iniciais() do PHP.
		var iniciais = function ( nome ) {
			return ( nome || '?' ).trim().split( /\s+/ ).filter( Boolean ).slice( 0, 2 ).map( function ( p ) {
				return p.charAt( 0 ).toUpperCase();
			} ).join( '' );
		};

		var elemento = function ( tag, classe, texto ) {
			var el = document.createElement( tag );

			if ( classe ) {
				el.className = classe;
			}
			if ( undefined !== texto ) {
				el.textContent = texto;
			}

			return el;
		};

		// Mudar só com marcações escolhidas e um destino com lugar para todas; remover, com alguma escolhida.
		var acertarMarcacoes = function () {
			var n = lista.querySelectorAll( 'input:checked' ).length;
			var opcao = destino.options[ destino.selectedIndex ];

			remover.disabled = 0 === n;
			destino.disabled = 0 === n;
			mover.disabled = 0 === n || ! opcao || ! opcao.value || parseInt( opcao.getAttribute( 'data-livres' ), 10 ) < n;

			Array.prototype.forEach.call( lista.querySelectorAll( 'li' ), function ( li ) {
				// Com uma marcação por horário, ela está sempre escolhida: não se destaca.
				li.classList.toggle( 'is-escolhido', grelha.lugares && li.querySelector( 'input' ).checked );
			} );
		};

		// Marcar até aos lugares livres: chegado ao limite, as outras caixas fecham.
		var acertarMarcar = function () {
			var n = 0;

			Array.prototype.forEach.call( associados, function ( a ) {
				n += a.querySelector( 'input' ).checked ? 1 : 0;
			} );

			Array.prototype.forEach.call( associados, function ( a ) {
				var caixa = a.querySelector( 'input' );

				// Com lugares, as caixas fecham no limite; com uma marcação por horário é um radio, e troca-se à vontade.
				if ( grelha.lugares && ! a.hasAttribute( 'data-jar-ocupado' ) ) {
					caixa.disabled = ! caixa.checked && n >= livres;
				}
				a.classList.toggle( 'is-escolhido', caixa.checked );
				a.classList.toggle( 'is-fechado', caixa.disabled );
			} );

			if ( confirmar ) {
				confirmar.disabled = 0 === n;
				escolhidos.textContent = grelha.lugares
					? plural( n, 'escolhido', 'escolhidos' ) + ' de ' + plural( livres, 'lugar livre', 'lugares livres' )
					: ( n ? 'Associado escolhido' : 'Escolha o associado a marcar.' );
			}
		};

		var abrirBloco = function ( botao ) {
			var mesa = mesaDe( parseInt( botao.getAttribute( 'data-mesa' ), 10 ) );
			var dia = botao.getAttribute( 'data-dia' );
			var hora = botao.getAttribute( 'data-hora' );
			var aqui = marcacoesDe( mesa.id, dia, hora );
			var lugares = em( '[data-jar-bloco-lugares]' );
			var local = em( '[data-jar-bloco-local]' );

			origem = botao;
			livres = Math.max( 0, mesa.lugares - aqui.length );

			/* A cabeça. */
			em( '#jar-bloco-titulo' ).textContent = mesa.nome;
			em( '[data-jar-bloco-quando]' ).textContent = grelha.dias[ dia ].rotulo + ' · ' + hora + ' – ' + somar( hora, grelha.dias[ dia ].intervalo );
			local.hidden = ! mesa.localizacao;
			local.querySelector( 'span' ).textContent = mesa.localizacao || '';
			em( '[data-jar-bloco-icone]' ).className = 'jar-icone jar-icone--' + ( livres ? 'turquesa' : 'magenta' );

			/*
			 * A ocupação. Com lugares, o número e um traço por lugar; com uma
			 * marcação por horário, só se o horário está livre ou marcado.
			 */
			lugares.textContent = '';
			lugares.hidden = ! grelha.lugares;

			if ( grelha.lugares ) {
				em( '[data-jar-bloco-ocupados]' ).textContent = aqui.length + '/' + mesa.lugares;
				em( '[data-jar-bloco-ocupados-texto]' ).textContent = livres
					? ( 1 === mesa.lugares ? 'lugar ocupado' : 'lugares ocupados' ) + ' · ' + plural( livres, 'livre', 'livres' )
					: 'lugares ocupados · bloco completo';
				for ( var i = 0; i < mesa.lugares; i++ ) {
					lugares.appendChild( elemento( 'span', 'jar-lugar jar-lugar--' + ( aqui[ i ] ? aqui[ i ].estado : 'livre' ) ) );
				}
			} else {
				em( '[data-jar-bloco-ocupados]' ).textContent = aqui.length ? 'Horário marcado' : 'Horário livre';
				em( '[data-jar-bloco-ocupados-texto]' ).textContent = aqui.length
					? '· ' + ( grelha.estados[ aqui[ 0 ].estado ] || aqui[ 0 ].estado )
					: '· disponível para marcar';
			}

			/* As marcações do bloco. */
			partes.marcacoes.hidden = 0 === aqui.length;
			em( '[data-jar-bloco-conta]' ).textContent = aqui.length;
			em( '[data-jar-bloco-conta]' ).hidden = ! grelha.lugares;
			lista.textContent = '';

			aqui.forEach( function ( m ) {
				var li = elemento( 'li' );
				var rotulo = elemento( 'label', 'jar-janela__pessoa' );
				var caixa = elemento( 'input' );
				var texto = elemento( 'span', 'jar-janela__pessoa-texto' );

				caixa.type = 'checkbox';
				caixa.name = 'marcacao[]';
				caixa.value = m.id;

				// Uma marcação por horário: é essa que se muda ou remove, sem ter de a escolher.
				if ( ! grelha.lugares ) {
					caixa.checked = true;
					caixa.hidden = true;
				}
				texto.appendChild( elemento( 'strong', '', m.quem || '—' ) );
				texto.appendChild( elemento( 'small', '', m.empresa || '' ) );

				rotulo.appendChild( caixa );
				rotulo.appendChild( elemento( 'span', 'jar-avatar', iniciais( m.quem ) ) );
				rotulo.appendChild( texto );
				rotulo.appendChild( elemento( 'span', 'jar-estado jar-estado--' + m.estado, grelha.estados[ m.estado ] || m.estado ) );
				li.appendChild( rotulo );

				// O caminho desta marcação: quem a fez, mudou ou aprovou, e quando (inc/historico.php).
				var passos = ( grelha.historico || {} )[ m.id ] || [];
				if ( passos.length ) {
					var historico = elemento( 'ol', 'jar-janela__historico' );

					passos.forEach( function ( p ) {
						var linha = elemento( 'li' );

						linha.appendChild( elemento( 'strong', '', p.rotulo ) );
						linha.appendChild( document.createTextNode( ' · ' + p.autor + ' · ' + p.quando ) );
						historico.appendChild( linha );
					} );
					li.appendChild( historico );
				}

				lista.appendChild( li );
			} );

			// Os blocos para onde se pode mudar: os outros, com lugares livres, dia a dia.
			destino.textContent = '';
			destino.appendChild( new Option( grelha.lugares ? 'Mudar para outro bloco…' : 'Mudar para outro horário…', '' ) );

			Object.keys( grelha.dias ).forEach( function ( d ) {
				var grupo = document.createElement( 'optgroup' );

				grupo.label = grelha.dias[ d ].rotulo;

				grelha.dias[ d ].blocos.forEach( function ( h ) {
					grelha.mesas.forEach( function ( outra ) {
						var vagos = outra.lugares - marcacoesDe( outra.id, d, h ).length;
						var opcao;

						if ( ( outra.id === mesa.id && d === dia && h === hora ) || vagos < 1 ) {
							return;
						}

						// Sem lugares, o destino é só a hora e a mesa: se aparece, está livre.
						opcao = new Option( h + ' · ' + outra.nome + ( grelha.lugares ? ' (' + plural( vagos, 'livre', 'livres' ) + ')' : '' ), outra.id + '|' + d + '|' + h );
						opcao.setAttribute( 'data-livres', vagos );
						grupo.appendChild( opcao );
					} );
				} );

				if ( grupo.children.length ) {
					destino.appendChild( grupo );
				}
			} );

			acertarMarcacoes();

			/* Marcar associados. */
			partes.marcar.querySelector( '[name="mesa"]' ).value = mesa.id;
			partes.marcar.querySelector( '[name="dia"]' ).value = dia;
			partes.marcar.querySelector( '[name="hora"]' ).value = hora;
			em( '[data-jar-bloco-livres]' ).textContent = livres ? ( grelha.lugares ? plural( livres, 'lugar livre', 'lugares livres' ) : 'Livre' ) : '';
			em( '[data-jar-bloco-livres]' ).hidden = ! livres;
			em( '[data-jar-bloco-cheio]' ).hidden = livres > 0;
			em( '[data-jar-bloco-escolha]' ).hidden = ! livres;
			// Com uma marcação por horário, um horário marcado não tem mais nada a marcar: a parte sai (a faixa de cima já o diz).
			partes.marcar.hidden = ! grelha.lugares && ! livres;

			// Quem já está marcado a esta hora, nesta mesa ou noutra.
			var nesta = aqui.map( function ( m ) {
				return m.user_id;
			} );
			var aEstaHora = [];

			grelha.mesas.forEach( function ( outra ) {
				marcacoesDe( outra.id, dia, hora ).forEach( function ( m ) {
					aEstaHora.push( m.user_id );
				} );
			} );

			Array.prototype.forEach.call( associados, function ( a ) {
				var id = parseInt( a.getAttribute( 'data-jar-associado' ), 10 );
				var caixa = a.querySelector( 'input' );
				var nota = a.querySelector( '[data-jar-associado-nota]' );
				var ocupado = -1 !== aEstaHora.indexOf( id );

				caixa.checked = false;
				caixa.disabled = ocupado;
				a.toggleAttribute( 'data-jar-ocupado', ocupado );
				// Quem já está neste bloco está na lista de cima: aqui, sai.
				a.classList.toggle( 'is-neste', -1 !== nesta.indexOf( id ) );
				nota.hidden = ! ocupado;
				nota.textContent = 'Noutra mesa a esta hora';
			} );

			// A lista inteira outra vez: a procura da vez anterior sai.
			if ( procura ) {
				procura.value = '';
				procura.dispatchEvent( new Event( 'input' ) );
			}

			acertarMarcar();

			janela.hidden = false;
			em( '#jar-bloco-titulo' ).focus();
		};

		var fecharBloco = function () {
			janela.hidden = true;

			if ( origem ) {
				origem.focus();
			}
			origem = null;
		};

		raiz.addEventListener( 'click', function ( e ) {
			var botao = e.target.closest( '[data-jar-bloco]' );

			if ( botao ) {
				abrirBloco( botao );
			}
		} );

		Array.prototype.forEach.call( janela.querySelectorAll( '[data-jar-bloco-fechar]' ), function ( el ) {
			el.addEventListener( 'click', fecharBloco );
		} );

		lista.addEventListener( 'change', acertarMarcacoes );
		destino.addEventListener( 'change', acertarMarcacoes );
		partes.marcar.addEventListener( 'change', acertarMarcar );

		document.addEventListener( 'keydown', function ( e ) {
			if ( janela.hidden ) {
				return;
			}

			if ( 'Escape' === e.key ) {
				fecharBloco();
				return;
			}

			// O Tab fica dentro da janela.
			if ( 'Tab' === e.key ) {
				var focaveis = Array.prototype.filter.call(
					caixaJanela.querySelectorAll( 'a, button, input, select, [tabindex]' ),
					function ( el ) {
						return ! el.disabled && el.tabIndex >= 0 && 'hidden' !== el.type && null !== el.offsetParent;
					}
				);
				var primeiro = focaveis[ 0 ];
				var ultimo = focaveis[ focaveis.length - 1 ];

				if ( e.shiftKey && document.activeElement === primeiro ) {
					e.preventDefault();
					ultimo.focus();
				} else if ( ! e.shiftKey && document.activeElement === ultimo ) {
					e.preventDefault();
					primeiro.focus();
				}
			}
		} );
	}

	/* ---------- Escolher várias linhas de uma lista ---------- */

	/*
	 * Um formulário data-jar-escolhas (as Aprovações): a caixa de cima escolhe
	 * todas as linhas; os botões data-jar-precisa-escolha só se ligam com alguma
	 * escolhida; e a barra diz quantas.
	 */
	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-escolhas]' ), function ( form ) {
		var todas = form.querySelector( '[data-jar-escolhas-todas]' );
		var caixas = form.querySelectorAll( '[data-jar-escolha]' );
		var conta = form.querySelector( '[data-jar-escolhas-conta]' );
		var barra = form.querySelector( '[data-jar-escolhas-barra]' );
		var inicial = conta ? conta.textContent : '';

		function acertar() {
			var n = form.querySelectorAll( '[data-jar-escolha]:checked' ).length;

			Array.prototype.forEach.call( form.querySelectorAll( '[data-jar-precisa-escolha]' ), function ( b ) {
				b.disabled = 0 === n;
			} );

			Array.prototype.forEach.call( caixas, function ( c ) {
				c.closest( 'tr' ).classList.toggle( 'is-escolhida', c.checked );
			} );

			if ( todas ) {
				todas.checked = n > 0 && n === caixas.length;
				todas.indeterminate = n > 0 && n < caixas.length;
			}

			if ( conta ) {
				conta.textContent = n ? n + ( 1 === n ? ' pedido escolhido' : ' pedidos escolhidos' ) : inicial;
			}

			if ( barra ) {
				barra.classList.toggle( 'is-ativa', n > 0 );
			}
		}

		if ( todas ) {
			todas.addEventListener( 'change', function () {
				Array.prototype.forEach.call( caixas, function ( c ) {
					c.checked = todas.checked;
				} );
				acertar();
			} );
		}

		form.addEventListener( 'change', function ( e ) {
			if ( e.target.hasAttribute( 'data-jar-escolha' ) ) {
				acertar();
			}
		} );

		acertar();
	} );

	/* ---------- Calendário: escolher o dia ---------- */

	/*
	 * Um clique num dia da grelha (data-jar-calendario-dia) mostra as
	 * marcações desse dia, que já vêm todas na página
	 * (templates/admin/calendario.php).
	 */
	var calendario = raiz.querySelector( '[data-jar-calendario]' );

	if ( calendario ) {
		calendario.addEventListener( 'click', function ( e ) {
			var botao = e.target.closest( '[data-jar-calendario-dia]' );

			if ( ! botao ) {
				return;
			}

			var dia = botao.getAttribute( 'data-jar-calendario-dia' );

			Array.prototype.forEach.call( calendario.querySelectorAll( '[data-jar-calendario-dia]' ), function ( b ) {
				var este = b === botao;

				b.classList.toggle( 'is-escolhido', este );
				b.setAttribute( 'aria-pressed', este ? 'true' : 'false' );
			} );

			Array.prototype.forEach.call( calendario.querySelectorAll( '[data-jar-calendario-lista]' ), function ( l ) {
				l.hidden = l.getAttribute( 'data-jar-calendario-lista' ) !== dia;
			} );

			// No telemóvel a lista fica por baixo da grelha: vai-se até ela.
			if ( window.matchMedia( '(max-width: 900px)' ).matches ) {
				calendario.querySelector( '[data-jar-calendario-lista="' + dia + '"]' ).scrollIntoView( { behavior: 'smooth', block: 'start' } );
			}
		} );
	}

	/* ---------- Contar os escolhidos de uma lista ---------- */

	/*
	 * Uma nota data-jar-contar="<id da lista>" diz quantas caixas da lista
	 * estão marcadas, e quais — também as que a procura esconde (o Novo
	 * documento, com os eventos).
	 */
	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-contar]' ), function ( nota ) {
		var lista = document.getElementById( nota.getAttribute( 'data-jar-contar' ) );
		var texto = nota.querySelector( '[data-jar-contar-texto]' );

		if ( ! lista || ! texto ) {
			return;
		}

		function contar() {
			var nomes = Array.prototype.map.call( lista.querySelectorAll( 'input:checked' ), function ( c ) {
				var rotulo = c.closest( 'label' ).querySelector( 'span' );

				// Só o título, sem as datas por baixo.
				return rotulo ? rotulo.firstChild.textContent.trim() : '';
			} );

			texto.textContent = ! nomes.length
				? 'Nenhum evento escolhido: o documento fica só na lista de Documentos.'
				: ( 1 === nomes.length ? '1 evento escolhido: ' : nomes.length + ' eventos escolhidos: ' ) + nomes.join( ', ' );
			nota.classList.toggle( 'tem-escolhidos', nomes.length > 0 );
		}

		lista.addEventListener( 'change', contar );
		contar();
	} );

	/* ---------- O gráfico do Painel ---------- */

	/*
	 * A atividade do mês (templates/admin/painel.php, data-jar-grafico): uma
	 * linha por série, desenhada em SVG ao tamanho do cartão — e outra vez
	 * quando ele muda de tamanho. Ao passar o rato, ao tocar ou com as setas,
	 * uma linha vertical marca o dia e a dica mostra os valores dele; a legenda
	 * mostra ou esconde cada série.
	 */
	var SVG = 'http://www.w3.org/2000/svg';

	function no( tag, atributos ) {
		var e = document.createElementNS( SVG, tag );

		Object.keys( atributos || {} ).forEach( function ( a ) {
			e.setAttribute( a, atributos[ a ] );
		} );

		return e;
	}

	/*
	 * Uma curva suave que passa por todos os pontos sem os ultrapassar
	 * (interpolação monótona): não inventa picos nem desce abaixo de zero
	 * entre dois dias sem nada.
	 */
	function curva( p ) {
		var n = p.length;
		var d = [];
		var m = [];
		var i;

		if ( n < 2 ) {
			return '';
		}

		for ( i = 0; i < n - 1; i++ ) {
			d[ i ] = ( p[ i + 1 ][ 1 ] - p[ i ][ 1 ] ) / ( p[ i + 1 ][ 0 ] - p[ i ][ 0 ] );
		}
		m[ 0 ] = d[ 0 ];
		m[ n - 1 ] = d[ n - 2 ];
		for ( i = 1; i < n - 1; i++ ) {
			m[ i ] = d[ i - 1 ] * d[ i ] <= 0 ? 0 : ( d[ i - 1 ] + d[ i ] ) / 2;
		}
		for ( i = 0; i < n - 1; i++ ) {
			if ( 0 === d[ i ] ) {
				m[ i ] = 0;
				m[ i + 1 ] = 0;
			}
		}

		var caminho = 'M' + p[ 0 ][ 0 ] + ',' + p[ 0 ][ 1 ];
		for ( i = 0; i < n - 1; i++ ) {
			var h = ( p[ i + 1 ][ 0 ] - p[ i ][ 0 ] ) / 3;
			caminho += ' C' + ( p[ i ][ 0 ] + h ) + ',' + ( p[ i ][ 1 ] + m[ i ] * h ) + ' ' + ( p[ i + 1 ][ 0 ] - h ) + ',' + ( p[ i + 1 ][ 1 ] - m[ i + 1 ] * h ) + ' ' + p[ i + 1 ][ 0 ] + ',' + p[ i + 1 ][ 1 ];
		}

		return caminho;
	}

	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-grafico]' ), function ( figura ) {
		var dados = JSON.parse( figura.querySelector( '[data-jar-grafico-dados]' ).textContent );
		var area = figura.querySelector( '[data-jar-grafico-area]' );
		var dica = figura.querySelector( '[data-jar-grafico-dica]' );
		var visiveis = {};
		var atual = null;
		var geo = null;

		/*
		 * O mês vai do dia 1 ao último; os dias que ainda não chegaram (futuro)
		 * ficam no eixo mas sem linha, e não se apontam. `ultimo` é o último dia
		 * com dados — hoje, no mês corrente.
		 */
		var ultimo = 0;

		function acertarUltimo() {
			ultimo = dados.dias.length - 1;
			while ( ultimo > 0 && dados.dias[ ultimo ].futuro ) {
				ultimo--;
			}
		}
		acertarUltimo();

		dados.series.forEach( function ( s ) {
			visiveis[ s.chave ] = true;
		} );

		function desenhar() {
			var largura = area.clientWidth;
			var altura = 190;
			var margem = { cima: 12, direita: 8, baixo: 26, esquerda: 30 };
			var dias = dados.dias;
			var ativas = dados.series.filter( function ( s ) {
				return visiveis[ s.chave ];
			} );

			// A escala: o maior valor das séries à vista, arredondado para cima a um número redondo.
			var maior = 1;
			ativas.forEach( function ( s ) {
				dias.forEach( function ( d ) {
					maior = Math.max( maior, d[ s.chave ] );
				} );
			} );
			var passo = maior <= 4 ? 1 : Math.ceil( maior / 4 );
			var topo = passo * Math.ceil( maior / passo );

			var x = function ( i ) {
				return margem.esquerda + i * ( largura - margem.esquerda - margem.direita ) / ( dias.length - 1 );
			};
			var y = function ( v ) {
				return margem.cima + ( 1 - v / topo ) * ( altura - margem.cima - margem.baixo );
			};

			var svg = no( 'svg', { width: largura, height: altura, viewBox: '0 0 ' + largura + ' ' + altura, 'aria-hidden': 'true' } );
			var defs = no( 'defs' );

			// A escala à esquerda, com as linhas de fundo.
			for ( var v = 0; v <= topo; v += passo ) {
				svg.appendChild( no( 'line', { class: 'jar-grafico__grelha', x1: margem.esquerda, x2: largura - margem.direita, y1: y( v ), y2: y( v ) } ) );
				var numero = no( 'text', { class: 'jar-grafico__eixo', x: margem.esquerda - 8, y: y( v ) + 4, 'text-anchor': 'end' } );
				numero.textContent = v;
				svg.appendChild( numero );
			}

			// As datas por baixo: o dia 1 e de semana a semana (8, 15, 22, 29), e o último dia — "Hoje", no mês de hoje.
			dias.forEach( function ( d, i ) {
				var fimDoMes = i === dias.length - 1;

				if ( 0 === i % 7 && dias.length - 1 - i >= 3 || fimDoMes ) {
					var data = no( 'text', { class: 'jar-grafico__eixo', x: x( i ), y: altura - 6, 'text-anchor': fimDoMes ? 'end' : ( 0 === i ? 'start' : 'middle' ) } );
					data.textContent = d.hoje ? 'Hoje' : d.curto;
					svg.appendChild( data );
				}
			} );

			// Cada série: a área (esbatida) e a linha, do dia 1 até ao último dia com dados.
			ativas.forEach( function ( s, n ) {
				var pontos = dias.slice( 0, ultimo + 1 ).map( function ( d, i ) {
					return [ x( i ), y( d[ s.chave ] ) ];
				} );
				var linha = curva( pontos );
				var id = 'jar-grafico-grad-' + s.chave;
				var grad = no( 'linearGradient', { id: id, x1: 0, y1: 0, x2: 0, y2: 1 } );

				grad.appendChild( no( 'stop', { offset: 0, 'stop-color': s.cor, 'stop-opacity': 0 === n ? 0.2 : 0.1 } ) );
				grad.appendChild( no( 'stop', { offset: 1, 'stop-color': s.cor, 'stop-opacity': 0 } ) );
				defs.appendChild( grad );

				svg.appendChild( no( 'path', { d: linha + ' L' + x( ultimo ) + ',' + y( 0 ) + ' L' + x( 0 ) + ',' + y( 0 ) + ' Z', fill: 'url(#' + id + ')' } ) );
				svg.appendChild( no( 'path', { d: linha, class: 'jar-grafico__linha', stroke: s.cor } ) );
			} );
			svg.insertBefore( defs, svg.firstChild );

			// A marca do dia apontado: a linha vertical e um ponto por série.
			var guia = no( 'line', { class: 'jar-grafico__guia', y1: margem.cima, y2: altura - margem.baixo, visibility: 'hidden' } );
			svg.appendChild( guia );
			var pontos = ativas.map( function ( s ) {
				var c = no( 'circle', { r: 5, fill: '#fff', stroke: s.cor, 'stroke-width': 2.5, visibility: 'hidden' } );
				svg.appendChild( c );

				return { serie: s, circulo: c };
			} );

			var antigo = area.querySelector( 'svg' );
			if ( antigo ) {
				area.removeChild( antigo );
			}
			area.insertBefore( svg, dica );

			geo = { x: x, y: y, guia: guia, pontos: pontos, margem: margem, largura: largura };

			if ( null !== atual ) {
				mostrar( atual );
			}
		}

		function mostrar( i ) {
			var d = dados.dias[ i ];

			atual = i;
			geo.guia.setAttribute( 'x1', geo.x( i ) );
			geo.guia.setAttribute( 'x2', geo.x( i ) );
			geo.guia.setAttribute( 'visibility', 'visible' );

			geo.pontos.forEach( function ( p ) {
				p.circulo.setAttribute( 'cx', geo.x( i ) );
				p.circulo.setAttribute( 'cy', geo.y( d[ p.serie.chave ] ) );
				p.circulo.setAttribute( 'visibility', 'visible' );
			} );

			// A dica: o dia e o valor de cada série à vista.
			dica.textContent = '';
			var titulo = document.createElement( 'strong' );
			titulo.textContent = d.hoje ? 'Hoje · ' + d.rotulo : d.rotulo;
			dica.appendChild( titulo );
			geo.pontos.forEach( function ( p ) {
				var linha = document.createElement( 'span' );
				var cor = document.createElement( 'i' );

				cor.style.background = p.serie.cor;
				linha.appendChild( cor );
				linha.appendChild( document.createTextNode( p.serie.nome + ': ' ) );
				var valor = document.createElement( 'b' );
				valor.textContent = d[ p.serie.chave ];
				linha.appendChild( valor );
				dica.appendChild( linha );
			} );
			dica.hidden = false;

			// Ao lado da linha, e do lado de dentro do cartão.
			var esquerda = geo.x( i ) + 14;
			if ( esquerda + dica.offsetWidth > geo.largura ) {
				esquerda = geo.x( i ) - 14 - dica.offsetWidth;
			}
			dica.style.left = Math.max( 0, esquerda ) + 'px';
		}

		function esconder() {
			atual = null;
			dica.hidden = true;
			if ( geo ) {
				geo.guia.setAttribute( 'visibility', 'hidden' );
				geo.pontos.forEach( function ( p ) {
					p.circulo.setAttribute( 'visibility', 'hidden' );
				} );
			}
		}

		// O dia mais perto do rato ou do dedo.
		function diaEm( clientX ) {
			var r = area.getBoundingClientRect();
			var util = geo.largura - geo.margem.esquerda - geo.margem.direita;
			var i = Math.round( ( clientX - r.left - geo.margem.esquerda ) / util * ( dados.dias.length - 1 ) );

			return Math.max( 0, Math.min( ultimo, i ) );
		}

		area.addEventListener( 'mousemove', function ( e ) {
			mostrar( diaEm( e.clientX ) );
		} );
		area.addEventListener( 'mouseleave', esconder );
		area.addEventListener( 'touchstart', function ( e ) {
			mostrar( diaEm( e.touches[ 0 ].clientX ) );
		}, { passive: true } );
		area.addEventListener( 'touchmove', function ( e ) {
			mostrar( diaEm( e.touches[ 0 ].clientX ) );
		}, { passive: true } );

		// As setas percorrem os dias; Home e End vão ao primeiro e a hoje.
		area.addEventListener( 'keydown', function ( e ) {
			var fim = ultimo;
			var i = null === atual ? fim : atual;

			if ( 'ArrowLeft' === e.key ) {
				i = Math.max( 0, i - 1 );
			} else if ( 'ArrowRight' === e.key ) {
				i = Math.min( fim, i + 1 );
			} else if ( 'Home' === e.key ) {
				i = 0;
			} else if ( 'End' === e.key ) {
				i = fim;
			} else {
				return;
			}
			e.preventDefault();
			mostrar( i );
		} );
		area.addEventListener( 'focus', function () {
			mostrar( null === atual ? ultimo : atual );
		} );
		area.addEventListener( 'blur', esconder );

		// A legenda: mostrar ou esconder cada série (fica sempre pelo menos uma).
		Array.prototype.forEach.call( figura.querySelectorAll( '[data-jar-grafico-serie]' ), function ( b ) {
			b.addEventListener( 'click', function () {
				var chave = b.getAttribute( 'data-jar-grafico-serie' );
				var outras = Object.keys( visiveis ).filter( function ( k ) {
					return k !== chave && visiveis[ k ];
				} );

				if ( visiveis[ chave ] && ! outras.length ) {
					return;
				}
				visiveis[ chave ] = ! visiveis[ chave ];
				b.setAttribute( 'aria-pressed', visiveis[ chave ] ? 'true' : 'false' );
				desenhar();
			} );
		} );

		desenhar();

		// Outro mês (as setas do Painel): os dados novos, o mesmo gráfico redesenhado.
		figura.jarGraficoMudar = function ( novos ) {
			dados = novos;
			acertarUltimo();
			esconder();
			desenhar();
		};

		if ( window.ResizeObserver ) {
			new window.ResizeObserver( function () {
				if ( geo && area.clientWidth !== geo.largura ) {
					desenhar();
				}
			} ).observe( area );
		}
	} );

	/* ---------- O mês do Painel, por Ajax ---------- */

	/*
	 * As setas do cartão do mês (data-jar-painel-mes) trazem o mês pedido do
	 * servidor (jelly_ar_painel_mes_ajax(), inc/painel.php) sem recarregar a
	 * página: mudam o título, os totais, as setas e o gráfico, e o endereço
	 * fica com o mês, para se poder recarregar ou partilhar. Sem JavaScript,
	 * as setas são ligações normais.
	 */
	Array.prototype.forEach.call( raiz.querySelectorAll( '[data-jar-painel-mes]' ), function ( cartao ) {
		var figura = cartao.querySelector( '[data-jar-grafico]' );
		var titulo = cartao.querySelector( '[data-jar-painel-mes-titulo]' );
		var ocupado = false;

		function acertarSeta( seta, mes, rotulo ) {
			seta.setAttribute( 'data-jar-painel-ir', mes );
			seta.title = mes ? rotulo : 'Já é o mês atual';
			if ( mes ) {
				seta.removeAttribute( 'aria-disabled' );
			} else {
				seta.setAttribute( 'aria-disabled', 'true' );
			}
		}

		function mudar( mes, seta ) {
			if ( ocupado || ! mes ) {
				return;
			}
			ocupado = true;
			cartao.classList.add( 'is-a-carregar' );

			var url = cartao.getAttribute( 'data-ajax' ) + '?action=jelly_ar_painel_mes&_ajax_nonce=' + encodeURIComponent( cartao.getAttribute( 'data-nonce' ) ) + '&mes=' + encodeURIComponent( mes );

			fetch( url, { credentials: 'same-origin' } )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( resposta ) {
					if ( ! resposta || ! resposta.success ) {
						throw new Error( 'resposta inesperada' );
					}

					var c = resposta.data;

					titulo.textContent = c.titulo;
					Object.keys( c.totais ).forEach( function ( k ) {
						var n = cartao.querySelector( '[data-jar-painel-total="' + k + '"]' );
						if ( n ) {
							n.textContent = c.totais[ k ];
						}
					} );
					acertarSeta( cartao.querySelector( '[data-jar-painel-seta="anterior"]' ), c.anterior, 'Mês anterior' );
					acertarSeta( cartao.querySelector( '[data-jar-painel-seta="seguinte"]' ), c.seguinte, 'Mês seguinte' );

					if ( figura && figura.jarGraficoMudar ) {
						figura.jarGraficoMudar( c.grafico );
					}

					// O endereço com o mês, sem o mês quando é o de hoje.
					var endereco = new window.URL( window.location.href );
					if ( c.mes === c.atual ) {
						endereco.searchParams.delete( 'mes' );
					} else {
						endereco.searchParams.set( 'mes', c.mes );
					}
					endereco.hash = '';
					window.history.replaceState( window.history.state, '', endereco.toString() );

					if ( seta && ! seta.hasAttribute( 'aria-disabled' ) ) {
						seta.focus();
					}
				} )
				.catch( function ( erro ) {
					// Sem resposta: vai-se pela ligação, como sem JavaScript.
					window.console.error( 'Painel: o mês não carregou', erro );
					if ( seta && seta.href ) {
						window.location.assign( seta.href );
					}
				} )
				.then( function () {
					ocupado = false;
					cartao.classList.remove( 'is-a-carregar' );
				} );
		}

		Array.prototype.forEach.call( cartao.querySelectorAll( '[data-jar-painel-seta]' ), function ( seta ) {
			seta.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				mudar( seta.getAttribute( 'data-jar-painel-ir' ), seta );
			} );
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
