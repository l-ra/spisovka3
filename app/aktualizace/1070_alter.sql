ALTER TABLE [{tbls3}orgjednotka]
  CHANGE [sekvence] [sekvence] varchar(150) NOT NULL,
  CHANGE [sekvence_string] [sekvence_string] varchar(1000) NOT NULL;

ALTER TABLE [{tbls3}spis]
  CHANGE [sekvence] [sekvence] varchar(150) NOT NULL,
  CHANGE [sekvence_string] [sekvence_string] varchar(1000) NOT NULL;

ALTER TABLE [{tbls3}spisovy_znak]
  CHANGE [sekvence] [sekvence] varchar(150) NOT NULL,
  CHANGE [sekvence_string] [sekvence_string] varchar(1000) NOT NULL;

ALTER TABLE [{tbls3}user_role]
  CHANGE [sekvence] [sekvence] varchar(150) NOT NULL,
  CHANGE [sekvence_string] [sekvence_string] varchar(1000) NOT NULL;
