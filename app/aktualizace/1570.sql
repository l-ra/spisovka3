
SELECT [id] INTO @RESOURCE_ID FROM [acl_resource] WHERE [code] = 'Dokument';

INSERT INTO [acl_privilege] ([resource_id], [name], [privilege], [order])
  VALUES (@RESOURCE_ID, 'Změny všech dokumentů', 'menit_vse', 20);
SET @PRIVILEGE_ID = LAST_INSERT_ID();

UPDATE [acl_privilege] SET [order] = 5 WHERE [privilege] = 'cist_moje_oj';
UPDATE [acl_privilege] SET [order] = 10 WHERE [privilege] = 'cist_vse';
UPDATE [acl_privilege] SET [order] = 15 WHERE [privilege] = 'menit_moje_oj';
UPDATE [acl_privilege] SET [order] = 25 WHERE [privilege] = 'znovu_otevrit';

SELECT [id] INTO @ROLE_ID FROM [acl_role] WHERE [code] = 'admin';

INSERT INTO [acl_role_to_privilege] ([role_id], [privilege_id], [allowed])
  VALUES (@ROLE_ID, @PRIVILEGE_ID, 'N');