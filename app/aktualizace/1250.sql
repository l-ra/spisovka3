SELECT [id] INTO @RESOURCE_ID FROM [:PREFIX:user_resource] WHERE [code] = 'Dokument';

INSERT INTO [:PREFIX:user_rule] ([resource_id], [name], [privilege], [order])
  VALUES (@RESOURCE_ID, 'Otevření uzavřeného dokumentu', 'znovu_otevrit', 10);