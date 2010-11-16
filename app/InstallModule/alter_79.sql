-- -----------------------------------------------------------------------------
-- revision 79
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

ALTER TABLE `{tbls3}cislo_jednaci` ADD INDEX ( `orgjednotka_id` );
ALTER TABLE `{tbls3}cislo_jednaci` ADD INDEX ( `user_id` );
ALTER TABLE `{tbls3}cislo_jednaci` ADD INDEX ( `urad_zkratka` );

ALTER TABLE `{tbls3}dokument` ADD `datum_spousteci_udalosti` DATETIME NULL;

ALTER TABLE `{tbls3}dokument` ADD INDEX ( `cislojednaci_id` );
ALTER TABLE `{tbls3}dokument` ADD INDEX ( `typ_dokumentu_id` );
ALTER TABLE `{tbls3}dokument` ADD INDEX ( `spisovy_znak_id` );
ALTER TABLE `{tbls3}dokument` ADD INDEX ( `zmocneni_id` );
ALTER TABLE `{tbls3}dokument` ADD INDEX ( `zpusob_doruceni_id` );
ALTER TABLE `{tbls3}dokument` ADD INDEX ( `zpusob_vyrizeni_id` );

ALTER TABLE `{tbls3}spousteci_udalost` ADD `poznamka_k_datumu` VARCHAR( 150 ) NOT NULL;

UPDATE `{tbls3}spousteci_udalost` SET `poznamka_k_datumu` = 'datum ukončení platnosti dokumentu' WHERE `{tbls3}spousteci_udalost`.`id` =1;
UPDATE `{tbls3}spousteci_udalost` SET `poznamka_k_datumu` = 'ukončení záruky' WHERE `{tbls3}spousteci_udalost`.`id` =2;
UPDATE `{tbls3}spousteci_udalost` SET `stav` = '2', `poznamka_k_datumu` = 'datum uzavření/vyřízení dokumentu' WHERE `{tbls3}spousteci_udalost`.`id` =3;
UPDATE `{tbls3}spousteci_udalost` SET `stav` = '2', `poznamka_k_datumu` = 'datum zařazení dokumentů' WHERE `{tbls3}spousteci_udalost`.`id` =4;
UPDATE `{tbls3}spousteci_udalost` SET `poznamka_k_datumu` = 'datum vyhotovení dokumentu' WHERE `{tbls3}spousteci_udalost`.`id` =5;
UPDATE `{tbls3}spousteci_udalost` SET `poznamka_k_datumu` = 'datum posledního použití nebo ukončení použití' WHERE `{tbls3}spousteci_udalost`.`id` =6;
UPDATE `{tbls3}spousteci_udalost` SET `poznamka_k_datumu` = 'datum vyhlášení výsledku voleb' WHERE `{tbls3}spousteci_udalost`.`id` =7;

ALTER TABLE `{tbls3}user_rule` ADD INDEX ( `resource_id` );

INSERT INTO `{tbls3}user_resource` (`id` ,`code` ,`note` ,`name` )
VALUES (NULL , 'Admin_AkonverzePresenter', '', 'Administrace - Autorizovaná konverze');

INSERT INTO `{tbls3}user_resource` (`id`, `code`, `note`, `name`)
VALUES (NULL, 'Spisovka_NapovedaPresenter', '', 'Nápověda');
INSERT INTO `{tbls3}user_rule` (`id` ,`name` ,`note` ,`resource_id` ,`privilege` )
VALUES (NULL , 'Nápověda', '', '31', NULL );