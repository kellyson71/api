<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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

// Recebe os parâmetros da URL
$usuario_id = $_GET['usuario_id'] ?? null;
$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;
$tipo = $_GET['tipo'] ?? null;
$limite = (int)($_GET['limite'] ?? 10); // Converte para inteiro

// Validação do usuário_id
if (!$usuario_id) {
    die(json_encode(['success' => false, 'error' => 'ID do usuário é obrigatório']));
}

// Monta a query base
$query = "SELECT * FROM registros_ponto WHERE usuario_id = :usuario_id";
$params = [':usuario_id' => $usuario_id];

// Adiciona filtros se fornecidos
if ($data_inicio) {
    $query .= " AND data_hora >= :data_inicio";
    $params[':data_inicio'] = $data_inicio;
}

if ($data_fim) {
    $query .= " AND data_hora <= :data_fim";
    $params[':data_fim'] = $data_fim;
}

if ($tipo) {
    if (!in_array($tipo, ['entrada', 'saida'])) {
        die(json_encode(['success' => false, 'error' => 'Tipo inválido. Use "entrada" ou "saida"']));
    }
    $query .= " AND tipo = :tipo";
    $params[':tipo'] = $tipo;
}

// Ordena por data/hora decrescente e limita resultados
$query .= " ORDER BY data_hora DESC LIMIT :limite";
$params[':limite'] = $limite;

try {
    $stmt = $pdo->prepare($query);

    // Bind dos parâmetros
    foreach ($params as $key => $value) {
        if ($key === ':limite') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }

    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca informações do usuário
    $stmt = $pdo->prepare("SELECT matricula FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'usuario' => $usuario['matricula'] ?? 'Usuário não encontrado',
        'total_registros' => count($registros),
        'registros' => $registros
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao consultar registros: ' . $e->getMessage()
    ]);
}
