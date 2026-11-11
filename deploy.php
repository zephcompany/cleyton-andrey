<?php
/*
 * Atualiza o site com a versão mais recente da branch main do GitHub.
 * O GitHub chama este endereço (webhook) a cada envio; também dá para abrir no navegador.
 * O repositório é público, então não há senha nem chave envolvida.
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
ignore_user_abort(true);
set_time_limit(120);

const ZIP_URL  = 'https://codeload.github.com/zephcompany/cleyton-andrey/zip/refs/heads/main';
const IGNORAR  = ['README.md', '.gitignore', '.github'];   // ficam só no GitHub
const PRESERVAR = ['.htaccess', '.well-known', '.user.ini']; // nunca são apagados do servidor

$site  = __DIR__;                      // public_html
$base  = dirname(__DIR__);             // pasta acima, fora do site
$trava = $base . '/.deploy-trava';
$zip   = $base . '/.deploy-main.zip';
$novo  = $base . '/.deploy-novo';
$log   = $base . '/deploy.log';

function sair(int $code, string $msg, string $log): void {
    http_response_code($code);
    @file_put_contents($log, date('c') . " [$code] $msg\n", FILE_APPEND);
    exit($msg . "\n");
}
function apagar(string $p): void {
    if (is_link($p) || is_file($p)) { @unlink($p); return; }
    if (!is_dir($p)) return;
    foreach (scandir($p) as $f) if ($f !== '.' && $f !== '..') apagar("$p/$f");
    @rmdir($p);
}
function listar(string $raiz, string $rel = ''): array {
    $out = [];
    foreach (scandir("$raiz/$rel") as $f) {
        if ($f === '.' || $f === '..') continue;
        $r = ltrim("$rel/$f", '/');
        $out[] = $r;
        if (is_dir("$raiz/$r")) $out = array_merge($out, listar($raiz, $r));
    }
    return $out;
}

// 1) baixa o zip da main (sempre a main deste repositório, nunca outro endereço).
// Se o webhook informou o commit novo, só serve como referência para esperar o
// pacote da main ficar atualizado no GitHub; o commit em si nunca é baixado direto.
$esperado = null;
$payload = json_decode($_POST['payload'] ?? (file_get_contents('php://input') ?: 'null'), true);
if (is_array($payload) && ($payload['ref'] ?? '') === 'refs/heads/main'
    && isset($payload['after']) && preg_match('/^[0-9a-f]{40}$/', (string) $payload['after'])) {
    $esperado = $payload['after'];
}
// responde logo (o GitHub desiste depois de 10 s) e continua o trabalho em segundo plano
if ($esperado !== null) {
    http_response_code(202);
    echo "Recebido, atualizando para " . substr($esperado, 0, 7) . ".\n";
    if (function_exists('litespeed_finish_request')) litespeed_finish_request();
    elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    else { @ob_flush(); flush(); }
}

// uma atualização por vez: as seguintes esperam a anterior terminar
$tr = fopen($trava, 'c');
flock($tr, LOCK_EX);

$versao = '?';
for ($tentativa = 1; $tentativa <= 15; $tentativa++) {
    $fp = fopen($zip, 'wb');
    $ch = curl_init(ZIP_URL);
    curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60, CURLOPT_FAILONERROR => true, CURLOPT_USERAGENT => 'deploy-hostinger']);
    $ok = curl_exec($ch); $err = curl_error($ch); curl_close($ch); fclose($fp);
    if (!$ok) sair(502, "Falha ao baixar do GitHub: $err", $log);
    $z = new ZipArchive();
    if ($z->open($zip) !== true) sair(500, 'Zip inválido.', $log);
    $versao = trim((string) $z->getArchiveComment());   // o GitHub grava o commit no comentário do zip
    if ($esperado === null || $versao === $esperado) break;
    $z->close();
    sleep(4);                                             // pacote ainda antigo: espera e tenta de novo
}

// 2) extrai numa pasta temporária
apagar($novo); mkdir($novo, 0755, true);
$z->extractTo($novo); $z->close(); @unlink($zip);
$pastas = array_values(array_filter(scandir($novo), fn($f) => $f[0] !== '.'));
if (count($pastas) !== 1 || !is_file("$novo/{$pastas[0]}/index.html")) sair(500, 'Conteúdo inesperado no zip (sem index.html).', $log);
$fonte = "$novo/{$pastas[0]}";

$ignorado = fn(string $r) => in_array(explode('/', $r)[0], IGNORAR, true);
$preservado = fn(string $r) => in_array(explode('/', $r)[0], PRESERVAR, true);

// 3) copia o que mudou
$copiados = 0;
foreach (listar($fonte) as $r) {
    if ($ignorado($r)) continue;
    if (is_dir("$fonte/$r")) { if (!is_dir("$site/$r")) mkdir("$site/$r", 0755, true); continue; }
    if (!is_file("$site/$r") || md5_file("$fonte/$r") !== md5_file("$site/$r")) {
        if (!copy("$fonte/$r", "$site/$r.tmp") || !rename("$site/$r.tmp", "$site/$r")) sair(500, "Falha ao gravar $r", $log);
        $copiados++;
    }
}

// 4) remove do servidor o que saiu do repositório
$removidos = 0;
foreach (array_reverse(listar($site)) as $r) {
    if ($preservado($r) || $ignorado($r)) continue;
    if (!file_exists("$fonte/$r")) { apagar("$site/$r"); $removidos++; }
}
apagar($novo);

sair(200, "Site atualizado (" . substr($versao, 0, 7) . "): $copiados arquivo(s) novo(s) ou alterado(s), $removidos removido(s).", $log);
