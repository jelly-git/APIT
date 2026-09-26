<?php
/**
 * O que as listas do back-office têm em comum — utilizadores, documentos e as
 * que vierem: o estado da lista no endereço, os cabeçalhos ordenáveis, a
 * paginação e os campos escondidos dos formulários de pesquisa.
 *
 * Tudo o que define uma lista vem no endereço — filtros, pesquisa, ordem,
 * página e tamanho da página — e é feito no servidor. Com dados reais passa a
 * ser a consulta à base de dados, com LIMIT e OFFSET; o endereço de uma lista
 * serve para a partilhar tal como está, e a exportação lê-o para exportar a
 * mesma lista.
 *
 * Cada área diz os seus filtros e valores por omissão, e que colunas se
 * ordenam; o que a lista contém e como se compara fica com a área.
 */

defined( 'ABSPATH' ) || exit;

class Jelly_AR_Lista {

	const TAMANHOS = [ 10, 20, 50, 100 ];

	/** A área do back-office, para os endereços: 'utilizadores', 'documentos'… */
	private $area;

	/** Os valores por omissão; as chaves são tudo o que a lista lê do endereço. */
	private $omissao;

	/** Para cada filtro de valores fixos, os valores aceites. */
	private $validos;

	/** Recebe os valores da lista e devolve as colunas ordenáveis nesse estado. */
	private $ordenaveis;

	private $valores = [];

	public $total    = 0;
	public $paginas  = 1;
	public $primeiro = 0;
	public $mostrados = 0;

	/**
	 * @param string   $area       A área do back-office.
	 * @param array    $filtros    Filtros próprios da área, com o valor por omissão (['estado' => '']).
	 * @param string   $ordenar    A coluna por que se ordena por omissão.
	 * @param callable $ordenaveis function ( $valores ) : string[].
	 * @param array    $validos    Para os filtros de valores fixos, os aceites (['estado' => ['ativo', …]]).
	 * @param string   $ordem      A direção por omissão: "asc", ou "desc" para os mais recentes primeiro.
	 */
	public function __construct( $area, $filtros, $ordenar, $ordenaveis, $validos = [], $ordem = 'asc' ) {
		$this->area       = $area;
		$this->validos    = $validos;
		$this->ordenaveis = $ordenaveis;
		$this->omissao    = array_merge(
			$filtros,
			[
				'q'          => '',
				'ordenar'    => $ordenar,
				'ordem'      => $ordem,
				'por_pagina' => 10,
				'pagina'     => 1,
			]
		);

		$this->ler_pedido();
	}

	/*
	 * Lê e valida o endereço. Um valor que não se reconhece volta ao de omissão,
	 * em vez de chegar à consulta.
	 */
	private function ler_pedido() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		foreach ( $this->omissao as $chave => $omissao ) {
			if ( ! isset( $_GET[ $chave ] ) ) {
				$this->valores[ $chave ] = $omissao;
				continue;
			}

			$bruto = wp_unslash( $_GET[ $chave ] );

			switch ( $chave ) {
				case 'q':
					$valor = sanitize_text_field( $bruto );
					break;
				case 'pagina':
					$valor = max( 1, absint( $bruto ) );
					break;
				case 'por_pagina':
					$valor = in_array( absint( $bruto ), self::TAMANHOS, true ) ? absint( $bruto ) : $omissao;
					break;
				case 'ordem':
					$valor = 'desc' === $bruto ? 'desc' : 'asc';
					break;
				default:
					$valor = sanitize_key( $bruto );
			}

			if ( isset( $this->validos[ $chave ] ) && '' !== $valor && ! in_array( $valor, $this->validos[ $chave ], true ) ) {
				$valor = $omissao;
			}

			$this->valores[ $chave ] = $valor;
		}
		// phpcs:enable

		$this->valores = $this->corrigir_ordem( $this->valores );
	}

	/**
	 * Uma ordem que não existe no estado pedido — ordenar por Estado dentro de um
	 * separador de um estado só — volta à de omissão.
	 */
	private function corrigir_ordem( $valores ) {
		if ( ! in_array( $valores['ordenar'], call_user_func( $this->ordenaveis, $valores ), true ) ) {
			$valores['ordenar'] = $this->omissao['ordenar'];
			$valores['ordem']   = $this->omissao['ordem'];
		}

		return $valores;
	}

	public function get( $chave ) {
		return $this->valores[ $chave ];
	}

	/**
	 * Os filtros, a pesquisa e a ordem, sem a paginação — o que a exportação
	 * precisa para dar a mesma lista inteira.
	 */
	public function pedido() {
		return array_diff_key( $this->valores, [ 'pagina' => 1, 'por_pagina' => 1 ] );
	}

	public function ordenaveis() {
		return call_user_func( $this->ordenaveis, $this->valores );
	}

	/**
	 * O endereço da lista com o estado atual e as mudanças pedidas. Os valores
	 * por omissão ficam de fora, para os endereços ficarem curtos; mudar outra
	 * coisa que não a página volta à página 1.
	 */
	public function url( $mudar = [] ) {
		if ( ! array_key_exists( 'pagina', $mudar ) ) {
			$mudar['pagina'] = 1;
		}

		$args = $this->corrigir_ordem( array_merge( $this->valores, $mudar ) );

		foreach ( $this->omissao as $chave => $omissao ) {
			if ( (string) $args[ $chave ] === (string) $omissao ) {
				unset( $args[ $chave ] );
			}
		}

		return jelly_ar_admin_url( $this->area, $args );
	}

	/**
	 * Cabeçalho de coluna: ordenável é um link que alterna a direção, e diz ao
	 * leitor de ecrã como a tabela está ordenada (aria-sort).
	 */
	public function coluna( $chave, $rotulo, $classe = '' ) {
		if ( ! in_array( $chave, $this->ordenaveis(), true ) ) {
			printf( '<th class="%s">%s</th>', esc_attr( $classe ), esc_html( $rotulo ) );
			return;
		}

		$ordem = $this->valores['ordem'];
		$atual = $chave === $this->valores['ordenar'];
		$icone = $atual ? ( 'asc' === $ordem ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short' ) : 'fa-sort';

		printf(
			'<th class="%1$s" aria-sort="%2$s"><a class="jar-ordenar%3$s" href="%4$s">%5$s <i class="fa-solid %6$s" aria-hidden="true"></i></a></th>',
			esc_attr( $classe ),
			esc_attr( $atual ? ( 'asc' === $ordem ? 'ascending' : 'descending' ) : 'none' ),
			$atual ? ' is-atual' : '',
			esc_url( $this->url( [ 'ordenar' => $chave, 'ordem' => $atual && 'asc' === $ordem ? 'desc' : 'asc' ] ) ),
			esc_html( $rotulo ),
			esc_attr( $icone )
		);
	}

	/**
	 * Corta a lista na página pedida. Uma página para lá da última — um link
	 * antigo — leva à última.
	 */
	public function paginar( $lista ) {
		$por_pagina = $this->valores['por_pagina'];

		$this->total   = count( $lista );
		$this->paginas = max( 1, (int) ceil( $this->total / $por_pagina ) );

		$this->valores['pagina'] = min( $this->valores['pagina'], $this->paginas );

		$this->primeiro  = ( $this->valores['pagina'] - 1 ) * $por_pagina;
		$pagina          = array_slice( $lista, $this->primeiro, $por_pagina );
		$this->mostrados = count( $pagina );

		return $pagina;
	}

	/**
	 * Os campos escondidos de um formulário da lista, para não perder o resto ao
	 * enviá-lo. A página fica sempre de fora: um formulário volta à primeira.
	 */
	public function campos_escondidos( $menos = [] ) {
		echo '<input type="hidden" name="page" value="' . esc_attr( jelly_ar_admin_slug( $this->area ) ) . '">';

		foreach ( $this->valores as $chave => $valor ) {
			if ( in_array( $chave, $menos, true ) || 'pagina' === $chave || (string) $valor === (string) $this->omissao[ $chave ] ) {
				continue;
			}
			echo '<input type="hidden" name="' . esc_attr( $chave ) . '" value="' . esc_attr( $valor ) . '">';
		}
	}

	/**
	 * O rodapé da tabela: a contagem, o tamanho da página e os números das
	 * páginas — a primeira, a última e duas de cada lado da atual.
	 */
	public function paginacao() {
		$pagina = $this->valores['pagina'];
		$numeros = [];

		for ( $p = 1; $p <= $this->paginas; $p++ ) {
			if ( 1 === $p || $this->paginas === $p || abs( $p - $pagina ) <= 2 ) {
				$numeros[] = $p;
			} elseif ( '…' !== end( $numeros ) ) {
				$numeros[] = '…';
			}
		}
		?>
		<div class="jar-paginacao">
			<p class="jar-paginacao__contagem">
				<?php
				if ( $this->total ) {
					/* translators: 1: primeiro mostrado, 2: último mostrado, 3: total */
					printf( esc_html__( '%1$d–%2$d de %3$d', 'jelly-area-reservada' ), (int) ( $this->primeiro + 1 ), (int) ( $this->primeiro + $this->mostrados ), (int) $this->total );
				}
				?>
			</p>

			<form class="jar-paginacao__tamanho" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" data-jar-auto>
				<?php $this->campos_escondidos( [ 'por_pagina' ] ); ?>
				<label for="jar-por-pagina"><?php esc_html_e( 'Por página', 'jelly-area-reservada' ); ?></label>
				<select id="jar-por-pagina" name="por_pagina">
					<?php foreach ( self::TAMANHOS as $t ) : ?>
						<option value="<?php echo (int) $t; ?>" <?php selected( $t, $this->valores['por_pagina'] ); ?>><?php echo (int) $t; ?></option>
					<?php endforeach; ?>
				</select>
				<noscript><button type="submit" class="jar-btn jar-btn--pequeno jar-btn--contorno"><?php esc_html_e( 'Aplicar', 'jelly-area-reservada' ); ?></button></noscript>
			</form>

			<?php if ( $this->paginas > 1 ) : ?>
				<nav class="jar-paginas" aria-label="<?php esc_attr_e( 'Páginas', 'jelly-area-reservada' ); ?>">
					<?php if ( $pagina > 1 ) : ?>
						<a class="jar-pagina" href="<?php echo esc_url( $this->url( [ 'pagina' => $pagina - 1 ] ) ); ?>" aria-label="<?php esc_attr_e( 'Página anterior', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
					<?php else : ?>
						<span class="jar-pagina is-inativa" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>
					<?php endif; ?>

					<?php foreach ( $numeros as $p ) : ?>
						<?php if ( '…' === $p ) : ?>
							<span class="jar-pagina is-reticencias" aria-hidden="true">…</span>
						<?php elseif ( $p === $pagina ) : ?>
							<span class="jar-pagina is-atual" aria-current="page"><?php echo (int) $p; ?></span>
						<?php else : ?>
							<?php /* translators: %d: número da página */ ?>
							<a class="jar-pagina" href="<?php echo esc_url( $this->url( [ 'pagina' => $p ] ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Página %d', 'jelly-area-reservada' ), $p ) ); ?>"><?php echo (int) $p; ?></a>
						<?php endif; ?>
					<?php endforeach; ?>

					<?php if ( $pagina < $this->paginas ) : ?>
						<a class="jar-pagina" href="<?php echo esc_url( $this->url( [ 'pagina' => $pagina + 1 ] ) ); ?>" aria-label="<?php esc_attr_e( 'Página seguinte', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
					<?php else : ?>
						<span class="jar-pagina is-inativa" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Compara dois valores de texto para ordenar: sem acentos, para "Álvaro" não
	 * ir parar depois de "Zé", e com os vazios sempre no fim, seja qual for a
	 * direção.
	 */
	public static function comparar_texto( $a, $b, $ordem ) {
		if ( '' === (string) $a || '' === (string) $b ) {
			return ( '' === (string) $a ) - ( '' === (string) $b );
		}

		$r = mb_strtolower( remove_accents( $a ) ) <=> mb_strtolower( remove_accents( $b ) );

		return 'desc' === $ordem ? -$r : $r;
	}

	/**
	 * Uma pesquisa de texto: o termo sem acentos nem maiúsculas, dentro de
	 * qualquer dos textos dados.
	 */
	public static function contem( $termo, $textos ) {
		return false !== strpos(
			mb_strtolower( remove_accents( implode( ' ', $textos ) ) ),
			mb_strtolower( remove_accents( $termo ) )
		);
	}
}
