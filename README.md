# Engenharia de Cartas de Crédito

Landing page publicada em https://engenhariadascartasdeconsorcio.com

Site estático (HTML + imagens).

- `index.html` — a página inteira (estilos e scripts inclusos)
- `assets/` — imagens e logos
- Link de checkout: constante `CHECKOUT_URL` no topo do `index.html`

## Deploy

Cada envio para a branch `main` vai para o ar sozinho:
o webhook do GitHub chama `https://engenhariadascartasdeconsorcio.com/deploy.php`,
que baixa a `main` deste repositório (público) e atualiza os arquivos na Hostinger.

Para forçar uma atualização, basta abrir esse endereço no navegador.
Os arquivos `README.md` e `.gitignore` ficam só aqui no GitHub.
