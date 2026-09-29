<?php
/**
 * A ARU — a Área Reservada do Utilizador: as páginas de cada associado, com os
 * dados dele.
 *
 * Vive em endereços do plugin, e não em páginas do WordPress, para não
 * depender de nada na base de dados e subir com o código:
 *
 *   /area-reservada/               Início
 *   /area-reservada/eventos/       os eventos da Área Reservada
 *   /area-reservada/eventos/<slug>/  um evento: as datas, a marcação, os documentos
 *                                  (com o id no lugar do slug, reencaminha para o slug)
 *   /area-reservada/marcacoes/     as marcações de mesa do associado
 *   /area-reservada/documentos/    os documentos publicados, para descarregar
 *   /area-reservada/perfil/        os meus dados: o telefone, a empresa e a palavra-passe
 *
 * Os eventos são os publicados que aceitam marcações (o visto "Os associados
 * podem marcar mesas neste evento"): é isso, e só isso, que põe um evento na
 * Área Reservada. Nada na ARU leva ao calendário do site.
 *
 * Tem o seu próprio desenho (templates/aru/, assets/css/aru.css), sem o
 * cabeçalho e o rodapé do site, mas com o wp_head() e o wp_footer(): as
 * folhas do tema (a letra, os botões) e o pop-up da marcação continuam a
 * carregar.
 *
 * Cada um vê só o que é seu: tudo se lê da conta com sessão
 * (get_current_user_id()), nunca de um id vindo do endereço.
 *
 * - Sem sessão: vai para a página inicial com o pop-up de entrar aberto; ao
 *   entrar, o login traz de volta aqui (jelly_ar_destino_associado()).
 * - Com sessão, mas sem acesso (um associado suspenso, uma conta que não é da
 *   AR): vai para a página inicial.
 *
 * Com sessão, os botões "Área Reservada" do tema (apit_area_reservada_url())
 * passam a trazer aqui, em vez de abrirem o pop-up.
 */

defined( 'ABSPATH' ) || exit;

// O endereço, sem barras: /area-reservada/.
const JELLY_AR_AREA_CAMINHO = 'area-reservada';

/**
 * O endereço da ARU, ou de uma das suas páginas.
 */
function jelly_ar_area_url( $secao = '' ) {
	// Só as secções que existem: outra coisa qualquer dá o Início, e nunca entra no endereço.
	$secao = in_array( $secao, [ 'eventos', 'marcacoes', 'documentos', 'perfil' ], true ) ? $secao : '';

	return home_url( '/' . JELLY_AR_AREA_CAMINHO . '/' . ( $secao ? $secao . '/' : '' ) );
}

/**
 * Quem pode ver a ARU: um associado ativo, ou a equipa.
 */
function jelly_ar_area_tem_acesso( $user = null ) {
	$user = $user instanceof WP_User ? $user : wp_get_current_user();

	if ( ! $user->exists() ) {
		return false;
	}

	if ( jelly_ar_e_administrador( $user ) ) {
		return true;
	}

	return jelly_ar_e_associado( $user ) && 'ativo' === jelly_ar_associado_estado( $user->ID );
}

/**
 * O menu da ARU, pela ordem em que aparece. `url` vazio: ainda não existe
 * (aparece com "Em breve"). `fora`: leva a uma página do site.
 */
function jelly_ar_aru_menu() {
	return [
		'inicio'     => [ 'titulo' => __( 'Início', 'jelly-area-reservada' ), 'icone' => 'fa-house', 'url' => jelly_ar_area_url() ],
		'eventos'    => [ 'titulo' => __( 'Eventos', 'jelly-area-reservada' ), 'icone' => 'fa-calendar-days', 'url' => jelly_ar_area_url( 'eventos' ) ],
		'marcacoes'  => [ 'titulo' => __( 'Marcações', 'jelly-area-reservada' ), 'icone' => 'fa-calendar-check', 'url' => jelly_ar_area_url( 'marcacoes' ) ],
		'documentos' => [ 'titulo' => __( 'Documentos', 'jelly-area-reservada' ), 'icone' => 'fa-file-lines', 'url' => jelly_ar_area_url( 'documentos' ) ],
		'perfil'     => [ 'titulo' => __( 'Os meus dados', 'jelly-area-reservada' ), 'icone' => 'fa-user', 'url' => jelly_ar_area_url( 'perfil' ) ],
		'encontros'  => [ 'titulo' => __( 'Encontros', 'jelly-area-reservada' ), 'icone' => 'fa-user-group', 'url' => '' ],
	];
}

/* ---------- Os endereços ---------- */

function jelly_ar_area_regra() {
	add_rewrite_rule( '^' . JELLY_AR_AREA_CAMINHO . '/eventos/([^/]+)/?$', 'index.php?jelly_ar_area=1&jelly_ar_aru=eventos&jelly_ar_aru_evento=$matches[1]', 'top' );
	add_rewrite_rule( '^' . JELLY_AR_AREA_CAMINHO . '(?:/(eventos|marcacoes|documentos|perfil))?/?$', 'index.php?jelly_ar_area=1&jelly_ar_aru=$matches[1]', 'top' );

}
add_action( 'init', 'jelly_ar_area_regra' );

/*
 * Uma regra nova só vale depois de as regras se refazerem: uma vez por versão
 * do plugin. No wp_loaded, e não no init, para já estarem registadas as regras
 * de todos — refeitas no init, o próprio pedido que as refazia dava 404.
 */
function jelly_ar_area_regras_refazer() {
	if ( get_option( 'jelly_ar_regras' ) !== JELLY_AR_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'jelly_ar_regras', JELLY_AR_VERSION, true );
	}
}
add_action( 'wp_loaded', 'jelly_ar_area_regras_refazer' );

function jelly_ar_area_query_vars( $vars ) {
	$vars[] = 'jelly_ar_area';
	$vars[] = 'jelly_ar_aru';
	$vars[] = 'jelly_ar_aru_evento';

	return $vars;
}
add_filter( 'query_vars', 'jelly_ar_area_query_vars' );

function jelly_ar_e_area() {
	return (bool) get_query_var( 'jelly_ar_area' );
}

/**
 * A secção da ARU pedida (a do menu): inicio, eventos, marcacoes, documentos ou perfil.
 */
function jelly_ar_aru_secao() {
	$secao = (string) get_query_var( 'jelly_ar_aru' );

	return in_array( $secao, [ 'eventos', 'marcacoes', 'documentos', 'perfil' ], true ) ? $secao : 'inicio';
}

/**
 * O que vem no endereço no lugar do evento (/area-reservada/eventos/<slug>/):
 * o slug, ou o id dos endereços antigos. '' sem nada.
 */
function jelly_ar_aru_evento_pedido_valor() {
	return sanitize_title( (string) get_query_var( 'jelly_ar_aru_evento' ) );
}

/**
 * O evento pedido no endereço, pelo slug — ou pelo id, nos endereços antigos
 * —, se for da Área Reservada; null se não houver ou se o evento não for de lá.
 */
function jelly_ar_aru_evento_pedido() {
	$valor = jelly_ar_aru_evento_pedido_valor();

	if ( '' === $valor ) {
		return null;
	}

	return ctype_digit( $valor ) ? jelly_ar_aru_evento( (int) $valor ) : jelly_ar_aru_evento_por_slug( $valor );
}

/**
 * O endereço da página de um evento na ARU, pelo slug (o id, se ainda não o tiver).
 */
function jelly_ar_aru_evento_url( $evento ) {
	$chave = is_array( $evento ) ? ( '' !== $evento['slug'] ? $evento['slug'] : $evento['id'] ) : (int) $evento;

	return jelly_ar_area_url( 'eventos' ) . rawurlencode( (string) $chave ) . '/';
}

/* ---------- A página ---------- */

function jelly_ar_area_mostrar() {
	if ( ! jelly_ar_e_area() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/#area-reservada' ) );
		exit;
	}

	if ( ! jelly_ar_area_tem_acesso() ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	// Dados de uma pessoa: nada de caches pelo caminho.
	nocache_headers();
	status_header( 200 );

	$aru = [
		'secao'  => jelly_ar_aru_secao(),
		'user'   => wp_get_current_user(),
		'evento' => null,
	];
	// O template da página: o da secção, ou o de um evento.
	$aru['pagina'] = $aru['secao'];

	// A página de um evento: só os da Área Reservada; os outros, e os que não existem, dão 404.
	if ( '' !== jelly_ar_aru_evento_pedido_valor() ) {
		$aru['evento'] = jelly_ar_aru_evento_pedido();
		$aru['pagina'] = $aru['evento'] ? 'evento' : 'nao-existe';

		// Um endereço antigo, com o id: vai para o do slug, e fica esse.
		if ( $aru['evento'] && ctype_digit( jelly_ar_aru_evento_pedido_valor() ) && '' !== $aru['evento']['slug'] ) {
			wp_safe_redirect( $aru['evento']['url'], 301 );
			exit;
		}

		if ( ! $aru['evento'] ) {
			status_header( 404 );
		}
	}

	include JELLY_AR_DIR . 'templates/aru/shell.php';
	exit;
}
add_action( 'template_redirect', 'jelly_ar_area_mostrar' );

/**
 * As páginas da ARU não são posts: sem isto, o WordPress dava-lhes o título
 * da página inicial.
 */
function jelly_ar_area_titulo( $partes ) {
	if ( jelly_ar_e_area() ) {
		$menu            = jelly_ar_aru_menu();
		$secao           = jelly_ar_aru_secao();
		$evento          = jelly_ar_aru_evento_pedido();
		$partes['title'] = 'inicio' === $secao
			? __( 'Área Reservada', 'jelly-area-reservada' )
			/* translators: %s: página da Área Reservada, ou o título do evento */
			: sprintf( __( '%s · Área Reservada', 'jelly-area-reservada' ), $evento ? $evento['titulo'] : $menu[ $secao ]['titulo'] );
	}

	return $partes;
}
add_filter( 'document_title_parts', 'jelly_ar_area_titulo' );

function jelly_ar_area_body_class( $classes ) {
	if ( jelly_ar_e_area() ) {
		$classes   = array_diff( $classes, [ 'error404', 'home', 'blog' ] );
		$classes[] = 'aru-corpo';
	}

	return $classes;
}
add_filter( 'body_class', 'jelly_ar_area_body_class' );

// Depois da folha do pop-up (prioridade 20), para a ARU poder afinar por cima dela.
function jelly_ar_area_estilos() {
	if ( ! jelly_ar_e_area() ) {
		return;
	}

	wp_enqueue_style(
		'jelly-ar-aru',
		JELLY_AR_URL . 'assets/css/aru.css',
		[ 'jelly-area-reservada' ],
		jelly_ar_versao_ficheiro( 'assets/css/aru.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'jelly_ar_area_estilos', 25 );

/* ---------- Ligar o login e os botões à ARU ---------- */

// Ao entrar (e ao tentar abrir o wp-admin), o associado vem para a sua área.
// Uma função própria, e não jelly_ar_area_url(): o filtro passa-lhe o destino antigo, que não é uma secção.
function jelly_ar_area_destino() {
	return jelly_ar_area_url();
}
add_filter( 'jelly_ar_destino_associado', 'jelly_ar_area_destino' );

// Com sessão, os botões "Área Reservada" do tema levam à ARU; sem sessão, abrem o pop-up.
function jelly_ar_area_botoes( $url ) {
	return jelly_ar_area_tem_acesso() ? jelly_ar_area_url() : $url;
}
add_filter( 'apit_area_reservada_url', 'jelly_ar_area_botoes' );

/* ---------- Os dados da ARU ---------- */

/**
 * Quem tem a sessão: o nome, o primeiro nome, a empresa e as iniciais.
 */
function jelly_ar_aru_pessoa( $user ) {
	$perfil  = jelly_ar_associado( $user->ID );
	$nome    = $perfil ? trim( $perfil->nome . ' ' . $perfil->apelido ) : '';
	$nome    = '' !== $nome ? $nome : $user->display_name;
	$partes  = preg_split( '/\s+/', trim( $nome ) );
	$iniciais = mb_strtoupper( mb_substr( $partes[0] ?? '', 0, 1 ) . ( count( $partes ) > 1 ? mb_substr( end( $partes ), 0, 1 ) : '' ) );

	return [
		'nome'     => $nome,
		'primeiro' => $partes[0] ?? $nome,
		'empresa'  => $perfil ? (string) $perfil->empresa : '',
		'iniciais' => $iniciais,
		'equipa'   => jelly_ar_e_administrador( $user ),
	];
}

/**
 * O intervalo de uma marcação, do início ao fim do bloco: "12:00 às 12:30"
 * (com um traço, as duas horas liam-se como duas marcações). A
 * duração é a dos blocos desse dia do evento; sem horário para o dia, a de
 * omissão.
 */
function jelly_ar_aru_intervalo( $evento_id, $dia, $hora ) {
	static $horarios = [];

	if ( ! isset( $horarios[ $evento_id ] ) ) {
		$horarios[ $evento_id ] = jelly_ar_horarios( $evento_id );
	}

	$duracao = $horarios[ $evento_id ][ $dia ]['intervalo'] ?? JELLY_AR_INTERVALO_OMISSAO;
	$fim     = jelly_ar_minutos( $hora ) + $duracao;

	/* translators: 1: hora de início, 2: hora de fim */
	return sprintf( __( '%1$s às %2$s', 'jelly-area-reservada' ), $hora, sprintf( '%02d:%02d', intdiv( $fim, 60 ) % 24, $fim % 60 ) );
}

/**
 * O nome e a cor de cada estado de uma marcação, como o associado os lê.
 */
function jelly_ar_aru_estados() {
	return [
		'pendente'  => __( 'Por aprovar', 'jelly-area-reservada' ),
		'aprovada'  => __( 'Confirmado', 'jelly-area-reservada' ),
		'rejeitada' => __( 'Não aprovada', 'jelly-area-reservada' ),
		'cancelada' => __( 'Cancelada', 'jelly-area-reservada' ),
	];
}

/**
 * As marcações do associado: as que vêm primeiro (da mais próxima), depois
 * as que já passaram (da mais recente).
 */
function jelly_ar_aru_marcacoes( $user_id ) {
	$hoje   = current_time( 'Y-m-d' );
	$suas   = array_filter( jelly_ar_marcacoes_todas(), function ( $m ) use ( $user_id ) {
		return $m['user_id'] === (int) $user_id;
	} );
	$cores  = [];
	foreach ( jelly_ar_eventos_todos() as $e ) {
		$cores[ $e['id'] ] = $e['cores'];
	}

	$lista = array_map( function ( $m ) use ( $hoje, $cores ) {
		$ts = strtotime( $m['dia'] );

		return $m + [
			'futura' => $m['dia'] >= $hoje,
			'dia_n'  => (int) gmdate( 'j', $ts ),
			'mes'    => jelly_ar_data( 'M', $ts ),
			'data'   => ucfirst( jelly_ar_data( 'D, j M', $ts ) ),
			'horas'  => jelly_ar_aru_intervalo( $m['evento_id'], $m['dia'], $m['hora'] ),
			// Viva: ainda prende o horário (por aprovar ou confirmada).
			'viva'   => in_array( $m['estado'], [ 'pendente', 'aprovada' ], true ),
			'cores'  => $cores[ $m['evento_id'] ] ?? [ 'inicio' => '#f41892', 'fim' => '#8048a6' ],
		];
	}, $suas );

	usort( $lista, function ( $a, $b ) {
		if ( $a['futura'] !== $b['futura'] ) {
			return $a['futura'] ? -1 : 1;
		}

		$ordem = strcmp( $a['dia'] . $a['hora'], $b['dia'] . $b['hora'] );

		return $a['futura'] ? $ordem : -$ordem;
	} );

	return $lista;
}

// Os eventos que a ARU mostra: publicados e que aceitam marcações.
const JELLY_AR_ARU_ONDE = "e.estado = 'publicado' AND e.marcacoes = 1";

/**
 * Os eventos da Área Reservada que ainda não acabaram, do mais próximo. Com
 * $passados, os que já acabaram, do mais recente, até $limite.
 */
function jelly_ar_aru_eventos( $passados = false, $limite = 0 ) {
	global $wpdb;

	$sql = $passados
		? 'WHERE ' . JELLY_AR_ARU_ONDE . ' AND COALESCE(e.fim, e.inicio) < %s ORDER BY e.inicio DESC, e.id DESC'
		: 'WHERE ' . JELLY_AR_ARU_ONDE . ' AND COALESCE(e.fim, e.inicio) >= %s ORDER BY e.inicio, e.id';

	$linhas = jelly_ar_eventos_consulta( $wpdb->prepare( $sql, current_time( 'Y-m-d' ) ) . ( $limite ? ' LIMIT ' . (int) $limite : '' ) );

	return array_map( 'jelly_ar_aru_evento_da_linha', $linhas );
}

/**
 * Um evento da Área Reservada pelo id, ou null: não existe, não está
 * publicado, ou não aceita marcações.
 */
function jelly_ar_aru_evento( $id ) {
	global $wpdb;

	$linhas = jelly_ar_eventos_consulta( $wpdb->prepare( 'WHERE e.id = %d AND ' . JELLY_AR_ARU_ONDE, $id ) );

	return $linhas ? jelly_ar_aru_evento_da_linha( $linhas[0] ) : null;
}

/**
 * Um evento da Área Reservada pelo slug, ou null.
 */
function jelly_ar_aru_evento_por_slug( $slug ) {
	global $wpdb;

	$linhas = jelly_ar_eventos_consulta( $wpdb->prepare( 'WHERE e.slug = %s AND ' . JELLY_AR_ARU_ONDE . ' LIMIT 1', $slug ) );

	return $linhas ? jelly_ar_aru_evento_da_linha( $linhas[0] ) : null;
}

/**
 * Um evento na forma que a ARU mostra: o de sempre, mais as datas por
 * extenso, o dia e o mês do quadrado, se já acabou, e o endereço na ARU.
 */
function jelly_ar_aru_evento_da_linha( $l ) {
	$e  = jelly_ar_evento_da_linha( $l );
	$ts = strtotime( $e['inicio'] );

	return $e + [
		'datas'     => jelly_ar_intervalo_datas( $e['inicio'], $e['fim'] ),
		'dia_n'     => (int) gmdate( 'j', $ts ),
		'mes'       => jelly_ar_data( 'M', $ts ),
		'terminado' => ( $e['fim'] ? $e['fim'] : $e['inicio'] ) < current_time( 'Ymd' ),
		'url'       => jelly_ar_aru_evento_url( $e ),
	];
}

/**
 * Os horários de um evento, dia a dia, e o que cada um é para este
 * associado: livre, ocupado, ou a marcação dele. Só os que ainda não
 * passaram (jelly_ar_disponibilidade()). $minhas são as marcações dele no
 * evento, pelo dia (jelly_ar_marcacoes_do_associado()); `tem` diz se o dia já
 * tem uma — é uma por dia, e nesse dia não se marca outra.
 */
function jelly_ar_aru_horarios( $evento, $minhas ) {
	return array_map( function ( $d ) use ( $minhas ) {
		$minha = $minhas[ $d['dia'] ] ?? null;

		return [
			'dia'   => $d['dia'],
			'nome'  => ucfirst( jelly_ar_data( 'l, j \d\e F', strtotime( $d['dia'] ) ) ),
			'tem'   => (bool) $minha,
			// O estado da marcação desse dia: pendente ou aprovada ('' sem marcação).
			'estado' => $minha ? $minha['estado'] : '',
			'horas' => array_map( function ( $b ) use ( $minha ) {
				$e_minha = $minha && $minha['hora'] === $b['hora'];

				return [
					'hora'   => $b['hora'],
					// A marcação do associado: confirmada (minha) ou à espera da equipa (minha-pendente).
					'estado' => $e_minha ? ( 'pendente' === $minha['estado'] ? 'minha-pendente' : 'minha' ) : ( array_sum( wp_list_pluck( $b['mesas'], 'livres' ) ) ? 'livre' : 'ocupado' ),
				];
			}, $d['blocos'] ),
		];
	}, jelly_ar_disponibilidade( $evento ) );
}

/**
 * Se o associado ainda pode marcar no evento: num dia sem marcação sua (é uma
 * por dia), com algum horário livre.
 */
function jelly_ar_aru_pode_marcar( $evento, $user_id ) {
	if ( empty( $evento['marcacoes'] ) || 'disponivel' !== jelly_ar_disponibilidade_estado( $evento )['estado'] ) {
		return false;
	}

	foreach ( jelly_ar_aru_horarios( $evento, jelly_ar_marcacoes_do_associado( $evento['id'], $user_id ) ) as $d ) {
		if ( ! $d['tem'] && in_array( 'livre', wp_list_pluck( $d['horas'], 'estado' ), true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Os documentos publicados, do publicado mais recentemente, com o nome da
 * categoria e, para o associado $user_id, se é "Novo" para ele.
 */
function jelly_ar_aru_documentos( $user_id = 0 ) {
	global $wpdb;

	$categorias = jelly_ar_documento_categorias();
	$docs       = array_values( array_filter( jelly_ar_documentos_reais(), function ( $d ) {
		return 'publicado' === $d['estado'];
	} ) );

	// Os que o associado já descarregou: para ele, deixam de ser "Novo".
	$descarregados = $user_id ? array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT documento_id FROM ' . jelly_ar_tabela( 'descargas' ) . ' WHERE user_id = %d', $user_id ) ) ) : []; // phpcs:ignore WordPress.DB
	$novo_desde    = time() - 30 * DAY_IN_SECONDS;

	foreach ( $docs as &$d ) {
		// A data que conta é a da publicação; sem ela (não devia faltar), a do carregamento.
		$quando              = DateTime::createFromFormat( 'd/m/Y H:i', $d['data'] );
		$d['ts']             = $d['publicado_ts'] ? $d['publicado_ts'] : ( $quando ? $quando->getTimestamp() : 0 );
		$d['categoria_nome'] = $categorias[ $d['categoria'] ] ?? '';
		// "Novo": publicado há menos de 30 dias, e ainda não descarregado por este associado.
		$d['novo']           = $d['ts'] >= $novo_desde && ! in_array( $d['id'], $descarregados, true );
	}
	unset( $d );

	usort( $docs, function ( $a, $b ) {
		return $b['ts'] - $a['ts'];
	} );

	return $docs;
}

/**
 * O ícone de cada tipo de ficheiro (o mesmo do back-office).
 */
function jelly_ar_aru_icone( $tipo ) {
	$icones = [
		'pdf'  => 'fa-file-pdf',
		'docx' => 'fa-file-word',
		'doc'  => 'fa-file-word',
		'xlsx' => 'fa-file-excel',
		'xls'  => 'fa-file-excel',
		'pptx' => 'fa-file-powerpoint',
		'ppt'  => 'fa-file-powerpoint',
		'zip'  => 'fa-file-zipper',
	];

	return $icones[ $tipo ] ?? 'fa-file-lines';
}

/**
 * O evento em destaque no Início: o próximo com marcação de mesas e grelha
 * feita. Com os horários do primeiro dia que ainda tem horários, e o que cada
 * um é para este associado: livre, ocupado, ou a marcação dele.
 */
function jelly_ar_aru_destaque( $eventos, $user_id ) {
	foreach ( $eventos as $e ) {
		if ( ! $e['marcacoes'] ) {
			continue;
		}

		$estado = jelly_ar_disponibilidade_estado( $e );

		if ( ! in_array( $estado['estado'], [ 'disponivel', 'completo' ], true ) ) {
			continue;
		}

		$dias   = jelly_ar_disponibilidade( $e );
		$minhas = jelly_ar_marcacoes_do_associado( $e['id'], $user_id );
		$minha  = $minhas ? reset( $minhas ) : null;
		$total  = 0;
		$livres = 0;

		foreach ( $dias as $d ) {
			foreach ( $d['blocos'] as $b ) {
				$total  += array_sum( wp_list_pluck( $b['mesas'], 'lugares' ) );
				$livres += array_sum( wp_list_pluck( $b['mesas'], 'livres' ) );
			}
		}

		// Os horários do primeiro dia que ainda os tem.
		$horarios = jelly_ar_aru_horarios( $e, $minhas );
		$dia      = $horarios[0] ?? null;

		return [
			'evento' => $e,
			'estado' => $estado['estado'],
			'total'  => $total,
			'livres' => $livres,
			'minha'  => $minha,
			'minhas' => $minhas,
			// Nesse dia ainda pode marcar (é uma por dia).
			'pode'   => $dia && ! $dia['tem'],
			'dia'    => $dia ? $dia['nome'] : '',
			'dia_ymd' => $dia ? $dia['dia'] : '',
			'horas'  => $dia ? array_slice( $dia['horas'], 0, 6 ) : [],
			'mais'   => $dia ? max( 0, count( $dia['horas'] ) - 6 ) : 0,
		];
	}

	return null;
}
