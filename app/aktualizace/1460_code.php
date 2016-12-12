<?php

function revision_1460_after()
{
    $res = dibi::query("SHOW COLUMNS FROM [acl_role_to_privilege] LIKE 'id'");
    if (count($res))
        dibi::query(
                'ALTER TABLE [acl_role_to_privilege]
    DROP PRIMARY KEY,
    DROP COLUMN [id],
    ADD PRIMARY KEY ([role_id], [privilege_id]);');
}
