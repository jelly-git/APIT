<?php
/**
 * A lista de utilizadores do back-office: filtro por estado, pesquisa e ordem.
 *
 * Num sítio só, porque a servem dois: o ecrã, que a mostra às páginas, e a
 * exportação, que a descarrega inteira — e as duas têm de dar as mesmas
 * pessoas pela mesma ordem.
 */

defined( 'ABSPATH' ) || exit;

// Por estado, a ordem é a do trabalho e não a do alfabeto: primeiro o que espera.
const JELLY_AR_ORDEM_ESTADOS = [ 'pendente' => 0, 'ativo' => 1, 'suspenso' => 2, 'rejeitado' => 3 ];

/**
 * A lista com o que o endereço pede. O Estado só se ordena em "Todos": num
 * separador de um estado só, todas as linhas têm o mesmo.
 */
function jelly_ar_utilizadores_lista() {
	return new Jelly_AR_Lista(
		'utilizadores',
		[ 'estado' => '' ],
		'nome',
		function ( $valores ) {
			$ordenaveis = [ 'nome', 'email', 'empresa', 'telefone' ];

			if ( '' === $valores['estado'] ) {
				$ordenaveis[] = 'estado';
			}

			return $ordenaveis;
		},
		[ 'estado' => array_keys( JELLY_AR_ORDEM_ESTADOS ) ]
	);
}

/**
 * Aplica a pesquisa, o filtro e a ordem. Devolve `encontrados` (só com a
 * pesquisa, para os números dos separadores) e `lista` (tudo aplicado).
 */
function jelly_ar_utilizadores_filtrar( $utilizadores, $pedido ) {
	/*
	 * A pesquisa procura no nome, e-mail, empresa e telefone. O telefone compara
	 * também só em algarismos, para "912 345" dar com "+351 912345678".
	 */
	$encontrados = $utilizadores;

	if ( '' !== $pedido['q'] ) {
		$digitos  = preg_replace( '/\D/', '', $pedido['q'] );
		$numerico = strlen( $digitos ) >= 3 && preg_match( '/^[\d\s+()-]+$/', $pedido['q'] );

		$encontrados = array_values( array_filter( $utilizadores, function ( $x ) use ( $pedido, $digitos, $numerico ) {
			return Jelly_AR_Lista::contem( $pedido['q'], [ $x['nome'] . ' ' . $x['apelido'], $x['email'], $x['empresa'], $x['telefone'] ] )
				|| ( $numerico && false !== strpos( preg_replace( '/\D/', '', $x['telefone'] ), $digitos ) );
		} ) );
	}

	$lista = $pedido['estado'] ? array_values( array_filter( $encontrados, function ( $x ) use ( $pedido ) {
		return $x['estado'] === $pedido['estado'];
	} ) ) : $encontrados;

	$ordenar = $pedido['ordenar'];
	$ordem   = $pedido['ordem'];

	usort( $lista, function ( $a, $b ) use ( $ordenar, $ordem ) {
		if ( 'estado' === $ordenar ) {
			$r = ( JELLY_AR_ORDEM_ESTADOS[ $a['estado'] ] ?? 9 ) <=> ( JELLY_AR_ORDEM_ESTADOS[ $b['estado'] ] ?? 9 );

			return 'desc' === $ordem ? -$r : $r;
		}

		if ( 'nome' === $ordenar ) {
			return Jelly_AR_Lista::comparar_texto( $a['nome'] . ' ' . $a['apelido'], $b['nome'] . ' ' . $b['apelido'], $ordem );
		}

		return Jelly_AR_Lista::comparar_texto( $a[ $ordenar ], $b[ $ordenar ], $ordem );
	} );

	return [
		'encontrados' => $encontrados,
		'lista'       => $lista,
	];
}

/**
 * Os utilizadores, de onde vierem. Hoje os de exemplo; com os dados reais, a
 * leitura dos utilizadores com o papel apit_associado.
 */
function jelly_ar_utilizadores_todos() {
	require_once JELLY_AR_DIR . 'inc/admin-exemplo.php';

	return jelly_ar_exemplo_utilizadores();
}
