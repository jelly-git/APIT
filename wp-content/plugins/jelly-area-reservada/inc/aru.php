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
 *   /area-reservada/eventos/<id>/  um evento: as datas, a marcação, os documentos
 *   /area-reservada/marcacoes/     as marcações de mesa do associado
 *   /area-reservada/documentos/    os documentos publicados, para descarregar
 *
 * Os eventos são os publicados para a Área Reservada: os "só Área reservada"
 * e os "Site e Área reservada". Os "só site" não aparecem aqui, e nada na ARU
 * leva ao calendário do site.
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
	$secao = is_string( $secao ) ? $secao : '';

	return home_url( '/' . JELLY_AR_AREA_CAMINHO . '/' . ( $secao && 'inicio' !== $secao ? $secao . '/' : '' ) );
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
		'encontros'  => [ 'titulo' => __( 'Encontros', 'jelly-area-reservada' ), 'icone' => 'fa-user-group', 'url' => '' ],
	];
}

/* ---------- Os endereços ---------- */

function jelly_ar_area_regra() {
	add_rewrite_rule( '^' . JELLY_AR_AREA_CAMINHO . '/eventos/([0-9]+)/?$', 'index.php?jelly_ar_area=1&jelly_ar_aru=eventos&jelly_ar_aru_evento=$matches[1]', 'top' );
	add_rewrite_rule( '^' . JELLY_AR_AREA_CAMINHO . '(?:/(eventos|marcacoes|documentos))?/?$', 'index.php?jelly_ar_area=1&jelly_ar_aru=$matches[1]', 'top' );

	// Uma regra nova só vale depois de as regras se refazerem: uma vez por versão do plugin.
	if ( get_option( 'jelly_ar_regras' ) !== JELLY_AR_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'jelly_ar_regras', JELLY_AR_VERSION, true );
	}
}
add_action( 'init', 'jelly_ar_area_regra' );

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
 * A secção da ARU pedida (a do menu): inicio, eventos, marcacoes ou documentos.
 */
function jelly_ar_aru_secao() {
	$secao = (string) get_query_var( 'jelly_ar_aru' );

	return in_array( $secao, [ 'eventos', 'marcacoes', 'documentos' ], true ) ? $secao : 'inicio';
}

/**
 * O evento pedido no endereço (/area-reservada/eventos/<id>/), se for da Área
 * Reservada; null se não houver id ou se o evento não for de lá.
 */
function jelly_ar_aru_evento_pedido() {
	$id = absint( get_query_var( 'jelly_ar_aru_evento' ) );

	return $id ? jelly_ar_aru_evento( $id ) : null;
}

/**
 * O endereço da página de um evento na ARU.
 */
function jelly_ar_aru_evento_url( $id ) {
	return jelly_ar_area_url( 'eventos' ) . (int) $id . '/';
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
	if ( absint( get_query_var( 'jelly_ar_aru_evento' ) ) ) {
		$aru['evento'] = jelly_ar_aru_evento_pedido();
		$aru['pagina'] = $aru['evento'] ? 'evento' : 'nao-existe';

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
add_filter( 'jelly_ar_destino_associado', 'jelly_ar_area_url' );

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
 * O nome e a cor de cada estado de uma marcação, como o associado os lê.
 */
function jelly_ar_aru_estados() {
	return [
		'pendente'  => __( 'Por aprovar', 'jelly-area-reservada' ),
		'aprovada'  => __( 'Confirmada', 'jelly-area-reservada' ),
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

// Os eventos que a ARU mostra: publicados, e para a Área Reservada (só AR, ou site e AR).
const JELLY_AR_ARU_ONDE = "e.estado = 'publicado' AND e.onde IN ('reservada', 'ambos')";

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
 * publicado, ou é só do site.
 */
function jelly_ar_aru_evento( $id ) {
	global $wpdb;

	$linhas = jelly_ar_eventos_consulta( $wpdb->prepare( 'WHERE e.id = %d AND ' . JELLY_AR_ARU_ONDE, $id ) );

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
		'url'       => jelly_ar_aru_evento_url( $e['id'] ),
	];
}

/**
 * Os horários de um evento, dia a dia, e o que cada um é para este
 * associado: livre, ocupado, ou a marcação dele. Só os que ainda não
 * passaram (jelly_ar_disponibilidade()).
 */
function jelly_ar_aru_horarios( $evento, $minha ) {
	return array_map( function ( $d ) use ( $minha ) {
		return [
			'dia'   => $d['dia'],
			'nome'  => ucfirst( jelly_ar_data( 'l, j \d\e F', strtotime( $d['dia'] ) ) ),
			'horas' => array_map( function ( $b ) use ( $d, $minha ) {
				$e_minha = $minha && $minha['dia'] === $d['dia'] && $minha['hora'] === $b['hora'];

				return [
					'hora'   => $b['hora'],
					'estado' => $e_minha ? 'minha' : ( array_sum( wp_list_pluck( $b['mesas'], 'livres' ) ) ? 'livre' : 'ocupado' ),
				];
			}, $d['blocos'] ),
		];
	}, jelly_ar_disponibilidade( $evento ) );
}

/**
 * Os documentos publicados, do mais recente, com o nome da categoria.
 */
function jelly_ar_aru_documentos() {
	$categorias = jelly_ar_documento_categorias();
	$docs       = array_values( array_filter( jelly_ar_documentos_reais(), function ( $d ) {
		return 'publicado' === $d['estado'];
	} ) );

	foreach ( $docs as &$d ) {
		$quando             = DateTime::createFromFormat( 'd/m/Y H:i', $d['data'] );
		$d['ts']            = $quando ? $quando->getTimestamp() : 0;
		$d['categoria_nome'] = $categorias[ $d['categoria'] ] ?? '';
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
		$minha  = jelly_ar_marcacao_do_associado( $e['id'], $user_id );
		$total  = 0;
		$livres = 0;

		foreach ( $dias as $d ) {
			foreach ( $d['blocos'] as $b ) {
				$total  += array_sum( wp_list_pluck( $b['mesas'], 'lugares' ) );
				$livres += array_sum( wp_list_pluck( $b['mesas'], 'livres' ) );
			}
		}

		// Os horários do primeiro dia que ainda os tem.
		$horarios = jelly_ar_aru_horarios( $e, $minha );
		$dia      = $horarios[0] ?? null;

		return [
			'evento' => $e,
			'estado' => $estado['estado'],
			'total'  => $total,
			'livres' => $livres,
			'minha'  => $minha,
			'dia'    => $dia ? $dia['nome'] : '',
			'horas'  => $dia ? array_slice( $dia['horas'], 0, 6 ) : [],
			'mais'   => $dia ? max( 0, count( $dia['horas'] ) - 6 ) : 0,
		];
	}

	return null;
}
