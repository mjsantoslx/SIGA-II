-- ============================================================================
-- SIGA — Script de MIGRAÇÃO (v02.01 → v02.02)
-- ============================================================================
--
-- Cobre a única alteração de schema desta versão: novos parentescos
-- exclusivos de contactos de emergência (regra 54).
--
-- Pré-requisito: a sua base de dados já deve estar no schema da v02.01
-- (que inclui o NIF, introduzido na v01.47 — se a sua instalação for mais
-- antiga do que isso, aplique primeiro os scripts de migração
-- intermédios correspondentes).
--
-- Idempotente: pode ser corrido mais do que uma vez sem risco (usa
-- IF NOT EXISTS / INSERT IGNORE).
--
-- Faça sempre uma cópia de segurança antes de correr:
--   mysqldump -u <utilizador> -p siga > backup_antes_da_v02.02.sql
--
-- Execução:
--   mysql -u <utilizador> -p siga < SIGA_Migracao_v02.01_para_v02.02.sql
--
-- ============================================================================

ALTER TABLE tipos_relacao
    ADD COLUMN IF NOT EXISTS AplicavelEncarregadoEducacao TINYINT(1) NOT NULL DEFAULT 1 AFTER Designacao;

INSERT IGNORE INTO tipos_relacao (Designacao, AplicavelEncarregadoEducacao) VALUES
    ('Filho', 0),
    ('Filha', 0),
    ('Enteado', 0),
    ('Enteada', 0);

-- ============================================================================
-- Fim da migração v02.01 → v02.02.
-- ============================================================================
