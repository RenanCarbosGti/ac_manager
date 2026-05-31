-- ============================================================
-- AC MANAGER — Script completo e definitivo
-- Banco de Dados MySQL | Versão final com tabela cliente
-- Importar pelo Workbench: File > Open SQL Script > Execute All
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES   = 0;

DROP DATABASE IF EXISTS ac_manager;
CREATE DATABASE ac_manager
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ac_manager;

-- ============================================================
-- TABELAS
-- ============================================================

CREATE TABLE usuario (
    idusuario   INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    senha       VARCHAR(255) NOT NULL,
    tipo        ENUM('admin','profissional') DEFAULT 'profissional',
    ativo       TINYINT(1) DEFAULT 1,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE profissional (
    idprofissional  INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100) NOT NULL,
    telefone        VARCHAR(20)  NOT NULL,
    idusuario       INT NULL,
    FOREIGN KEY (idusuario) REFERENCES usuario(idusuario) ON DELETE SET NULL
);

CREATE TABLE servico (
    idservico       INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100) NOT NULL,
    descricao       TEXT,
    validade_dias   INT DEFAULT NULL,
    preco           DECIMAL(10,2) NOT NULL DEFAULT 0.00
);

-- Tabela de clientes (um cliente pode ter vários equipamentos)
CREATE TABLE cliente (
    idcliente   INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    telefone    VARCHAR(20)  NOT NULL,
    endereco    VARCHAR(255) NOT NULL DEFAULT '',
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE equipamento (
    idequipamento   INT AUTO_INCREMENT PRIMARY KEY,
    codigo_qr       VARCHAR(50)  NOT NULL UNIQUE,
    idcliente       INT NULL,
    nome_cliente    VARCHAR(100) NOT NULL,
    endereco        VARCHAR(255) NOT NULL DEFAULT '',
    telefone        VARCHAR(20)  NOT NULL DEFAULT '',
    modelo          VARCHAR(100),
    marca           VARCHAR(100),
    FOREIGN KEY (idcliente) REFERENCES cliente(idcliente) ON DELETE SET NULL
);

CREATE TABLE ordem_servico (
    idordem         INT AUTO_INCREMENT PRIMARY KEY,
    idequipamento   INT NOT NULL,
    idservico       INT NOT NULL,
    idprofissional  INT NOT NULL,
    data_servico    DATE NOT NULL,
    data_vencimento DATE NULL,
    preco_cobrado   DECIMAL(10,2) NOT NULL,
    observacoes     TEXT,
    status          ENUM('ativo','concluido','cancelado') DEFAULT 'ativo',
    FOREIGN KEY (idequipamento)  REFERENCES equipamento(idequipamento),
    FOREIGN KEY (idservico)      REFERENCES servico(idservico),
    FOREIGN KEY (idprofissional) REFERENCES profissional(idprofissional)
);

CREATE TABLE financeiro (
    idfinanceiro    INT AUTO_INCREMENT PRIMARY KEY,
    tipo            ENUM('entrada','saida') NOT NULL,
    descricao       VARCHAR(255) NOT NULL,
    valor           DECIMAL(10,2) NOT NULL,
    data_lancamento DATE NOT NULL,
    idordem         INT NULL,
    categoria       ENUM('servico','material','combustivel','ferramenta','outros') DEFAULT 'outros',
    observacoes     TEXT,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idordem) REFERENCES ordem_servico(idordem) ON DELETE SET NULL
);

CREATE TABLE material (
    idmaterial      INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100) NOT NULL,
    descricao       TEXT,
    unidade         VARCHAR(20) NOT NULL DEFAULT 'un',
    estoque_atual   DECIMAL(10,2) NOT NULL DEFAULT 0,
    estoque_minimo  DECIMAL(10,2) NOT NULL DEFAULT 1,
    preco_custo     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE estoque_movimentacao (
    idmovimentacao    INT AUTO_INCREMENT PRIMARY KEY,
    idmaterial        INT NOT NULL,
    tipo              ENUM('entrada','saida') NOT NULL,
    quantidade        DECIMAL(10,2) NOT NULL,
    motivo            VARCHAR(255),
    idordem           INT NULL,
    data_movimentacao DATE NOT NULL,
    criado_em         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idmaterial) REFERENCES material(idmaterial),
    FOREIGN KEY (idordem)    REFERENCES ordem_servico(idordem) ON DELETE SET NULL
);

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

INSERT INTO servico (nome, descricao, validade_dias, preco) VALUES
('Higienização Completa',  'Limpeza completa do evaporador e condensador', 180, 150.00),
('Limpeza de Filtro',      'Limpeza e higienização dos filtros',            90,  80.00),
('Manutenção Preventiva',  'Verificação geral do equipamento',              365, 200.00),
('Recarga de Gás',         'Recarga de gás refrigerante',                  NULL, 300.00),
('Instalação',             'Instalação de novo equipamento',               NULL, 250.00),
('Reparo Elétrico',        'Reparo em componentes elétricos',              NULL, 180.00);

INSERT INTO profissional (nome, telefone) VALUES
('João da Silva', '(32) 99999-0001'),
('Maria Santos',  '(32) 99999-0002');

INSERT INTO material (nome, descricao, unidade, estoque_atual, estoque_minimo, preco_custo) VALUES
('Gás R-410A',          'Gás refrigerante R-410A',           'kg', 5.0, 2.0, 85.00),
('Gás R-22',            'Gás refrigerante R-22',              'kg', 3.0, 1.0, 60.00),
('Filtro de Ar',        'Filtro para ar condicionado split',  'un', 10,  3,   12.00),
('Fita Isolante',       'Fita isolante 19mm',                 'un', 8,   2,    4.50),
('Abraçadeira Nylon',   'Abraçadeira 20cm pacote 100un',      'cx', 4,   1,   15.00),
('Produto Higienizante','Higienizante para AC 500ml',          'un', 6,   2,   22.00);

-- ⚠️  Usuário admin criado pelo setup.php
-- Acesse: http://localhost/Renan/ac_manager/setup.php
-- Login: admin@acmanager.com | Senha: admin123

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES   = 1;
