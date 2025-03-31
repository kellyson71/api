<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Carrega as configurações
require_once 'config.php';
require_once 'conquistas.php';

// Inicia o tempo de execução para medição de performance
$tempo_inicio = microtime(true);

try {
    // Conexão com o banco de dados usando singleton para evitar múltiplas conexões
    $pdo = obterConexao();

    // Receber e validar parâmetros
    $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
    $mes = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT) ?: date('n');
    $ano = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT) ?: date('Y');

    if (!$usuario_id) {
        throw new Exception('ID do usuário não fornecido ou inválido');
    }

    if ($mes < 1 || $mes > 12) {
        throw new Exception('Mês inválido');
    }

    if ($ano < 2000 || $ano > 2100) {
        throw new Exception('Ano inválido');
    }

    // Verifica se o usuário existe - usando cache para evitar consultas repetidas
    $chave_cache = "usuario_{$usuario_id}";
    $usuario = obterCache($chave_cache);

    if ($usuario === null) {
        $stmt = $pdo->prepare("SELECT id, nome, cargo FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            throw new Exception('Usuário não encontrado');
        }

        definirCache($chave_cache, $usuario, 3600); // Cache por 1 hora
    }

    // Define o período com formato otimizado para SQL
    $data_inicio = sprintf('%04d-%02d-01', $ano, $mes);
    $data_fim = date('Y-m-t', strtotime($data_inicio));

    // Se for o mês atual, limitar ao dia atual
    $hoje = date('Y-m-d');
    if ($mes == date('n') && $ano == date('Y')) {
        $data_fim = $hoje;
    }

    // Preparação das datas para uso nos cálculos
    $data_inicio_obj = new DateTime($data_inicio);
    $data_fim_obj = new DateTime($data_fim);

    // Inicializa as estruturas de dados
    $estatisticas = inicializarEstatisticas($mes, $ano, $data_inicio, $data_fim);
    $dias_uteis = calcularDiasUteis($data_inicio_obj, $data_fim_obj, $estatisticas);

    // Busca os registros do período em uma única consulta otimizada
    $registros = buscarRegistrosPonto($pdo, $usuario_id, $data_inicio, $data_fim);

    // Processa os registros de forma otimizada
    $dados_processados = processarRegistros($registros, $dias_uteis, $estatisticas);
    $dias_registrados = $dados_processados['dias_registrados'];
    $estatisticas = $dados_processados['estatisticas'];
    $dias_uteis = $dados_processados['dias_uteis'];

    // Calcula médias, totais e estatísticas finais
    $estatisticas = calcularEstatisticasFinais(
        $estatisticas,
        $dias_registrados,
        $dias_uteis
    );

    // Registra o tempo de execução
    $tempo_execucao = round((microtime(true) - $tempo_inicio) * 1000, 2);
    $estatisticas['_meta'] = ['tempo_execucao_ms' => $tempo_execucao];

    // Resposta de sucesso
    enviarResposta(true, ['estatisticas' => $estatisticas]);
} catch (Exception $e) {
    // Resposta de erro
    enviarResposta(false, ['error' => $e->getMessage()]);
}

/**
 * Funções auxiliares
 */

// Obtém conexão com o banco (singleton)
function obterConexao()
{
    static $pdo = null;

    if ($pdo === null) {
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
    }

    return $pdo;
}

// Sistema de cache simples
function obterCache($chave)
{
    $arquivo_cache = sys_get_temp_dir() . '/ponto_cache_' . md5($chave);

    if (file_exists($arquivo_cache) && (filemtime($arquivo_cache) > time() - 3600)) {
        return unserialize(file_get_contents($arquivo_cache));
    }

    return null;
}

function definirCache($chave, $dados, $ttl = 3600)
{
    $arquivo_cache = sys_get_temp_dir() . '/ponto_cache_' . md5($chave);
    file_put_contents($arquivo_cache, serialize($dados));
}

// Inicialização das estatísticas
function inicializarEstatisticas($mes, $ano, $data_inicio, $data_fim)
{
    return [
        'mes' => (int)$mes,
        'ano' => (int)$ano,
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
        'carga_horaria_diaria' => CARGA_HORARIA_DIARIA,
        'carga_horaria_esperada' => 0,
        'media_entrada' => '',
        'media_saida' => '',
        'dias_semana' => [
            'registros' => array_fill(0, 7, 0),
            'horas' => array_fill(0, 7, 0)
        ]
    ];
}

// Calcula os dias úteis no período
function calcularDiasUteis($data_inicio_obj, $data_fim_obj, &$estatisticas)
{
    $dias_uteis = [];
    $data = clone $data_inicio_obj;

    while ($data <= $data_fim_obj) {
        $dia_semana = (int)$data->format('N'); // 1 (segunda) a 7 (domingo)
        $data_str = $data->format('Y-m-d');

        if ($dia_semana <= 5) { // Segunda a Sexta
            $estatisticas['dias_uteis_total']++;
            $dias_uteis[$data_str] = [
                'data' => $data_str,
                'dia_semana' => $dia_semana,
                'registrado' => false
            ];
        }

        $data->modify('+1 day');
    }

    return $dias_uteis;
}

// Busca registros de ponto otimizada
function buscarRegistrosPonto($pdo, $usuario_id, $data_inicio, $data_fim)
{
    $chave_cache = "registros_{$usuario_id}_{$data_inicio}_{$data_fim}";
    $registros = obterCache($chave_cache);

    if ($registros === null) {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(data_hora) as data,
                TIME(data_hora) as hora,
                tipo,
                data_hora
            FROM 
                registros_ponto 
            WHERE 
                usuario_id = ? 
                AND data_hora BETWEEN ? AND ?
            ORDER BY 
                data_hora
        ");
        $stmt->execute([
            $usuario_id,
            $data_inicio . ' 00:00:00',
            $data_fim . ' 23:59:59'
        ]);

        $registros = $stmt->fetchAll();
        definirCache($chave_cache, $registros, 600); // Cache por 10 minutos
    }

    return $registros;
}

// Processa os registros para gerar estatísticas
function processarRegistros($registros, &$dias_uteis, &$estatisticas)
{
    $dias_registrados = [];
    $total_minutos_atraso = 0;

    // Pré-processamento para agrupar entradas e saídas por dia
    foreach ($registros as $registro) {
        $data = $registro['data'];
        $hora = $registro['hora'];
        $tipo = $registro['tipo'];
        $data_hora = new DateTime($registro['data_hora']);
        $dia_semana = (int)$data_hora->format('N');

        // Se for dia útil
        if ($dia_semana <= 5 && isset($dias_uteis[$data])) {
            // Marca o dia como registrado
            $dias_uteis[$data]['registrado'] = true;

            // Inicializa o registro do dia se necessário
            if (!isset($dias_registrados[$data])) {
                $dias_registrados[$data] = [
                    'entrada' => null,
                    'saida' => null,
                    'dia_semana' => $dia_semana
                ];
            }

            // Processa entrada ou saída
            if ($tipo === 'entrada') {
                // Usa a primeira entrada do dia
                if ($dias_registrados[$data]['entrada'] === null) {
                    $dias_registrados[$data]['entrada'] = $data_hora;
                    $estatisticas['dias_semana']['registros'][$dia_semana]++;

                    // Verifica atraso
                    if ($hora > HORARIO_ENTRADA) {
                        $estatisticas['atrasos']++;
                        $horario_esperado = new DateTime($data . ' ' . HORARIO_ENTRADA);
                        $minutos_atraso = ($data_hora->getTimestamp() - $horario_esperado->getTimestamp()) / 60;
                        $total_minutos_atraso += $minutos_atraso;
                    }
                }
            } else {
                // Usa a última saída do dia
                $dias_registrados[$data]['saida'] = $data_hora;
            }
        }
    }

    // Calcula estatísticas de atraso
    if ($estatisticas['atrasos'] > 0) {
        $estatisticas['media_minutos_atraso'] = round($total_minutos_atraso / $estatisticas['atrasos']);
    }

    return [
        'dias_registrados' => $dias_registrados,
        'estatisticas' => $estatisticas,
        'dias_uteis' => $dias_uteis
    ];
}

// Calcula todas as estatísticas finais
function calcularEstatisticasFinais(&$estatisticas, $dias_registrados, $dias_uteis)
{
    $total_minutos = 0;
    $total_entradas = 0;
    $total_saidas = 0;
    $soma_minutos_entrada = 0;
    $soma_minutos_saida = 0;

    // Processa cada dia com registros
    foreach ($dias_registrados as $data => $info) {
        if ($info['entrada'] && $info['saida']) {
            $estatisticas['dias_trabalhados']++;
            $dia_semana = $info['dia_semana'];

            // Calcula tempo trabalhado
            $entrada = $info['entrada'];
            $saida = $info['saida'];
            $minutos = max(0, ($saida->getTimestamp() - $entrada->getTimestamp()) / 60);
            $total_minutos += $minutos;

            // Atualiza estatísticas por dia da semana
            $estatisticas['dias_semana']['horas'][$dia_semana] += $minutos / 60;

            // Verifica se é dia completo
            if ($minutos >= (CARGA_HORARIA_DIARIA * 60)) {
                $estatisticas['dias_completos']++;
            } else {
                $estatisticas['dias_incompletos']++;
            }

            // Acumula para médias de horários
            $minutos_desde_meia_noite = $entrada->format('H') * 60 + $entrada->format('i');
            $soma_minutos_entrada += $minutos_desde_meia_noite;

            $minutos_desde_meia_noite = $saida->format('H') * 60 + $saida->format('i');
            $soma_minutos_saida += $minutos_desde_meia_noite;

            $total_entradas++;
            $total_saidas++;
        }
    }

    // Calcula dias faltados
    $estatisticas['dias_faltados'] = 0;
    foreach ($dias_uteis as $data => $info) {
        if (!$info['registrado']) {
            $estatisticas['dias_faltados']++;
        }
    }

    // Arredonda valores para evitar casas decimais excessivas
    $estatisticas['total_horas'] = round($total_minutos / 60, 1);

    // Calcula médias de entrada e saída
    if ($total_entradas > 0) {
        $media_minutos_entrada = $soma_minutos_entrada / $total_entradas;
        $horas = floor($media_minutos_entrada / 60);
        $minutos = round($media_minutos_entrada % 60);
        $estatisticas['media_entrada'] = sprintf('%02d:%02d', $horas, $minutos);
    }

    if ($total_saidas > 0) {
        $media_minutos_saida = $soma_minutos_saida / $total_saidas;
        $horas = floor($media_minutos_saida / 60);
        $minutos = round($media_minutos_saida % 60);
        $estatisticas['media_saida'] = sprintf('%02d:%02d', $horas, $minutos);
    }

    // Calcula média de horas por dia
    $estatisticas['media_horas_dia'] = $estatisticas['dias_trabalhados'] > 0
        ? round($estatisticas['total_horas'] / $estatisticas['dias_trabalhados'], 1)
        : 0;

    // Calcula carga horária esperada e saldo
    $estatisticas['carga_horaria_esperada'] = $estatisticas['dias_uteis_total'] * CARGA_HORARIA_DIARIA;
    $estatisticas['saldo_horas'] = round($estatisticas['total_horas'] - $estatisticas['carga_horaria_esperada'], 1);

    // Distribui o saldo entre horas extras e devidas
    if ($estatisticas['saldo_horas'] > 0) {
        $estatisticas['horas_extras'] = round($estatisticas['saldo_horas'], 1);
        $estatisticas['horas_devidas'] = 0;
    } else {
        $estatisticas['horas_extras'] = 0;
        $estatisticas['horas_devidas'] = round(abs($estatisticas['saldo_horas']), 1);
    }

    // Calcula conquistas
    $estatisticas['conquistas'] = calcularConquistas($estatisticas);

    return $estatisticas;
}

// Função para enviar resposta padronizada
function enviarResposta($sucesso, $dados)
{
    $resposta = ['success' => $sucesso] + $dados;
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
