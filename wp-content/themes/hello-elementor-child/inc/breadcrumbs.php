<?php
/**
 * The breadcrumb, worked out from where the page actually is.
 *
 * It used to be HTML typed into a text editor on each page — eight copies of
 * the same line, and each one a promise that nobody would ever rename a page.
 * Rename one and the trail starts lying, silently, on a page nobody opens for
 * weeks. The trail is a fact about the site's shape, so the site is what should
 * say it.
 *
 * Pages follow `post_parent`: Todos os Associados is a child of Associados and
 * says so without anyone writing it down. The page itself closes the trail and
 * is not a link — it is where the visitor already is.
 *
 * An article is not a page and has no parent, so its trail is built from what
 * the Notícias page and the article's category say: Home / Notícias / a
 * categoria. The article's own title is the heading right underneath and would
 * only be said twice.
 *
 * The Home is left out of its own trail: one item is not a path.
 *
 * `apit_breadcrumbs_itens` is there for the day a page has to show a level that
 * the hierarchy does not have — the Documentos page is drawn under "APIT" in
 * the menu while living at the root — so that it is a line of code and not HTML
 * typed into the page again.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The trail, as a list of [ texto, url ]. An empty url means the current page.
 */
function apit_breadcrumbs_itens() {
	$itens = array(
		array(
			'texto' => __( 'Home', 'apit' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_singular( 'post' ) ) {
		$noticias = get_page_by_path( 'noticias' );

		if ( $noticias ) {
			$itens[] = array(
				'texto' => get_the_title( $noticias ),
				'url'   => get_permalink( $noticias ),
			);
		}

		$categorias = get_the_category();
		$categoria  = $categorias ? $categorias[0] : null;

		if ( $categoria ) {
			$itens[] = array(
				'texto' => $categoria->name,
				/*
				 * The archive filtered by this category, which is the page the
				 * Notícias list already links to — not WordPress's own category
				 * archive, which this site never renders.
				 */
				'url'   => function_exists( 'apit_noticias_url_categoria' )
					? apit_noticias_url_categoria( $categoria->slug )
					: get_category_link( $categoria ),
			);
		}

		return $itens;
	}

	if ( is_page() ) {
		$id = get_queried_object_id();

		// get_post_ancestors gives the nearest parent first; the trail reads the other way.
		foreach ( array_reverse( get_post_ancestors( $id ) ) as $ascendente ) {
			$itens[] = array(
				'texto' => get_the_title( $ascendente ),
				'url'   => get_permalink( $ascendente ),
			);
		}

		$itens[] = array(
			'texto' => get_the_title( $id ),
			'url'   => '',
		);
	}

	return $itens;
}

/**
 * The trail as markup, or an empty string where there is no path to show.
 *
 * @param string $classe A classe do `<nav>`. Cada hero tem a sua, porque cada
 *                       um tem a sua folha de estilo: a Sobre a APIT desenha
 *                       `.sobre-hero__crumb` em sobre.css e as outras
 *                       `.pagina-hero__crumb` em paginas.css.
 */
function apit_breadcrumbs( $classe = 'pagina-hero__crumb' ) {
	/**
	 * Filters the breadcrumb trail before it is drawn.
	 *
	 * @param array $itens Each one [ 'texto' => string, 'url' => string ].
	 */
	$itens = apply_filters( 'apit_breadcrumbs_itens', apit_breadcrumbs_itens() );

	if ( ! is_array( $itens ) || count( $itens ) < 2 ) {
		return '';
	}

	$partes = array();

	foreach ( $itens as $item ) {
		$texto = trim( (string) ( $item['texto'] ?? '' ) );

		if ( '' === $texto ) {
			continue;
		}

		$url = trim( (string) ( $item['url'] ?? '' ) );

		$partes[] = '' === $url
			? '<span aria-current="page">' . esc_html( $texto ) . '</span>'
			: '<a href="' . esc_url( $url ) . '">' . esc_html( $texto ) . '</a>';
	}

	if ( count( $partes ) < 2 ) {
		return '';
	}

	return '<nav class="' . esc_attr( $classe ) . '" aria-label="' . esc_attr__( 'Onde está', 'apit' ) . '">'
		. implode( ' / ', $partes )
		. '</nav>';
}

/*
 * A migalha entra sozinha na coluna do hero.
 *
 * Nem um widget nem um shortcode: qualquer um dos dois obrigava a pôr alguma
 * coisa em cada página, e a que se esquecesse ficava sem trilho — que é o mesmo
 * problema de o escrever à mão, só que mais discreto. Uma página nova feita com
 * este hero passa a trazer a migalha por existir.
 *
 * O sítio é o primeiro lugar dentro da coluna de texto do hero, que é onde a
 * migalha estava quando era escrita à mão. `elementor/frontend/the_content`
 * entrega a página já desenhada, que é o único momento em que essa coluna
 * existe — o Elementor não tem gancho para "dentro deste contentor, antes do
 * resto".
 *
 * O ficheiro single.php não passa por aqui: o hero dele é do tema e chama
 * apit_breadcrumbs() directamente.
 */

/**
 * Onde a migalha entra, e com que classe: contentor => classe do `<nav>`.
 *
 * São dois porque há dois heros — o das seis páginas e o da Sobre a APIT, que
 * tem desenho e folha de estilo próprios. Um hero novo acrescenta aqui a sua
 * linha: uma vez, em vez de um widget por página.
 */
function apit_breadcrumbs_ancoras() {
	return apply_filters( 'apit_breadcrumbs_ancoras', array(
		'pagina-hero__col'       => 'pagina-hero__crumb',
		'sobre-hero__col--texto' => 'sobre-hero__crumb',
	) );
}

function apit_breadcrumbs_no_hero( $html ) {
	if ( ! is_string( $html ) || '' === $html || is_admin() ) {
		return $html;
	}

	// No editor mostra-se a página como está guardada, sem nada acrescentado.
	if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $html;
	}

	foreach ( apit_breadcrumbs_ancoras() as $contentor => $classe ) {
		$migalha = apit_breadcrumbs( $classe );

		if ( '' === $migalha ) {
			return $html;
		}

		/*
		 * preg_replace_callback e não preg_replace: a migalha é o texto de
		 * substituição e leva títulos de páginas lá dentro. Um "$1" num título —
		 * ou uma barra invertida — seria lido como referência e comia o
		 * resultado.
		 */
		$feito = preg_replace_callback(
			'#<div[^>]*\sclass="[^"]*\b' . preg_quote( $contentor, '#' ) . '\b[^"]*"[^>]*>#',
			function ( $m ) use ( $migalha ) {
				return $m[0] . $migalha;
			},
			$html,
			1,
			$trocas
		);

		if ( $trocas ) {
			return $feito;
		}
	}

	return $html;
}
add_filter( 'elementor/frontend/the_content', 'apit_breadcrumbs_no_hero' );
