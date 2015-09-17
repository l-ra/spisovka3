
ALTER TABLE [{tbls3}dokument_historie] DROP FOREIGN KEY [fk_dokument_zmocneni10];
ALTER TABLE [{tbls3}dokument_historie] DROP COLUMN [zmocneni_id];

ALTER TABLE [{tbls3}dokument] DROP COLUMN [zmocneni_id];

DROP TABLE [{tbls3}zmocneni];