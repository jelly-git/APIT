# Changelog

Todas as alterações relevantes deste projeto são documentadas neste ficheiro.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o
projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).
A versão aqui registada corresponde ao campo `Version` de
`wp-content/themes/hello-elementor-child/style.css` e à constante
`APIT_CHILD_VERSION` em `functions.php`.

## [Não lançado]

### Por fazer
- Substituir a aproximação em CSS do gradiente do hero pelo asset real do
  Figma (nó `9:19574`) — o download esgotou o limite de chamadas MCP do plano.
- Substituir o selo "Watch Portugal" do hero (recriado em CSS) pelo artwork
  branco do Figma (nó `83:3098`).
- Confirmar as cores por categoria com o design (foram propostas a partir da
  paleta da marca, por o Figma estar em limite de chamadas).
- Página "Sobre a APIT" (nó `19:20294`) e layouts mobile (nó `66:643`).
- Menu hambúrguer: abaixo de 1024px o menu principal desaparece sem alternativa.
- Ligar o formulário da newsletter a um serviço de envio (não tem handler).
- Elementor Pro, caso se opte por usar (requer o `.zip` da licença).

## [0.12.0] - 2026-09-01

### Alterado
- Logótipos oficiais da marca em vez das recriações: o selo "Watch Portugal" do
  hero deixou de ser texto e pills em CSS e passa a ser o PNG branco (356×158,
  nó `83:3098`), e o rodapé usa o logótipo APIT a cor (250×141) e o "Watch
  Portugal" a preto (261×116).
- Os ficheiros foram copiados da biblioteca de multimédia para
  `assets/img/` do tema. O `.gitignore` exclui `wp-content/uploads`, pelo que
  ficheiros da biblioteca não seguiriam no deploy por git; nos assets do tema
  são versionados e viajam com ele.

### Removido
- `logo-apit-color.svg` e `logo-watch-portugal.svg`, exportações do Figma
  substituídas pelos ficheiros oficiais.
- CSS do selo recriado em pills, já sem uso.

## [0.11.0] - 2026-09-01

### Alterado
- Secção Notícias refeita: os três cartões têm estruturas diferentes no design,
  e estavam todos iguais (imagem com degradé escuro e texto branco por cima).
  Conforme o Figma:
  - **destaque** (nós 16:19927 + 16:19936): imagem de 855×523 com um painel
    branco de 496×225 sobreposto ao canto inferior esquerdo, com a categoria e
    o título em texto escuro;
  - **imagem** (nó 16:19929): imagem de 407×218 com a categoria e o título
    abaixo dela, sobre o fundo da secção;
  - **bloco** (nó 16:19935): bloco de 410×129 na cor da categoria, com o texto
    dentro dele a branco.
- A variante de cada cartão secundário vem do conteúdo: com imagem destacada
  fica "imagem", sem ela fica "bloco". Os que têm imagem aparecem primeiro,
  para dar a ordem do design (imagem em cima, bloco em baixo).
- Cor da categoria "setor" de roxo para turquesa, como no mockup.

### Adicionado
- Duas imagens de exemplo com degradés da marca, como imagens destacadas das
  notícias, para a secção poder ser avaliada antes de haver fotografia real.
  Substituíveis no wp-admin como qualquer imagem.

### Notas
- As etiquetas de categoria ficaram todas em magenta e o bloco em turquesa, lido
  da imagem do mockup — o Figma continua em limite de chamadas, pelo que estas
  duas cores estão por confirmar.

## [0.10.0] - 2026-09-01

### Adicionado
- Arrastar o carrossel com o rato, caneta ou dedo, com o cursor a mudar para
  `grabbing` e a lista a assentar na posição mais próxima ao largar. Um arrasto
  que termine sobre um cartão não segue o link.
- O carrossel estende-se até à margem do ecrã, deixando o cartão seguinte a
  espreitar — é o que sinaliza que há mais conteúdo.

### Corrigido
- Cartões cortados em cima e em baixo: `overflow-x: auto` obriga o
  `overflow-y` a recortar também, o que cortava a pill da categoria (5px acima
  do cartão) e a sombra da badge de data (4px abaixo). O track leva agora
  padding vertical para os acomodar, compensado por margem negativa.
- Movimento sem suavidade nas setas: o `scroll-snap-type: mandatory` refazia o
  snap a cada frame da animação, o que a transformava num salto. O snap passou
  a ser feito pelo JavaScript.
- Largura dos cartões: passou a ser calculada a partir da coluna de conteúdo e
  não do track, que é agora deliberadamente mais largo — de outro modo os
  cartões esticavam para 512px.
- Snap do arrasto a desfazer o gesto: como o track passa além da coluna, o
  scroll restante pode ser menor do que um cartão, e arredondar para um
  múltiplo da largura devolvia a lista a zero. As posições candidatas incluem
  agora o fim do track.

## [0.9.0] - 2026-09-01

### Adicionado
- Carrossel no Calendário: os cartões ficam todos na mesma linha, três visíveis
  de cada vez (nos 409px do design), e as setas deslocam a lista um cartão por
  clique, desativando-se em cada extremo. O Figma coloca um quarto cartão em
  x=1648, fora da coluna de 1300px, pelo que o transbordo é intencional.
- `assets/js/calendario.js` para conduzir o carrossel.

### Alterado
- O Calendário passou a mostrar até 12 eventos em vez de 4, já que o carrossel
  desloca a lista em vez de a limitar ao que caber numa vista.
- Dois cartões por vista abaixo de 1024px e um abaixo de 640px, mantendo o
  carrossel funcional.

### Notas
- A animação do carrossel é feita com `requestAnimationFrame` em vez de
  `scrollBy({ behavior: 'smooth' })`, que não é implementado por todos os
  motores — onde falta, o scroll não acontece de forma alguma. Como o
  `requestAnimationFrame` é suspenso em separadores escondidos, um temporizador
  de segurança coloca a lista no destino se nenhum frame chegar, e o estado das
  setas é atualizado por callback em vez de depender do evento `scroll`.

## [0.8.4] - 2026-09-01

### Corrigido
- Posicionamentos do cartão do calendário conferidos contra as coordenadas do
  Figma (nó `13:19650`) e agora a 1px do design em todos os elementos:
  - conteúdo alinhado ao topo (49px), não ao fundo;
  - título numa única linha, na caixa de 326px do design — antes o corpo estava
    limitado a `100% - 110px` para evitar a badge, o que o estreitava a 285px e
    o quebrava em duas linhas. A badge fica 159px abaixo, pelo que o título
    pode ocupar toda a largura;
  - largura do `.apit-container` de 1300 para 1340px, para que a coluna interior
    seja os 1300px do design e os cartões os 409px desenhados (eram 395px);
  - `line-height` do subtítulo de 1.4 para 1.68, a altura do design;
  - pill e badge nas medidas exactas (94×31 e 90×90);
  - pills empilhados na vertical com 13px de intervalo, como no Figma, em vez de
    lado a lado.

## [0.8.1] - 2026-09-01

### Corrigido
- Dia da data invisível nos cartões do calendário. O quadrado escuro deslocado
  era um `::before` com `z-index: -1`, mas a badge tem `z-index` próprio e por
  isso forma um contexto de empilhamento — nele, um filho com z-index negativo
  é pintado por cima do fundo branco do pai em vez de atrás, tapando-o e
  deixando o número escuro sobre fundo escuro. Passou a ser um `box-shadow`.

## [0.8.0] - 2026-09-01

### Adicionado
- Botão de ação opcional nos cartões do calendário (ex. "Marcar reunião"), com
  texto e link definidos por evento nos campos `apit_evento_acao_texto` e
  `apit_evento_acao_url`. Aparece só nos eventos que o tenham preenchido.
- Pill de localização com ícone de marcador, do campo `apit_evento_local`.

### Alterado
- Cartões do calendário refeitos a partir das imagens do mockup: fundo em
  degradé da cor da categoria para cinza claro, título e subtítulo brancos,
  pill da categoria sobreposta ao topo numa tonalidade mais escura, e badge de
  data encostada ao canto inferior direito sobre um quadrado escuro deslocado
  4px (como o Figma empilha Rectangle 7 atrás de Rectangle 6).
- Categorias de evento passaram a ser as do mockup — "Evento APIT" (turquesa),
  "Evento Internacional" (magenta) e "Stand APIT" (azul).
- Campos do evento reestruturados: `apit_evento_etiqueta` e
  `apit_evento_extra` deram lugar a `apit_evento_local`,
  `apit_evento_acao_texto` e `apit_evento_acao_url`.

### Corrigido
- `esc_url_raw` não pode ser usado diretamente como `sanitize_callback` de
  `register_post_meta`: recebe a chave da meta no segundo parâmetro, lê-a como
  a lista de `$protocols` e rejeita todos os URLs. Fica embrulhado numa closure.
- Lazy-load do Elementor a anular os degradés dos cartões: além do 4.º
  container, a regra desce para o 2.º em janelas com menos de 640px de altura.
  Todos os containers da Home levam agora `e-no-lazyload`.

## [0.7.0] - 2026-09-01

### Adicionado
- Cor por categoria: cada categoria tem uma cor da marca, que pinta a tira do
  mês e a pill nos cartões do calendário e tinge o degradé dos cartões de
  notícias. O mapa está em `inc/categoria-cores.php` e pode ser alterado sem
  editar o tema, pelo filtro `apit_categoria_cores`.
- Degradé de fundo na secção APIT News (o design empilha três retângulos de
  largura total no nó `19:19941`).

### Alterado
- Fundos do Calendário e das Notícias trocados: o Calendário passa a branco e
  as Notícias a cinza, como no mockup.
- Botão "Subscrever" com o mesmo desenho em pílula dos restantes botões.
- Legenda do vídeo no hero reposicionada para as coordenadas do Figma
  (x=828, y=904), e altura do hero alinhada com o frame (1143px).

### Corrigido
- Ordem de carregamento do CSS: o `style.css` do tema filho passou a depender
  dos handles do tema pai, porque carregava antes do `reset.css` e perdia todos
  os empates de especificidade — era o que impunha `border-radius: 3px` aos
  botões.
- Margens no Elementor: o `_margin` do widget sobrepõe-se ao CSS do tema, pelo
  que o deslocamento horizontal da legenda tem de ser definido no Elementor.
- Padding predefinido de 10px nos containers do Elementor, que desalinhava
  verticalmente todo o hero em 20px.
- Degradé da newsletter apagado pelo lazy-load do Elementor, que anula imagens
  de fundo a partir do 4.º container de topo (e nos seus descendentes). O
  container leva agora a classe `e-no-lazyload`.

## [0.6.0] - 2026-09-01

### Adicionado
- Página "Home" construída como página do Elementor, editável no editor visual.
  O título, subtítulo e botões do hero são widgets nativos; o gradiente e a
  decoração vêm do tema.
- Cores e tipografia globais do Elementor definidas com a paleta da marca
  (magenta, azul, black) e a fonte Omnes, editáveis em Definições do Site.
- Shortcodes `[apit_hero_decor]`, `[apit_calendario]`, `[apit_noticias]` e
  `[apit_newsletter]`, que expõem as secções do tema ao editor do Elementor.
  O Elementor gratuito não tem widgets Posts nem Form, por isso as secções
  dinâmicas são renderizadas pelo tema.
- Tipo de conteúdo "Evento" com data, categoria e etiquetas, a alimentar a
  secção Calendário. Três eventos de exemplo criados.
- Secção Notícias com um destaque grande e dois cartões secundários. O destaque
  é o post marcado como fixo (sticky), escolhido por um editor em vez de ser
  sempre o mais recente. Três notícias de exemplo criadas.
- Secção Newsletter com campos Nome, Empresa, Email e consentimento de RGPD.

### Alterado
- A Home passou a ser uma página estática do WordPress em vez do template
  `front-page.php`, que tinha precedência sobre qualquer página e por isso
  impedia a edição no Elementor. `front-page.php` e `hero-home.php` removidos.
- A página "Notícias" passou a ser a página de posts do blogue.

### Corrigido
- Nome da fonte: o kit Typekit serve a família como `omnes-pro`, não `omnes`,
  pelo que os títulos caíam para Roboto.
- Classes CSS no Elementor: containers usam a chave `css_classes`, widgets usam
  `_css_classes` — usar a chave errada fazia as classes não aparecerem.
- Botões do Elementor: as classes `btn` ficam no invólucro do widget, pelo que o
  estilo tem de ser aplicado ao `<a class="elementor-button">` interior.

## [0.5.0] - 2026-08-31

### Adicionado
- Secções Calendário, Notícias e Newsletter da Home (em template parts).
- Legenda do vídeo no hero.

## [0.4.0] - 2026-08-31

### Adicionado
- Este CHANGELOG.md, para documentar as alterações por versão.

### Alterado
- Versão do tema filho alinhada com o versionamento do projeto (0.4.0).

## [0.3.0] - 2026-08-31

### Adicionado
- Tema filho `hello-elementor-child`, codificado a partir do design em Figma.
- Header (nó `9:19484`): barra superior com menu secundário, seletor de idioma
  e redes sociais; barra principal com logótipo, menu, pesquisa e botão de
  Área Reservada.
- Footer (nó `19:21011`): logótipo APIT, links, contactos, redes sociais,
  logótipo Watch Portugal e barra legal.
- Hero da Home (nó `9:19573`): título, subtítulo, botões de ação e blobs
  decorativos.
- Fonte Omnes servida pelo kit Typekit e Font Awesome Free a substituir o
  conjunto de ícones Pro do design.
- Links das redes sociais editáveis no Personalizador (secção "APIT — Redes
  Sociais"), sem necessidade de editar código.
- 9 páginas e 3 menus (topo, principal, rodapé) criados via WP-CLI.

### Corrigido
- Classes com prefixo `apit-`: o tema pai hello-elementor estiliza
  `.site-header`/`.site-footer` e estava a limitar-lhes a largura a 1140px e
  a colapsar o logótipo.
- Posicionamento dos blobs do hero: os SVGs exportados incluem 400px de
  padding do desfoque gaussiano em cada bordo, pelo que precisam de um
  desvio de -400px face à posição desenhada.

### Notas
- O gradiente de fundo do hero é uma aproximação em CSS (ver "Não lançado").

## [0.2.0] - 2026-08-31

### Alterado
- Temas e plugins de terceiros excluídos do versionamento. O Elementor e o
  Hello Elementor são instalados via WP-CLI e reinstalados da mesma forma no
  deploy, por isso não precisam de viver no repositório.

## [0.1.0] - 2026-08-31

### Adicionado
- Estrutura inicial do repositório com o `.gitignore` do projeto WordPress:
  exclui o core do WP, os temas predefinidos, a pasta de uploads e o
  `wp-config.php` (contém credenciais).
