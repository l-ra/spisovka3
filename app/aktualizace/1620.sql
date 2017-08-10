
ALTER TABLE [epodatelna] ADD COLUMN [zfo_id] int(11) DEFAULT NULL AFTER [file_id];
ALTER TABLE [epodatelna] ADD CONSTRAINT [epodatelna__zfo] FOREIGN KEY ([zfo_id]) REFERENCES [file]([id]);

UPDATE [epodatelna] e INNER JOIN [file] f ON f.[nazev] = concat('ep-isds-', e.[id], '.zfo') SET e.[zfo_id] = f.[id];