
RENAME TABLE [:PREFIX:user_acl] TO [:PREFIX:acl_role_to_privilege];
RENAME TABLE [:PREFIX:user_resource] TO [:PREFIX:acl_resource];
RENAME TABLE [:PREFIX:user_role] TO [:PREFIX:acl_role];
RENAME TABLE [:PREFIX:user_rule] TO [:PREFIX:acl_privilege];

ALTER TABLE [:PREFIX:acl_role_to_privilege]
    DROP FOREIGN KEY [acl_role_to_privilege_ibfk_1],
    CHANGE [rule_id] [privilege_id]  int(10) unsigned NOT NULL,
    ADD CONSTRAINT [acl_role_to_privilege_privilege] FOREIGN KEY ([privilege_id]) 
        REFERENCES [:PREFIX:acl_privilege] ([id]) ON DELETE CASCADE ON UPDATE NO ACTION;