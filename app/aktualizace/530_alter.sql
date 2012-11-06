------------------------;

CREATE TABLE `{tbls3}settings` (
`name` VARCHAR( 50 ) CHARACTER SET ascii NOT NULL ,
`value` VARCHAR( 250 ) NOT NULL ,
PRIMARY KEY ( `name` )
) ENGINE = InnoDB CHARACTER SET utf8;

-- Do tabulky je nutne hned pridat zaznam. Na hodnote nezalezi, ale zaznam s timto klicem tam musi byt;

INSERT INTO `{tbls3}settings` VALUES('db_revision', '0');