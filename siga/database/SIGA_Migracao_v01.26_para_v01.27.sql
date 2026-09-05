-- ============================================================================
-- SIGA — Script de MIGRAÇÃO (v01.26 → v01.27)
-- ============================================================================
--
-- Cobre apenas as alterações de schema introduzidas na v01.27:
--  - novo tipo de relação "O Próprio" (regra 40 — encarregado de educação
--    de um associado adulto);
--  - novo cargo "Equipa Nacional de Clã", exclusivo de associados na
--    secção "Clã" (regra 39).
--
-- As restantes alterações desta versão (número de sócio automático,
-- nacionalidade Portuguesa em primeiro, restrições de email
-- associativo/cargos) são apenas de lógica da aplicação — não implicam
-- alterações de schema.
--
-- Pré-requisito: a sua base de dados já deve estar no schema da v01.26.
--
-- Idempotente: pode ser corrido mais do que uma vez sem risco (usa
-- INSERT IGNORE, que não duplica registos já existentes).
--
-- Faça sempre uma cópia de segurança antes de correr:
--   mysqldump -u <utilizador> -p siga > backup_antes_da_v01.27.sql
--
-- Execução:
--   mysql -u <utilizador> -p siga < SIGA_Migracao_v01.26_para_v01.27.sql
--
-- ============================================================================

INSERT IGNORE INTO tipos_relacao (Designacao) VALUES ('O Próprio');

INSERT IGNORE INTO cargos (Designacao) VALUES ('Equipa Nacional de Clã');

-- ============================================================================
-- Fim da migração v01.26 → v01.27.
-- ============================================================================
