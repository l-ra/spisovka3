
UPDATE [:PREFIX:dokument] d, [:PREFIX:workflow] w SET d.stav = w.stav_dokumentu
  WHERE d.id = w.dokument_id AND w.aktivni = 1;

UPDATE [:PREFIX:dokument] SET stav = 1 WHERE stav = 2;
