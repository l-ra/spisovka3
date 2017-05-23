
UPDATE [dokument] SET [user_created] = 1 WHERE [user_created] = 0;
UPDATE [dokument_to_file] SET [user_id] = 1 WHERE [user_id] = 0;
UPDATE [dokument_to_spis] SET [user_id] = 1 WHERE [user_id] = 0;
UPDATE [dokument_to_subjekt] SET [user_id] = 1 WHERE [user_id] = 0;
UPDATE [file] SET [user_modified] = [user_created] WHERE [user_modified] = 0;
DELETE FROM [log_access] WHERE [user_id] = 0;
UPDATE [orgjednotka] SET [user_created] = 1 WHERE [user_created] = 0;
UPDATE [orgjednotka] SET [user_modified] = [user_created] WHERE [user_modified] = 0;
UPDATE [osoba] SET [user_created] = 1 WHERE [user_created] = 0;
UPDATE [souvisejici_dokument] SET [user_id] = 1 WHERE [user_id] = 0;
UPDATE [spis] SET [user_created] = 1 WHERE [user_created] = 0;
UPDATE [spisovy_znak] SET [user_created] = 1 WHERE [user_created] = 0;
UPDATE [subjekt] SET [user_created] = 1 WHERE [user_created] = 0;