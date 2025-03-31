<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configurações do banco de dados
$host = 'srv1844.hstgr.io';
$dbname = 'u492577848_react';
$username = 'u492577848_react';
$password = 'Kellys0n_123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Erro de conexão com o banco de dados']));
}

// Recebe os dados do POST
$data = json_decode(file_get_contents('php://input'), true);
$matricula = $data['matricula'] ?? '';
$senha = $data['senha'] ?? '';

// Obtém o IP do cliente
$ip_address = $_SERVER['REMOTE_ADDR'];

// Valida as credenciais
$stmt = $pdo->prepare("SELECT * FROM users WHERE matricula = ?");
$stmt->execute([$matricula]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$status = 'credenciais_invalidas';
$error_message = 'Matrícula ou senha incorretos';
$response = ['success' => false, 'error' => $error_message];

if ($usuario && password_verify($senha, $usuario['password'])) {
    $status = 'sucesso';
    $error_message = null;

    // Remove informações sensíveis
    unset($usuario['password']);

    $response = [
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'usuario' => $usuario
    ];
}

// Registra o log
$stmt = $pdo->prepare("INSERT INTO logs_login (matricula, ip_address, status, error_message) VALUES (?, ?, ?, ?)");
$stmt->execute([$matricula, $ip_address, $status, $error_message]);

// Busca os últimos logs
$stmt = $pdo->query("SELECT * FROM logs_login ORDER BY created_at DESC LIMIT 10");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Adiciona logs à resposta
$response['logs'] = $logs;

echo json_encode($response);
