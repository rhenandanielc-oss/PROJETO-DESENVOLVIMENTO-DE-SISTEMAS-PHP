-- =====================================================================
--  ESCOLA CONECTADA - Sistema Escolar Integrado
--  Banco de dados MySQL / MariaDB
--
--  Módulos:
--    1. Usuários e autenticação
--    2. Agenda de tarefas do aluno
--    3. Reserva de salas e laboratório de informática
--    4. Cantina escolar (produtos, pedidos e itens)
--    5. Help desk de TI (chamados e comentários)
--
--  Como importar:
--    - phpMyAdmin: aba "Importar" > selecione este arquivo > Executar
--    - Terminal:   mysql -u root -p < database/escola_db.sql
--
--  Usuários de demonstração (senha de todos: 123456)
--    admin@escola.com      (Administrador)
--    professor@escola.com  (Professor)
--    aluno@escola.com      (Aluno)
--    tecnico@escola.com    (Técnico de TI)
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '-03:00';  -- horário de Brasília (o PHP usa o mesmo fuso na conexão)

DROP DATABASE IF EXISTS escola_db;
CREATE DATABASE escola_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE escola_db;

-- ---------------------------------------------------------------------
-- 1. USUÁRIOS
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(120) NOT NULL UNIQUE,
    senha       VARCHAR(255) NOT NULL COMMENT 'Hash gerado por password_hash()',
    perfil      ENUM('admin','professor','aluno','tecnico') NOT NULL DEFAULT 'aluno',
    matricula   VARCHAR(20)  NULL,
    turma       VARCHAR(30)  NULL,
    ativo       TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. AGENDA DE TAREFAS
-- ---------------------------------------------------------------------
CREATE TABLE tarefas (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT NOT NULL,
    titulo        VARCHAR(150) NOT NULL,
    descricao     TEXT NULL,
    disciplina    VARCHAR(80)  NULL,
    data_entrega  DATE NOT NULL,
    prioridade    ENUM('baixa','media','alta') NOT NULL DEFAULT 'media',
    status        ENUM('pendente','em_andamento','concluida') NOT NULL DEFAULT 'pendente',
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tarefas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_tarefas_usuario_data (usuario_id, data_entrega)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. SALAS E RESERVAS
-- ---------------------------------------------------------------------
CREATE TABLE salas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(80) NOT NULL UNIQUE,
    tipo         ENUM('sala','laboratorio','auditorio') NOT NULL DEFAULT 'sala',
    capacidade   INT NOT NULL DEFAULT 30,
    localizacao  VARCHAR(100) NULL,
    recursos     TEXT NULL COMMENT 'Ex.: projetor, 30 computadores, ar-condicionado',
    ativa        TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE reservas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    sala_id      INT NOT NULL,
    usuario_id   INT NOT NULL,
    data         DATE NOT NULL,
    hora_inicio  TIME NOT NULL,
    hora_fim     TIME NOT NULL,
    finalidade   VARCHAR(200) NOT NULL,
    status       ENUM('pendente','aprovada','recusada','cancelada') NOT NULL DEFAULT 'pendente',
    observacao   VARCHAR(255) NULL,
    criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservas_sala    FOREIGN KEY (sala_id)    REFERENCES salas(id)    ON DELETE CASCADE,
    CONSTRAINT fk_reservas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_reservas_sala_data (sala_id, data)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. CANTINA
-- ---------------------------------------------------------------------
CREATE TABLE produtos (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL,
    descricao  VARCHAR(255) NULL,
    categoria  ENUM('salgado','doce','bebida','lanche','refeicao','outro') NOT NULL DEFAULT 'outro',
    preco      DECIMAL(10,2) NOT NULL,
    estoque    INT NOT NULL DEFAULT 0,
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pedidos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT NOT NULL,
    total            DECIMAL(10,2) NOT NULL DEFAULT 0,
    status           ENUM('pendente','preparando','pronto','entregue','cancelado') NOT NULL DEFAULT 'pendente',
    forma_pagamento  ENUM('dinheiro','pix','cartao') NOT NULL DEFAULT 'pix',
    observacao       VARCHAR(255) NULL,
    criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_pedidos_data (criado_em)
) ENGINE=InnoDB;

CREATE TABLE pedido_itens (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id       INT NOT NULL,
    produto_id      INT NOT NULL,
    quantidade      INT NOT NULL,
    preco_unitario  DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_itens_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)  ON DELETE CASCADE,
    CONSTRAINT fk_itens_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. HELP DESK (CHAMADOS DE TI)
-- ---------------------------------------------------------------------
CREATE TABLE chamados (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT NOT NULL COMMENT 'Quem abriu o chamado',
    tecnico_id     INT NULL     COMMENT 'Técnico responsável',
    titulo         VARCHAR(150) NOT NULL,
    descricao      TEXT NOT NULL,
    categoria      ENUM('hardware','software','rede','impressora','acesso','outro') NOT NULL DEFAULT 'outro',
    prioridade     ENUM('baixa','media','alta','critica') NOT NULL DEFAULT 'media',
    local          VARCHAR(100) NULL,
    status         ENUM('aberto','em_andamento','aguardando','resolvido','fechado') NOT NULL DEFAULT 'aberto',
    solucao        TEXT NULL,
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolvido_em   DATETIME NULL,
    CONSTRAINT fk_chamados_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_chamados_tecnico FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_chamados_status (status)
) ENGINE=InnoDB;

CREATE TABLE chamado_comentarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    chamado_id  INT NOT NULL,
    usuario_id  INT NOT NULL,
    mensagem    TEXT NOT NULL,
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_coment_chamado FOREIGN KEY (chamado_id) REFERENCES chamados(id) ON DELETE CASCADE,
    CONSTRAINT fk_coment_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- VIEWS (consultas prontas usadas nos relatórios)
-- ---------------------------------------------------------------------
CREATE VIEW vw_vendas_diarias AS
SELECT DATE(p.criado_em)  AS dia,
       COUNT(*)           AS qtd_pedidos,
       SUM(p.total)       AS faturamento
FROM pedidos p
WHERE p.status <> 'cancelado'
GROUP BY DATE(p.criado_em);

CREATE VIEW vw_chamados_detalhados AS
SELECT c.id, c.titulo, c.categoria, c.prioridade, c.status, c.local,
       u.nome AS solicitante, t.nome AS tecnico,
       c.criado_em, c.resolvido_em,
       TIMESTAMPDIFF(HOUR, c.criado_em, c.resolvido_em) AS horas_para_resolver
FROM chamados c
JOIN usuarios u      ON u.id = c.usuario_id
LEFT JOIN usuarios t ON t.id = c.tecnico_id;

CREATE VIEW vw_reservas_detalhadas AS
SELECT r.id, s.nome AS sala, s.tipo, r.data, r.hora_inicio, r.hora_fim,
       r.finalidade, r.status, u.nome AS solicitante, u.perfil
FROM reservas r
JOIN salas s    ON s.id = r.sala_id
JOIN usuarios u ON u.id = r.usuario_id;

-- =====================================================================
-- DADOS DE DEMONSTRAÇÃO
-- =====================================================================

-- Senha de todos: 123456
INSERT INTO usuarios (nome, email, senha, perfil, matricula, turma) VALUES
('Administrador',      'admin@escola.com',     '$2y$10$ILzwBIfjud7cvmf1dfyZ6.zH6znrHeVrIuBDSYDC8qlyTGJ2.2INu', 'admin',     NULL,       NULL),
('Prof. Carla Mendes', 'professor@escola.com', '$2y$10$ILzwBIfjud7cvmf1dfyZ6.zH6znrHeVrIuBDSYDC8qlyTGJ2.2INu', 'professor', NULL,       NULL),
('João Silva',         'aluno@escola.com',     '$2y$10$ILzwBIfjud7cvmf1dfyZ6.zH6znrHeVrIuBDSYDC8qlyTGJ2.2INu', 'aluno',     '2026001',  '2º DS - A'),
('Marcos Técnico',     'tecnico@escola.com',   '$2y$10$ILzwBIfjud7cvmf1dfyZ6.zH6znrHeVrIuBDSYDC8qlyTGJ2.2INu', 'tecnico',   NULL,       NULL),
('Ana Souza',          'ana@escola.com',       '$2y$10$ILzwBIfjud7cvmf1dfyZ6.zH6znrHeVrIuBDSYDC8qlyTGJ2.2INu', 'aluno',     '2026002',  '2º DS - A');

INSERT INTO tarefas (usuario_id, titulo, descricao, disciplina, data_entrega, prioridade, status) VALUES
(3, 'Trabalho de Banco de Dados', 'Modelagem ER do sistema da cantina', 'Banco de Dados', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'alta', 'em_andamento'),
(3, 'Lista de exercícios PHP',    'Exercícios 1 a 10 da apostila',      'Programação Web', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'media', 'pendente'),
(3, 'Leitura capítulo 4',         'Redes de computadores - TCP/IP',      'Redes',           DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'baixa', 'pendente'),
(3, 'Apresentação de HTML/CSS',   'Seminário em grupo',                  'Front-end',       DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'alta', 'concluida'),
(5, 'Projeto integrador',         'Definir escopo com o grupo',          'Projeto',         DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'alta', 'pendente');

INSERT INTO salas (nome, tipo, capacidade, localizacao, recursos) VALUES
('Laboratório de Informática 1', 'laboratorio', 30, 'Bloco B - 1º andar', '30 computadores, projetor, ar-condicionado'),
('Laboratório de Informática 2', 'laboratorio', 25, 'Bloco B - 1º andar', '25 computadores, lousa digital'),
('Sala 101',                     'sala',        40, 'Bloco A - Térreo',   'Projetor, quadro branco'),
('Sala 102',                     'sala',        40, 'Bloco A - Térreo',   'Quadro branco'),
('Auditório',                    'auditorio',  120, 'Bloco C',            'Som, projetor, palco');

INSERT INTO reservas (sala_id, usuario_id, data, hora_inicio, hora_fim, finalidade, status) VALUES
(3, 2, CURDATE(),                          '07:30', '09:10', 'Aula de Matemática - 1º DS',     'aprovada'),
(1, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00', '10:00', 'Aula prática de PHP',            'aprovada'),
(1, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '13:30', '15:30', 'Aula prática de Banco de Dados', 'pendente'),
(5, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '19:00', '21:00', 'Reunião de pais e mestres',      'aprovada'),
(2, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00', '16:00', 'Estudo em grupo - projeto',      'pendente');

INSERT INTO produtos (nome, descricao, categoria, preco, estoque) VALUES
('Coxinha',            'Coxinha de frango',            'salgado',  6.50, 40),
('Pão de queijo',      'Porção com 5 unidades',        'salgado',  5.00, 50),
('Esfiha de carne',    'Esfiha aberta',                'salgado',  6.00, 30),
('Misto quente',       'Pão de forma, queijo e presunto','lanche', 7.50, 25),
('Prato feito',        'Arroz, feijão, carne e salada','refeicao',18.00, 20),
('Suco natural 300ml', 'Laranja ou maracujá',          'bebida',   6.00, 35),
('Refrigerante lata',  '350ml',                        'bebida',   5.50, 48),
('Água mineral',       '500ml',                        'bebida',   3.00, 60),
('Brigadeiro',         'Unidade',                      'doce',     3.50, 40),
('Bolo de pote',       'Chocolate ou cenoura',         'doce',     8.00, 15);

INSERT INTO pedidos (id, usuario_id, total, status, forma_pagamento, criado_em) VALUES
(1, 3, 12.50, 'entregue',  'pix',      DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 5, 24.00, 'entregue',  'dinheiro', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 2, 13.50, 'pronto',    'cartao',   NOW()),
(4, 3, 10.50, 'pendente',  'pix',      NOW());

INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, subtotal) VALUES
(1, 1, 1, 6.50, 6.50),  (1, 6, 1, 6.00, 6.00),
(2, 5, 1, 18.00, 18.00),(2, 6, 1, 6.00, 6.00),
(3, 4, 1, 7.50, 7.50),  (3, 6, 1, 6.00, 6.00),
(4, 2, 1, 5.00, 5.00),  (4, 7, 1, 5.50, 5.50);

INSERT INTO chamados (usuario_id, tecnico_id, titulo, descricao, categoria, prioridade, local, status, solucao, criado_em, resolvido_em) VALUES
(2, 4,    'Projetor não liga',           'O projetor da Sala 101 não liga, luz vermelha piscando.', 'hardware',   'alta',   'Sala 101',      'resolvido',    'Lâmpada substituída.', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 4,    'Sem internet no Lab 1',       'Computadores da fileira 3 sem acesso à internet.',        'rede',       'critica','Laboratório 1', 'em_andamento', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(5, NULL, 'Esqueci a senha do portal',   'Não consigo acessar o portal do aluno.',                  'acesso',     'media',  'Secretaria',    'aberto',       NULL, NOW(), NULL),
(2, NULL, 'Impressora atolando papel',   'Impressora da sala dos professores atola toda hora.',    'impressora', 'baixa',  'Sala dos professores', 'aberto', NULL, NOW(), NULL);

INSERT INTO chamado_comentarios (chamado_id, usuario_id, mensagem) VALUES
(1, 4, 'Vou verificar ainda hoje.'),
(1, 4, 'Lâmpada trocada, projetor funcionando.'),
(2, 4, 'Switch do laboratório com defeito, aguardando peça.');
