-- ============================================================
-- SISTEMA GERENCIADOR DE SERVIÇOS DE AR CONDICIONADO
-- Banco de Dados MySQL | Estrutura baseada em projeto_oo
-- ============================================================

CREATE DATABASE IF NOT EXISTS ac_manager
CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

USE ac_manager;

-- Tabela de usuários (login)
CREATE TABLE IF NOT EXISTS usuario (
    idusuario   INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    senha       VARCHAR(255) NOT NULL,
    tipo        ENUM('admin','profissional') DEFAULT 'profissional',
    ativo       TINYINT(1) DEFAULT 1,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de profissionais
CREATE TABLE IF NOT EXISTS profissional (
    idprofissional  INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100) NOT NULL,
    telefone        VARCHAR(20)  NOT NULL,
    idusuario       INT NULL,
    FOREIGN KEY (idusuario) REFERENCES usuario(idusuario) ON DELETE SET NULL
);

-- Tabela de tipos de serviço
CREATE TABLE IF NOT EXISTS servico (
    idservico       INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100) NOT NULL,
    descricao       TEXT,
    validade_dias   INT DEFAULT NULL COMMENT 'NULL = sem recorrência',
    preco           DECIMAL(10,2) NOT NULL DEFAULT 0.00
);

-- Tabela de equipamentos
CREATE TABLE IF NOT EXISTS equipamento (
    idequipamento   INT AUTO_INCREMENT PRIMARY KEY,
    codigo_qr       VARCHAR(50) NOT NULL UNIQUE,
    nome_cliente    VARCHAR(100) NOT NULL,
    endereco        VARCHAR(255) NOT NULL,
    telefone        VARCHAR(20)  NOT NULL,
    modelo          VARCHAR(100),
    marca           VARCHAR(100)
);

-- Tabela de ordens de serviço
CREATE TABLE IF NOT EXISTS ordem_servico (
    idordem             INT AUTO_INCREMENT PRIMARY KEY,
    idequipamento       INT NOT NULL,
    idservico           INT NOT NULL,
    idprofissional      INT NOT NULL,
    data_servico        DATE NOT NULL,
    data_vencimento     DATE NULL,
    preco_cobrado       DECIMAL(10,2) NOT NULL,
    observacoes         TEXT,
    status              ENUM('ativo','concluido','cancelado') DEFAULT 'ativo',
    FOREIGN KEY (idequipamento)  REFERENCES equipamento(idequipamento),
    FOREIGN KEY (idservico)      REFERENCES servico(idservico),
    FOREIGN KEY (idprofissional) REFERENCES profissional(idprofissional)
);

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- ⚠️  NÃO inserimos o usuário admin aqui.
--     Após importar este SQL, acesse setup.php pelo navegador
--     para criar o admin com hash correto.
--     Exemplo: http://localhost/Renan/ac_manager/setup.php

-- Serviços padrão
INSERT INTO servico (nome, descricao, validade_dias, preco) VALUES
('Higienização Completa',  'Limpeza completa do evaporador e condensador', 180, 150.00),
('Limpeza de Filtro',      'Limpeza e higienização dos filtros',            90,  80.00),
('Manutenção Preventiva',  'Verificação geral do equipamento',              365, 200.00),
('Recarga de Gás',         'Recarga de gás refrigerante',                   NULL, 300.00),
('Instalação',             'Instalação de novo equipamento',                NULL, 250.00),
('Reparo Elétrico',        'Reparo em componentes elétricos',               NULL, 180.00);

-- Profissionais de exemplo
INSERT INTO profissional (nome, telefone) VALUES
('João da Silva',  '(32) 99999-0001'),
('Maria Santos',   '(32) 99999-0002');
