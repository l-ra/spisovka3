
ALTER TABLE [dokument_to_spis]
    DROP FOREIGN KEY [dokument_to_spis__user],
    DROP COLUMN [user_id],
    DROP COLUMN [poradi],
    DROP COLUMN [date_added];
