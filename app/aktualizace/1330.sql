ALTER TABLE [souvisejici_dokument]
    DROP PRIMARY KEY,
    ADD PRIMARY KEY ([dokument_id], [spojit_s_id]),
    DROP COLUMN [id], 
    DROP COLUMN [type];

    
