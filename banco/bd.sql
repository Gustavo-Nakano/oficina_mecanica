CREATE DATABASE oficina;
USE oficina;

-- 1. ADMIN
CREATE TABLE admin (
    Id_admin INT PRIMARY KEY,
    nome_adm VARCHAR(100),
    tel_adm VARCHAR(20),
    email_adm VARCHAR(100)
);

CREATE TABLE funcionarios (
    tel_fun VARCHAR(20),
    nome_fun VARCHAR(100),
    Id_fun INT PRIMARY KEY,
    salario_fun DECIMAL(10,2),
    email_fun VARCHAR(100),
    horario_fun VARCHAR(50)
);


CREATE TABLE cliente (
    nome_clien VARCHAR(100),
    email_clien VARCHAR(100),
    telefone_clien VARCHAR(20),
    Id_clien INT PRIMARY KEY
);


CREATE TABLE estoque (
    Id_peca INT PRIMARY KEY,
    quant_peca INT,
    partnumber VARCHAR(50),
    Nome_peca VARCHAR(100),
    valor_peca DECIMAL(10,2)
);


CREATE TABLE veiculos (
    Id_clien INT,
    ano_veiculo VARCHAR(10),
    marca_veiculo VARCHAR(50),
    modelo_veiculo VARCHAR(50),
    Id_veiculo INT PRIMARY KEY,
    placa_veiculo VARCHAR(10),

    CONSTRAINT FK_veiculos_cliente
    FOREIGN KEY (Id_clien)
    REFERENCES cliente (Id_clien)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
);


CREATE TABLE agendamento (
    Id_agendamento INT PRIMARY KEY,
    data_agend DATE,
    hora_agend TIME,
    Id_veiculo INT,

    CONSTRAINT FK_agendamento_veiculos
    FOREIGN KEY (Id_veiculo)
    REFERENCES veiculos (Id_veiculo)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);


CREATE TABLE orcamento (
    Id_orcamento INT PRIMARY KEY,
    Id_fun INT,
    id_admin INT,
    total DECIMAL(10,2),
    data_orcamento DATE,
    Id_agendamento INT,

    CONSTRAINT FK_orcamento_admin
    FOREIGN KEY (id_admin)
    REFERENCES admin (Id_admin)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT,

    CONSTRAINT FK_orcamento_funcionario
    FOREIGN KEY (Id_fun)
    REFERENCES funcionarios (Id_fun)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT FK_orcamento_agendamento
    FOREIGN KEY (Id_agendamento)
    REFERENCES agendamento (Id_agendamento)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);


CREATE TABLE servicos (
    Id_servico INT PRIMARY KEY,
    nome_servico VARCHAR(100),
    descricao_servico VARCHAR(255)
);


CREATE TABLE notas_serv (
    data_servico DATE,
    Id_nota INT PRIMARY KEY,
    valor_total DECIMAL(10,2),
    Id_fun INT,
    Id_admin INT,
    Id_veiculo INT,
    Id_orcamento INT,
    Id_clien INT,

    CONSTRAINT FK_notas_funcionario
    FOREIGN KEY (Id_fun)
    REFERENCES funcionarios (Id_fun)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT,

    CONSTRAINT FK_notas_admin
    FOREIGN KEY (Id_admin)
    REFERENCES admin (Id_admin)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT FK_notas_veiculo
    FOREIGN KEY (Id_veiculo)
    REFERENCES veiculos (Id_veiculo)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT FK_notas_orcamento
    FOREIGN KEY (Id_orcamento)
    REFERENCES orcamento (Id_orcamento)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

    CONSTRAINT FK_notas_cliente
    FOREIGN KEY (Id_clien)
    REFERENCES cliente (Id_clien)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT
);


CREATE TABLE nota_servico (
    Id_servico INT,
    Id_notas INT,

    PRIMARY KEY (Id_servico, Id_notas),

    CONSTRAINT FK_nota_servico
    FOREIGN KEY (Id_servico)
    REFERENCES servicos (Id_servico)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT FK_nota_servico_notas
    FOREIGN KEY (Id_notas)
    REFERENCES notas_serv (Id_nota)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);


CREATE TABLE nota_peca (
    Id_item_np INT PRIMARY KEY,
    Id_peca INT,
    Id_nota INT,
    quant_pecas INT,
    valor_unit_peca DECIMAL(10,2),

    CONSTRAINT FK_nota_peca_estoque
    FOREIGN KEY (Id_peca)
    REFERENCES estoque (Id_peca)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT FK_nota_peca_notas
    FOREIGN KEY (Id_nota)
    REFERENCES notas_serv (Id_nota)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);


CREATE TABLE orcamento_serv (
    Id_serv INT,
    Id_orcamento INT,

    PRIMARY KEY (Id_serv, Id_orcamento),

    CONSTRAINT FK_orcamento_serv_servicos
    FOREIGN KEY (Id_serv)
    REFERENCES servicos (Id_servico)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT,

    CONSTRAINT FK_orcamento_serv_orcamento
    FOREIGN KEY (Id_orcamento)
    REFERENCES orcamento (Id_orcamento)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);


CREATE TABLE orcamento_peca (
    Id_orcamento INT,
    Id_peca INT,
    quant_peca INT,
    valor_unit DECIMAL(10,2),

    PRIMARY KEY (Id_orcamento, Id_peca),

    CONSTRAINT FK_orcamento_peca_orcamento
    FOREIGN KEY (Id_orcamento)
    REFERENCES orcamento (Id_orcamento)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

    CONSTRAINT FK_orcamento_peca_estoque
    FOREIGN KEY (Id_peca)
    REFERENCES estoque (Id_peca)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT
);