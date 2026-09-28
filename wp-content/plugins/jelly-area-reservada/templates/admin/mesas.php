<?php
/**
 * Mesas e horários: a lista dos eventos que aceitam marcações, e a
 * configuração de um deles (`evento=<id>`), em três separadores:
 *
 * - Mesas     as mesas do evento: nome, localização e lugares
 * - Horários  os dias do evento, cada um com a hora de início e a de fim, e o
 *             intervalo entre marcações (30 minutos por omissão)
 * - Grelha    as mesas por hora, dia a dia, com o estado de cada bloco; um
 *             clique num bloco abre as marcações dele, para marcar, remover
 *             ou mudar associados
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

	return $d ? ucfirst( jelly_ar_data( 'D, j M', $d->getTimestamp() ) ) : $ymd;
};

// Quantas marcações fez o pedido, e quantos e-mails não saíram.
$n         = isset( $_GET['n'] ) ? absint( $_GET['n'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sem_email = isset( $_GET['sem-email'] ) ? absint( $_GET['sem-email'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$avisos = [
	'mesa-criada'       => __( 'Mesa criada.', 'jelly-area-reservada' ),
	'mesa-atualizada'   => __( 'Mesa guardada.', 'jelly-area-reservada' ),
	'mesa-apagada'      => __( 'Mesa apagada.', 'jelly-area-reservada' ),
	'horarios'          => __( 'Horários guardados. A grelha já usa os blocos novos.', 'jelly-area-reservada' ),
	/* translators: %d: número de associados */
	'marcacao-criada'   => sprintf( _n( '%d associado marcado, já confirmado. Foi enviado um e-mail com os dados da marcação.', '%d associados marcados, já confirmados. Foi enviado a cada um um e-mail com os dados da marcação.', $n, 'jelly-area-reservada' ), $n ),
	/* translators: %d: número de marcações */
	'marcacao-removida' => sprintf( _n( '%d marcação removida. O associado foi avisado por e-mail.', '%d marcações removidas. Cada associado foi avisado por e-mail.', $n, 'jelly-area-reservada' ), $n ),
	/* translators: %d: número de marcações */
	'marcacao-mudada'   => sprintf( _n( '%d marcação mudada de bloco. O associado foi avisado por e-mail.', '%d marcações mudadas de bloco. Cada associado foi avisado por e-mail.', $n, 'jelly-area-reservada' ), $n ),
];
$erros  = [
	'mesa-nome'         => __( 'A mesa precisa de um nome.', 'jelly-area-reservada' ),
	'mesa-falhou'       => __( 'A mesa não foi encontrada neste evento.', 'jelly-area-reservada' ),
	'mesa-em-uso'       => __( 'Essa mesa tem marcações e não se pode apagar.', 'jelly-area-reservada' ),
	/* translators: %d: lugares ocupados */
	'mesa-lugares'      => sprintf( __( 'A mesa tem um bloco com %d lugares ocupados: os lugares não podem ficar abaixo disso. Nada foi gravado.', 'jelly-area-reservada' ), $n ),
	'marcacao-bloco'    => __( 'Esse horário já não existe na grelha: a mesa ou o horário mudaram. Nada foi gravado.', 'jelly-area-reservada' ),
	'marcacao-ninguem'  => __( 'Nenhum associado estava escolhido, por isso nada foi gravado.', 'jelly-area-reservada' ),
	'marcacao-mesmo'    => __( 'O horário escolhido é aquele onde a marcação já está. Nada foi mudado.', 'jelly-area-reservada' ),
	'marcacao-cheia'    => JELLY_AR_LUGARES
		? __( 'O horário não tem lugares livres para todos os associados escolhidos. Nada foi gravado.', 'jelly-area-reservada' )
		: __( 'Esse horário já tem uma marcação. Nada foi gravado.', 'jelly-area-reservada' ),
	'marcacao-hora'     => __( 'Um dos associados escolhidos já tem uma marcação a essa hora, noutra mesa. Nada foi gravado.', 'jelly-area-reservada' ),
	/* translators: %s: dia */
	'horario-horas'     => sprintf( __( 'Em %s, a hora de fim tem de ser depois da de início, com espaço para pelo menos um bloco.', 'jelly-area-reservada' ), $dia_erro ? $dia_curto( $dia_erro ) : '—' ),
	/* translators: %s: dia */
	'horario-passo'     => sprintf( __( 'Em %s, as horas de início e de fim têm de acompanhar o intervalo escolhido: com 30 minutos, por exemplo, 10:00 ou 10:30.', 'jelly-area-reservada' ), $dia_erro ? $dia_curto( $dia_erro ) : '—' ),
	'horario-nenhum'    => __( 'Nenhum dia estava escolhido, por isso nada foi gravado. Para os associados poderem marcar, é necessário escolher pelo menos um dia do evento.', 'jelly-area-reservada' ),
	/* translators: %s: dia */
	'horario-marcacoes' => sprintf( __( 'Em %s há marcações que ficariam fora dos blocos novos. Nada foi gravado: o horário desse dia tem de continuar a incluí-las.', 'jelly-area-reservada' ), $dia_erro ? $dia_curto( $dia_erro ) : '—' ),
];

$mostrar_aviso = function () use ( $aviso, $erro, $avisos, $erros, $sem_email ) {
	if ( isset( $avisos[ $aviso ] ) ) {
		printf( '<div class="jar-aviso jar-aviso--sucesso" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><p>%s</p></div>', esc_html( $avisos[ $aviso ] ) );
	}
	if ( isset( $avisos[ $aviso ] ) && $sem_email ) {
		/* translators: %d: número de e-mails */
		printf( '<div class="jar-aviso jar-aviso--pendente" role="alert"><i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i><p>%s</p></div>', esc_html( sprintf( _n( '%d e-mail não foi enviado: a alteração está gravada, mas o associado não foi avisado. Convém confirmar a configuração do SMTP.', '%d e-mails não foram enviados: as alterações estão gravadas, mas esses associados não foram avisados. Convém confirmar a configuração do SMTP.', $sem_email, 'jelly-area-reservada' ), $sem_email ) ) );
	}
	if ( isset( $erros[ $erro ] ) ) {
		printf( '<div class="jar-aviso jar-aviso--suspenso" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><p>%s</p></div>', esc_html( $erros[ $erro ] ) );
	}
};

/*
 * Uma hora, escolhida de uma lista sempre em 24 horas (11:00, 13:30…): o
 * <input type="time"> do browser segue o idioma do sistema e mostra AM/PM num
 * computador em inglês. A lista vai ao passo do intervalo
 * (jelly_ar_passo_horas()): com 30 minutos, 10:00, 10:30… Uma hora guardada
 * que não caia na lista entra também, para se ver o que está gravado.
 */
$select_hora = function ( $nome, $valor, $rotulo, $passo ) {
	$horas = [];
	for ( $m = 0; $m < 24 * 60; $m += $passo ) {
		$horas[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
	}
	if ( ! in_array( $valor, $horas, true ) ) {
		$horas[] = $valor;
		sort( $horas );
	}
	?>
	<?php // As opções refazem-se no browser quando o intervalo muda (assets/js/admin.js, data-jar-hora). ?>
	<select name="<?php echo esc_attr( $nome ); ?>" class="jar-hora" aria-label="<?php echo esc_attr( $rotulo ); ?>" data-jar-hora>
		<?php foreach ( $horas as $h ) : ?>
			<option value="<?php echo esc_attr( $h ); ?>" <?php selected( $h, $valor ); ?>><?php echo esc_html( $h ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
};

// A capa: o gradiente da categoria, como nos Eventos.
$capa = function ( $e ) {
	printf(
		'<span class="jar-evento-capa" style="--jar-cat-inicio: %1$s; --jar-cat-fim: %2$s;" aria-hidden="true"></span>',
		esc_attr( $e['cores']['inicio'] ),
		esc_attr( $e['cores']['fim'] )
	);
};

/*
 * O badge da disponibilidade (jelly_ar_disponibilidade_estado()): se os
 * associados ainda têm onde marcar. Só na lista: na página de cada evento, os
 * cartões de cima já o dizem.
 */
$badge = function ( $e ) {
	$d      = jelly_ar_disponibilidade_estado( $e );
	$textos = [
		'disponivel' => JELLY_AR_LUGARES
			/* translators: %d: lugares livres */
			? sprintf( _n( 'Disponível · %d lugar livre', 'Disponível · %d lugares livres', $d['livres'], 'jelly-area-reservada' ), $d['livres'] )
			/* translators: %d: horários livres */
			: sprintf( _n( 'Disponível · %d horário livre', 'Disponível · %d horários livres', $d['livres'], 'jelly-area-reservada' ), $d['livres'] ),
		'completo'   => __( 'Completo', 'jelly-area-reservada' ),
		'terminado'  => __( 'Terminado', 'jelly-area-reservada' ),
		'sem-grelha' => __( 'Sem grelha', 'jelly-area-reservada' ),
	];
	$titulos = [
		'disponivel' => __( 'Há horários por marcar, ainda por vir.', 'jelly-area-reservada' ),
		'completo'   => __( 'Todos os horários por vir estão marcados: os associados já não têm onde marcar.', 'jelly-area-reservada' ),
		'terminado'  => __( 'O evento já terminou.', 'jelly-area-reservada' ),
		'sem-grelha' => __( 'Faltam as mesas ou os horários: ainda não há onde marcar.', 'jelly-area-reservada' ),
	];

	printf(
		'<span class="jar-estado jar-estado--%1$s" title="%2$s">%3$s</span>',
		esc_attr( $d['estado'] ),
		esc_attr( $titulos[ $d['estado'] ] ),
		esc_html( $textos[ $d['estado'] ] )
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

	<section class="jar-cartao jar-cartao--tabela jar-mesas-lista">
		<table class="jar-tabela">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Evento', 'jelly-area-reservada' ); ?></th>
					<th class="jar-col--data"><?php esc_html_e( 'Datas', 'jelly-area-reservada' ); ?></th>
					<th class="jar-tabela__num jar-col--n-mesas"><?php esc_html_e( 'Mesas', 'jelly-area-reservada' ); ?></th>
					<th class="jar-tabela__num jar-col--local"><?php esc_html_e( 'Dias', 'jelly-area-reservada' ); ?></th>
					<th class="jar-tabela__num jar-col--ocupacao"><?php esc_html_e( 'Ocupação', 'jelly-area-reservada' ); ?></th>
					<th><?php esc_html_e( 'Disponibilidade', 'jelly-area-reservada' ); ?></th>
					<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $eventos ) : ?>
					<tr><td colspan="7" class="jar-vazio"><?php esc_html_e( 'Nenhum evento aceita marcações. As marcações ligam-se nos dados de cada evento, em Eventos.', 'jelly-area-reservada' ); ?></td></tr>
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
						<td class="jar-tabela__num jar-col--n-mesas"><?php echo (int) $r['mesas']; ?></td>
						<td class="jar-tabela__num jar-col--local"><?php echo (int) $r['dias']; ?></td>
						<td class="jar-tabela__num jar-col--ocupacao">
							<?php
							if ( $r['lugares'] ) {
								// Sem lugares por mesa, um lugar é um horário: "3 de 40" são marcações de horários.
								/* translators: 1: marcações, 2: horários (ou lugares) no total */
								printf( esc_html__( '%1$d de %2$d', 'jelly-area-reservada' ), (int) $usado, (int) $r['lugares'] );
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php $badge( $e ); ?></td>
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
			[ JELLY_AR_LUGARES ? __( 'Lugares para marcar', 'jelly-area-reservada' ) : __( 'Horários para marcar', 'jelly-area-reservada' ), $resumo['lugares'], 'fa-clock', 'turquesa' ],
			[ __( 'Marcações', 'jelly-area-reservada' ), $resumo['confirmadas'] + $resumo['pendentes'], 'fa-user-check', 'magenta' ],
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
						<?php if ( JELLY_AR_LUGARES ) : ?>
							<th class="jar-tabela__num jar-tabela__centro"><?php esc_html_e( 'Lugares', 'jelly-area-reservada' ); ?></th>
						<?php endif; ?>
						<th class="jar-tabela__num jar-tabela__centro"><?php esc_html_e( 'Marcações', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $mesas ) : ?>
						<tr><td colspan="<?php echo JELLY_AR_LUGARES ? 4 : 3; ?>" class="jar-vazio"><?php esc_html_e( 'Este evento ainda não tem mesas.', 'jelly-area-reservada' ); ?></td></tr>
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
									<?php if ( JELLY_AR_LUGARES ) : ?>
										<label class="jar-campo jar-campo--lugares">
											<span class="screen-reader-text"><?php esc_html_e( 'Lugares', 'jelly-area-reservada' ); ?></span>
											<input type="number" name="lugares" value="<?php echo (int) $m['lugares']; ?>" min="1" max="50" required>
										</label>
									<?php endif; ?>
									<span class="jar-linha-edicao__botoes">
										<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
										<button type="submit" class="jar-btn jar-btn--pequeno"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
									</span>
								</form>
							</td>
							<?php if ( JELLY_AR_LUGARES ) : ?>
								<td class="jar-tabela__num jar-tabela__centro"><?php echo (int) $m['lugares']; ?></td>
							<?php endif; ?>
							<td class="jar-tabela__num jar-tabela__centro"><?php echo (int) $m['marcacoes']; ?></td>
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
				<?php if ( JELLY_AR_LUGARES ) : ?>
					<label class="jar-campo">
						<span><?php esc_html_e( 'Lugares', 'jelly-area-reservada' ); ?></span>
						<input type="number" name="lugares" value="4" min="1" max="50" required>
					</label>
				<?php endif; ?>
				<footer class="jar-cartao__pe jar-campo--largo">
					<button type="submit" class="jar-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Criar mesa', 'jelly-area-reservada' ); ?></button>
				</footer>
			</form>
		</section>
	</div>

<?php elseif ( 'horarios' === $separador ) : ?>
	<?php /* ---------------------------------------------------------- Horários */ ?>
	<?php
	/*
	 * Depois de gravados, os horários mostram-se para ler, com o botão Editar,
	 * como os outros cartões do back-office. Na primeira vez (ainda sem
	 * horários) o formulário aparece logo; e um erro ao gravar reabre-o, para
	 * se corrigir.
	 */
	$ha_horarios = (bool) $horarios;
	?>
	<section class="jar-cartao"<?php echo $ha_horarios ? ' data-jar-editavel' : ''; ?><?php echo $ha_horarios && isset( $erros[ $erro ] ) ? ' data-jar-abrir' : ''; ?>>
		<header class="jar-cartao__cabeca jar-cartao__cabeca--acao">
			<div>
				<h2><?php esc_html_e( 'Horários de marcação', 'jelly-area-reservada' ); ?></h2>
				<span class="jar-cartao__meta"><?php esc_html_e( 'Os dias do evento em que os associados podem marcar, e entre que horas', 'jelly-area-reservada' ); ?></span>
			</div>
			<?php if ( $ha_horarios ) : ?>
				<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-editar>
					<i class="fa-solid fa-pen" aria-hidden="true"></i> <?php esc_html_e( 'Editar', 'jelly-area-reservada' ); ?>
				</button>
			<?php endif; ?>
		</header>

		<?php if ( $ha_horarios ) : ?>
			<?php // A leitura: os dias do evento, com as horas dos que têm horário. ?>
			<div data-jar-leitura>
				<?php /* translators: %d: minutos */ ?>
				<p class="jar-horarios__resumo"><?php echo esc_html( sprintf( __( 'Marcações de %d minutos.', 'jelly-area-reservada' ), $intervalo ) ); ?></p>
				<table class="jar-tabela jar-horarios__tabela">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Dia', 'jelly-area-reservada' ); ?></th>
							<th><?php esc_html_e( 'Início', 'jelly-area-reservada' ); ?></th>
							<th><?php esc_html_e( 'Fim', 'jelly-area-reservada' ); ?></th>
							<th class="jar-tabela__num jar-tabela__centro"><?php esc_html_e( 'Blocos', 'jelly-area-reservada' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $dias as $dia ) : ?>
							<?php $h = $horarios[ $dia ] ?? null; ?>
							<tr>
								<td><strong><?php echo esc_html( $dia_curto( $dia ) ); ?></strong></td>
								<?php if ( $h ) : ?>
									<td><?php echo esc_html( $h['inicio'] ); ?></td>
									<td><?php echo esc_html( $h['fim'] ); ?></td>
									<td class="jar-tabela__num jar-tabela__centro"><?php echo (int) count( jelly_ar_blocos( $h ) ); ?></td>
								<?php else : ?>
									<td colspan="3" class="jar-horarios__sem"><?php esc_html_e( 'Sem horário: não se marca neste dia', 'jelly-area-reservada' ); ?></td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<?php // Vai para jelly_ar_horarios_guardar(), em inc/mesas.php. ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="jar-horarios" id="jar-horarios" data-jar-horarios<?php echo $ha_horarios ? ' data-jar-edicao data-jar-gravar hidden' : ''; ?>>
			<input type="hidden" name="action" value="jelly_ar_horarios_guardar">
			<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
			<?php wp_nonce_field( 'jelly_ar_horarios_guardar_' . $evento['id'] ); ?>

			<?php if ( 1 === count( JELLY_AR_INTERVALOS ) ) : ?>
				<?php // Com um só intervalo (inc/mesas-dados.php), mostra-se em vez de se escolher. ?>
				<input type="hidden" name="intervalo" value="<?php echo (int) JELLY_AR_INTERVALOS[0]; ?>">
				<?php /* translators: %d: minutos */ ?>
				<p class="jar-horarios__resumo"><?php echo esc_html( sprintf( __( 'Marcações de %d minutos.', 'jelly-area-reservada' ), JELLY_AR_INTERVALOS[0] ) ); ?></p>
			<?php else : ?>
				<label class="jar-campo jar-horarios__intervalo">
					<span><?php esc_html_e( 'Intervalo entre marcações', 'jelly-area-reservada' ); ?></span>
					<select name="intervalo" data-jar-intervalo>
						<?php foreach ( JELLY_AR_INTERVALOS as $i ) : ?>
							<?php /* translators: %d: minutos */ ?>
							<option value="<?php echo (int) $i; ?>" <?php selected( $i, $intervalo ); ?>><?php echo esc_html( sprintf( __( '%d minutos', 'jelly-area-reservada' ), $i ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<small class="jar-campo__ajuda"><?php esc_html_e( 'A duração de cada marcação. É a mesma para todos os dias do evento.', 'jelly-area-reservada' ); ?></small>
				</label>
			<?php endif; ?>

			<table class="jar-tabela jar-horarios__tabela">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Dia', 'jelly-area-reservada' ); ?></th>
						<th><?php esc_html_e( 'Início', 'jelly-area-reservada' ); ?></th>
						<th><?php esc_html_e( 'Fim', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__num jar-tabela__centro"><?php esc_html_e( 'Blocos', 'jelly-area-reservada' ); ?></th>
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
									<input type="checkbox" name="dia[<?php echo esc_attr( $dia ); ?>]" value="1" <?php checked( null !== $h ); ?> data-jar-dia="<?php echo esc_attr( $dia_curto( $dia ) ); ?>">
									<span>
										<?php echo esc_html( $dia_curto( $dia ) ); ?>
										<?php if ( $marcas ) : ?>
											<?php /* translators: %d: número de marcações */ ?>
											<small class="jar-evento-docs__meta"><?php echo esc_html( sprintf( _n( '%d marcação', '%d marcações', $marcas, 'jelly-area-reservada' ), $marcas ) ); ?></small>
										<?php endif; ?>
									</span>
								</label>
							</td>
							<?php // Início e fim, sempre em 24 horas ($select_hora, acima). ?>
							<td><?php $select_hora( 'inicio[' . $dia . ']', $h['inicio'] ?? '10:00', __( 'Início', 'jelly-area-reservada' ) . ', ' . $dia_curto( $dia ), jelly_ar_passo_horas( $intervalo ) ); ?></td>
							<td><?php $select_hora( 'fim[' . $dia . ']', $h['fim'] ?? '18:00', __( 'Fim', 'jelly-area-reservada' ) . ', ' . $dia_curto( $dia ), jelly_ar_passo_horas( $intervalo ) ); ?></td>
							<td class="jar-tabela__num jar-tabela__centro"><?php echo $h ? (int) count( jelly_ar_blocos( $h ) ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php
			/*
			 * Antes de gravar (assets/js/admin.js, data-jar-horarios): sem nenhum
			 * dia, o alerta de baixo e nada se envia; com dias por escolher, a
			 * confirmação do back-office, pelo botão escondido, com os dias em falta.
			 */
			?>
			<div class="jar-aviso jar-aviso--suspenso" role="alert" data-jar-horarios-nenhum hidden>
				<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
				<p><?php esc_html_e( 'Nenhum dia está escolhido. Para os associados poderem marcar, é necessário escolher pelo menos um dia do evento.', 'jelly-area-reservada' ); ?></p>
			</div>
			<button
				type="button"
				hidden
				data-jar-horarios-confirmar
				data-jar-confirmar
				data-jar-form="jar-horarios"
				data-titulo="<?php esc_attr_e( 'Gravar sem todos os dias?', 'jelly-area-reservada' ); ?>"
				data-texto=""
				data-sim="<?php esc_attr_e( 'Gravar horários', 'jelly-area-reservada' ); ?>"
				data-resultado=""
			></button>

			<footer class="jar-cartao__pe">
				<?php if ( $ha_horarios ) : ?>
					<button type="button" class="jar-btn jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
				<?php endif; ?>
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
		<?php
		$associados = jelly_ar_associados_ativos();

		/*
		 * O que a janela de um bloco precisa (assets/js/admin.js,
		 * data-jar-bloco): as mesas, os blocos de cada dia e as marcações de
		 * cada bloco. Os associados que se podem marcar vão já escritos na
		 * janela, com a procura.
		 */
		$dados_grelha = [
			'mesas'   => [],
			'dias'    => [],
			'blocos'  => $ocupados,
			// Sem lugares por mesa, a janela fala de uma marcação por horário (JELLY_AR_LUGARES).
			'lugares' => JELLY_AR_LUGARES,
			'estados' => [
				'pendente' => __( 'Pendente', 'jelly-area-reservada' ),
				'aprovada' => __( 'Confirmada', 'jelly-area-reservada' ),
			],
		];
		foreach ( $mesas as $m ) {
			$dados_grelha['mesas'][] = [ 'id' => $m['id'], 'nome' => $m['nome'], 'localizacao' => $m['localizacao'], 'lugares' => $m['lugares'] ];
		}
		foreach ( $dias as $dia ) {
			if ( isset( $horarios[ $dia ] ) ) {
				$dados_grelha['dias'][ $dia ] = [ 'rotulo' => $dia_curto( $dia ), 'intervalo' => $horarios[ $dia ]['intervalo'], 'blocos' => jelly_ar_blocos( $horarios[ $dia ] ) ];
			}
		}
		?>
		<ul class="jar-legenda" aria-label="<?php esc_attr_e( 'Legenda', 'jelly-area-reservada' ); ?>">
			<li><span class="jar-bloco jar-bloco--livre" aria-hidden="true"></span> <?php esc_html_e( 'Disponível', 'jelly-area-reservada' ); ?></li>
			<li><span class="jar-bloco jar-bloco--pendente" aria-hidden="true"></span> <?php esc_html_e( 'Pendente', 'jelly-area-reservada' ); ?></li>
			<li><span class="jar-bloco jar-bloco--aprovada" aria-hidden="true"></span> <?php esc_html_e( 'Confirmado', 'jelly-area-reservada' ); ?></li>
			<li class="jar-legenda__nota">
				<?php
				echo esc_html(
					JELLY_AR_LUGARES
						? __( 'Em cada bloco, os lugares ocupados e os da mesa. Um clique no bloco abre as marcações.', 'jelly-area-reservada' )
						: __( 'Um clique num horário abre a marcação: para marcar um associado, ou para a mudar ou remover.', 'jelly-area-reservada' )
				);
				?>
			</li>
		</ul>

		<?php foreach ( $dias as $dia ) : ?>
			<?php
			if ( ! isset( $horarios[ $dia ] ) ) {
				continue;
			}
			$blocos = jelly_ar_blocos( $horarios[ $dia ] );
			?>
			<section class="jar-cartao jar-cartao--tabela" id="jar-dia-<?php echo esc_attr( $dia ); ?>">
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
										/*
										 * A cor do bloco: disponível sem ninguém; pendente se
										 * alguma marcação ainda espera aprovação; confirmado
										 * se estão todas aprovadas.
										 */
										$lista   = $ocupados[ $m['id'] ][ $dia ][ $b ] ?? [];
										$estados = wp_list_pluck( $lista, 'estado' );
										$estado  = ! $lista ? 'livre' : ( in_array( 'pendente', $estados, true ) ? 'pendente' : 'aprovada' );
										$nomes   = array_map( function ( $o ) {
											return $o['quem'] . ( $o['empresa'] ? ' (' . $o['empresa'] . ')' : '' );
										}, $lista );
										if ( JELLY_AR_LUGARES ) {
											/* translators: 1: mesa, 2: hora, 3: lugares ocupados, 4: lugares da mesa */
											$titulo = sprintf( __( '%1$s, %2$s: %3$d de %4$d lugares', 'jelly-area-reservada' ), $m['nome'], $b, count( $lista ), $m['lugares'] ) . ( $nomes ? ' — ' . implode( ', ', $nomes ) : '' );
										} else {
											// Uma marcação por horário: quem marcou e o estado, ou "Disponível".
											$titulo = $m['nome'] . ', ' . $b . ': ' . ( $nomes
												? implode( ', ', $nomes ) . ' — ' . ( 'aprovada' === $estado ? __( 'Confirmado', 'jelly-area-reservada' ) : __( 'Pendente', 'jelly-area-reservada' ) )
												: __( 'Disponível', 'jelly-area-reservada' ) );
										}
										?>
										<td>
											<button
												type="button"
												class="jar-bloco jar-bloco--<?php echo esc_attr( $estado ); ?><?php echo count( $lista ) >= $m['lugares'] ? ' is-cheio' : ''; ?>"
												title="<?php echo esc_attr( $titulo ); ?>"
												aria-label="<?php echo esc_attr( $titulo ); ?>"
												data-jar-bloco
												data-mesa="<?php echo (int) $m['id']; ?>"
												data-dia="<?php echo esc_attr( $dia ); ?>"
												data-hora="<?php echo esc_attr( $b ); ?>"
											><?php echo JELLY_AR_LUGARES && $lista ? (int) count( $lista ) . '/' . (int) $m['lugares'] : ''; ?></button>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>
		<?php endforeach; ?>

		<script type="application/json" id="jar-grelha-dados"><?php echo wp_json_encode( $dados_grelha, JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>

		<?php
		/*
		 * A janela de um bloco. Abre com o clique no bloco e o assets/js/admin.js
		 * enche-a com o que é desse bloco:
		 * - as marcações que tem, para as remover ou mudar para outro bloco
		 *   (jelly_ar_marcacoes_alterar());
		 * - com lugares livres, os associados ativos, com procura por nome,
		 *   e-mail ou empresa, para os marcar (jelly_ar_marcacao_criar()).
		 * Um associado já marcado a essa hora, nesta ou noutra mesa, aparece
		 * na lista mas não se escolhe.
		 */
		?>
		<div class="jar-confirmar jar-janela" data-jar-bloco-janela hidden>
			<div class="jar-confirmar__fundo" data-jar-bloco-fechar></div>
			<div class="jar-confirmar__caixa jar-janela__caixa" role="dialog" aria-modal="true" aria-labelledby="jar-bloco-titulo">
				<?php // A cabeça: a mesa, o dia e a hora do bloco, e a ocupação em lugares. ?>
				<header class="jar-janela__cabeca">
					<span class="jar-icone jar-icone--turquesa" data-jar-bloco-icone><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
					<div class="jar-janela__titulos">
						<span class="jar-janela__quando" data-jar-bloco-quando></span>
						<h2 id="jar-bloco-titulo" tabindex="-1"></h2>
						<span class="jar-janela__local" data-jar-bloco-local><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <span></span></span>
					</div>
					<button type="button" class="jar-janela__fechar" data-jar-bloco-fechar aria-label="<?php esc_attr_e( 'Fechar', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
				</header>

				<div class="jar-janela__ocupacao">
					<p><strong data-jar-bloco-ocupados></strong> <span data-jar-bloco-ocupados-texto></span></p>
					<?php // Um traço por lugar da mesa: confirmado, pendente ou livre. ?>
					<span class="jar-lugares" data-jar-bloco-lugares aria-hidden="true"></span>
				</div>

				<div class="jar-janela__corpo">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="jar-janela__parte" data-jar-bloco-marcacoes>
						<input type="hidden" name="action" value="jelly_ar_marcacoes_alterar">
						<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
						<?php wp_nonce_field( 'jelly_ar_marcacoes_' . $evento['id'] ); ?>
						<h3 class="jar-janela__titulo-parte"><?php echo esc_html( JELLY_AR_LUGARES ? __( 'Marcações neste bloco', 'jelly-area-reservada' ) : __( 'Marcação', 'jelly-area-reservada' ) ); ?> <b data-jar-bloco-conta></b></h3>
						<ul class="jar-janela__pessoas" data-jar-bloco-lista></ul>

						<div class="jar-janela__barra">
							<label class="jar-janela__destino">
								<span class="screen-reader-text"><?php esc_html_e( 'Mudar a marcação para', 'jelly-area-reservada' ); ?></span>
								<i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
								<select name="destino" data-jar-bloco-destino></select>
							</label>
							<button type="submit" name="operacao" value="mover" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-bloco-mover><?php esc_html_e( 'Mudar', 'jelly-area-reservada' ); ?></button>
							<button type="submit" name="operacao" value="remover" class="jar-btn jar-btn--pequeno jar-btn--perigo" data-jar-bloco-remover><i class="fa-regular fa-trash-can" aria-hidden="true"></i> <?php esc_html_e( 'Remover', 'jelly-area-reservada' ); ?></button>
						</div>
						<p class="jar-janela__ajuda" data-jar-bloco-ajuda-marcacoes>
							<?php
							echo esc_html(
								JELLY_AR_LUGARES
									? __( 'Escolha uma ou mais marcações para as mudar de bloco ou remover. Cada associado recebe um e-mail com a alteração.', 'jelly-area-reservada' )
									: __( 'A marcação pode mudar para outro horário livre, ou ser removida. O associado recebe um e-mail com a alteração.', 'jelly-area-reservada' )
							);
							?>
						</p>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="jar-janela__parte" data-jar-bloco-marcar>
						<input type="hidden" name="action" value="jelly_ar_marcacao_criar">
						<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
						<input type="hidden" name="mesa" value="">
						<input type="hidden" name="dia" value="">
						<input type="hidden" name="hora" value="">
						<?php wp_nonce_field( 'jelly_ar_marcacoes_' . $evento['id'] ); ?>
						<h3 class="jar-janela__titulo-parte"><?php echo esc_html( JELLY_AR_LUGARES ? __( 'Marcar associados', 'jelly-area-reservada' ) : __( 'Marcar associado', 'jelly-area-reservada' ) ); ?> <b data-jar-bloco-livres></b></h3>

						<?php // O bloco cheio: não há onde marcar. ?>
						<div class="jar-janela__vazio" data-jar-bloco-cheio hidden>
							<span class="jar-icone jar-icone--magenta"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
							<div>
								<?php if ( JELLY_AR_LUGARES ) : ?>
									<strong><?php esc_html_e( 'Bloco completo', 'jelly-area-reservada' ); ?></strong>
									<p><?php esc_html_e( 'Todos os lugares desta mesa estão ocupados a esta hora. Para marcar outro associado, é necessário remover ou mudar uma das marcações acima.', 'jelly-area-reservada' ); ?></p>
								<?php else : ?>
									<strong><?php esc_html_e( 'Horário marcado', 'jelly-area-reservada' ); ?></strong>
									<p><?php esc_html_e( 'Esta mesa já tem uma marcação a esta hora. Para marcar outro associado, é necessário mudar ou remover a marcação acima.', 'jelly-area-reservada' ); ?></p>
								<?php endif; ?>
							</div>
						</div>

						<?php if ( $associados ) : ?>
							<div data-jar-bloco-escolha>
								<div class="jar-filtro jar-janela__procura" role="search">
									<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
									<label class="screen-reader-text" for="jar-associados-procura"><?php esc_html_e( 'Procurar associados', 'jelly-area-reservada' ); ?></label>
									<input type="search" id="jar-associados-procura" placeholder="<?php esc_attr_e( 'Procurar por nome, e-mail ou empresa', 'jelly-area-reservada' ); ?>" data-jar-filtrar="jar-associados-lista" autocomplete="off">
								</div>

								<fieldset class="jar-janela__pessoas jar-janela__associados" id="jar-associados-lista">
									<legend class="screen-reader-text"><?php esc_html_e( 'Associados', 'jelly-area-reservada' ); ?></legend>
									<?php foreach ( $associados as $a ) : ?>
										<label class="jar-janela__pessoa" data-jar-filtrar-texto="<?php echo esc_attr( $a['nome'] . ' ' . $a['email'] . ' ' . $a['empresa'] ); ?>" data-jar-associado="<?php echo (int) $a['id']; ?>">
											<?php // Uma marcação por horário: escolhe-se um associado só (radio); com lugares, vários. ?>
											<input type="<?php echo JELLY_AR_LUGARES ? 'checkbox' : 'radio'; ?>" name="user[]" value="<?php echo (int) $a['id']; ?>">
											<span class="jar-avatar" aria-hidden="true"><?php echo esc_html( jelly_ar_iniciais( $a['nome'] ) ); ?></span>
											<span class="jar-janela__pessoa-texto">
												<strong><?php echo esc_html( $a['nome'] ); ?></strong>
												<small><?php echo esc_html( implode( ' · ', array_filter( [ $a['empresa'], $a['email'] ] ) ) ); ?></small>
											</span>
											<span class="jar-janela__marca" data-jar-associado-nota hidden></span>
										</label>
									<?php endforeach; ?>
									<p class="jar-janela__nada" data-jar-filtrar-nada hidden><?php esc_html_e( 'Nenhum associado corresponde à procura.', 'jelly-area-reservada' ); ?></p>
								</fieldset>

								<div class="jar-janela__barra">
									<span class="jar-janela__escolhidos" data-jar-bloco-escolhidos aria-live="polite"></span>
									<button type="submit" class="jar-btn jar-btn--pequeno" data-jar-bloco-confirmar disabled><i class="fa-solid fa-check" aria-hidden="true"></i> <?php esc_html_e( 'Marcar', 'jelly-area-reservada' ); ?></button>
								</div>
								<p class="jar-janela__ajuda"><?php esc_html_e( 'A marcação fica confirmada e o associado recebe um e-mail com os dados.', 'jelly-area-reservada' ); ?></p>
							</div>
						<?php else : ?>
							<?php // Sem associados ativos, o caminho para os ter: aprovar os pedidos em Utilizadores. ?>
							<div class="jar-janela__vazio" data-jar-bloco-escolha>
								<span class="jar-icone jar-icone--roxo"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
								<div>
									<strong><?php esc_html_e( 'Ainda não há associados ativos', 'jelly-area-reservada' ); ?></strong>
									<p><?php esc_html_e( 'Os associados aparecem aqui depois de o pedido de registo ser aprovado e de a conta ser ativada.', 'jelly-area-reservada' ); ?></p>
									<a class="jar-btn jar-btn--pequeno jar-btn--contorno" href="<?php echo esc_url( jelly_ar_admin_url( 'utilizadores' ) ); ?>"><?php esc_html_e( 'Ver utilizadores', 'jelly-area-reservada' ); ?></a>
								</div>
							</div>
						<?php endif; ?>
					</form>
				</div>
			</div>
		</div>
	<?php endif; ?>
<?php endif; ?>
