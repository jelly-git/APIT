<?php
/**
 * Mesas e horários: a lista dos eventos que aceitam marcações, e a
 * configuração de um deles (`evento=<id>`), em três separadores:
 *
 * - Mesas     as mesas do evento: nome, localização e lugares
 * - Horários  os dias do evento, cada um com a hora de início e a de fim, e o
 *             intervalo entre marcações (30 minutos por omissão)
 * - Grelha    as mesas por hora, dia a dia, com o estado de cada bloco
 *
 * Por onde a área se liga às outras:
 * - os eventos são os de Eventos; só entram os que têm o visto "Os associados
 *   podem marcar mesas neste evento", e a página de cada evento leva aqui;
 * - os associados marcam na área deles os blocos livres da grelha, e os
 *   pedidos são aprovados em Aprovações, quando essas áreas existirem.
 */

defined( 'ABSPATH' ) || exit;

$id     = isset( $_GET['evento'] ) ? absint( $_GET['evento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$evento = $id ? jelly_ar_evento( $id ) : null;

$aviso  = isset( $_GET['aviso'] ) ? sanitize_key( wp_unslash( $_GET['aviso'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$erro   = isset( $_GET['erro'] ) ? sanitize_key( wp_unslash( $_GET['erro'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$dia_erro = isset( $_GET['dia'] ) ? sanitize_text_field( wp_unslash( $_GET['dia'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// "12 out." a partir de Y-m-d, para os dias da grelha e dos horários.
$dia_curto = function ( $ymd ) {
	$d = DateTime::createFromFormat( '!Y-m-d', $ymd );

	return $d ? wp_date( 'D, j M', $d->getTimestamp() ) : $ymd;
};

$avisos = [
	'mesa-criada'     => __( 'Mesa criada.', 'jelly-area-reservada' ),
	'mesa-atualizada' => __( 'Mesa guardada.', 'jelly-area-reservada' ),
	'mesa-apagada'    => __( 'Mesa apagada.', 'jelly-area-reservada' ),
	'horarios'        => __( 'Horários guardados. A grelha já usa os blocos novos.', 'jelly-area-reservada' ),
];
$erros  = [
	'mesa-nome'         => __( 'A mesa precisa de um nome.', 'jelly-area-reservada' ),
	'mesa-falhou'       => __( 'A mesa não foi encontrada neste evento.', 'jelly-area-reservada' ),
	'mesa-em-uso'       => __( 'Essa mesa tem marcações e não se pode apagar.', 'jelly-area-reservada' ),
	/* translators: %s: dia */
	'horario-horas'     => sprintf( __( 'Em %s, a hora de fim tem de ser depois da de início, com espaço para pelo menos um bloco.', 'jelly-area-reservada' ), $dia_erro ? $dia_curto( $dia_erro ) : '—' ),
	/* translators: %s: dia */
	'horario-marcacoes' => sprintf( __( 'Em %s há marcações que ficariam fora dos blocos novos. Nada foi gravado: o horário desse dia tem de continuar a incluí-las.', 'jelly-area-reservada' ), $dia_erro ? $dia_curto( $dia_erro ) : '—' ),
];

$mostrar_aviso = function () use ( $aviso, $erro, $avisos, $erros ) {
	if ( isset( $avisos[ $aviso ] ) ) {
		printf( '<div class="jar-aviso jar-aviso--sucesso" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><p>%s</p></div>', esc_html( $avisos[ $aviso ] ) );
	}
	if ( isset( $erros[ $erro ] ) ) {
		printf( '<div class="jar-aviso jar-aviso--suspenso" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><p>%s</p></div>', esc_html( $erros[ $erro ] ) );
	}
};

// A capa: o gradiente da categoria, como nos Eventos.
$capa = function ( $e ) {
	printf(
		'<span class="jar-evento-capa" style="--jar-cat-inicio: %1$s; --jar-cat-fim: %2$s;" aria-hidden="true"></span>',
		esc_attr( $e['cores']['inicio'] ),
		esc_attr( $e['cores']['fim'] )
	);
};

/* ---------------------------------------------------------------- Lista */

if ( ! $evento ) :
	$eventos = array_values( array_filter( jelly_ar_eventos_todos(), function ( $e ) {
		return $e['marcacoes'];
	} ) );

	// Os que ainda vêm primeiro, do mais próximo; os que já passaram a seguir.
	$hoje = current_time( 'Ymd' );
	usort( $eventos, function ( $a, $b ) use ( $hoje ) {
		$pa = $a['fim'] < $hoje;
		$pb = $b['fim'] < $hoje;

		return $pa !== $pb ? ( $pa ? 1 : -1 ) : ( $pa ? strcmp( $b['inicio'], $a['inicio'] ) : strcmp( $a['inicio'], $b['inicio'] ) );
	} );
	?>
	<div class="jar-cabeca">
		<div>
			<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Mesas e horários', 'jelly-area-reservada' ); ?></h1>
			<p class="jar-cabeca__intro"><?php esc_html_e( 'As mesas e os horários de marcação dos eventos que aceitam marcações.', 'jelly-area-reservada' ); ?></p>
		</div>
	</div>

	<?php if ( $id ) : ?>
		<div class="jar-aviso jar-aviso--suspenso" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><p><?php esc_html_e( 'Esse evento não existe ou está no lixo.', 'jelly-area-reservada' ); ?></p></div>
	<?php endif; ?>

	<section class="jar-cartao jar-cartao--tabela">
		<table class="jar-tabela">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Evento', 'jelly-area-reservada' ); ?></th>
					<th class="jar-col--data"><?php esc_html_e( 'Datas', 'jelly-area-reservada' ); ?></th>
					<th class="jar-tabela__num"><?php esc_html_e( 'Mesas', 'jelly-area-reservada' ); ?></th>
					<th class="jar-tabela__num jar-col--local"><?php esc_html_e( 'Dias', 'jelly-area-reservada' ); ?></th>
					<th class="jar-tabela__num"><?php esc_html_e( 'Ocupação', 'jelly-area-reservada' ); ?></th>
					<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $eventos ) : ?>
					<tr><td colspan="6" class="jar-vazio"><?php esc_html_e( 'Nenhum evento aceita marcações. As marcações ligam-se nos dados de cada evento, em Eventos.', 'jelly-area-reservada' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $eventos as $e ) : ?>
					<?php
					$r     = jelly_ar_mesas_resumo( $e['id'] );
					$abrir = jelly_ar_mesas_url( $e['id'] );
					$usado = $r['confirmadas'] + $r['pendentes'];
					?>
					<tr>
						<td>
							<a class="jar-ficheiro" href="<?php echo esc_url( $abrir ); ?>">
								<?php $capa( $e ); ?>
								<span>
									<strong><?php echo esc_html( $e['titulo'] ); ?></strong>
									<small><?php echo esc_html( $e['local'] ? $e['local'] : '—' ); ?></small>
								</span>
							</a>
						</td>
						<td class="jar-col--data"><?php echo esc_html( jelly_ar_intervalo_datas( $e['inicio'], $e['fim'] ) ); ?></td>
						<td class="jar-tabela__num"><?php echo (int) $r['mesas']; ?></td>
						<td class="jar-tabela__num jar-col--local"><?php echo (int) $r['dias']; ?></td>
						<td class="jar-tabela__num">
							<?php
							if ( $r['blocos'] ) {
								/* translators: 1: blocos ocupados, 2: blocos no total */
								printf( esc_html__( '%1$d de %2$d', 'jelly-area-reservada' ), (int) $usado, (int) $r['blocos'] );
							} else {
								echo '—';
							}
							?>
						</td>
						<td class="jar-tabela__fim">
							<?php /* translators: %s: evento */ ?>
							<a class="jar-acao" href="<?php echo esc_url( $abrir ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Configurar as mesas de %s', 'jelly-area-reservada' ), $e['titulo'] ) ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Um evento */

$mesas     = jelly_ar_mesas( $evento['id'] );
$horarios  = jelly_ar_horarios( $evento['id'] );
$dias      = jelly_ar_evento_dias( $evento );
$ocupados  = jelly_ar_marcacoes_grelha( $evento['id'] );
$resumo    = jelly_ar_mesas_resumo( $evento['id'] );
$separador = isset( $_GET['separador'] ) ? sanitize_key( wp_unslash( $_GET['separador'] ) ) : 'mesas'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$separador = in_array( $separador, [ 'mesas', 'horarios', 'grelha' ], true ) ? $separador : 'mesas';
$intervalo = $horarios ? current( $horarios )['intervalo'] : JELLY_AR_INTERVALO_OMISSAO;

// Horários de dias que já não são do evento (as datas mudaram depois).
$fora = array_diff( array_keys( $horarios ), $dias );
?>
<div class="jar-cabeca">
	<div>
		<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'mesas' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Mesas e horários', 'jelly-area-reservada' ); ?></a>
		<h1 class="jar-cabeca__titulo"><?php echo esc_html( $evento['titulo'] ); ?></h1>
		<p class="jar-cabeca__intro"><?php echo esc_html( jelly_ar_intervalo_datas( $evento['inicio'], $evento['fim'] ) . ( $evento['local'] ? ' · ' . $evento['local'] : '' ) ); ?></p>
	</div>
	<div class="jar-cabeca__acoes">
		<a class="jar-btn jar-btn--contorno" href="<?php echo esc_url( jelly_ar_admin_url( 'eventos', [ 'evento' => $evento['id'] ] ) ); ?>"><i class="fa-solid fa-earth-europe" aria-hidden="true"></i> <?php esc_html_e( 'Ver o evento', 'jelly-area-reservada' ); ?></a>
	</div>
</div>

<?php $mostrar_aviso(); ?>

<?php if ( ! $evento['marcacoes'] ) : ?>
	<div class="jar-aviso jar-aviso--pendente">
		<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
		<p>
			<?php esc_html_e( 'Este evento não aceita marcações: as mesas e os horários ficam guardados, mas os associados não os veem. As marcações ligam-se nos dados do evento.', 'jelly-area-reservada' ); ?>
		</p>
	</div>
<?php endif; ?>

<?php if ( $fora ) : ?>
	<div class="jar-aviso jar-aviso--pendente">
		<i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i>
		<p><?php esc_html_e( 'Há horários guardados para dias que já não são do evento (as datas mudaram). Não aparecem aos associados; ao guardar os horários, saem.', 'jelly-area-reservada' ); ?></p>
	</div>
<?php endif; ?>

<div class="jar-numeros">
	<?php
	foreach (
		[
			[ __( 'Mesas', 'jelly-area-reservada' ), $resumo['mesas'], 'fa-table-cells-large', 'azul' ],
			[ __( 'Dias com horário', 'jelly-area-reservada' ), $resumo['dias'], 'fa-calendar-days', 'roxo' ],
			[ __( 'Blocos para marcar', 'jelly-area-reservada' ), $resumo['blocos'], 'fa-clock', 'turquesa' ],
			[ __( 'Ocupados', 'jelly-area-reservada' ), $resumo['confirmadas'] + $resumo['pendentes'], 'fa-user-check', 'magenta' ],
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

<nav class="jar-separadores" aria-label="<?php esc_attr_e( 'Mesas e horários', 'jelly-area-reservada' ); ?>">
	<?php
	foreach (
		[
			'mesas'    => [ __( 'Mesas', 'jelly-area-reservada' ), count( $mesas ) ],
			'horarios' => [ __( 'Horários', 'jelly-area-reservada' ), count( $horarios ) ],
			'grelha'   => [ __( 'Grelha', 'jelly-area-reservada' ), null ],
		] as $s => $rotulo
	) :
		?>
		<a class="jar-separador<?php echo $s === $separador ? ' is-atual' : ''; ?>" href="<?php echo esc_url( jelly_ar_mesas_url( $evento['id'], [ 'separador' => $s ] ) ); ?>"<?php echo $s === $separador ? ' aria-current="page"' : ''; ?>>
			<?php echo esc_html( $rotulo[0] ); ?>
			<?php if ( null !== $rotulo[1] ) : ?>
				<b><?php echo (int) $rotulo[1]; ?></b>
			<?php endif; ?>
		</a>
	<?php endforeach; ?>
</nav>

<?php if ( 'mesas' === $separador ) : ?>
	<?php /* ---------------------------------------------------------- Mesas */ ?>
	<div class="jar-grelha jar-grelha--2-1">
		<section class="jar-cartao jar-cartao--tabela">
			<table class="jar-tabela">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Mesa', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__num"><?php esc_html_e( 'Lugares', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__num"><?php esc_html_e( 'Marcações', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $mesas ) : ?>
						<tr><td colspan="4" class="jar-vazio"><?php esc_html_e( 'Este evento ainda não tem mesas.', 'jelly-area-reservada' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $mesas as $m ) : ?>
						<tr data-jar-editavel>
							<td>
								<span class="jar-mesa-nome" data-jar-leitura>
									<strong><?php echo esc_html( $m['nome'] ); ?></strong>
									<?php if ( $m['localizacao'] ) : ?>
										<small><?php echo esc_html( $m['localizacao'] ); ?></small>
									<?php endif; ?>
								</span>

								<?php // O nome, a localização e os lugares, na própria linha; vai para jelly_ar_mesa_editar(). ?>
								<form class="jar-linha-edicao jar-linha-edicao--mesa" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-jar-edicao data-jar-gravar hidden>
									<input type="hidden" name="action" value="jelly_ar_mesa_editar">
									<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
									<input type="hidden" name="mesa" value="<?php echo (int) $m['id']; ?>">
									<?php wp_nonce_field( 'jelly_ar_mesa_editar_' . $evento['id'] ); ?>
									<label class="jar-campo">
										<span class="screen-reader-text"><?php esc_html_e( 'Nome da mesa', 'jelly-area-reservada' ); ?></span>
										<input type="text" name="nome" value="<?php echo esc_attr( $m['nome'] ); ?>" required maxlength="80">
									</label>
									<label class="jar-campo">
										<span class="screen-reader-text"><?php esc_html_e( 'Localização', 'jelly-area-reservada' ); ?></span>
										<input type="text" name="localizacao" value="<?php echo esc_attr( $m['localizacao'] ); ?>" maxlength="150" placeholder="<?php esc_attr_e( 'Localização', 'jelly-area-reservada' ); ?>">
									</label>
									<label class="jar-campo jar-campo--lugares">
										<span class="screen-reader-text"><?php esc_html_e( 'Lugares', 'jelly-area-reservada' ); ?></span>
										<input type="number" name="lugares" value="<?php echo (int) $m['lugares']; ?>" min="1" max="50" required>
									</label>
									<span class="jar-linha-edicao__botoes">
										<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
										<button type="submit" class="jar-btn jar-btn--pequeno"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
									</span>
								</form>
							</td>
							<td class="jar-tabela__num"><?php echo (int) $m['lugares']; ?></td>
							<td class="jar-tabela__num"><?php echo (int) $m['marcacoes']; ?></td>
							<td class="jar-tabela__fim">
								<span class="jar-acoes">
									<?php /* translators: %s: mesa */ ?>
									<button type="button" class="jar-acao" data-jar-editar aria-label="<?php echo esc_attr( sprintf( __( 'Editar %s', 'jelly-area-reservada' ), $m['nome'] ) ); ?>" title="<?php esc_attr_e( 'Editar', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>

									<?php if ( $m['marcacoes'] ) : ?>
										<?php // Com marcações não se apaga: o botão diz porquê. ?>
										<button type="button" class="jar-acao" disabled title="<?php esc_attr_e( 'Tem marcações: não se pode apagar', 'jelly-area-reservada' ); ?>"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
									<?php else : ?>
										<button
											type="button"
											class="jar-acao jar-acao--nao"
											data-jar-confirmar
											data-jar-form="jar-apagar-mesa-<?php echo (int) $m['id']; ?>"
											<?php /* translators: %s: mesa */ ?>
											data-titulo="<?php echo esc_attr( sprintf( __( 'Apagar a mesa "%s"?', 'jelly-area-reservada' ), $m['nome'] ) ); ?>"
											data-texto="<?php esc_attr_e( 'Sai da grelha deste evento. Não tem marcações, por isso ninguém a perde.', 'jelly-area-reservada' ); ?>"
											data-sim="<?php esc_attr_e( 'Apagar mesa', 'jelly-area-reservada' ); ?>"
											data-resultado=""
											<?php /* translators: %s: mesa */ ?>
											aria-label="<?php echo esc_attr( sprintf( __( 'Apagar %s', 'jelly-area-reservada' ), $m['nome'] ) ); ?>"
											title="<?php esc_attr_e( 'Apagar', 'jelly-area-reservada' ); ?>"
										><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
										<form id="jar-apagar-mesa-<?php echo (int) $m['id']; ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
											<input type="hidden" name="action" value="jelly_ar_mesa_apagar">
											<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
											<input type="hidden" name="mesa" value="<?php echo (int) $m['id']; ?>">
											<?php wp_nonce_field( 'jelly_ar_mesa_apagar_' . $evento['id'] ); ?>
										</form>
									<?php endif; ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="jar-cartao">
			<header class="jar-cartao__cabeca"><h2><?php esc_html_e( 'Nova mesa', 'jelly-area-reservada' ); ?></h2></header>
			<?php // Vai para jelly_ar_mesa_criar(), em inc/mesas.php. ?>
			<form class="jar-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="jelly_ar_mesa_criar">
				<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
				<?php wp_nonce_field( 'jelly_ar_mesa_criar_' . $evento['id'] ); ?>
				<label class="jar-campo jar-campo--largo">
					<span><?php esc_html_e( 'Nome', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
					<?php /* translators: %d: número da mesa */ ?>
					<input type="text" name="nome" required maxlength="80" value="<?php echo esc_attr( sprintf( __( 'Mesa %d', 'jelly-area-reservada' ), count( $mesas ) + 1 ) ); ?>">
				</label>
				<label class="jar-campo jar-campo--largo">
					<span><?php esc_html_e( 'Localização', 'jelly-area-reservada' ); ?></span>
					<input type="text" name="localizacao" maxlength="150" placeholder="<?php esc_attr_e( 'Ex.: Stand APIT, junto à entrada', 'jelly-area-reservada' ); ?>">
				</label>
				<label class="jar-campo">
					<span><?php esc_html_e( 'Lugares', 'jelly-area-reservada' ); ?></span>
					<input type="number" name="lugares" value="4" min="1" max="50" required>
				</label>
				<footer class="jar-cartao__pe jar-campo--largo">
					<button type="submit" class="jar-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Criar mesa', 'jelly-area-reservada' ); ?></button>
				</footer>
			</form>
		</section>
	</div>

<?php elseif ( 'horarios' === $separador ) : ?>
	<?php /* ---------------------------------------------------------- Horários */ ?>
	<section class="jar-cartao">
		<header class="jar-cartao__cabeca">
			<div>
				<h2><?php esc_html_e( 'Horários de marcação', 'jelly-area-reservada' ); ?></h2>
				<span class="jar-cartao__meta"><?php esc_html_e( 'Os dias do evento em que os associados podem marcar, e entre que horas', 'jelly-area-reservada' ); ?></span>
			</div>
		</header>

		<?php // Vai para jelly_ar_horarios_guardar(), em inc/mesas.php. ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="jar-horarios">
			<input type="hidden" name="action" value="jelly_ar_horarios_guardar">
			<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
			<?php wp_nonce_field( 'jelly_ar_horarios_guardar_' . $evento['id'] ); ?>

			<label class="jar-campo jar-horarios__intervalo">
				<span><?php esc_html_e( 'Intervalo entre marcações', 'jelly-area-reservada' ); ?></span>
				<select name="intervalo">
					<?php foreach ( JELLY_AR_INTERVALOS as $i ) : ?>
						<?php /* translators: %d: minutos */ ?>
						<option value="<?php echo (int) $i; ?>" <?php selected( $i, $intervalo ); ?>><?php echo esc_html( sprintf( __( '%d minutos', 'jelly-area-reservada' ), $i ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<small class="jar-campo__ajuda"><?php esc_html_e( 'A duração de cada marcação. É a mesma para todos os dias do evento.', 'jelly-area-reservada' ); ?></small>
			</label>

			<table class="jar-tabela jar-horarios__tabela">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Dia', 'jelly-area-reservada' ); ?></th>
						<th><?php esc_html_e( 'Início', 'jelly-area-reservada' ); ?></th>
						<th><?php esc_html_e( 'Fim', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__num"><?php esc_html_e( 'Blocos', 'jelly-area-reservada' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $dias as $dia ) : ?>
						<?php
						$h      = $horarios[ $dia ] ?? null;
						$marcas = 0;
						foreach ( $ocupados as $por_dia ) {
							$marcas += count( $por_dia[ $dia ] ?? [] );
						}
						?>
						<tr class="<?php echo $dia === $dia_erro ? 'is-erro' : ''; ?>">
							<td>
								<label class="jar-caixa">
									<input type="checkbox" name="dia[<?php echo esc_attr( $dia ); ?>]" value="1" <?php checked( null !== $h ); ?>>
									<span>
										<?php echo esc_html( $dia_curto( $dia ) ); ?>
										<?php if ( $marcas ) : ?>
											<?php /* translators: %d: número de marcações */ ?>
											<small class="jar-evento-docs__meta"><?php echo esc_html( sprintf( _n( '%d marcação', '%d marcações', $marcas, 'jelly-area-reservada' ), $marcas ) ); ?></small>
										<?php endif; ?>
									</span>
								</label>
							</td>
							<td><input type="time" name="inicio[<?php echo esc_attr( $dia ); ?>]" value="<?php echo esc_attr( $h['inicio'] ?? '10:00' ); ?>" step="300"></td>
							<td><input type="time" name="fim[<?php echo esc_attr( $dia ); ?>]" value="<?php echo esc_attr( $h['fim'] ?? '18:00' ); ?>" step="300"></td>
							<td class="jar-tabela__num"><?php echo $h ? (int) count( jelly_ar_blocos( $h ) ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<footer class="jar-cartao__pe">
				<button type="submit" class="jar-btn"><?php esc_html_e( 'Guardar horários', 'jelly-area-reservada' ); ?></button>
			</footer>
		</form>
	</section>

<?php else : ?>
	<?php /* ---------------------------------------------------------- Grelha */ ?>
	<?php if ( ! $mesas || ! $horarios ) : ?>
		<section class="jar-cartao">
			<p class="jar-vazio">
				<?php
				echo esc_html(
					! $mesas
						? __( 'A grelha aparece quando o evento tiver mesas e horários. Faltam as mesas.', 'jelly-area-reservada' )
						: __( 'A grelha aparece quando o evento tiver mesas e horários. Faltam os horários.', 'jelly-area-reservada' )
				);
				?>
			</p>
		</section>
	<?php else : ?>
		<ul class="jar-legenda" aria-label="<?php esc_attr_e( 'Legenda', 'jelly-area-reservada' ); ?>">
			<li><span class="jar-bloco jar-bloco--livre" aria-hidden="true"></span> <?php esc_html_e( 'Disponível', 'jelly-area-reservada' ); ?></li>
			<li><span class="jar-bloco jar-bloco--pendente" aria-hidden="true"></span> <?php esc_html_e( 'Pendente', 'jelly-area-reservada' ); ?></li>
			<li><span class="jar-bloco jar-bloco--aprovada" aria-hidden="true"></span> <?php esc_html_e( 'Confirmado', 'jelly-area-reservada' ); ?></li>
		</ul>

		<?php foreach ( $dias as $dia ) : ?>
			<?php
			if ( ! isset( $horarios[ $dia ] ) ) {
				continue;
			}
			$blocos = jelly_ar_blocos( $horarios[ $dia ] );
			?>
			<section class="jar-cartao jar-cartao--tabela">
				<header class="jar-cartao__cabeca">
					<div>
						<h2><?php echo esc_html( $dia_curto( $dia ) ); ?></h2>
						<?php /* translators: 1: início, 2: fim, 3: minutos */ ?>
						<span class="jar-cartao__meta"><?php echo esc_html( sprintf( __( '%1$s – %2$s · blocos de %3$d minutos', 'jelly-area-reservada' ), $horarios[ $dia ]['inicio'], $horarios[ $dia ]['fim'], $horarios[ $dia ]['intervalo'] ) ); ?></span>
					</div>
				</header>
				<div class="jar-grelha-horas">
					<table>
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Mesa', 'jelly-area-reservada' ); ?></th>
								<?php foreach ( $blocos as $b ) : ?>
									<th scope="col"><?php echo esc_html( $b ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $mesas as $m ) : ?>
								<tr>
									<th scope="row"><?php echo esc_html( $m['nome'] ); ?></th>
									<?php foreach ( $blocos as $b ) : ?>
										<?php
										$o      = $ocupados[ $m['id'] ][ $dia ][ $b ] ?? null;
										$estado = $o ? ( 'aprovada' === $o['estado'] ? 'aprovada' : 'pendente' ) : 'livre';
										$titulo = $o
											? trim( $o['quem'] . ( $o['empresa'] ? ' · ' . $o['empresa'] : '' ) ) . ' — ' . ( 'aprovada' === $estado ? __( 'Confirmado', 'jelly-area-reservada' ) : __( 'Pendente', 'jelly-area-reservada' ) )
											: __( 'Disponível', 'jelly-area-reservada' );
										?>
										<td>
											<span class="jar-bloco jar-bloco--<?php echo esc_attr( $estado ); ?>" title="<?php echo esc_attr( $m['nome'] . ', ' . $b . ': ' . $titulo ); ?>">
												<span class="screen-reader-text"><?php echo esc_html( $titulo ); ?></span>
											</span>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>
<?php endif; ?>
