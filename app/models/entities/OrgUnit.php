<?php

namespace Spisovka;

/**
 * Description of OrgUnit
 *
 * @author Pavel Laštovička
 */
class OrgUnit extends CachedDBEntity
{

    const TBL_NAME = 'orgjednotka';

    public function __toString()
    {
        return $this->zkraceny_nazev;
    }

}
