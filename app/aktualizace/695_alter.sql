-- Změna primárního klíče v tabulce user_to_role;

ALTER TABLE `{tbls3}user_acl` DROP PRIMARY KEY, ADD PRIMARY KEY(role_id, rule_id), DROP COLUMN id;

ALTER TABLE `{tbls3}user_rule` 
    ADD COLUMN `order` INT NOT NULL DEFAULT '0',
    DROP COLUMN `note`;
