ALTER TABLE [{tbls3}dokument] ADD KEY [fk_dokument_spisovy_znak] ([spisovy_znak_id]),
 ADD CONSTRAINT [fk_dokument_spisovy_znak] FOREIGN KEY ([spisovy_znak_id])
 REFERENCES [{tbls3}spisovy_znak] ([id]) ON DELETE NO ACTION ON UPDATE NO ACTION;
