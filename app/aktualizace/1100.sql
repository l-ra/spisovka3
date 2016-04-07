ALTER TABLE [{tbls3}user]
  ADD [osoba_id] int NULL AFTER [active];

UPDATE [{tbls3}user] u, [{tbls3}osoba_to_user] ou SET u.[osoba_id] = ou.[osoba_id]
  WHERE u.[id] = ou.[user_id];

ALTER TABLE [{tbls3}user]
  CHANGE [osoba_id] [osoba_id] int NOT NULL;

ALTER TABLE [{tbls3}user]
  ADD CONSTRAINT [fk_user_osoba] FOREIGN KEY ([osoba_id]) REFERENCES [{tbls3}osoba] ([id])
    ON DELETE NO ACTION ON UPDATE NO ACTION;

DROP TABLE [{tbls3}osoba_to_user];
