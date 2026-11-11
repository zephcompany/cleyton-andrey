# Engenharia de Cartas de Crédito

Landing page publicada em https://engenhariadascartasdeconsorcio.com

Site estático (HTML + imagens). Cada alteração enviada para a branch `main` é publicada automaticamente na Hostinger.

- `index.html` — a página inteira (estilos e scripts inclusos)
- `assets/` — imagens e logos
- Link de checkout: constante `CHECKOUT_URL` no topo do `index.html`

## Deploy

O arquivo `.github/workflows/deploy.yml` envia o site para a Hostinger (via SSH + rsync) a cada `push` na `main`.
Ele precisa do secret `HOSTINGER_SSH_KEY` em **Settings → Secrets and variables → Actions**, com a chave privada de deploy.
Para publicar à mão: aba **Actions → Deploy na Hostinger → Run workflow**.

Último deploy configurado em 11/11/2026.
