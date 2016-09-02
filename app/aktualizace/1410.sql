--
-- Omezení pro tabulku `acl_privilege`
--
ALTER TABLE `acl_privilege`
  ADD CONSTRAINT `acl_privilege__acl_resource` FOREIGN KEY (`resource_id`) REFERENCES `acl_resource` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `acl_role`
--
ALTER TABLE `acl_role`
  ADD CONSTRAINT `acl_role__orgjednotka` FOREIGN KEY (`orgjednotka_id`) REFERENCES `orgjednotka` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `acl_role__acl_role` FOREIGN KEY (`parent_id`) REFERENCES `acl_role` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `acl_role_to_privilege`
--
ALTER TABLE `acl_role_to_privilege`
  ADD CONSTRAINT `acl_role_to_privilege__acl_privilege` FOREIGN KEY (`privilege_id`) REFERENCES `acl_privilege` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `acl_role_to_privilege__acl_role` FOREIGN KEY (`role_id`) REFERENCES `acl_role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `cislo_jednaci`
--
ALTER TABLE `cislo_jednaci`
  ADD CONSTRAINT `cislo_jednaci__orgjednotka` FOREIGN KEY (`orgjednotka_id`) REFERENCES `orgjednotka` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `cislo_jednaci__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `dokument`
--
ALTER TABLE `dokument`
  ADD CONSTRAINT `dokument__cislo_jednaci` FOREIGN KEY (`cislo_jednaci_id`) REFERENCES `cislo_jednaci` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__dokument_typ` FOREIGN KEY (`dokument_typ_id`) REFERENCES `dokument_typ` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__user1` FOREIGN KEY (`forward_user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__orgjednotka1` FOREIGN KEY (`forward_orgunit_id`) REFERENCES `orgjednotka` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__user2` FOREIGN KEY (`owner_user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__orgjednotka2` FOREIGN KEY (`owner_orgunit_id`) REFERENCES `orgjednotka` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__spisovy_znak` FOREIGN KEY (`spisovy_znak_id`) REFERENCES `spisovy_znak` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__spousteci_udalost` FOREIGN KEY (`spousteci_udalost_id`) REFERENCES `spousteci_udalost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__user3` FOREIGN KEY (`user_created`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__user4` FOREIGN KEY (`user_modified`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__zpusob_doruceni` FOREIGN KEY (`zpusob_doruceni_id`) REFERENCES `zpusob_doruceni` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument__zpusob_vyrizeni` FOREIGN KEY (`zpusob_vyrizeni_id`) REFERENCES `zpusob_vyrizeni` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `dokument_odeslani`
--
ALTER TABLE `dokument_odeslani`
  ADD CONSTRAINT `dokument_odeslani__dokument` FOREIGN KEY (`dokument_id`) REFERENCES `dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_odeslani__epodatelna` FOREIGN KEY (`epodatelna_id`) REFERENCES `epodatelna` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_odeslani__subjekt` FOREIGN KEY (`subjekt_id`) REFERENCES `subjekt` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_odeslani__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_odeslani__zpusob_odeslani` FOREIGN KEY (`zpusob_odeslani_id`) REFERENCES `zpusob_odeslani` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `dokument_to_file`
--
ALTER TABLE `dokument_to_file`
  ADD CONSTRAINT `dokument_to_file__dokument` FOREIGN KEY (`dokument_id`) REFERENCES `dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_to_file__file` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_to_file__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `dokument_to_spis`
--
ALTER TABLE `dokument_to_spis`
  ADD CONSTRAINT `dokument_to_spis__dokument` FOREIGN KEY (`dokument_id`) REFERENCES `dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_to_spis__spis` FOREIGN KEY (`spis_id`) REFERENCES `spis` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_to_spis__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `dokument_to_subjekt`
--
ALTER TABLE `dokument_to_subjekt`
  ADD CONSTRAINT `dokument_to_subjekt__dokument` FOREIGN KEY (`dokument_id`) REFERENCES `dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_to_subjekt__subjekt` FOREIGN KEY (`subjekt_id`) REFERENCES `subjekt` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `dokument_to_subjekt__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `epodatelna`
--
ALTER TABLE `epodatelna`
  ADD CONSTRAINT `epodatelna__dokument` FOREIGN KEY (`dokument_id`) REFERENCES `dokument` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `epodatelna__file` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `epodatelna__subjekt` FOREIGN KEY (`subjekt_id`) REFERENCES `subjekt` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `epodatelna__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `file`
--
ALTER TABLE `file`
  ADD CONSTRAINT `file__user1` FOREIGN KEY (`user_created`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `file__user2` FOREIGN KEY (`user_modified`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `file_historie`
--
ALTER TABLE `file_historie`
  ADD CONSTRAINT `file_historie__file` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `file_historie__user` FOREIGN KEY (`user_created`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `log_access`
--
ALTER TABLE `log_access`
  ADD CONSTRAINT `log_access__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `log_dokument`
--
ALTER TABLE `log_dokument`
  ADD CONSTRAINT `log_dokument__dokument` FOREIGN KEY (`dokument_id`) REFERENCES `dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `log_dokument__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `log_spis`
--
ALTER TABLE `log_spis`
  ADD CONSTRAINT `log_spis__spis` FOREIGN KEY (`spis_id`) REFERENCES `spis` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `log_spis__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `orgjednotka`
--
ALTER TABLE `orgjednotka`
  ADD CONSTRAINT `orgjednotka__user1` FOREIGN KEY (`user_created`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `orgjednotka__user2` FOREIGN KEY (`user_modified`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `osoba`
--
ALTER TABLE `osoba`
  ADD CONSTRAINT `osoba__user1` FOREIGN KEY (`user_created`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `osoba__user2` FOREIGN KEY (`user_modified`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `souvisejici_dokument`
--
ALTER TABLE `souvisejici_dokument`
  ADD CONSTRAINT `souvisejici_dokument__dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `souvisejici_dokument__dokument2` FOREIGN KEY (`spojit_s_id`) REFERENCES `dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `souvisejici_dokument__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `spis`
--
ALTER TABLE `spis`
  ADD CONSTRAINT `spis__orgjednotka1` FOREIGN KEY (`orgjednotka_id`) REFERENCES `orgjednotka` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `spis__orgjednotka2` FOREIGN KEY (`orgjednotka_id_predano`) REFERENCES `orgjednotka` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `spis__spousteci_udalost` FOREIGN KEY (`spousteci_udalost_id`) REFERENCES `spousteci_udalost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `spis__user1` FOREIGN KEY (`user_created`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `spis__user2` FOREIGN KEY (`user_modified`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `spis__spisovy_znak` FOREIGN KEY (`spisovy_znak_id`) REFERENCES `spisovy_znak` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `spisovy_znak`
--
ALTER TABLE `spisovy_znak`
  ADD CONSTRAINT `spisovy_znak__spousteci_udalost` FOREIGN KEY (`spousteci_udalost_id`) REFERENCES `spousteci_udalost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `spisovy_znak__spisovy_znak` FOREIGN KEY (`parent_id`) REFERENCES `spisovy_znak` (`id`);

--
-- Omezení pro tabulku `subjekt`
--
ALTER TABLE `subjekt`
  ADD CONSTRAINT `subjekt__user1` FOREIGN KEY (`user_created`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `subjekt__user2` FOREIGN KEY (`user_modified`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user__orgjednotka` FOREIGN KEY (`orgjednotka_id`) REFERENCES `orgjednotka` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `user__osoba` FOREIGN KEY (`osoba_id`) REFERENCES `osoba` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings__user` FOREIGN KEY (`id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `user_to_role`
--
ALTER TABLE `user_to_role`
  ADD CONSTRAINT `user_to_role__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `user_to_role__acl_role` FOREIGN KEY (`role_id`) REFERENCES `acl_role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `zapujcka`
--
ALTER TABLE `zapujcka`
  ADD CONSTRAINT `zapujcka__user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
