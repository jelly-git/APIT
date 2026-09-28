<?php
/**
 * Calendário: as marcações de mesa no tempo, mês a mês.
 *
 * À esquerda, a grelha do mês (de segunda a domingo), com o número de
 * marcações de cada dia — confirmadas e por aprovar. À direita, as marcações
 * do dia escolhido, pela hora: o associado, o evento e a mesa, com o caminho
 * para a grelha do evento e, nas pendentes, para as Aprovações.
 *
 * O mês e o evento vêm no endereço (`mes=2026-10`, `evento=<id>`); o dia
 * escolhe-se no ecrã (assets/js/admin.js, data-jar-calendario), sem
 * recarregar. Só as marcações vivas: as rejeitadas e as canceladas não
 * ocupam o dia.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$mes_pedido = isset( $_GET['mes'] ) ? sanitize_text_field( wp_unslash( $_GET['mes'] ) ) : '';
$evento_id  = isset( $_GET['evento'] ) ? absint( $_GET['evento'] ) : 0;
// phpcs:enable

$hoje   = current_time( 'Y-m-d' );
$inicio = DateTime::createFromFormat( '!Y-m-d', ( preg_match( '/^\d{4}-\d{2}$/', $mes_pedido ) ? $mes_pedido : current_time( 'Y-m' ) ) . '-01' );
$fim    = ( clone $inicio )->modify( 'last day of this month' );

// A grelha começa na segunda-feira antes do dia 1 e acaba no domingo depois do último.
$grelha_de  = ( clone $inicio )->modify( '-' . ( ( (int) $inicio->format( 'N' ) ) - 1 ) . ' days' );
$grelha_ate = ( clone $fim )->modify( '+' . ( 7 - (int) $fim->format( 'N' ) ) . ' days' );

$marcacoes = jelly_ar_marcacoes_entre( $grelha_de->format( 'Y-m-d' ), $grelha_ate->format( 'Y-m-d' ), $evento_id );

// Os números do mês (só os dias do mês, não os de fora da grelha).
$no_mes = [];
foreach ( $marcacoes as $dia => $lista ) {
	if ( substr( $dia, 0, 7 ) === $inicio->format( 'Y-m' ) ) {
		$no_mes = array_merge( $no_mes, $lista );
	}
}
$estados = array_count_values( wp_list_pluck( $no_mes, 'estado' ) );

// O dia aberto ao entrar: hoje, se for deste mês; senão, o primeiro com marcações; senão, o dia 1.
$aberto = substr( $hoje, 0, 7 ) === $inicio->format( 'Y-m' ) ? $hoje : '';
if ( ! $aberto ) {
	foreach ( array_keys( $marcacoes ) as $dia ) {
		if ( substr( $dia, 0, 7 ) === $inicio->format( 'Y-m' ) ) {
			$aberto = $dia;
			break;
		}
	}
}
$aberto = $aberto ? $aberto : $inicio->format( 'Y-m-d' );

// Os eventos com marcações, para o filtro.
$eventos = [];
foreach ( jelly_ar_eventos_todos() as $e ) {
	if ( $e['marcacoes'] ) {
		$eventos[ $e['id'] ] = $e['titulo'];
	}
}

$url = function ( $args ) use ( $evento_id ) {
	return jelly_ar_admin_url( 'calendario', array_filter( $args + [ 'evento' => $evento_id ] ) );
};

$anterior = ( clone $inicio )->modify( '-1 month' )->format( 'Y-m' );
$seguinte = ( clone $inicio )->modify( '+1 month' )->format( 'Y-m' );
$semana   = [ 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom' ];

// "qua, 7 out" / "quarta-feira, 7 de outubro".
$rotulo_dia = function ( $ymd, $formato ) {
	$d = DateTime::createFromFormat( '!Y-m-d', $ymd );

	return $d ? jelly_ar_data( $formato, $d->getTimestamp() ) : $ymd;
};
?>
<div class="jar-cabeca">
	<div>
		<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Calendário', 'jelly-area-reservada' ); ?></h1>
		<p class="jar-cabeca__intro"><?php esc_html_e( 'As marcações de mesa, dia a dia.', 'jelly-area-reservada' ); ?></p>
	</div>
</div>

<div class="jar-numeros">
	<?php
	foreach (
		[
			[ __( 'Marcações no mês', 'jelly-area-reservada' ), count( $no_mes ), 'fa-calendar-days', 'azul' ],
			[ __( 'Confirmadas', 'jelly-area-reservada' ), $estados['aprovada'] ?? 0, 'fa-circle-check', 'turquesa' ],
			[ __( 'Por aprovar', 'jelly-area-reservada' ), $estados['pendente'] ?? 0, 'fa-hourglass-half', 'roxo' ],
			[ __( 'Dias com marcações', 'jelly-area-reservada' ), count( array_unique( array_filter( array_keys( $marcacoes ), function ( $d ) use ( $inicio ) {
				return substr( $d, 0, 7 ) === $inicio->format( 'Y-m' );
			} ) ) ), 'fa-calendar-check', 'magenta' ],
		] as $n
	) :
		?>
		<div class="jar-cartao jar-numero">
			<span class="jar-icone jar-icone--<?php echo esc_attr( $n[3] ); ?>"><i class="fa-solid <?php echo esc_attr( $n[2] ); ?>" aria-hidden="true"></i></span>
			<span class="jar-numero__rotulo"><?php echo esc_html( $n[0] ); ?></span>
			<strong class="jar-numero__valor"><?php echo (int) $n[1]; ?></strong>
		</div>
	<?php endforeach; ?>
</div>

<div class="jar-calendario" data-jar-calendario>
	<section class="jar-cartao jar-calendario__mes">
		<header class="jar-calendario__barra">
			<div class="jar-calendario__navegar">
				<a class="jar-acao" href="<?php echo esc_url( $url( [ 'mes' => $anterior ] ) ); ?>" aria-label="<?php esc_attr_e( 'Mês anterior', 'jelly-area-reservada' ); ?>" title="<?php esc_attr_e( 'Mês anterior', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
				<h2><?php echo esc_html( ucfirst( jelly_ar_data( 'F Y', $inicio->getTimestamp() ) ) ); ?></h2>
				<a class="jar-acao" href="<?php echo esc_url( $url( [ 'mes' => $seguinte ] ) ); ?>" aria-label="<?php esc_attr_e( 'Mês seguinte', 'jelly-area-reservada' ); ?>" title="<?php esc_attr_e( 'Mês seguinte', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
				<?php if ( substr( $hoje, 0, 7 ) !== $inicio->format( 'Y-m' ) ) : ?>
					<a class="jar-btn jar-btn--pequeno" href="<?php echo esc_url( $url( [] ) ); ?>"><?php esc_html_e( 'Hoje', 'jelly-area-reservada' ); ?></a>
				<?php endif; ?>
			</div>

			<?php if ( $eventos ) : ?>
				<form class="jar-escolha" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" data-jar-auto>
					<input type="hidden" name="page" value="<?php echo esc_attr( jelly_ar_admin_slug( 'calendario' ) ); ?>">
					<input type="hidden" name="mes" value="<?php echo esc_attr( $inicio->format( 'Y-m' ) ); ?>">
					<label class="screen-reader-text" for="jar-calendario-evento"><?php esc_html_e( 'Evento', 'jelly-area-reservada' ); ?></label>
					<select id="jar-calendario-evento" name="evento">
						<option value=""><?php esc_html_e( 'Todos os eventos', 'jelly-area-reservada' ); ?></option>
						<?php foreach ( $eventos as $eid => $titulo ) : ?>
							<option value="<?php echo (int) $eid; ?>" <?php selected( $eid, $evento_id ); ?>><?php echo esc_html( $titulo ); ?></option>
						<?php endforeach; ?>
					</select>
					<noscript><button type="submit" class="jar-btn jar-btn--pequeno jar-btn--contorno"><?php esc_html_e( 'Aplicar', 'jelly-area-reservada' ); ?></button></noscript>
				</form>
			<?php endif; ?>
		</header>

		<div class="jar-calendario__grelha" role="grid" aria-label="<?php echo esc_attr( ucfirst( jelly_ar_data( 'F Y', $inicio->getTimestamp() ) ) ); ?>">
			<div class="jar-calendario__semana" role="row">
				<?php foreach ( $semana as $s ) : ?>
					<span role="columnheader"><?php echo esc_html( $s ); ?></span>
				<?php endforeach; ?>
			</div>

			<?php for ( $d = clone $grelha_de; $d <= $grelha_ate; $d->modify( '+7 days' ) ) : ?>
				<div class="jar-calendario__linha" role="row">
					<?php for ( $i = 0; $i < 7; $i++ ) : ?>
						<?php
						$dia    = ( clone $d )->modify( "+{$i} days" );
						$ymd    = $dia->format( 'Y-m-d' );
						$lista  = $marcacoes[ $ymd ] ?? [];
						$contas = array_count_values( wp_list_pluck( $lista, 'estado' ) );
						$classe = 'jar-calendario__dia'
							. ( $dia->format( 'm' ) !== $inicio->format( 'm' ) ? ' is-fora' : '' )
							. ( $ymd === $hoje ? ' is-hoje' : '' )
							. ( $lista ? ' tem-marcacoes' : '' )
							. ( $ymd === $aberto ? ' is-escolhido' : '' );
						/* translators: 1: dia, 2: número de marcações */
						$rotulo = $lista ? sprintf( _n( '%1$s: %2$d marcação', '%1$s: %2$d marcações', count( $lista ), 'jelly-area-reservada' ), $rotulo_dia( $ymd, 'l, j \d\e F' ), count( $lista ) ) : $rotulo_dia( $ymd, 'l, j \d\e F' );
						?>
						<button type="button" class="<?php echo esc_attr( $classe ); ?>" role="gridcell" data-jar-calendario-dia="<?php echo esc_attr( $ymd ); ?>" aria-label="<?php echo esc_attr( $rotulo ); ?>" aria-pressed="<?php echo $ymd === $aberto ? 'true' : 'false'; ?>">
							<span class="jar-calendario__numero"><?php echo (int) $dia->format( 'j' ); ?></span>
							<?php if ( $lista ) : ?>
								<span class="jar-calendario__marcas" aria-hidden="true">
									<?php if ( ! empty( $contas['aprovada'] ) ) : ?>
										<span class="jar-calendario__marca jar-calendario__marca--aprovada"><?php echo (int) $contas['aprovada']; ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $contas['pendente'] ) ) : ?>
										<span class="jar-calendario__marca jar-calendario__marca--pendente"><?php echo (int) $contas['pendente']; ?></span>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</button>
					<?php endfor; ?>
				</div>
			<?php endfor; ?>
		</div>

		<ul class="jar-legenda jar-calendario__legenda">
			<li><span class="jar-calendario__marca jar-calendario__marca--aprovada" aria-hidden="true">2</span> <?php esc_html_e( 'Confirmadas', 'jelly-area-reservada' ); ?></li>
			<li><span class="jar-calendario__marca jar-calendario__marca--pendente" aria-hidden="true">1</span> <?php esc_html_e( 'Por aprovar', 'jelly-area-reservada' ); ?></li>
		</ul>
	</section>

	<?php
	/*
	 * As marcações de cada dia da grelha, escritas já todas: o clique num dia
	 * mostra a sua e esconde as outras, sem ir ao servidor.
	 */
	?>
	<section class="jar-cartao jar-calendario__dias">
		<?php for ( $d = clone $grelha_de; $d <= $grelha_ate; $d->modify( '+1 day' ) ) : ?>
			<?php
			$ymd   = $d->format( 'Y-m-d' );
			$lista = $marcacoes[ $ymd ] ?? [];
			?>
			<div class="jar-calendario__lista" data-jar-calendario-lista="<?php echo esc_attr( $ymd ); ?>" <?php echo $ymd === $aberto ? '' : 'hidden'; ?>>
				<header class="jar-cartao__cabeca">
					<div>
						<h2><?php echo esc_html( ucfirst( $rotulo_dia( $ymd, 'l, j \d\e F' ) ) ); ?></h2>
						<span class="jar-cartao__meta">
							<?php
							echo esc_html(
								$lista
									/* translators: %d: número de marcações */
									? sprintf( _n( '%d marcação', '%d marcações', count( $lista ), 'jelly-area-reservada' ), count( $lista ) )
									: __( 'Sem marcações', 'jelly-area-reservada' )
							);
							?>
						</span>
					</div>
				</header>

				<?php if ( ! $lista ) : ?>
					<p class="jar-vazio"><?php esc_html_e( 'Não há marcações neste dia.', 'jelly-area-reservada' ); ?></p>
				<?php else : ?>
					<ol class="jar-calendario__marcacoes">
						<?php foreach ( $lista as $m ) : ?>
							<?php
							// O estado vai na barra de cor à esquerda (magenta confirmada, roxo por aprovar); por aprovar diz-se também por extenso.
							$pendente = 'pendente' === $m['estado'];
							?>
							<li class="jar-calendario__marcacao jar-calendario__marcacao--<?php echo esc_attr( $m['estado'] ); ?>" title="<?php echo esc_attr( $pendente ? __( 'Por aprovar', 'jelly-area-reservada' ) : __( 'Confirmada', 'jelly-area-reservada' ) ); ?>">
								<span class="jar-calendario__hora"><?php echo esc_html( $m['hora'] ); ?></span>
								<span class="jar-calendario__quem">
									<strong><?php echo esc_html( $m['quem'] ? $m['quem'] : __( 'Associado removido', 'jelly-area-reservada' ) ); ?></strong>
									<?php if ( $pendente ) : ?>
										<em class="jar-calendario__estado"><?php esc_html_e( 'Por aprovar', 'jelly-area-reservada' ); ?></em>
									<?php endif; ?>
									<small><?php echo esc_html( implode( ' · ', array_filter( [ $m['empresa'], $m['mesa'], $m['evento'] ] ) ) ); ?></small>
								</span>
								<span class="jar-calendario__acoes">
									<?php if ( $pendente ) : ?>
										<a class="jar-acao" href="<?php echo esc_url( jelly_ar_admin_url( 'marcacoes', [ 'evento' => $m['evento_id'] ] ) ); ?>" aria-label="<?php esc_attr_e( 'Decidir em Aprovações', 'jelly-area-reservada' ); ?>" title="<?php esc_attr_e( 'Decidir em Aprovações', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></a>
									<?php endif; ?>
									<a class="jar-acao" href="<?php echo esc_url( jelly_ar_admin_url( 'mesas', [ 'evento' => $m['evento_id'], 'separador' => 'grelha' ] ) . '#jar-dia-' . $ymd ); ?>" aria-label="<?php esc_attr_e( 'Ver na grelha', 'jelly-area-reservada' ); ?>" title="<?php esc_attr_e( 'Ver na grelha', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i></a>
								</span>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</div>
		<?php endfor; ?>
	</section>
</div>
