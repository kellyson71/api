    <?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');

    // Lista de todas as conquistas disponíveis
    $conquistas = [
        [
            'id' => 'pontualidade_perfeita',
            'titulo' => 'Pontualidade Perfeita',
            'descricao' => 'Sem atrasos no mês',
            'icone' => 'fa-solid fa-clock',
            'cor' => '#28a745',
            'regra' => function ($stats) {
                return $stats['atrasos'] == 0;
            }
        ],
        [
            'id' => 'assiduidade_perfeita',
            'titulo' => 'Assiduidade Perfeita',
            'descricao' => '100% de presença no mês',
            'icone' => 'fa-solid fa-calendar-check',
            'cor' => '#17a2b8',
            'regra' => function ($stats) {
                return $stats['dias_trabalhados'] == $stats['dias_uteis_total'];
            }
        ],
        [
            'id' => 'dedicacao_extra',
            'titulo' => 'Dedicação Extra',
            'descricao' => 'Acumulou 10+ horas extras',
            'icone' => 'fa-solid fa-star',
            'cor' => '#ffc107',
            'regra' => function ($stats) {
                return $stats['horas_extras'] >= 10;
            }
        ],
        [
            'id' => 'regularidade_perfeita',
            'titulo' => 'Regularidade Perfeita',
            'descricao' => 'Todos os dias completos',
            'icone' => 'fa-solid fa-circle-check',
            'cor' => '#6f42c1',
            'regra' => function ($stats) {
                return $stats['dias_incompletos'] == 0;
            }
        ],
        [
            'id' => 'maestro_do_tempo',
            'titulo' => 'Maestro do Tempo',
            'descricao' => 'Média de entrada e saída perfeita',
            'icone' => 'fa-solid fa-music',
            'cor' => '#e83e8c',
            'regra' => function ($stats) {
                return $stats['media_entrada'] == '13:00' && $stats['media_saida'] == '19:00';
            }
        ],
        [
            'id' => 'guardião_da_consistência',
            'titulo' => 'Guardião da Consistência',
            'descricao' => 'Sem horas devidas no mês',
            'icone' => 'fa-solid fa-shield-halved',
            'cor' => '#dc3545',
            'regra' => function ($stats) {
                return $stats['horas_devidas'] == 0;
            }
        ],
        [
            'id' => 'mestre_da_organização',
            'titulo' => 'Mestre da Organização',
            'descricao' => 'Média de horas por dia perfeita (6h)',
            'icone' => 'fa-solid fa-layer-group',
            'cor' => '#20c997',
            'regra' => function ($stats) {
                return $stats['media_horas_dia'] == 6.0;
            }
        ],
        [
            'id' => 'guardião_do_tempo',
            'titulo' => 'Guardião do Tempo',
            'descricao' => 'Total de horas exato do mês',
            'icone' => 'fa-solid fa-hourglass-half',
            'cor' => '#fd7e14',
            'regra' => function ($stats) {
                return $stats['total_horas'] == $stats['carga_horaria_esperada'];
            }
        ],
        [
            'id' => 'virtuoso_da_pontualidade',
            'titulo' => 'Virtuoso da Pontualidade',
            'descricao' => 'Média de atrasos menor que 5 minutos',
            'icone' => 'fa-solid fa-gauge-high',
            'cor' => '#0dcaf0',
            'regra' => function ($stats) {
                return $stats['media_minutos_atraso'] < 5;
            }
        ],
        [
            'id' => 'campeão_da_consistência',
            'titulo' => 'Campeão da Consistência',
            'descricao' => '3 meses seguidos sem atrasos',
            'icone' => 'fa-solid fa-trophy',
            'cor' => '#ffd700',
            'regra' => function ($stats) {
                // Esta regra precisará ser implementada com histórico
                return false;
            }
        ],
        [
            'id' => 'mestre_da_eficiência',
            'titulo' => 'Mestre da Eficiência',
            'descricao' => 'Saldo positivo de horas no mês',
            'icone' => 'fa-solid fa-chart-line',
            'cor' => '#198754',
            'regra' => function ($stats) {
                return $stats['saldo_horas'] > 0;
            }
        ],
        [
            'id' => 'guardião_da_excelência',
            'titulo' => 'Guardião da Excelência',
            'descricao' => 'Todas as conquistas do mês desbloqueadas',
            'icone' => 'fa-solid fa-crown',
            'cor' => '#6f42c1',
            'regra' => function ($stats) {
                // Esta regra será calculada no frontend
                return false;
            }
        ],
        [
            'id' => 'virtuoso_da_regularidade',
            'titulo' => 'Virtuoso da Regularidade',
            'descricao' => '90% dos dias com carga horária completa',
            'icone' => 'fa-solid fa-calendar-days',
            'cor' => '#0d6efd',
            'regra' => function ($stats) {
                return ($stats['dias_completos'] / $stats['dias_trabalhados']) >= 0.9;
            }
        ],
        [
            'id' => 'mestre_da_adaptabilidade',
            'titulo' => 'Mestre da Adaptabilidade',
            'descricao' => 'Média de entrada e saída dentro do padrão',
            'icone' => 'fa-solid fa-arrows-rotate',
            'cor' => '#6610f2',
            'regra' => function ($stats) {
                $entrada = strtotime($stats['media_entrada']);
                $saida = strtotime($stats['media_saida']);
                $entrada_esperada = strtotime('13:00');
                $saida_esperada = strtotime('19:00');

                return abs($entrada - $entrada_esperada) <= 900 && // 15 minutos
                    abs($saida - $saida_esperada) <= 900;
            }
        ]
    ];

    // Se for uma requisição GET sem parâmetros, retorna todas as conquistas
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
        echo json_encode([
            'success' => true,
            'conquistas' => $conquistas
        ]);
        exit;
    }

    // Função para calcular conquistas baseado nas estatísticas
    function calcularConquistas($estatisticas)
    {
        global $conquistas;
        $conquistasDesbloqueadas = [];

        foreach ($conquistas as $conquista) {
            if ($conquista['regra']($estatisticas)) {
                $conquistasDesbloqueadas[] = [
                    'id' => $conquista['id'],
                    'titulo' => $conquista['titulo'],
                    'descricao' => $conquista['descricao'],
                    'icone' => $conquista['icone'],
                    'cor' => $conquista['cor']
                ];
            }
        }

        return $conquistasDesbloqueadas;
    }
