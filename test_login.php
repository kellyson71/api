<?php
// URL da API
$api_url = 'https://api.protocolosead.com/login.php';

// Dados de teste
$test_cases = [
    [
        'matricula' => 'kellyson',
        'senha' => 'teste123'
    ],
    [
        'matricula' => 'usuario_errado',
        'senha' => 'senha_errada'
    ]
];

// Função para fazer a requisição
function testLogin($data)
{
    global $api_url;

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desativa verificação SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Desativa verificação do hostname

    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo "Erro cURL: " . curl_error($ch) . "\n";
    }

    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);

    curl_close($ch);

    echo "Testando login com matrícula: {$data['matricula']}\n";
    echo "Código HTTP: $http_code\n";
    echo "Log detalhado:\n$verboseLog\n";
    echo "Resposta: " . $response . "\n\n";
}

// Executar os testes
foreach ($test_cases as $test) {
    testLogin($test);
}
