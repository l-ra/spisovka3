
DELETE FROM [acl_resource] WHERE [code] = 'Spisovna_ZapujckyPresenter';

INSERT INTO [acl_resource] ([code], [name]) VALUES ('Zapujcka', 'Spisovna - zápůjčky');

SET @RESOURCE_ID = LAST_INSERT_ID();

INSERT INTO [acl_privilege] ([resource_id], [name], [privilege]) VALUES (@RESOURCE_ID, 'Vytvořit žádost', 'vytvorit');
SET @PRIV_CREATE = LAST_INSERT_ID();
INSERT INTO [acl_privilege] ([resource_id], [name], [privilege]) VALUES (@RESOURCE_ID, 'Schválit žádost', 'schvalit');
SET @PRIV_APPROVE = LAST_INSERT_ID();

SELECT [id] INTO @ROLE_REFERENT FROM [acl_role] WHERE [code] = 'referent';
SELECT [id] INTO @ROLE_SPISOVNA FROM [acl_role] WHERE [code] = 'spisovna';

INSERT INTO [acl_role_to_privilege] ([role_id], [privilege_id], [allowed]) VALUES (@ROLE_REFERENT, @PRIV_CREATE, 'Y');
INSERT INTO [acl_role_to_privilege] ([role_id], [privilege_id], [allowed]) VALUES (@ROLE_SPISOVNA, @PRIV_CREATE, 'Y');
INSERT INTO [acl_role_to_privilege] ([role_id], [privilege_id], [allowed]) VALUES (@ROLE_SPISOVNA, @PRIV_APPROVE, 'Y');
