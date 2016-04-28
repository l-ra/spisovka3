ALTER TABLE [:PREFIX:spis]
  DROP COLUMN [spisovy_znak],
  DROP COLUMN [spisovy_znak_plneurceny],
  CHANGE [typ] [typ] char(1) CHARACTER SET ascii NOT NULL DEFAULT 'S',
  ADD INDEX [stav] ([stav]);

