<?php
/**
 * ACF Pro glue.
 *
 * Field groups live as JSON in the theme's acf-json folder, not in the
 * database. ACF writes them there whenever a group is saved in wp-admin and
 * reads them from there on every request, so a field created locally travels
 * to the server in the same commit as the template that uses it. Without this,
 * a new field would exist only on the machine it was created on.
 */

defined( 'ABSPATH' ) || exit;

function apit_acf_pasta_json() {
	return get_stylesheet_directory() . '/acf-json';
}

add_filter( 'acf/settings/save_json', 'apit_acf_pasta_json' );

/**
 * Replaces ACF's default load path rather than adding to it, so the theme's
 * folder is the only source and there is no chance of two copies of a group
 * disagreeing.
 */
function apit_acf_carregar_json( $caminhos ) {
	unset( $caminhos[0] );

	$caminhos[] = apit_acf_pasta_json();

	return $caminhos;
}
add_filter( 'acf/settings/load_json', 'apit_acf_carregar_json' );

/**
 * Reads a field, and keeps working when ACF is not installed.
 *
 * The plugin is licensed and therefore not in the repository, so a deploy can
 * land on a site where it is missing or deactivated. Returning null there means
 * the sections render empty instead of taking the whole page down with a fatal.
 */
function apit_campo( $nome, $post_id = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	return get_field( $nome, $post_id ?? get_the_ID() );
}

/**
 * Fills the Órgão dropdown from the órgãos registered in post-types.php, so
 * adding a fourth órgão there makes it selectable here without the field group
 * being touched.
 */
function apit_acf_opcoes_orgao( $campo ) {
	$campo['choices'] = [];

	foreach ( apit_get_orgaos() as $slug => $orgao ) {
		$campo['choices'][ $slug ] = $orgao['nome'];
	}

	return $campo;
}
add_filter( 'acf/load_field/name=apit_orgao_social_orgao', 'apit_acf_opcoes_orgao' );

/**
 * Suggests the categories that already have a colour mapped, while leaving the
 * field free text — the value is printed on the card as typed, and the colour
 * is resolved from whatever is entered.
 */
function apit_acf_ajuda_categoria( $campo ) {
	$categorias = implode( ', ', array_keys( apit_categoria_cores() ) );

	$campo['instructions'] = sprintf(
		/* translators: %s: comma-separated list of category slugs. */
		__( 'Texto da etiqueta sobre o cartão. A cor do cartão vem desta categoria. Já têm cor atribuída: %s.', 'apit' ),
		$categorias
	);

	return $campo;
}
add_filter( 'acf/load_field/name=apit_evento_categoria', 'apit_acf_ajuda_categoria' );
