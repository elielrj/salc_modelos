% Modelos & Controle de Pregões e Contratos

Central de trabalho para a SALC que reúne modelos de documentos e ferramentas para consulta, seleção e consolidação de informações de compras públicas (Pregões, ARP/Atas, Itens, Contratos), além de links úteis para processos digitais (SPED 3.0).

A aplicação é totalmente em PHP (sem frameworks), com UI em Bootstrap, e expõe endpoints internos que consomem Dados Abertos do Compras.gov.br e serviços do TCU.

## Visão Geral
- UI em abas com foco em tarefas da SALC: UGs, Pregão, Carona, Dispensa, Requisitórias, Termo de Referência, Itens de Pregão, Atas, Contratos (itens) e Listar Contratos (resumo), além do SPED 3.0.
- Consumo de Dados Abertos com cache local e retry/backoff para robustez contra rate limit e instabilidade.
- Seleção de linhas e somatórios para facilitar consolidação (copiar/colar em planilhas ou imprimir PDF das seleções).
- Filtros por Compra/Ano, busca textual (descrição/fornecedor), ordenação de colunas e indicadores de situação SICAF.
- Emissão de certidão do TCU em PDF por CNPJ diretamente da UI.

## Principais Funcionalidades (por aba)
- UGs
  - Carrega lista a partir de CSV local (`data/ugs.csv`) e permite ordenação por colunas.
- Pregão
  - Central de links para DFD, ETP, Matriz de Riscos, Relatório de Preço e Mapa Comparativo.
- Carona
  - Links para modelos (Capa, Índice, Checklist, Abertura, Encerramento) e documentos do Demandante.
- Dispensa
  - Links para modelos de Requisitória/Termo específicos.
- Requisitórias
  - Links para modelos prontos por modalidade (Pregão, Carona, Dispensa).
- Termo de Referência
  - Links para modelos de TR (compras/serviços/TI). 
- Itens de Pregão
  - Consulta Itens de ARP via Dados Abertos (UASG selecionável).
  - Deduplicação por compra/ano/item; ordenação, filtros por Compra/Ano e busca textual.
  - Seleção de itens com cálculo de “Qtde a comprar” e total da seleção; copiar tabela ou imprimir.
  - Link rápido para Certidão TCU (por CNPJ) e indicadores de situação SICAF (Regular/Restrição/Desconhecido).
- Lista de Atas
  - Consulta Atas da UASG (janela de vigência -365 a +365 dias).
  - Ordenação por valor/data, seleção de atas com soma total; copiar/Imprimir seleção.
- Contratos — Itens
  - Consulta itens de contratos via Dados Abertos (janelas anuais de 2012 até o ano atual).
  - Filtros por Compra/Ano e por Contrato, busca textual, ordenação; seleção de linhas com totalizador e ações de copiar/imprimir.
- Listar Contratos (resumo)
  - Lista contratos (sem detalhar itens), com filtros por Compra/Ano e Nº do Contrato, seleção e totalização.
- SPED 3.0
  - Central de links e materiais para processos digitais (abertura, acompanhamento, empenho, etc.).

## Endpoints Internos (API)
- `api/ugs.php`
  - Lê `data/ugs.csv` e retorna JSON com `codug`, `sigla`, `cma`, `cidade_estado`.
- `api/itens.php`
  - Chama `dadosabertos.compras.gov.br/modulo-arp/2_consultarARPItem` via `ARPItensClient`.
  - Janela padrão: vigência de -365 a +365 dias; deduplica por compra/ano/item; mapeia campos via `app/models/Item.php`.
  - Parâmetros: `uasg` (opcional, padrão em `.env`).
- `api/atas.php`
  - Tenta múltiplos endpoints do módulo ARP (Atas) via `ARPAtaClient` para maior compatibilidade.
  - Janela padrão: vigência de -365 a +365 dias; deduplicação por chaves de compra/ata/id.
- `api/contratos.php`
  - Consulta itens de contratos (`modulo-contratos/2_consultarContratosItem`) em janelas anuais de 2012 ao ano atual, com retry/backoff e cache; deduplica por chave (contrato/compra/ano/item/CNPJ/valor unit.).
- `api/contratos_listar.php`
  - Lista contratos (resumo) por janelas anuais (`modulo-contratos/1_consultarContratos`), deduplica por `numeroContrato|numeroCompra`.
- `api/certidao.php`
  - Emite a Certidão do TCU (PDF) por CNPJ válido (14 dígitos), consumindo `https://certidoes-apf.apps.tcu.gov.br/...`. Faz tentativa direta em PDF e fallback por JSON/base64.

## Robustez de Rede
- Cliente HTTP com cache em `/tmp` e retries exponenciais com jitter.
- Respeita `Retry-After` e mensagens “Try again in N seconds” quando presentes.
- Opções tunáveis por `.env`: `CACHE_TTL`, `REQUEST_DELAY_MS`, `MAX_RETRIES`, `BASE_BACKOFF`.

## Estrutura do Projeto
- Interface
  - `index.php`: layout base e roteamento das abas.
  - `views/`: componentes por aba (tabelas, filtros, botões de copiar/imprimir).
  - `assets/css/app.css`: estilos para tabelas, filtros e responsividade.
  - `assets/js/app.js`: carregamento dos dados (fetch APIs), ordenação, filtros, seleção, totalizadores, impressão.
- Backend
  - `api/`: endpoints REST em PHP que agregam e saneiam dados.
  - `app/api/`: clientes HTTP para Dados Abertos (ARP/Atas/Itens/Contratos) e TCU.
  - `app/lib/HttpClient.php`: GET com retry/backoff, cache e parsing JSON.
  - `app/models/`: mapeamento e padronização dos registros (ex.: `Item`, `Ata`).
  - `app/config.php`, `app/Env.php`, `app/polyfills.php`: configuração, leitura de `.env` e compatibilidade com PHP 7.4.
- Dados
  - `data/ugs.csv`: base local de UGs consumida por `api/ugs.php`.

## Configuração e Execução
- Requisitos
  - Docker e Docker Compose (ou PHP 7.4+ com curl habilitado).
- Variáveis de ambiente (`.env`)
  - `UASG` (padrão `160517`)
  - `TIMEZONE` (padrão `America/Sao_Paulo`)
  - `CACHE_TTL` (segundos, padrão `600`)
  - `REQUEST_DELAY_MS` (milissegundos entre páginas, padrão `200`)
  - `MAX_RETRIES` (padrão `6`)
  - `BASE_BACKOFF` (padrão `1.0`)
- Subir via Docker
  1. Copie `.env.example` para `.env` e ajuste conforme necessário.
  2. Execute: `docker compose up`.
  3. Acesse: `http://localhost:8080`.

## Fontes de Dados Externas
- Compras.gov.br — Dados Abertos (ARP Itens/Atas/Contratos)
- Transparência Comprasnet (endereços públicos quando aplicável)
- TCU — Certidões APF (emissão de PDF)

## Observações
- Não há persistência de banco de dados; o cache é temporário em arquivo.
- As consultas de contratos percorrem janelas anuais de 2012 ao ano atual — podem levar tempo e respeitam espera aleatória entre janelas para mitigar rate limit.
- As ações de “Copiar” usam o clipboard do navegador; a de “Imprimir” gera visualização adequada para PDF.

---
Se quiser, posso adaptar o README com screenshots, GIFs de uso ou instruções específicas do seu ambiente.

## Como Usar
1) Acesse `http://localhost:8080` e navegue pelas abas conforme a tarefa.
2) Em “Itens de Pregão”, selecione a UASG desejada (ou use a padrão), aguarde o carregamento e use:
   - Filtros por Compra/Ano e busca por descrição/fornecedor.
   - Ordenação clicando nos cabeçalhos das colunas.
   - Seleção de linhas e definição de “Qtde a comprar”; o totalizador é atualizado automaticamente.
   - Botões “Copiar” (cola em planilhas) e “Imprimir” (gera PDF do navegador).
3) Em “Lista de Atas”, use a seleção de atas para montar um consolidado e exportar por copiar/imprimir.
4) Em “Contratos — Itens” e “Listar Contratos”, aplique filtros, busque por texto, selecione linhas e exporte.
5) Clique no ícone do livro na coluna do fornecedor para abrir a Certidão TCU (PDF) pelo CNPJ.

## Capturas de Tela
Adicione imagens nas rotas abaixo para que apareçam neste README (os links já estão prontos):

![Home](assets/screenshots/home.png)
![Itens de Pregão](assets/screenshots/itens.png)
![Itens — Seleção e Total](assets/screenshots/itens-selecao.png)
![Lista de Atas](assets/screenshots/atas.png)
![Contratos — Itens](assets/screenshots/contratos-itens.png)
![Listar Contratos](assets/screenshots/contratos-listar.png)
![SPED 3.0](assets/screenshots/sped.png)

Coloque os arquivos de imagem em `assets/screenshots/` com os nomes acima. PNG/JPG são aceitos.

## GIFs de Uso (opcional)
Sugestões de fluxos para gravar (Peek/OBS/ScreenToGif):
- `assets/screenshots/fluxo-itens.gif`: filtro por Compra/Ano, busca por texto, seleção de linhas e impressão.
- `assets/screenshots/fluxo-contratos.gif`: filtros, seleção e totalização, cópia para planilha.

Inclua os GIFs nos caminhos acima para que os links a seguir funcionem:

![Fluxo: Itens](assets/screenshots/fluxo-itens.gif)
![Fluxo: Contratos](assets/screenshots/fluxo-contratos.gif)

## Exemplos de API (curl)
Substitua a porta se necessário. As respostas foram reduzidas para brevidade.

Itens de ARP (UASG padrão):
```
curl http://localhost:8080/api/itens.php | jq '. | {uasg, total, exemplo: .itens[0]}'
```

Atas (janela de -365 a +365 dias):
```
curl http://localhost:8080/api/atas.php | jq '{uasg, total, exemplo: .atas[0]}'
```

Contratos — Itens (janelas anuais 2012–ano atual):
```
curl http://localhost:8080/api/contratos.php | jq '{filtros, total, exemplo: .contratos[0]}'
```

Listar Contratos (resumo):
```
curl http://localhost:8080/api/contratos_listar.php | jq '{filtros, totalRegistros, exemplo: .resultado[0]}'
```

Certidão TCU (PDF inline):
```
curl -I "http://localhost:8080/api/certidao.php?cnpj=00000000000191"
```

## Dicas e Solução de Problemas
- Sem dados ou lentidão: pode ser rate limit dos Dados Abertos. Ajuste `CACHE_TTL`, `REQUEST_DELAY_MS`, `MAX_RETRIES` e `BASE_BACKOFF` no `.env`.
- “Falha ao obter certidão do TCU”: verifique se o CNPJ tem 14 dígitos; o serviço do TCU pode estar instável. Há fallback por JSON/base64.
- Ordenação não muda: verifique se `assets/js/app.js` foi carregado (console do navegador) e se não há extensões bloqueando scripts.
- Atualização de listas: algumas guias fazem carga sob demanda (ao abrir a aba). Clique na aba novamente para recarregar.
- Contratos demorados: a coleta é por janelas anuais desde 2012, com esperas aleatórias entre janelas — execução pode levar minutos.
