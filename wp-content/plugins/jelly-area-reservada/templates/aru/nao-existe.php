<?php
/**
 * Um evento que não está na Área Reservada (não existe, não está publicado, ou
 * é só do site): a mesma moldura, com o caminho de volta. Responde 404.
 */

defined( 'ABSPATH' ) || exit;
?>

<a class="aru-voltar" href="<?php echo esc_url( jelly_ar_area_url( 'eventos' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></a>

<section class="aru-cartao">
	<div class="aru-vazio">
		<i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i>
		<p><?php esc_html_e( 'Este evento não existe ou já não está disponível na Área Reservada.', 'jelly-area-reservada' ); ?></p>
	</div>
</section>
