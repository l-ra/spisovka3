
RENAME TABLE [user_acl] TO [acl_role_to_privilege];
RENAME TABLE [user_resource] TO [acl_resource];
RENAME TABLE [user_role] TO [acl_role];
RENAME TABLE [user_rule] TO [acl_privilege];

ALTER TABLE [acl_role_to_privilege]
    DROP FOREIGN KEY [acl_role_to_privilege_ibfk_1],
    CHANGE [rule_id] [privilege_id]  int(10) unsigned NOT NULL,
    ADD CONSTRAINT `acl_role_to_privilege_privilege` FOREIGN KEY (`privilege_id`) 
        REFERENCES `acl_privilege` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;