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

// Validação dos dados obrigatórios
$required_fields = ['usuario_id', 'tipo'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        die(json_encode(['success' => false, 'error' => "Campo obrigatório não fornecido: $field"]));
    }
}

// Validação do tipo de ponto
if (!in_array($data['tipo'], ['entrada', 'saida'])) {
    die(json_encode(['success' => false, 'error' => 'Tipo de ponto inválido. Use "entrada" ou "saida"']));
}

// Validação de latitude e longitude se fornecidos
if (isset($data['latitude']) && !is_numeric($data['latitude'])) {
    die(json_encode(['success' => false, 'error' => 'Latitude inválida']));
}
if (isset($data['longitude']) && !is_numeric($data['longitude'])) {
    die(json_encode(['success' => false, 'error' => 'Longitude inválida']));
}

// Verifica se já existe um registro de ponto para este usuário no mesmo dia
$stmt = $pdo->prepare("
    SELECT tipo 
    FROM registros_ponto 
    WHERE usuario_id = ? 
    AND DATE(data_hora) = CURDATE()
    ORDER BY data_hora DESC 
    LIMIT 1
");
$stmt->execute([$data['usuario_id']]);
$ultimo_registro = $stmt->fetch(PDO::FETCH_ASSOC);

// Validação de sequência de pontos
if ($ultimo_registro) {
    if ($ultimo_registro['tipo'] === $data['tipo']) {
        die(json_encode([
            'success' => false,
            'error' => "Não é possível registrar $data[tipo] consecutivamente"
        ]));
    }
}

// Prepara os dados para inserção
$dados = [
    'usuario_id' => $data['usuario_id'],
    'data_hora' => date('Y-m-d H:i:s'),
    'tipo' => $data['tipo'],
    'latitude' => $data['latitude'] ?? null,
    'longitude' => $data['longitude'] ?? null,
    'endereco' => $data['endereco'] ?? null,
    'observacoes' => $data['observacoes'] ?? null
];

// Insere o registro
try {
    $stmt = $pdo->prepare("
        INSERT INTO registros_ponto 
        (usuario_id, data_hora, tipo, latitude, longitude, endereco, observacoes) 
        VALUES 
        (:usuario_id, :data_hora, :tipo, :latitude, :longitude, :endereco, :observacoes)
    ");

    $stmt->execute($dados);

    // Busca o registro inserido
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM registros_ponto WHERE id = ?");
    $stmt->execute([$id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Ponto registrado com sucesso',
        'registro' => $registro
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao registrar ponto: ' . $e->getMessage()
    ]);
}