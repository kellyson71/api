<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    // Verifica se é uma requisição GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Método não permitido");
    }

    // Obtém o ID do usuário da URL
    $usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;

    if ($usuario_id <= 0) {
        throw new Exception("ID do usuário inválido");
    }

    // Obtém conexão otimizada
    $pdo = obterConexao();

    // Consulta as últimas 20 batidas de ponto
    $stmt = $pdo->prepare("
        SELECT 
            rp.id,
            rp.usuario_id,
            rp.data_hora,
            rp.tipo,
            rp.latitude,
            rp.longitude,
            rp.endereco,
            rp.observacoes,
            u.nome as nome_usuario,
            u.matricula
        FROM registros_ponto rp
        INNER JOIN users u ON rp.usuario_id = u.id
        WHERE rp.usuario_id = ?
        ORDER BY rp.data_hora DESC
        LIMIT 20
    ");

    $stmt->execute([$usuario_id]);
    $registros = $stmt->fetchAll();

    // Formata os dados para retorno
    $pontos = array_map(function ($registro) {
        return [
            'id' => $registro['id'],
            'usuario_id' => $registro['usuario_id'],
            'nome_usuario' => $registro['nome_usuario'],
            'matricula' => $registro['matricula'],
            'data_hora' => $registro['data_hora'],
            'tipo' => $registro['tipo'],
            'latitude' => $registro['latitude'],
            'longitude' => $registro['longitude'],
            'endereco' => $registro['endereco'],
            'observacoes' => $registro['observacoes'],
            'data_formatada' => date('d/m/Y H:i:s', strtotime($registro['data_hora'])),
            'tipo_formatado' => ucfirst($registro['tipo'])
        ];
    }, $registros);

    // Retorna sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Últimos pontos consultados com sucesso',
        'total_registros' => count($pontos),
        'pontos' => $pontos
    ]);
} catch (Exception $e) {
    error_log("Erro na consulta dos últimos pontos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Erro ao processar a requisição'
    ]);
}
