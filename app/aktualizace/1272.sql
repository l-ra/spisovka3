ALTER TABLE [:PREFIX:dokument]
  ADD COLUMN [owner_user_id] int(10) unsigned DEFAULT NULL,
  ADD COLUMN [owner_orgunit_id] int(10) unsigned DEFAULT NULL;

UPDATE [:PREFIX:dokument] d, [:PREFIX:workflow] w SET d.owner_user_id = w.prideleno_id, d.owner_orgunit_id = w.orgjednotka_id
  WHERE d.id = w.dokument_id AND w.aktivni = 1 AND w.stav_osoby = 1;

UPDATE [:PREFIX:dokument] SET [owner_user_id] = [user_created] WHERE [owner_user_id] IS NULL;

ALTER TABLE [:PREFIX:dokument]
  MODIFY COLUMN [owner_user_id] int(10) unsigned NOT NULL,
  ADD CONSTRAINT [fk_dokument_owner1] FOREIGN KEY ([owner_user_id]) REFERENCES [:PREFIX:user] ([id]) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT [fk_dokument_owner2] FOREIGN KEY ([owner_orgunit_id]) REFERENCES [:PREFIX:orgjednotka] ([id]) ON DELETE SET NULL ON UPDATE NO ACTION;
