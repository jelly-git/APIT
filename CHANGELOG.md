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
- Confirmar as cores por categoria com o design (foram propostas a partir da
  paleta da marca, por o Figma estar em limite de chamadas).
- Página "Sobre a APIT" (nó `19:20294`), incluindo o seu responsivo. O da Home
  está feito; falta o das páginas interiores.
- Confirmar o responsivo da Home contra o Figma mobile (nó `66:2060`): as
  medidas foram inferidas dos mockups, por o Figma estar em limite de chamadas.
- Ligar o formulário da newsletter a um serviço de envio (não tem handler).
- Tratamento do header em páginas sem hero colorido atrás dele: o menu é branco
  e desaparece sobre um fundo claro.
- Elementor Pro, caso se opte por usar (requer o `.zip` da licença).

## [0.18.1] - 2026-09-01

### Corrigido
- Degradé do hero a tingir a página toda em mobile, rodapé incluído. A camada
  de decoração passa a fluir abaixo dos 1400px, e para manter o degradé a
  cobrir a secção eu tinha-lhe posto `position: fixed` — que segue o scroll e
  cobre a janela inteira. Passou para a camada do vídeo, que é absoluta e
  cobre o hero em todos os breakpoints.
- Texto do hero encostado à margem do ecrã em mobile: os containers do
  Elementor não têm padding, e sem a largura do desktop não sobra margem que
  faça esse papel. Levam agora 20px laterais, como o resto das secções.
- Espaço morto no fim do carrossel em mobile: o alargamento até à margem do
  ecrã acrescenta largura para além do último cartão. Abaixo dos 768px o
  alargamento sai e o cartão fica mais estreito do que a coluna, o que mantém
  o cartão seguinte à vista sem deixar folga no fim.

## [0.18.0] - 2026-09-01

### Adicionado
- **Menu mobile** em painel de ecrã inteiro, com o degradé da marca: logótipo,
  pesquisa e fecho no topo, menu principal, botão de Área Reservada, menu
  secundário, seletor PT/EN e redes sociais — o conteúdo do mockup.
  Abre pelo hambúrguer e fecha pelo X, pela tecla Escape ou ao clicar num link.
  Bloqueia o scroll da página enquanto está aberto, move o foco para dentro do
  painel e devolve-o ao hambúrguer ao fechar. Fecha-se sozinho se a janela
  passar acima do breakpoint, para não deixar a página presa debaixo dele.
- `assets/js/menu-mobile.js`.

### Alterado
- **Hero responsivo**: abaixo dos 1400px o selo Watch Portugal e o painel de
  vídeo saem das posições absolutas e passam a fluir depois do texto, com as
  legendas a seguir — a ordem do mockup. Antes estavam simplesmente escondidos.
- As legendas do hero passaram a viver num container próprio
  (`.apit-hero__legendas`), em vez de dentro do bloco de texto. Sem isso não era
  possível intercalá-las: o design coloca o selo e o vídeo **entre** os botões e
  as legendas, e um único container não permite essa ordem.
- **Notícias em mobile**: o cartão de destaque perde o painel branco sobreposto
  e passa a empilhar imagem e texto, como os outros dois.
- **Newsletter em mobile**: Nome e Empresa mantêm-se emparelhados e só a linha
  de baixo é que empilha, conforme o mockup — a regra anterior punha os três
  campos em coluna.
- Cabeçalho abaixo dos 1024px: o menu e o botão de Área Reservada dão lugar ao
  hambúrguer, e o logótipo reduz para 130px.
- Logótipos do rodapé e o selo do hero passaram a ter `max-width: 100%`, para
  não transbordarem nos telefones mais estreitos.

### Corrigido
- Scroll horizontal em tablet e mobile: as legendas do hero mantinham o recuo de
  518px do desktop, o que empurrava a página 518px além da janela. Vem do
  `_margin` do widget, escrito três classes fundo no CSS da página, e precisa de
  `!important` para ser anulado.
- Ordem dos blocos do hero ignorada: um seletor de uma só classe perde para as
  regras de container do Elementor, pelo que o `order` tem de ser aplicado com o
  pai no seletor.
- Regra base do hambúrguer estava depois da media query que o mostra, e por isso
  vencia-a — o botão nunca aparecia.
- Legendas do hero encolhidas à largura do texto: o container do Elementor é
  flex e não estica os filhos, o que exige uma largura explícita.

## [0.17.0] - 2026-09-01

### Adicionado
- Camada de degradé por cima do vídeo do hero, como no design: unifica a secção
  e suaviza as formas do vídeo, que continuam visíveis através dela. Entra como
  `::before` da camada de decoração, pelo que fica acima do vídeo e abaixo do
  selo e do painel, que o design mantém nítidos.

### Notas
- Os valores do degradé são aproximados a partir da imagem do mockup, com a
  paleta da marca: quatro radiais para os focos de cor e um linear por baixo a
  preencher, a 55% de opacidade. O Figma continua no limite de chamadas MCP,
  pelo que os valores reais estão por confirmar — falta sobretudo presença do
  turquesa na zona central superior.

## [0.16.0] - 2026-09-01

### Corrigido
- Painel escuro do vídeo e selo "Watch Portugal" tapados pelo vídeo de fundo. A
  causa era a ordem no DOM: o vídeo entrava depois da camada de decoração, e um
  `<video>` é composto na sua própria camada, pintando por cima dos irmãos
  seguintes independentemente do `z-index` — que sozinho não resolveu. O media
  passou a ser o primeiro filho do hero, pelo que a ordem do DOM já basta e o
  `z-index` fica apenas como reforço.

### Removido
- Blobs desfocados do hero e os respetivos SVGs (`hero-ellipse-*.svg`,
  `hero-line.svg`). Eram a aproximação em CSS do fundo em degradé, que o vídeo
  agora substitui — por cima dele só enlameavam a imagem.

## [0.15.0] - 2026-09-01

### Adicionado
- Vídeo de fundo no hero, em vez do degradé: `assets/videos/homepage.mp4`, em
  autoplay silencioso e em ciclo. O degradé continua pintado por baixo, pelo que
  é o que se vê enquanto o vídeo carrega ou se falhar.
- Shortcode `[apit_hero_media]`, editável no Elementor, que aceita vídeo,
  imagem, ou os dois — com a imagem a servir de `poster`, de modo a ser sempre
  uma imagem a carregar primeiro. Atributos: `video`, `imagem`, `autoplay`,
  `loop`, `controls`.
- `apit_media_url()`, que resolve um ficheiro em `assets/`, um URL completo, um
  caminho absoluto ou um ID da biblioteca de multimédia. A resolução é feita em
  execução, para que nada fixe o URL do site — o que importa porque o site tem
  de mudar de domínio no deploy.

### Removido
- Marca de água "index" do hero.

### Corrigido
- `gap` predefinido de 20px nos containers do Elementor, que com os três filhos
  do hero somava 40px antes do texto. Com o gap a zero o padding pode ser os
  235px do design.
- Margem inferior que o Elementor dá a todos os widgets menos o último, que
  acrescentava altura às camadas invisíveis do hero.

## [0.14.1] - 2026-09-01

### Corrigido
- Fundo dos campos do formulário da newsletter para o valor do Figma,
  `rgba(72, 37, 95, 0.1)` — estavam transparentes.

## [0.14.0] - 2026-09-01

### Corrigido
- Fundo da secção APIT News: tinha uma base escura (`--apit-black`) com três
  degradés radiais saturados por cima. O design é um único varrimento pastel —
  rosa à esquerda, passando por violeta e um meio azul-acinzentado, até
  turquesa à direita. A base escura foi removida.
- Botão "Subscrever": estava na variante contornada usada no header, e no design
  é preenchido a magenta com texto branco.

### Notas
- O degradé foi aproximado a partir da imagem do mockup, com tonalidades
  clareadas da paleta da marca. O Figma continua no limite de chamadas MCP, pelo
  que os preenchimentos reais dos três retângulos (nó `19:19941`) estão por ler.

## [0.13.0] - 2026-09-01

### Corrigido
- **Header opaco**: tinha uma cor de fundo escura sólida, posta como reserva
  para páginas sem hero, que tapava o hero por completo. O header passa a ser
  transparente; o único fundo é o véu do Figma (nó `9:19483`), um degradé escuro
  que desvanece de cerca de 39% de opacidade no topo até zero em baixo, ali para
  manter o menu legível sobre a arte.
- **Botão "Quero ser associado"** aparecia azul e não transparente: o Elementor
  pinta os botões com a cor de acento do kit a partir do CSS da página, que
  carrega depois do tema e empata em especificidade.
- **Altura dos botões** 61px em vez de 57px, e o `<p>` dos widgets de texto
  trazia 14.4px de margem que esticava o subtítulo e subia os botões.
- **Margens de containers no Elementor**: os containers leem `margin` e
  `padding`, e só os widgets leem as chaves com underscore — com `_margin` o
  afastamento de 40px dos botões nunca chegou a aplicar-se.
- **Divisor do hero**: o widget traz 15px de padding acima e abaixo da régua e
  define a sua própria espessura e cor, o que a punha 18px abaixo do sítio, com
  1px e a preto em vez de 2px a branco 50%.
- **Ritmo vertical** de todas as secções alinhado ao frame do Figma: faixa do
  calendário 1143–1576 com a linha de topo em 1190 e os cartões em 1266,
  notícias 1647–2170, newsletter 2236–2608, e rodapé com o logótipo em 2677 e a
  régua em 2914. Cada secção carrega o intervalo acima de si no seu próprio
  padding.
- **Newsletter**: o conteúdo é recuado no design (coluna esquerda em x=402,
  direita a terminar em 1518, 1116px dentro dos 1300), o que é o que dá aos
  campos Nome e Empresa os 329px desenhados; e o botão passou de 57px para os
  48px do design.
- **Posição do painel de vídeo** e do logótipo Watch Portugal no rodapé, ambos
  desalinhados face ao design.

### Notas
- Verificado por medição contra as coordenadas do Figma: desvio médio de 1.8px
  em treze pontos de referência, altura de página 2986 contra 2984, e todas as
  cores conformes com as variáveis da marca.
- Páginas sem hero colorido atrás do header vão precisar de tratamento próprio:
  o menu é branco e desaparece sobre um fundo claro.

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
