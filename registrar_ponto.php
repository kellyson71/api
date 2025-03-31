<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Carrega as configurações
require_once 'config.php';

// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Cache de conexão PDO
function obterConexao()
{
    static $pdo = null;

    if ($pdo === null) {
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

        // Define o fuso horário na conexão MySQL
        $pdo->exec("SET time_zone = '-03:00'");
    }

    return $pdo;
}

try {
    // Recebe os dados do POST
    $data = json_decode(file_get_contents('php://input'), true);

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
        // Consulta otimizada do último registro usando NOW() do MySQL
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

    // Prepara os dados para inserção usando NOW() do MySQL
    $dados = [
        'usuario_id' => $data['usuario_id'],
        'data_hora' => new DateTime('now', new DateTimeZone('America/Sao_Paulo')),
        'tipo' => $data['tipo'],
        'latitude' => $data['latitude'] ?? null,
        'longitude' => $data['longitude'] ?? null,
        'endereco' => $data['endereco'] ?? null,
        'observacoes' => $data['observacoes'] ?? null
    ];

    // Inserção otimizada usando NOW() do MySQL
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

    // Retorna sucesso sem buscar o registro novamente
    echo json_encode([
        'success' => true,
        'message' => 'Ponto registrado com sucesso',
        'registro' => array_merge(['id' => $pdo->lastInsertId()], $dados)
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}