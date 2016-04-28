ALTER TABLE [:PREFIX:user]
  ADD [osoba_id] int NULL AFTER [active];

UPDATE [:PREFIX:user] u, [:PREFIX:osoba_to_user] ou SET u.[osoba_id] = ou.[osoba_id]
  WHERE u.[id] = ou.[user_id];

ALTER TABLE [:PREFIX:user]
  CHANGE [osoba_id] [osoba_id] int NOT NULL;

ALTER TABLE [:PREFIX:user]
  ADD CONSTRAINT [fk_user_osoba] FOREIGN KEY ([osoba_id]) REFERENCES [:PREFIX:osoba] ([id])
    ON DELETE NO ACTION ON UPDATE NO ACTION;

DROP TABLE [:PREFIX:osoba_to_user];
