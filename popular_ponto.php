<?php
// Configurações do banco de dados
$host = 'srv1844.hstgr.io';
$dbname = 'u492577848_react';
$username = 'u492577848_react';
$password = 'Kellys0n_123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Configurações
$usuario_id = 2;
$enderecos = [
    'Rua Donaciano Cavalcante, Pau dos Ferros',
];

$coordenadas = [
    ['-6.11614850', '-38.20145230'],
    ['-6.11620000', '-38.20150000'],
    ['-6.11630000', '-38.20160000'],
    ['-6.11640000', '-38.20170000'],
    ['-6.11650000', '-38.20180000']
];

// Função para gerar horário de entrada
function gerarHorarioEntrada()
{
    // 95% de chance de chegar no horário (13:00)
    if (rand(1, 100) <= 95) {
        return '13:00:00';
    }

    // 5% de chance de atraso (entre 13:01 e 13:30)
    $minutos_atraso = rand(1, 30);
    return '13:' . str_pad($minutos_atraso, 2, '0', STR_PAD_LEFT) . ':00';
}

// Função para gerar horário de saída
function gerarHorarioSaida($entrada_atrasada = false)
{
    if ($entrada_atrasada) {
        // Se chegou atrasado, sai mais tarde (19:30 - 20:00)
        $minutos_extra = rand(30, 60);
        return '19:' . str_pad($minutos_extra, 2, '0', STR_PAD_LEFT) . ':00';
    }

    // 95% de chance de sair no horário (19:00)
    if (rand(1, 100) <= 95) {
        return '19:00:00';
    }

    // 5% de chance de sair mais tarde (19:01 - 19:30)
    $minutos_atraso = rand(1, 30);
    return '19:' . str_pad($minutos_atraso, 2, '0', STR_PAD_LEFT) . ':00';
}

// Função para gerar observação aleatória
function gerarObservacao($tipo, $atrasado = false)
{
    $observacoes = [
        'entrada' => [
            'Chegada no trabalho',
            'Início do expediente',
            'Chegada na empresa',
            'Início das atividades'
        ],
        'saida' => [
            'Fim do expediente',
            'Encerramento das atividades',
            'Saída do trabalho',
            'Fim do dia de trabalho'
        ]
    ];

    $observacao = $observacoes[$tipo][array_rand($observacoes[$tipo])];

    if ($atrasado) {
        if ($tipo === 'entrada') {
            $observacao .= ' (Atraso)';
        } else {
            $observacao .= ' (Compensação de atraso)';
        }
    }

    return $observacao;
}

// Data atual
$data_atual = new DateTime();

// Gerar registros para os últimos 3 meses
for ($meses = 0; $meses < 3; $meses++) {
    $data = clone $data_atual;
    $data->modify("-$meses months");

    // Número de dias no mês
    $dias_no_mes = $data->format('t');

    // Gerar registros para cada dia do mês
    for ($dia = 1; $dia <= $dias_no_mes; $dia++) {
        // Pular finais de semana
        $data->setDate($data->format('Y'), $data->format('m'), $dia);
        if ($data->format('N') > 5) continue;

        // Gerar entrada
        $local_index = array_rand($enderecos);
        $horario_entrada = gerarHorarioEntrada();
        $entrada_atrasada = $horario_entrada !== '13:00:00';

        $stmt = $pdo->prepare("INSERT INTO registros_ponto (usuario_id, data_hora, tipo, latitude, longitude, endereco, observacoes, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

        $data_hora = $data->format('Y-m-d') . ' ' . $horario_entrada;
        $stmt->execute([
            $usuario_id,
            $data_hora,
            'entrada',
            $coordenadas[$local_index][0],
            $coordenadas[$local_index][1],
            $enderecos[$local_index],
            gerarObservacao('entrada', $entrada_atrasada)
        ]);

        // Gerar saída
        $local_index = array_rand($enderecos);
        $horario_saida = gerarHorarioSaida($entrada_atrasada);
        $saida_atrasada = $horario_saida !== '19:00:00';

        $data_hora = $data->format('Y-m-d') . ' ' . $horario_saida;
        $stmt->execute([
            $usuario_id,
            $data_hora,
            'saida',
            $coordenadas[$local_index][0],
            $coordenadas[$local_index][1],
            $enderecos[$local_index],
            gerarObservacao('saida', $saida_atrasada)
        ]);
    }
}

echo "Registros de ponto gerados com sucesso para o usuário $usuario_id nos últimos 3 meses!\n";
