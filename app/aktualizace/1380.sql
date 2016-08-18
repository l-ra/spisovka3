
UPDATE [file] SET [nazev] = REPLACE([nazev], 'ep_isds_', 'ep-isds-') WHERE [nazev] LIKE 'ep_isds_%';
