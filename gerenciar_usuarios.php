<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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

// Recebe o método da requisição
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

// Função para validar dados do usuário
function validarDadosUsuario($data, $isUpdate = false)
{
    $errors = [];

    if (!$isUpdate) {
        if (empty($data['matricula'])) $errors[] = 'Matrícula é obrigatória';
        if (empty($data['password'])) $errors[] = 'Senha é obrigatória';
        if (empty($data['email'])) $errors[] = 'Email é obrigatório';
    }

    if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }

    if (isset($data['telefone']) && !preg_match('/^[0-9]{10,11}$/', preg_replace('/[^0-9]/', '', $data['telefone']))) {
        $errors[] = 'Telefone inválido';
    }

    if (isset($data['cargo']) && !in_array($data['cargo'], ['usuario', 'admin'])) {
        $errors[] = 'Cargo inválido';
    }

    return $errors;
}

// Função para criar usuário
function criarUsuario($pdo, $data)
{
    $errors = validarDadosUsuario($data);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Verifica se matrícula já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE matricula = ?");
    $stmt->execute([$data['matricula']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Matrícula já cadastrada'];
    }

    // Hash da senha
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

    // Define valores padrão
    $data['cargo'] = $data['cargo'] ?? 'usuario';
    $data['hora_entrada'] = $data['hora_entrada'] ?? '08:00:00';
    $data['hora_saida'] = $data['hora_saida'] ?? '18:00:00';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (matricula, password, name, email, telefone, cargo, hora_entrada, hora_saida) 
            VALUES 
            (:matricula, :password, :name, :email, :telefone, :cargo, :hora_entrada, :hora_saida)
        ");

        $stmt->execute($data);
        $id = $pdo->lastInsertId();

        // Busca o usuário criado
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Remove senha da resposta
        unset($usuario['password']);

        return ['success' => true, 'usuario' => $usuario];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Erro ao criar usuário: ' . $e->getMessage()];
    }
}

// Função para atualizar usuário
function atualizarUsuario($pdo, $id, $data)
{
    $errors = validarDadosUsuario($data, true);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Verifica se usuário existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'Usuário não encontrado'];
    }

    // Se tiver nova senha, faz o hash
    if (!empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    } else {
        unset($data['password']);
    }

    try {
        // Monta a query dinamicamente baseada nos campos fornecidos
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        $params[':id'] = $id;
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Busca o usuário atualizado
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Remove senha da resposta
        unset($usuario['password']);

        return ['success' => true, 'usuario' => $usuario];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Erro ao atualizar usuário: ' . $e->getMessage()];
    }
}

// Função para listar usuários
function listarUsuarios($pdo)
{
    try {
        $stmt = $pdo->query("SELECT id, matricula, name, email, telefone, cargo, hora_entrada, hora_saida, created_at FROM users ORDER BY name");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'usuarios' => $usuarios];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Erro ao listar usuários: ' . $e->getMessage()];
    }
}

// Roteamento das requisições
switch ($method) {
    case 'POST':
        echo json_encode(criarUsuario($pdo, $data));
        break;

    case 'PUT':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID do usuário não fornecido']);
            break;
        }
        echo json_encode(atualizarUsuario($pdo, $id, $data));
        break;

    case 'GET':
        echo json_encode(listarUsuarios($pdo));
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
}
