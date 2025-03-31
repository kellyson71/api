# API de Registro de Ponto

## Descrição

API para registrar entradas e saídas de ponto dos usuários.

## Endpoint

```
POST https://api.protocolosead.com/registrar_ponto.php
```

## Headers

```
Content-Type: application/json
```

## Parâmetros

| Parâmetro   | Tipo    | Obrigatório | Descrição                               |
| ----------- | ------- | ----------- | --------------------------------------- |
| usuario_id  | int     | Sim         | ID do usuário                           |
| tipo        | string  | Sim         | Tipo do registro ("entrada" ou "saida") |
| latitude    | decimal | Não         | Latitude do local                       |
| longitude   | decimal | Não         | Longitude do local                      |
| endereco    | string  | Não         | Endereço do local                       |
| observacoes | string  | Não         | Observações adicionais                  |

## Exemplo de Requisição

```json
{
  "usuario_id": 1,
  "tipo": "entrada",
  "latitude": -23.55052,
  "longitude": -46.633308,
  "endereco": "Av. Paulista, 1000",
  "observacoes": "Chegada no trabalho"
}
```

## Resposta de Sucesso

```json
{
  "success": true,
  "message": "Ponto registrado com sucesso",
  "registro": {
    "id": 1,
    "usuario_id": 1,
    "data_hora": "2024-03-29 08:00:00",
    "tipo": "entrada",
    "latitude": "-23.55052000",
    "longitude": "-46.63330800",
    "endereco": "Av. Paulista, 1000",
    "observacoes": "Chegada no trabalho",
    "criado_em": "2024-03-29 08:00:00"
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
fetch("https://api.protocolosead.com/registrar_ponto.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    usuario_id: 1,
    tipo: "entrada",
    latitude: -23.55052,
    longitude: -46.633308,
    endereco: "Av. Paulista, 1000",
  }),
})
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      console.log("Ponto registrado:", data.registro);
    } else {
      console.error("Erro ao registrar ponto:", data.error);
    }
  })
  .catch((error) => console.error("Erro na requisição:", error));
```

## Exemplo de Uso em PHP

```php
$url = 'https://api.protocolosead.com/registrar_ponto.php';
$data = [
    'usuario_id' => 1,
    'tipo' => 'entrada',
    'latitude' => -23.550520,
    'longitude' => -46.633308,
    'endereco' => 'Av. Paulista, 1000'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['success']) {
    echo "Ponto registrado: " . $data['registro']['data_hora'];
} else {
    echo "Erro ao registrar ponto: " . $data['error'];
}
```

## Validações

- Não é possível registrar o mesmo tipo consecutivamente (entrada após entrada ou saída após saída)
- Tipo deve ser "entrada" ou "saida"
- Latitude e longitude devem ser números válidos
- Data/hora é registrada automaticamente
- Usuário deve existir no sistema

## Observações

- A API registra automaticamente a data e hora do registro
- Os campos de localização (latitude, longitude, endereço) são opcionais
- O sistema verifica a sequência correta de entradas e saídas
- As observações podem ser usadas para registrar informações adicionais
