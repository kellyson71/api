# API de Gerenciamento de Usuários

## Descrição

API para gerenciar usuários do sistema (criar, atualizar e listar).

## Endpoints

### 1. Criar Usuário

```
POST https://api.protocolosead.com/gerenciar_usuarios.php
```

### 2. Atualizar Usuário

```
PUT https://api.protocolosead.com/gerenciar_usuarios.php?id={id}
```

### 3. Listar Usuários

```
GET https://api.protocolosead.com/gerenciar_usuarios.php
```

## Headers

```
Content-Type: application/json
```

## Parâmetros

### Criar Usuário (POST)

| Parâmetro    | Tipo   | Obrigatório | Descrição                                    |
| ------------ | ------ | ----------- | -------------------------------------------- |
| matricula    | string | Sim         | Matrícula do usuário                         |
| password     | string | Sim         | Senha do usuário                             |
| name         | string | Não         | Nome completo                                |
| email        | string | Sim         | Email do usuário                             |
| telefone     | string | Não         | Telefone do usuário                          |
| cargo        | string | Não         | Cargo do usuário (usuario/admin)             |
| hora_entrada | time   | Não         | Horário padrão de entrada (padrão: 08:00:00) |
| hora_saida   | time   | Não         | Horário padrão de saída (padrão: 18:00:00)   |

### Atualizar Usuário (PUT)

| Parâmetro    | Tipo   | Obrigatório | Descrição                 |
| ------------ | ------ | ----------- | ------------------------- |
| id           | int    | Sim         | ID do usuário (na URL)    |
| name         | string | Não         | Nome completo             |
| email        | string | Não         | Email do usuário          |
| telefone     | string | Não         | Telefone do usuário       |
| password     | string | Não         | Nova senha                |
| cargo        | string | Não         | Cargo do usuário          |
| hora_entrada | time   | Não         | Horário padrão de entrada |
| hora_saida   | time   | Não         | Horário padrão de saída   |

## Exemplos de Requisição

### Criar Usuário

```json
{
  "matricula": "123456",
  "password": "senha123",
  "name": "Nome do Usuário",
  "email": "email@exemplo.com",
  "telefone": "11999999999",
  "cargo": "usuario"
}
```

### Atualizar Usuário

```json
{
  "name": "Novo Nome",
  "email": "novo@email.com",
  "telefone": "11988888888"
}
```

## Respostas

### Sucesso na Criação

```json
{
  "success": true,
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
  }
}
```

### Sucesso na Atualização

```json
{
  "success": true,
  "usuario": {
    "id": 1,
    "matricula": "123456",
    "name": "Novo Nome",
    "email": "novo@email.com",
    "telefone": "11988888888",
    "cargo": "usuario",
    "hora_entrada": "08:00:00",
    "hora_saida": "18:00:00",
    "created_at": "2024-03-29 02:04:53"
  }
}
```

### Listagem de Usuários

```json
{
  "success": true,
  "usuarios": [
    {
      "id": 1,
      "matricula": "123456",
      "name": "Nome do Usuário",
      "email": "email@exemplo.com",
      "telefone": "11999999999",
      "cargo": "usuario",
      "hora_entrada": "08:00:00",
      "hora_saida": "18:00:00",
      "created_at": "2024-03-29 02:04:53"
    }
    // ... mais usuários ...
  ]
}
```

### Erro

```json
{
  "success": false,
  "error": "Mensagem de erro",
  "errors": ["Email inválido", "Telefone inválido"]
}
```

## Exemplo de Uso em JavaScript

### Criar Usuário

```javascript
fetch("https://api.protocolosead.com/gerenciar_usuarios.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    matricula: "123456",
    password: "senha123",
    name: "Nome do Usuário",
    email: "email@exemplo.com",
  }),
})
  .then((response) => response.json())
  .then((data) => console.log(data));
```

### Atualizar Usuário

```javascript
fetch("https://api.protocolosead.com/gerenciar_usuarios.php?id=1", {
  method: "PUT",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    name: "Novo Nome",
    email: "novo@email.com",
  }),
})
  .then((response) => response.json())
  .then((data) => console.log(data));
```

### Listar Usuários

```javascript
fetch("https://api.protocolosead.com/gerenciar_usuarios.php")
  .then((response) => response.json())
  .then((data) => console.log(data));
```

## Validações

- Matrícula deve ser única
- Email deve ser válido
- Telefone deve ter 10 ou 11 dígitos
- Cargo deve ser "usuario" ou "admin"
- Senha é armazenada com hash seguro
- Campos obrigatórios são validados
- Valores padrão para hora_entrada e hora_saida
