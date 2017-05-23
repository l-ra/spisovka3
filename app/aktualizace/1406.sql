DELETE FROM [souvisejici_dokument] WHERE [dokument_id] NOT IN (SELECT [id] FROM [dokument]);
DELETE FROM [souvisejici_dokument] WHERE [spojit_s_id] NOT IN (SELECT [id] FROM [dokument]);