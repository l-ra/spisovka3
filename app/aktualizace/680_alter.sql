------------------------;

INSERT INTO `{tbls3}user_resource` (`code`, `name`) VALUES ('Dokument', 'Dokumenty');
SET @RESOURCE_ID=LAST_INSERT_ID();

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES (@RESOURCE_ID, 'Čtení dokumentů svojí org. jednotky', 'cist_moje_oj');
INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES (@RESOURCE_ID, 'Čtení všech dokumentů', 'cist_vse');
INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES (@RESOURCE_ID, 'Změny dokumentů svojí org. jednotky', 'menit_moje_oj');
