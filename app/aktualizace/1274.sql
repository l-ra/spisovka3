ALTER TABLE [:PREFIX:dokument]
  ADD COLUMN [is_forwarded] bit(1) NOT NULL DEFAULT 0,
  ADD COLUMN [forward_user_id] int(10) unsigned DEFAULT NULL,
  ADD COLUMN [forward_orgunit_id] int(10) unsigned DEFAULT NULL,
  ADD COLUMN [forward_note] text DEFAULT NULL;

UPDATE [:PREFIX:dokument] d, [:PREFIX:workflow] w SET d.forward_user_id = w.prideleno_id, d.forward_orgunit_id = w.orgjednotka_id
  WHERE d.id = w.dokument_id AND w.aktivni = 1 AND w.stav_osoby = 0;

UPDATE [:PREFIX:dokument] SET [is_forwarded] = [forward_user_id] IS NOT NULL OR [forward_orgunit_id] IS NOT NULL;

UPDATE [:PREFIX:dokument] d, [:PREFIX:workflow] w SET d.forward_note = w.poznamka
  WHERE d.id = w.dokument_id AND w.aktivni = 1;

ALTER TABLE [:PREFIX:dokument]
  ADD CONSTRAINT [fk_dokument_forward1] FOREIGN KEY ([forward_user_id]) REFERENCES [:PREFIX:user] ([id]) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT [fk_dokument_forward2] FOREIGN KEY ([forward_orgunit_id]) REFERENCES [:PREFIX:orgjednotka] ([id]) ON DELETE SET NULL ON UPDATE NO ACTION;
