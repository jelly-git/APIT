<?php
/**
 * Aprovações: os pedidos de marcação de mesa, por estado — Por aprovar
 * primeiro. Os pendentes aprovam-se ou rejeitam-se aqui, um a um ou vários
 * escolhidos; a decisão vai para jelly_ar_aprovacoes_decidir()
 * (inc/aprovacoes.php), e a grelha do evento fica logo com o estado novo.
 */

defined( 'ABSPATH' ) || exit;

$tabela    = jelly_ar_aprovacoes_lista();
$marcacoes = jelly_ar_marcacoes_todas();
$filtro    = $tabela->get( 'estado' );
$pesquisa  = $tabela->get( 'q' );

$resultado   = jelly_ar_aprovacoes_filtrar( $marcacoes, $tabela->pedido() );
$encontrados = $resultado['encontrados'];
$lista       = $tabela->paginar( $resultado['lista'] );
$contagem    = array_count_values( array_column( $encontrados, 'estado' ) );
$todos       = array_count_values( array_column( $marcacoes, 'estado' ) );

// Os eventos que têm marcações, para o filtro.
$eventos = [];
foreach ( $marcacoes as $m ) {
	$eventos[ $m['evento_id'] ] = $m['evento'];
}
asort( $eventos );

$aviso     = isset( $_GET['aviso'] ) ? sanitize_key( wp_unslash( $_GET['aviso'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$n         = isset( $_GET['n'] ) ? absint( $_GET['n'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sem_email = isset( $_GET['sem-email'] ) ? absint( $_GET['sem-email'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$avisos = [
	/* translators: %d: número de pedidos */
	'aprovada'  => sprintf( _n( '%d pedido aprovado. O associado foi avisado por e-mail, e a grelha já mostra o lugar confirmado.', '%d pedidos aprovados. Cada associado foi avisado por e-mail, e a grelha já mostra os lugares confirmados.', $n, 'jelly-area-reservada' ), $n ),
	/* translators: %d: número de pedidos */
	'rejeitada' => sprintf( _n( '%d pedido rejeitado. O associado foi avisado por e-mail, e o lugar voltou a ficar livre.', '%d pedidos rejeitados. Cada associado foi avisado por e-mail, e os lugares voltaram a ficar livres.', $n, 'jelly-area-reservada' ), $n ),
];

$filtros = [
	'pendente'  => __( 'Por aprovar', 'jelly-area-reservada' ),
	'aprovada'  => __( 'Aprovadas', 'jelly-area-reservada' ),
	'rejeitada' => __( 'Rejeitadas', 'jelly-area-reservada' ),
	'cancelada' => __( 'Canceladas', 'jelly-area-reservada' ),
	''          => __( 'Todas', 'jelly-area-reservada' ),
];
$resumo  = [
	[ __( 'Por aprovar', 'jelly-area-reservada' ), $todos['pendente'] ?? 0, 'fa-hourglass-half', 'roxo', 'pendente' ],
	[ __( 'Aprovadas', 'jelly-area-reservada' ), $todos['aprovada'] ?? 0, 'fa-circle-check', 'turquesa', 'aprovada' ],
	[ __( 'Rejeitadas', 'jelly-area-reservada' ), $todos['rejeitada'] ?? 0, 'fa-circle-xmark', 'magenta', 'rejeitada' ],
	[ __( 'Eventos com marcações', 'jelly-area-reservada' ), count( $eventos ), 'fa-earth-europe', 'azul', null ],
];

$pendentes_na_pagina = array_filter( $lista, function ( $m ) {
	return 'pendente' === $m['estado'];
} );

// "Qua, 7 out · 10:00".
$quando = function ( $m ) {
	$d = DateTime::createFromFormat( '!Y-m-d', $m['dia'] );

	return ( $d ? ucfirst( jelly_ar_data( 'D, j M', $d->getTimestamp() ) ) : $m['dia'] ) . ' · ' . $m['hora'];
};
?>
<div class="jar-cabeca">
	<div>
		<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Aprovações', 'jelly-area-reservada' ); ?></h1>
		<p class="jar-cabeca__intro"><?php esc_html_e( 'Os pedidos de marcação de mesa feitos pelos associados no calendário do site.', 'jelly-area-reservada' ); ?></p>
	</div>
</div>

<?php if ( isset( $avisos[ $aviso ] ) ) : ?>
	<div class="jar-aviso jar-aviso--sucesso" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><p><?php echo esc_html( $avisos[ $aviso ] ); ?></p></div>
	<?php if ( $sem_email ) : ?>
		<?php /* translators: %d: número de e-mails */ ?>
		<div class="jar-aviso jar-aviso--pendente" role="alert"><i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i><p><?php echo esc_html( sprintf( _n( '%d e-mail não foi enviado: a decisão está gravada, mas o associado não foi avisado. Convém confirmar a configuração do SMTP.', '%d e-mails não foram enviados: as decisões estão gravadas, mas esses associados não foram avisados. Convém confirmar a configuração do SMTP.', $sem_email, 'jelly-area-reservada' ), $sem_email ) ); ?></p></div>
	<?php endif; ?>
<?php elseif ( 'nenhuma' === $aviso ) : ?>
	<div class="jar-aviso jar-aviso--suspenso" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><p><?php esc_html_e( 'Nenhum pedido por aprovar estava escolhido, por isso nada mudou.', 'jelly-area-reservada' ); ?></p></div>
<?php endif; ?>

<div class="jar-numeros">
	<?php foreach ( $resumo as $r ) : ?>
		<?php
		$tag  = null === $r[4] ? 'div' : 'a';
		$href = null === $r[4] ? '' : ' href="' . esc_url( jelly_ar_admin_url( 'marcacoes', 'pendente' === $r[4] ? [] : [ 'estado' => $r[4] ] ) ) . '"';
		?>
		<<?php echo esc_html( $tag ); ?> class="jar-cartao jar-numero"<?php echo $href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado acima ?>>
			<span class="jar-icone jar-icone--<?php echo esc_attr( $r[3] ); ?>"><i class="fa-solid <?php echo esc_attr( $r[2] ); ?>" aria-hidden="true"></i></span>
			<span class="jar-numero__rotulo"><?php echo esc_html( $r[0] ); ?></span>
			<strong class="jar-numero__valor"><?php echo (int) $r[1]; ?></strong>
		</<?php echo esc_html( $tag ); ?>>
	<?php endforeach; ?>
</div>

<section class="jar-cartao jar-cartao--tabela jar-aprovacoes">
	<div class="jar-barra">
		<nav class="jar-separadores" aria-label="<?php esc_attr_e( 'Filtrar por estado', 'jelly-area-reservada' ); ?>">
			<?php foreach ( $filtros as $f => $rotulo ) : ?>
				<?php $conta = '' === $f ? count( $encontrados ) : ( $contagem[ $f ] ?? 0 ); ?>
				<a class="jar-separador<?php echo $f === $filtro ? ' is-atual' : ''; ?>" href="<?php echo esc_url( $tabela->url( [ 'estado' => $f ] ) ); ?>"<?php echo $f === $filtro ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $rotulo ); ?> <b><?php echo (int) $conta; ?></b>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="jar-barra__filtros">
			<form class="jar-escolha" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" data-jar-auto>
				<?php $tabela->campos_escondidos( [ 'evento' ] ); ?>
				<label class="screen-reader-text" for="jar-evento"><?php esc_html_e( 'Evento', 'jelly-area-reservada' ); ?></label>
				<select id="jar-evento" name="evento">
					<option value=""><?php esc_html_e( 'Todos os eventos', 'jelly-area-reservada' ); ?></option>
					<?php foreach ( $eventos as $eid => $titulo ) : ?>
						<option value="<?php echo (int) $eid; ?>" <?php selected( (string) $eid, (string) $tabela->get( 'evento' ) ); ?>><?php echo esc_html( $titulo ); ?></option>
					<?php endforeach; ?>
				</select>
				<noscript><button type="submit" class="jar-btn jar-btn--pequeno jar-btn--contorno"><?php esc_html_e( 'Aplicar', 'jelly-area-reservada' ); ?></button></noscript>
			</form>

			<form class="jar-filtro" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search" data-jar-pesquisa>
				<?php $tabela->campos_escondidos( [ 'q' ] ); ?>
				<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
				<label class="screen-reader-text" for="jar-pesquisa"><?php esc_html_e( 'Pesquisar marcações', 'jelly-area-reservada' ); ?></label>
				<input type="search" id="jar-pesquisa" name="q" value="<?php echo esc_attr( $pesquisa ); ?>" placeholder="<?php esc_attr_e( 'Associado, empresa, evento ou mesa', 'jelly-area-reservada' ); ?>">
			</form>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="jar-aprovacoes" data-jar-escolhas>
		<input type="hidden" name="action" value="jelly_ar_aprovacoes">
		<input type="hidden" name="voltar" value="<?php echo esc_url( $tabela->url( [ 'pagina' => $tabela->get( 'pagina' ) ] ) ); ?>">
		<?php wp_nonce_field( 'jelly_ar_aprovacoes' ); ?>

		<?php // Com pedidos por aprovar nesta página: decidir os escolhidos de uma vez. ?>
		<?php if ( $pendentes_na_pagina ) : ?>
			<div class="jar-aprovacoes__barra" data-jar-escolhas-barra>
				<span data-jar-escolhas-conta><?php esc_html_e( 'Escolha os pedidos para os decidir de uma vez.', 'jelly-area-reservada' ); ?></span>
				<button type="button" class="jar-btn jar-btn--pequeno jar-btn--perigo" data-jar-precisa-escolha disabled
					data-jar-confirmar
					data-jar-form="jar-aprovacoes"
					data-titulo="<?php esc_attr_e( 'Rejeitar os pedidos escolhidos?', 'jelly-area-reservada' ); ?>"
					data-texto="<?php esc_attr_e( 'Os lugares voltam a ficar livres na grelha, e cada associado recebe um e-mail a informar que o pedido não foi aprovado.', 'jelly-area-reservada' ); ?>"
					data-sim="<?php esc_attr_e( 'Rejeitar', 'jelly-area-reservada' ); ?>"
					data-resultado=""
				><i class="fa-solid fa-xmark" aria-hidden="true"></i> <?php esc_html_e( 'Rejeitar', 'jelly-area-reservada' ); ?></button>
				<button type="submit" name="aprovar" value="escolhidas" class="jar-btn jar-btn--pequeno" data-jar-precisa-escolha disabled><i class="fa-solid fa-check" aria-hidden="true"></i> <?php esc_html_e( 'Aprovar', 'jelly-area-reservada' ); ?></button>
			</div>
		<?php endif; ?>

		<table class="jar-tabela">
			<thead>
				<tr>
					<th class="jar-tabela__escolha">
						<?php if ( $pendentes_na_pagina ) : ?>
							<label class="jar-caixa"><input type="checkbox" data-jar-escolhas-todas><span class="screen-reader-text"><?php esc_html_e( 'Escolher todos os pedidos por aprovar', 'jelly-area-reservada' ); ?></span></label>
						<?php endif; ?>
					</th>
					<?php
					$tabela->coluna( 'nome', __( 'Associado', 'jelly-area-reservada' ) );
					$tabela->coluna( 'evento', __( 'Evento', 'jelly-area-reservada' ), 'jar-col--evento' );
					$tabela->coluna( 'quando', __( 'Dia e hora', 'jelly-area-reservada' ), 'jar-col--quando' );
					?>
					<th class="jar-col--mesa"><?php esc_html_e( 'Mesa', 'jelly-area-reservada' ); ?></th>
					<?php
					$tabela->coluna( 'pedido', __( 'Pedido', 'jelly-area-reservada' ), 'jar-col--data' );
					$tabela->coluna( 'estado', __( 'Estado', 'jelly-area-reservada' ), 'jar-col--estado' );
					?>
					<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $lista ) : ?>
					<tr>
						<td colspan="8" class="jar-vazio">
							<?php
							if ( '' !== $pesquisa ) {
								/* translators: %s: o que se pesquisou */
								printf( esc_html__( 'Nenhuma marcação corresponde a "%s".', 'jelly-area-reservada' ), esc_html( $pesquisa ) );
							} elseif ( 'pendente' === $filtro ) {
								esc_html_e( 'Não há pedidos por aprovar.', 'jelly-area-reservada' );
							} else {
								esc_html_e( 'Nenhuma marcação nesta lista.', 'jelly-area-reservada' );
							}
							?>
						</td>
					</tr>
				<?php endif; ?>
				<?php foreach ( $lista as $m ) : ?>
					<?php
					$pendente = 'pendente' === $m['estado'];
					$grelha   = jelly_ar_admin_url( 'mesas', [ 'evento' => $m['evento_id'], 'separador' => 'grelha' ] ) . '#jar-dia-' . $m['dia'];
					?>
					<tr data-jar-linha>
						<td class="jar-tabela__escolha">
							<?php if ( $pendente ) : ?>
								<?php /* translators: %s: associado */ ?>
								<label class="jar-caixa"><input type="checkbox" name="marcacao[]" value="<?php echo (int) $m['id']; ?>" data-jar-escolha><span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'Escolher o pedido de %s', 'jelly-area-reservada' ), $m['nome'] ) ); ?></span></label>
							<?php endif; ?>
						</td>
						<td>
							<span class="jar-pessoa">
								<span class="jar-avatar" aria-hidden="true"><?php echo esc_html( jelly_ar_iniciais( $m['nome'] ) ); ?></span>
								<span>
									<strong><?php echo esc_html( $m['nome'] ? $m['nome'] : '—' ); ?></strong>
									<small><?php echo esc_html( $m['empresa'] ? $m['empresa'] : $m['email'] ); ?></small>
									<?php // No telemóvel as colunas do dia e da mesa saem, e o essencial vem para aqui. ?>
									<small class="jar-aprovacoes__movel"><?php echo esc_html( $quando( $m ) . ' · ' . $m['mesa'] ); ?></small>
								</span>
							</span>
						</td>
						<td class="jar-col--evento"><?php echo esc_html( $m['evento'] ); ?></td>
						<td class="jar-tabela__num jar-col--quando"><?php echo esc_html( $quando( $m ) ); ?></td>
						<td class="jar-col--mesa">
							<?php echo esc_html( $m['mesa'] ); ?>
							<?php if ( $m['localizacao'] ) : ?>
								<small class="jar-aprovacoes__local"><?php echo esc_html( $m['localizacao'] ); ?></small>
							<?php endif; ?>
						</td>
						<td class="jar-tabela__num jar-col--data"><?php jelly_ar_data_hora( $m['pedido'] ); ?></td>
						<td class="jar-col--estado"><?php jelly_ar_estado( $m['estado'] ); ?></td>
						<td class="jar-tabela__fim">
							<span class="jar-acoes">
								<?php if ( $pendente ) : ?>
									<?php /* translators: %s: associado */ ?>
									<button type="submit" name="aprovar" value="<?php echo (int) $m['id']; ?>" class="jar-acao jar-acao--sim" aria-label="<?php echo esc_attr( sprintf( __( 'Aprovar o pedido de %s', 'jelly-area-reservada' ), $m['nome'] ) ); ?>" title="<?php esc_attr_e( 'Aprovar', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
									<button
										type="button"
										class="jar-acao jar-acao--nao"
										data-jar-confirmar
										data-jar-form="jar-rejeitar-<?php echo (int) $m['id']; ?>"
										<?php /* translators: %s: associado */ ?>
										data-titulo="<?php echo esc_attr( sprintf( __( 'Rejeitar o pedido de %s?', 'jelly-area-reservada' ), $m['nome'] ) ); ?>"
										data-texto="<?php esc_attr_e( 'O lugar volta a ficar livre na grelha, e o associado recebe um e-mail a informar que o pedido não foi aprovado.', 'jelly-area-reservada' ); ?>"
										data-sim="<?php esc_attr_e( 'Rejeitar', 'jelly-area-reservada' ); ?>"
										data-resultado=""
										<?php /* translators: %s: associado */ ?>
										aria-label="<?php echo esc_attr( sprintf( __( 'Rejeitar o pedido de %s', 'jelly-area-reservada' ), $m['nome'] ) ); ?>"
										title="<?php esc_attr_e( 'Rejeitar', 'jelly-area-reservada' ); ?>"
									><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
								<?php endif; ?>
								<a class="jar-acao" href="<?php echo esc_url( $grelha ); ?>" aria-label="<?php esc_attr_e( 'Ver na grelha', 'jelly-area-reservada' ); ?>" title="<?php esc_attr_e( 'Ver na grelha', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i></a>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</form>

	<?php
	// Rejeitar uma linha: o formulário dela, fora do da lista (não se aninham), que a confirmação envia.
	foreach ( $pendentes_na_pagina as $m ) :
		?>
		<form id="jar-rejeitar-<?php echo (int) $m['id']; ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
			<input type="hidden" name="action" value="jelly_ar_aprovacoes">
			<input type="hidden" name="marcacao[]" value="<?php echo (int) $m['id']; ?>">
			<input type="hidden" name="voltar" value="<?php echo esc_url( $tabela->url( [ 'pagina' => $tabela->get( 'pagina' ) ] ) ); ?>">
			<?php wp_nonce_field( 'jelly_ar_aprovacoes', '_wpnonce', false ); ?>
		</form>
	<?php endforeach; ?>

	<?php $tabela->paginacao(); ?>
</section>
