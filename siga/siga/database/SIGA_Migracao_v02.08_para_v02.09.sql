-- ============================================================================
-- SIGA — Script de MIGRAÇÃO (v02.08 → v02.09)
-- ============================================================================
--
-- Única alteração de schema desta versão: tabela do registo de actividade
-- (regra 59).
--
-- Idempotente: pode ser corrido mais do que uma vez sem risco.
--
-- Faça sempre uma cópia de segurança antes de correr:
--   mysqldump -u <utilizador> -p siga > backup_antes_da_v02.09.sql
--
-- Execução:
--   mysql -u <utilizador> -p siga < SIGA_Migracao_v02.08_para_v02.09.sql
-- ============================================================================

CREATE TABLE IF NOT EXISTS registo_actividade (
    Id BIGINT NOT NULL AUTO_INCREMENT,
    DataHora DATETIME NOT NULL,
    IdUtilizador INT NULL,
    NomeUtilizador VARCHAR(100) NULL,
    Accao VARCHAR(30) NOT NULL,
    Descricao VARCHAR(500) NOT NULL,
    Resultado VARCHAR(10) NOT NULL DEFAULT 'sucesso',
    EnderecoIP VARCHAR(45) NULL,
    PRIMARY KEY (Id),
    KEY ix_registo_datahora (DataHora),
    KEY ix_registo_utilizador (IdUtilizador, DataHora),
    KEY ix_registo_accao (Accao, DataHora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;
