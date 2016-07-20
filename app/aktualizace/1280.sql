
ALTER TABLE [:PREFIX:dokument]
    CHANGE [pocet_priloh] [pocet_listu_priloh] int DEFAULT NULL,
    CHANGE [typ_prilohy] [nelistinne_prilohy] varchar(150) DEFAULT NULL;
