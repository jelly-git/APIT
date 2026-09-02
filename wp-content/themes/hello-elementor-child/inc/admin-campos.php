<?php
/**
 * Editing UI for the custom fields in wp-admin.
 *
 * register_post_meta declares a field and its sanitising, but it draws no
 * control — without this file the fields exist and are readable, yet the only
 * way to set them is code, which is no use to whoever maintains the site.
 *
 * One table drives the boxes, the saving and the list columns, so adding a
 * field means adding a line here and nothing else.
 */

defined( 'ABSPATH' ) || exit;

/**
 * post type => box title + fields.
 *
 * Field keys are the meta keys. "coluna" marks the ones worth showing in the
 * post list, so the client can see who holds which role without opening each
 * entry.
 */
function apit_campos_admin() {
	$orgaos = [];

	foreach ( apit_get_orgaos() as $slug => $orgao ) {
		$orgaos[ $slug ] = $orgao['nome'];
	}

	return [
		'apit_equipa' => [
			'titulo' => __( 'Dados do membro', 'apit' ),
			'campos' => [
				'apit_equipa_cargo' => [
					'etiqueta' => __( 'Cargo', 'apit' ),
					'tipo'     => 'text',
					'ajuda'    => __( 'Ex.: Presidente Executiva. Aparece sob o nome, na página Sobre a APIT.', 'apit' ),
					'coluna'   => __( 'Cargo', 'apit' ),
				],
			],
		],

		'apit_orgao_social' => [
			'titulo' => __( 'Órgão e função', 'apit' ),
			'campos' => [
				'apit_orgao_social_orgao' => [
					'etiqueta' => __( 'Órgão', 'apit' ),
					'tipo'     => 'select',
					'opcoes'   => $orgaos,
					'ajuda'    => __( 'Determina em que linha da secção Órgãos Sociais aparece.', 'apit' ),
					'coluna'   => __( 'Órgão', 'apit' ),
				],
				'apit_orgao_social_cargo' => [
					'etiqueta' => __( 'Função', 'apit' ),
					'tipo'     => 'text',
					'ajuda'    => __( 'Ex.: Presidente, Vice-Presidente, Secretário, Vogal.', 'apit' ),
					'coluna'   => __( 'Função', 'apit' ),
				],
			],
		],

		'apit_evento' => [
			'titulo' => __( 'Dados do evento', 'apit' ),
			'campos' => [
				'apit_evento_data' => [
					'etiqueta' => __( 'Data', 'apit' ),
					'tipo'     => 'date',
					'ajuda'    => __( 'Alimenta o dia e o mês no canto do cartão, e a ordem do calendário.', 'apit' ),
					'coluna'   => __( 'Data', 'apit' ),
				],
				'apit_evento_categoria' => [
					'etiqueta'  => __( 'Categoria', 'apit' ),
					'tipo'      => 'text',
					// Free text: it is printed on the pill as typed, and the colour
					// is looked up from the sanitised form of whatever is entered.
					'sugestoes' => array_keys( apit_categoria_cores() ),
					'ajuda'     => __( 'Texto da etiqueta sobre o cartão. A cor do cartão vem desta categoria — as sugeridas já têm cor atribuída.', 'apit' ),
					'coluna'    => __( 'Categoria', 'apit' ),
				],
				'apit_evento_local' => [
					'etiqueta' => __( 'Local', 'apit' ),
					'tipo'     => 'text',
					'ajuda'    => __( 'Ex.: Lisboa.', 'apit' ),
				],
				'apit_evento_acao_texto' => [
					'etiqueta' => __( 'Texto do botão', 'apit' ),
					'tipo'     => 'text',
					'ajuda'    => __( 'Opcional. Ex.: Marcar reunião. Deixe vazio para o cartão não ter botão.', 'apit' ),
				],
				'apit_evento_acao_url' => [
					'etiqueta' => __( 'Link do botão', 'apit' ),
					'tipo'     => 'url',
				],
			],
		],
	];
}

function apit_registar_meta_boxes() {
	foreach ( apit_campos_admin() as $tipo => $caixa ) {
		add_meta_box(
			'apit-campos-' . $tipo,
			$caixa['titulo'],
			'apit_render_meta_box',
			$tipo,
			'normal',
			'high',
			[ 'campos' => $caixa['campos'] ]
		);
	}
}
add_action( 'add_meta_boxes', 'apit_registar_meta_boxes' );

function apit_render_meta_box( $post, $metabox ) {
	$campos = $metabox['args']['campos'];

	wp_nonce_field( 'apit_guardar_campos', 'apit_campos_nonce' );

	echo '<table class="form-table" role="presentation"><tbody>';

	foreach ( $campos as $chave => $campo ) {
		$valor = get_post_meta( $post->ID, $chave, true );
		$id    = esc_attr( $chave );

		echo '<tr><th scope="row"><label for="' . $id . '">' . esc_html( $campo['etiqueta'] ) . '</label></th><td>';

		if ( 'select' === $campo['tipo'] ) {
			echo '<select id="' . $id . '" name="' . $id . '">';
			echo '<option value="">' . esc_html__( '— Selecionar —', 'apit' ) . '</option>';

			foreach ( $campo['opcoes'] as $opcao_valor => $opcao_texto ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $opcao_valor ),
					selected( $valor, $opcao_valor, false ),
					esc_html( $opcao_texto )
				);
			}

			echo '</select>';
		} else {
			$lista = '';

			if ( ! empty( $campo['sugestoes'] ) ) {
				$lista = $id . '-sugestoes';

				echo '<datalist id="' . esc_attr( $lista ) . '">';

				foreach ( $campo['sugestoes'] as $sugestao ) {
					echo '<option value="' . esc_attr( $sugestao ) . '"></option>';
				}

				echo '</datalist>';
			}

			printf(
				'<input type="%s" id="%s" name="%s" value="%s" class="regular-text"%s>',
				esc_attr( $campo['tipo'] ),
				$id,
				$id,
				esc_attr( $valor ),
				$lista ? ' list="' . esc_attr( $lista ) . '"' : ''
			);
		}

		if ( ! empty( $campo['ajuda'] ) ) {
			echo '<p class="description">' . esc_html( $campo['ajuda'] ) . '</p>';
		}

		echo '</td></tr>';
	}

	echo '</tbody></table>';
}

/**
 * Values go through update_post_meta, so the sanitize_callback each field was
 * registered with is what cleans them — the rules stay in one place.
 */
function apit_guardar_campos( $post_id, $post ) {
	$tabela = apit_campos_admin();

	if ( ! isset( $tabela[ $post->post_type ] ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['apit_campos_nonce'] ) || ! wp_verify_nonce( $_POST['apit_campos_nonce'], 'apit_guardar_campos' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array_keys( $tabela[ $post->post_type ]['campos'] ) as $chave ) {
		if ( ! isset( $_POST[ $chave ] ) ) {
			continue;
		}

		update_post_meta( $post_id, $chave, wp_unslash( $_POST[ $chave ] ) );
	}
}
add_action( 'save_post', 'apit_guardar_campos', 10, 2 );

/* -------------------------------------------------------------------------
 * Post list columns
 * ---------------------------------------------------------------------- */

/**
 * Adds a column for each field marked with "coluna", after the title.
 */
function apit_colunas_admin() {
	foreach ( apit_campos_admin() as $tipo => $caixa ) {
		add_filter( "manage_{$tipo}_posts_columns", function ( $colunas ) use ( $caixa ) {
			$novas = [];

			foreach ( $colunas as $chave => $etiqueta ) {
				$novas[ $chave ] = $etiqueta;

				if ( 'title' === $chave ) {
					foreach ( $caixa['campos'] as $meta => $campo ) {
						if ( ! empty( $campo['coluna'] ) ) {
							$novas[ $meta ] = $campo['coluna'];
						}
					}
				}
			}

			return $novas;
		} );

		add_action( "manage_{$tipo}_posts_custom_column", function ( $coluna, $post_id ) use ( $caixa ) {
			if ( ! isset( $caixa['campos'][ $coluna ] ) ) {
				return;
			}

			$valor = get_post_meta( $post_id, $coluna, true );
			$campo = $caixa['campos'][ $coluna ];

			// A select stores a slug; show the label the editor picked.
			if ( 'select' === $campo['tipo'] && isset( $campo['opcoes'][ $valor ] ) ) {
				$valor = $campo['opcoes'][ $valor ];
			}

			echo $valor ? esc_html( $valor ) : '—';
		}, 10, 2 );
	}
}
add_action( 'admin_init', 'apit_colunas_admin' );
