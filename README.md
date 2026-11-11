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

Leva poucos segundos. Para forçar uma atualização sem enviar nada, use **Redeliver** na última entrega em
Settings → Webhooks → Recent Deliveries (abrir o endereço no navegador pode devolver uma resposta em cache).
O histórico de atualizações fica em `deploy.log`, na pasta acima do site no servidor.
Os arquivos `README.md` e `.gitignore` ficam só aqui no GitHub.
