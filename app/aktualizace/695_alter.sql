-- Změna primárního klíče v tabulce user_to_role;

ALTER TABLE `{tbls3}user_acl` DROP PRIMARY KEY, ADD PRIMARY KEY(role_id, rule_id), DROP COLUMN id;
