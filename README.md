# SALC Modelos

Este repositório distribui apenas o módulo de **Modelos** utilizado pela SALC.  
Todo o conteúdo dinâmico de itens, atas e contratos foi extraído para um pacote independente.

## Visão geral
- `index.php` (raiz) faz apenas o roteamento inicial, redirecionando para `modelos/`.
- `modelos/` contém a interface em abas com todos os modelos de processos e documentos.
- `modelos/views/` reúne os fragmentos HTML individuais de cada modelo.
- `modelos/assets/` inclui a folha de estilos utilizada nas páginas de modelos.

Na interface principal existe um único botão que leva o usuário ao módulo de **Itens & Consultas**, agora hospedado em outro servidor/container (`http://10.34.156.121/`).

## Pacote Itens & Consultas
Para facilitar a implantação do módulo dinâmico em infraestrutura separada, o pacote completo permanece neste repositório dentro da pasta `itens_consultas/`.

Esse diretório é auto-suficiente (contém APIs, assets, `docker-compose.yml`, `.env.example`, etc.) e pode ser movido integralmente para outro local ou repositório.  
Consulte `itens_consultas/README.md` para instruções detalhadas de configuração e execução em container.

## Desenvolvimento
1. Edite os modelos diretamente em `modelos/views/`.
2. Ajustes visuais podem ser feitos em `modelos/assets/css/app.css`.
3. Não há dependências de backend dentro do módulo de modelos; o conteúdo é estático.

## Implantação
- Servir `index.php` e a pasta `modelos/` é suficiente para disponibilizar este módulo.
- Caso deseje disponibilizar também o módulo dinâmico, mova `itens_consultas/` para a infraestrutura desejada e siga as instruções do README específico.
