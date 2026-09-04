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
- Confirmar as cores das categorias de notícias com o design (foram propostas
  a partir da paleta da marca, por o Figma estar em limite de chamadas). As dos
  eventos deixaram de estar em código: são campos na categoria.
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
