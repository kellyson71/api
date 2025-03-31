CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS logs_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    status VARCHAR(20) NOT NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir um usuário de teste (senha: teste123)
INSERT INTO usuarios (matricula, senha) VALUES 
('kellyson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); 