<?php

class Zpravy extends BaseModel 
{

    protected $name = 'zprava';

    
    /* public static function prectena($zprava_id)
    {
        
        $data = array(
            'zprava_id' => (int)$zprava_id,
            'osoba_id' => Environment::getUser()->getIdentity()->identity->id,
        );
        
        $ZpravaOsoba = new ZpravaOsoba();
        return $ZpravaOsoba->insert($data);        
    } */
    
    private static function zpracuj_zpravy_ze_serveru($source)
    {
        /* $xml = simplexml_load_string($source);
        
        if ( isset($xml->channel->item) )
            foreach ( $xml->channel->item as $item ) {

                $title = trim((string)$item->title);
                $description = trim((string)$item->description);
                
                  ...                    
                $Zprava->ulozit($data);
            }
        */
    }

    public static function dej_pocet_neprectenych_zprav()    
    {
        return 0;
    }
}

