------------------------;

ALTER TABLE `{tbls3}user`
  ADD COLUMN `orgjednotka_id` int(10) unsigned DEFAULT NULL;

ALTER TABLE `{tbls3}user`
  ADD CONSTRAINT `fk_user_orgjednotka1` FOREIGN KEY (`orgjednotka_id`) REFERENCES `{tbls3}orgjednotka` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

UPDATE `{tbls3}user` u JOIN (
  SELECT user_id, MIN( orgjednotka_id ) AS oj
  FROM `{tbls3}user_to_role` u2r
  JOIN `{tbls3}user_role` ur ON u2r.role_id = ur.`id`
  GROUP BY user_id
) AS sq ON u.`id` = sq.user_id
SET u.orgjednotka_id = sq.oj;

DELETE FROM `{tbls3}user_rule` WHERE `privilege` LIKE 'orgjednotka_%';