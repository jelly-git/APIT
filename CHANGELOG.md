# Changelog

Todas as alterações relevantes deste projeto são documentadas neste ficheiro.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o
projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).
A versão aqui registada corresponde ao campo `Version` de
`wp-content/themes/hello-elementor-child/style.css` e à constante
`APIT_CHILD_VERSION` em `functions.php`.

## [Não lançado]


### Adicionado
- **A notícia individual** (`single.php`), desenhada a partir do que o tema já
  tem: o hero das outras páginas com o degradé das Notícias e a imagem de
  destaque do artigo por trás, wordmark `NOTÍ CIAS`, migalha até à categoria,
  título, etiqueta da categoria na cor dela e data; o corpo; as ligações à
  notícia anterior e à seguinte com o botão de voltar ao meio; uma fila de
  outras notícias; e o bloco que a fecha.
  - **O que é do Elementor ficou no Elementor.** O grátis não tem Theme
    Builder, pelo que o que rodeia o artigo tem de ser um template do tema — e
    por isso o tema só desenha o que é mesmo de cada notícia. O corpo é
    `the_content()`: uma notícia aberta com "Editar com Elementor" é feita dos
    widgets que o editor quiser. E o que fecha o artigo é um **Saved Template**,
    "Rodapé das notícias" (banda da Internacionalização + bloco duplo +
    newsletter), lido ao vivo pelo `[apit_template]`: editá-lo uma vez muda
    todas as notícias.
  - A fila de outras notícias usa o **cartão do arquivo**, sem uma linha de
    markup nova: as da mesma categoria primeiro e as mais recentes a completar,
    porque uma fila de dois ao lado de um vazio lê-se como coisa que falhou.
  - Seis campos novos no separador *Notícia individual* da página das Notícias:
    texto do botão de voltar, mostrar a anterior/seguinte, mostrar as outras
    notícias, quantas, a etiqueta dessa fila, e **qual o template que fecha a
    notícia** — um seletor dos modelos guardados, para o cliente trocar o bloco
    sem tocar em código.
  - A capa leva um véu escuro por baixo do degradé do hero: uma fotografia é um
    fundo mais atarefado do que um degradé, e o título é branco.
- **Página principal das Notícias** (`/noticias/`), no molde das outras quatro:
  hero partilhado com o degradé próprio `hero--noticias` (azul → roxo →
  magenta, ao contrário do Calendário, para duas páginas vizinhas no menu não
  abrirem no mesmo quadro), wordmark `NOTÍ CIAS`, e a primeira secção a subir
  para dentro do hero pela `apit-sobrepoe` já existente.
- Secção do arquivo — `[apit_noticias_arquivo]`: cartão de destaque, filtros por
  categoria, grelha paginada e o mesmo bloco duplo e newsletter das outras
  páginas. Está dividida em duas: `arquivo.php` é a moldura que não muda — o
  destaque, a etiqueta da lista e a barra de filtros — e `resultados.php` é só a
  grelha e a paginação, que é o que o AJAX troca. Os cartões são os da Home com data, resumo e link acrescentados,
  pelo que a etiqueta de categoria e o título continuam com uma só definição.
- **Filtro e paginação sem recarregar a página.** O clique num filtro ou num
  número troca só a lista, por `fetch` a um endpoint no `admin-ajax` que devolve
  o mesmo `template-parts/noticias/resultados.php` que a página imprime — uma
  só fonte para os cartões. O endereço acompanha (`history.pushState`), pelo que
  recuar, avançar, partilhar ou recarregar continuam a dar a mesma lista.
  - *Progressive enhancement*: os filtros e a paginação continuam a ser links
    verdadeiros para `?categoria=` e `?pg=`. Sem JavaScript, ou se o pedido
    falhar, o clique navega e o servidor devolve a mesma página.
  - O endpoint valida o `pagina` que recebe — página publicada, ou 400 — para
    não renderizar o bloco com os campos de outro post qualquer. Sem nonce: lê
    artigos publicados e não escreve nada.
  - Uma resposta que chegue atrasada, depois de outro clique, é deitada fora em
    vez de escrever por cima da categoria mais recente.
  - `aria-live="polite"` na lista e o foco devolvido ao filtro ou ao número que
    ficou activo — sem isso, a substituição do HTML deixava quem navega por
    teclado no `body`.
  - **Sem *flash* nas imagens.** O HTML que vem a caminho é lido fora do
    documento, as capas que traz são carregadas e descodificadas, e só depois a
    lista é trocada — as `background-image` dos cartões, postas a direito no
    DOM, só eram pedidas ao servidor depois de pintadas, e durante um instante
    cada cartão mostrava a cor de fundo em vez da capa. Uma imagem em falta ou
    lenta não trava nada: ao fim de 1,5s a lista entra com o que houver.
  - **A página fica onde estava.** Trocar de filtro já não desloca nada: a barra
    de filtros é medida antes e reposta à mesma altura na janela depois, o que
    também absorve a mudança de altura do que está acima dela. Só a paginação
    reenquadra, e apenas quando a grelha ficou acima da janela — é o caso de
    quem carrega no "seguinte" a partir do fundo. A correcção é `instant`,
    porque o tema declara `scroll-behavior: smooth` e um `scrollBy` normal
    herdava-o: via-se a acontecer, que era o safanão que ela evita.
- Grupo de campos **Notícias — página** (21 campos, em quatro separadores). Não
  há texto nesta página que não seja editável: mostrar ou não o destaque e qual
  a notícia que o ocupa, as etiquetas das duas secções, notícias por página,
  colunas em desktop, data, resumo e o seu número de palavras, o texto do link
  do cartão, o texto de lista vazia, os filtros e as categorias que mostram, e
  a paginação com os dois rótulos. A migalha, o título e a introdução do hero
  ficam no Elementor, como nas outras páginas.
- **Campo "Notícia em destaque"**, na barra lateral do artigo
  (`group_noticia_destaque`). A mais recente das notícias com a opção ligada é a
  que ocupa a área de destaque. Podem ficar várias ligadas: marcar a de hoje
  chega, não é preciso desmarcar a de ontem.
  - **O destaque não acompanha o filtro**: é o mesmo em toda a página, e só a
    lista por baixo dele é que estreita. O lugar é da página e não da lista —
    quem marca uma notícia espera encontrá-la ali, e não dentro de um filtro.
    Continua a sair da grelha em todas as páginas da lista, para não aparecer
    duas vezes.
  - **E não é redesenhado.** O cartão passou para fora da caixa que o AJAX
    troca (`arquivo.php`, e não `resultados.php`): como não depende do filtro,
    voltar a imprimi-lo a cada clique era substituir um cartão igual e obrigar o
    browser a pintar a imagem outra vez — era isso que se via como a área de
    destaque a recarregar. Da segunda página da lista em diante é escondido com
    o atributo `hidden`, nunca removido, pelo que o elemento é o mesmo do
    princípio ao fim. Os dois templates concordam sobre qual é a notícia por
    `apit_noticias_destaque_da_pagina()`, com cache por pedido.
  - **A etiqueta da lista e a barra de filtros também ficam**, pela mesma razão:
    nenhuma delas depende do que está a ser listado. O que muda é qual a pílula
    marcada, e isso o script faz no sítio — classe e `aria-current` — logo no
    clique, antes mesmo da resposta chegar. Redesenhar a barra piscava a linha
    toda e tirava o foco a quem tinha acabado de carregar nela.
  - Substitui o *Fixar no topo do blogue* que fazia este trabalho, **também na
    Home**: é o mesmo campo e a mesma regra nos dois sítios, um interruptor só.
    O *sticky* é uma funcionalidade do índice do blogue, com efeitos próprios
    nas consultas, e quem o lê no editor não tem como adivinhar que significa o
    cartão grande de uma página.
  - O que estava fixado no topo ficou com o campo ligado, para o site continuar
    a mostrar a mesma notícia em destaque depois da troca.
- A **banda da Internacionalização** passa a fechar também a página das
  Notícias, logo a seguir à grelha e antes do bloco duplo — é o mesmo bloco das
  outras três páginas, com a cópia lida sempre da Sobre a APIT, e a sua folha de
  estilo passa a carregar aqui também.
- A lista abre com **12 artigos por página** (era 9) em três colunas, ambos
  campos no painel da página. A paginação aparece a partir do 13.º artigo
  publicado.
- **Cor por categoria de notícia**, campo na própria categoria
  (`group_categoria_noticia`). Pinta a etiqueta, o filtro seleccionado e o
  cartão sem imagem. Em branco, a categoria mantém a cor que o tema lhe dá.
- A página das Notícias entra no grupo **Hero — fundo**: o vídeo, a imagem ou a
  galeria do seu hero escolhem-se no back office como nas restantes.

### Corrigido
- **As ligações internas voltam a ser guardadas como caminhos**, que é o que o
  filtro de `inc/links.php` espera. Tinham sido construídos dois mecanismos para
  o mesmo problema: o filtro, que prefixa os `href` e `src` com `/apit` na saída
  da página, e uma conversão de tudo para endereços completos, que dependia do
  `search-replace` da exportação. Os dois funcionam, mas não convivem — e o
  segundo escrevia o domínio dentro das páginas, ao ponto de a banda dos
  Associados na Sobre a APIT ficar com
  `url="http://apit.local/associados/"` no shortcode, a ler ao contrário de
  todos os outros. Ficou o filtro: 28 ligações voltaram a caminho, nenhuma parte
  dos dados nomeia um domínio, e mudar de alojamento deixa de pedir uma
  passagem de substituição.
  - Duas provas passam a viver no repositório, em `tools/`, porque nada disto se
    vê no local — o site está na raiz do domínio, o caminho é vazio e o filtro
    sai logo. A `prova-prefixo.php` corre os catorze casos do filtro com o
    `home_url` a fingir a subpasta, incluindo o `/apitico/`, que começa por
    "apit" e não é a subpasta. A `prova-servidor.php` volta a passar o HTML
    servido de cada página pelo filtro e lista o que um visitante em `/apit`
    clicaria: 11 ligações internas, todas sob `/apit`.
- **A banda da Internacionalização tinha uma faixa acima e abaixo**, da cor da
  secção, a cortá-la do que ficava à volta. Eram os 10px de padding que o
  Elementor dá a qualquer contentor: a `apit-sangra` só zerava os horizontais.
  Passa a zerar os quatro — um bloco que sangra, sangra. Vale para a banda e
  para o bloco duplo, nas páginas todas que os têm.
- **E a banda nunca chegava a pintar o degradé.** Em ecrãs com 1024px de altura
  ou menos, o Elementor apaga o `background-image` de cada contentor a partir do
  terceiro — e dos seus descendentes — até o marcar como `e-lazyloaded` quando
  entra no ecrã. Nesta instalação esse marco nunca chega: percorri a página toda
  e não há um único `.e-lazyloaded`. A banda ficava um vazio de 505px com o
  texto e o logótipo a flutuar no branco, o que explica o padding que parecia
  estar lá. Os contentores da banda no Calendário, nos Documentos e nas Notícias
  passam a levar `e-no-lazyload`, que é a saída oficial e já era o que o hero e a
  newsletter usavam pela mesma razão.
- **O degradé de transparente para branco faltava no hero das Notícias.** Ele
  vivia na camada de média, e essa camada só existe quando há vídeo ou imagem no
  grupo *Hero — fundo* — nas Notícias não há nenhum, pelo que o hero acabava a
  direito contra a secção seguinte. Passou para a própria secção
  (`.apit-pagina-hero::after`, a z-index 1: por cima da arte e da média, por
  baixo do conteúdo e do wordmark, que é exactamente onde estava). As páginas
  que já o tinham ficam iguais, a camada de média deixa de pintar o seu, e a
  Associados continua a desligá-lo pela `--apit-hero-scrim: none`.
- **O botão do cartão colava-se ao texto** no cartão mais alto de cada linha: o
  `margin-top: auto` que alinha os botões não tem folga nenhuma para recolher aí.
  Passa a haver um mínimo de 26px acima dele, ao qual o `auto` só acrescenta.
  Aplicado ao que estiver imediatamente antes do botão, porque uma notícia sem
  resumo tem lá o título — que é o caso das três de demonstração, sem corpo de
  texto, e foi assim que se viu.
- **Os cartões de uma linha passam a ter todos a altura do maior**, como os dos
  Documentos. A grelha já esticava o `<article>`, mas a cadeia parava aí: o
  artigo passa a coluna para a ligação o preencher, e a ligação a coluna para o
  bloco de texto crescer — sem isso o botão seguia o seu próprio título para
  cima e para baixo. Medido: dois cartões a 413px e os dois botões a 1587px.
- A paginação levava consigo os parâmetros do pedido AJAX
  (`?pg=1&action=apit_noticias&pagina=9` na barra de endereço). O
  `paginate_links()` não constrói só a partir da base que recebe: lê também o
  `get_pagenum_link()` — o endereço que está a ser servido — e acrescenta a cada
  ligação os parâmetros que lá encontrar. Em AJAX esse endereço é o
  `admin-ajax.php`. Durante a chamada passa a receber a permalink limpa da
  página, e é removida a seguir.

### Alterado
- **Etiqueta de secção** (`.apit-secao__etiqueta`) passa aos valores dados pelo
  cliente: Omnes Medium 20px, altura de linha 110%, tracking 0.4px. Substitui os
  18px/300 com 35% de tracking da v0.26.8 — a essa distância a linha lia-se como
  capitais soltas e não como palavra. Muda nas cinco páginas, e tanto nas
  etiquetas que o template imprime em `h2` como nas que imprime em `p`: é a
  mesma classe, e uma regra só para os títulos deixaria o site com dois estilos
  de etiqueta.
- O **"Ler notícia"** do cartão passa a ser o botão dos Documentos: a mesma
  pílula de contorno 2px, o mesmo rótulo em maiúsculas a 12px e o mesmo
  preenchimento no hover. Um só controlo no site, e quem já esteve nos
  Documentos reconhece-o. Muda a cor — a da categoria do cartão, e não o azul
  fixo, porque nesta página tudo o que pertence a uma categoria é tingido por
  ela; o azul fica como reserva, que é a cor que o botão dos Documentos tem de
  qualquer maneira. E muda o ícone: um jornal (`assets/img/icon-noticia.svg`),
  **à direita do rótulo** — o dos Documentos leva a seta à esquerda —, desenhado
  como máscara pintada com `currentColor` tal como ela, para seguir o rótulo no
  hover sem um segundo ficheiro.
- Uma notícia **sem imagem de destaque** deixa de aparecer como um rectângulo
  quase preto: fica com a cor da sua categoria, como já acontecia no cartão
  "bloco" da Home. Vale para o cartão grande e para os da grelha, nas duas
  páginas — a regra está no `style.css`, e não na folha só das Notícias.
- `apit_cor_categoria()` passa a resolver a categoria pelo termo e a ler
  primeiro o campo de cor. **Corrige "Mercados & Feiras"**, que saía magenta na
  Home: o nome impresso sanitiza para `mercados-amp-feiras` e não coincidia com
  a chave da tabela nem com o slug do termo.
- `paginas.css` passa a servir cinco páginas; a folha nova `noticias.css` traz
  só o que não existe em mais lado nenhum — filtros, grelha, resumo e
  paginação.

### Notas
- **Categorias das notícias**: *Institucional* (magenta), *Mercados & Feiras*
  (azul) e *Eventos* (turquesa), com a cor no campo da própria categoria. O
  filtro está fixado nestas três, por esta ordem, pelo campo *Categorias a
  mostrar* — a lista automática esconde as que ainda não têm notícias.
  *Mercados & Feiras* é a categoria que já existia, reaproveitada em vez de
  criada de novo. *Setor* foi apagada, já sem artigos.
- **Os três artigos de demonstração foram distribuídos** pelas três categorias,
  um em cada. Enquanto estiveram todos na mesma, o destaque parecia não
  responder ao filtro — e não respondia mesmo: não havia outra notícia para
  mostrar. É conteúdo de exemplo; a categoria de cada notícia é do cliente, no
  editor.
- **`page_for_posts` deixou de ser a página Notícias** (Definições › Leitura,
  "Página de artigos" agora vazia). Enquanto era, o WordPress servia `/noticias/`
  pelo `index.php` do tema pai e ignorava tudo o que a página tivesse — hero
  incluído. É uma opção da base de dados: viaja na exportação, e num servidor
  onde a base de dados seja mais antiga tem de se desmarcar à mão.
- A paginação e o filtro viajam em `?pg=` e `?categoria=`, não em segmentos do
  caminho: numa página estática o `/2/` é a variável `page` do WordPress, feita
  para o `<!--nextpage-->`, e o `redirect_canonical` devolve-a à página 1.
- As páginas de notícia individual e os arquivos de categoria continuam com o
  aspecto por omissão do tema pai — não estavam no desenho desta passagem.

### Por fazer
- Substituir a aproximação em CSS do gradiente do hero pelo asset real do
  Figma (nó `9:19574`) — o download esgotou o limite de chamadas MCP do plano.
- Confirmar as cores das categorias de notícias com o design (foram propostas
  a partir da paleta da marca, por o Figma estar em limite de chamadas). Já não
  é preciso mexer em código para as trocar: são um campo na categoria, como nas
  dos eventos — o que está no tema é só o ponto de partida.
- Confirmar o responsivo da Home contra o Figma mobile (nó `66:2060`): as
  medidas foram inferidas dos mockups, por o Figma estar em limite de chamadas.
- Confirmar as medidas da "Sobre a APIT" contra o Figma (nó `44:21610`): foram
  derivadas do screenshot do cliente à escala do design (1920px), com o limite
  de chamadas MCP ainda ativo.
- Página "Área Reservada": ainda não é para criar (indicação do cliente). Os
  botões que lhe apontam ficam em `#` até existir.
- PDF do "Regulamento Interno": não está na multimédia. Carregar no campo
  Ficheiro dessa linha, em Sobre a APIT › Documentos; até lá o botão aponta
  para `/documentos/`.
- Efeito "Scanline warp" do Figma: está aproximado em CSS no wordmark "About
  Us" e usa o asset real (`bola4`) nos círculos. Se a aproximação do wordmark
  não servir, exportar a palavra do Figma como PNG.
- Secção "Associados" ficou só com rótulo e botão, por indicação do cliente
  (sem os logos). Confirmar se quer um parágrafo de apoio — o campo Texto, em
  Sobre a APIT › Associados, está lá e vazio.
- Ligar o formulário da newsletter a um serviço de envio (não tem handler).
- Tratamento do header em páginas sem hero colorido atrás dele: o menu é branco
  e desaparece sobre um fundo claro.
- Elementor Pro, caso se opte por usar (requer o `.zip` da licença).

## [0.33.0] - 2026-09-24

### Adicionado
- **A página Media Kit**, que estava publicada e vazia, ligada no menu principal
  e no rodapé desde o início. Montada com as peças das outras quatro: hero com
  fundo do back office, wordmark, migalha e título; uma secção própria; a banda
  da Internacionalização; o bloco duplo #167; e a newsletter a fechar.
  - **Os textos são widgets do Elementor, não campos nem código.** O cliente
    ainda não disse o que esta página é — se é material de imprensa, se é a
    "Seja nosso Associado" do site antigo — por isso tudo o que lá está
    reescreve-se no editor sem tocar em PHP.
  - O hero leva um degradé roxo-laranja, o par da paleta que nenhuma das outras
    sete páginas usa. A página entrou no grupo ACF **Hero — fundo**, para poder
    escolher vídeo ou galeria como as restantes.
- **O formulário da ficha de inscrição**, em Gravity Forms: nome, empresa, email
  e consentimento. A confirmação toma o lugar do formulário e mostra um botão
  que abre o PDF num separador novo.
  - **O botão e não uma abertura automática**, porque o browser bloqueia um
    `window.open` que não venha de um clique. Com o botão, o separador abre
    sempre, e abre por acção de quem o pediu.
  - O endereço do PDF fica como caminho, sem domínio. Um URL completo ficaria
    guardado dentro do JSON das definições do formulário, que é o género de
    sítio onde o `search-replace` da exportação já falhou uma vez.
  - O formulário veste-se pelas **variáveis do tema "orbital"** do plugin e não
    por selectores: é o vocabulário público dele, e sobrevive a uma
    actualização. Os tamanhos têm de ser os tokens da variante (`-md`) — o
    plugin escreve `--gf-ctrl-btn-size: var(--gf-ctrl-btn-size-md)` no próprio
    botão, e isso ganha ao que se declare no invólucro.
- Os dois botões **"Descarregar ficha de inscrição"** da página Associados
  apontavam para `#` e não faziam nada. Passam a levar a esta página.

### Corrigido
- **Um título feito no editor saía magenta.** O Elementor escreve, na folha por
  página, que todo o `.elementor-heading-title` leva a cor "Primária" do kit —
  que aqui é o magenta — e essa folha é impressa depois da do tema. Nunca se
  tinha visto porque todos os títulos até agora vinham de templates do tema. As
  cores desta página levam `!important`, e a etiqueta de secção ganhou uma regra
  que serve qualquer etiqueta posta no editor de futuro.

## [0.32.1] - 2026-09-23

### Corrigido
- **Links internos no servidor.** O site está em `dev.jellycode.agency/apit/`, e os
  links guardados como caminho — `/associados/todos-os-associados/`,
  `/contactos/`, `/calendario/`, o `/` das migalhas — levavam à raiz do
  domínio, fora do WordPress: o 404 do próprio Apache. Eram 27, no Elementor
  (19), nos campos de link ACF (6) e no menu (2), em quase todas as páginas.
  - Corrigido à saída, em `inc/links.php`, e não nos dados: quando o WordPress
    está numa subpasta, os `href` e `src` começados por um só `/` recebem o
    caminho do site. Os dados continuam relativos — e portáveis, que é a razão
    de o serem —, e um link acrescentado amanhã no painel do Elementor fica
    coberto sem ninguém saber disto.
  - Não toca nos que já trazem `/apit/` (os que o tema constrói com
    `home_url()`), nos absolutos, nos `//cdn`, nas âncoras nem em atributos
    `data-`. Na raiz de um domínio — o site local — não faz nada.
  - Fora do wp-admin, do editor do Elementor, de feeds, REST e AJAX.

## [0.32.0] - 2026-09-23

### Alterado
- **Internacionalização conforme o design, e em widgets do Elementor.** As três
  secções abaixo do hero deixam de ser shortcodes com campos ACF e passam a ser
  widgets nativos — Imagem, Título, Editor de texto, Botão e Lista de ícones —
  editados no painel do Elementor; o tema só dá a tipografia e a geometria.
  - **Watch Portugal**: o logótipo volta a aparecer (o campo estava vazio), agora
    o ficheiro do cliente de 738px, `whach-portugal-logo-preto-738px.png`
    (media ID 449), a 734px como no design — nítido, onde o de 261px do tema
    ficava esbatido ao ser ampliado. A
    secção sobe para dentro do esbatimento do hero pela `apit-sobrepoe`, com o
    logótipo a começar nos 690px. Título a 48px e parágrafo recuado 76px.
  - **Como apoiamos os associados**: os quatro cartões ficam dentro da coluna de
    1300px, em vez de irem de ponta a ponta do ecrã; título a 30px com a quebra
    do design; e o degradé de cada cartão, da cor para cinza, é o fundo em
    degradé do próprio contentor no Elementor — mudar a cor não precisa de código.
  - **Área Reservada** em vez de "Reserve a sua mesa no stand": título, texto e
    botão à esquerda, "Serviços disponíveis" com a lista de vistos à direita,
    sobre a arte do cliente, `fundo-bloco-area-reservada.png` (media ID 447),
    posta como imagem de fundo do contentor no Elementor — troca-se no painel.
    O CSS (`.apit-banda-area`) só dá o magenta que aparece enquanto carrega.
  - **Hero**: o WORLD fica à altura do título (topo nos 262px) e já não por baixo
    dos botões; e o esbatimento desta página começa nos 540px, para os botões
    brancos não se apagarem no claro.
  - **Próximos mercados**: a secção passa a ter o id `mercados`, o destino que o
    botão "Ver próximos mercados" do hero já apontava e não existia; e o bloco
    do calendário deixa de pintar uma caixa branca dentro da faixa clara.
  - **Cabeçalho do calendário**: as setas ficam centradas com o título e com o
    botão (`.calendario__nav` com `align-items: center` — com o botão de 57px
    ao lado, as setas de 40px encostavam-se ao topo); e o "Calendário completo"
    passa a `btn--escuro`, porque o contorno branco num fundo claro só se via
    no hover. É a única página que mostra esse botão.
  - Entre 1025px e 1920px o WORLD escala com o ecrã (`min(280px, 14.6vw)`) em
    vez de passar por cima do título, e o logótipo Watch Portugal ocupa a
    coluna — encolhia para os 261px do ficheiro. A posição do WORLD e o
    esbatimento mais baixo passam a valer desde os 1025px: só começavam nos
    1201px, e entre os dois o WORLD voltava para baixo e os botões do hero
    apagavam-se no claro.

- **Hover de todos os botões**, com um sistema só. Até aqui só o contorno escuro
  e o botão da newsletter tinham hover: os botões do Elementor não tinham
  nenhum, e os que os templates imprimem como `<a class="btn">` apanhavam o
  reset do Hello, `a:hover { color: #336 }`, que punha o texto azul-marinho
  sobre as bandas de cor.
  - **Sólidos** (azul, magenta, turquesa) enchem de preto `--apit-black`, como
    já fazia o botão da newsletter.
  - **Contorno branco**, sobre cor, enche de branco com o texto escuro — também
    o "Área Reservada" do cabeçalho.
  - **Contorno escuro** enche de preto (já existia).
  - Transição de 0,2s, e o mesmo estado no foco por teclado (`:focus-visible`).
  - O reset do Hello pinta ainda todo o `button:hover` e `button:focus` de
    `#c36`: o hambúrguer e o fechar do menu móvel ficavam com fundo rosa no
    hover, e as setas do calendário ficavam rosa depois de clicadas. Os três
    repostos, como a lupa do cabeçalho já estava.
  - O "voltar às notícias" da notícia individual passa a `btn--escuro`: era
    contorno branco em fundo claro, invisível fora do hover — o mesmo caso do
    "Calendário completo".

## [0.31.1] - 2026-09-23

### Alterado
- O hover dos botões de contorno volta a mudar de cor. Na 0.30.1, além de pôr
  o contorno em `currentColor` — que era o que bastava —, tinha-se impedido o
  rótulo de mudar, e o resultado foi um botão que não reage a nada. O
  `currentColor` já faz o contorno seguir o rótulo seja qual for a cor que este
  tome.

## [0.31.0] - 2026-09-23

### Adicionado
- **Página Todos os Associados** (`/associados/todos-os-associados/`, filha de
  Associados, ID 352): hero, grelha de logótipos, as FAQ e a newsletter.
- Grelha de logótipos — `[apit_assoc_lista]`. Cinco por linha em desktop, em
  células de 230x150, que é o tamanho a que os ficheiros vieram; quatro em
  tablet, três num telemóvel largo, dois abaixo dos 480px. Os 50 logótipos
  enviados estão na biblioteca com o nome da produtora como título e texto
  alternativo, pela ordem do design.
  - Metade dos ficheiros vem sobre branco e a outra metade transparente. Com
    `mix-blend-mode: multiply` o branco toma o tom da página, pelo que nenhum
    aparece como uma caixa sobre o #F4F9FF, e os que são cor de ponta a ponta,
    como o vermelho da No Murphy, ficam como estão.
  - Um associado sem logótipo não é desenhado: um buraco numa grelha de cinco
    lê-se como um erro.
- Grupo de campos **Todos os Associados — conteúdos**: a etiqueta, o texto de
  introdução, e um repetidor com logótipo, nome e site por associado. O nome é
  o texto alternativo da imagem; o site, opcional, faz do logótipo um link que
  abre num novo separador, validado como os outros campos de link.
- Hero `hero--todos-associados`: a mesma arte da Associados, mas recortada por
  baixo — o que tira o magenta do topo e deixa o turquesa, o roxo e o verde do
  design — e esbatida no tom da página, com o texto e o botão escuros. O
  esbatimento é desta página, e não o 292/537 partilhado: aqui o hero acaba no
  botão, sem a folga que as outras páginas lhe deixam por baixo.
- As FAQ são o mesmo módulo da Associados, clonado no Elementor com as mesmas
  perguntas e respostas.

### Alterado
- O botão "Ver todos os associados" da banda aponta para a nova página, na
  Associados e na Sobre a APIT. A Sobre deixa o `url` próprio: com o directório
  a existir, as duas colocações vão para o mesmo sítio, e o destino fica num só
  campo.
- `.btn--escuro` passa do `sobre.css` para o `style.css`, porque há agora um
  segundo hero claro que precisa dele.

### Corrigido
- No telemóvel, a camada de fundo do hero desta página ficava com 0px de
  altura: a regra da Home que a mede pela banda de cor (`--apit-hero-banda`)
  apanha todos os `.apit-hero__media`, e estas páginas não têm essa banda. Sem a
  camada ia-se a imagem e o esbatimento. Corrigido só aqui — as outras páginas
  da folha comum foram afinadas com a camada a 0px (o título branco do
  Calendário depende de não haver esbatimento no telemóvel), e repô-la lá é uma
  alteração própria.

## [0.30.1] - 2026-09-23

### Corrigido
- O contorno dos botões `btn--outline` passa a `currentColor`, pelo que nunca é
  de cor diferente do rótulo que rodeia, em qualquer estado.
- E o rótulo deixa de mudar de cor sozinho. O `reset.css` do tema pai pinta
  todos os links de `#336` no hover, e um pseudo-selector mais um elemento
  pesam mais do que uma classe: qualquer `.btn` que seja um `<a>` perdia a sua
  cor assim que o ponteiro lhe tocava. Na banda dos Associados o rótulo ficava
  azul-escuro e o contorno branco, que foi como isto se viu. São nove botões,
  em seis páginas; os que são widgets do Elementor nunca foram afectados,
  porque aí a cor está em `.btn .elementor-button`.

## [0.30.0] - 2026-09-23

### Alterado
- A faixa "Associados" da Sobre a APIT — rótulo e botão, o que sobrou do
  carrossel de logótipos — dá lugar à banda construída na página Associados.
- `[apit_assoc_banda]` aceita `pagina`, por slug ou id, para ir buscar as
  palavras a outra página: a Sobre a APIT lê as que estão escritas na
  Associados, e uma edição muda as duas. E aceita `url`, que substitui só o
  destino do botão — o texto é partilhado, mas de cada página o visitante vai
  para onde faz sentido a partir dali: `#` na Associados, `/associados/` na
  Sobre a APIT.
- As regras da banda passam do `paginas.css` para o `style.css`: o bloco está
  agora em duas páginas que carregam folhas diferentes.

### Removido
- `[apit_associados]`, o seu template e o seu CSS, e os três campos
  `sobre_assoc_*` — a banda que os substitui vai buscar tudo à página
  Associados, e deixados ficar seriam campos no back office sem nada a lê-los.

## [0.29.19] - 2026-09-23

### Alterado
- Linha fechada das FAQ pelo painel do próprio elemento: 24px acima e abaixo,
  raio de 2px, e uma borda de 1px que não é uma cor mas um degradé de cinco —
  roxo, laranja, magenta, azul, turquesa. Um degradé não pode ser
  `border-color`, e o `border-image` ignora o raio, por isso a borda é pintada
  como segunda camada de fundo: o branco enche a caixa interior, o degradé a
  exterior, e o 1px de borda transparente é o único sítio onde a segunda se vê.
- A borda vai a 40% e não à força total dos tons do painel. A ampliação enviada
  pelo cliente lê 192,177,211 onde a primeira paragem é o roxo 103,65,150, e
  sobre branco isso dá 0,41 nos três canais — a camada está esbatida no
  ficheiro, coisa que o recorte do painel não mostra.
- Colunas das FAQ a 410 e 855 dentro dos 1340, o que deixa 35px entre elas e
  não os 40 do Elementor; e a coluna da lista deixa de ficar com os seus 10px
  de cada lado, que punham as linhas a 830.

### Corrigido
- A borda de topo das linhas: o Elementor põe-na a zero, porque os seus
  acordeões são feitos para ler como uma pilha de caixas que partilham arestas,
  e o design tem caixas separadas fechadas dos quatro lados.

## [0.29.17] - 2026-09-23

### Alterado
- FAQ dos Associados com os três estilos de tipografia dos painéis do Figma,
  que são três e não um: a pergunta fechada em "Corpo Big" (500, 20px, 120%,
  tracking -3%), a pergunta aberta em "H5" (Omnes Regular 22px, 120%, sem
  tracking) e a resposta em "Corpo" (Omnes Medium 16px, 168%, tracking 2%).
  Estavam todas a 15px Regular, com a resposta a 14px — o que fazia a lista ler
  como letra miúda debaixo de um título em vez de como as próprias perguntas.
- Linhas fechadas mais justas, 14px acima e abaixo contra os 42px da aberta: os
  42px do painel são a medida da linha aberta, que é a que foi medida.
- Abaixo dos 768px as margens laterais descem para 24px e o corpo um ponto: com
  os 42px de um desenho de 855, as perguntas corriam a quatro linhas num
  telemóvel.

### Corrigido
- As três regras que se tinham acumulado sobre `.elementor-tab-title` passam a
  uma só. Estavam a definir a mesma propriedade em sítios diferentes do
  ficheiro, à espera de que a próxima alteração contradissesse a anterior.

## [0.29.15] - 2026-09-23

### Adicionado
- Respostas às quatro perguntas frequentes dos Associados, escritas a partir do
  que a própria página diz — os três passos, os seis benefícios, os Estatutos e
  a ficha de inscrição. Nenhum valor, prazo ou regra que não esteja já no site:
  onde a resposta precisaria de um, aponta para o documento ou para quem o
  decide.

### Alterado
- Cartão "Pronto para fazer parte?" com as medidas do painel do Figma: 410×284,
  margem interna de 50 em cima e 37 aos lados, título Omnes Regular 28px/120%
  sem tracking. Tinha 320×217, 10px de margem — os do Elementor, porque a regra
  que lá estava perdia para a folha por página — e o título a 15px.
- Botões do cartão numa variante mais pequena, também do painel: 48px de altura,
  42px de raio, margem interna 12/30/11/30 e 16px de intervalo. Duas medidas do
  painel fixam o corpo da letra entre si, e é 14px; com os 15px do botão comum,
  "Descarregar ficha de inscrição" partia em duas linhas dentro do cartão.

## [0.29.12] - 2026-09-23

### Alterado
- Banda dos Associados com o título do design ("Associados"), texto escrito para
  a página em vez do que vinha do carrossel de logótipos, e o botão a apontar
  para `#` até existir a página do directório. Com o botão no lugar a banda fica
  com 409px, a altura do desenho.
- FAQ com as cores dos painéis: o cartão passa a `#28c4ba` → `#4a85c8` e o item
  aberto a magenta → `#b1b9c2`, um cinzento frio. O segundo tom era um malva
  lido de uma exportação, que mantinha toda a passagem rosa. Mais 10px de
  intervalo e 42px de margem interna.
- O sinal `+` passa para a direita da linha, azul quando fechada e branco quando
  aberta. Três coisas o impediam: o `float` do Elementor ganha ao nosso, o kit
  pinta o `fill` do SVG directamente, e o título de cada linha é um `<a>` que
  apanhava a cor de destaque — as linhas fechadas saíam magenta e a aberta azul
  sobre o próprio magenta.

### Corrigido
- O contentor das FAQ leva `e-no-lazyload`: é o 5.º de topo na página, e a regra
  do Elementor apagava o `background-image` do cartão e do item aberto.

## [0.29.7] - 2026-09-23

### Corrigido
- O bloco da newsletter aparecia dentro de uma moldura de 10px em cinco das sete
  páginas. São os 10px que o Elementor dá a qualquer contentor por omissão: na
  Home e na Sobre a APIT tinham sido postos a zero quando essas páginas foram
  construídas, nas cinco seguintes não. Uma faixa que pinta de ponta a ponta não
  pode ser encolhida pelo contentor, por isso a regra é escrita contra o bloco e
  não página a página.

## [0.29.6] - 2026-09-23

### Alterado
- A etiqueta "Calendário" por cima da grelha sai. O título da página logo acima
  já o diz, e o desenho não a tem. O valor por omissão passa a ser só do
  carrossel; uma grelha que queira cabeçalho continua a poder passar `etiqueta`
  no shortcode. A linha de cabeçalho só existe se tiver alguma coisa dentro.

## [0.29.5] - 2026-09-23

### Alterado
- Bloco da newsletter com a arte enviada pelo cliente, em vez da aproximação em
  CSS. O degradé em CSS fica por baixo, como cor que se vê enquanto a imagem
  carrega. `background-size: 100% 100%` e não `cover`: num telemóvel o `cover`
  cortava as pontas do degradé, que são a parte que se lê.

## [0.29.4] - 2026-09-23

### Corrigido
- O degradé do hero não aparecia por cima do vídeo. O Chromium dá a um `<video>`
  uma camada de composição própria, pintada acima do `::after` da secção seja
  qual for o `z-index` dos dois — provado removendo o `<video>` da página com a
  camada de média no lugar, e o degradé apareceu de imediato. Por cima de vídeo
  o degradé passa a andar no `::after` da própria camada de média, que é irmão
  do vídeo na mesma subárvore. `:has()` impede que os dois se somem: a secção só
  o pinta onde não há camada de média, que é o caso das Notícias.

## [0.29.3] - 2026-09-23

### Corrigido
- O degradé do hero passa a ter as paragens em pixels e não em percentagem. No
  Figma é um rectângulo próprio — 1920×649, Top 108 — que começa a pegar aos
  292px do topo da página e fica opaco aos 537. Em percentagem resolvia contra a
  caixa do hero, pelo que se movia sempre que um hero mudava de altura: no
  Calendário, de 610px, acabava aos 403 em vez dos 537. Abaixo dos 1024px voltam
  as percentagens, que 292 e 537 são medidas de um desenho de 1920.

## [0.29.2] - 2026-09-22

### Adicionado
- `[apit_assoc_banda]`: a banda dos Associados, com o fundo enviado pelo cliente,
  título, texto e botão centrados. Substitui o carrossel de logótipos, que nunca
  chegou a aparecer — a galeria estava vazia, a secção saía cedo e ficava um
  contentor vazio na página.
- A banda leva `id="associados"`, de modo que o botão "Ver associados" do hero,
  que apontava para `#associados` e para nada, passe a ter destino.

### Alterado
- Título e texto dos seis benefícios voltam a ficar centrados.
- Os campos `assoc_logos_*` passam a `assoc_banda_*`, com os valores migrados; a
  galeria de logótipos foi removida do grupo.

### Corrigido
- O contentor da banda leva `e-no-lazyload`. A regra de lazy-load do Elementor
  aplica `background-image: none !important` ao 4.º contentor de topo **e a todos
  os seus descendentes** até ser visto, o que apagava o fundo da banda.

## [0.28.4] - 2026-09-22

### Alterado
- Glifo do PDF à direita do rótulo, que é o lado em que os outros botões da
  página levam a seta e o ícone de lista.

## [0.28.3] - 2026-09-22

### Adicionado
- Modificador `btn--doc`: um glifo de PDF depois do rótulo, em máscara pintada
  com `currentColor` para seguir a cor do botão. Aplicado a "Consultar os
  Estatutos" e aos dois "Descarregar ficha de inscrição" (hero e FAQ).
- Os dois botões "Descarregar ficha de inscrição" apontam para a ficha de
  inscrição de 2025 e abrem em nova janela.

## [0.28.2] - 2026-09-22

### Corrigido
- Passos dos Associados sem o número desenhado por cima do círculo: as imagens
  do cliente já o trazem, e apareciam os dois sobrepostos.
- Círculo dos passos com 172px, um pouco maior que os 157px dos benefícios ao
  lado, como no design. Estava com 118px, que punha os dois ao contrário.

## [0.28.1] - 2026-09-22

### Alterado
- Na página Associados os três passos passam para antes dos seis benefícios,
  como no design: primeiro como se adere, depois o que se ganha.
- Os seis benefícios passam a uma só linha de seis colunas, alinhados à
  esquerda e com círculos de 157px. Eram três e três, centrados.
- O sangramento de 102px do wordmark COMO ADERIR só existe a partir dos
  1504px de janela, que é onde a margem ao lado da coluna o comporta.

### Corrigido
- A Internacionalização tinha voltado a ser item de topo do menu — a relação
  de subitem perdeu-se algures depois de ter sido criada. Com 186px a mais na
  barra, o header transbordava a janela entre os 1024px e os 1418px.

## [0.27.9] - 2026-09-22

### Alterado
- Wordmark COMO ADERIR com os valores do painel do Figma: 177,22px em vez dos
  190px, sem tracking, opacidade 80% e 102px para fora da coluna à esquerda.
  O tamanho anterior tinha sido acertado à coluna, não lido.
- As duas linhas deixam de se sobrepor: 2 × 177,22 × 82% dá os 290px que o
  painel reporta, portanto a entrelinha é todo o espaçamento.

## [0.27.8] - 2026-09-22

### Alterado
- O bloco "Como aderir" passa a fazer parte do hero dos Associados, como no
  design: o wordmark COMO ADERIR à esquerda, o título, o texto e os botões à
  direita, tudo sobre a mesma arte. Deixou de ter gradiente próprio — tinha um
  turquesa que acabava a direito na fronteira do hero.
- O wordmark ASSOCIADOS passa para dentro da coluna de conteúdo do hero. Está
  ancorado ao fundo do que o contém, e com o hero a crescer para receber o novo
  bloco esse fundo deixou de ser onde o design o põe.
- `[apit_wordmark]` aceita `alinhamento="esquerda"`, que o torna uma coluna em
  vez de uma camada e espelha o esbatimento — à esquerda ele tem de afinar para
  a direita, ou apagava-se contra o título ao lado.
- Hero dos Associados com 96px de espaço em baixo, contra os 260px das outras
  páginas: o que ali era espaço para o degradé esbater é agora espaço debaixo
  do bloco, e o design quase não lho dá.

### Corrigido
- O Elementor põe `position: relative` em todos os `.elementor-element`, e o
  invólucro do shortcode passou a ser o bloco de referência do wordmark quando
  este deixou de ser filho directo do hero: uma caixa sem altura no topo da
  coluna, que punha uma palavra de 441px a -242px, fora da página.

## [0.27.3] - 2026-09-22

### Alterado
- Hero dos Associados com o fundo enviado pelo cliente. A imagem entra na
  multimédia e no campo ACF da página, não no tema: é o campo que já existe
  para isto e permite trocá-la no back office. O vídeo que lá estava era o
  ficheiro da Home, emprestado enquanto a página se construía, e saiu.
- O véu que esbate o hero para branco passa a uma propriedade
  (`--apit-hero-scrim`) e está desligado nos Associados, cujo design corre a
  cor cheia até baixo. Com ele o título branco, os botões de contorno branco e
  o wordmark ficavam sobre quase-branco.
- Gradiente de recurso dos Associados reescrito segundo a imagem: magenta em
  cima à esquerda, turquesa à esquerda, verde à direita, núcleo roxo em baixo.
  É o que se vê no telemóvel, onde a camada de media não é usada.

### Corrigido
- `.elementor img { height: auto }` ganhava o empate com a regra da imagem do
  hero: um fundo de 1920x1261 ficava com 1251px de altura dentro de uma camada
  de 957px — sem recorte nenhum e a transbordar 294px. Afecta qualquer página
  com imagem no hero, não só esta.
- `overflow: hidden` do hero estava a ser substituído pelo Elementor e
  calculava `visible`, o que deixava a camada de media passar para a secção
  seguinte.

## [0.27.0] - 2026-09-22

### Adicionado
- Submenus no menu principal, abertos ao passar o rato e fechados ao sair.
  Painel branco com cantos de 3px e sombra suave, itens a `--apit-black` que
  passam a magenta no hover, e uma seta no item que abre.
- As regras são escritas contra a marcação do próprio WordPress
  (`.menu-item-has-children` e `.sub-menu`), nunca contra um item em concreto:
  qualquer subitem que o cliente crie em qualquer entrada do menu fica com este
  aspecto sem se tocar em CSS. Um terceiro nível abre para o lado.
- No menu do telemóvel, onde não há hover, os subitens ficam listados sob o seu
  item, indentados e com um filete à esquerda.
- A página de Internacionalização entra no menu como subitem da APIT (alteração
  em base de dados, não em código).

## [0.26.8] - 2026-09-22

### Alterado
- Hero das quatro páginas com os valores do painel do Figma em vez das
  proporções lidas de exportações: breadcrumb a 231px do topo (Omnes Medium
  14px/140%/10%), título a 278px (Omnes Regular 52px/120%, sem tracking) e a
  primeira etiqueta de secção a 379px. As três caixas assentam agora nos Top
  exactos do painel.
- Etiqueta de secção ("Section Eyebrown") passa a Omnes Light 18px/120% com
  35% de tracking. Estava a 13px/400, estimada de uma exportação — afecta todas
  as etiquetas das quatro páginas.
- A sobreposição da grelha sobre o hero passa a uma só medida, usada pela
  margem e pela extensão transparente do fundo. No tablet as duas tinham
  divergido e 103px abaixo do hero mostravam o branco do body em vez do
  #F4F9FF da secção.

### Corrigido
- No Calendário o branco de `.calendario` pintava por cima da metade inferior
  do hero e da segunda linha do wordmark. Nada dentro da secção sobreposta
  pinta fundo próprio.

## [0.26.3] - 2026-09-22

### Alterado
- Os cartões de documento de uma linha passam a ter todos a altura do mais alto
  e os botões de download alinhados entre si, independentemente do número de
  linhas do título. A grelha já igualava as alturas — por linha, não pela
  grelha toda; faltava o botão descolar do título e assentar no fundo do
  cartão.

## [0.26.2] - 2026-09-17

### Alterado
- A primeira secção do **Calendário** e dos **Documentos** sobe para dentro do
  hero, como no desenho, através da classe `apit-sobrepoe`:
  - Margem de −193px. É até onde a secção pode subir sem que a junção se veja:
    o *scrim* do hero termina exactamente em `#F4F9FF`, a mesma
    `--apit-branco` da secção, e completa aos 66% da altura — os últimos 34% de
    um hero de 569px são precisamente 193px já planos.
  - O fundo da secção é transparente nesses 193px e só depois toma a sua cor.
    Pintado a direito, tapava 161px da segunda linha do wordmark — e não havia
    razão para o pintar ali, onde o degradé já chegou a essa mesma cor.
  - Sem sobreposição abaixo dos 768px, e reduzida a −90px no intervalo
    intermédio: o hero é mais baixo aí e há menos degradé plano onde esconder a
    junção.
- Removidos dois blocos de comentário que tinham ficado órfãos de edições
  sucessivas sobre o wordmark.

## [0.26.1] - 2026-09-09

### Corrigido
- Wordmark dos heros igual ao da Sobre a APIT, com os cortes indicados: 280px,
  peso 700, `letter-spacing: -6px`, branco, e a máscara *scanline* com
  `mask-composite: intersect`. Sem os prefixos `-webkit-`.
- **A quebra de linha passou a estar no texto do shortcode**, não na CSS:
  `[apit_wordmark texto="DOCU MENTOS"]`. O template imprime uma linha por
  palavra, pelo que a quebra é exacta e editável no Elementor. Deixada à CSS,
  saía "DOCUME / NTOS" e o ponto mudava com a fonte e a largura do ecrã.
  - Associados `ASSO CIADOS` · Calendário `EVEN TOS` · Documentos
    `DOCU MENTOS` · Internacionalização `WORLD`
- O bloco está ancorado à base do hero, 32px acima dela, e não ao topo: duas
  linhas de Omnes a 280px medem 459px contra os 410px da Gilroy, pelo que
  qualquer `top` copiado do desenho cortava a segunda linha.
- Reposto o wordmark "COMO ADERIR" da banda dos Associados, que a passagem
  anterior tinha substituído por "ASSO CIADOS" — a página tem dois e eu
  procurei pelo nome do shortcode em vez do id do elemento.

### Notas
- A geometria do hero **já correspondia ao desenho** antes desta versão: altura
  569 contra 572, breadcrumb 192 contra 199, primeira capa 675 contra 680. O
  vazio no meio da secção vinha do wordmark estar fino e pálido, não do
  espaçamento.

## [0.26.0] - 2026-09-09

### Corrigido
- **O degradé do hero estava do lado errado da camada de vídeo.** Eu tinha posto
  os quatro degradés como *fundo* da secção, onde nunca podiam ser vistos: a
  camada de média é absoluta e cobre a secção em todos os breakpoints — está
  documentado no `.apit-hero__media::after` da Home, que é justamente por isso
  que existe. Passam a ser um *overlay* sobre a média, e os degradés de fundo
  ficam como reserva, para enquanto o vídeo carrega ou quando não há vídeo nem
  imagem.
- O overlay usa os valores fornecidos, iguais nas quatro páginas:
  `linear-gradient(178.03deg, rgba(244,249,255,0) 28.49%, #F4F9FF 66%)`.
- Geometria do hero: o conteúdo começava demasiado abaixo. O breadcrumb passou
  de 51% para 34% da altura da secção, contra os 32% do desenho, e o padding
  inferior dá agora espaço ao degradé para desvanecer sem apanhar o título.
- Wordmark com os valores do painel: 275px, entrelinha 82%, sem
  letter-spacing, alinhado à direita, maiúsculas.

### Por fazer
- **Gilroy-Bold não existe neste site.** O kit do Typekit serve só as famílias
  omnes e não há ficheiro de fonte no tema. O wordmark cai na Omnes, o que muda
  a letra e — mais visível — parte a palavra noutro ponto: "ASSOCIADOS" sai em
  três linhas onde o desenho tem duas. Falta acrescentar a Gilroy ao kit ou
  colocar os ficheiros no tema.
- Com o degradé aplicado, os botões do hero dos Associados ficam a 62% da altura
  da secção, onde o degradé já vai em 89% do tom claro — um botão de contorno
  branco fica ilegível. Confirmar se nessa página o hero é mais baixo ou se as
  paragens do degradé são outras.

## [0.25.8] - 2026-09-08

### Corrigido
- Cartão de documento com as medidas do painel do Figma, que corrigem quatro
  aproximações minhas:
  - **Proporção 230/322** em vez de 3/4 arredondado. 0,714 contra 0,75 são 9px
    de diferença em altura a esta largura, o que basta para recortar uma capa
    diferentemente do original.
  - **Raio 3px**, não 8px — na capa, na imagem e no degradé.
  - **Desvio do degradé 7px**, não 8px (topo 1581 contra 1574 da capa).
  - **Degradé com as três paragens que o Figma reporta**, canto a canto:
    `rgb(57,240,185)` → `rgb(74,133,200)` → `rgb(230,54,146)`. Esta magenta não
    é a `--apit-magenta` da marca (`#f41892`); fica como o desenho a dá.
- Botão com os valores fornecidos: Omnes Medium 12px, entrelinha 100%,
  letter-spacing 3% (0,36px), maiúsculas, e contorno de **2px na cor cheia** —
  não mais claro, como eu tinha inferido. Sendo igual ao texto, usa
  `currentColor`. Ícone com moldura de 15×15.
- Intervalo da grelha a 37px, o mesmo dos cartões do calendário: cinco capas de
  230px com quatro intervalos dão 1298px na coluna de 1340px.

## [0.25.6] - 2026-09-08

### Corrigido
- Título do cartão de documento com os valores do Figma que o cliente forneceu:
  Omnes Medium 22px, entrelinha 120%, sem letter-spacing. As minhas estimativas
  a partir da imagem exportada estavam erradas nos três — 18px, peso 400 e
  −0,2px.
- Botão de download: o contorno passa a ser mais claro do que a etiqueta. Os
  dois usavam o mesmo azul e a pastilha lia-se mais pesada do que no desenho. O
  tom do contorno é derivado da cor do texto com `color-mix`, para não haver um
  segundo valor a manter em sincronia; com um literal antes, para um browser sem
  `color-mix`.

## [0.25.5] - 2026-09-08

### Corrigido
- A capa do documento passa a ter os mesmos cantos arredondados que o degradé
  atrás dela. O raio ficou numa propriedade — `--apit-capa-raio` — lida pela
  capa, pela imagem e pelo degradé, para os três não poderem divergir.
  Aplicado à imagem e não ao contentor com `overflow: hidden`, que voltaria a
  recortar a sombra que tem de sobressair.

## [0.25.4] - 2026-09-08

### Corrigido
- Cartões dos Documentos. Faltava o degradé deslocado que o desenho usa em vez
  de uma sombra: turquesa no canto de cima a correr para magenta em baixo,
  8px à direita e abaixo da capa, com cantos arredondados. O `overflow: hidden`
  da capa teve de sair — recortava justamente o que tem de sobressair; o corte
  passa a vir do `object-fit`, que já era quem fazia o trabalho.
- Botão de download: contorno fino e texto no azul da marca, como no desenho,
  em vez do contorno quase preto que tinha. Usa o `icon-download.svg` carregado,
  copiado para o tema como se fez com a lupa — é interface, viaja com o código.
  O glifo é uma máscara pintada com `currentColor`, pelo que segue o texto no
  hover sem um segundo ficheiro.
- **Altura das imagens**: `.elementor img { height: auto }` tem a mesma
  especificidade que uma classe e é impressa depois desta folha, pelo que ganhava
  o empate e cada capa tomava a proporção do ficheiro em vez da da caixa. Uma
  capa horizontal saía com 150px numa moldura de 333px; uma vertical só parecia
  certa por coincidência. Resolvido com duas classes, como já se fazia no logo
  da banda Internacionalização — e aplicado também aos círculos e aos logótipos
  dos Associados, onde era a mesma falha à espera.

## [0.25.3] - 2026-09-08

### Alterado
- A banda **Internacionalização** do Calendário e dos Documentos passa a ser o
  módulo que já existia na Sobre a APIT, em vez de um Saved Template paralelo.
  Duas implementações do mesmo bloco era uma a mais: a da Sobre já estava
  estilada e editável. O template duplicado foi apagado.
- Esse módulo passa a ler os campos **sempre da Sobre a APIT**, qualquer que
  seja a página que o renderiza — uma origem e um ecrã para a editar, em vez do
  mesmo parágrafo escrito três vezes.
- A CSS dele saiu de `sobre.css` para `assets/css/internacionalizacao.css`,
  carregada nas três páginas que o mostram. Ficheiro próprio e não `style.css`,
  para as outras três não a levarem sem uso.
- Os nove círculos dos Associados ficaram ligados aos repetidores, casando pelo
  nome do ficheiro. Um deles precisou de ligação directa: o ficheiro chama-se
  `envie-a-cadidatura.png`, sem o «n».

### Corrigido
- O bloco *Documentação de Apoio / Ainda não é associado?* amontoava-se à
  esquerda: como container flex sem folga, as duas colunas ficavam encostadas.
  Passou a grelha de duas colunas de 470px com 77px de intervalo, centrada — no
  desenho os títulos começam aos 429px e 966px do quadro de 1920px; ficaram aos
  454px e 1001px.
- O `gap` e o `justify-content` precisaram de `!important`, e não por
  especificidade: o Elementor conduz o container por propriedades
  personalizadas (`gap: var(--gap)` em `.e-con`, com `--gap` a 0), e a sua
  `frontend.min.css` é impressa depois desta folha — a declaração perdia o
  empate por ordem de origem.
- Em mobile o bloco mantinha duas colunas de 136px e provocava 19px de
  overflow: a regra antiga usava `flex-direction`, que deixou de se aplicar a
  uma grelha.

## [0.25.1] - 2026-09-08

### Corrigido
- O hero das páginas novas, a banda "Quem pode associar-se" e o bloco
  "Documentação de Apoio / Ainda não é associado?" estavam encostados à
  esquerda. São containers do Elementor, e o Elementor escreve `margin: 0` e o
  seu próprio padding para cada container na folha por página — três classes de
  profundidade e depois da nossa. A `margin-inline: auto` era simplesmente
  ignorada.
  - Colunas de conteúdo: `max-width`, `margin-inline` e `padding-inline` com
    `!important`.
  - Blocos com fundo a sangrar: a caixa fica à largura do ecrã e o conteúdo
    centra-se por `padding-inline: max(20px, calc(50% - 670px))`, o mesmo que a
    secção de contactos da Sobre a APIT já usava.
- Os containers que envolvem um Saved Template levavam os 10px de padding por
  omissão do Elementor, o que inseria a banda 10px de cada lado — visível como
  uma tira clara nas arestas do degradé. Passaram a ter a classe `apit-sangra`,
  com o padding a zero.

### Alterado
- O vídeo do hero passa a vir sempre do campo ACF, nas seis páginas. A Home e a
  Sobre a APIT ainda tinham o nome do ficheiro dentro do shortcode
  (`video="homepage.mp4"`), que era precisamente o hardcoding que o grupo de
  campos veio remover — e que continuava a ganhar ao campo, por desenho.
  As quatro páginas novas ficam com `homepage.mp4` como ponto de partida
  visível; é um campo por página, pelo que trocar uma não afecta as outras.

## [0.25.0] - 2026-09-08

### Adicionado
- Templates e CSS das quatro páginas novas. `assets/css/paginas.css`, carregada
  só nelas: um hero com quatro degradés, isolados em variáveis por página, mais
  as secções e os blocos partilhados.
- `template-parts/wordmark.php` — o wordmark de cada hero, com o texto em
  atributo do shortcode. Divide em linhas pelos espaços, como o "COMO ADERIR"
  do desenho.
- `template-parts/watch-portugal.php`, `template-parts/documentos.php`, os três
  dos Associados e os três da Internacionalização.
- Grupo `Ficheiro do documento` no CPT: o título e a capa vêm do WordPress, pelo
  que só falta o ficheiro.
- Copy do desenho semeada nos campos, sem sobrepor nada já preenchido. As
  páginas chegam com texto real e só faltam as imagens.

### Alterado
- `[apit_calendario]` passa a ler os atributos: `layout=grelha` dá as três
  colunas do Calendário sem setas, `layout=carrossel` mantém a Home intocada.
- O ícone de download passou a servir também a página Documentos, lido do campo
  que já existia na Sobre a APIT em vez de duplicado.
- Removido `inter_mercados_label`: a etiqueta dos mercados estava em ACF e no
  shortcode ao mesmo tempo — dois controlos para um valor.

### Notas
- Os degradés dos heros e as cores dos cartões foram **amostrados dos exports do
  cliente**, não lidos do Figma: o limite do MCP continua esgotado. Estão
  isolados num bloco por página, e as cores dos cartões são campos, pelo que
  corrigi-los é uma edição pontual.

## [0.24.1] - 2026-09-08

### Adicionado
- `_elementor_data` das quatro páginas novas, com o hero de cada uma a partilhar
  `.apit-pagina-hero` e a acrescentar uma classe própria — a CSS do hero fica
  num lugar e só o degradé difere por página.
- Dois Saved Templates, renderizados ao vivo por `[apit_template]`: o bloco
  *Documentação de Apoio / Ainda não é associado?* (nas três páginas) e a banda
  *Internacionalização* (em duas). Confirmado no frontend: editam-se uma vez.
- Shortcodes `[apit_wordmark]`, `[apit_watch_portugal]`, os três dos Associados,
  os três da Internacionalização e `[apit_documentos]`.
- `[apit_calendario]` passa a aceitar `layout` (carrossel ou grelha), `limite`,
  `acao` e `etiqueta`, para um só componente servir a Home, a Internacionalização
  e o Calendário. Os valores por omissão são os da Home, que fica intocada.

### Por fazer
- Os *template parts* e a CSS destas secções: por agora os shortcodes existem e
  devolvem vazio, pelo que as páginas mostram a estrutura sem o conteúdo.
- O `limite` do `[apit_calendario]` ainda não é lido pelo template — continua a
  usar o valor interno de 4.

## [0.24.0] - 2026-09-08

### Adicionado
- Base das páginas **Associados**, **Internacionalização**, **Calendário** e
  **Documentos**. Só estrutura e campos; templates e CSS vêm a seguir.
- CPT `apit_documento` com a taxonomia `apit_area_documento` e os três termos
  do desenho: Anuários, Brochuras, Estudos. Um tipo com taxonomia em vez de
  três tipos — uma quarta área passa a ser um termo que o cliente acrescenta.
  Ordena por `menu_order`, não pela data, porque o desenho mostra uma sequência
  deliberada de capas.
- Página Internacionalização (#137), fora do menu por indicação do cliente.
- Grupos `Associados — conteúdos` e `Internacionalização — conteúdos`. O
  Calendário e os Documentos não têm grupo: o conteúdo vem dos CPT e o texto do
  hero de widgets do Elementor, como na Sobre a APIT.
- `[apit_template id="X"]` — renderiza um Saved Template do Elementor ao vivo,
  pela API `get_builder_content_for_display`. É o que o widget de Template do
  Pro faz e o grátis não tem: um Saved Template inserido pela via normal é
  **copiado**, e editá-lo depois não muda as páginas que levaram cópia. Assim os
  blocos repetidos das três páginas editam-se uma vez, dentro do Elementor.
- O grupo `Hero — fundo` passa a cobrir as seis páginas com hero.

### Notas
- As medidas destas páginas vão ser derivadas dos screenshots do cliente à
  escala do desenho (1920px): o limite de chamadas do MCP do Figma esgotou-se
  outra vez, com os nós `351:958`, `351:1869`, `353:3042` e `353:3832` ainda por
  ler. Confirmar quando o limite renovar.

## [0.23.2] - 2026-09-08

### Corrigido
- Regras de localização dos grupos de campos. Estavam a usar o param `post`,
  que no ACF é o selector de **Artigos**, com IDs de páginas como valor. Uma
  página não é um valor válido ali, pelo que ao gravar o ACF trocava-o pelo
  primeiro artigo real: o grupo **Hero — fundo** ficou com
  `post == 33` duas vezes e deixou de aparecer em qualquer página.
  - Home: `page_type == front_page`, sem ID nenhum.
  - Sobre a APIT: `page == 6`, o param correcto — "Página".
- O grupo **Sobre a APIT — conteúdos** tinha o mesmo `post == 6` e funcionava
  por acaso, porque o ACF compara o ID sem olhar ao tipo. Bastava alguém abri-lo
  e gravar para os 21 campos da página desaparecerem. Corrigido antes de
  acontecer.

## [0.23.1] - 2026-09-08

### Corrigido
- Os grupos de campos aparecem agora em **Tudo** e podem ser editados no BO. Só
  existiam como JSON no tema, e o ACF lista em Tudo os registos da base de
  dados — um grupo só em JSON aparece a cinzento, em "Sincronização
  disponível", e não abre. Os seis foram importados: 6 grupos e 36 campos.
- Guarda em `inc/acf.php` que retira `local_file` e `local` do JSON depois de o
  ACF o gravar. São chaves de runtime, e o `local_file` é um caminho absoluto —
  `C:/Users/...` — que iria para o servidor num ficheiro cuja função é
  descrever campos. Corre à prioridade 20 porque o ACF escreve o ficheiro à 10
  e a acção passa o array por valor, o que impede tirar a chave antes.

### Alterado
- Os seis JSON ficaram na forma canónica do ACF: ordem das chaves normalizada e
  os campos `display_title`, `allow_ai_access` e `ai_description` que esta
  versão acrescenta. Nada de conteúdo mudou, e daqui em diante uma edição pelo
  BO produz diferenças mínimas.

## [0.23.0] - 2026-09-08

### Adicionado
- Grupo de campos **Hero — fundo**, na Home e na Sobre a APIT, com o fundo da
  secção editável no back office:
  - **Vídeo** — campo File restrito a `mp4`/`webm`, escolhido da biblioteca de
    multimédia. Preenchido, é ele que aparece no desktop.
  - **Imagens — desktop** — galeria. Uma imagem fica fixa; duas ou mais passam
    a slider, na ordem definida por arrastamento.
  - **Imagens — mobile** — galeria própria para os ficheiros ao alto, usada
    abaixo dos 768px. Vazia, cai para a de desktop, e vice-versa.
  - **Intervalo do slider** — em milésimos de segundo, por omissão 4000.
- `assets/js/hero-slider.js`, carregado só nas duas páginas com hero.
- `inc/hero.php` com os helpers que convertem os campos em marcação.

### Alterado
- Em mobile a camada de média passa a ocupar a banda de cor no topo da secção e
  a parar onde ela para — a opção (b) das duas que foram pesadas: o branco por
  baixo e a legenda escura da v0.22.8 ficam intactos. A altura da banda passou
  a `--apit-hero-banda`, lida pelo `background-size` e pela camada, para que uma
  não possa afastar-se da outra.
- O vídeo continua escondido abaixo dos 768px. A resposta a esse recorte é
  agora a galeria de mobile, e não um `object-position` melhor.
- `[apit_hero_media]` deixa de precisar de atributos: lê os campos. Os
  atributos continuam a ganhar quando existem, o que mantém os vídeos a
  funcionar da pasta do tema até serem carregados para a multimédia.

## [0.22.11] - 2026-09-07

### Alterado
- Lupa do cabeçalho a 24x24px. A caixa do botão desceu de 30 para 24 e o
  `mask-size` passou a `30px`, para que o glifo — que mede 24 unidades no
  ficheiro de 30 — assente exactamente nas arestas da caixa. Com `contain`
  ficaria a 19px.

### Corrigido
- Hover da lupa: muda a cor do ícone, não o fundo da caixa. O rosa `#c36` que
  aparecia vinha do `reset.css` do tema pai, que o aplica a todos os
  `button:hover`; não pertence a nenhuma paleta do projecto. A caixa fica
  transparente e o glifo passa a magenta da marca — a máscara pinta com
  `currentColor`, pelo que basta a `color`. Vale também para o botão de
  pesquisa do menu mobile, com o mesmo defeito.

## [0.22.10] - 2026-09-04

### Corrigido
- Hero da Home: o risco branco passa por trás do painel preto do teaser em vez
  de lhe atravessar um pedaço de linha por cima. O painel subiu para
  `z-index: 3` e a camada de decoração deixou de ter `z-index` próprio — com
  ele era um contexto de empilhamento e selava o painel lá dentro, sem forma de
  o ordenar contra os widgets de texto. A linha continua inteira em todo o
  resto do seu percurso.

## [0.22.9] - 2026-09-04

### Corrigido
- Calendário em mobile: passa a mostrar um card completo de cada vez, sem o
  segundo a espreitar. O card enche a coluna (`--apit-card: 100%`), pelo que o
  seguinte arranca já fora da margem direita — o mesmo critério dos ecrãs mais
  largos, onde três cards e os dois intervalos dão exatamente a largura do
  track. As setas continuam a ser o controlo.

### Alterado
- Logo da Jelly no rodapé: `title="Jelly Digital Agency"` na ligação e
  `alt="Logo Jelly Digital Agency"` na imagem.

## [0.22.8] - 2026-09-04

### Corrigido
- Hero da Home em mobile: a cor passa a ser uma banda no topo da secção e não
  o fundo de toda ela, como no nó `66:1660` do Figma. O painel do vídeo começa
  dentro da banda e atravessa-a; a legenda que fica por baixo passou a estar
  sobre branco, com o título e o texto em `--apit-black` em vez de branco.
  A banda é o próprio gradiente do hero com altura definida (`background-size`
  com `no-repeat`), o que evita uma segunda camada e mantém os radiais na
  posição que têm em ecrã largo. Os 819px colocam o limite 90px dentro do
  painel, que arranca aos 729px nesta largura.

## [0.22.7] - 2026-09-04

### Corrigido
- Removida a moldura branca desencontrada do painel do teaser abaixo dos
  1024px, que era a origem das linhas brancas que o cliente reportou. É o
  `.hero__video::after`, com `border: 2px solid #fff` e um inset negativo.
  Funciona num ecrã largo, onde o painel tem 329px dentro de 1425px e a moldura
  fica 292px afastada da margem; assim que o painel passa a ocupar a largura
  toda, a moldura não tem para onde se deslocar e a sua borda direita fica a
  **8px da margem do ecrã** — tanto a 390px como a 985px. Em vez de emoldurar o
  painel, lia-se como uma linha branca ao lado da página.

### Notas
- Removida também em tablet, e não só em mobile como pedido: a geometria é
  idêntica nas duas larguras, com a borda a 8px da margem em ambas. Em desktop
  a moldura fica intacta.
- Na versão anterior tinha concluído que a linha vinha da moldura do modo
  dispositivo das ferramentas de desenvolvimento. Estava errado: a minha busca
  procurou bordas nos **elementos** e não nos pseudo-elementos, e foi por isso
  que não a encontrou. O cliente localizou-a.

## [0.22.6] - 2026-09-04

### Corrigido
- O divisor do hero volta a aparecer abaixo dos 1024px. Estava com
  `display: none` desde o trabalho de responsivo da v0.18.x, sem razão
  registada — provavelmente porque os 112px de margem superior que o widget
  carrega para o desktop ficavam absurdos a esta largura. Era a margem que
  precisava de correcção, não a linha: fica em 40px e a linha sai a 350x2 em
  `rgba(255,255,255,0.5)`, entre os botões e o lockup Watch Portugal.
- Removido um comentário duplicado no bloco de tablet, sobra de uma edição
  anterior.

### Notas
- Procurei a linha branca vertical que o cliente reporta e **não existe no
  CSS**: varri todos os elementos da página por bordas, contornos, sombras e
  caixas finas, e a única linha é o divisor, horizontal. A vertical aparece
  tanto na captura que fiz como no screenshot do cliente, sempre encostada à
  margem, o que aponta para a moldura do modo dispositivo das ferramentas de
  desenvolvimento e não para o site.

## [0.22.5] - 2026-09-04

### Corrigido
- Hero da Home em mobile: o vídeo de fundo mostrava **15% da sua largura**. O
  `object-fit: cover` numa caixa de 390x1432 recorta os 1920px do ficheiro para
  uma fatia de 294px, pelo que um telefone via uma tira vertical da arte —
  turquesa no topo, azul a meio e vermelho sólido em baixo. Era isso que dava a
  impressão de a cor acabar a meio da secção. Nenhum `object-position` resolve:
  o hero tem proporção 0,27 contra 1,78 do vídeo, e a altura vem do
  empilhamento do conteúdo. A camada de vídeo passa a estar oculta abaixo dos
  768px e o degradé do próprio hero — o mesmo que existia antes do vídeo —
  carrega a secção.
- Retirados 118px de cor vazia no fim do hero. Era a margem inferior da legenda,
  um valor definido no widget para o espaçamento do desktop, que em mobile se
  somava aos 56px de padding do hero. O hero desce de 1432px para 1311px, e o
  espaço depois da legenda passa a ser exactamente o padding.

### Notas
- Não foi possível comparar com o Figma: o nó do mobile da Home (`66:2060`)
  continua inacessível por limite de chamadas. A decisão de usar o degradé em
  vez da fatia de vídeo é minha, apoiada no mockup enviado pelo cliente e no
  facto de uma fatia de 15% não poder corresponder a nenhuma intenção de
  desenho.
- O ficheiro de vídeo continua a ser descarregado em mobile, apenas não é
  mostrado. Evitá-lo exige carregar o `src` por script acima de uma largura —
  não feito.
- O painel do teaser (a caixa escura com o play) não foi alterado: mantém-se a
  350x429, como ficou na v0.18.1.

## [0.22.4] - 2026-09-04

### Alterado
- A lupa do cabeçalho passa a ser o SVG carregado, em vez do glifo do Font
  Awesome. O mesmo no botão de pesquisa do menu mobile, que usava o mesmo
  glifo.
- Desenhada como **máscara** pintada com `currentColor`, não como `<img>`: o
  ficheiro traz `fill="white"` embutido no path, pelo que como imagem só
  poderia ser branca. A máscara aproveita a forma e deixa a cor ao botão.

### Notas
- O ficheiro foi **copiado para `assets/img/icon-lupa.svg`**, não referenciado
  da multimédia. É onde vivem os outros assets do cabeçalho e do rodapé — os
  logótipos e o véu — e a razão é prática: assim viaja no git e funciona no
  servidor logo após o deploy, sem depender da pasta de uploads nem da base de
  dados. A cópia na multimédia fica sem uso.
- SVG inspecionado antes de ser usado: sem `<script>`, sem atributos `on*` e
  sem referências externas.

## [0.22.3] - 2026-09-04

### Corrigido
- A borda do botão "Área Reservada" fica da cor do texto no hover. Passa a
  `currentColor` em vez de um branco fixo, pelo que acompanha a etiqueta em
  qualquer estado — em repouso continua branca, como estava. Aplicado também ao
  mesmo botão no menu mobile.

### Notas
- O botão não tem, nem tinha, regra de hover própria: a cor do texto no hover
  vem do `a:hover { color: #336 }` genérico do tema pai. Esse azul-escuro sobre
  o véu do cabeçalho dá pouco contraste — não é uma escolha de design, é o que
  o tema pai aplica a qualquer link. Fica por decidir se o hover deve manter-se
  branco ou tomar uma cor da marca.

## [0.22.2] - 2026-09-03

### Alterado
- `max-width` do título da newsletter fixado em **300px** em desktop, por
  indicação do cliente.
- Os breakpoints abaixo de 1024px passam a declarar `11ch`. Antes o valor em
  `ch` estava só na regra base e servia todos os tamanhos; com 300px fixos ali,
  os tamanhos menores precisam do seu próprio valor — 300px seriam largos
  demais para o tipo a 36px e a 30px, e o título quebraria no lugar errado.

### Notas
- Confirmado em quatro larguras, todas com o título em duas linhas: 1425px
  (fonte 48px, caixa 300px), 985px (36px, 247px), 390px e 320px (30px, 206px).

## [0.22.1] - 2026-09-03

### Alterado
- As redes sociais abrem num novo separador, nos quatro sítios onde aparecem.
  Uma só edição, no `apit_redes_sociais_html()` — o que a consolidação da
  v0.22.0 tornou possível.
- Vai com `rel="noopener"`: sem ele a página aberta consegue alcançar esta
  através do `window.opener`.
- A etiqueta passa a dizê-lo — "Instagram (abre num novo separador)". Um ícone
  sozinho não dá a um leitor de ecrã qualquer indicação de que o link sai do
  site.

## [0.22.0] - 2026-09-03

### Alterado
- Redes sociais preenchidas com as contas reais, nos quatro sítios onde
  aparecem: barra superior, menu mobile, rodapé e bloco de contactos da Sobre a
  APIT. Os endereços ficam em Personalizar › APIT — Redes Sociais, não em
  código.
- **LinkedIn substituído por YouTube.** A APIT não tem LinkedIn na lista de
  contas fornecida, e tinha YouTube, que o tema não previa. Mantêm-se quatro
  ícones, como no design.

### Adicionado
- `apit_redes_sociais()`, a lista das redes, e `apit_redes_sociais_html()`, que
  desenha a linha de ícones. Os nomes e os ícones estavam repetidos em quatro
  sítios — o `header.php` tinha a sua própria closure e o `footer.php` e o
  `contactos.php` tinham o markup escrito à mão — pelo que trocar uma rede
  obrigava a quatro edições coerentes entre si. Agora é uma.

### Removido
- `apit_child_social_url()`, que os quatro sítios usavam e que o novo renderizador
  torna desnecessária.

### Corrigido
- Uma rede sem endereço deixa de gerar um ícone com `href="#"`. O valor por
  omissão era `#`, pelo que um campo vazio produzia um ícone que não levava a
  nenhum lado; agora o ícone simplesmente não aparece.

## [0.21.15] - 2026-09-03

### Corrigido
- O contorno branco em volta do campo selecionado era um **`box-shadow`**, não
  um `outline` — vinha do `reset.css` do tema pai, que declara
  `box-shadow: 0 0 0 4px #fff` em `input:focus-visible`. A v0.21.14 anulou o
  `outline` e deixou o halo, que era precisamente o que se via. Agora fica
  apenas o sublinhado em baixo, na cor do botão Subscrever.
- O reset declara também `border-color: #333` em `input[type=text]:focus`, que
  empatava em especificidade com a regra deste ficheiro. Continua a ganhar por
  ordem de carregamento, mas fica registado.
- A mesma regra do reset atinge todos os controlos do formulário, não só os
  campos de texto. A caixa de aceitação passa a marcar-se com o bordo magenta,
  como os campos, e o botão perde o halo mas mantém um contorno fino afastado
  da margem — é o controlo que submete, e quem chega por teclado tem de ver que
  está selecionado antes de premir Enter.

### Notas
- Um clique de rato num campo de texto **faz corresponder `:focus-visible`** nos
  navegadores actuais, porque ali se espera escrita por teclado. Por isso o
  halo aparecia ao clique, e não só na navegação por tabulação — as regras
  cobrem agora os dois selectores.

## [0.21.14] - 2026-09-03

### Alterado
- Removida a caixa de foco dos campos da newsletter. O contorno magenta
  disparava em `:focus`, portanto aparecia a cada clique — e uma moldura em
  volta de um campo que é só sublinhado lia-se como estado de erro.
- Em vez dela, o próprio sublinhado passa a magenta quando o campo está
  selecionado, na mesma cor do botão Subscrever. Serve rato e teclado, e não
  desenha nada em volta do campo.

### Notas
- O foco não pôde ser exercitado no ambiente de verificação: a pane do
  navegador não detém o foco da janela (`document.hasFocus()` é `false`), pelo
  que `:focus` nunca corresponde ali. Confirmado o que era possível — a regra
  existe na folha com `outline-style: none` e `border-bottom-color:
  var(--apit-magenta)`, tem especificidade acima da regra base, e a variável
  resolve para `#f41892` no próprio input. Falta confirmar no navegador.
- Os outros dois estados de foco do tema (setas do carrossel e caixa de
  aceitação) já usavam `:focus-visible`, que nunca corresponde a um clique de
  rato, e por isso não foram tocados.

## [0.21.13] - 2026-09-03

### Alterado
- Os campos da newsletter passam a `border-width: 0 0 2px 0` e
  `border-radius: 0`, por indicação do cliente: sublinhado de 2px em baixo e
  nada nos outros três lados, com cantos rectos. A borda ténue que existia à
  volta deixou de fazer sentido e saiu.

## [0.21.12] - 2026-09-03

### Corrigido
- O título "Subscrever APIT News" tinha um `<br>` no markup, que congelava a
  quebra e a punha no lugar errado em mobile. Passa a ser controlada por
  `max-width: 11ch` — largura suficiente para "Subscrever" e insuficiente para
  "Subscrever APIT", pelo que a linha quebra sempre no mesmo sítio. Em `ch` e
  não em px porque a unidade acompanha o tamanho da fonte: a mesma regra serve
  aos 48px do desktop e aos 30px do telefone, sem nenhum breakpoint a repeti-la.
- Os campos do formulário passam a ter a linha branca em baixo que o design
  tem. A borda continua ténue nos outros três lados.
- A caixa de aceitação passa de 25px a **20x20**, transparente e com bordo
  branco, como no design. Um checkbox nativo não consegue ser nenhuma das
  coisas — o navegador desenha a sua própria caixa, ao seu tamanho, e preenche-a
  ao marcar. O `appearance: none` remove esse desenho, e o visto passa a ser um
  pseudo-elemento.
- Em mobile o botão de submeter volta à direita. Tinha `justify-self: start` no
  breakpoint de 640px, o que o encostava à esquerda quando descia para baixo da
  linha de aceitação.



Só documentação — o tema não mudou.

### Corrigido
- O procedimento de deploy não avisava que **`Deploy HEAD Commit` sozinho não
  traz nada de novo**. O cPanel publica o HEAD do clone que está no servidor,
  não o do GitHub: sem `Update from Remote` primeiro, o deploy repõe a versão
  antiga e o *Commit Date* mostra a data do clone. Foi o que aconteceu na
  primeira subida — o servidor ficou com o tema v0.17.0, de 1 de setembro,
  enquanto a base de dados era de hoje, e os shortcodes da Sobre a APIT
  apareciam em texto cru por não existirem nessa versão. A ordem, agora
  explícita nos dois documentos: **Update from Remote → Deploy HEAD Commit**.
- Acrescentado como confirmar que versão está no ar (`style.css?ver=`) e o
  sintoma que denuncia o problema (nomes de shortcode em texto cru).

## [0.21.10] - 2026-09-03

Só documentação — o tema não mudou.

### Corrigido
- O `DEPLOY.md` dizia que a base de dados "já está exportada" e apontava para
  `apit-bd-para-servidor.sql`. Esse ficheiro é de 1 de setembro e **está
  obsoleto**: não tem a página Sobre a APIT, a equipa, os órgãos sociais, as
  categorias de eventos nem os campos ACF. Subi-lo apagaria tudo isso. O passo
  passa a mandar exportar **no momento de subir**, com os comandos, e avisa que
  os dois ficheiros antigos não servem.
- O procedimento de importação estava incompleto. As tabelas do WordPress
  declaram datas `0000-00-00` por omissão e um MySQL em modo estrito recusa-as
  com `Invalid default value for 'comment_date'` — erro que apanhei ao importar
  o dump numa base de dados de teste. O `wp search-replace --export` não escreve
  a instrução que desliga esse modo, pelo que o passo passa a acrescentá-la.

### Adicionado
- `APIT-subir-producao.pdf` em `Local Sites/apit/` (7 páginas): o passo a passo
  para produção, do lado do cPanel e da base de dados, com a ordem das
  operações, os comandos, a lista de verificação e as armadilhas já
  encontradas. Fica fora do repositório; a fonte é o `DEPLOY.md`.



### Corrigido
- O título "Subscrever APIT News" fica centrado verticalmente com o formulário
  em desktop. Estava alinhado ao topo (`align-items: start`), o que o punha ao
  nível do primeiro campo em vez do meio do bloco.
- Removido o deslocamento de 7px no topo do formulário. Existia para começar os
  campos ligeiramente abaixo do título enquanto os dois estavam alinhados ao
  topo; com o título centrado, só puxava a linha 7px para fora do centro.

### Notas
- Abaixo de 1024px a grelha é de uma coluna e cada item é a sua própria linha,
  onde `align-items` não tem efeito — o empilhado em mobile fica igual.
- A Home perde 7px de altura (2986 → 2979) por causa do deslocamento removido.

## [0.21.8] - 2026-09-03

### Corrigido
- Os Órgãos Sociais passam a ter sempre três colunas, quaisquer que sejam os
  membros da linha. O `auto-fit` colapsava uma linha de dois membros em duas
  colunas largas, e o segundo deixava de alinhar com o membro acima — o
  "Creart" caía a 918px em vez dos 669px do "Fremantle Portugal". Com três
  colunas fixas a terceira célula fica vazia, e um quarto membro continua a
  passar à linha seguinte com a mesma largura.
- Cor da "Direção" corrigida de azul para o roxo da marca (`#8048a6`).
  "Assembleia Geral" fica no azul e "Conselho Fiscal" no magenta, como estavam.
- A coluna do nome do órgão passa de 260px a 180px, o que quebra "Assembleia
  Geral" e "Conselho Fiscal" em duas linhas — como no design — e deixa
  "Direção" numa só.

### Notas
- Em mobile as colunas são duas, também fixas: três deixariam cada membro com
  cerca de 76px, largura em que nenhum nome de empresa cabe.
- As três cores vivem em `apit_get_orgaos()`, em `inc/post-types.php`, e o mapa
  é filtrável. O roxo foi lido do mockup: se o valor exacto for outro, é uma
  linha.

## [0.21.7] - 2026-09-03

### Alterado
- O ícone dos botões de documento passa a usar o SVG carregado na multimédia,
  em vez do PNG.
- O ícone toma agora a cor do texto do botão, incluindo no hover. O SVG deixa
  de ser imagem de fundo e passa a **máscara** pintada com `currentColor`: o
  ícone segue a cor da etiqueta sozinho, sem uma segunda regra a manter em
  sincronia. Como imagem de fundo era impossível — o SVG traz o `#1B2A33`
  embutido no path, e sobre o fundo escuro do hover desaparecia.

### Notas
- A máscara tem como recurso um degradé transparente, que não deixa passar
  nada. Sem ele, um campo de ícone vazio desligava a máscara e pintava um
  quadrado sólido de 20px.
- O PNG `icon-pdf` fica sem uso na multimédia; não foi apagado.
- O WordPress bloqueia o carregamento de SVG por omissão, e este ficheiro não
  passou pela lista de tipos permitidos. Se for preciso carregar mais SVGs pelo
  wp-admin, é preciso permitir o tipo — de preferência com sanitização, porque
  um SVG pode transportar script. Este foi verificado: não tem `<script>`,
  atributos `on*` nem referências externas.

## [0.21.6] - 2026-09-02

### Alterado
- Os botões dos Documentos Institucionais voltam a ser botões normais do
  Elementor, como os do hero da Home: etiqueta e link editam-se na interface do
  Elementor. Estavam num repetidor do ACF, que só é acessível pelo editor do
  WordPress — não serve para quem edita a página no Elementor.
- Acrescentar um documento passa a ser duplicar um botão no Elementor, em vez de
  acrescentar uma linha ao repetidor.

### Adicionado
- `inc/elementor.php`, com duas coisas que o Elementor gratuito não sabe fazer:
  - Um botão que aponte para um ficheiro na multimédia passa a **descarregá-lo**
    em vez de o entregar ao visualizador de PDF do navegador. O campo de link do
    Elementor gratuito não permite atributos, por isso o `download` é
    acrescentado por filtro. Só toca em ficheiros dentro de `uploads` e com
    extensão de documento: num link para outro site o navegador ignoraria o
    atributo, e um link interno deve navegar.
  - O ícone dos botões chega por custom property, porque o selector de ícones do
    Elementor só oferece Font Awesome e o design usa um PNG carregado. Fica
    editável no campo "Ícone dos botões".

### Removido
- Shortcode `apit_sobre_documentos`, o seu template-part e o repetidor
  `sobre_documentos`, que os botões do Elementor substituem.

### Corrigido
- O empilhar dos botões em mobile só funcionava por acidente: o
  `flex-direction: column` perdia para o CSS por página do Elementor, e estas
  duas etiquetas apenas são largas demais para caber lado a lado. Uma etiqueta
  curta teria posto os botões em linha sem ninguém perceber porquê.

## [0.21.5] - 2026-09-02

### Alterado
- Breadcrumb e título do hero da "Sobre a APIT" passam a brancos, em desktop e
  em mobile. O parágrafo mantém-se escuro, por indicação do cliente.
- O degradé do hero deixa de lavar a banda de cima. O vídeo tem luminância
  medida de 0,32 por trás do título, e qualquer lavado o aproxima do branco e
  leva o texto com ele — com o lavado de 50% que este ficheiro tinha, o branco
  caía para 1,6:1, ilegível. Agora o vídeo fica intacto até aos 42% da altura
  do hero (24% em mobile, onde o bloco é mais alto) e clareia depressa a
  seguir, para o parágrafo, o "Documentos Institucionais" e os botões, que
  continuam escuros, ficarem sobre fundo claro.

### Notas
- Contrastes medidos: título branco 2,83:1 e breadcrumb branco 2,70:1 sobre o
  vídeo; parágrafo escuro 10,3:1, título dos documentos 7,9:1 e botões 11,2:1.
  Os escuros estão folgados. Os brancos ficam abaixo dos 4,5:1 das WCAG AA — e
  o título, a 46px, fica a um cabelo dos 3:1 de texto grande. É a mesma
  caracterização que o hero da Home já tem, por isso é coerente com o site,
  mas não é acessível: um véu escuro a 45% na banda de cima levaria o branco a
  4,65:1, ao custo de escurecer visivelmente o topo do hero.

## [0.21.4] - 2026-09-02

### Corrigido
- Rodapé em mobile alinhado com o design: o logo da APIT fica ao lado do menu,
  e os ícones das redes sociais ao lado do lockup Watch Portugal. Estava tudo
  empilhado numa coluna.
- Na barra inferior, os três links legais passam a um por linha, com os
  separadores `/` escondidos, e o crédito Jelly abaixo — como no design.

### Alterado
- `.apit-footer__main` passa de flex a grelha com áreas nomeadas. É o que
  permite ao bloco das redes sociais mudar de vizinho entre breakpoints: em
  desktop fica sob o email, em mobile ao lado do Watch Portugal. Os ícones
  saíram de dentro do bloco de contactos para o poderem fazer — um filho não
  pode sair da linha do pai.
- Os separadores `/` dos links legais passaram de texto a `<span>`, porque um
  nó de texto não se consegue esconder por CSS.

### Notas
- A grelha usa `space-between` com pistas automáticas e sem intervalo de coluna,
  o que distribui o espaço livre exactamente como a linha flex que substitui:
  as posições em desktop ficaram iguais, com os ícones 4px abaixo do que
  estavam e o lockup 1px à esquerda.

## [0.21.3] - 2026-09-02

### Alterado
- Mobile da "Sobre a APIT" alinhado com o Figma (nó `66:2508`):
  - Equipa passa a um membro por linha, com o retrato a 280px. A dois por
    linha o retrato caía para 161px, metade do que o design lhe dá — e o
    retrato é o assunto da secção.
  - Órgãos Sociais deixa de empilhar: o nome do órgão fica ao lado dos
    membros, em coluna de 100px que deixa "Assembleia Geral" e "Conselho
    Fiscal" a quebrar em duas linhas, como no design. Os membros ficam em
    duas colunas de 106px à direita.
  - O wordmark "About Us" fica escondido. Sem espaço ao lado do texto só
    podia ficar atrás do título, e aí é um segundo bloco de tipografia a
    competir com ele em vez da textura que é num ecrã largo.
  - A faixa da Internacionalização termina no botão, sem o lockup Watch
    Portugal — a mesma marca que o rodapé já carrega poucos ecrãs abaixo.
- Em tablet o nome do órgão fica numa coluna de 180px, também ao lado dos
  membros.

### Notas
- O Figma mobile é anterior às notas da cliente e mostra ainda os logos dos
  associados, os logos dos órgãos sociais e os quatro círculos diferentes. Fica
  o que a cliente decidiu, que é posterior.
- Mantive o título "Contactos" em mobile, que no mockup não se distingue. Se
  for para sair, é uma regra.

## [0.21.2] - 2026-09-02

### Adicionado
- Campo "Ícone dos botões" em Sobre a APIT › Documentos, ligado ao `icon-pdf`
  da multimédia. Substitui o glifo do Font Awesome nos botões de documento; sem
  imagem, o glifo volta, para nenhum botão ficar com um espaço vazio.
- Campo "Imagem à direita" em Sobre a APIT › Internacionalização, ligado ao
  `sobre-apit-whatch-portugal` da multimédia (589×262, contra os 356×158 da
  cópia que vinha no tema). Sem imagem, usa a do tema.

### Corrigido
- O lockup Watch Portugal era esticado de 589 para 622px. O `max-width` tem a
  especificidade de uma classe, igual à do `img { max-width: 100% }` do
  Elementor, que é impresso depois deste ficheiro e ganhava o empate. Passa a
  ter duas classes.
- Removida a regra que limitava o lockup a 300px em mobile: nunca se aplicou,
  pelo mesmo empate. Fica com a largura da coluna de conteúdo, que num
  telefone lê melhor.

## [0.21.1] - 2026-09-02

### Corrigido
- O carrossel do calendário mostrava uma fatia de 33px do quarto cartão. A
  pista tinha uma margem negativa que a fazia sangrar até à margem do ecrã,
  para o cartão seguinte espreitar — mas o design pede três, e as setas são
  já a indicação de que há mais. Sem a sangria, os três cartões e os seus dois
  intervalos enchem a pista exactamente e o quarto começa fora dela.
- As larguras dos cartões passam a ser uma percentagem da própria pista em vez
  de `100vw`. O `100vw` conta a barra de scroll e a pista não, o que em tablet
  deixava o segundo cartão cortado 15px.

## [0.21.0] - 2026-09-02

### Adicionado
- Taxonomia "Categorias de evento", em Eventos › Categorias. A categoria era
  texto livre reescrito em cada evento, e a sua cor vivia num mapa em PHP — uma
  categoria nova exigia um programador. Agora acrescenta-se no wp-admin, e o
  próprio campo do evento permite criar uma sem sair do ecrã.
- Dois color pickers por categoria: cor inicial e cor final do degradé. O
  ângulo do degradé e a posição das paragens continuam iguais em todos os
  cartões — só as duas cores mudam. A etiqueta sobre o cartão e a faixa do mês
  no badge usam a cor inicial, esta última num tom 18% mais escuro.
- As três categorias em uso foram criadas com as cores que já estavam em
  código: Evento APIT `#2ec6b0`, Evento Internacional `#f41892` e Stand APIT
  `#4a85c8`, todas a terminar no cinzento `#e9edf0` em que os cartões já
  acabavam. Os cartões ficam visualmente iguais; a diferença é quem manda nas
  cores.

### Removido
- Meta `apit_evento_categoria` e as três entradas de evento no mapa
  `apit_categoria_cores()`, substituídas pela taxonomia. O mapa fica só com as
  categorias de notícias, que continuam a usá-lo.
- Suportes `editor` e `thumbnail` no tipo de conteúdo Evento: eram declarados e
  nunca lidos, e sem página de evento não há onde um corpo de conteúdo
  apareceria. O cartão usa o título e o excerto.
- Regras CSS `.pill` e `.pill--categoria` — a primeira versão da etiqueta do
  cartão, substituída por `.evento-card__categoria` e sem uso em template,
  script ou dados do Elementor.
- Filtro que sugeria as categorias com cor mapeada, sem sentido agora que a
  escolha é um select de termos.

### Alterado
- Evento passa ao editor clássico (`show_in_rest => false`), como Equipa e
  Órgãos Sociais: sem suporte de editor, o editor de blocos mostrava uma tela
  vazia acima dos campos.
- A caixa de taxonomia própria do WordPress está desligada (`meta_box_cb`), para
  não haver dois controlos sobre o mesmo valor — o campo do ACF é o único.

### Notas
- O carrossel do calendário já mostrava 3 cartões em desktop e revelava os
  restantes por arrasto ou setas, pelo que não foi alterado: cartão de 409px,
  376px para deslizar com 4 eventos, e as setas desactivam-se nos extremos. Em
  mobile mostra 1 cartão com o seguinte à vista.

## [0.20.3] - 2026-09-02

### Corrigido
- Os eventos passados não saíam do calendário. A consulta não filtrava por
  data e, por ordenar ascendente, os já realizados apareciam **primeiro** no
  carrossel. Passa a devolver só de hoje em diante: o cartão mantém-se no dia
  do evento e sai na manhã seguinte.
- O corte usa `current_time`, pelo que muda à meia-noite no fuso do site e não
  em UTC. Um evento sem data passa a ser excluído em vez de ordenado no fim —
  a data é campo obrigatório, e sem ela a entrada não está pronta para
  aparecer.

### Notas
- Sem eventos futuros a secção do calendário desaparece por inteiro, o que já
  era o comportamento do template.
- Eventos de vários dias não estão modelados: só existe uma data, pelo que um
  evento de 3 dias sai no dia seguinte ao da data indicada. Se for preciso,
  acrescentar uma data de fim e usá-la no corte.

## [0.20.2] - 2026-09-02

### Alterado
- O título dos cartões do calendário deixa de ser um link. Não existe página
  de evento, pelo que levava a uma página sem estilo.
- O botão do cartão passa a exigir link, além do texto: caía no permalink do
  evento quando o link estava vazio, o que dava no mesmo sítio.
- Os eventos passam a tipo de conteúdo não público (`public`,
  `publicly_queryable` e `has_archive` a `false`). Sem template de evento,
  `/eventos/<slug>/` servia uma página sem estilo e indexável — agora devolve
  404. Continuam editáveis no wp-admin e a alimentar o calendário da Home.

### Por fazer, relacionado
- Se vierem a existir páginas de evento, voltar a ligar `public` e
  `has_archive` em `inc/post-types.php`, actualizar as permalinks e repor o
  link no título do cartão.

## [0.20.1] - 2026-09-02

### Corrigido
- Os campos de link recusavam caminhos internos. Estavam declarados com o tipo
  `url` do ACF, que exige um esquema e rejeitava `/contactos/` — precisamente o
  formato que se deve guardar, porque acompanha o site quando muda de domínio,
  ao contrário de um endereço completo, que teria de ser substituído na base de
  dados. Passam a texto com validação própria (`Link do botão` nos Eventos, nos
  Associados, na Internacionalização e nos Documentos).
- A validação que substitui a do ACF aceita caminhos internos (`/contactos/`),
  âncoras (`#secao`), queries, endereços protocolo-relativos, endereços
  completos, `mailto:` e `tel:`, e recusa o resto com uma mensagem que diz o
  que fazer. Esquemas fora de `wp_allowed_protocols` — `javascript:`, `data:` —
  continuam barrados, pelo que dispensar o tipo `url` não significou dispensar
  validação.

## [0.20.0] - 2026-09-02

### Adicionado
- ACF Pro 6.8.9 como dependência, com os grupos de campos em Local JSON
  (`acf-json/` dentro do tema). O ACF lê-os dessa pasta em cada pedido, pelo
  que um campo criado no local passa a existir no servidor no mesmo commit que
  o template que o usa. O plugin é licenciado, não vai no git, e por isso
  continua a ser instalado à mão nas duas pontas — registado no `DEPLOY.md`.
- Repetidor para os Documentos Institucionais: eram dois botões fixos no
  Elementor, passam a linhas editáveis com etiqueta e ficheiro (ou link), pelo
  que um terceiro documento não obriga a alterar código.
- Quatro grupos de campos: Equipa (cargo), Órgãos Sociais (órgão e função),
  Eventos (data, categoria, local, botão) e os conteúdos da Sobre a APIT
  (documentos, círculos, associados, internacionalização, contactos), este
  último organizado por separadores.
- Helper `apit_campo()`, que devolve `null` se o ACF não estiver instalado: um
  deploy sem o plugin faz as secções renderizarem vazias em vez de derrubar a
  página com um erro fatal.

### Alterado
- Os textos das secções da Sobre a APIT saíram dos atributos de shortcode para
  campos ACF na página. Editar uma frase deixa de obrigar a entrar no Elementor
  e mexer numa linha que parece código. Os shortcodes ficaram sem atributos.
- O endereço dos contactos é agora uma área de texto com linhas reais, o que
  dispensa o `|` que marcava a mudança de linha por um atributo de shortcode
  não poder conter um newline.
- Os selects de Órgão e as sugestões de Categoria são preenchidos em tempo de
  execução a partir de `apit_get_orgaos()` e `apit_categoria_cores()`, para não
  haver uma segunda lista a divergir dentro do grupo de campos.
- Datas dos eventos normalizadas para `Ymd`, o formato que o date picker do ACF
  grava. Misturar com o `Y-m-d` anterior teria desordenado o calendário, que
  ordena pelo valor cru do campo — `"2"` ordena depois de `"-"`.
- As meta boxes escritas à mão na v0.19.1 foram removidas: o ACF passa a
  desenhar os mesmos campos, e duas interfaces para o mesmo campo seriam
  confusas e podiam entrar em conflito na gravação. As chaves de meta não
  mudaram, pelo que o frontend não precisou de alterações. Equipa e Órgãos
  Sociais ficam no editor clássico: continuam sem corpo de conteúdo, e o
  editor de blocos mostraria uma tela vazia acima dos campos.

### Corrigido
- Atributo `datetime` dos cartões do calendário: com a data guardada em `Ymd`
  ficava inválido para HTML, e passa a ser convertido para `Y-m-d` na saída.

## [0.19.1] - 2026-09-02

### Corrigido
- Os campos personalizados não tinham onde ser editados no wp-admin.
  `register_post_meta` declara o campo e a sua sanitização, mas não desenha
  controlo nenhum, pelo que só eram graváveis por código — o cargo da equipa
  incluído. Adicionadas caixas de edição para Equipa (cargo), Órgãos Sociais
  (órgão e função) e Eventos (data, categoria, local, texto e link do botão).
  Os Eventos tinham o mesmo problema desde a v0.6.0, ainda sem ter sido notado.
- Equipa e Órgãos Sociais passam a usar o editor clássico
  (`show_in_rest => false`): não têm corpo de conteúdo, e o editor de blocos
  mostrava uma tela vazia acima dos campos que interessam.

### Adicionado
- Colunas na listagem de cada tipo de conteúdo (cargo, órgão, função, data,
  categoria), para se ver quem tem que função sem abrir cada entrada.

## [0.19.0] - 2026-09-02

### Adicionado
- Página "Sobre a APIT" (nó `44:21610`), montada na página 6 já existente — a
  mesma para onde o hero da Home aponta. Sete secções: hero com vídeo, círculos,
  Equipa APIT, Associados, Órgãos Sociais, Internacionalização, Contactos e
  newsletter.
- Vídeo `sobre-a-apit.mp4` no fundo do hero, pelo shortcode
  `[apit_hero_media]` já usado na Home, com o degradé desta página a lavar o
  vídeo até ao fundo branco em vez do lavado colorido da Home.
- Tipos de conteúdo `apit_equipa` (nome, cargo, fotografia) e
  `apit_orgao_social` (nome, função, órgão), ambos privados: só aparecem dentro
  desta página. Preenchidos com os 4 membros da equipa e os 8 dos órgãos
  sociais.
- Grelha dos órgãos sociais em `auto-fit`: um quarto ou quinto membro passa à
  linha seguinte com a mesma largura de coluna, em vez de estreitar a linha.
- Mapa interativo em Contactos, pelo endpoint de embed do Google construído a
  partir do endereço. Sem chave de API e sem plugin, e como o URL é montado no
  render sobrevive à mudança de domínio.
- Shortcodes `apit_sobre_hero_decor`, `apit_sobre_pilares`, `apit_equipa`,
  `apit_associados`, `apit_orgaos_sociais`, `apit_internacionalizacao` e
  `apit_contactos`.
- Variante `.btn--escuro` para botões de contorno sobre fundo claro: o
  `.btn--outline` é branco, feito para o hero da Home, e aqui desaparecia.
- CSS da página em `assets/css/sobre.css`, carregado só nesta página. Mantém o
  `style.css` intocado e evita que uma alteração aqui faça regredir a Home.

### Alterado
- Notas do cliente aplicadas antes da construção, não como correção depois:
  Associados sem os logos (fica o rótulo e o botão, o que anula o carrossel que
  estava previsto), Órgãos Sociais sem logos (nome e função), e os quatro
  círculos todos com o mesmo tratamento (o `bola4` da multimédia) a 140px em
  vez dos 183px do design, por criarem ruído visual ao tamanho original.
- O espaçamento vertical daquelas duas secções foi recomposto: o do design
  tinha sido desenhado para o volume dos logos.

### Corrigido
- Camadas do hero: os wrappers dos widgets de shortcode passam a `position:
  static`, senão o wrapper (relativo e de altura zero) tornava-se o bloco
  contentor e o vídeo com `inset: 0` colapsava para 0px de altura.
- Scroll horizontal de 55px: o `overflow: hidden` do hero era reposto a
  `visible` pelo CSS por página do Elementor, impresso depois deste ficheiro.
- Alinhamento do título de Contactos: a percentagem em `padding-left` resolve
  contra a largura do pai, não do elemento, o que empurrava o bloco 712px para
  a direita e esmagava o mapa.

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
