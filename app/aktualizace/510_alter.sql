------------------------;

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`, `privilege`) VALUES
(3, 'Zobrazit', ''),
(6, 'Zobrazit / měnit', ''),
(9, 'Zobrazit / měnit', ''),
(10, 'Zobrazit / měnit', ''),
(11, 'Zobrazit / měnit', ''),
(12, 'Zobrazit / měnit', ''),
(13, 'Zobrazit', ''),
(14, 'Zobrazit / měnit', ''),
(15, 'Zobrazit / měnit', ''),
(16, 'Zobrazit / měnit', ''),
(17, 'Zobrazit', ''),
(21, 'Zobrazit / měnit', '');

UPDATE `{tbls3}user_resource` SET `name` = 'Administrace - spisy' WHERE `code` = 'Admin_SpisyPresenter';

ALTER TABLE `{tbls3}user_acl`
  DROP FOREIGN KEY `fk_user_acl_user_role1`,
  DROP FOREIGN KEY `user_acl_ibfk_1`;

ALTER TABLE `{tbls3}user_acl`
  ADD CONSTRAINT `fk_user_acl_user_role1` FOREIGN KEY (`role_id`) REFERENCES `{tbls3}user_role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `user_acl_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `{tbls3}user_rule` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

DELETE FROM `{tbls3}user_rule` WHERE `name` = 'Cron';
