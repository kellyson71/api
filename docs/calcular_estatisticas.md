# API de Cálculo de Estatísticas

## Descrição

API para calcular estatísticas detalhadas de ponto de um usuário em um determinado período.

## Endpoint

```
GET https://api.protocolosead.com/calcular_estatisticas.php
```

## Parâmetros

| Parâmetro  | Tipo | Obrigatório | Descrição                                                 |
| ---------- | ---- | ----------- | --------------------------------------------------------- |
| usuario_id | int  | Sim         | ID do usuário                                             |
| mes        | int  | Não         | Mês para calcular (1-12). Se não fornecido, usa mês atual |
| ano        | int  | Não         | Ano para calcular. Se não fornecido, usa ano atual        |

## Exemplo de Requisição

```
GET https://api.protocolosead.com/calcular_estatisticas.php?usuario_id=2&mes=3&ano=2024
```

## Resposta de Sucesso

```json
{
  "success": true,
  "estatisticas": {
    "mes": 3,
    "ano": 2024,
    "periodo": {
      "inicio": "2024-03-01",
      "fim": "2024-03-31"
    },
    "total_horas": 120.5,
    "media_horas_dia": 6.0,
    "saldo_horas": 2.5,
    "dias_trabalhados": 20,
    "dias_uteis_total": 21,
    "dias_completos": 18,
    "dias_incompletos": 2,
    "dias_faltados": 1,
    "atrasos": 3,
    "media_minutos_atraso": 15,
    "horas_extras": 5.0,
    "horas_devidas": 2.5,
    "carga_horaria_diaria": 6,
    "carga_horaria_esperada": 126,
    "media_entrada": "13:05",
    "media_saida": "19:10",
    "dias_semana": {
      "registros": [0, 4, 4, 4, 4, 4, 0],
      "horas": [0, 24, 24, 24, 24, 24, 0]
    },
    "conquistas": [
      {
        "id": "dedicacao_extra",
        "titulo": "Dedicação Extra",
        "descricao": "Acumulou 10+ horas extras",
        "icone": "clock",
        "cor": "#17a2b8"
      }
    ]
  }
}
```

## Resposta de Erro

```json
{
  "success": false,
  "error": "Mensagem de erro"
}
```

## Exemplo de Uso em JavaScript

```javascript
const params = new URLSearchParams({
  usuario_id: 2,
  mes: 3,
  ano: 2024,
});

fetch(`https://api.protocolosead.com/calcular_estatisticas.php?${params}`)
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      const stats = data.estatisticas;
      console.log("Total de horas:", stats.total_horas);
      console.log("Dias trabalhados:", stats.dias_trabalhados);
      console.log("Conquistas:", stats.conquistas);
    } else {
      console.error("Erro:", data.error);
    }
  })
  .catch((error) => console.error("Erro na requisição:", error));
```

## Exemplo de Uso em PHP

```php
$params = http_build_query([
    'usuario_id' => 2,
    'mes' => 3,
    'ano' => 2024
]);

$url = "https://api.protocolosead.com/calcular_estatisticas.php?{$params}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['success']) {
    $stats = $data['estatisticas'];
    echo "Total de horas: {$stats['total_horas']}h\n";
    echo "Dias trabalhados: {$stats['dias_trabalhados']}\n";
    echo "Conquistas: " . count($stats['conquistas']) . "\n";
} else {
    echo "Erro: " . $data['error'];
}
```

## Detalhes das Estatísticas

### Informações Básicas

- **total_horas**: Total de horas trabalhadas no mês
- **media_horas_dia**: Média de horas trabalhadas por dia
- **saldo_horas**: Diferença entre horas extras e devidas
- **dias_trabalhados**: Número de dias com registro de ponto
- **dias_uteis_total**: Total de dias úteis no mês (ou até a data atual)
- **dias_faltados**: Número de dias úteis sem registro de ponto

### Pontualidade

- **atrasos**: Número de atrasos registrados
- **media_minutos_atraso**: Média de minutos de atraso
- **media_entrada**: Horário médio de entrada
- **media_saida**: Horário médio de saída

### Carga Horária

- **carga_horaria_diaria**: Horas esperadas por dia (6h)
- **carga_horaria_esperada**: Total de horas esperadas no mês
- **horas_extras**: Horas extras acumuladas
- **horas_devidas**: Horas que precisam ser compensadas

### Qualidade dos Registros

- **dias_completos**: Dias com carga horária completa
- **dias_incompletos**: Dias com carga horária incompleta

### Distribuição por Dia da Semana

- **dias_semana.registros**: Número de registros por dia
- **dias_semana.horas**: Total de horas por dia

### Conquistas

Possíveis conquistas que podem ser desbloqueadas:

#### Conquistas Básicas

- **Pontualidade Perfeita** (`pontualidade_perfeita`)

  - Ícone: `fa-solid fa-clock`
  - Cor: Verde (#28a745)
  - Requisito: Sem atrasos no mês

- **Assiduidade Perfeita** (`assiduidade_perfeita`)

  - Ícone: `fa-solid fa-calendar-check`
  - Cor: Azul (#17a2b8)
  - Requisito: 100% de presença no mês

- **Dedicação Extra** (`dedicacao_extra`)

  - Ícone: `fa-solid fa-star`
  - Cor: Amarelo (#ffc107)
  - Requisito: 10+ horas extras

- **Regularidade Perfeita** (`regularidade_perfeita`)
  - Ícone: `fa-solid fa-circle-check`
  - Cor: Roxo (#6f42c1)
  - Requisito: Todos os dias completos

#### Conquistas Avançadas

- **Maestro do Tempo** (`maestro_do_tempo`)

  - Ícone: `fa-solid fa-music`
  - Cor: Rosa (#e83e8c)
  - Requisito: Média de entrada e saída perfeita

- **Guardião da Consistência** (`guardião_da_consistência`)

  - Ícone: `fa-solid fa-shield-halved`
  - Cor: Vermelho (#dc3545)
  - Requisito: Sem horas devidas no mês

- **Mestre da Organização** (`mestre_da_organização`)

  - Ícone: `fa-solid fa-layer-group`
  - Cor: Verde água (#20c997)
  - Requisito: Média de 6h por dia

- **Guardião do Tempo** (`guardião_do_tempo`)
  - Ícone: `fa-solid fa-hourglass-half`
  - Cor: Laranja (#fd7e14)
  - Requisito: Total de horas exato do mês

#### Conquistas Especiais

- **Virtuoso da Pontualidade** (`virtuoso_da_pontualidade`)

  - Ícone: `fa-solid fa-gauge-high`
  - Cor: Azul claro (#0dcaf0)
  - Requisito: Média de atrasos < 5 minutos

- **Campeão da Consistência** (`campeão_da_consistência`)

  - Ícone: `fa-solid fa-trophy`
  - Cor: Dourado (#ffd700)
  - Requisito: 3 meses seguidos sem atrasos

- **Mestre da Eficiência** (`mestre_da_eficiência`)

  - Ícone: `fa-solid fa-chart-line`
  - Cor: Verde (#198754)
  - Requisito: Saldo positivo de horas no mês

- **Guardião da Excelência** (`guardião_da_excelência`)

  - Ícone: `fa-solid fa-crown`
  - Cor: Roxo (#6f42c1)
  - Requisito: Todas as conquistas do mês desbloqueadas

- **Virtuoso da Regularidade** (`virtuoso_da_regularidade`)

  - Ícone: `fa-solid fa-calendar-days`
  - Cor: Azul (#0d6efd)
  - Requisito: 90% dos dias com carga horária completa

- **Mestre da Adaptabilidade** (`mestre_da_adaptabilidade`)
  - Ícone: `fa-solid fa-arrows-rotate`
  - Cor: Roxo escuro (#6610f2)
  - Requisito: Média de entrada e saída dentro do padrão (±15 minutos)

### Observações sobre Conquistas

- As conquistas são calculadas automaticamente com base nas estatísticas do mês
- Algumas conquistas podem requerer histórico de meses anteriores
- Os ícones utilizam a biblioteca Font Awesome 6
- Cada conquista possui uma cor única para identificação visual
- As conquistas são retornadas apenas quando seus requisitos são atendidos

## Validações

- Usuário deve existir no sistema
- Mês deve estar entre 1 e 12
- Ano deve estar entre 2000 e 2100
- Período deve ter registros de ponto

## Observações

- A API considera apenas dias úteis (segunda a sexta)
- Horários são calculados em relação à carga horária diária de 6 horas
- Conquistas são calculadas com base nas estatísticas do mês
- A API retorna dados formatados e prontos para exibição

## Lógica de Cálculo

### Período de Análise

- Para o mês atual, a API considera apenas os dias até a data atual (hoje)
- Para meses anteriores, a API considera todo o mês
- Os cálculos de dias úteis são baseados nos dias de segunda a sexta-feira

### Dias Faltados

- Dias faltados são calculados como dias úteis (segunda a sexta) sem nenhum registro
- A API considera feriados como dias úteis, a menos que caiam em finais de semana
- Dias úteis no futuro (do mês atual) não são contabilizados como faltados

### Carga Horária

- O cálculo do saldo considera todos os dias úteis do período, incluindo os faltados
- Uma falta representa -6 horas no saldo total do mês
- Para o mês atual, o cálculo de carga horária esperada considera apenas os dias até hoje
