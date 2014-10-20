-- ;

ALTER TABLE `{tbls3}epodatelna` CHANGE `email_signature` `email_id` VARCHAR(200) NULL DEFAULT NULL,
CHANGE `isds_signature` `isds_id` VARCHAR(45) NULL DEFAULT NULL;
