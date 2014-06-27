------------------------;

CREATE TABLE IF NOT EXISTS `{tbls3}user_settings` (
  `id` int(10) unsigned NOT NULL,
  `settings` varchar(2000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `{tbls3}user_settings` 
  ADD CONSTRAINT `fk_user_settings_id1` FOREIGN KEY ( `id` ) REFERENCES `{tbls3}user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
