-- -----------------------------------------------------------------------------
-- revision 500
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

INSERT INTO `{tbls3}user_resource` (`code`, `name`) VALUES ('DatovaSchranka', 'Datová schránka');
INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES (LAST_INSERT_ID(), 'Odesílání datových zpráv', 'odesilani');

DELETE FROM `{tbls3}user_acl` WHERE `role_id` = 4 AND `rule_id` IN (38, 39);