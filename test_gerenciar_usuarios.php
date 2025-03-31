<?php
// URL base da API
$base_url = 'https://api.protocolosead.com/gerenciar_usuarios.php';

// Casos de teste
$test_cases = [
    // Teste de criação de usuário
    [
        'descricao' => 'Criar novo usuário',
        'method' => 'POST',
        'data' => [
            'matricula' => 'teste123',
            'password' => 'senha123',
            'name' => 'Usuário Teste',
            'email' => 'teste@email.com',
            'telefone' => '11999999999',
            'cargo' => 'usuario'
        ]
    ],
    // Teste de listagem
    [
        'descricao' => 'Listar usuários',
        'method' => 'GET'
    ],
    // Teste de atualização
    [
        'descricao' => 'Atualizar usuário',
        'method' => 'PUT',
        'id' => 1,
        'data' => [
            'name' => 'Usuário Atualizado',
            'email' => 'atualizado@email.com',
            'telefone' => '11988888888'
        ]
    ]
];

// Função para fazer a requisição
function testGerenciarUsuarios($test)
{
    global $base_url;

    $url = $base_url;
    if (isset($test['id'])) {
        $url .= "?id=" . $test['id'];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $test['method']);

    if (in_array($test['method'], ['POST', 'PUT']) && isset($test['data'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo "Erro cURL: " . curl_error($ch) . "\n";
    }

    curl_close($ch);

    return [
        'http_code' => $http_code,
        'response' => $response
    ];
}

// Executar os testes
foreach ($test_cases as $test) {
    echo "Teste: {$test['descricao']}\n";
    if (isset($test['data'])) {
        echo "Dados: " . json_encode($test['data'], JSON_PRETTY_PRINT) . "\n";
    }

    $result = testGerenciarUsuarios($test);

    echo "Código HTTP: {$result['http_code']}\n";
    echo "Resposta: {$result['response']}\n\n";

    // Aguarda 1 segundo entre os testes
    sleep(1);
}
