<?php
/**
 * A área privada do associado (inc/area-privada.php). Por agora só o nome,
 * para provar o caminho: entrar, chegar aqui, ver os próprios dados, sair.
 *
 * Os dados são sempre os da conta com sessão.
 */

defined( 'ABSPATH' ) || exit;

$user   = wp_get_current_user();
$perfil = jelly_ar_associado( $user->ID );
$nome   = $perfil ? trim( $perfil->nome . ' ' . $perfil->apelido ) : '';
$nome   = '' !== $nome ? $nome : $user->display_name;

get_header();
?>

<section class="apit-pagina-hero hero--area-privada e-no-lazyload area-privada-hero">
	<?php if ( shortcode_exists( 'apit_wordmark' ) ) : ?>
		<?php echo do_shortcode( '[apit_wordmark texto="RESER VADA"]' ); ?>
	<?php endif; ?>

	<div class="pagina-hero__col">
		<p class="area-privada-hero__ola"><?php esc_html_e( 'Área Reservada', 'jelly-area-reservada' ); ?></p>
		<h1 class="pagina-hero__titulo area-privada-hero__nome"><?php echo esc_html( $nome ); ?></h1>
		<?php if ( $perfil && $perfil->empresa ) : ?>
			<p class="pagina-hero__texto area-privada-hero__empresa"><?php echo esc_html( $perfil->empresa ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="area-privada">
	<div class="area-privada__caixa">
		<p class="area-privada__texto">
			<?php
			/* translators: %s: nome do associado */
			echo esc_html( sprintf( __( 'Bem-vindo(a), %s. Esta é a sua área privada.', 'jelly-area-reservada' ), $nome ) );
			?>
		</p>
		<a class="btn btn--outline area-privada__sair" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Terminar sessão', 'jelly-area-reservada' ); ?></a>
	</div>
</section>

<?php
get_footer();
