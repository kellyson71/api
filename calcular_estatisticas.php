<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'conquistas.php';

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

// Receber parâmetros
$usuario_id = $_GET['usuario_id'] ?? null;
$mes = $_GET['mes'] ?? date('n');
$ano = $_GET['ano'] ?? date('Y');

if (!$usuario_id) {
    die(json_encode(['success' => false, 'error' => 'ID do usuário não fornecido']));
}

if ($mes < 1 || $mes > 12) {
    die(json_encode(['success' => false, 'error' => 'Mês inválido']));
}

if ($ano < 2000 || $ano > 2100) {
    die(json_encode(['success' => false, 'error' => 'Ano inválido']));
}

// Verifica se o usuário existe
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$usuario_id]);
if (!$stmt->fetch()) {
    die(json_encode(['success' => false, 'error' => 'Usuário não encontrado']));
}

// Define o período
$data_inicio = "$ano-$mes-01";
$data_fim = date('Y-m-t', strtotime($data_inicio));

// Se o mês atual, limitar ao dia atual
$hoje = date('Y-m-d');
if ($mes == date('n') && $ano == date('Y')) {
    $data_fim = $hoje;
}

// Busca os registros do período
$stmt = $pdo->prepare("
    SELECT * FROM registros_ponto 
    WHERE usuario_id = ? 
    AND data_hora BETWEEN ? AND ?
    ORDER BY data_hora
");
$stmt->execute([$usuario_id, $data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializa as estatísticas
$estatisticas = [
    'mes' => $mes,
    'ano' => $ano,
    'periodo' => [
        'inicio' => $data_inicio,
        'fim' => $data_fim
    ],
    'total_horas' => 0,
    'media_horas_dia' => 0,
    'saldo_horas' => 0,
    'dias_trabalhados' => 0,
    'dias_uteis_total' => 0,
    'dias_completos' => 0,
    'dias_incompletos' => 0,
    'dias_faltados' => 0,
    'atrasos' => 0,
    'media_minutos_atraso' => 0,
    'horas_extras' => 0,
    'horas_devidas' => 0,
    'carga_horaria_diaria' => 6,
    'carga_horaria_esperada' => 0,
    'media_entrada' => '',
    'media_saida' => '',
    'dias_semana' => [
        'registros' => [0, 0, 0, 0, 0, 0, 0],
        'horas' => [0, 0, 0, 0, 0, 0, 0]
    ],
    'dias_uteis_detalhados' => [] // Para debug e verificação
];

// Calcula dias úteis e armazena em um array para verificação
$data = new DateTime($data_inicio);
$data_fim_obj = new DateTime($data_fim);
$dias_uteis = [];

while ($data <= $data_fim_obj) {
    $dia_semana = $data->format('N');
    $data_str = $data->format('Y-m-d');

    if ($dia_semana <= 5) { // Segunda a Sexta
        $estatisticas['dias_uteis_total']++;
        $dias_uteis[$data_str] = [
            'data' => $data_str,
            'dia_semana' => $dia_semana,
            'nome_dia' => $data->format('l'),
            'registrado' => false
        ];

        // Adiciona ao array de detalhes (para debug)
        $estatisticas['dias_uteis_detalhados'][] = [
            'data' => $data_str,
            'dia_semana' => $dia_semana,
            'nome_dia' => $data->format('l')
        ];
    }

    $data->modify('+1 day');
}

// Processa os registros
$dias_registrados = [];
$total_minutos_atraso = 0;

foreach ($registros as $registro) {
    $data = new DateTime($registro['data_hora']);
    $data_str = $data->format('Y-m-d');
    $dia_semana = $data->format('N');

    if ($dia_semana <= 5) { // Segunda a Sexta
        // Marca o dia como registrado
        if (isset($dias_uteis[$data_str])) {
            $dias_uteis[$data_str]['registrado'] = true;
        }

        if (!isset($dias_registrados[$data_str])) {
            $dias_registrados[$data_str] = [
                'entrada' => null,
                'saida' => null
            ];
        }

        if ($registro['tipo'] === 'entrada') {
            $dias_registrados[$data_str]['entrada'] = $data;
            $estatisticas['dias_semana']['registros'][$dia_semana]++;

            // Verifica atraso
            $hora_entrada = $data->format('H:i');
            if ($hora_entrada > '13:00') {
                $estatisticas['atrasos']++;
                $minutos_atraso = (int)$data->diff(new DateTime($data_str . ' 13:00'))->format('%i');
                $total_minutos_atraso += $minutos_atraso;
            }
        } else {
            $dias_registrados[$data_str]['saida'] = $data;
        }
    }
}

// Calcula médias e totais
$total_minutos = 0;
$total_entradas = 0;
$total_saidas = 0;
$minutos_entrada = 0;
$minutos_saida = 0;

foreach ($dias_registrados as $dia => $registros) {
    if ($registros['entrada'] && $registros['saida']) {
        $estatisticas['dias_trabalhados']++;

        $diff = $registros['saida']->diff($registros['entrada']);
        $minutos = $diff->h * 60 + $diff->i;
        $total_minutos += $minutos;

        // Verifica se é dia completo
        if ($minutos >= 360) { // 6 horas
            $estatisticas['dias_completos']++;
        } else {
            $estatisticas['dias_incompletos']++;
        }

        // Acumula minutos para média de entrada/saída
        $minutos_entrada += $registros['entrada']->format('H') * 60 + $registros['entrada']->format('i');
        $minutos_saida += $registros['saida']->format('H') * 60 + $registros['saida']->format('i');
        $total_entradas++;
        $total_saidas++;
    }
}

// Calcula dias faltados (dias úteis sem registro)
foreach ($dias_uteis as $data => $info) {
    if (!$info['registrado']) {
        $estatisticas['dias_faltados']++;
    }
}

// Calcula médias finais
$estatisticas['total_horas'] = round($total_minutos / 60, 1);
$estatisticas['media_horas_dia'] = $estatisticas['dias_trabalhados'] > 0 ?
    round($estatisticas['total_horas'] / $estatisticas['dias_trabalhados'], 1) : 0;

$estatisticas['media_minutos_atraso'] = $estatisticas['atrasos'] > 0 ?
    round($total_minutos_atraso / $estatisticas['atrasos']) : 0;

$estatisticas['media_entrada'] = $total_entradas > 0 ?
    date('H:i', strtotime('00:00') + ($minutos_entrada / $total_entradas * 60)) : '';

$estatisticas['media_saida'] = $total_saidas > 0 ?
    date('H:i', strtotime('00:00') + ($minutos_saida / $total_saidas * 60)) : '';

// Calcula saldo de horas
$carga_horaria_esperada = $estatisticas['dias_uteis_total'] * 6;
$estatisticas['carga_horaria_esperada'] = $carga_horaria_esperada;
$estatisticas['saldo_horas'] = round($estatisticas['total_horas'] - $carga_horaria_esperada, 1);

// Calcula horas extras e devidas
if ($estatisticas['saldo_horas'] > 0) {
    $estatisticas['horas_extras'] = round($estatisticas['saldo_horas'], 1);
    $estatisticas['horas_devidas'] = 0;
} else {
    $estatisticas['horas_extras'] = 0;
    $estatisticas['horas_devidas'] = round(abs($estatisticas['saldo_horas']), 1);
}

// Remover detalhes dos dias úteis na resposta final
unset($estatisticas['dias_uteis_detalhados']);

// Calcula conquistas
$estatisticas['conquistas'] = calcularConquistas($estatisticas);

echo json_encode([
    'success' => true,
    'estatisticas' => $estatisticas
]);
