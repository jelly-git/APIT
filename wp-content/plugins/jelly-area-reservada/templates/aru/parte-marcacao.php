<?php
/**
 * Uma marcação do associado, numa lista da ARU (o Início e as Marcações): a
 * data na cor do evento, o evento, o dia e a hora, a mesa e o estado. As que
 * ainda vêm abrem o pop-up da marcação, onde se vê o detalhe.
 *
 * @var array $m       uma linha de jelly_ar_aru_marcacoes().
 * @var array $estados jelly_ar_aru_estados().
 */

defined( 'ABSPATH' ) || exit;

$abre = $m['futura'] && $m['viva'];
$tag  = $abre ? 'a' : 'div';
?>
<li>
	<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="aru-marcacao<?php echo $abre ? '' : ' is-passada'; ?>"<?php echo $abre ? ' href="' . esc_url( jelly_ar_url_marcacao( $m['evento_id'] ) ) . '"' : ''; ?>>
		<span class="aru-data" style="--aru-cor-a: <?php echo esc_attr( $m['cores']['inicio'] ); ?>; --aru-cor-b: <?php echo esc_attr( $m['cores']['fim'] ); ?>;">
			<strong><?php echo (int) $m['dia_n']; ?></strong>
			<small><?php echo esc_html( $m['mes'] ); ?></small>
		</span>
		<span class="aru-marcacao__texto">
			<strong><?php echo esc_html( $m['evento'] ); ?></strong>
			<small><?php echo esc_html( $m['data'] . ' · ' . $m['hora'] ); ?></small>
			<small class="aru-marcacao__mesa"><i class="fa-solid fa-chair" aria-hidden="true"></i> <?php echo esc_html( implode( ' · ', array_filter( [ $m['mesa'], $m['localizacao'] ] ) ) ); ?></small>
		</span>
		<span class="aru-estado aru-estado--<?php echo esc_attr( $m['estado'] ); ?>"><?php echo esc_html( $estados[ $m['estado'] ] ?? $m['estado'] ); ?></span>
		<?php if ( $abre ) : ?>
			<i class="fa-solid fa-chevron-right aru-marcacao__seta" aria-hidden="true"></i>
		<?php endif; ?>
	</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
</li>
