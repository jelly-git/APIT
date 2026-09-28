<?php
/**
 * O Início da ARU: a saudação, os números, o evento em destaque com os
 * horários livres, as marcações do associado, os próximos eventos e as
 * oportunidades.
 *
 * @var array $aru    secao, user.
 * @var array $pessoa jelly_ar_aru_pessoa().
 */

defined( 'ABSPATH' ) || exit;

$uid        = $aru['user']->ID;
$documentos = jelly_ar_aru_documentos();
$marcacoes  = jelly_ar_aru_marcacoes( $uid );
$eventos    = jelly_ar_aru_eventos();
$destaque   = jelly_ar_aru_destaque( $eventos, $uid );
$estados    = jelly_ar_aru_estados();

$novos = count( array_filter( $documentos, function ( $d ) {
	return $d['ts'] >= time() - 30 * DAY_IN_SECONDS;
} ) );

$proximas  = array_values( array_filter( $marcacoes, function ( $m ) {
	return $m['futura'] && in_array( $m['estado'], [ 'pendente', 'aprovada' ], true );
} ) );
// A lista do Início: as marcações vivas (pendentes e confirmadas), das que vêm às que passaram.
$vivas = array_values( array_filter( $marcacoes, function ( $m ) {
	return $m['viva'];
} ) );
$pendentes = count( array_filter( $proximas, function ( $m ) {
	return 'pendente' === $m['estado'];
} ) );

$com_marcacao = count( array_filter( $eventos, function ( $e ) {
	return $e['marcacoes'];
} ) );

// Os próximos eventos por baixo do destaque: sem repetir o destaque.
$outros = array_values( array_filter( $eventos, function ( $e ) use ( $destaque ) {
	return ! $destaque || $e['id'] !== $destaque['evento']['id'];
} ) );

// O cartão de um número: o ícone, o rótulo, o valor e o que ele quer dizer.
$numero = function ( $rotulo, $valor, $nota, $icone, $cor, $url ) {
	$marca = $url ? 'a' : 'div';
	?>
	<<?php echo $marca; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="aru-numero<?php echo $url ? '' : ' is-em-breve'; ?>"<?php echo $url ? ' href="' . esc_url( $url ) . '"' : ''; ?>>
		<span class="aru-icone aru-icone--<?php echo esc_attr( $cor ); ?>"><i class="fa-solid <?php echo esc_attr( $icone ); ?>" aria-hidden="true"></i></span>
		<span class="aru-numero__rotulo"><?php echo esc_html( $rotulo ); ?></span>
		<strong class="aru-numero__valor"><?php echo esc_html( $valor ); ?></strong>
		<span class="aru-numero__nota"><?php echo esc_html( $nota ); ?></span>
		<?php if ( $url ) : ?>
			<i class="fa-solid fa-arrow-right aru-numero__seta" aria-hidden="true"></i>
		<?php endif; ?>
	</<?php echo $marca; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
};
?>

<section class="aru-ola">
	<div class="aru-ola__texto">
		<?php /* translators: %s: primeiro nome */ ?>
		<h1><?php echo esc_html( sprintf( __( 'Olá, %s', 'jelly-area-reservada' ), $pessoa['primeiro'] ) ); ?></h1>
		<p class="aru-ola__sub"><?php esc_html_e( 'Bem-vindo(a) à sua Área Reservada', 'jelly-area-reservada' ); ?></p>
		<p><?php esc_html_e( 'Aqui pode consultar documentos exclusivos, gerir as suas marcações e acompanhar os próximos eventos da APIT.', 'jelly-area-reservada' ); ?></p>
	</div>
	<span class="aru-ola__arte" aria-hidden="true"></span>
</section>

<div class="aru-numeros">
	<?php
	$numero(
		__( 'Documentos', 'jelly-area-reservada' ),
		count( $documentos ),
		/* translators: %d: documentos novos */
		$novos ? sprintf( _n( '%d novo nos últimos 30 dias', '%d novos nos últimos 30 dias', $novos, 'jelly-area-reservada' ), $novos ) : __( 'disponíveis para descarregar', 'jelly-area-reservada' ),
		'fa-file-lines',
		'roxo',
		jelly_ar_area_url( 'documentos' )
	);
	$numero(
		__( 'Marcações', 'jelly-area-reservada' ),
		count( $proximas ),
		/* translators: %d: marcações por aprovar */
		$pendentes ? sprintf( _n( '%d por aprovar', '%d por aprovar', $pendentes, 'jelly-area-reservada' ), $pendentes ) : __( 'marcações por vir', 'jelly-area-reservada' ),
		'fa-calendar-check',
		'turquesa',
		jelly_ar_area_url( 'marcacoes' )
	);
	$numero(
		__( 'Próximos eventos', 'jelly-area-reservada' ),
		count( $eventos ),
		/* translators: %d: eventos com marcação de mesa */
		$com_marcacao ? sprintf( _n( '%d com marcação de mesa', '%d com marcação de mesa', $com_marcacao, 'jelly-area-reservada' ), $com_marcacao ) : __( 'no calendário', 'jelly-area-reservada' ),
		'fa-star',
		'magenta',
		home_url( '/calendario/' )
	);
	$numero( __( 'Encontros', 'jelly-area-reservada' ), '—', __( 'Em breve', 'jelly-area-reservada' ), 'fa-user-group', 'azul', '' );
	?>
</div>

<div class="aru-grelha">
	<?php /* ---------- O evento em destaque ---------- */ ?>
	<section class="aru-cartao aru-destaque">
		<?php if ( ! $destaque ) : ?>
			<header class="aru-cartao__cabeca">
				<div>
					<span class="aru-etiqueta"><?php esc_html_e( 'Destaque', 'jelly-area-reservada' ); ?></span>
					<h2><?php esc_html_e( 'Marcação de mesas', 'jelly-area-reservada' ); ?></h2>
				</div>
			</header>
			<div class="aru-vazio">
				<i class="fa-regular fa-calendar" aria-hidden="true"></i>
				<p><?php esc_html_e( 'De momento não há eventos com marcação de mesas abertas. Quando houver, aparecem aqui.', 'jelly-area-reservada' ); ?></p>
			</div>
		<?php else : ?>
			<?php
			$e     = $destaque['evento'];
			$minha = $destaque['minha'];
			?>
			<header class="aru-cartao__cabeca">
				<div>
					<span class="aru-etiqueta"><?php esc_html_e( 'Destaque', 'jelly-area-reservada' ); ?></span>
					<h2><?php echo esc_html( $e['titulo'] ); ?></h2>
					<?php if ( $e['categoria_nome'] ) : ?>
						<p class="aru-cartao__meta"><?php echo esc_html( $e['categoria_nome'] ); ?></p>
					<?php endif; ?>
				</div>
				<a class="aru-ligacao" href="<?php echo esc_url( home_url( '/calendario/' ) ); ?>"><?php esc_html_e( 'Ver o calendário', 'jelly-area-reservada' ); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
			</header>

			<div class="aru-destaque__corpo">
				<div class="aru-destaque__capa" style="--aru-cor-a: <?php echo esc_attr( $e['cores']['inicio'] ); ?>; --aru-cor-b: <?php echo esc_attr( $e['cores']['fim'] ); ?>;">
					<span class="aru-destaque__dia"><?php echo (int) $e['dia_n']; ?></span>
					<span class="aru-destaque__mes"><?php echo esc_html( $e['mes'] ); ?></span>
					<span class="aru-destaque__nome"><?php echo esc_html( $e['titulo'] ); ?></span>
				</div>

				<div class="aru-destaque__info">
					<ul class="aru-factos">
						<li><i class="fa-regular fa-calendar" aria-hidden="true"></i><?php echo esc_html( $e['datas'] ); ?></li>
						<?php if ( $e['local'] ) : ?>
							<li><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?php echo esc_html( $e['local'] ); ?></li>
						<?php endif; ?>
						<li>
							<i class="fa-solid fa-chair" aria-hidden="true"></i>
							<span>
								<?php esc_html_e( 'Horários livres', 'jelly-area-reservada' ); ?>
								<strong><?php echo (int) $destaque['livres']; ?> / <?php echo (int) $destaque['total']; ?></strong>
							</span>
						</li>
					</ul>

					<?php if ( $minha ) : ?>
						<p class="aru-destaque__minha">
							<span class="aru-estado aru-estado--<?php echo esc_attr( $minha['estado'] ); ?>"><?php echo esc_html( $estados[ $minha['estado'] ] ?? $minha['estado'] ); ?></span>
							<?php /* translators: 1: dia, 2: hora */ ?>
							<?php echo esc_html( sprintf( __( 'A sua marcação: %1$s, %2$s', 'jelly-area-reservada' ), ucfirst( jelly_ar_data( 'j M', strtotime( $minha['dia'] ) ) ), $minha['hora'] ) ); ?>
						</p>
					<?php endif; ?>

					<a class="aru-botao" href="<?php echo esc_url( jelly_ar_url_marcacao( $e['id'] ) ); ?>">
						<span><?php echo esc_html( $minha ? __( 'Ver a minha marcação', 'jelly-area-reservada' ) : __( 'Marcar mesa', 'jelly-area-reservada' ) ); ?></span>
						<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
					</a>
				</div>
			</div>

			<?php if ( $destaque['horas'] ) : ?>
				<div class="aru-horarios">
					<div class="aru-horarios__cabeca">
						<i class="fa-regular fa-clock" aria-hidden="true"></i>
						<div>
							<strong><?php esc_html_e( 'Horários', 'jelly-area-reservada' ); ?></strong>
							<span><?php echo esc_html( $destaque['dia'] ); ?></span>
						</div>
					</div>
					<ul class="aru-horarios__lista">
						<?php
						$rotulos = [
							'livre'   => __( 'Disponível', 'jelly-area-reservada' ),
							'ocupado' => __( 'Ocupado', 'jelly-area-reservada' ),
							'minha'   => __( 'A sua marcação', 'jelly-area-reservada' ),
						];
						?>
						<?php foreach ( $destaque['horas'] as $h ) : ?>
							<li class="aru-hora aru-hora--<?php echo esc_attr( $h['estado'] ); ?>">
								<strong><?php echo esc_html( $h['hora'] ); ?></strong>
								<span><?php echo esc_html( $rotulos[ $h['estado'] ] ); ?></span>
							</li>
						<?php endforeach; ?>
						<?php if ( $destaque['mais'] ) : ?>
							<li class="aru-hora aru-hora--mais">
								<a href="<?php echo esc_url( jelly_ar_url_marcacao( $e['id'] ) ); ?>">
									<?php /* translators: %d: horários que não se mostram */ ?>
									<strong><?php echo esc_html( sprintf( __( '+%d', 'jelly-area-reservada' ), $destaque['mais'] ) ); ?></strong>
									<span><?php esc_html_e( 'Ver todos', 'jelly-area-reservada' ); ?></span>
								</a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</section>

	<?php /* ---------- As minhas marcações ---------- */ ?>
	<section class="aru-cartao aru-minhas">
		<header class="aru-cartao__cabeca">
			<h2><i class="fa-regular fa-clock" aria-hidden="true"></i> <?php esc_html_e( 'As minhas marcações', 'jelly-area-reservada' ); ?></h2>
			<?php if ( $marcacoes ) : ?>
				<a class="aru-ligacao" href="<?php echo esc_url( jelly_ar_area_url( 'marcacoes' ) ); ?>"><?php esc_html_e( 'Ver todas', 'jelly-area-reservada' ); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
			<?php endif; ?>
		</header>

		<?php if ( ! $vivas ) : ?>
			<div class="aru-vazio">
				<i class="fa-solid fa-chair" aria-hidden="true"></i>
				<p><?php esc_html_e( 'Ainda não tem marcações de mesa. Nos eventos com marcação, escolha a mesa e a hora.', 'jelly-area-reservada' ); ?></p>
			</div>
		<?php else : ?>
			<ul class="aru-lista">
				<?php foreach ( array_slice( $vivas, 0, 4 ) as $m ) : ?>
					<?php include JELLY_AR_DIR . 'templates/aru/parte-marcacao.php'; ?>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<?php /* ---------- Próximos eventos ---------- */ ?>
	<section class="aru-cartao aru-proximos">
		<header class="aru-cartao__cabeca">
			<h2><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?php esc_html_e( 'Próximos eventos', 'jelly-area-reservada' ); ?></h2>
			<a class="aru-ligacao" href="<?php echo esc_url( home_url( '/calendario/' ) ); ?>"><?php esc_html_e( 'Ver todos', 'jelly-area-reservada' ); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
		</header>

		<?php if ( ! $outros ) : ?>
			<div class="aru-vazio">
				<i class="fa-regular fa-calendar" aria-hidden="true"></i>
				<p><?php esc_html_e( 'Não há outros eventos marcados por agora.', 'jelly-area-reservada' ); ?></p>
			</div>
		<?php else : ?>
			<ul class="aru-eventos">
				<?php foreach ( array_slice( $outros, 0, 3 ) as $e ) : ?>
					<li>
						<a class="aru-evento" href="<?php echo esc_url( $e['marcacoes'] ? jelly_ar_url_marcacao( $e['id'] ) : home_url( '/calendario/' ) ); ?>">
							<span class="aru-data" style="--aru-cor-a: <?php echo esc_attr( $e['cores']['inicio'] ); ?>; --aru-cor-b: <?php echo esc_attr( $e['cores']['fim'] ); ?>;">
								<strong><?php echo (int) $e['dia_n']; ?></strong>
								<small><?php echo esc_html( $e['mes'] ); ?></small>
							</span>
							<span class="aru-evento__texto">
								<strong><?php echo esc_html( $e['titulo'] ); ?></strong>
								<small><?php echo esc_html( implode( ' · ', array_filter( [ $e['datas'], $e['local'] ] ) ) ); ?></small>
								<?php if ( $e['marcacoes'] ) : ?>
									<em><?php esc_html_e( 'Marcação de mesa', 'jelly-area-reservada' ); ?></em>
								<?php endif; ?>
							</span>
							<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<?php /* ---------- Oportunidades ---------- */ ?>
	<section class="aru-oportunidades">
		<h2><?php esc_html_e( 'Mais oportunidades para o seu projeto', 'jelly-area-reservada' ); ?></h2>
		<p><?php esc_html_e( 'A APIT liga os seus associados aos principais mercados internacionais de televisão.', 'jelly-area-reservada' ); ?></p>
		<a class="aru-botao aru-botao--branco" href="<?php echo esc_url( home_url( '/internacionalizacao/' ) ); ?>"><span><?php esc_html_e( 'Saber mais', 'jelly-area-reservada' ); ?></span> <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
	</section>
</div>
