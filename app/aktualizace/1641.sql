ALTER TABLE [dokument_to_file]
    DROP FOREIGN KEY [dokument_to_file__user],
    DROP COLUMN [active],
    DROP COLUMN [user_id],
    DROP COLUMN [date_added];
