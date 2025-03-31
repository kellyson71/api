# Consulta de Registros de Ponto

## Descrição
API para consultar os registros de ponto de um usuário em um período específico ou as últimas batidas de ponto.

## Endpoints

### 1. Consulta por Período
**Endpoint:** `GET https://api.protocolosead.com/consultar_ponto.php`

#### Parâmetros
| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| usuario_id | int | Sim | ID do usuário |
| data_inicio | date | Sim | Data inicial (formato: YYYY-MM-DD) |
| data_fim | date | Sim | Data final (formato: YYYY-MM-DD) |

#### Exemplo de Requisição
```http
GET https://api.protocolosead.com/consultar_ponto.php?usuario_id=2&data_inicio=2024-03-01&data_fim=2024-03-31
```

#### Resposta de Sucesso
```json
{
    "success": true,
    "message": "Pontos consultados com sucesso",
    "total_registros": 42,
    "pontos": [
        {
            "id": 123,
            "usuario_id": 2,
            "data_hora": "2024-03-20 13:00:00",
            "tipo": "entrada",
            "latitude": -6.123456,
            "longitude": -38.123456,
            "endereco": "Rua Exemplo, 123",
            "observacoes": "Observação do ponto",
            "data_formatada": "20/03/2024 13:00:00",
            "tipo_formatado": "Entrada"
        }
    ]
}
```

### 2. Consulta das Últimas Batidas
**Endpoint:** `GET https://api.protocolosead.com/consultar_ultimos_pontos.php`

#### Parâmetros
| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| usuario_id | int | Sim | ID do usuário |

#### Exemplo de Requisição
```http
GET https://api.protocolosead.com/consultar_ultimos_pontos.php?usuario_id=2
```

#### Resposta de Sucesso
```json
{
    "success": true,
    "message": "Últimos pontos consultados com sucesso",
    "total_registros": 20,
    "pontos": [
        {
            "id": 123,
            "usuario_id": 2,
            "nome_usuario": "Nome do Usuário",
            "matricula": "123456",
            "data_hora": "2024-03-20 19:00:00",
            "tipo": "saida",
            "latitude": -6.123456,
            "longitude": -38.123456,
            "endereco": "Rua Exemplo, 123",
            "observacoes": "Observação do ponto",
            "data_formatada": "20/03/2024 19:00:00",
            "tipo_formatado": "Saida"
        }
    ]
}
```

#### Características da Consulta das Últimas Batidas
- Retorna as 20 batidas de ponto mais recentes
- Ordenadas do mais recente para o mais antigo
- Inclui dados do usuário (nome e matrícula)
- Formatação de data e hora em formato brasileiro
- Tipo de ponto formatado com primeira letra maiúscula

## Exemplos de Uso

### JavaScript
```javascript
// Consulta por período
fetch('https://api.protocolosead.com/consultar_ponto.php?usuario_id=2&data_inicio=2024-03-01&data_fim=2024-03-31')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Total de registros:', data.total_registros);
            data.pontos.forEach(ponto => {
                console.log(`${ponto.data_formatada} - ${ponto.tipo_formatado}`);
            });
        }
    });

// Consulta das últimas batidas
fetch('https://api.protocolosead.com/consultar_ultimos_pontos.php?usuario_id=2')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Últimas batidas:', data.pontos);
        }
    });
```

### PHP
```php
// Consulta por período
$url = 'https://api.protocolosead.com/consultar_ponto.php?usuario_id=2&data_inicio=2024-03-01&data_fim=2024-03-31';
$response = file_get_contents($url);
$data = json_decode($response, true);

// Consulta das últimas batidas
$url = 'https://api.protocolosead.com/consultar_ultimos_pontos.php?usuario_id=2';
$response = file_get_contents($url);
$data = json_decode($response, true);
```

## Validações
- O usuário deve existir no sistema
- As datas devem estar no formato correto (YYYY-MM-DD)
- A data final não pode ser maior que a data atual
- O ID do usuário deve ser um número positivo

## Observações
- Os registros são retornados em ordem cronológica
- A consulta das últimas batidas retorna no máximo 20 registros
- As datas são retornadas no formato brasileiro (dd/mm/yyyy)
- O tipo de ponto é formatado com primeira letra maiúscula
- A consulta das últimas batidas inclui informações adicionais do usuário
