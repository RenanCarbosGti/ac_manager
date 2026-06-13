-- ============================================================
-- MIGRAÇÃO: Campo observacao em equipamento + permissão usuario
-- Execute no phpMyAdmin da InfinityFree
-- ============================================================
USE ac_manager;

-- 1. Adiciona campo observacao na tabela equipamento
ALTER TABLE equipamento
    ADD COLUMN observacao TEXT NULL AFTER marca;

-- 2. Garante que a coluna tipo aceita 'usuario' além de 'profissional'
ALTER TABLE usuario
    MODIFY tipo ENUM('admin','profissional','usuario') DEFAULT 'profissional';
