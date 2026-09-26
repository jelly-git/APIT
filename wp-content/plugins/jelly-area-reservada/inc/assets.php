<?php
/**
 * Folha e script do pop-up.
 *
 * Em todas as páginas do front-end, porque o botão que o abre está no
 * cabeçalho. São pequenos, e o script não faz nada enquanto ninguém carregar
 * num link da área reservada.
 */

defined( 'ABSPATH' ) || exit;

function jelly_ar_enqueue_assets() {
	/*
	 * Depois da folha do tema, para herdar os tokens --apit-* e os .btn, e poder
	 * afinar por cima deles sem subir a especificidade.
	 */
	$depende = wp_style_is( 'apit-child-style', 'registered' ) ? [ 'apit-child-style' ] : [];

	wp_enqueue_style(
		'jelly-area-reservada',
		JELLY_AR_URL . 'assets/css/area-reservada.css',
		$depende,
		jelly_ar_versao_ficheiro( 'assets/css/area-reservada.css' )
	);

	wp_enqueue_script(
		'jelly-area-reservada',
		JELLY_AR_URL . 'assets/js/area-reservada.js',
		[],
		jelly_ar_versao_ficheiro( 'assets/js/area-reservada.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'jelly_ar_enqueue_assets', 20 );
