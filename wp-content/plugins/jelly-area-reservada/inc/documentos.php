<?php
/**
 * Documentos no back-office: a lista, com a pesquisa, os filtros e a ordem.
 *
 * Como os documentos e os ficheiros ficam guardados está em
 * inc/documentos-dados.php. A lista segue a de utilizadores: o estado no
 * endereço (inc/lista.php), a pesquisa, o filtro e a ordem numa função só.
 */

defined( 'ABSPATH' ) || exit;

const JELLY_AR_DOC_ESTADOS = [ 'publicado', 'rascunho' ];

// As categorias vêm da tabela: jelly_ar_documento_categorias(), em inc/documentos-dados.php.

function jelly_ar_documentos_lista() {
	return new Jelly_AR_Lista(
		'documentos',
		[
			'estado'    => '',
			'categoria' => '',
		],
		'data',
		function ( $valores ) {
			$ordenaveis = [ 'titulo', 'categoria', 'data', 'descargas' ];

			if ( '' === $valores['estado'] ) {
				$ordenaveis[] = 'estado';
			}

			return $ordenaveis;
		},
		[
			'estado'    => JELLY_AR_DOC_ESTADOS,
			'categoria' => array_keys( jelly_ar_documento_categorias() ),
		],
		'desc'
	);
}

/**
 * "25/09/2026 14:32" numa forma que se compara: "202609251432".
 */
function jelly_ar_data_ordenavel( $data ) {
	return preg_replace( '#^(\d{2})/(\d{2})/(\d{4}) (\d{2}):(\d{2})$#', '$3$2$1$4$5', $data );
}

/**
 * Aplica a pesquisa, a categoria, o estado e a ordem. Como nos utilizadores,
 * `encontrados` leva só a pesquisa e a categoria, para os números dos
 * separadores de estado.
 */
function jelly_ar_documentos_filtrar( $documentos, $pedido ) {
	$categorias  = jelly_ar_documento_categorias();
	$encontrados = array_values( array_filter( $documentos, function ( $d ) use ( $pedido, $categorias ) {
		if ( $pedido['categoria'] && $d['categoria'] !== $pedido['categoria'] ) {
			return false;
		}

		return '' === $pedido['q']
			|| Jelly_AR_Lista::contem( $pedido['q'], [ $d['titulo'], $d['descricao'], $d['ficheiro'], $categorias[ $d['categoria'] ] ?? '' ] );
	} ) );

	$lista = $pedido['estado'] ? array_values( array_filter( $encontrados, function ( $d ) use ( $pedido ) {
		return $d['estado'] === $pedido['estado'];
	} ) ) : $encontrados;

	$ordenar = $pedido['ordenar'];
	$ordem   = $pedido['ordem'];

	usort( $lista, function ( $a, $b ) use ( $ordenar, $ordem, $categorias ) {
		switch ( $ordenar ) {
			case 'data':
				$r = jelly_ar_data_ordenavel( $a['data'] ) <=> jelly_ar_data_ordenavel( $b['data'] );
				break;
			case 'descargas':
				$r = $a['descargas'] <=> $b['descargas'];
				break;
			case 'estado':
				$r = array_search( $a['estado'], JELLY_AR_DOC_ESTADOS, true ) <=> array_search( $b['estado'], JELLY_AR_DOC_ESTADOS, true );
				break;
			case 'categoria':
				return Jelly_AR_Lista::comparar_texto( $categorias[ $a['categoria'] ] ?? '', $categorias[ $b['categoria'] ] ?? '', $ordem );
			default:
				return Jelly_AR_Lista::comparar_texto( $a['titulo'], $b['titulo'], $ordem );
		}

		return 'desc' === $ordem ? -$r : $r;
	} );

	return [
		'encontrados' => $encontrados,
		'lista'       => $lista,
	];
}

/**
 * Os documentos: os guardados de verdade e, enquanto JELLY_AR_EXEMPLO for
 * true, os de exemplo a seguir, para a lista não parecer vazia. Os de exemplo
 * não têm ficheiro nem se apagam.
 */
function jelly_ar_documentos_todos() {
	$reais = jelly_ar_documentos_reais();

	if ( ! JELLY_AR_EXEMPLO ) {
		return $reais;
	}

	require_once JELLY_AR_DIR . 'inc/admin-exemplo.php';

	return array_merge( $reais, jelly_ar_exemplo_documentos() );
}

/**
 * Quem descarregou um documento: da tabela, se for real; dos exemplos, se não.
 */
function jelly_ar_descargas_de( $doc, $limite = 0 ) {
	if ( ! empty( $doc['real'] ) ) {
		return jelly_ar_descargas_reais( $doc['id'], $limite );
	}

	require_once JELLY_AR_DIR . 'inc/admin-exemplo.php';

	$linhas = jelly_ar_exemplo_descargas( $doc['id'] );

	return $limite ? array_slice( $linhas, 0, $limite ) : $linhas;
}

/**
 * Um documento pelo id, real ou de exemplo.
 */
function jelly_ar_documento( $id ) {
	foreach ( jelly_ar_documentos_todos() as $d ) {
		if ( $d['id'] === $id ) {
			return $d;
		}
	}

	return null;
}

/**
 * O ícone e a cor de cada tipo de ficheiro.
 */
function jelly_ar_icone_ficheiro( $tipo ) {
	$icones = [
		'pdf'  => 'fa-file-pdf',
		'docx' => 'fa-file-word',
		'xlsx' => 'fa-file-excel',
		'pptx' => 'fa-file-powerpoint',
		'zip'  => 'fa-file-zipper',
	];

	return $icones[ $tipo ] ?? 'fa-file';
}
