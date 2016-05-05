<?php

//netteloader=Epodatelna

class Epodatelna extends BaseModel
{
    protected $name = 'epodatelna';
    protected $primary = 'id';

    /**
     * Seznam dokumentu s zivotnim cyklem
     * 
     * @param <type> $args 
     */
    public function seznam($args = array())
    {
        if (isset($args['where'])) {
            $where = $args['where'];
        } else {
            $where = null;
        }
        if (isset($args['where_or'])) {
            $where_or = $args['where_or'];
        } else {
            $where_or = null;
        }

        if (isset($args['order'])) {
            $order = $args['order'];
        } else {
            $order = ['doruceno_dne' => 'DESC'];
        }
        if (isset($args['limit'])) {
            $limit = $args['limit'];
        } else {
            $limit = null;
        }
        if (isset($args['offset'])) {
            $offset = $args['offset'];
        } else {
            $offset = null;
        }

        $sql = array(
            'from' => array($this->name => 'ep'),
            'where' => $where,
            'where_or' => $where_or,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
        );

        //echo "<pre>";
        //print_r($sql);
        //echo "</pre>";

        $select = $this->selectComplex($sql);
        //$result = $select->fetchAll();

        return $select;
    }

    public function existuje($id_zpravy, $typ = 'ie')
    {

        if ($typ == 'isds') {
            $args = array(
                'where' => array(
                    array('isds_id = %s', $id_zpravy)
                )
            );
        } else if ($typ == 'email') {
            $args = array(
                'where' => array(
                    array('email_id = %s', $id_zpravy)
                )
            );
        } else if ($typ == 'vse') {
            $args = array(
                'where_or' => array(
                    array('isds_id = %s', $id_zpravy),
                    array('email_id = %s', $id_zpravy)
                )
            );
        } else {
            return 0;
        }

        $query = $this->selectComplex($args);
        return $query->count();
    }

    /**
     * @param int $smer  0 - prichozi, 1 - odchozi
     * @return int 
     */
    public function getMax($smer = 0)
    {
        $result = $this->select(array('rok' => date('Y'), array('odchozi = %i', $smer)),
                array('poradi' => 'DESC'), null, 1);
        $row = $result->fetch();
        return $row ? $row->poradi + 1 : 1;
    }

    public function identifikator($zprava, $param = null)
    {
        if (empty($zprava) && empty($param)) {
            return null;
        } else if (!empty($zprava->identifikator)
                // Bugfix: Pokud identifikátor obsahuje tento řetězec, je nutné jej sestavit znovu
                && strpos($zprava->identifikator, 'ale má rozdilné emailové adresy!') === false) {
            if (is_array($zprava->identifikator)) {
                $identifikator = $zprava->identifikator;
            } else {
                $identifikator = unserialize($zprava->identifikator);
            }
        } else if (isset($zprava['typ'])) {
            // Zavoláno při zobrazení detailu dokumentu
            $identifikator = $zprava;
        } else {
            $identifikator = array();
            $identifikator['predmet'] = $zprava->predmet;

            $doruceno = strtotime($zprava->doruceno_dne);
            $prijato = strtotime($zprava->prijato_dne);
            $identifikator['doruceno_dne'] = $doruceno;
            $identifikator['prijato_dne'] = $prijato;

            if ($zprava->typ == 'E') {

                $identifikator['typ'] = "email";
                $identifikator['odesilatel'] = $zprava->odesilatel;
                $identifikator['email'] = @$param['zprava']->from->email;
                $identifikator['adresat'] = @$param['zprava']->to_address;

                $popis = "";
                $popis .= "Předmět     : " . $zprava->predmet . "\n";
                $popis .= "Odesílatel  : " . $zprava->odesilatel . "\n";
                $popis .= "Adresát     : " . @$param['zprava']->to_address . "\n";
                $popis .= "\n";
                $popis .= "Datum a čas doručení            : " . date("d.m.Y H:i:s", $doruceno) . "\n";
                $popis .= "Datum a čas přijetí e-podatelnou : " . date("d.m.Y H:i:s", $prijato) . "\n";
                $popis .= "\n";

                if ((int) $param['signature']['signed'] >= 0 && !empty($param['signature']['signed'])) {

                    if (!empty($param['signature']['cert_info']['organizace'])) {
                        $popis .= "Certifikát  : " . $param['signature']['cert_info']['organizace'] . "\n";
                        if (!empty($param['signature']['cert_info']['jednotka'])) {
                            $popis .= "              " . $param['signature']['cert_info']['jednotka'] . "\n";
                        }
                        $popis .= "              " . $param['signature']['cert_info']['jmeno'] . "\n";
                    } else {
                        $popis .= "Certifikát  : " . $param['signature']['cert_info']['jmeno'] . "\n";
                    }

                    if (!empty($param['signature']['cert_info']['adresa'])) {
                        $popis .= "              " . $param['signature']['cert_info']['adresa'] . "\n";
                    }
                    if (!empty($param['signature']['cert_info']['email'])) {
                        $popis .= "              " . $param['signature']['cert_info']['email'] . "\n";
                    }
                    $popis .= "              platnost: " .
                            date("j.n.Y G:i:s",
                                    $param['signature']['cert_info']['platnost_od']) . " - " .
                            date("j.n.Y G:i:s",
                                    $param['signature']['cert_info']['platnost_do']) . "\n";
                    $popis .= "              CA: " . $param['signature']['cert_info']['CA'] . "\n";
                    $popis .= "                  " . $param['signature']['cert_info']['CA_org'] . "\n";
                }

                $identifikator['cert_info'] = @$param['signature']['cert_info'];
                $identifikator['cert_status'] = $param['signature']['status'];
                $identifikator['cert_signed'] = $param['signature']['signed'];

                $identifikator['popis'] = $popis;
            } else if ($zprava->typ == 'I') {

                if (!empty($param->dmDm->dmID)) {

                    $identifikator['typ'] = "isds";
                    $identifikator['id_datove_zpravy'] = $param->dmDm->dmID;
                    $identifikator['odesilatel'] = $param->dmDm->dbIDSender;
                    $identifikator['adresat'] = $param->dmDm->dbIDRecipient;

                    $popis = "ID datové zprávy  : " . $param->dmDm->dmID . "\n";
                    $popis .= "Předmět    : " . $param->dmDm->dmAnnotation . "\n";
                    $popis .= "\n";
                    $popis .= "Odesílatel : " . $param->dmDm->dbIDSender . "\n";
                    $popis .= "             " . $param->dmDm->dmSender . "\n";
                    $popis .= "             " . $param->dmDm->dmSenderAddress . "\n";
                    $popis .= "\n";
                    $popis .= "Adresát    : " . $param->dmDm->dbIDRecipient . "\n";
                    $popis .= "             " . $param->dmDm->dmRecipient . "\n";
                    $popis .= "             " . $param->dmDm->dmRecipientAddress . "\n";
                    $popis .= "\n";

                    $dt_dodani = strtotime($param->dmDeliveryTime);
                    $dt_doruceni = strtotime($param->dmAcceptanceTime);
                    $popis .= "Datum a čas dodání              : " . date("d.m.Y H:i:s",
                                    $dt_dodani) . "\n";
                    $popis .= "Datum a čas doručení            : " . date("d.m.Y H:i:s",
                                    $dt_doruceni) . "\n";
                    $popis .= "Datum a čas přijetí e-podatelnou : " . date("d.m.Y H:i:s",
                                    $prijato) . "\n";
                    $popis .= "\n";

                    $popis .= "Číslo jednací odesílatele   : " . $param->dmDm->dmSenderRefNumber . "\n";
                    $popis .= "Spisová značka odesílatele : " . $param->dmDm->dmSenderIdent . "\n";
                    $popis .= "Číslo jednací příjemce     : " . $param->dmDm->dmRecipientRefNumber . "\n";
                    $popis .= "Spisová značka příjemce    : " . $param->dmDm->dmRecipientIdent . "\n";
                    $popis .= "\n";
                    $popis .= "Do vlastních rukou? : " . (!empty($param->dmDm->dmPersonalDelivery)
                                        ? "ano" : "ne") . "\n";
                    $popis .= "Doručeno fikcí?     : " . (!empty($param->dmDm->dmAllowSubstDelivery)
                                        ? "ano" : "ne") . "\n";
                    $popis .= "Zpráva určena pro   : " . $param->dmDm->dmToHands . "\n";
                    //$popis .= "\n";
                    //$popis .= "Status: ". $original->dmMessageStatus ." - ". ISDS_Spisovka::stavZpravy($original->dmMessageStatus) ."\n";

                    $identifikator['popis'] = $popis;
                } else if (!empty($param)) {

                    $identifikator['typ'] = "isds";
                    $identifikator['id_datove_zpravy'] = $param->dmID;
                    $identifikator['odesilatel'] = $param->dbIDSender;
                    $identifikator['adresat'] = $param->dbIDRecipient;

                    $popis = "ID datové zprávy  : " . $param->dmID . "\n";
                    $popis .= "Předmět    : " . $param->dmAnnotation . "\n";
                    $popis .= "\n";
                    $popis .= "Odesílatel : " . $param->dbIDSender . "\n";
                    $popis .= "             " . $param->dmSender . "\n";
                    $popis .= "             " . $param->dmSenderAddress . "\n";
                    $popis .= "\n";
                    $popis .= "Adresát    : " . $param->dbIDRecipient . "\n";
                    $popis .= "             " . $param->dmRecipient . "\n";
                    $popis .= "             " . $param->dmRecipientAddress . "\n";
                    $popis .= "\n";

                    $dt_dodani = strtotime($param->dmDeliveryTime);
                    $dt_doruceni = strtotime($param->dmAcceptanceTime);
                    $popis .= "Datum a čas dodání              : " . date("d.m.Y H:i:s",
                                    $dt_dodani) . "\n";
                    $popis .= "Datum a čas doručení            : " . date("d.m.Y H:i:s",
                                    $dt_doruceni) . "\n";
                    $popis .= "Datum a čas přijetí e-podatelnou : " . date("d.m.Y H:i:s",
                                    $prijato) . "\n";
                    $popis .= "\n";

                    $popis .= "Číslo jednací odesílatele   : " . $param->dmSenderRefNumber . "\n";
                    $popis .= "Spisová značka odesílatele : " . $param->dmSenderIdent . "\n";
                    $popis .= "Číslo jednací příjemce     : " . $param->dmRecipientRefNumber . "\n";
                    $popis .= "Spisová značka příjemce    : " . $param->dmRecipientIdent . "\n";
                    $popis .= "\n";
                    $popis .= "Do vlastních rukou? : " . (!empty($param->dmPersonalDelivery)
                                        ? "ano" : "ne") . "\n";
                    $popis .= "Doručeno fikcí?     : " . (!empty($param->dmAllowSubstDelivery)
                                        ? "ano" : "ne") . "\n";
                    $popis .= "Zpráva určena pro   : " . $param->dmToHands . "\n";
                    //$popis .= "\n";
                    //$popis .= "Status: ". $original->dmMessageStatus ." - ". ISDS_Spisovka::stavZpravy($original->dmMessageStatus) ."\n";

                    $identifikator['popis'] = $popis;
                }
            } else {
                $identifikator['typ'] = "listinná";
            }

            if (!empty($zprava->id)) {
                $this->update(
                        array('identifikator' => serialize($identifikator)),
                        array(array('id=%i', $zprava->id))
                );
            }
        } // if empty(identifikator)
        // Kontrola certiikatu
        if ($identifikator['typ'] == "email") {

            if (empty($identifikator['cert_signed'])) {
                $identifikator['cert_signed'] = -1;
                $identifikator['cert_status'] = "Email není podepsán.";
            } elseif ((int) $identifikator['cert_signed'] > 0) {
                //$identifikator['cert_info']['platnost_do'] = time() - (86400*2);
                $od = $identifikator['cert_info']['platnost_od'];
                $do = $identifikator['cert_info']['platnost_do'];

                // CRL
                //$identifikator['cert_info']['serial_number'] = 1002891;

                $CRL = new CRLParser();
                $CRL->enableCache(TEMP_DIR);
                //$CRL->setDateFormat('j.n.Y G:i:s');
                if (isset($identifikator['cert_info']['CRL']) && count($identifikator['cert_info']['CRL']) > 0) {
                    foreach ($identifikator['cert_info']['CRL'] as $crl_url) {
                        $seznam = $CRL->fromUrl($crl_url);
                        //print_r($seznam);
                        if (isset($seznam->seznam)) {
                            if (isset($seznam->seznam[$identifikator['cert_info']['serial_number']])) {
                                $identifikator['cert_signed'] = 2;
                                $identifikator['cert_crl_date'] = $seznam->seznam[$identifikator['cert_info']['serial_number']]->datum;
                                $identifikator['cert_status'] = 'Certifikát byl zneplatněn! Datum zneplatnění: ' . date("j.n.Y G:i:s",
                                                $identifikator['cert_crl_date']);
                                $do = $identifikator['cert_crl_date'];
                            }
                            break;
                        }
                    }
                }

                $identifikator['cert_log']['aktualne']['date'] = date("d.m.Y H:i:s");
                if ($od <= time() && time() <= $do) {
                    // platny
                    $identifikator['cert_log']['aktualne']['message'] = $identifikator['cert_status'];
                    $identifikator['cert_log']['aktualne']['status'] = $identifikator['cert_signed'];
                } else if ($do != $identifikator['cert_info']['platnost_do']) {
                    // zneplatnen
                    $identifikator['cert_log']['aktualne']['message'] = $identifikator['cert_status'];
                    $identifikator['cert_log']['aktualne']['status'] = $identifikator['cert_signed'];
                } else {
                    // neplatny
                    $identifikator['cert_log']['aktualne']['message'] = "Podpis je neplatný! Certifikátu vypršela platnost!";
                    $identifikator['cert_log']['aktualne']['status'] = 0;
                }

                $doruceno = $identifikator['doruceno_dne'];
                $identifikator['cert_log']['doruceno']['date'] = date("d.m.Y H:i:s",
                        $identifikator['doruceno_dne']);
                if ($od <= $doruceno && $doruceno <= $do) {
                    $identifikator['cert_log']['doruceno']['message'] = "Podpis byl v době doručení platný";
                    $identifikator['cert_log']['doruceno']['status'] = 1;
                } else {
                    $identifikator['cert_log']['doruceno']['message'] = "Podpis nebyl v době doručení platný!";
                    $identifikator['cert_log']['doruceno']['status'] = 0;
                }

                $prijato = $identifikator['prijato_dne'];
                $identifikator['cert_log']['prijato']['date'] = date("d.m.Y H:i:s", $prijato);
                if ($od <= $prijato && $prijato <= $do) {
                    $identifikator['cert_log']['prijato']['message'] = "Podpis byl v době přijetí platný";
                    $identifikator['cert_log']['prijato']['status'] = 1;
                } else {
                    $identifikator['cert_log']['prijato']['message'] = "Podpis nebyl v době přijetí platný!";
                    $identifikator['cert_log']['prijato']['status'] = 0;
                }
            } else if ((int) $identifikator['cert_signed'] == -1) {
                $identifikator['cert_log']['aktualne']['date'] = date("d.m.Y H:i:s");
                $identifikator['cert_log']['aktualne']['message'] = $identifikator['cert_status'];
                $identifikator['cert_log']['aktualne']['status'] = $identifikator['cert_signed'];

                // [P.L.] Oprava problemu z minulosti, kdy aplikace hlasila, ze maily bez podpisu
                // mely poskozeny podpis. Zobraz aktualni stav overereni, ne text z databaze
                $identifikator['cert_status'] = $param['signature']['status'];
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
        $data = $this->select(array('odchozi = 0 AND typ = \'I\''),
                        array('doruceno_dne' => 'DESC'), 0, 1)->fetch();
        if ($data) {
            $do = strtotime($data->doruceno_dne);
            if ($do != 0) {
                return $do - 10800; // posledni - 3 dny
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

}
