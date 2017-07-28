
ALTER TABLE [epodatelna] ADD COLUMN [isds_envelope] varchar(2000) DEFAULT NULL;

UPDATE [settings] SET [value] = 'true' WHERE [name] = 'upgrade_needed';