
UPDATE [sestava] SET [sloupce] = REPLACE([sloupce], 'pocet_priloh', 'pocet_listu_priloh') WHERE [sloupce] IS NOT NULL;
UPDATE [sestava] SET [sloupce] = REPLACE([sloupce], 'pocet_nelistu', 'pocet_souboru') WHERE [sloupce] IS NOT NULL;
