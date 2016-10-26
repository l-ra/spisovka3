<?php

function revision_1420_before()
{
    $duplicates = dibi::query("SELECT * FROM [dokument_to_subjekt] GROUP BY [dokument_id], [subjekt_id] HAVING COUNT(*) > 1")->fetchAll();
    if (empty($duplicates)) {
        echo "Žádné chyby nenalezeny.";
        return;
    }

    echo "Nalezeny duplicity v přiřazení subjektů.\n" .
    "Vypisuji ID dotčených dokumentů:\n";
    
    foreach ($duplicates as $row) {
        echo $row->dokument_id . "\n";
        
        dibi::query("DELETE FROM [dokument_to_subjekt] WHERE [dokument_id] = %i AND [subjekt_id] = %i",
                $row->dokument_id, $row->subjekt_id);
        unset($row->id);
        dibi::insert("dokument_to_subjekt", $row)->execute();
    }
    
    echo "Chyby byly opraveny.";
}
