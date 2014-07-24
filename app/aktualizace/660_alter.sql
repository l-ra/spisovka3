-- Pozn. zakomentovane prikazy by nemusely uspet

-- ALTER TABLE `{tbls3}spisovy_znak` ADD UNIQUE KEY `nazev` (`nazev`);

-- ALTER TABLE `{tbls3}spis` ADD UNIQUE KEY `nazev` (`nazev`);

ALTER TABLE `{tbls3}user_role` ADD UNIQUE KEY `code` (`code`);

-- Změny primárních klíčů;

ALTER TABLE `{tbls3}dokument_to_spis` DROP PRIMARY KEY, DROP KEY fk_dokument_to_spis_dokument1, ADD PRIMARY KEY(dokument_id), DROP COLUMN id, stav;

ALTER TABLE `{tbls3}user_to_role` DROP PRIMARY KEY, ADD PRIMARY KEY(user_id, role_id), DROP COLUMN id;

ALTER TABLE `{tbls3}user_acl` DROP PRIMARY KEY, ADD PRIMARY KEY(role_id, rule_id), DROP COLUMN id;

