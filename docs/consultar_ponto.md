# API de Consulta de Ponto

## Descrição

API para consultar os registros de ponto de um usuário em um determinado período.

## Endpoint

```
GET https://api.protocolosead.com/consultar_ponto.php
```

## Parâmetros

| Parâmetro   | Tipo | Obrigatório | Descrição                                     |
| ----------- | ---- | ----------- | --------------------------------------------- |
| usuario_id  | int  | Sim         | ID do usuário                                 |
| data_inicio | date | Sim         | Data inicial do período (formato: YYYY-MM-DD) |
| data_fim    | date | Sim         | Data final do período (formato: YYYY-MM-DD)   |

## Exemplo de Requisição

```
GET https://api.protocolosead.com/consultar_ponto.php?usuario_id=1&data_inicio=2024-03-01&data_fim=2024-03-31
```

## Resposta de Sucesso

```json
{
  "success": true,
  "registros": [
    {
      "id": 1,
      "usuario_id": 1,
      "data_hora": "2024-03-29 08:00:00",
      "tipo": "entrada",
      "latitude": "-23.55052000",
      "longitude": "-46.63330800",
      "endereco": "Av. Paulista, 1000",
      "observacoes": "Chegada no trabalho"
    },
    {
      "id": 2,
      "usuario_id": 1,
      "data_hora": "2024-03-29 18:00:00",
      "tipo": "saida",
      "latitude": "-23.55052000",
      "longitude": "-46.63330800",
      "endereco": "Av. Paulista, 1000",
      "observacoes": "Saída do trabalho"
    }
  ],
  "resumo": {
    "total_registros": 2,
    "total_horas": 10,
    "registros_entrada": 1,
    "registros_saida": 1
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
  usuario_id: 1,
  data_inicio: "2024-03-01",
  data_fim: "2024-03-31",
});

fetch(`https://api.protocolosead.com/consultar_ponto.php?${params}`)
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      console.log("Registros:", data.registros);
      console.log("Resumo:", data.resumo);
    } else {
      console.error("Erro:", data.error);
    }
  })
  .catch((error) => console.error("Erro na requisição:", error));
```

## Exemplo de Uso em PHP

```php
$params = http_build_query([
    'usuario_id' => 1,
    'data_inicio' => '2024-03-01',
    'data_fim' => '2024-03-31'
]);

$url = "https://api.protocolosead.com/consultar_ponto.php?{$params}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['success']) {
    echo "Total de registros: " . $data['resumo']['total_registros'] . "\n";
    echo "Total de horas: " . $data['resumo']['total_horas'] . "\n";

    foreach ($data['registros'] as $registro) {
        echo "Data/Hora: {$registro['data_hora']} - Tipo: {$registro['tipo']}\n";
    }
} else {
    echo "Erro: " . $data['error'];
}
```

## Validações

- Usuário deve existir no sistema
- Data inicial deve ser anterior ou igual à data final
- Período máximo de consulta é de 31 dias
- Datas devem estar no formato YYYY-MM-DD

## Observações

- A API retorna todos os registros de ponto do período especificado
- Inclui um resumo com totais e contagens
- Os registros são ordenados por data/hora
- Campos de localização (latitude, longitude, endereço) são opcionais
- As observações podem conter informações adicionais sobre o registro
