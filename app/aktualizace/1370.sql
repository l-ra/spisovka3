
ALTER TABLE [sestava]
  MODIFY COLUMN [parametry] varchar(4000) DEFAULT NULL,
  MODIFY COLUMN [sloupce] varchar(1000) NOT NULL,
  MODIFY COLUMN [filtr] bit(1) NOT NULL DEFAULT 0,
  MODIFY COLUMN [zobrazeni_dat] varchar(500) DEFAULT NULL;
