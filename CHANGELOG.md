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
