ALTER TABLE [:PREFIX:orgjednotka]
  CHANGE [sekvence] [sekvence] varchar(150) NOT NULL,
  CHANGE [sekvence_string] [sekvence_string] varchar(1000) NOT NULL;

ALTER TABLE [:PREFIX:spis]
  CHANGE [sekvence] [sekvence] varchar(150) NOT NULL,
  CHANGE [sekvence_string] [sekvence_string] varchar(1000) NOT NULL;

ALTER TABLE [:PREFIX:spisovy_znak]
  CHANGE [sekvence] [sekvence] varchar(150) NOT NULL,
  CHANGE [sekvence_string] [sekvence_string] varchar(1000) NOT NULL;

ALTER TABLE [:PREFIX:user_role]
  CHANGE [sekvence] [sekvence] varchar(150) NOT NULL,
  CHANGE [sekvence_string] [sekvence_string] varchar(1000) NOT NULL;
