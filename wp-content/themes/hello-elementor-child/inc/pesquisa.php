<?php
/**
 * A pesquisa do site.
 *
 * Quatro tipos de conteúdo: páginas, notícias, documentos e eventos. Os dois
 * últimos são conteúdos privados — não têm página própria nem endereço — e é de
 * propósito: dar-lhes página seria inventar 28 fichas magras que ninguém pediu.
 * Em vez disso a pesquisa olha para eles e o resultado leva onde a coisa vive:
 * o documento abre o PDF, o evento vai ao calendário, na sua vez.
 *
 * `exclude_from_search` nesses dois tipos só vale quando a consulta não nomeia
 * os tipos. Ao nomeá-los em `pre_get_posts`, entram — que é exactamente o que
 * essa bandeira serve para permitir: ficam fora do "tudo" e dentro do que se
 * pede pelo nome.
 *
 * O painel do cabeçalho vai buscar os resultados a uma rota REST própria, e não
 * ao admin-ajax: é leitura pública, sem sessão nem estado, e uma rota nomeada
 * diz o que faz.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Os tipos que a pesquisa cobre, por ordem de apresentação.
 */
function apit_pesquisa_tipos() {
	return apply_filters( 'apit_pesquisa_tipos', array(
		'apit_documento' => __( 'Documentos', 'apit' ),
		'post'           => __( 'Notícias', 'apit' ),
		'apit_evento'    => __( 'Eventos', 'apit' ),
		'page'           => __( 'Páginas', 'apit' ),
	) );
}

/**
 * A consulta de pesquisa do site passa a olhar para os quatro tipos.
 */
function apit_pesquisa_consulta( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$query->set( 'post_type', array_keys( apit_pesquisa_tipos() ) );
	$query->set( 'posts_per_page', 30 );

	/*
	 * A página de exemplo do WordPress e a de privacidade não são conteúdo do
	 * site; aparecerem numa busca por "página" seria ruído.
	 */
	$fora = array_filter( array(
		(int) get_option( 'wp_page_for_privacy_policy' ),
		(int) ( get_page_by_path( 'pagina-exemplo' )->ID ?? 0 ),
	) );

	if ( $fora ) {
		$query->set( 'post__not_in', $fora );
	}
}
add_action( 'pre_get_posts', 'apit_pesquisa_consulta' );

/**
 * Onde é que um resultado leva.
 *
 * Só as páginas e as notícias têm endereço próprio. Para os outros dois o
 * destino é construído aqui, porque é aqui que se sabe o que cada um é.
 */
function apit_pesquisa_url( $post ) {
	if ( 'apit_documento' === $post->post_type ) {
		$ficheiro = apit_media_url( (string) apit_campo( 'documento_ficheiro', $post->ID ), 'img' );

		if ( $ficheiro ) {
			return $ficheiro;
		}

		$pagina = get_page_by_path( 'documentos' );

		return $pagina ? get_permalink( $pagina ) : home_url( '/' );
	}

	if ( 'apit_evento' === $post->post_type ) {
		$pagina = get_page_by_path( 'calendario' );

		return ( $pagina ? get_permalink( $pagina ) : home_url( '/' ) ) . '#evento-' . $post->ID;
	}

	return get_permalink( $post );
}

/**
 * A linha de contexto por baixo do título — o que distingue dois resultados
 * parecidos. Cada tipo tem a sua: a área do documento, a data do evento, a
 * categoria e a data da notícia.
 */
function apit_pesquisa_contexto( $post ) {
	if ( 'apit_documento' === $post->post_type ) {
		$areas = wp_get_post_terms( $post->ID, 'apit_area_documento', array( 'fields' => 'names' ) );

		return is_array( $areas ) && $areas ? $areas[0] : __( 'Documento', 'apit' );
	}

	if ( 'apit_evento' === $post->post_type ) {
		$data  = get_post_meta( $post->ID, 'apit_evento_data', true );
		$local = get_post_meta( $post->ID, 'apit_evento_local', true );
		$carimbo = $data ? strtotime( $data ) : false;

		$partes = array_filter( array(
			$carimbo ? wp_date( 'j \d\e F \d\e Y', $carimbo ) : '',
			$local,
		) );

		return implode( ' · ', $partes );
	}

	if ( 'post' === $post->post_type ) {
		$cats = get_the_category( $post->ID );

		return implode( ' · ', array_filter( array(
			$cats ? $cats[0]->name : '',
			get_the_date( 'j \d\e F \d\e Y', $post ),
		) ) );
	}

	/*
	 * Numa página do Elementor o `post_content` é a cópia em texto que o plugin
	 * guarda, e essa cópia traz os shortcodes tal e qual — o resumo da
	 * Internacionalização começava por `[apit_hero_media][apit_wordmark
	 * texto="WORLD"]`. `strip_shortcodes()` tira-os; sem isso o visitante lia
	 * código onde devia ler a página.
	 *
	 * E a cópia é escrita de cima a baixo, pelo que começa pelo hero: migalha,
	 * título, primeira frase. A migalha não diz nada a quem está a ler
	 * resultados, por isso sai também — é o que o padrão apanha.
	 */
	if ( has_excerpt( $post ) ) {
		return wp_trim_words( get_the_excerpt( $post ), 18, '…' );
	}

	$resumo = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$resumo = preg_replace( '~^\s*Home\s*/[^\n]{0,80}?(\n|\s{2,})~u', '', $resumo );

	/*
	 * A seguir à migalha vem o título, que já está na linha de cima do
	 * resultado. Repeti-lo gastava metade do espaço a dizer o mesmo duas vezes.
	 */
	$titulo = get_the_title( $post );
	$limpo  = ltrim( $resumo );

	if ( $titulo && 0 === mb_stripos( $limpo, $titulo ) ) {
		$resumo = mb_substr( $limpo, mb_strlen( $titulo ) );
	}

	return wp_trim_words( $resumo, 18, '…' );
}

/**
 * Um resultado, na forma que tanto o template como a rota REST usam — para as
 * duas apresentações nunca divergirem no que mostram.
 */
function apit_pesquisa_resultado( $post ) {
	$tipos = apit_pesquisa_tipos();

	/*
	 * Descodificado uma vez, aqui. O que sai daqui é texto, não HTML: o
	 * template passa-o por esc_html e o JavaScript escapa-o por sua conta. Sem
	 * isto lia-se "Mercados &amp; Feiras", porque é assim que o WordPress
	 * guarda o & no nome da categoria.
	 */
	$texto = static function ( $valor ) {
		return wp_specialchars_decode( (string) $valor, ENT_QUOTES );
	};

	return array(
		'tipo'     => $post->post_type,
		'etiqueta' => $tipos[ $post->post_type ] ?? '',
		'titulo'   => $texto( get_the_title( $post ) ),
		'url'      => apit_pesquisa_url( $post ),
		'contexto' => $texto( apit_pesquisa_contexto( $post ) ),
		'externo'  => 'apit_documento' === $post->post_type,
	);
}

/**
 * Os resultados agrupados por tipo, pela ordem de apit_pesquisa_tipos().
 *
 * @param string $termo   O que se procura.
 * @param int    $por_tipo Quantos mostrar de cada grupo. 0 = todos.
 */
function apit_pesquisa_agrupada( $termo, $por_tipo = 0 ) {
	$termo = trim( (string) $termo );

	if ( mb_strlen( $termo ) < 2 ) {
		return array();
	}

	$grupos = array();

	foreach ( apit_pesquisa_tipos() as $tipo => $etiqueta ) {
		$posts = get_posts( array(
			's'                => $termo,
			'post_type'        => $tipo,
			'post_status'      => 'publish',
			'posts_per_page'   => $por_tipo > 0 ? $por_tipo : 50,
			'suppress_filters' => false,
		) );

		if ( ! $posts ) {
			continue;
		}

		$grupos[ $tipo ] = array(
			'etiqueta'   => $etiqueta,
			'resultados' => array_map( 'apit_pesquisa_resultado', $posts ),
		);
	}

	return $grupos;
}

/**
 * A rota que o painel do cabeçalho consulta enquanto se escreve.
 */
function apit_pesquisa_rota() {
	register_rest_route( 'apit/v1', '/pesquisa', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'q' => array(
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
		'callback'            => function ( $pedido ) {
			$termo = (string) $pedido->get_param( 'q' );

			return rest_ensure_response( array(
				'termo'  => $termo,
				'grupos' => array_values( apit_pesquisa_agrupada( $termo, 4 ) ),
				'url'    => home_url( '/?s=' . rawurlencode( $termo ) ),
			) );
		},
	) );
}
add_action( 'rest_api_init', 'apit_pesquisa_rota' );
