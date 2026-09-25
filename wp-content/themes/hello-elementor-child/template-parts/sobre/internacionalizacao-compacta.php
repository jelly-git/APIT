<?php
/**
 * Internacionalização — a mesma banda, em versão baixa.
 *
 * A banda inteira ocupa meio ecrã no fim de páginas que já são longas. Esta diz
 * exactamente o mesmo — título, texto, botão e logótipo — em pouco mais de
 * metade da altura.
 *
 * O conteúdo é o mesmo de propósito: a poupança vem do respiro, que passa de
 * 120px para 44px em cima e em baixo, e do logótipo, que deixa de ser a peça
 * mais alta da banda e passa a medir o que vai do título ao botão. Assim é o
 * texto que manda na altura, e não a imagem.
 *
 * Os campos são os mesmos, lidos da Sobre a APIT como na versão grande: uma só
 * fonte, um só sítio para editar, e as duas versões nunca dizem coisas
 * diferentes.
 */
$origem = get_page_by_path( 'sobre-apit' );
$origem = $origem ? $origem->ID : null;

$titulo = trim( (string) apit_campo( 'sobre_inter_titulo', $origem ) );
$texto  = trim( (string) apit_campo( 'sobre_inter_texto', $origem ) );
$botao  = trim( (string) apit_campo( 'sobre_inter_botao', $origem ) );
$url    = trim( (string) apit_campo( 'sobre_inter_url', $origem ) );

$marca = apit_media_url( (string) apit_campo( 'sobre_inter_marca', $origem ), 'img' );

if ( ! $marca ) {
	$marca = get_stylesheet_directory_uri() . '/assets/img/logo-watch-portugal-branco.png';
}

if ( ! $titulo && ! $botao ) {
	return;
}
?>
<section class="sobre-inter sobre-inter--compacta">
	<div class="apit-container sobre-inter__interior">
		<div class="sobre-inter__texto">
			<?php if ( $titulo ) : ?>
				<h2 class="sobre-inter__titulo"><?php echo esc_html( $titulo ); ?></h2>
			<?php endif; ?>

			<?php if ( $texto ) : ?>
				<p class="sobre-inter__descricao"><?php echo esc_html( $texto ); ?></p>
			<?php endif; ?>

			<?php if ( $botao ) : ?>
				<a class="btn btn--outline" href="<?php echo esc_url( $url ? $url : '#' ); ?>">
					<?php echo esc_html( $botao ); ?>
					<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
				</a>
			<?php endif; ?>
		</div>

		<div class="sobre-inter__marca">
			<img
				src="<?php echo esc_url( $marca ); ?>"
				alt="Watch Portugal — Independent TV Producers"
			>
		</div>
	</div>
</section>
