<?php

/**
 * Pozn.: Toto mohlo být řešeno jako subquery, ale to mě tehdy nenapadlo.
 * Teď už nemá cenu tento kód přepisovat.
 */
function revision_890_after()
{
    $res = dibi::query('SELECT d.[id] FROM [dokument] d        
JOIN [epodatelna] e ON e.[dokument_id] = d.[id] AND e.[epodatelna_typ] = 0
WHERE [email_id] IS NOT NULL');
    $ids = $res->fetchPairs();

    dibi::query('UPDATE [dokument] SET [zpusob_doruceni_id] = 1 WHERE [id] IN %in',
           $ids);

    $res = dibi::query('SELECT d.[id] FROM [dokument] d        
JOIN [epodatelna] e ON e.[dokument_id] = d.[id] AND e.[epodatelna_typ] = 0
WHERE [isds_id] IS NOT NULL');
    $ids = $res->fetchPairs();

    dibi::query('UPDATE [dokument] SET [zpusob_doruceni_id] = 2 WHERE [id] IN %in',
           $ids);
    
}
