<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Se for uma requisição OPTIONS (preflight do CORS), retorna 200 e para
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/src/routes/api.php';

if (isset($_GET['url'])) {
    // Modo Apache com .htaccess
    $url = $_GET['url'];
} else {
    // Modo servidor embutido do PHP (php -S)
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $url = ltrim($uri, '/');
}

// Pega o método HTTP (GET, POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];

// Cria o router e processa a requisição
$router = new Router();
$router->route($url, $method);
