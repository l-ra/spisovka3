-- ----------------------;

INSERT INTO `{tbls3}user_resource` (`code`, `name`) VALUES ('Spisovna', 'Spisovna');
SET @RESOURCE_ID=LAST_INSERT_ID();

SELECT `id` INTO @SPISOVNA_ROLE_ID FROM `{tbls3}user_role` WHERE `code` = 'skartacni_dohled';
SELECT `id` INTO @KOMISE_ROLE_ID FROM `{tbls3}user_role` WHERE `code` = 'skartacni_komise';

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES (@RESOURCE_ID, 'Příjem dokumentů a spisů', 'prijem_dokumentu');
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@SPISOVNA_ROLE_ID, LAST_INSERT_ID(), 'Y');

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES (@RESOURCE_ID, 'Čtení dokumentů a spisů', 'cist_dokumenty');
SET @RULE_ID=LAST_INSERT_ID();
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@SPISOVNA_ROLE_ID, @RULE_ID, 'Y');
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@KOMISE_ROLE_ID, @RULE_ID, 'Y');

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES (@RESOURCE_ID, 'Upravit skartační režim', 'zmenit_skartacni_rezim');
SET @RULE_ID=LAST_INSERT_ID();
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@SPISOVNA_ROLE_ID, @RULE_ID, 'Y');
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@KOMISE_ROLE_ID, @RULE_ID, 'Y');

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES (@RESOURCE_ID, 'Zařadit dokumenty do skartačního řízení', 'skartacni_navrh');
SET @RULE_ID=LAST_INSERT_ID();
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@SPISOVNA_ROLE_ID, @RULE_ID, 'Y');
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@KOMISE_ROLE_ID, @RULE_ID, 'Y');

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES (@RESOURCE_ID, 'Skartační řízení', 'skartacni_rizeni');
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@KOMISE_ROLE_ID, LAST_INSERT_ID(), 'Y');

-- ----------------------;

UPDATE `{tbls3}user_role` SET `code` = 'spisovna2' WHERE `code` = 'spisovna';
UPDATE `{tbls3}user_role` SET `code` = 'spisovna' WHERE `code` = 'skartacni_dohled';

-- ----------------------;

DELETE FROM `{tbls3}user_resource` WHERE `code` IN ('Spisovna_DokumentyPresenter', 'Spisovna_SpisyPresenter', 'Spisovna_VyhledatPresenter', 'Spisovna_DefaultPresenter');
