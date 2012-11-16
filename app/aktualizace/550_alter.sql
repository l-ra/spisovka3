------------------------;

DELETE FROM `{tbls3}user_resource` WHERE `code` IN ('ErrorPresenter', 'Spisovka_ErrorPresenter', 'DefaultPresenter');

ALTER TABLE `{tbls3}user_rule`
  ADD CONSTRAINT `fk_user_rule_user_resource1` FOREIGN KEY (`resource_id`) REFERENCES `{tbls3}user_resource` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

DELETE FROM `{tbls3}user_rule` WHERE `resource_id` IN (5);
