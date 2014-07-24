-- Uživatelé, kteří upgradovali z 3.0.x verze nemají vytvořeny role pro spisovnu!! ;

-- Předpoklad - MUSÍ existovat role "referent" s ID 4. To kontroluje check skript. ;

INSERT IGNORE INTO `{tbls3}user_role` (`parent_id`, `fixed_id`, `orgjednotka_id`, `code`, `name`, `note`, `fixed`, `order`, `active`, `date_created`, `date_modified`, `sekvence`, `sekvence_string`) VALUES
(4, NULL, NULL, 'skartacni_dohled', 'pracovník spisovny', 'Má na starost spisovnu', 1, 30, 1, NOW(), NOW(), '4.7', 'referent.4#spisovna.7'),
(4, NULL, NULL, 'skartacni_komise', 'člen skartační komise', 'člen skartační komise, která rozhoduje o skartaci nebo archivaci dokumentu.', 1, 40, 1, NOW(), NOW(), '4.8', 'referent.4#skartacni_komise.8');

INSERT INTO `{tbls3}user_resource` (`code`, `name`) VALUES ('Spisovna', 'Spisovna');
SET @RESOURCE_ID=LAST_INSERT_ID();

SELECT `id` INTO @SPISOVNA_ROLE_ID FROM `{tbls3}user_role` WHERE `code` = 'skartacni_dohled';
SELECT `id` INTO @KOMISE_ROLE_ID FROM `{tbls3}user_role` WHERE `code` = 'skartacni_komise';

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`, `order`) VALUES (@RESOURCE_ID, 'Příjem dokumentů a spisů', 'prijem_dokumentu', 20);
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@SPISOVNA_ROLE_ID, LAST_INSERT_ID(), 'Y');

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`, `order`) VALUES (@RESOURCE_ID, 'Čtení dokumentů a spisů', 'cist_dokumenty', 10);
SET @RULE_ID=LAST_INSERT_ID();
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@SPISOVNA_ROLE_ID, @RULE_ID, 'Y');
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@KOMISE_ROLE_ID, @RULE_ID, 'Y');

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`, `order`) VALUES (@RESOURCE_ID, 'Upravit skartační režim', 'zmenit_skartacni_rezim', 30);
SET @RULE_ID=LAST_INSERT_ID();
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@SPISOVNA_ROLE_ID, @RULE_ID, 'Y');
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@KOMISE_ROLE_ID, @RULE_ID, 'Y');

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`, `order`) VALUES (@RESOURCE_ID, 'Zařadit dokumenty do skartačního řízení', 'skartacni_navrh', 40);
SET @RULE_ID=LAST_INSERT_ID();
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@SPISOVNA_ROLE_ID, @RULE_ID, 'Y');
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@KOMISE_ROLE_ID, @RULE_ID, 'Y');

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`, `order`) VALUES (@RESOURCE_ID, 'Skartační řízení', 'skartacni_rizeni', 50);
INSERT INTO `{tbls3}user_acl` (`role_id`, `rule_id`, `allowed`) VALUES (@KOMISE_ROLE_ID, LAST_INSERT_ID(), 'Y');

-- ----------------------;

UPDATE `{tbls3}user_role` SET `code` = 'spisovna2' WHERE `code` = 'spisovna';
UPDATE `{tbls3}user_role` SET `code` = 'spisovna' WHERE `code` = 'skartacni_dohled';

-- ----------------------;

DELETE FROM `{tbls3}user_resource` WHERE `code` IN ('Spisovna_DokumentyPresenter', 'Spisovna_SpisyPresenter', 'Spisovna_VyhledatPresenter', 'Spisovna_DefaultPresenter');
