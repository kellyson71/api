# API de Login

## Descrição

API para autenticação de usuários no sistema de ponto virtual.

## Endpoint

```
POST https://api.protocolosead.com/login.php
```

## Headers

```
Content-Type: application/json
```

## Parâmetros

| Parâmetro | Tipo   | Obrigatório | Descrição            |
| --------- | ------ | ----------- | -------------------- |
| matricula | string | Sim         | Matrícula do usuário |
| senha     | string | Sim         | Senha do usuário     |

## Exemplo de Requisição

```json
{
  "matricula": "123456",
  "senha": "minhasenha123"
}
```

## Resposta de Sucesso

```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "usuario": {
    "id": 1,
    "matricula": "123456",
    "name": "Nome do Usuário",
    "email": "email@exemplo.com",
    "telefone": "11999999999",
    "cargo": "usuario",
    "hora_entrada": "08:00:00",
    "hora_saida": "18:00:00",
    "created_at": "2024-03-29 02:04:53"
  },
  "logs": [
    {
      "id": 1,
      "matricula": "123456",
      "ip_address": "192.168.1.1",
      "status": "sucesso",
      "error_message": null,
      "created_at": "2024-03-29 02:04:53"
    }
    // ... mais logs ...
  ]
}
```

## Resposta de Erro

```json
{
  "success": false,
  "error": "Matrícula ou senha incorretos",
  "logs": [
    {
      "id": 1,
      "matricula": "123456",
      "ip_address": "192.168.1.1",
      "status": "credenciais_invalidas",
      "error_message": "Matrícula ou senha incorretos",
      "created_at": "2024-03-29 02:04:53"
    }
  ]
}
```

## Exemplo de Uso em JavaScript

```javascript
fetch("https://api.protocolosead.com/login.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    matricula: "123456",
    senha: "minhasenha123",
  }),
})
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      console.log("Login realizado:", data.usuario);
      // Armazena dados do usuário
      localStorage.setItem("usuario", JSON.stringify(data.usuario));
    } else {
      console.error("Erro no login:", data.error);
    }
  })
  .catch((error) => console.error("Erro na requisição:", error));
```

## Exemplo de Uso em PHP

```php
$url = 'https://api.protocolosead.com/login.php';
$data = [
    'matricula' => '123456',
    'senha' => 'minhasenha123'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['success']) {
    echo "Login realizado: " . $data['usuario']['name'];
} else {
    echo "Erro no login: " . $data['error'];
}
```

## Observações

- A API registra automaticamente o IP do usuário
- Todas as tentativas de login são registradas
- A resposta inclui os últimos 10 logs de tentativas de login
- A senha é verificada usando hash seguro
- Informações sensíveis (como senha) são removidas da resposta
