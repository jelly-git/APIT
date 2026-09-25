<?php
/**
 * O botão de voltar ao início da página.
 *
 * As páginas deste site são longas — a das Notícias passa dos cinco ecrãs — e
 * quem chega ao fim não tem como voltar ao menu sem arrastar tudo para trás.
 *
 * Sai no rodapé, uma vez, em todas as páginas: é comportamento do site e não
 * conteúdo de nenhuma, pelo que não há nada a colocar página a página — a mesma
 * razão por que a migalha deixou de ser um widget.
 *
 * É um `<button>` e não uma âncora para `#`: não leva a lado nenhum, faz uma
 * coisa. Uma âncora deixaria `#` na barra de endereço e um item no histórico.
 *
 * Nasce escondido pelo atributo `hidden`, que o tira também da árvore de
 * acessibilidade, e o JavaScript mostra-o depois de a página descer. Sem
 * JavaScript não aparece — o que é certo, porque sem JavaScript também não
 * faria nada.
 */

defined( 'ABSPATH' ) || exit;

function apit_voltar_ao_topo() {
	if ( is_admin() ) {
		return;
	}
	?>
	<button
		type="button"
		class="apit-topo"
		id="apit-topo"
		hidden
		aria-label="<?php esc_attr_e( 'Voltar ao início da página', 'apit' ); ?>"
	>
		<i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
	</button>
	<?php
}
add_action( 'wp_footer', 'apit_voltar_ao_topo' );
