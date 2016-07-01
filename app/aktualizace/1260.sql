
ALTER TABLE [:PREFIX:user_acl]
    DROP FOREIGN KEY [user_acl_ibfk_1];

ALTER TABLE [:PREFIX:user_acl]
    CHANGE [rule_id] [privilege_id]  int(10) unsigned NOT NULL,
    ADD CONSTRAINT [acl_role_to_privilege_privilege] FOREIGN KEY ([privilege_id]) 
        REFERENCES [:PREFIX:user_rule] ([id]) ON DELETE CASCADE ON UPDATE NO ACTION;

RENAME TABLE [:PREFIX:user_acl] TO [:PREFIX:acl_role_to_privilege];
RENAME TABLE [:PREFIX:user_resource] TO [:PREFIX:acl_resource];
RENAME TABLE [:PREFIX:user_role] TO [:PREFIX:acl_role];
RENAME TABLE [:PREFIX:user_rule] TO [:PREFIX:acl_privilege];
