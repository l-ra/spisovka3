<?php

function revision_1500_after()
{
    $m = new SpisovyZnak();    
    $m->rebuildIndex();    
}
