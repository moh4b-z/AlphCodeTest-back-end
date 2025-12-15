<?php
function carregarEnv() {
    $envFile = __DIR__ . '/../../.env';
    
    if (!file_exists($envFile)) {
        die("Erro: Arquivo .env não encontrado. Copie o .env.example e configure.");
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignora comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}
carregarEnv();

function conectar() {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    $db = $_ENV['DB_DATABASE'] ?? 'db_contatos';
    
    $servername = "$host:$port";

    $conn = new mysqli($servername, $username, $password, $db);
    $conn->set_charset('utf8');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
