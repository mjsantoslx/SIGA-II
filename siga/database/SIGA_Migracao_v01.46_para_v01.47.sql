-- ============================================================================
-- SIGA — Script de MIGRAÇÃO (v01.46 → v01.47)
-- ============================================================================
--
-- Cobre a única alteração de schema introduzida na v01.47: novo campo NIF
-- (9 dígitos, tratado sempre como texto — nunca como número, para não
-- perder zeros à esquerda) na ficha do associado.
--
-- Pré-requisito: a sua base de dados já deve estar no schema da v01.46.
--
-- Idempotente: pode ser corrido mais do que uma vez sem risco (usa
-- IF NOT EXISTS).
--
-- Faça sempre uma cópia de segurança antes de correr:
--   mysqldump -u <utilizador> -p siga > backup_antes_da_v01.47.sql
--
-- Execução:
--   mysql -u <utilizador> -p siga < SIGA_Migracao_v01.46_para_v01.47.sql
--
-- ============================================================================

ALTER TABLE associados
    ADD COLUMN IF NOT EXISTS NIF CHAR(9) NULL AFTER NumeroCartaoUtente;

-- MariaDB não suporta "ADD CONSTRAINT IF NOT EXISTS" para CHECK — se esta
-- migração for corrida mais do que uma vez, o segundo ALTER abaixo falha
-- com "Duplicate check constraint name" (inofensivo; a validação já lá
-- está da primeira vez). Se isso acontecer, ignore o erro dessa linha.
ALTER TABLE associados
    ADD CONSTRAINT chk_associados_nif
        CHECK (
            NIF IS NULL
            OR NIF REGEXP '^[0-9]{9}$'
        );

-- ============================================================================
-- Fim da migração v01.46 → v01.47.
-- ============================================================================
