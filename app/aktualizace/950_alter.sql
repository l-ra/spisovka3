UPDATE [{tbls3}spisovy_znak] SET [stav] = 0 WHERE [selected] = 0;

ALTER TABLE [{tbls3}spisovy_znak] DROP COLUMN [selected];