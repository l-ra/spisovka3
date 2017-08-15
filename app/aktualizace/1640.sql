ALTER TABLE [file]
    DROP COLUMN [stav],
    DROP COLUMN [guid],
    CHANGE COLUMN [real_path] [storage_path] varchar(255) NOT NULL,
    CHANGE COLUMN [user_modified] [user_modified] int(10) unsigned DEFAULT NULL;

UPDATE [file] SET [date_modified] = NULL, [user_modified] = NULL WHERE [date_modified] = [date_created];
