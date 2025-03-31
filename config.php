<?php

/**
 * Carrega as configurações do arquivo .env
 */

// Função para carregar as variáveis do arquivo .env
function loadEnv($envFile = '.env')
{
    if (!file_exists($envFile)) {
        die("Arquivo .env não encontrado!");
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Processar linha com variável de ambiente
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remover aspas
        if (strpos($value, '"') === 0 || strpos($value, "'") === 0) {
            $value = substr($value, 1, -1);
        }

        // Definir variável de ambiente
        if (!empty($name)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Carregar configurações
loadEnv();

// Funções auxiliares para obter as configurações
function getConfig($key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

function getDbConfig($param)
{
    $params = [
        'host' => 'DB_HOST',
        'dbname' => 'DB_NAME',
        'username' => 'DB_USER',
        'password' => 'DB_PASS'
    ];

    if (!isset($params[$param])) {
        return null;
    }

    return getConfig($params[$param]);
}

// Configurar timezone
date_default_timezone_set(getConfig('TIMEZONE', 'UTC'));

// Constantes com configurações de horário
define('HORARIO_ENTRADA', getConfig('HORARIO_ENTRADA', '13:00'));
define('HORARIO_SAIDA', getConfig('HORARIO_SAIDA', '19:00'));
define('CARGA_HORARIA_DIARIA', (int)getConfig('CARGA_HORARIA_DIARIA', 6));
