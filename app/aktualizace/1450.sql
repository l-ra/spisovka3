
ALTER TABLE [dokument]
    ADD COLUMN [spis_id] INT DEFAULT NULL AFTER [spousteci_udalost_id],
    ADD CONSTRAINT [dokument__spis] FOREIGN KEY ([spis_id]) REFERENCES [spis]([id])
    ON UPDATE NO ACTION ON DELETE NO ACTION;

UPDATE [dokument] d, [dokument_to_spis] ds SET d.[spis_id] = ds.[spis_id]
    WHERE d.[id] = ds.[dokument_id];

DROP TABLE [dokument_to_spis];
