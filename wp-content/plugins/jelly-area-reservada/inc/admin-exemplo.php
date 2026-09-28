<?php
/**
 * Dados de exemplo do back-office.
 *
 * Só existem para se ver o desenho, enquanto JELLY_AR_EXEMPLO for true. Cada
 * função devolve a forma que a leitura real há de devolver — os campos do
 * esquema em inc/instalar.php, já formatados para mostrar —, para que os
 * templates não mudem quando os dados reais chegarem. Nessa altura este
 * ficheiro sai.
 */

defined( 'ABSPATH' ) || exit;

function jelly_ar_exemplo_utilizadores() {
	static $lista = null;

	if ( null !== $lista ) {
		return $lista;
	}

	/*
	 * Só a Ana Ferreira: os outros utilizadores de exemplo saíram. O número
	 * dela fica, porque as marcações e os acessos de exemplo usam-no.
	 */
	$lista = [
		[ 'id' => JELLY_AR_EXEMPLO_ID + 4, 'nome' => 'Ana', 'apelido' => 'Ferreira', 'empresa' => 'Norte Conteúdos', 'email' => 'ana@norteconteudos.pt', 'telefone' => '+351 936555010', 'estado' => 'ativo', 'registo' => '20/01/2026 12:10', 'aprovado' => '21/01/2026 09:30', 'aprovado_por' => 'Rui Lopes', 'ultimo' => '23/09/2026 09:12', 'acessos' => 63 ],
	];

	return $lista;
}

/**
 * Acessos de um utilizador, ou de todos com 0. Cada linha traz o nome e o
 * e-mail, como a exportação os precisa.
 */
function jelly_ar_exemplo_acessos( $utilizador = 0 ) {
	$momentos = [
		[ '25/09/2026 14:32', '185.23.45.12', 'Chrome · Windows' ],
		[ '24/09/2026 09:05', '185.23.45.12', 'Chrome · Windows' ],
		[ '21/09/2026 18:47', '89.114.20.7', 'Safari · iPhone' ],
		[ '18/09/2026 11:30', '185.23.45.12', 'Chrome · Windows' ],
		[ '12/09/2026 16:02', '89.114.20.7', 'Safari · macOS' ],
		[ '09/09/2026 10:21', '185.23.45.12', 'Chrome · Windows' ],
		[ '03/09/2026 17:44', '89.114.20.7', 'Safari · iPhone' ],
	];
	$linhas = [];

	foreach ( jelly_ar_exemplo_utilizadores() as $u ) {
		if ( ! $u['acessos'] || ( $utilizador && $u['id'] !== $utilizador ) ) {
			continue;
		}

		foreach ( $momentos as $m ) {
			$linhas[] = [
				'quando'      => $m[0],
				'nome'        => $u['nome'] . ' ' . $u['apelido'],
				'email'       => $u['email'],
				'ip'          => $m[1],
				'dispositivo' => $m[2],
			];
		}
	}

	return $linhas;
}

/**
 * As marcações de mesa de um utilizador. Vêm do módulo das marcações; aqui só
 * para mostrar onde o perfil se liga a ele.
 */
function jelly_ar_exemplo_marcacoes_de( $id ) {
	if ( JELLY_AR_EXEMPLO_ID + 4 !== $id ) {
		return [];
	}

	return [
		[ 'evento' => 'MIPCOM 2026', 'mesa' => 'Mesa 3', 'quando' => '20/10/2026 10:00', 'estado' => 'pendente' ],
		[ 'evento' => 'MIPCOM 2026', 'mesa' => 'Mesa 2', 'quando' => '21/10/2026 09:30', 'estado' => 'aprovada' ],
	];
}

/**
 * Documentos de exemplo: uma lista escrita à mão, com os títulos que a APIT
 * tem hoje no site, e o que falta para haver páginas.
 */
function jelly_ar_exemplo_documentos() {
	static $lista = null;

	if ( null !== $lista ) {
		return $lista;
	}

	$base = [
		[ 'Apresentação MIPCOM 2026', 'eventos', 'pptx', 'Apresentação da presença da APIT e do stand Watch Portugal no MIPCOM.' ],
		[ 'Guia do Associado', 'institucional', 'pdf', 'Direitos, deveres e serviços para os associados da APIT.' ],
		[ 'Relatório Anual 2025', 'institucional', 'pdf', 'Atividade, contas e números do setor em 2025.' ],
		[ 'Tabela de mesas — MIPCOM 2026', 'eventos', 'xlsx', 'Mesas disponíveis e horários de 30 minutos no stand.' ],
		[ 'Regulamento de apoios à internacionalização', 'regulamentos', 'pdf', 'Condições e prazos dos apoios à presença em mercados.' ],
		[ 'Guia de mercado — Reino Unido', 'mercados', 'pdf', 'Compradores, canais e janelas de coprodução no Reino Unido.' ],
		[ 'Guia de mercado — Espanha', 'mercados', 'pdf', 'O mercado espanhol: canais, plataformas e coprodução.' ],
		[ 'Formulário de inscrição em mercados', 'formularios', 'docx', 'Para pedir lugar na delegação portuguesa a um mercado.' ],
		[ 'Ficha de catálogo Watch Portugal', 'watch-portugal', 'docx', 'O que enviar para o catálogo Watch Portugal.' ],
		[ 'Manual de marca Watch Portugal', 'watch-portugal', 'pdf', 'Logótipos, cores e regras de uso da marca.' ],
		[ 'Logótipos Watch Portugal', 'watch-portugal', 'zip', 'Os logótipos em todos os formatos, para impressão e ecrã.' ],
		[ 'Estatutos da APIT', 'regulamentos', 'pdf', 'Os estatutos em vigor.' ],
	];

	$lista = [];
	$autores = [ 'Ana Martins', 'Rui Lopes' ];

	foreach ( $base as $i => $b ) {
		$lista[] = jelly_ar_exemplo_documento( $i, $b[0], $b[1], $b[2], $b[3], $autores[ $i % 2 ] );
	}

	// Mais guias de mercado, para a lista ter páginas.
	$paises = [ 'França', 'Alemanha', 'Itália', 'Brasil', 'México', 'Estados Unidos', 'Canadá', 'Japão', 'Coreia do Sul', 'Países Nórdicos', 'Benelux', 'Polónia', 'Angola', 'Moçambique' ];

	foreach ( $paises as $j => $pais ) {
		$i       = count( $base ) + $j;
		$lista[] = jelly_ar_exemplo_documento( $i, 'Guia de mercado — ' . $pais, 'mercados', 'pdf', 'Compradores, canais e oportunidades de coprodução: ' . $pais . '.', $autores[ $i % 2 ] );
	}

	// Os que se apagaram no back-office (jelly_ar_documento_exemplo_apagar()) já não aparecem.
	$fora  = array_map( 'intval', (array) get_option( 'jelly_ar_exemplo_documentos_apagados', [] ) );
	$lista = array_values( array_filter( $lista, function ( $d ) use ( $fora ) {
		return ! in_array( $d['id'], $fora, true );
	} ) );

	return $lista;
}

function jelly_ar_exemplo_documento( $i, $titulo, $categoria, $tipo, $descricao, $autor ) {
	$rascunho = in_array( $i, [ 3, 13, 21 ], true );

	return [
		'id'        => 900000 + $i,
		'titulo'    => $titulo,
		'descricao' => $descricao,
		'categoria' => $categoria,
		'tipo'      => $tipo,
		'ficheiro'  => sanitize_title( $titulo ) . '.' . $tipo,
		'tamanho'   => 180000 + ( $i * 377711 ) % 9000000,
		'data'      => sprintf( '%02d/%02d/2026 %02d:%02d', 1 + ( $i * 11 ) % 28, 1 + ( $i * 5 ) % 9, 9 + $i % 9, ( $i * 23 ) % 60 ),
		'estado'    => $rascunho ? 'rascunho' : 'publicado',
		'autor'     => $autor,
		'descargas' => $rascunho ? 0 : 4 + ( $i * 37 ) % 120,
	];
}

/**
 * Quem descarregou um documento. Cada linha liga ao perfil do utilizador.
 */
function jelly_ar_exemplo_descargas( $documento ) {
	$doc = current( array_filter( jelly_ar_exemplo_documentos(), function ( $d ) use ( $documento ) {
		return $d['id'] === $documento;
	} ) );

	if ( ! $doc || ! $doc['descargas'] ) {
		return [];
	}

	$ativos = array_values( array_filter( jelly_ar_exemplo_utilizadores(), function ( $u ) {
		return 'ativo' === $u['estado'];
	} ) );
	$linhas = [];

	for ( $k = 0; $k < min( 12, $doc['descargas'] ); $k++ ) {
		$u        = $ativos[ ( $documento + $k * 3 ) % count( $ativos ) ];
		$linhas[] = [
			'utilizador' => $u['id'],
			'nome'       => $u['nome'] . ' ' . $u['apelido'],
			'email'      => $u['email'],
			'empresa'    => $u['empresa'],
			'quando'     => sprintf( '%02d/09/2026 %02d:%02d', 25 - $k * 2, 9 + ( $k * 3 ) % 9, ( $k * 19 ) % 60 ),
		];
	}

	return $linhas;
}
