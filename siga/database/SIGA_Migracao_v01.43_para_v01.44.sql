-- ============================================================================
-- SIGA — Script de MIGRAÇÃO (v01.43 → v01.44)
-- ============================================================================
--
-- Cobre as alterações de schema introduzidas na v01.44 — módulo de Censos
-- e o novo atributo "Membro Honorário" do associado. Não houve alterações
-- de schema entre a v01.27 e a v01.43 (só lógica de aplicação/documentação),
-- por isso este script cobre exactamente a mesma diferença que existiria
-- desde a v01.27.
--
-- Pré-requisito: a sua base de dados já deve estar no schema da v01.27 (ou
-- posterior — nada mudou de schema até à v01.43).
--
-- Idempotente: pode ser corrido mais do que uma vez sem risco (usa
-- IF NOT EXISTS / INSERT IGNORE).
--
-- Faça sempre uma cópia de segurança antes de correr:
--   mysqldump -u <utilizador> -p siga > backup_antes_da_v01.44.sql
--
-- Execução:
--   mysql -u <utilizador> -p siga < SIGA_Migracao_v01.43_para_v01.44.sql
--
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Membro honorário (regra 51.1)
-- ----------------------------------------------------------------------------
ALTER TABLE associados
    ADD COLUMN IF NOT EXISTS MembroHonorario TINYINT(1) NOT NULL DEFAULT 0 AFTER DataInsigniaMadeira;
ALTER TABLE associados
    ADD COLUMN IF NOT EXISTS DataInicioHonorario DATE NULL AFTER MembroHonorario;

INSERT IGNORE INTO tipos_evento (Designacao) VALUES ('Membro Honorário');


-- ----------------------------------------------------------------------------
-- 2. Anos escotistas — valores anuais do Censo (regra 51.2)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS anos_escotistas (
    Id INT NOT NULL AUTO_INCREMENT,
    AnoInicio SMALLINT NOT NULL COMMENT 'Ano civil em que o ano escotista começa (Outubro). Ex.: 2026 = ano escotista 2026/2027.',
    ValorSeguroEscotista DECIMAL(8,2) NOT NULL,
    ValorQuotaUEP DECIMAL(8,2) NOT NULL,
    ValorQuotaWFIS DECIMAL(8,2) NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_anos_escotistas_ano (AnoInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;


-- ----------------------------------------------------------------------------
-- 3. Registo de pagamento e histórico (regra 51.3)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS censos_associados (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    IdAnoEscotista INT NOT NULL,
    Pago TINYINT(1) NOT NULL DEFAULT 0,
    DataPagamento DATE NULL,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_censos_associado_ano (IdAssociado, IdAnoEscotista),
    KEY ix_censos_ano (IdAnoEscotista),
    CONSTRAINT fk_censos_associado FOREIGN KEY (IdAssociado) REFERENCES associados(Id),
    CONSTRAINT fk_censos_ano FOREIGN KEY (IdAnoEscotista) REFERENCES anos_escotistas(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE IF NOT EXISTS censos_historico (
    Id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdCenso INT NOT NULL,
    IdAssociado INT NOT NULL,
    IdAnoEscotista INT NOT NULL,
    IdUtilizador INT NOT NULL,
    DataHora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Operacao VARCHAR(20) NOT NULL COMMENT 'PAGAMENTO ou ANULACAO',
    DataPagamento DATE NULL,
    PRIMARY KEY (Id),
    KEY idx_ch_associado_ano (IdAssociado, IdAnoEscotista),
    CONSTRAINT fk_ch_censo FOREIGN KEY (IdCenso) REFERENCES censos_associados(Id),
    CONSTRAINT fk_ch_associado FOREIGN KEY (IdAssociado) REFERENCES associados(Id),
    CONSTRAINT fk_ch_ano FOREIGN KEY (IdAnoEscotista) REFERENCES anos_escotistas(Id),
    CONSTRAINT fk_ch_utilizador FOREIGN KEY (IdUtilizador) REFERENCES utilizadores(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- Fim da migração v01.43 → v01.44.
--
-- Nota: a tabela anos_escotistas fica vazia — use a página de administração
-- (/admin/anos-escotistas) para definir o primeiro ano escotista e os
-- respectivos valores (seguro escotista, quota UEP, quota WFIS).
-- ============================================================================
