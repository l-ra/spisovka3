<?php

function revision_890_after()
{
    $res = dibi::query('SELECT d.[id] FROM [:PREFIX:dokument] d        
JOIN [:PREFIX:epodatelna] e ON e.[dokument_id] = d.[id] AND e.[epodatelna_typ] = 0
WHERE [email_id] IS NOT NULL');
    $ids = $res->fetchPairs();

    dibi::query('UPDATE [:PREFIX:dokument] SET [zpusob_doruceni_id] = 1 WHERE [id] IN %in',
           $ids);

    $res = dibi::query('SELECT d.[id] FROM [:PREFIX:dokument] d        
JOIN [:PREFIX:epodatelna] e ON e.[dokument_id] = d.[id] AND e.[epodatelna_typ] = 0
WHERE [isds_id] IS NOT NULL');
    $ids = $res->fetchPairs();

    dibi::query('UPDATE [:PREFIX:dokument] SET [zpusob_doruceni_id] = 2 WHERE [id] IN %in',
           $ids);
    
}
