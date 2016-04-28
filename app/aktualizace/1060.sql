UPDATE [:PREFIX:spis] SET [parent_id] = NULL WHERE [parent_id] = 1;

DELETE FROM [:PREFIX:spis] WHERE [id] = 1;
