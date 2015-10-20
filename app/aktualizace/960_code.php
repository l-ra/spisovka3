<?php

function revision_960_after()
{
    $res = dibi::query("SELECT * FROM [:PREFIX:osoba] WHERE [email] != ''");
    $osoby = $res->fetchAll();
    
    $all_ok = true;
    foreach ($osoby as $osoba) {
        if (!Nette\Utils\Validators::isEmail($osoba->email)) {
            $all_ok = false;
            echo "Chyba: $osoba->jmeno $osoba->prijmeni - $osoba->email\n";
        }
    }
    
    if ($all_ok)
        echo "Všechny e-mailové adresy zaměstnanců jsou v pořádku.";
    else
        echo "\nProsíme opravte tyto chyby v e-mailových adresách zaměstnanců.";
    
}
