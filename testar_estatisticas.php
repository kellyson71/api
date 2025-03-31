<?php
// URL da API
$base_url = 'https://api.protocolosead.com/calcular_estatisticas.php';

// Função para fazer a requisição
function testarEstatisticas($usuario_id, $mes = null, $ano = null)
{
    global $base_url;

    // Construir URL com parâmetros
    $url = $base_url . "?usuario_id=" . $usuario_id;
    if ($mes) $url .= "&mes=" . $mes;
    if ($ano) $url .= "&ano=" . $ano;

    echo "Testando URL: " . $url . "\n\n";

    // Fazer a requisição
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Exibir resultado
    if ($data['success']) {
        echo "✅ Teste bem sucedido!\n\n";
        echo "Estatísticas do mês {$data['estatisticas']['mes']}/{$data['estatisticas']['ano']}:\n";
        echo "----------------------------------------\n";
        echo "Total de horas: {$data['estatisticas']['total_horas']}h\n";
        echo "Média por dia: {$data['estatisticas']['media_horas_dia']}h\n";
        echo "Saldo: {$data['estatisticas']['saldo_horas']}h\n";
        echo "Dias trabalhados: {$data['estatisticas']['dias_trabalhados']}/{$data['estatisticas']['dias_uteis_total']}\n";
        echo "Dias completos: {$data['estatisticas']['dias_completos']}\n";
        echo "Dias incompletos: {$data['estatisticas']['dias_incompletos']}\n";
        echo "Atrasos: {$data['estatisticas']['atrasos']} (média: {$data['estatisticas']['media_minutos_atraso']}min)\n";
        echo "Horas extras: {$data['estatisticas']['horas_extras']}h\n";
        echo "Horas devidas: {$data['estatisticas']['horas_devidas']}h\n";
        echo "Média entrada: {$data['estatisticas']['media_entrada']}\n";
        echo "Média saída: {$data['estatisticas']['media_saida']}\n\n";

        echo "Conquistas desbloqueadas: " . count($data['estatisticas']['conquistas']) . "\n";
        foreach ($data['estatisticas']['conquistas'] as $conquista) {
            echo "- {$conquista['titulo']}: {$conquista['descricao']}\n";
        }
    } else {
        echo "❌ Erro: {$data['error']}\n";
    }

    echo "\n----------------------------------------\n\n";
}

// Testar diferentes cenários
echo "=== Testando API de Estatísticas ===\n\n";

// Teste 1: Mês atual
echo "Teste 1: Mês atual\n";
testarEstatisticas(2);

// Teste 2: Mês específico
echo "Teste 2: Mês específico (Março 2024)\n";
testarEstatisticas(2, 3, 2024);

// Teste 3: Usuário inválido
echo "Teste 3: Usuário inválido\n";
testarEstatisticas(999);

// Teste 4: Mês inválido
echo "Teste 4: Mês inválido\n";
testarEstatisticas(2, 13, 2024);
