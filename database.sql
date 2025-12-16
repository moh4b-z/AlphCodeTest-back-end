CREATE DATABASE IF NOT EXISTS db_contatos;

USE db_contatos;

-- TABELA DE PROFISSÕES

CREATE TABLE IF NOT EXISTS profissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB ;

-- TABELA DE CONTATOS 

CREATE TABLE IF NOT EXISTS contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    data_nascimento DATE NOT NULL,
    permite_notificacao_email BOOLEAN DEFAULT TRUE,
    id_profissao INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_profissao) REFERENCES profissoes(id)
) ENGINE=InnoDB ;

-- TABELA DE TELEFONES

CREATE TABLE IF NOT EXISTS telefones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL,
    e_celular BOOLEAN DEFAULT FALSE,
    tem_whatsapp BOOLEAN DEFAULT FALSE,
    permite_sms BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_celular (numero, e_celular)
) ENGINE=InnoDB ;

-- TABELA INTERMEDIÁRIA

CREATE TABLE IF NOT EXISTS contatos_telefones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_contato INT NOT NULL,
    id_telefone INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_contato) REFERENCES contatos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_telefone) REFERENCES telefones(id) ON DELETE CASCADE,
    UNIQUE KEY unique_contato_telefone (id_contato, id_telefone)
) ENGINE=InnoDB ;

-- TRIGGER - VALIDAÇÃO DE TELEFONE

DELIMITER $$

CREATE TRIGGER validar_telefone_antes_insert
BEFORE INSERT ON telefones
FOR EACH ROW
BEGIN
    IF NEW.e_celular = FALSE THEN
        IF NEW.tem_whatsapp = TRUE OR NEW.permite_sms = TRUE THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Telefone fixo não pode ter WhatsApp ou SMS';
        END IF;
    END IF;
END$$

CREATE TRIGGER validar_telefone_antes_update
BEFORE UPDATE ON telefones
FOR EACH ROW
BEGIN
    IF NEW.e_celular = FALSE THEN
        IF NEW.tem_whatsapp = TRUE OR NEW.permite_sms = TRUE THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Telefone fixo não pode ter WhatsApp ou SMS';
        END IF;
    END IF;
END$$

DELIMITER ;

-- VIEW - RETORNA DADOS FORMATADOS

CREATE OR REPLACE VIEW vw_contatos_completo AS
SELECT 
    c.id,
    c.nome,
    c.email,
    DATE_FORMAT(c.data_nascimento, '%d/%m/%Y') AS data_nascimento_br,
    TIMESTAMPDIFF(YEAR, c.data_nascimento, CURDATE()) AS idade,
    c.permite_notificacao_email,
    p.id AS profissao_id,
    p.nome AS profissao_nome,
    c.created_at,
    c.updated_at
FROM contatos c
INNER JOIN profissoes p ON c.id_profissao = p.id;

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_cadastrar_contato$$

CREATE PROCEDURE sp_cadastrar_contato(
    IN p_json JSON
)
BEGIN
    DECLARE v_id_contato INT;
    DECLARE v_id_telefone INT;
    DECLARE v_id_profissao INT;
    DECLARE v_numero_celular VARCHAR(20);
    DECLARE v_numero_telefone VARCHAR(20);
    DECLARE v_tem_whatsapp BOOLEAN;
    DECLARE v_permite_sms BOOLEAN;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Extrai dados do JSON
    SET v_numero_celular = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.telefone_celular.numero'));
    SET v_numero_telefone = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.telefone_fixo.numero'));
    SET v_tem_whatsapp = JSON_EXTRACT(p_json, '$.telefone_celular.tem_whatsapp');
    SET v_permite_sms = JSON_EXTRACT(p_json, '$.telefone_celular.permite_sms');
    
    -- 1. Trata Profissão
    SET v_id_profissao = (
        SELECT id FROM profissoes 
        WHERE nome = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.profissao'))
        LIMIT 1
    );
    
    IF v_id_profissao IS NULL THEN
        INSERT INTO profissoes (nome) 
        VALUES (JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.profissao')));
        SET v_id_profissao = LAST_INSERT_ID();
    END IF;
    
    -- 2. Insere Contato
    INSERT INTO contatos (nome, email, data_nascimento, permite_notificacao_email, id_profissao)
    VALUES (
        JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.nome')),
        JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.email')),
        JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.data_nascimento')),
        JSON_EXTRACT(p_json, '$.permite_notificacao_email'),
        v_id_profissao
    );
    SET v_id_contato = LAST_INSERT_ID();
    
    -- 3. Trata Telefone Celular (Reutiliza se existir)
    SET v_id_telefone = NULL;
    SELECT id INTO v_id_telefone FROM telefones WHERE numero = v_numero_celular AND e_celular = TRUE LIMIT 1;
    
    IF v_id_telefone IS NULL THEN
        INSERT INTO telefones (numero, e_celular, tem_whatsapp, permite_sms)
        VALUES (v_numero_celular, TRUE, v_tem_whatsapp, v_permite_sms);
        SET v_id_telefone = LAST_INSERT_ID();
    ELSE
        -- Opcional: Atualiza capabilities se o telefone já existe
        UPDATE telefones SET tem_whatsapp = v_tem_whatsapp, permite_sms = v_permite_sms WHERE id = v_id_telefone;
    END IF;
    
    INSERT INTO contatos_telefones (id_contato, id_telefone) VALUES (v_id_contato, v_id_telefone);
    
    -- 4. Trata Telefone Fixo (Reutiliza se existir)
    IF v_numero_telefone IS NOT NULL AND v_numero_telefone != 'null' AND v_numero_telefone != '' THEN
        SET v_id_telefone = NULL;
        SELECT id INTO v_id_telefone FROM telefones WHERE numero = v_numero_telefone AND e_celular = FALSE LIMIT 1;
        
        IF v_id_telefone IS NULL THEN
            INSERT INTO telefones (numero, e_celular, tem_whatsapp, permite_sms)
            VALUES (v_numero_telefone, FALSE, FALSE, FALSE);
            SET v_id_telefone = LAST_INSERT_ID();
        END IF;
        
        -- Verifica se já não vinculou (caso bizarro de passar fixo igual celular)
        IF NOT EXISTS (SELECT 1 FROM contatos_telefones WHERE id_contato = v_id_contato AND id_telefone = v_id_telefone) THEN
            INSERT INTO contatos_telefones (id_contato, id_telefone) VALUES (v_id_contato, v_id_telefone);
        END IF;
    END IF;
    
    COMMIT;
    SELECT v_id_contato AS id;
END$$

DELIMITER $$

-- PROCEDURE - EDITAR CONTATO

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_editar_contato$$

CREATE PROCEDURE sp_editar_contato(
    IN p_id_contato INT,
    IN p_json JSON
)
BEGIN
    DECLARE v_id_profissao INT;
    DECLARE v_numero_fixo VARCHAR(20);
    DECLARE v_id_telefone_fixo INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Atualizações simples (Nome, Email, Nascimento, Notificação)
    -- Só atualiza os campos que foram enviados no JSON
    IF JSON_CONTAINS_PATH(p_json, 'one', '$.nome') THEN
        UPDATE contatos SET nome = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.nome')) WHERE id = p_id_contato;
    END IF;
    
    IF JSON_CONTAINS_PATH(p_json, 'one', '$.email') THEN
        UPDATE contatos SET email = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.email')) WHERE id = p_id_contato;
    END IF;
    
    IF JSON_CONTAINS_PATH(p_json, 'one', '$.data_nascimento') THEN
        UPDATE contatos SET data_nascimento = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.data_nascimento')) WHERE id = p_id_contato;
    END IF;
    
    IF JSON_CONTAINS_PATH(p_json, 'one', '$.permite_notificacao_email') THEN
        UPDATE contatos SET permite_notificacao_email = JSON_EXTRACT(p_json, '$.permite_notificacao_email') WHERE id = p_id_contato;
    END IF;
    
    -- Atualiza Profissão (só se foi enviada no JSON)
    IF JSON_CONTAINS_PATH(p_json, 'one', '$.profissao') THEN
        SET v_id_profissao = (
            SELECT id FROM profissoes WHERE nome = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.profissao')) LIMIT 1
        );
        IF v_id_profissao IS NULL THEN
            INSERT INTO profissoes (nome) VALUES (JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.profissao')));
            SET v_id_profissao = LAST_INSERT_ID();
        END IF;
        UPDATE contatos SET id_profissao = v_id_profissao WHERE id = p_id_contato;
    END IF;
    
    -- Atualiza Celular (atualiza cada campo individualmente se foi enviado)
    IF JSON_CONTAINS_PATH(p_json, 'one', '$.telefone_celular') THEN
        IF JSON_CONTAINS_PATH(p_json, 'one', '$.telefone_celular.numero') THEN
            UPDATE telefones t
            INNER JOIN contatos_telefones ct ON t.id = ct.id_telefone
            SET t.numero = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.telefone_celular.numero'))
            WHERE ct.id_contato = p_id_contato AND t.e_celular = TRUE;
        END IF;
        
        IF JSON_CONTAINS_PATH(p_json, 'one', '$.telefone_celular.tem_whatsapp') THEN
            UPDATE telefones t
            INNER JOIN contatos_telefones ct ON t.id = ct.id_telefone
            SET t.tem_whatsapp = JSON_EXTRACT(p_json, '$.telefone_celular.tem_whatsapp')
            WHERE ct.id_contato = p_id_contato AND t.e_celular = TRUE;
        END IF;
        
        IF JSON_CONTAINS_PATH(p_json, 'one', '$.telefone_celular.permite_sms') THEN
            UPDATE telefones t
            INNER JOIN contatos_telefones ct ON t.id = ct.id_telefone
            SET t.permite_sms = JSON_EXTRACT(p_json, '$.telefone_celular.permite_sms')
            WHERE ct.id_contato = p_id_contato AND t.e_celular = TRUE;
        END IF;
    END IF;
    
    -- Atualiza ou Insere Fixo (só se foi enviado)
    IF JSON_CONTAINS_PATH(p_json, 'one', '$.telefone_fixo.numero') THEN
        SET v_numero_fixo = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.telefone_fixo.numero'));
        
        IF v_numero_fixo IS NOT NULL AND v_numero_fixo != '' THEN
            -- Verifica se o contato já tem um telefone fixo vinculado
            SET v_id_telefone_fixo = (
                SELECT t.id FROM telefones t
                INNER JOIN contatos_telefones ct ON t.id = ct.id_telefone
                WHERE ct.id_contato = p_id_contato AND t.e_celular = FALSE
                LIMIT 1
            );
            
            IF v_id_telefone_fixo IS NOT NULL THEN
                -- Se já tem, atualiza o número
                UPDATE telefones SET numero = v_numero_fixo WHERE id = v_id_telefone_fixo;
            ELSE
                -- Se não tem, precisamos procurar se o número já existe no banco ou criar novo
                SELECT id INTO v_id_telefone_fixo FROM telefones WHERE numero = v_numero_fixo AND e_celular = FALSE LIMIT 1;
                
                IF v_id_telefone_fixo IS NULL THEN
                    INSERT INTO telefones (numero, e_celular, tem_whatsapp, permite_sms) 
                    VALUES (v_numero_fixo, FALSE, FALSE, FALSE);
                    SET v_id_telefone_fixo = LAST_INSERT_ID();
                END IF;
                
                -- Vincula
                INSERT INTO contatos_telefones (id_contato, id_telefone) VALUES (p_id_contato, v_id_telefone_fixo);
            END IF;
        END IF;
    END IF;
    
    COMMIT;
END$$

DELIMITER ;

-- DADOS DE EXEMPLO

INSERT INTO profissoes (nome) VALUES
('Desenvolvedor'),
('Designer'),
('Gerente de Projetos');

CALL sp_cadastrar_contato(JSON_OBJECT(
    'nome', 'João Silva',
    'email', 'joao@email.com',
    'data_nascimento', '1990-05-15',
    'permite_notificacao_email', TRUE,
    'profissao', 'Desenvolvedor',
    'telefone_celular', JSON_OBJECT('numero', '11987654321', 'tem_whatsapp', TRUE, 'permite_sms', TRUE),
    'telefone_fixo', JSON_OBJECT('numero', '1133334444')
));

CALL sp_cadastrar_contato(JSON_OBJECT(
    'nome', 'Maria Santos',
    'email', 'maria@email.com',
    'data_nascimento', '1985-08-20',
    'permite_notificacao_email', TRUE,
    'profissao', 'Designer',
    'telefone_celular', JSON_OBJECT('numero', '11912345678', 'tem_whatsapp', TRUE, 'permite_sms', FALSE),
    'telefone_fixo', NULL
));
