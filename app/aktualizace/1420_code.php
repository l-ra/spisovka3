<?php

function revision_1420_before()
{
    $duplicates = dibi::query("SELECT [dokument_id], [subjekt_id] FROM [dokument_to_subjekt] GROUP BY [dokument_id], [subjekt_id] HAVING COUNT(*) > 1")->fetchAll();
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
        
        $new_record = ['dokument_id' => $row->dokument_id,
            'subjekt_id' => $row->subjekt_id,
            'user_id' => 1,
            'typ' => 'AO',
            'date_added' => new \DateTime
        ];
        dibi::insert("dokument_to_subjekt", $new_record)->execute();
    }

    echo "Chyby byly opraveny.";
}
