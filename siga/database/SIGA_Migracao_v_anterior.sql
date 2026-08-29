-- ============================================================================
-- SIGA — Script de MIGRAÇÃO (versão anterior → versão actual)
-- ============================================================================
--
-- ⚠️ IMPORTANTE — LEIA ANTES DE EXECUTAR:
--
-- Este script destina-se a uma base de dados já existente, criada a partir
-- do script original fornecido no início deste projecto (antes de todas as
-- alterações abaixo descritas). NÃO o execute contra uma base de dados já
-- criada com o SIGA_Criacao_BD.sql actual — essa já tem tudo isto incluído.
--
-- Assunção adoptada (a confirmar): "a versão anterior" é o schema original
-- tal como fornecido antes de qualquer alteração feita ao longo deste
-- projecto — não uma versão intermédia específica (ex.: v01.09). Se isso
-- não corresponder à sua base de dados real, este script pode falhar a
-- meio (ex.: tentar acrescentar uma coluna que já existe) — nesse caso,
-- diga-nos em que ponto exacto está a sua base de dados para ajustarmos.
--
-- Este script foi escrito para ser o mais seguro possível de repetir
-- (idempotente): usa IF NOT EXISTS / IF EXISTS onde o MariaDB permite, e
-- INSERT IGNORE nas tabelas de referência com designação única. Ainda
-- assim, faça sempre uma cópia de segurança da base de dados antes de o
-- correr:
--
--   mysqldump -u <utilizador> -p siga > backup_antes_da_migracao.sql
--
-- Execução:
--   mysql -u <utilizador> -p siga < SIGA_Migracao_v_anterior.sql
--
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1. Tipos de evento novos (Desactivação, Reactivação, Correcção de secção)
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO tipos_evento (Designacao) VALUES ('Desactivação');
INSERT IGNORE INTO tipos_evento (Designacao) VALUES ('Reactivação');
INSERT IGNORE INTO tipos_evento (Designacao) VALUES ('Correcção de secção');


-- ----------------------------------------------------------------------------
-- 2. Tipo de contacto novo (Email Associativo)
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO tipos_contacto (Designacao) VALUES ('Email Associativo');


-- ----------------------------------------------------------------------------
-- 3. Reestruturação de associados_contactos_emergencia
--    Deixa de depender obrigatoriamente de um registo em "pessoas" — passa a
--    guardar Nome/Contacto directamente (regra 17.1 das regras de negócio).
--    Preserva os dados existentes: copia o nome da pessoa ligada e, se
--    existir, o contacto de telemóvel dessa pessoa.
-- ----------------------------------------------------------------------------

-- 3.1 Acrescentar as novas colunas (nullable, para poderem ser preenchidas antes de tornar Nome obrigatório).
ALTER TABLE associados_contactos_emergencia
    ADD COLUMN IF NOT EXISTS Nome VARCHAR(150) NULL AFTER IdAssociado;
ALTER TABLE associados_contactos_emergencia
    ADD COLUMN IF NOT EXISTS Contacto VARCHAR(50) NULL AFTER Nome;

-- 3.2 Preencher a partir dos dados existentes (só corre se a coluna IdPessoa ainda existir).
SET @coluna_idpessoa_existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'associados_contactos_emergencia'
      AND COLUMN_NAME = 'IdPessoa'
);

SET @sql_copiar_nome := IF(@coluna_idpessoa_existe > 0,
    'UPDATE associados_contactos_emergencia ace
     INNER JOIN pessoas p ON p.Id = ace.IdPessoa
     SET ace.Nome = p.Nome
     WHERE ace.Nome IS NULL',
    'SELECT 1');
PREPARE stmt_copiar_nome FROM @sql_copiar_nome;
EXECUTE stmt_copiar_nome;
DEALLOCATE PREPARE stmt_copiar_nome;

SET @sql_copiar_contacto := IF(@coluna_idpessoa_existe > 0,
    'UPDATE associados_contactos_emergencia ace
     SET ace.Contacto = (
         SELECT c.Valor FROM contactos c
         INNER JOIN tipos_contacto tc ON tc.Id = c.IdTipoContacto
         WHERE c.IdPessoa = ace.IdPessoa AND tc.Designacao = ''Telemóvel''
         ORDER BY c.Id LIMIT 1
     )
     WHERE ace.Contacto IS NULL',
    'SELECT 1');
PREPARE stmt_copiar_contacto FROM @sql_copiar_contacto;
EXECUTE stmt_copiar_contacto;
DEALLOCATE PREPARE stmt_copiar_contacto;

-- 3.3 Salvaguarda: se alguma linha tiver ficado sem Nome (não deveria
--     acontecer, já que IdPessoa era NOT NULL), preenche com um valor
--     visível para não bloquear o passo seguinte — reveja manualmente.
UPDATE associados_contactos_emergencia
SET Nome = '(por preencher — verificar manualmente)'
WHERE Nome IS NULL;

-- 3.4 Tornar Nome obrigatório, agora que está preenchido.
ALTER TABLE associados_contactos_emergencia
    MODIFY Nome VARCHAR(150) NOT NULL;

-- 3.5 Remover a antiga dependência de "pessoas" (só corre se ainda existir).
ALTER TABLE associados_contactos_emergencia
    DROP FOREIGN KEY IF EXISTS fk_ace_pessoa;
ALTER TABLE associados_contactos_emergencia
    DROP KEY IF EXISTS uk_ace_associado_pessoa;
ALTER TABLE associados_contactos_emergencia
    DROP KEY IF EXISTS ix_ace_pessoa;
ALTER TABLE associados_contactos_emergencia
    DROP COLUMN IF EXISTS IdPessoa;


-- ----------------------------------------------------------------------------
-- 4. Órgãos (tabela nova) — regras 29 e 30
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orgaos (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(150) NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_orgaos_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE IF NOT EXISTS associados_orgaos (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    IdOrgao INT NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    KEY ix_associados_orgaos_associado (IdAssociado),
    KEY ix_associados_orgaos_orgao (IdOrgao),
    CONSTRAINT fk_associados_orgaos_associado
        FOREIGN KEY (IdAssociado) REFERENCES associados(Id),
    CONSTRAINT fk_associados_orgaos_orgao
        FOREIGN KEY (IdOrgao) REFERENCES orgaos(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

INSERT IGNORE INTO orgaos (Designacao) VALUES ('Mesa do Indaba');
INSERT IGNORE INTO orgaos (Designacao) VALUES ('Conselho Fiscal');
INSERT IGNORE INTO orgaos (Designacao) VALUES ('Conselho Jurisdicional');
INSERT IGNORE INTO orgaos (Designacao) VALUES ('Academia de Formação');


-- ----------------------------------------------------------------------------
-- 5. Formador e insígnia de madeira (regra 32) — colunas novas em associados
-- ----------------------------------------------------------------------------
ALTER TABLE associados
    ADD COLUMN IF NOT EXISTS Formador TINYINT(1) NOT NULL DEFAULT 0 AFTER Activo;
ALTER TABLE associados
    ADD COLUMN IF NOT EXISTS InsigniaMadeira TINYINT(1) NOT NULL DEFAULT 0 AFTER Formador;
ALTER TABLE associados
    ADD COLUMN IF NOT EXISTS DataInsigniaMadeira DATE NULL AFTER InsigniaMadeira;


-- ============================================================================
-- Fim da migração.
-- ============================================================================
