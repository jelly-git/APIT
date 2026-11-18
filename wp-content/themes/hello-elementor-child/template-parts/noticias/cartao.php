<?php
/**
 * One news card in the archive grid.
 *
 * Wears the same .noticia classes as the cards on the Home so the category
 * label and the title keep one definition for the whole site, and adds the
 * pieces only the archive has: the date, the summary and the "ler" link.
 *
 * Só texto, sem imagem, haja ou não fotografia (a opção B apresentada à APIT em
 * novembro de 2026). Muitas notícias não têm fotografia, e a grelha mostrava-as
 * com degradés de cores diferentes, o ruído de que a APIT se queixou. Cada
 * cartão abre agora com uma linha fina na cor da categoria, a mesma da etiqueta
 * e do botão; a fotografia fica para o destaque, para a Home e para a notícia.
 *
 * $args: post, mostrar_data, mostrar_resumo, resumo_palavras, ler_mais.
 */
$post_cartao = $args['post'];
$categorias  = get_the_category( $post_cartao->ID );
$categoria   = $categorias ? $categorias[0]->name : '';
$resumo      = ! empty( $args['mostrar_resumo'] )
	? apit_noticia_resumo( $post_cartao, $args['resumo_palavras'] )
	: '';
?>
<article class="noticia noticia--cartao"
	style="<?php echo esc_attr( apit_cor_categoria_style( $categoria ) ); ?>">
	<a class="noticia__link" href="<?php echo esc_url( get_permalink( $post_cartao ) ); ?>">
		<span class="noticia__texto">
			<span class="noticia__meta">
				<?php if ( $categoria ) : ?>
					<span class="noticia__categoria"><?php echo esc_html( $categoria ); ?></span>
				<?php endif; ?>

				<?php if ( ! empty( $args['mostrar_data'] ) ) : ?>
					<time class="noticia__data" datetime="<?php echo esc_attr( get_the_date( 'c', $post_cartao ) ); ?>">
						<?php echo esc_html( get_the_date( '', $post_cartao ) ); ?>
					</time>
				<?php endif; ?>
			</span>

			<span class="noticia__title"><?php echo esc_html( get_the_title( $post_cartao ) ); ?></span>

			<?php if ( $resumo ) : ?>
				<span class="noticia__resumo"><?php echo esc_html( $resumo ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $args['ler_mais'] ) ) : ?>
				<span class="noticia__ler"><?php echo esc_html( $args['ler_mais'] ); ?></span>
			<?php endif; ?>
		</span>
	</a>
</article>
