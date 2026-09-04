-- ============================================================================
-- SIGA — Script de MIGRAÇÃO (v01.12 → v01.13)
-- ============================================================================
--
-- Cobre apenas as alterações de schema introduzidas na v01.13: a nova
-- funcionalidade de cargos de dirigentes (regra 34 das regras de negócio).
--
-- Pré-requisito: a sua base de dados já deve estar no schema da v01.12
-- (ou seja, já deve ter as tabelas orgaos/associados_orgaos e as colunas
-- Formador/InsigniaMadeira em associados). Se ainda não tiver, corra
-- primeiro SIGA_Migracao_original_para_v01.12.sql.
--
-- Idempotente: pode ser corrido mais do que uma vez sem risco (usa
-- IF NOT EXISTS / INSERT IGNORE).
--
-- Faça sempre uma cópia de segurança antes de correr:
--   mysqldump -u <utilizador> -p siga > backup_antes_da_v01.13.sql
--
-- Execução:
--   mysql -u <utilizador> -p siga < SIGA_Migracao_v01.12_para_v01.13.sql
--
-- ============================================================================

CREATE TABLE IF NOT EXISTS cargos (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(150) NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_cargos_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE IF NOT EXISTS associados_cargos (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    IdCargo INT NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    KEY ix_associados_cargos_associado (IdAssociado),
    KEY ix_associados_cargos_cargo (IdCargo),
    CONSTRAINT fk_associados_cargos_associado
        FOREIGN KEY (IdAssociado) REFERENCES associados(Id),
    CONSTRAINT fk_associados_cargos_cargo
        FOREIGN KEY (IdCargo) REFERENCES cargos(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

INSERT IGNORE INTO cargos (Designacao) VALUES ('Chefe da Companhia');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Subchefe da Companhia');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Colaborador da Chefia');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Chefe de Divisão');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Subchefe de Divisão');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Chefe de Secretaria');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Chefe de Finanças');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Assistente Confessional');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Chefe Regional');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Chefe Regional Adjunto');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Chefe Nacional');
INSERT IGNORE INTO cargos (Designacao) VALUES ('Chefe Nacional Adjunto');

-- ============================================================================
-- Fim da migração v01.12 → v01.13.
-- ============================================================================
