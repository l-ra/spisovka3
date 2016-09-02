
ALTER TABLE [log_access] ENGINE = InnoDB;

ALTER TABLE [log_access]
  ADD CONSTRAINT [log_access_ibfk_user_id] FOREIGN KEY ([user_id]) REFERENCES [user] ([id]) ON DELETE CASCADE ON UPDATE NO ACTION;
