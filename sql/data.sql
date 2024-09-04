insert into llx_c_paiement (id,code,libelle,type,active) values (100, 'PNT', 'Conversión Puntos', 2,1);
ALTER TABLE llx_rewards MODIFY COLUMN points double(24,8) DEFAULT 0;
ALTER TABLE llx_rewards ADD date DATE NULL DEFAULT NULL;