<?php
/**
 * Internacionalização — a mesma banda, em versão baixa.
 *
 * A banda inteira ocupa meio ecrã no fim de páginas que já são longas. Esta diz
 * o mesmo em menos: o título, o botão e o logótipo, lado a lado numa só linha.
 *
 * **O parágrafo fica de fora**, e é daí que vem quase toda a altura poupada.
 * Quem chega ao fim das Notícias ou do Calendário já leu bastante; o que falta
 * ali é o convite e a porta, não a explicação — que continua inteira na banda
 * grande, nas páginas onde ela faz sentido.
 *
 * Os campos são os mesmos, lidos da Sobre a APIT como na versão grande: uma só
 * fonte, um só sítio para editar, e as duas versões nunca dizem coisas
 * diferentes.
 */
$origem = get_page_by_path( 'sobre-apit' );
$origem = $origem ? $origem->ID : null;

$titulo = trim( (string) apit_campo( 'sobre_inter_titulo', $origem ) );
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
