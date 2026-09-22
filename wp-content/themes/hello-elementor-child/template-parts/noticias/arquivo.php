<?php
/**
 * The Notícias page: the frame around the list.
 *
 * Only the shell is here. Everything that changes when a category or a page is
 * chosen lives in resultados.php, which is also what the AJAX endpoint returns
 * — the script swaps the contents of the box below and nothing else on the page
 * moves.
 *
 * The filter and the pagination stay ordinary links, pointing at ?categoria=
 * and ?pg= on this same page. That is what the script intercepts; without it,
 * or if a request fails, they simply navigate and the page renders the same
 * list server-side.
 *
 * The three data attributes are what the script needs and nothing more: where
 * to ask, and which page's fields to render — there is no inline script and no
 * global to carry them.
 */
?>
<section class="noticias-arquivo apit-container"
	data-noticias
	data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	data-pagina="<?php echo (int) apit_noticias_pagina_id(); ?>">
	<?php
	/*
	 * aria-live so the change is announced: the visitor clicked a filter, the
	 * page did not reload, and a screen reader would otherwise have nothing to
	 * report. "polite" because it can wait for the current utterance to finish.
	 */
	?>
	<div class="noticias-arquivo__conteudo" data-noticias-conteudo aria-live="polite">
		<?php get_template_part( 'template-parts/noticias/resultados' ); ?>
	</div>
</section>
