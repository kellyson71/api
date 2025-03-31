<?php
// URL da API
$api_url = 'https://api.protocolosead.com/registrar_ponto.php';

// Dados de teste
$test_cases = [
    [
        'usuario_id' => 1,
        'tipo' => 'entrada',
        'latitude' => -23.550520,
        'longitude' => -46.633308,
        'endereco' => 'Av. Paulista, 1000 - São Paulo, SP',
        'observacoes' => 'Teste de entrada'
    ],
    [
        'usuario_id' => 1,
        'tipo' => 'saida',
        'latitude' => -23.550520,
        'longitude' => -46.633308,
        'endereco' => 'Av. Paulista, 1000 - São Paulo, SP',
        'observacoes' => 'Teste de saída'
    ]
];

// Função para fazer a requisição
function testRegistrarPonto($data)
{
    global $api_url;

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo "Erro cURL: " . curl_error($ch) . "\n";
    }

    curl_close($ch);

    echo "Testando registro de ponto:\n";
    echo "Tipo: {$data['tipo']}\n";
    echo "Código HTTP: $http_code\n";
    echo "Resposta: " . $response . "\n\n";
}

// Executar os testes
foreach ($test_cases as $test) {
    testRegistrarPonto($test);
    // Aguarda 1 segundo entre os testes
    sleep(1);
}
