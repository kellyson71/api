<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 3600');

// Se for uma requisição OPTIONS, retorna 200
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carrega as configurações
require_once 'config.php';

// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Cache de conexão PDO
function obterConexao()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $host = getDbConfig('host');
            $dbname = getDbConfig('dbname');
            $username = getDbConfig('username');
            $password = getDbConfig('password');

            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
                PDO::ATTR_PERSISTENT => true
            ];

            $pdo = new PDO($dsn, $username, $password, $options);
            $pdo->exec("SET time_zone = '-03:00'");
        } catch (PDOException $e) {
            error_log("Erro de conexão com o banco: " . $e->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }
    }

    return $pdo;
}

try {
    // Verifica se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método não permitido");
    }

    // Recebe os dados do POST
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception("Dados não fornecidos");
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg());
    }

    // Validação rápida dos dados obrigatórios
    if (!isset($data['usuario_id'], $data['tipo']) || empty($data['usuario_id']) || empty($data['tipo'])) {
        throw new Exception('Dados obrigatórios não fornecidos');
    }

    // Validação do tipo de ponto
    if (!in_array($data['tipo'], ['entrada', 'saida'])) {
        throw new Exception('Tipo de ponto inválido');
    }

    // Obtém conexão otimizada
    $pdo = obterConexao();

    // Cache do último registro (5 segundos)
    $cache_key = "ultimo_registro_{$data['usuario_id']}";
    $cache_file = sys_get_temp_dir() . '/ponto_cache_' . md5($cache_key);

    $ultimo_registro = null;
    if (file_exists($cache_file) && (filemtime($cache_file) > time() - 5)) {
        $ultimo_registro = unserialize(file_get_contents($cache_file));
    } else {
        // Consulta otimizada do último registro
        $stmt = $pdo->prepare("
            SELECT tipo 
            FROM registros_ponto 
            WHERE usuario_id = ? 
            AND DATE(data_hora) = CURDATE()
            ORDER BY data_hora DESC 
            LIMIT 1
        ");
        $stmt->execute([$data['usuario_id']]);
        $ultimo_registro = $stmt->fetch();

        if ($ultimo_registro) {
            file_put_contents($cache_file, serialize($ultimo_registro));
        }
    }

    // Validação de sequência de pontos
    if ($ultimo_registro && $ultimo_registro['tipo'] === $data['tipo']) {
        throw new Exception("Não é possível registrar {$data['tipo']} consecutivamente");
    }

    // Prepara os dados para inserção
    $dados = [
        'usuario_id' => $data['usuario_id'],
        'tipo' => $data['tipo'],
        'latitude' => $data['latitude'] ?? null,
        'longitude' => $data['longitude'] ?? null,
        'endereco' => $data['endereco'] ?? null,
        'observacoes' => $data['observacoes'] ?? null
    ];

    // Inserção otimizada
    $stmt = $pdo->prepare("
        INSERT INTO registros_ponto 
        (usuario_id, data_hora, tipo, latitude, longitude, endereco, observacoes) 
        VALUES 
        (:usuario_id, NOW(), :tipo, :latitude, :longitude, :endereco, :observacoes)
    ");

    $stmt->execute($dados);

    // Limpa o cache do último registro
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }

    // Retorna sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Ponto registrado com sucesso',
        'registro' => array_merge(['id' => $pdo->lastInsertId()], $dados)
    ]);
} catch (Exception $e) {
    error_log("Erro no registro de ponto: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Erro ao processar a requisição'
    ]);
}