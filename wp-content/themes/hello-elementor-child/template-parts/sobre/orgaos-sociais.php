<?php
/**
 * Órgãos Sociais — one row per órgão, its name on the left and its members
 * beside it.
 *
 * The client dropped the logos, so a member is a name and a function. The
 * member grid is auto-fit rather than a fixed three columns, which is what the
 * client asked for: a fourth or fifth member wraps onto a new line at the same
 * column width instead of squeezing the row.
 */
$orgaos  = apit_get_orgaos();
$membros = apit_get_membros_por_orgao();

if ( ! $membros ) {
	return;
}
?>
<section class="sobre-orgaos">
	<div class="apit-container">
		<p class="sobre-rotulo sobre-rotulo--direita"><?php esc_html_e( 'Órgãos Sociais', 'apit' ); ?></p>

		<?php foreach ( $orgaos as $slug => $orgao ) : ?>
			<?php if ( empty( $membros[ $slug ] ) ) : ?>
				<?php continue; ?>
			<?php endif; ?>

			<div class="sobre-orgaos__linha">
				<h3 class="sobre-orgaos__nome" style="color: <?php echo esc_attr( $orgao['cor'] ); ?>">
					<?php echo esc_html( $orgao['nome'] ); ?>
				</h3>

				<ul class="sobre-orgaos__membros">
					<?php foreach ( $membros[ $slug ] as $membro ) : ?>
						<li class="sobre-orgaos__membro">
							<span class="sobre-orgaos__membro-nome"><?php echo esc_html( get_the_title( $membro ) ); ?></span>
							<?php $cargo = get_post_meta( $membro->ID, 'apit_orgao_social_cargo', true ); ?>
							<?php if ( $cargo ) : ?>
								<span class="sobre-orgaos__membro-cargo"><?php echo esc_html( $cargo ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
</section>
