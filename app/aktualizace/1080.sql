ALTER TABLE [{tbls3}orgjednotka]
  CHANGE [plny_nazev] [plny_nazev] varchar(200) DEFAULT NULL,
  CHANGE [zkraceny_nazev] [zkraceny_nazev] varchar(100) NOT NULL,
  ADD UNIQUE [ciselna_rada] ( [ciselna_rada] );

