<?php //netteloader=Epodatelna

class Epodatelna extends BaseModel
{

    protected $name = 'epodatelna';
    protected $primary = 'id';
    protected $tb_file = 'file';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_file = $prefix . $this->tb_file;

    }


    /**
     * Seznam dokumentu s zivotnim cyklem
     * 
     * @param <type> $args 
     */
    public function seznam($args = array(), $detail = 0) {

        if ( isset($args['where']) ) {
            $where = $args['where'];
        } else {
            $where = null;
        }
        if ( isset($args['where_or']) ) {
            $where_or = $args['where_or'];
        } else {
            $where_or = null;
        }
        
        if ( isset($args['order']) ) {
            $order = $args['order'];
        } else {
            $order = array('id'=>'DESC');
        } 
        if ( isset($args['limit']) ) {
            $limit = $args['limit'];
        } else {
            $limit = null;
        }
        if ( isset($args['offset']) ) {
            $offset = $args['offset'];
        } else {
            $offset = null;
        }
        
        $sql = array(
        
            'distinct'=>1,
            'from' => array($this->name => 'ep'),
            'where' => $where,
            'where_or' => $where_or,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_file => 'f'),
                    'on' => array('f.id=ep.file_id'),
                    'cols' => array('real_path')
                )
            )
        
        );

        //echo "<pre>";
        //print_r($sql);
        //echo "</pre>";

        $select = $this->fetchAllComplet($sql);
        //$result = $select->fetchAll();

        return $select;

    }

    public function existuje($id_zpravy, $typ = 'ie') {

        if ( $typ == 'isds' ) {
            $args = array(
                'where' => array(
                    array('isds_signature = %s',$id_zpravy)
                )
            );
        } else if ( $typ == 'email' ) {
            $args = array(
                'where' => array(
                    array('email_signature = %s',$id_zpravy)
                )
            );
        } else if ( $typ == 'vse' ) {
            $args = array(
                'where_or' => array(
                    array('isds_signature = %s',$id_zpravy),
                    array('email_signature = %s',$id_zpravy)
                )
            );
        } else {
            return 0;
        }

        $query = $this->fetchAllComplet($args);
        return $query->count();

    }

    public function getInfo($epodatelna_id, $detail = 0) {

        $args = array(
            'where' => array(
                array('id=%i',$epodatelna_id)
            )
        );


        $query = $this->fetchAllComplet($args);
        return $query->fetch();

    }

    public function getMax($smer = 0) {

        $result = $this->fetchAll(array('poradi'=>'DESC'),array('rok'=>date('Y'),array('epodatelna_typ=%i',$smer)),null,null,1);
        $row = $result->fetch();
        return ($row) ? ($row->poradi+1) : 1;

    }

    public function identifikator($zprava, $original = null)
    {
        if ( empty($zprava) && empty($original) ) {
            return null;
        } else if (!empty($zprava->identifikator)) {
            if ( is_array($zprava->identifikator) ) {
                $identifikator = $zprava->identifikator;
            } else {
                $identifikator = unserialize($zprava->identifikator);
            }
        } else if ( isset($zprava['typ']) ) {
            $identifikator = $zprava;
        } else {
            $identifikator = array();
            $identifikator['predmet'] = $zprava->predmet;

            $doruceno = strtotime($zprava->doruceno_dne);
            $prijato = strtotime($zprava->prijato_dne);
            $identifikator['doruceno_dne'] = $doruceno;
            $identifikator['prijato_dne'] = $prijato;

            if ( !empty($zprava->email_signature) ) {

                $identifikator['typ'] = "email";
                $identifikator['odesilatel'] = $zprava->odesilatel;
                $identifikator['email'] = @$original['zprava']->from->email;
                $identifikator['adresat'] = @$original['zprava']->to_address;

                $popis  = "";
                $popis .= "Předmět     : ". $zprava->predmet ."\n";
                $popis .= "Odesílatel  : ". $zprava->odesilatel ."\n";
                $popis .= "Adresát     : ". @$original['zprava']->to_address ."\n";
                $popis .= "\n";
                $popis .= "Datum a čas doručení            : ". date("d.m.Y H:i:s",$doruceno) ."\n";
                $popis .= "Datum a čas přijetí e-podatelnou : ". date("d.m.Y H:i:s",$prijato) ."\n";
                $popis .= "\n";

                if ( (int)$original['signature']['signed'] >= 0 && !empty($original['signature']['signed']) ) {

                    if ( !empty($original['signature']['cert_info']['organizace']) ) {
                        $popis .= "Certifikát  : ". $original['signature']['cert_info']['organizace'] ."\n";
                        if ( !empty($original['signature']['cert_info']['jednotka']) ) {
                            $popis .= "              ". $original['signature']['cert_info']['jednotka'] ."\n";
                        }
                        $popis .= "              ". $original['signature']['cert_info']['jmeno'] ."\n";
                    } else {
                        $popis .= "Certifikát  : ". $original['signature']['cert_info']['jmeno'] ."\n";
                    }

                    if ( !empty($original['signature']['cert_info']['adresa']) ) {
                        $popis .= "              ". $original['signature']['cert_info']['adresa'] ."\n";
                    }
                    if ( !empty($original['signature']['cert_info']['email']) ) {
                        $popis .= "              ". $original['signature']['cert_info']['email'] ."\n";
                    }
                    $popis .= "              platnost: ".
                            date("j.n.Y G:i:s",$original['signature']['cert_info']['platnost_od']) ." - ".
                            date("j.n.Y G:i:s",$original['signature']['cert_info']['platnost_do']) ."\n";
                    $popis .= "              CA: ". $original['signature']['cert_info']['CA'] ."\n";
                    $popis .= "                  ". $original['signature']['cert_info']['CA_org'] ."\n";


                }

                $identifikator['cert_info'] = @$original['signature']['cert_info'];
                $identifikator['cert_status'] = $original['signature']['status'];
                $identifikator['cert_signed'] = $original['signature']['signed'];

                $identifikator['popis'] = $popis;

            } else if ( !empty($zprava->isds_signature) ) {

                if ( !empty($original->dmDm->dmID) ) {

                $identifikator['typ'] = "isds";
                $identifikator['id_datove_zpravy'] = $original->dmDm->dmID;
                $identifikator['odesilatel'] = $original->dmDm->dbIDSender;
                $identifikator['adresat'] = $original->dmDm->dbIDRecipient;

                $popis  = "ID datové zprávy  : ". $original->dmDm->dmID ."\n";
                $popis .= "Předmět    : ". $original->dmDm->dmAnnotation ."\n";
                $popis .= "\n";
                $popis .= "Odesílatel : ". $original->dmDm->dbIDSender ."\n";
                $popis .= "             ". $original->dmDm->dmSender ."\n";
                $popis .= "             ". $original->dmDm->dmSenderAddress ."\n";
                $popis .= "\n";
                $popis .= "Adresát    : ". $original->dmDm->dbIDRecipient ."\n";
                $popis .= "             ". $original->dmDm->dmRecipient ."\n";
                $popis .= "             ". $original->dmDm->dmRecipientAddress ."\n";
                $popis .= "\n";

                $dt_dodani = strtotime($original->dmDeliveryTime);
                $dt_doruceni = strtotime($original->dmAcceptanceTime);
                $popis .= "Datum a čas dodání              : ". date("d.m.Y H:i:s",$dt_dodani) ."\n";
                $popis .= "Datum a čas doručení            : ". date("d.m.Y H:i:s",$dt_doruceni) ."\n";
                $popis .= "Datum a čas přijetí e-podatelnou : ". date("d.m.Y H:i:s",$prijato) ."\n";
                $popis .= "\n";

                $popis .= "Číslo jednací odesílatele   : ". $original->dmDm->dmSenderRefNumber ."\n";
                $popis .= "Spisová značka odesílatele : ". $original->dmDm->dmSenderIdent ."\n";
                $popis .= "Číslo jednací příjemce     : ". $original->dmDm->dmRecipientRefNumber ."\n";
                $popis .= "Spisová značka příjemce    : ". $original->dmDm->dmRecipientIdent ."\n";
                $popis .= "\n";
                $popis .= "Do vlastních rukou? : ". (!empty($original->dmDm->dmPersonalDelivery)?"ano":"ne") ."\n";
                $popis .= "Doručeno fikcí?     : ". (!empty($original->dmDm->dmAllowSubstDelivery)?"ano":"ne") ."\n";
                $popis .= "Zpráva určena pro   : ". $original->dmDm->dmToHands ."\n";
                //$popis .= "\n";
                //$popis .= "Status: ". $original->dmMessageStatus ." - ". ISDS_Spisovka::stavZpravy($original->dmMessageStatus) ."\n";

                $identifikator['popis'] = $popis;
                
                } else if ( !empty($original) ) {

                $identifikator['typ'] = "isds";
                $identifikator['id_datove_zpravy'] = $original->dmID;
                $identifikator['odesilatel'] = $original->dbIDSender;
                $identifikator['adresat'] = $original->dbIDRecipient;

                $popis  = "ID datové zprávy  : ". $original->dmID ."\n";
                $popis .= "Předmět    : ". $original->dmAnnotation ."\n";
                $popis .= "\n";
                $popis .= "Odesílatel : ". $original->dbIDSender ."\n";
                $popis .= "             ". $original->dmSender ."\n";
                $popis .= "             ". $original->dmSenderAddress ."\n";
                $popis .= "\n";
                $popis .= "Adresát    : ". $original->dbIDRecipient ."\n";
                $popis .= "             ". $original->dmRecipient ."\n";
                $popis .= "             ". $original->dmRecipientAddress ."\n";
                $popis .= "\n";

                $dt_dodani = strtotime($original->dmDeliveryTime);
                $dt_doruceni = strtotime($original->dmAcceptanceTime);
                $popis .= "Datum a čas dodání              : ". date("d.m.Y H:i:s",$dt_dodani) ."\n";
                $popis .= "Datum a čas doručení            : ". date("d.m.Y H:i:s",$dt_doruceni) ."\n";
                $popis .= "Datum a čas přijetí e-podatelnou : ". date("d.m.Y H:i:s",$prijato) ."\n";
                $popis .= "\n";

                $popis .= "Číslo jednací odesílatele   : ". $original->dmSenderRefNumber ."\n";
                $popis .= "Spisová značka odesílatele : ". $original->dmSenderIdent ."\n";
                $popis .= "Číslo jednací příjemce     : ". $original->dmRecipientRefNumber ."\n";
                $popis .= "Spisová značka příjemce    : ". $original->dmRecipientIdent ."\n";
                $popis .= "\n";
                $popis .= "Do vlastních rukou? : ". (!empty($original->dmPersonalDelivery)?"ano":"ne") ."\n";
                $popis .= "Doručeno fikcí?     : ". (!empty($original->dmAllowSubstDelivery)?"ano":"ne") ."\n";
                $popis .= "Zpráva určena pro   : ". $original->dmToHands ."\n";
                //$popis .= "\n";
                //$popis .= "Status: ". $original->dmMessageStatus ." - ". ISDS_Spisovka::stavZpravy($original->dmMessageStatus) ."\n";

                $identifikator['popis'] = $popis;
                    
                }

            } else {
                $identifikator['typ'] = "listinná";
            }

            if ( !empty($zprava->id) ) {
                $this->update(
                    array('identifikator'=>serialize($identifikator)),
                    array( array('id=%i',$zprava->id) )
                );
            }

        } // if empty(identifikator)

        // Kontrola certiikatu
        if ( $identifikator['typ'] == "email" ) {

            if ( empty($identifikator['cert_signed']) ) {
                $identifikator['cert_signed'] = -1;
                $identifikator['cert_status'] = "Email není podepsán!";
            } elseif ( (int)$identifikator['cert_signed'] >= 0 ) {
                //$identifikator['cert_info']['platnost_do'] = time() - (86400*2);
                $od = $identifikator['cert_info']['platnost_od'];
                $do = $identifikator['cert_info']['platnost_do'];

                // CRL
                //$identifikator['cert_info']['serial_number'] = 1002891;

                $CRL = new CRLParser();
                $CRL->cache(1, CLIENT_DIR ."/temp/");
                //$CRL->setDateFormat('j.n.Y G:i:s');
                if ( isset($identifikator['cert_info']['CRL']) && count($identifikator['cert_info']['CRL'])>0 ) {
                    foreach ( $identifikator['cert_info']['CRL'] as $crl_url ) {
                        $seznam = $CRL->fromUrl($crl_url);
                        //print_r($seznam);
                        if ( isset($seznam->seznam) ) {
                            if ( isset($seznam->seznam[ $identifikator['cert_info']['serial_number'] ]) ) {
                                $identifikator['cert_signed'] = 2;
                                $identifikator['cert_crl_date'] = $seznam->seznam[$identifikator['cert_info']['serial_number']]->datum;
                                $identifikator['cert_status'] = 'Certifikát byl zneplatněn! Datum zneplatnění: '. date("j.n.Y G:i:s", $identifikator['cert_crl_date']);
                                $do = $identifikator['cert_crl_date'];
                            }
                            break;
                        }
                    }
                }

                $identifikator['cert_log']['aktualne']['date'] = date("d.m.Y H:i:s");
                if ( $od <= time() && time() <= $do ) {
                    // platny
                    $identifikator['cert_log']['aktualne']['message'] = $identifikator['cert_status'];
                    $identifikator['cert_log']['aktualne']['status'] = $identifikator['cert_signed'];
                } else if ( $do != $identifikator['cert_info']['platnost_do'] ) {
                    // zneplatnen
                    $identifikator['cert_log']['aktualne']['message'] = $identifikator['cert_status'];
                    $identifikator['cert_log']['aktualne']['status'] = $identifikator['cert_signed'];
                } else {
                    // neplatny
                    $identifikator['cert_log']['aktualne']['message'] = "Podpis je neplatný! Certifikátu vypršela platnost!";
                    $identifikator['cert_log']['aktualne']['status'] = 0;
                }

                $doruceno = $identifikator['doruceno_dne'];
                $identifikator['cert_log']['doruceno']['date'] = date("d.m.Y H:i:s",$identifikator['doruceno_dne']);
                if ( $od <= $doruceno && $doruceno <= $do ) {
                    $identifikator['cert_log']['doruceno']['message'] = "Podpis byl v době doručení platný";
                    $identifikator['cert_log']['doruceno']['status'] = 1;
                } else {
                    $identifikator['cert_log']['doruceno']['message'] = "Podpis nebyl v době doručení platný!";
                    $identifikator['cert_log']['doruceno']['status'] = 0;
                }

                $prijato = $identifikator['prijato_dne'];
                $identifikator['cert_log']['prijato']['date'] = date("d.m.Y H:i:s",$prijato);
                if ( $od <= $prijato && $prijato <= $do ) {
                    $identifikator['cert_log']['prijato']['message'] = "Podpis byl v době přijetí platný";
                    $identifikator['cert_log']['prijato']['status'] = 1;
                } else {
                    $identifikator['cert_log']['prijato']['message'] = "Podpis nebyl v době přijetí platný!";
                    $identifikator['cert_log']['prijato']['status'] = 0;
                }
            } else if ( (int)$identifikator['cert_signed'] == -1 ) {
                $identifikator['cert_log']['aktualne']['date'] = date("d.m.Y H:i:s");
                $identifikator['cert_log']['aktualne']['message'] = $identifikator['cert_status'];
                $identifikator['cert_log']['aktualne']['status'] = $identifikator['cert_signed'];
            } else {
                $identifikator['cert_log']['aktualne']['date'] = date("d.m.Y H:i:s");
                $identifikator['cert_log']['aktualne']['message'] = $identifikator['cert_status'];
                $identifikator['cert_log']['aktualne']['status'] = $identifikator['cert_signed'];
            }
        }

        return $identifikator;

    }

    public function getLastISDS()
    {
        
        $data = $this->fetchAll(array('doruceno_dne'=>'DESC'), array(
            'epodatelna_typ=0','isds_signature IS NOT NULL'
        ), 0, 1)->fetch();
        if ( $data ) {
            $do = strtotime($data->doruceno_dne);
            if ( $do != 0 ) {
                return $do - 10800; // posledni - 3 dny
            } else {
                return null;
            } 
        } else {
            return null;
        }
        
    }

}
