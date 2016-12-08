
ALTER TABLE [acl_role_to_privilege]
    DROP PRIMARY KEY,
    DROP COLUMN [id],
    ADD PRIMARY KEY ([role_id], [privilege_id]);