<?php

function revision_940_after()
{
    $res = dibi::query("SELECT [id] FROM [:PREFIX:dokument_typ] WHERE [nazev] LIKE %s", '%- doručeno%');
    $ids = $res->fetchPairs();

    foreach ($ids as $id)
        dibi::query("DELETE IGNORE FROM [:PREFIX:dokument_typ] WHERE [id] = %i", $id);
}