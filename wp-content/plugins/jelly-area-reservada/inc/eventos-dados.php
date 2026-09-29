<?php
/**
 * Os eventos e as categorias de eventos, nas tabelas da AR
 * (jelly_ar_eventos, jelly_ar_evento_categorias — ver inc/instalar.php).
 *
 * É a única fonte dos eventos: o back-office escreve aqui, e o calendário do
 * site (o shortcode [apit_calendario] do tema) e a pesquisa do site leem daqui,
 * por jelly_ar_eventos_calendario*() e jelly_ar_eventos_pesquisa(). O tema não
 * sabe onde os eventos estão guardados, e o WordPress já não tem eventos.
 *
 * Onde cada evento aparece: no site, todos os publicados; na Área Reservada
 * (a ARU, inc/aru.php), os que aceitam marcações. A escolha "Onde aparece"
 * (site, área reservada, os dois) saiu; a coluna `onde` fica na tabela, sem
 * uso, para não se perder o que lá estava.
 */

defined( 'ABSPATH' ) || exit;

const JELLY_AR_EVENTO_ESTADOS = [ 'publicado', 'rascunho', 'lixo' ];

/* ---------- Ler ---------- */

/**
 * A consulta base: o evento com a categoria ao lado. $onde é o resto do SQL
 * (WHERE, ORDER), já preparado.
 */
function jelly_ar_eventos_consulta( $onde = '' ) {
	global $wpdb;

	$e = jelly_ar_tabela( 'eventos' );
	$c = jelly_ar_tabela( 'evento_categorias' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	return $wpdb->get_results( "SELECT e.*, c.nome AS cat_nome, c.slug AS cat_slug, c.cor_inicio, c.cor_fim FROM {$e} e LEFT JOIN {$c} c ON c.id = e.categoria_id {$onde}" );
}

/**
 * Uma linha da tabela na forma que os ecrãs usam. As datas saem como Ymd,
 * que se comparam como números e que os ecrãs já esperam.
 */
function jelly_ar_evento_da_linha( $l ) {
	$ymd = function ( $data ) {
		return $data ? str_replace( '-', '', $data ) : '';
	};

	$inicio = $ymd( $l->inicio );
	$fim    = $ymd( $l->fim );

	return [
		'id'             => (int) $l->id,
		'titulo'         => $l->titulo,
		'resumo'         => $l->resumo,
		'categoria'      => (string) $l->cat_slug,
		'categoria_id'   => (int) $l->categoria_id,
		'categoria_nome' => (string) $l->cat_nome,
		'cores'          => [
			'inicio' => $l->cor_inicio ? $l->cor_inicio : '#f41892',
			'fim'    => $l->cor_fim ? $l->cor_fim : '#e9edf0',
		],
		'inicio'         => $inicio,
		'fim'            => $fim && $fim > $inicio ? $fim : $inicio,
		'local'          => $l->local,
		'acao_texto'     => $l->botao_texto,
		'marcacoes'      => '1' === (string) $l->marcacoes,
		'estado'         => $l->estado,
	];
}

/**
 * Os eventos do back-office: todos menos os do lixo.
 */
function jelly_ar_eventos_todos() {
	global $wpdb;

	return array_map( 'jelly_ar_evento_da_linha', jelly_ar_eventos_consulta( $wpdb->prepare( 'WHERE e.estado <> %s', 'lixo' ) ) );
}

function jelly_ar_evento( $id ) {
	global $wpdb;

	$linhas = jelly_ar_eventos_consulta( $wpdb->prepare( 'WHERE e.id = %d AND e.estado <> %s', $id, 'lixo' ) );

	return $linhas ? jelly_ar_evento_da_linha( $linhas[0] ) : null;
}

/**
 * As categorias dos eventos: slug => [ id, nome, cores ], por ordem alfabética.
 */
function jelly_ar_evento_categorias() {
	global $wpdb;

	$lista = [];

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	foreach ( $wpdb->get_results( 'SELECT * FROM ' . jelly_ar_tabela( 'evento_categorias' ) . ' ORDER BY nome' ) as $c ) {
		$lista[ $c->slug ] = [
			'id'    => (int) $c->id,
			'nome'  => $c->nome,
			'cores' => [ 'inicio' => $c->cor_inicio, 'fim' => $c->cor_fim ],
		];
	}

	return $lista;
}

/**
 * As cores de uma categoria, com as de partida quando não há categoria.
 */
function jelly_ar_evento_categoria_cores( $id ) {
	foreach ( jelly_ar_evento_categorias() as $c ) {
		if ( $c['id'] === (int) $id ) {
			return $c['cores'];
		}
	}

	return [ 'inicio' => '#f41892', 'fim' => '#e9edf0' ];
}

/* ---------- O botão do cartão ---------- */

/**
 * Para onde leva o botão de um evento com marcações: ao pop-up da marcação de
 * mesa desse evento. É o mesmo endereço com ou sem sessão, para a página poder
 * vir de uma cache; o pop-up pergunta ao servidor no clique e mostra o login ou
 * a escolha do dia, da hora e da mesa (inc/marcacoes.php).
 */
function jelly_ar_url_marcacao( $evento_id ) {
	return '#area-reservada-marcar-' . (int) $evento_id;
}

/*
 * O botão do cartão de um evento: só nos eventos em que os associados podem
 * marcar mesas; nos outros, não há botão (null). O texto é o do evento, e
 * "Fazer inscrição" se estiver vazio; o link não se escreve à mão — é o da
 * marcação.
 */
function jelly_ar_botao_do_evento( $evento ) {
	if ( empty( $evento['marcacoes'] ) ) {
		return null;
	}

	return [
		'texto' => '' !== $evento['acao_texto'] ? $evento['acao_texto'] : __( 'Fazer inscrição', 'jelly-area-reservada' ),
		'url'   => jelly_ar_url_marcacao( $evento['id'] ),
	];
}

/* ---------- O calendário do site ---------- */

/**
 * Os eventos do calendário do site, prontos a desenhar — é daqui, e só daqui,
 * que o shortcode [apit_calendario] do tema os tira. O tema desenha o cartão;
 * o que entra, por que ordem e com que dados é decidido no back-office da
 * Área Reservada:
 *
 * - todos os publicados;
 * - até ao fim do último dia: o Fim do evento, ou o Início se não tiver fim;
 * - do mais próximo para o mais distante.
 *
 * Cada evento traz: id, titulo, resumo, inicio (Ymd), datas ("6–9 out 2026"),
 * local, categoria (o nome), cores (inicio e fim do gradiente) e botao
 * ([ texto, url ], ou null sem marcações).
 *
 * Esta dá-os todos; as duas de baixo dão os primeiros (o carrossel) ou uma
 * página deles (a grelha da página Calendário, com paginação).
 */
function jelly_ar_eventos_calendario_todos() {
	global $wpdb;

	$linhas = jelly_ar_eventos_consulta(
		$wpdb->prepare(
			"WHERE e.estado = 'publicado' AND COALESCE(e.fim, e.inicio) >= %s ORDER BY e.inicio, e.id",
			current_time( 'Y-m-d' )
		)
	);

	return array_map( function ( $l ) {
		$e = jelly_ar_evento_da_linha( $l );

		return [
			'id'        => $e['id'],
			'titulo'    => $e['titulo'],
			'resumo'    => $e['resumo'],
			'inicio'    => $e['inicio'],
			'datas'     => jelly_ar_intervalo_datas( $e['inicio'], $e['fim'] ),
			'local'     => $e['local'],
			'categoria' => $e['categoria_nome'],
			'cores'     => $e['cores'],
			'botao'     => jelly_ar_botao_do_evento( $e ),
		];
	}, $linhas );
}

/**
 * Os primeiros $limite eventos do calendário — o que um carrossel mostra.
 */
function jelly_ar_eventos_calendario( $limite = 12 ) {
	return array_slice( jelly_ar_eventos_calendario_todos(), 0, max( 1, (int) $limite ) );
}

/**
 * Uma página do calendário — o que a grelha da página Calendário mostra. Uma
 * página para lá da última (um link antigo) dá a última.
 *
 * @return array [ eventos, pagina, paginas, total ]
 */
function jelly_ar_eventos_calendario_pagina( $por_pagina = 12, $pagina = 1 ) {
	$todos      = jelly_ar_eventos_calendario_todos();
	$por_pagina = max( 1, (int) $por_pagina );
	$paginas    = max( 1, (int) ceil( count( $todos ) / $por_pagina ) );
	$pagina     = min( max( 1, (int) $pagina ), $paginas );

	return [
		'eventos' => array_slice( $todos, ( $pagina - 1 ) * $por_pagina, $por_pagina ),
		'pagina'  => $pagina,
		'paginas' => $paginas,
		'total'   => count( $todos ),
	];
}

/* ---------- A pesquisa do site ---------- */

/**
 * Os eventos que a pesquisa do site encontra, já na forma dos resultados dela
 * (template-parts de pesquisa do tema): tipo, etiqueta, titulo, url, contexto.
 *
 * Os mesmos que o site mostra — publicados e com "Onde aparece" no site —,
 * passados incluídos, como a pesquisa sempre os teve. Um evento que ainda está
 * no calendário leva à página e ao cartão dele (/calendario/?pg=2#evento-7);
 * um que já passou leva ao calendário.
 *
 * @param int $por_pagina Quantos a página Calendário mostra por página, para o
 *                        link acertar na página do cartão.
 */
function jelly_ar_eventos_pesquisa( $termo, $limite = 50, $por_pagina = 12 ) {
	global $wpdb;

	$termo = trim( (string) $termo );

	if ( mb_strlen( $termo ) < 2 ) {
		return [];
	}

	/*
	 * Primeiro os que ainda vêm, do mais próximo para o mais longe — quem
	 * procura "MIPCOM" quer o próximo —, e depois os que já passaram, do mais
	 * recente para trás.
	 */
	$like   = '%' . $wpdb->esc_like( $termo ) . '%';
	$hoje   = current_time( 'Y-m-d' );
	$linhas = jelly_ar_eventos_consulta(
		$wpdb->prepare(
			"WHERE e.estado = 'publicado' AND (e.titulo LIKE %s OR e.resumo LIKE %s OR e.local LIKE %s)
			ORDER BY COALESCE(e.fim, e.inicio) < %s, CASE WHEN COALESCE(e.fim, e.inicio) >= %s THEN e.inicio END, e.inicio DESC, e.id
			LIMIT %d",
			$like,
			$like,
			$like,
			$hoje,
			$hoje,
			max( 1, (int) $limite )
		)
	);

	// Em que página do calendário está cada evento que ainda lá está.
	$posicao = array_flip( wp_list_pluck( jelly_ar_eventos_calendario_todos(), 'id' ) );
	$pagina  = get_page_by_path( 'calendario' );
	$base    = $pagina ? get_permalink( $pagina ) : home_url( '/' );

	return array_map( function ( $l ) use ( $posicao, $base, $por_pagina ) {
		$e   = jelly_ar_evento_da_linha( $l );
		$url = $base;

		if ( isset( $posicao[ $e['id'] ] ) ) {
			$pag = intdiv( $posicao[ $e['id'] ], max( 1, (int) $por_pagina ) ) + 1;
			$url = ( $pag > 1 ? add_query_arg( 'pg', $pag, $base ) : $base ) . '#evento-' . $e['id'];
		}

		$data = DateTime::createFromFormat( '!Ymd', $e['inicio'] );

		return [
			'tipo'     => 'apit_evento',
			'etiqueta' => __( 'Eventos', 'jelly-area-reservada' ),
			'titulo'   => $e['titulo'],
			'url'      => $url,
			'contexto' => implode( ' · ', array_filter( [
				$data ? jelly_ar_data( 'j \d\e F \d\e Y', $data->getTimestamp() ) : '',
				$e['local'],
			] ) ),
			'externo'  => false,
		];
	}, $linhas );
}

/* ---------- As datas escritas ---------- */

/**
 * As datas escritas, as mesmas no cartão do calendário do site e no
 * back-office — uma função só, para os dois dizerem sempre o mesmo:
 *
 *   12 out 2026 · 6–9 out 2026 · 30 nov – 3 dez 2026 · 30 dez 2026 – 2 jan 2027
 */
/**
 * Uma data em português, sem depender das traduções do WordPress: num servidor
 * sem o pacote pt_PT, o wp_date() dá "october" e "Wed". Aceita as letras do
 * date() que a AR usa — D (qua), l (quarta-feira), j, d, M (out), F (outubro),
 * Y, H, i — e \ para escrever uma letra tal como está ("j \d\e F").
 * Os dias e os meses vão em minúscula, como se escrevem em português.
 */
function jelly_ar_data( $formato, $timestamp ) {
	$meses  = [ 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro' ];
	$curtos = [ 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez' ];
	$dias   = [ 'domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado' ];
	$dcurto = [ 'dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb' ];

	// As datas da AR são dias do calendário, sem fuso: lidas tal como estão.
	$d = ( new DateTime( '@' . (int) $timestamp ) )->setTimezone( new DateTimeZone( 'UTC' ) );
	$r = '';

	for ( $i = 0, $n = strlen( $formato ); $i < $n; $i++ ) {
		$c = $formato[ $i ];

		if ( '\\' === $c && $i + 1 < $n ) {
			$r .= $formato[ ++$i ];
			continue;
		}

		switch ( $c ) {
			case 'D':
				$r .= $dcurto[ (int) $d->format( 'w' ) ];
				break;
			case 'l':
				$r .= $dias[ (int) $d->format( 'w' ) ];
				break;
			case 'M':
				$r .= $curtos[ (int) $d->format( 'n' ) - 1 ];
				break;
			case 'F':
				$r .= $meses[ (int) $d->format( 'n' ) - 1 ];
				break;
			case 'j':
			case 'd':
			case 'Y':
			case 'H':
			case 'i':
				$r .= $d->format( $c );
				break;
			default:
				$r .= $c;
		}
	}

	return $r;
}

function jelly_ar_intervalo_datas( $inicio, $fim ) {
	$a = DateTime::createFromFormat( '!Ymd', (string) $inicio );
	$b = DateTime::createFromFormat( '!Ymd', (string) $fim );

	if ( ! $a ) {
		return '—';
	}

	$mes = function ( $d ) {
		return jelly_ar_data( 'M', $d->getTimestamp() );
	};

	if ( ! $b || $b <= $a ) {
		return $a->format( 'j' ) . ' ' . $mes( $a ) . ' ' . $a->format( 'Y' );
	}

	if ( $a->format( 'Y' ) !== $b->format( 'Y' ) ) {
		return $a->format( 'j' ) . ' ' . $mes( $a ) . ' ' . $a->format( 'Y' ) . ' – ' . $b->format( 'j' ) . ' ' . $mes( $b ) . ' ' . $b->format( 'Y' );
	}

	if ( $a->format( 'm' ) !== $b->format( 'm' ) ) {
		return $a->format( 'j' ) . ' ' . $mes( $a ) . ' – ' . $b->format( 'j' ) . ' ' . $mes( $b ) . ' ' . $b->format( 'Y' );
	}

	return $a->format( 'j' ) . '–' . $b->format( 'j' ) . ' ' . $mes( $b ) . ' ' . $b->format( 'Y' );
}
