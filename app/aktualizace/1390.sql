
ALTER TABLE [orgjednotka] MODIFY COLUMN [note] varchar(2000) DEFAULT NULL;

ALTER TABLE [spousteci_udalost]
  MODIFY COLUMN [nazev] varchar(600) NOT NULL,
  MODIFY COLUMN [poznamka] varchar(2000) DEFAULT NULL;

ALTER TABLE [subjekt] MODIFY COLUMN [poznamka] varchar(4000) DEFAULT NULL;

ALTER TABLE [zapujcka] MODIFY COLUMN [duvod] varchar(1000) DEFAULT NULL;

ALTER TABLE [dokument] MODIFY COLUMN [forward_note] varchar(1000) DEFAULT NULL;

