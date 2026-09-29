<?php
/**
 * Um evento que não está na Área Reservada (não existe, não está publicado, ou
 * não aceita marcações), ou um encontro que não existe ou não está publicado:
 * a mesma moldura, com o caminho de volta. Responde 404.
 *
 * @var array $aru secao, user, pagina.
 */

defined( 'ABSPATH' ) || exit;

$e_encontro = 'encontros' === $aru['secao'];
?>

<a class="aru-voltar" href="<?php echo esc_url( jelly_ar_area_url( $e_encontro ? 'encontros' : 'eventos' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php echo esc_html( $e_encontro ? __( 'Encontros', 'jelly-area-reservada' ) : __( 'Eventos', 'jelly-area-reservada' ) ); ?></a>

<section class="aru-cartao">
	<div class="aru-vazio">
		<i class="fa-regular <?php echo $e_encontro ? 'fa-circle-play' : 'fa-calendar-xmark'; ?>" aria-hidden="true"></i>
		<p><?php echo esc_html( $e_encontro ? __( 'Este encontro não existe ou já não está disponível na Área Reservada.', 'jelly-area-reservada' ) : __( 'Este evento não existe ou já não está disponível na Área Reservada.', 'jelly-area-reservada' ) ); ?></p>
	</div>
</section>
