ALTER TABLE [{tbls3}user_settings] DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE [{tbls3}user_settings] CHANGE [settings] [settings] VARCHAR(5000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
