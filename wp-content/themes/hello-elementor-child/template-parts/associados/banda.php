<?php
/**
 * Associados — the full-bleed band that invites the visitor to the directory.
 *
 * It replaces the logo carousel that used to sit here. The carousel never
 * appeared on the site: its gallery field was empty, so the section returned
 * early and left an empty container behind. The design now puts a title, a line
 * of copy and a single button on the client's own artwork instead.
 *
 * The artwork is a theme asset rather than a media item, because it is part of
 * this block's design and not content someone chooses per page — the same call
 * the header's scrim makes. The words are fields, so they are edited in the
 * back office.
 *
 * The button needs both a label and a destination: with only one it either
 * says nothing or goes nowhere. Same rule as the calendar's event button.
 *
 * `pagina` lets a second page show this band without its words being typed
 * again — Sobre a APIT reads the ones written here. `url` overrides only the
 * destination, so the shared copy can still send the visitor somewhere that
 * makes sense from the page they are on.
 *
 * @var array $args pagina, url
 */
$fonte = (int) ( $args['pagina'] ?? 0 );
$fonte = $fonte > 0 ? $fonte : null;

$titulo = trim( (string) apit_campo( 'assoc_banda_titulo', $fonte ) );
$texto  = trim( (string) apit_campo( 'assoc_banda_texto', $fonte ) );
$botao  = trim( (string) apit_campo( 'assoc_banda_botao', $fonte ) );
$url    = trim( (string) ( $args['url'] ?? '' ) );

if ( '' === $url ) {
	$url = trim( (string) apit_campo( 'assoc_banda_url', $fonte ) );
}

if ( '' === $titulo && '' === $texto ) {
	return;
}
?>
<?php // The hero's "Ver associados" button points at #associados and, until now, at nothing. ?>
<section class="assoc-banda" id="associados">
	<div class="assoc-banda__col">
		<?php if ( $titulo ) : ?>
			<h2 class="assoc-banda__titulo"><?php echo esc_html( $titulo ); ?></h2>
		<?php endif; ?>

		<?php if ( $texto ) : ?>
			<p class="assoc-banda__texto"><?php echo nl2br( esc_html( $texto ) ); ?></p>
		<?php endif; ?>

		<?php if ( $botao && $url ) : ?>
			<a class="btn btn--outline assoc-banda__botao" href="<?php echo esc_url( $url ); ?>">
				<?php echo esc_html( $botao ); ?>
				<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
			</a>
		<?php endif; ?>
	</div>
</section>
