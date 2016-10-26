
ALTER TABLE [dokument_to_subjekt]
    DROP PRIMARY KEY,
    DROP COLUMN [id],
    ADD PRIMARY KEY ([dokument_id], [subjekt_id]);
