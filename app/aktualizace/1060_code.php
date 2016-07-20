<?php

function revision_1060_after()
{
    $m = new SpisModel();    
    $m->rebuildIndex();    
}
