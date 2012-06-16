<?php

class Zprava extends BaseModel 
{

    protected $name = 'zprava';
    
    /**
     * Nacte zpravy pro zobrazeni hlasek
     * 
     * @return array 
     */
    public function hlasky()
    {
        
        $sql = array(
            'distinct'=>null,
            'from' => array($this->name => 'z'),
            'cols' => array('id','zprava','zprava_typ_id','date_created','user_created','zobrazit_od','zobrazit_do','stav'),
            'leftJoin' => array(
                'userosoba' => array(
                    'from' => array($this->tb_osoba_to_user => 'uo'),
                    'on' => array('uo.user_id=z.user_created'),
                ),
                'osoba' => array(
                    'from' => array($this->tb_osoba => 'o'),
                    'on' => array('o.id=uo.osoba_id'),
                    'cols' => array('prijmeni','jmeno')
                ),
                
            ),
            'where' => array('z.stav=1'),
            'order_by' => array('z.zobrazit_od')
        );        
        
        $osoba_id = Environment::getUser()->getIdentity()->identity->id;
        $sql['where'][] = array('z.id NOT IN (SELECT zo.zprava_id FROM '.$this->tb_zprava_osoba.' AS zo WHERE zo.zprava_id=z.id AND zo.osoba_id=%i)',$osoba_id);
        
        $select = $this->fetchAllComplet($sql);
        
        $result = $select->fetchAll();
        $zpravy = array();
        if ( $result ) {
                foreach( $result as $r ) {
                    
                    if ( empty($r->zobrazit_od) ) {
                        $date = strtotime($r->date_created);
                        $datum = date("j.n.Y",$date);
                    } else {
                        $date = strtotime($r->zobrazit_od);
                        $datum = date("j.n.Y",$date);
                    }
                    
                    if ( empty($r->user_created) ) {
                        $od = "Informační zpráva OSS Spisové služby (www.spisovka3.cz)";
                    } else {
                        $od = $r->prijmeni ." ". $r->jmeno;
                    }
                    
                    $zpravy[] = array(
                        'id' => $r->id,
                        'zprava' => $r->zprava,
                        'typ' => $r->zprava_typ_id,
                        'datum' => $datum,
                        'od' => $od
                    );
                    
                }
        }
            
        return $zpravy;
            
    }

    /**
     * Nacte seznam zprav
     * 
     * @return array 
     */
    public function nacti()
    {
        
        $osoba_id = Environment::getUser()->getIdentity()->identity->id;
        
        $sql = array(
            'distinct'=>null,
            'from' => array($this->name => 'z'),
            'cols' => array('id','zprava','zprava_typ_id','date_created','user_created','zobrazit_od','zobrazit_do','stav'),
            'leftJoin' => array(
                'zpravyosoba' => array(
                    'from' => array($this->tb_zprava_osoba => 'zo'),
                    'on' => array('zo.zprava_id=z.id AND zo.osoba_id='.$osoba_id),
                    'cols' => array('stav' => 'precteno')
                ),
                'userosoba' => array(
                    'from' => array($this->tb_osoba_to_user => 'uo'),
                    'on' => array('uo.user_id=z.user_created'),
                ),
                'osoba' => array(
                    'from' => array($this->tb_osoba => 'o'),
                    'on' => array('o.id=uo.osoba_id'),
                    'cols' => array('prijmeni','jmeno')
                ),
                
            ),
            'where' => array('z.stav=1'),
            'order_by' => array('z.zobrazit_od DESC')
        );        
        
        $select = $this->fetchAllComplet($sql);
        return $select;
            
    }
    
    
    public function ulozit($data)
    {
        
        if ( !isset($data['zprava_typ_id']) ) $data['zprava_typ_id'] = 1;
        if ( !isset($data['date_created']) ) $data['date_created'] = new DateTime();
        if ( !isset($data['user_created']) ) {
            $user = Environment::getUser();
            if ( !$user->isAuthenticated() ) {
                $data['user_created'] = $user->id;
            } else {
                $data['user_created'] = null;
            }
        }
        if ( !isset($data['zobrazit_od']) ) $data['zobrazit_od'] = date("Y-m-d H:i:s");
        if ( !isset($data['stav']) ) $data['stav'] = 1;
        
        $data['uid'] = md5($data['zprava'].$data['zobrazit_od']);
        
        //echo "<pre>"; print_r($data); echo "</pre>";
        try {
            return $this->insert($data);
        } catch (Exception $e) {
            return false;
        }
        
    }
    
    public function precteno($zprava_id)
    {
        
        $data = array(
            'zprava_id' => (int)$zprava_id,
            'osoba_id' => Environment::getUser()->getIdentity()->identity->id,
            'date' => new DateTime(),
            'stav' => 1
        );
        
        $ZpravaOsoba = new ZpravaOsoba();
        return $ZpravaOsoba->insert($data);
        
    }
    
    public function skryt($zprava_id)
    {
        
        $data = array(
            'stav' => 2
        );
        
        $ZpravaOsoba = new ZpravaOsoba();
        return $ZpravaOsoba->update($data,array(array('zprava_id=%i',(int)$zprava_id)));
        
    }    
    
    public function smazat($zprava_id)
    {

        try {
            $ZpravaOsoba = new ZpravaOsoba();
            $ZpravaOsoba->delete(array(array('zprava_id=%i',$zprava_id)));
            $this->delete(array(array('id=%i',$zprava_id)));
            return true;
        } catch (Exception $e) {
            return false;
        }
        
    }
    
    
    public static function informace_z_webu()
    {
        $url = "http://www.mojespisovka.cz/rss/novinky";
        
        if (file_exists(CLIENT_DIR .'/temp/'. md5($url)) ) {
            $cachetime = filemtime(CLIENT_DIR .'/temp/'. md5($url));
            if ( date("Ymd") < date("Ymd",$cachetime) ) {
                $source = self::getSource($url);
                self::ulozit_zpravy($source);
                file_put_contents(CLIENT_DIR .'/temp/'. md5($url), $source);
            }
        } else {
            $source = self::getSource($url);
            //echo $source; exit;
            self::ulozit_zpravy($source);
            file_put_contents(CLIENT_DIR .'/temp/'. md5($url), $source);
        }
    }
    
    private static function ulozit_zpravy($source)
    {
        if (!is_null($source) ) {
            $xml = simplexml_load_string($source);
            
            if ( isset($xml->channel->item) ) {
                $Zprava = new Zprava();
                foreach ( $xml->channel->item as $item ) {

                    $title = trim((string)$item->title);
                    $description = trim((string)$item->description);
                    
                    if ( $title == $description ) {
                        $zprava = $title;
                    } else if ( $description != "" ) {
                        $zprava = "<strong>". $title ."</strong><br />";
                        $zprava .= $description;
                    } else {
                        $zprava = $title;
                    }
                    
                    $zprava .= '<br /><a href="'. $item->link .'">Více informací zde.</a>';
                    
                    $zobrazit_od = strtotime($item->pubDate);
                    if ( $zobrazit_od ) {
                        $zobrazit_od = date("Y-m-d H:i:s",$zobrazit_od);
                    } else {
                        $zobrazit_od = date("Y-m-d H:i:s");
                    }
                    
                    $data = array(
                        'zprava' => $zprava,
                        'zobrazit_od' => $zobrazit_od,
                        'zprava_typ_id' => 2, /* z webu */
                    );
                    
                    //echo "<pre>"; print_r($item); echo "</pre>";
                    //echo "<pre>"; print_r($data); echo "</pre>";
                    
                    return $Zprava->ulozit($data);
                }
            } else {
                return false;
            }
        } else {
            return false;
        }        
    }    
    
    public static function aktualni_verze()
    {
        $url = "http://www.mojespisovka.cz/rss/verze";
        
        if (file_exists(CLIENT_DIR .'/temp/'. md5($url)) ) {
            $cachetime = filemtime(CLIENT_DIR .'/temp/'. md5($url));
            if ( date("Ymd") < date("Ymd",$cachetime) ) {
                $source = self::getSource($url);
                self::aktualni_verze_zpracuj($source);
                file_put_contents(CLIENT_DIR .'/temp/'. md5($url), $source);
            }
        } else {
            $source = self::getSource($url);
            self::aktualni_verze_zpracuj($source);
            file_put_contents(CLIENT_DIR .'/temp/'. md5($url), $source);
        }
    }  
 
    private static function aktualni_verze_zpracuj($source)
    {
        if (!is_null($source) ) {
            $xml = simplexml_load_string($source);
            
            if ( isset($xml->channel->item) ) {
                foreach ( $xml->channel->item as $item ) {
                    $title = trim((string)$item->title);
                    file_put_contents(CLIENT_DIR .'/temp/aktualni_verze', $title);
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }        
    }    
    
    
    public static function je_aktualni()
    {
        $app_info = Environment::getVariable('app_info');
        if ( !empty($app_info) ) {
            $app_info = explode("#",$app_info);
        } else {
            $app_info = array('3.3.0','rev.X','OSS Spisová služba v3','1270716764');
        }        
        
        $aktualni = @file_get_contents(CLIENT_DIR .'/temp/aktualni_verze');
        if ( $aktualni ) {
            
            $aktualni = trim($aktualni);
            $akt_part = explode(".",$aktualni);
            $aktualni_cislo = "";
            foreach ( $akt_part as $part ) {
                $aktualni_cislo .= sprintf("%05d",(int)$part);
            }

            $soucasna = trim($app_info[0]);
            $sou_part = explode(".",$soucasna);
            $soucasne_cislo = "";
            foreach ( $sou_part as $part ) {
                $soucasne_cislo .= sprintf("%05d",(int)$part);
            }
            
            if ( $aktualni_cislo > $soucasne_cislo ) {
                return array(
                    "aktualni"=>0,
                    'aktualni_verze' => $aktualni,
                    'soucasna_verze' => $soucasna
                    );
            } else {
                return array(
                    "aktualni"=>1,
                    'aktualni_verze' => $aktualni,
                    'soucasna_verze' => $soucasna
                    );
            }
            
        } else {
            return array("aktualni"=>1);
        }
        
        
    }
    
    private static function getSource($zdroj) {

        $app_info = Environment::getVariable('app_info');
        if ( !empty($app_info) ) {
            $app_info = explode("#",$app_info);
            $user_agent = "OSS Spisova sluzba v". $app_info[0];
        } else {
            $user_agent = "OSS Spisova sluzba v3";
            $app_info = array('3.x','rev.X','OSS Spisová služba v3','1270716764');
        }

        $curli = @curl_version();
        // OSS Spisovka v3.0 (i686-pc-linux-gnu) libcurl 7.7.3 (OpenSSL 0.9.6)
        if(isset($curli['host'])) $user_agent .= " (". $curli['host'] .")";
        if(isset($curli['version'])) $user_agent .= " libcurl ". $curli['version'] ."";
        if(isset($curli['ssl_version'])) $user_agent .= " (". $curli['ssl_version'] .")";
            
        $user_config = Environment::getVariable('user_config');
        $klient_info = $user_config->urad;
            
        $unique_info = Environment::getVariable('unique_info');
        $unique_part = explode('#',$unique_info);
        
        
        $zprava = "id=".$unique_part[0]."\n".
                  "zkratka=". $klient_info->zkratka."\n".
                  "name=".$klient_info->nazev."\n".
                  "ic=".$klient_info->ico."\n".
                  "tel=".$klient_info->kontakt->telefon."\n".
                  "mail=".$klient_info->kontakt->email."\n".
                  "version=".$app_info[0] ." (".$app_info[1].")\n".
                  "klient_ip=".$_SERVER['REMOTE_ADDR'].")\n".
                  "server_ip=".$_SERVER['SERVER_ADDR'].")\n".
                  "server_name=".$_SERVER['SERVER_SOFTWARE'].")\n";
                  
        
        $zdroj = $zdroj ."?id=". base64_encode($zprava);
        
        if (@ini_get("allow_url_fopen")) {
            return @file_get_contents($zdroj);
        } else if ( function_exists('curl_init') ) {
            if ( $ch = curl_init($zdroj) ) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $response = curl_exec($ch);
                curl_close($ch);
                return $response;
            } else {
                return null;
            }
        } else {
            return null;
        }

    }       
    
}
