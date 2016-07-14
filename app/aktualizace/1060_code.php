<?php

function revision_1060_after()
{
    $m = new Spis();    
    $m->rebuildIndex();    
}
