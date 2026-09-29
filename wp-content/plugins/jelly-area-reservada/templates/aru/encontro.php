<?php
/**
 * Um encontro na ARU (/area-reservada/encontros/<slug>/).
 *
 * À esquerda, o leitor com o vídeo escolhido e, por baixo, a lista dos
 * vídeos do encontro, pela ordem do back-office: escolher um troca o vídeo do
 * leitor sem sair da página (assets/js/area-reservada.js, data-aru-leitor);
 * sem JavaScript, o link (?video=<id>) abre a página com esse vídeo. Depois,
 * o texto do encontro. À direita, os outros encontros mais recentes.
 *
 * Os vídeos vêm do youtube-nocookie, que só guarda cookies de quem carrega
 * no play.
 *
 * @var array $aru    secao, user, pagina, encontro.
 * @var array $pessoa jelly_ar_aru_pessoa().
 */

defined( 'ABSPATH' ) || exit;

$e      = $aru['encontro'];
$videos = $e['videos'];
$pedido = isset( $_GET['video'] ) ? absint( $_GET['video'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$atual  = $videos ? $videos[0] : null;

foreach ( $videos as $v ) {
	if ( $v['id'] === $pedido ) {
		$atual = $v;
	}
}

$outros = array_slice( array_values( array_filter( jelly_ar_encontros_publicados(), function ( $o ) use ( $e ) {
	return $o['id'] !== $e['id'];
} ) ), 0, 4 );
$n      = count( $videos );
?>

<a class="aru-voltar" href="<?php echo esc_url( jelly_ar_area_url( 'encontros' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Encontros', 'jelly-area-reservada' ); ?></a>

<header class="aru-titulo aru-encontro-titulo">
	<h1><?php echo esc_html( $e['titulo'] ); ?></h1>
	<ul class="aru-encontro-titulo__factos">
		<li><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?php echo esc_html( ucfirst( jelly_ar_data( 'j \d\e F \d\e Y', strtotime( $e['data'] ) ) ) ); ?></li>
		<?php if ( $n ) : ?>
			<?php /* translators: %d: número de vídeos */ ?>
			<li><i class="fa-solid fa-film" aria-hidden="true"></i> <?php echo esc_html( sprintf( _n( '%d vídeo', '%d vídeos', $n, 'jelly-area-reservada' ), $n ) ); ?></li>
		<?php endif; ?>
	</ul>
	<?php if ( $e['resumo'] ) : ?>
		<p><?php echo esc_html( $e['resumo'] ); ?></p>
	<?php endif; ?>
</header>

<div class="aru-evento-grelha">
	<div class="aru-evento-grelha__principal">
		<section class="aru-cartao aru-leitor" data-aru-leitor>
			<?php if ( ! $atual ) : ?>
				<div class="aru-vazio">
					<i class="fa-regular fa-circle-play" aria-hidden="true"></i>
					<p><?php esc_html_e( 'Os vídeos deste encontro ainda não estão disponíveis.', 'jelly-area-reservada' ); ?></p>
				</div>
			<?php else : ?>
				<div class="aru-leitor__ecra">
					<iframe
						src="<?php echo esc_url( jelly_ar_youtube_embed( $atual['youtube'] ) ); ?>"
						title="<?php echo esc_attr( $atual['titulo'] ? $atual['titulo'] : $e['titulo'] ); ?>"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
						allowfullscreen
						referrerpolicy="strict-origin-when-cross-origin"
						data-aru-leitor-ecra
					></iframe>
				</div>
				<h2 class="aru-leitor__titulo" data-aru-leitor-titulo><?php echo esc_html( $atual['titulo'] ? $atual['titulo'] : $e['titulo'] ); ?></h2>

				<?php if ( $n > 1 ) : ?>
					<ol class="aru-leitor__lista">
						<?php foreach ( $videos as $i => $v ) : ?>
							<?php $e_atual = $v['id'] === $atual['id']; ?>
							<li>
								<a
									class="aru-leitor__video<?php echo $e_atual ? ' is-atual' : ''; ?>"
									href="<?php echo esc_url( add_query_arg( 'video', $v['id'], jelly_ar_aru_encontro_url( $e ) ) ); ?>"
									data-aru-leitor-video="<?php echo esc_attr( jelly_ar_youtube_embed( $v['youtube'] ) ); ?>"
									data-titulo="<?php echo esc_attr( $v['titulo'] ? $v['titulo'] : $e['titulo'] ); ?>"
									<?php echo $e_atual ? 'aria-current="true"' : ''; ?>
								>
									<span class="aru-leitor__mini">
										<img src="<?php echo esc_url( $v['miniatura'] ); ?>" alt="" loading="lazy">
										<span class="aru-leitor__a-ver"><i class="fa-solid fa-play" aria-hidden="true"></i> <?php esc_html_e( 'A ver', 'jelly-area-reservada' ); ?></span>
									</span>
									<span class="aru-leitor__texto">
										<small><?php echo (int) ( $i + 1 ); ?></small>
										<strong><?php echo esc_html( $v['titulo'] ? $v['titulo'] : __( 'Vídeo', 'jelly-area-reservada' ) . ' ' . ( $i + 1 ) ); ?></strong>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			<?php endif; ?>
		</section>

		<?php if ( $e['texto'] ) : ?>
			<section class="aru-cartao">
				<header class="aru-cartao__cabeca">
					<h2><i class="fa-regular fa-file-lines" aria-hidden="true"></i> <?php esc_html_e( 'Sobre o encontro', 'jelly-area-reservada' ); ?></h2>
				</header>
				<div class="aru-texto"><?php echo wp_kses_post( wpautop( $e['texto'] ) ); ?></div>
			</section>
		<?php endif; ?>
	</div>

	<?php if ( $outros ) : ?>
		<aside class="aru-cartao aru-encontro-outros">
			<header class="aru-cartao__cabeca">
				<h2><i class="fa-solid fa-user-group" aria-hidden="true"></i> <?php esc_html_e( 'Outros encontros', 'jelly-area-reservada' ); ?></h2>
			</header>
			<ul>
				<?php foreach ( $outros as $o ) : ?>
					<li>
						<a href="<?php echo esc_url( jelly_ar_aru_encontro_url( $o ) ); ?>">
							<span class="aru-encontro-outros__capa<?php echo $o['capa'] ? '' : ' is-vazia'; ?>">
								<?php if ( $o['capa'] ) : ?>
									<img src="<?php echo esc_url( $o['capa'] ); ?>" alt="" loading="lazy">
								<?php endif; ?>
							</span>
							<span>
								<strong><?php echo esc_html( $o['titulo'] ); ?></strong>
								<?php /* translators: 1: data, 2: número de vídeos */ ?>
								<small><?php echo esc_html( $o['data_texto'] . ( $o['videos'] ? ' · ' . sprintf( _n( '%d vídeo', '%d vídeos', count( $o['videos'] ), 'jelly-area-reservada' ), count( $o['videos'] ) ) : '' ) ); ?></small>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<a class="aru-botao aru-botao--contorno aru-evento-resumo__botao" href="<?php echo esc_url( jelly_ar_area_url( 'encontros' ) ); ?>">
				<span><?php esc_html_e( 'Ver todos', 'jelly-area-reservada' ); ?></span>
				<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
			</a>
		</aside>
	<?php endif; ?>
</div>
