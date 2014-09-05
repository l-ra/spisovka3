-- ;

INSERT INTO `{tbls3}user_resource` (`code`, `name`) VALUES ('Sestava', 'Sestavy');
SET @RESOURCE_ID=LAST_INSERT_ID();

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`, `order`) VALUES (@RESOURCE_ID, 'Zobrazit', 'zobrazit', 10);
SET @RULE_READ=LAST_INSERT_ID();
INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`, `order`) VALUES (@RESOURCE_ID, 'Měnit', 'menit', 20);
SET @RULE_MODIFY=LAST_INSERT_ID();
INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`, `order`) VALUES (@RESOURCE_ID, 'Mazat', 'mazat', 30);

SELECT `id` INTO @OLD_RULE_ID FROM `{tbls3}user_rule` WHERE `name` = 'Sestavy';

-- Nahrad stare opraveni u roli novym, aby uzivatele meli stejny pristup jako drive;
UPDATE `{tbls3}user_acl` SET rule_id = @RULE_READ  WHERE rule_id = @OLD_RULE_ID;
INSERT INTO `{tbls3}user_acl` (role_id, rule_id, `allowed`) 
  SELECT role_id, @RULE_MODIFY, `allowed` FROM `{tbls3}user_acl` WHERE rule_id = @RULE_READ;

DELETE FROM `{tbls3}user_resource` WHERE `code` = 'Spisovka_SestavyPresenter';
