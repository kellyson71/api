<?php
// URL base da API
$base_url = 'https://api.protocolosead.com/consultar_ponto.php';

// Casos de teste
$test_cases = [
    // Teste básico - apenas usuário
    [
        'descricao' => 'Consultar todos os registros do usuário',
        'params' => ['usuario_id' => 1]
    ],
    // Teste com filtro de tipo
    [
        'descricao' => 'Consultar apenas entradas',
        'params' => [
            'usuario_id' => 1,
            'tipo' => 'entrada'
        ]
    ],
    // Teste com filtro de data
    [
        'descricao' => 'Consultar registros de hoje',
        'params' => [
            'usuario_id' => 1,
            'data_inicio' => date('Y-m-d 00:00:00'),
            'data_fim' => date('Y-m-d 23:59:59')
        ]
    ],
    // Teste com limite personalizado
    [
        'descricao' => 'Consultar apenas 5 registros',
        'params' => [
            'usuario_id' => 1,
            'limite' => 5
        ]
    ]
];

// Função para fazer a requisição
function testConsultarPonto($params)
{
    global $base_url;

    // Monta a URL com os parâmetros
    $url = $base_url . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
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
    echo "Parâmetros: " . json_encode($test['params'], JSON_PRETTY_PRINT) . "\n";

    $result = testConsultarPonto($test['params']);

    echo "Código HTTP: {$result['http_code']}\n";
    echo "Resposta: {$result['response']}\n\n";

    // Aguarda 1 segundo entre os testes
    sleep(1);
}
