-- Změna primárního klíče v tabulce user_to_role;

ALTER TABLE `{tbls3}user_to_role` DROP PRIMARY KEY, ADD PRIMARY KEY(user_id, role_id), DROP COLUMN id;
