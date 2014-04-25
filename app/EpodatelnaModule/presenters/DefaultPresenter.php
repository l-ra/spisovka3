<?php //netteloader=Epodatelna_DefaultPresenter

class Epodatelna_DefaultPresenter extends BasePresenter
{

    private $Epodatelna;
    private $pdf_output = 0;

    public function actionDefault()
    {
        $this->redirect('nove');
    }

    protected function shutdown($response) {
        
        if ($this->pdf_output == 1 || $this->pdf_output == 2) {

            function handlePDFError($errno, $errstr, $errfile, $errline, array $errcontext)
            {
                if (0 === error_reporting()) {
                    return;
                }
                //if ( $errno == 8 ) {
                if ( strpos($errstr,'Undefined') === false ) {    
                    throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
                }
                
                
            }
            set_error_handler('handlePDFError');
            
            try {                
        
            ob_start();
            $response->send();
            $content = ob_get_clean();
            if ($content) {
        
                @ini_set("memory_limit",PDF_MEMORY_LIMIT);
                
                if ($this->pdf_output == 2) {
                    $content = str_replace("<td", "<td valign='top'", $content);
                    $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                    $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                    $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s','', $content);
                    $content = preg_replace('#<table id="table_top">.*?</table>#s','', $content);
                
                    $mpdf = new mPDF('iso-8859-2', 'A4',9,'Helvetica');
                
                    $app_info = Environment::getVariable('app_info');
                    $app_info = explode("#",$app_info);
                    $app_name = (isset($app_info[2]))?$app_info[2]:'OSS Spisová služba v3';
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor(Environment::getUser()->getIdentity()->name);
                    $mpdf->SetTitle('Spisová služba - Epodatelna - Detail zprávy');                
                
                    $mpdf->defaultheaderfontsize = 10;	/* in pts */
                    $mpdf->defaultheaderfontstyle = 'B';	/* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9;	/* in pts */
                    $mpdf->defaultfooterfontstyle = '';	/* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->SetHeader('||'.$this->template->Urad->nazev);
                    $mpdf->SetFooter("{DATE j.n.Y}/".Environment::getUser()->getIdentity()->name."||{PAGENO}/{nb}");	/* defines footer for Odd and Even Pages - placed at Outer margin */
                
                    $mpdf->WriteHTML($content);
                
                    $mpdf->Output('dokument.pdf', 'I');                    
                } else {
                    $content = str_replace("<td", "<td valign='top'", $content);
                    $content = str_replace("Vytištěno dne:", "Vygenerováno dne:", $content);
                    $content = str_replace("Vytiskl: ", "Vygeneroval: ", $content);
                    $content = preg_replace('#<div id="tisk_podpis">.*?</div>#s','', $content);
                    $content = preg_replace('#<table id="table_top">.*?</table>#s','', $content);
                
                    $mpdf = new mPDF('iso-8859-2', 'A4-L',9,'Helvetica');
                
                    $app_info = Environment::getVariable('app_info');
                    $app_info = explode("#",$app_info);
                    $app_name = (isset($app_info[2]))?$app_info[2]:'OSS Spisová služba v3';
                    $mpdf->SetCreator($app_name);
                    $mpdf->SetAuthor(Environment::getUser()->getIdentity()->name);
                    $mpdf->SetTitle('Spisová služba - Tisk');                
                
                    $mpdf->defaultheaderfontsize = 10;	/* in pts */
                    $mpdf->defaultheaderfontstyle = 'B';	/* blank, B, I, or BI */
                    $mpdf->defaultheaderline = 1; 	/* 1 to include line below header/above footer */
                    $mpdf->defaultfooterfontsize = 9;	/* in pts */
                    $mpdf->defaultfooterfontstyle = '';	/* blank, B, I, or BI */
                    $mpdf->defaultfooterline = 1; 	/* 1 to include line below header/above footer */
                    
                    if ( $this->getParam('typ') == 'odchozi' ) {
                        $mpdf->SetHeader('Seznam odchozích zpráv||'.$this->template->Urad->nazev);
                    } else {
                        $mpdf->SetHeader('Seznam příchozích zpráv||'.$this->template->Urad->nazev);
                    }
                    $mpdf->SetFooter("{DATE j.n.Y}/".Environment::getUser()->getIdentity()->name."||{PAGENO}/{nb}");	/* defines footer for Odd and Even Pages - placed at Outer margin */
                
                    $mpdf->WriteHTML($content);
                
                    $mpdf->Output('spisova_sluzba.pdf', 'I');
                }
            }
            
            } catch (Exception $e) {
                $location = str_replace("pdfprint=1","",Environment::getHttpRequest()->getUri());

                echo "<h1>Nelze vygenerovat PDF výstup.</h1>";
                echo "<p>Generovaný obsah obsahuje příliš mnoho dat, které není možné zpracovat.</p>";
                echo "<p><a href=".$location.">Přejít na předchozí stránku.</a></p>";
                echo "<p>".$e->getMessage()."</p>";
                exit;
            }
            
        }
        
    }    
    
    
    public function renderNove()
    {

        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();

        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;


        $args = array(
            'where' => array('(ep.stav=1) AND (ep.epodatelna_typ=0)')
        );
        $result = $this->Epodatelna->seznam($args);
        $paginator->itemCount = count($result);
        
        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParam('print');
        $pdf = $this->getParam('pdfprint');
        if ( $tisk ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setLayout(false);
            $this->setView('print');
        } elseif ( $pdf ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $this->pdf_output = 1;
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setLayout(false);
            $this->setView('print');
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $this->setView('seznam');
        }          

        $this->template->seznam = $seznam;
        
    }

    public function renderPrichozi()
    {

        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();

        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;


        $args = null;
        $args = array(
            'where' => array('(ep.stav>=1) AND (ep.epodatelna_typ=0)')
        );
        $result = $this->Epodatelna->seznam($args);
        $paginator->itemCount = count($result);
        
        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParam('print');
        $pdf = $this->getParam('pdfprint');
        if ( $tisk ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setLayout(false);
            $this->setView('print');
        } elseif ( $pdf ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $this->pdf_output = 1;
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setLayout(false);
            $this->setView('print');
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $this->setView('seznam');
        }        

        $this->template->seznam = $seznam;

    }

    public function renderOdchozi()
    {

        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();

        $user_config = Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;


        $args = null;
        $args = array(
            'where' => array('ep.epodatelna_typ=1')
        );
        $result = $this->Epodatelna->seznam($args);
        $paginator->itemCount = count($result);
        
        // Volba vystupu - web/tisk/pdf
        $tisk = $this->getParam('print');
        $pdf = $this->getParam('pdfprint');
        if ( $tisk ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setLayout(false);
            $this->setView('printo');
        } elseif ( $pdf ) {
            @ini_set("memory_limit",PDF_MEMORY_LIMIT);
            $this->pdf_output = 1;
            //$seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
            $seznam = $result->fetchAll();
            $this->setLayout(false);
            $this->setView('printo');
        } else {
            $seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
        }           

        $this->template->seznam = $seznam;
        //$this->setView('seznam');

    }

    public function actionDetail()
    {

        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();

        $epodatelna_id = $this->getParam('id',null);
        $zprava = $this->Epodatelna->getInfo($epodatelna_id);

        if ( $zprava ) {

            $this->template->Zprava = $zprava;

            if ($prilohy = unserialize($zprava->prilohy) ) {
                $this->template->Prilohy = $prilohy;
            } else {
                $this->template->Prilohy = null;
            }

            $original = null;
            if ( !empty($zprava->email_signature) ) {
                // Nacteni originalu emailu
                if ( !empty( $zprava->file_id ) ) {
                    $original = $this->nactiEmail($zprava->file_id);

                    if ( $original['signature']['signed'] == 3 ) {

                        $od = $original['signature']['cert_info']['platnost_od'];
                        $do = $original['signature']['cert_info']['platnost_do'];

                        $original['signature']['log']['aktualne']['date'] = date("d.m.Y H:i:s");
                        $original['signature']['log']['aktualne']['message'] = $original['signature']['status'];
                        $original['signature']['log']['aktualne']['status'] = 0;


                        $doruceno = strtotime($zprava->doruceno_dne);
                        $original['signature']['log']['doruceno']['date'] = date("d.m.Y H:i:s",$doruceno);
                        if ( $od <= $doruceno && $doruceno <= $do ) {
                            $original['signature']['log']['doruceno']['message'] = "Podpis byl v době doručení platný";
                            $original['signature']['log']['doruceno']['status'] = 1;
                        } else {
                            $original['signature']['log']['doruceno']['message'] = "Podpis nebyl v době doručení platný!";
                            $original['signature']['log']['doruceno']['status'] = 0;
                        }

                        $prijato = strtotime($zprava->prijato_dne);
                        $original['signature']['log']['prijato']['date'] = date("d.m.Y H:i:s",$prijato);
                        if ( $od <= $prijato && $prijato <= $do ) {
                            $original['signature']['log']['prijato']['message'] = "Podpis byl v době přijetí platný";
                            $original['signature']['log']['prijato']['status'] = 1;
                        } else {
                            $original['signature']['log']['prijato']['message'] = "Podpis nebyl v době přijetí platný!";
                            $original['signature']['log']['prijato']['status'] = 0;
                        }

                    }


                }
            } else if ( !empty($zprava->isds_signature) ) {
                // Nacteni originalu DS
                if ( !empty( $zprava->file_id ) ) {
                    $source = $this->nactiISDS($zprava->file_id);
                    if ( $source ) {
                        $original = unserialize($source);
                    } else {
                        $original = null;
                    }
                    if ( empty($original->dmAcceptanceTime) ) {
                        $this->zkontrolujOdchoziISDS($zprava);
                    }
                }
            } else {
                // zrejme odchozi zprava ven
            }

            if ( !empty($zprava->dokument_id) ) {
                $Dokument = new Dokument();
                $this->template->Dokument = $Dokument->getInfo($zprava->dokument_id);
            } else {
                $this->template->Dokument = null;
            }
            
            
            // Volba vystupu - web/tisk/pdf
            $tisk = $this->getParam('print');
            $pdf = $this->getParam('pdfprint');
            if ( $tisk ) {
                @ini_set("memory_limit",PDF_MEMORY_LIMIT);
                $this->setLayout(false);
                $this->setView('printdetail');
            } elseif ( $pdf ) {
                @ini_set("memory_limit",PDF_MEMORY_LIMIT);
                $this->pdf_output = 2;
                $this->setLayout(false);
                $this->setView('printdetail');
            }              
            
            
            
            $this->template->Original = $original;
            $this->template->Identifikator = $this->Epodatelna->identifikator($zprava, $original);

        } else {
            $this->flashMessage('Požadovaná zpráva neexistuje!','warning');
            $this->redirect('nove');
        }

    }

    public function actionOdetail()
    {
        $this->actionDetail();
        
            // Volba vystupu - web/tisk/pdf
            $tisk = $this->getParam('print');
            $pdf = $this->getParam('pdfprint');
            if ( $tisk ) {
                @ini_set("memory_limit",PDF_MEMORY_LIMIT);
                $this->setLayout(false);
                $this->setView('printdetailo');
            } elseif ( $pdf ) {
                @ini_set("memory_limit",PDF_MEMORY_LIMIT);
                $this->pdf_output = 2;
                $this->setLayout(false);
                $this->setView('printdetailo');
            }          
        
    }

    public function renderZkontrolovat()
    {
        new SeznamStatu($this, 'seznamstatu');
    }

    // Stáhne zprávy ze všech schránek a dá uživateli vědět výsledek
    
    public function actionZkontrolovatAjax()
    {
        @set_time_limit(120); // z moznych dusledku vetsich poctu polozek je nastaven timeout

        /* $id = $this->getParam('id',null);
        $typ = substr($id,0,1);
        $index = substr($id,1); */

        $config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $config_data = $config->toArray();
        $result = array();

        $nalezena_aktivni_schranka = 0;

        // kontrola ISDS
        $zkontroluj_isds = 1;
        if ( count( $config_data['isds'] )>0 && $zkontroluj_isds==1 ) {
            foreach ($config_data['isds'] as $index => $isds_config) {
                if ( $isds_config['aktivni'] != 1 )
                    continue;
                if ( $isds_config['podatelna'] 
                     && !Orgjednotka::isInOrg($isds_config['podatelna']) )
                    continue;
                
                $nalezena_aktivni_schranka = 1;
                $result = $this->zkontrolujISDS($isds_config);
                if ( count($result)>0 ) {
                    echo 'Z ISDS schránky "'.$isds_config['ucet'].'" bylo přijato '.(count($result)).' nových zpráv.<br />';
                } else {
                    echo 'Z ISDS schránky "'.$isds_config['ucet'].'" nebyly zjištěny žádné nové zprávy.<br />';
                }
            }
        }
        // kontrola emailu
        $zkontroluj_email = 1;
        if ( count( $config_data['email'] )>0 && $zkontroluj_email==1 ) {
            foreach ($config_data['email'] as $index => $email_config) {
                if ( $email_config['aktivni'] != 1 )
                    continue;
                if ( $email_config['podatelna'] 
                     && !Orgjednotka::isInOrg($email_config['podatelna']) )
                    continue;

                $nalezena_aktivni_schranka = 1;
                $result = $this->zkontrolujEmail($email_config);
                if (is_string($result))
                    echo $result . '<br />';
                else if ( $result > 0 ) {
                    echo "Z emailové schránky \"".$email_config['ucet']."\" bylo přijato $result nových zpráv.<br />";
                } else {
                    echo 'Z emailové schránky "'.$email_config['ucet'].'" nebyly zjištěny žádné nové zprávy.<br />';
                }
            }
        }

        if ( ! $nalezena_aktivni_schranka)
            echo 'Žádná schránka není definována nebo nastavena jako aktivní.<br />';
        
        exit;
    }

    public function actionZkontrolovatOdchoziISDS()
    {
        // @set_time_limit(600);   
        $this->zkontrolujOdchoziISDS();
        exit;
    }
    
    public function actionNactiNoveAjax()
    {
        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();

        //$user_config = Environment::getVariable('user_config');
        //$vp = new VisualPaginator($this, 'vp');
        //$paginator = $vp->getPaginator();
        //$paginator->itemsPerPage = 2;// isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;

        $args = array(
            'where' => array('(ep.stav=0 OR ep.stav=1) AND (ep.epodatelna_typ=0)')
        );
        $result = $this->Epodatelna->seznam($args);
        //$paginator->itemCount = count($result);
        $seznam = $result->fetchAll();//$paginator->offset, $paginator->itemsPerPage);

        if ( $seznam ) {
            $zpravy = array();
            foreach ( $seznam as $zprava ) {

                $zpravy[ $zprava->id ] = $zprava;
                $prilohy = unserialize($zprava->prilohy);
                if ( $prilohy ) {
                    $zpravy[ $zprava->id ]->prilohy = $prilohy;
                    $prilohy = null;
                } else if ( $zprava->prilohy == 'a:0:{}' ) {
                    $zpravy[ $zprava->id ]->prilohy = array();
                    $prilohy = null;
                }
                $identifikator = unserialize($zprava->identifikator);
                if ( $identifikator ) {
                    $zpravy[ $zprava->id ]->identifikator = $identifikator;
                    $identifikator = null;
                }
                $doruceno_dne = strtotime($zprava->doruceno_dne);
                $zpravy[ $zprava->id ]->doruceno_dne_datum = date("j.n.Y", $doruceno_dne);
                $zpravy[ $zprava->id ]->doruceno_dne_cas = date("G:i:s", $doruceno_dne);
                $zpravy[ $zprava->id ]->odesilatel = htmlspecialchars($zprava->odesilatel);

                $subjekt = new stdClass();
                $original = null;
                if ( !empty($zprava->email_signature) ) {
                    // Nacteni originalu emailu
                    if ( !empty( $zprava->file_id ) ) {
                        $DefaultPresenter = new Epodatelna_DefaultPresenter();
                        $original = $DefaultPresenter->nactiEmail($zprava->file_id);
                    }

                    $subjekt->nazev_subjektu = $zprava->odesilatel;
                    $subjekt->prijmeni = @$original['zprava']->from->personal;
                    $subjekt->email = @$original['zprava']->from->email;

                    if ( $original['signature']['signed'] >= 0 ) {

                        $subjekt->nazev_subjektu = $original['signature']['cert_info']['organizace'];
                        $subjekt->prijmeni = $original['signature']['cert_info']['jmeno'];
                        if ( !empty($original['signature']['cert_info']['email']) && $subjekt->email != $original['signature']['cert_info']['email'] ) {
                            $subjekt->email = $subjekt->email ."; ". $original['signature']['cert_info']['email'];
                        }
                        $subjekt->adresa_ulice = $original['signature']['cert_info']['adresa'];
                    }

                    $SubjektModel = new Subjekt();
                    $subjekt_databaze = $SubjektModel->hledat($subjekt,'email');
                    $zpravy[ $zprava->id ]->subjekt = array('original'=>$subjekt,'databaze'=>$subjekt_databaze);

                } else if ( !empty($zprava->isds_signature) ) {
                    // Nacteni originalu DS
                    if ( !empty( $zprava->file_id ) ) {
                        $DefaultPresenter = new Epodatelna_DefaultPresenter();
                        $file_id = explode("-",$zprava->file_id);
                        $original = $DefaultPresenter->nactiISDS($file_id[0]);
                        $original = unserialize($original);

                        // odebrat obsah priloh, aby to neotravovalo
                        unset($original->dmDm->dmFiles);

                        //echo "<pre>"; print_r($original); exit;

                    }

                    $subjekt->id_isds = @$original->dmDm->dbIDSender;
                    $subjekt->nazev_subjektu = @$original->dmDm->dmSender;
                    $subjekt->type = ISDS_Spisovka::typDS(@$original->dmDm->dmSenderType);
                    $subjekt->adresa_ulice = @$original->dmDm->dmSenderAddress;

                    $SubjektModel = new Subjekt();
                    $subjekt_databaze = $SubjektModel->hledat($subjekt,'isds');
                    $zpravy[ $zprava->id ]->subjekt = array('original'=>$subjekt,'databaze'=>$subjekt_databaze);

                }

            }
        } else {
            $zpravy = null;
        }

        // Funkce nekdy loguje varovani, ze vstup neni ve formatu utf-8
        echo @json_encode($zpravy);
        exit;

    }

    public function zkontrolujISDS($config)
    {

        $isds = new ISDS_Spisovka();

        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();

        try {
            $isds->pripojit($config);
            
            $od = $this->Epodatelna->getLastISDS();
            $do = time() + 7200;
            //echo $od ." ". date("j.n.Y",$od) ."<br />";
            //echo $do ." ". date("j.n.Y",$do);
            //exit;
            
            $zpravy = $isds->seznamPrichozichZprav($od,$do);
            if ( count( $zpravy )>0 ) {
                $tmp = array();
                $user = Environment::getUser()->getIdentity();

                $storage_conf = Environment::getConfig('storage');
                eval("\$UploadFile = new ".$storage_conf->type."();");

                foreach($zpravy as $z) {
                    // kontrola existence v epodatelny
                    if ( ! $this->Epodatelna->existuje($z->dmID ,'isds') ) {
                        // nova zprava, ktera neni nahrana v epodatelne

                        // rozparsovat do jednotne podoby
                        $storage = new FileStorage(CLIENT_DIR .'/temp');
                        $cache = new Cache($storage); // nebo $cache = Environment::getCache()
                        if (isset($cache['zkontrolovat_isds_'.$z->dmID])):
                            $mess = $cache['zkontrolovat_isds_'.$z->dmID];
                        else:
                            $mess = $isds->prectiZpravu($z->dmID);
                        endif;

                        //echo "<pre>";
/*
dmDm = objekt
dmDm->dmFiles
dmHash = objekt
dmQTimestamp = string
dmDeliveryTime = 2010-05-11T12:24:13.242+02:00
dmAcceptanceTime = 2010-05-11T12:26:53.899+02:00
dmMessageStatus = 6
dmAttachmentSize = 260

*/
                        /*foreach( $mess->dmDm->dmFiles->dmFile[0] as $k => $m ) {
                            
                            if ( $k == 'dmEncodedContent' ) continue;
                            if ( is_object($m) ) {
                                echo $k ." = objekt\n";
                            } else {
                                echo $k ." = ". $m ."\n";    
                            }

                            
                        }*/



/*
dmID = 342682
dbIDSender = hjyaavk
dmSender = Město Milotice
dmSenderAddress = Kovářská 14/1, 37612 Milotice, CZ
dmSenderType = 10
dmRecipient = Společnost pro výzkum a podporu OpenSource
dmRecipientAddress = 40501 Děčín, CZ
dmAmbiguousRecipient =
dmSenderOrgUnit =
dmSenderOrgUnitNum =
dbIDRecipient = pksakua
dmRecipientOrgUnit =
dmRecipientOrgUnitNum =
dmToHands =
dmAnnotation = Vaše datová zpráva byla přijata
dmRecipientRefNumber = KAV-34/06-ŘKAV/2010
dmSenderRefNumber = AB-44656
dmRecipientIdent = 0.06.00
dmSenderIdent = ZN-161
dmLegalTitleLaw =
dmLegalTitleYear =
dmLegalTitleSect =
dmLegalTitlePar =
dmLegalTitlePoint =
dmPersonalDelivery =
dmAllowSubstDelivery =
dmFiles = objekt
 */

                        $annotation = empty($mess->dmDm->dmAnnotation)?"(Datová zpráva č. ".$mess->dmDm->dmID.")":$mess->dmDm->dmAnnotation;

                        $popis  = '';
                        $popis .= "ID datové zprávy    : ". $mess->dmDm->dmID ."\n";// = 342682
                        $popis .= "Věc, předmět zprávy : ". $annotation ."\n";//  = Vaše datová zpráva byla přijata
                        $popis .= "\n";
                        $popis .= "Číslo jednací odesílatele   : ". $mess->dmDm->dmSenderRefNumber ."\n";//  = AB-44656
                        $popis .= "Spisová značka odesílatele : ". $mess->dmDm->dmSenderIdent ."\n";//  = ZN-161
                        $popis .= "Číslo jednací příjemce     : ". $mess->dmDm->dmRecipientRefNumber ."\n";//  = KAV-34/06-ŘKAV/2010
                        $popis .= "Spisová značka příjemce    : ". $mess->dmDm->dmRecipientIdent ."\n";//  = 0.06.00
                        $popis .= "\n";
                        $popis .= "Do vlastních rukou? : ". (!empty($mess->dmDm->dmPersonalDelivery)?"ano":"ne") ."\n";//  =
                        $popis .= "Doručeno fikcí?     : ". (!empty($mess->dmDm->dmAllowSubstDelivery)?"ano":"ne") ."\n";//  =
                        $popis .= "Zpráva určena pro   : ". $mess->dmDm->dmToHands ."\n";//  =
                        $popis .= "\n";
                        $popis .= "Odesílatel:\n";
                        $popis .= "            ". $mess->dmDm->dbIDSender ."\n";//  = hjyaavk
                        $popis .= "            ". $mess->dmDm->dmSender ."\n";//  = Město Milotice
                        $popis .= "            ". $mess->dmDm->dmSenderAddress ."\n";//  = Kovářská 14/1, 37612 Milotice, CZ
                        $popis .= "            ". $mess->dmDm->dmSenderType ." - ". ISDS_Spisovka::typDS($mess->dmDm->dmSenderType) ."\n";//  = 10
                        $popis .= "            org.jednotka: ". $mess->dmDm->dmSenderOrgUnit ." [". $mess->dmDm->dmSenderOrgUnitNum ."]\n";//  =
                        $popis .= "\n";
                        $popis .= "Příjemce:\n";
                        $popis .= "            ". $mess->dmDm->dbIDRecipient ."\n";//  = pksakua
                        $popis .= "            ". $mess->dmDm->dmRecipient ."\n";//  = Společnost pro výzkum a podporu OpenSource
                        $popis .= "            ". $mess->dmDm->dmRecipientAddress ."\n";//  = 40501 Děčín, CZ
                        //$popis .= "Je příjemce ne-OVM povýšený na OVM: ". $mess->dmDm->dmAmbiguousRecipient ."\n";//  =
                        $popis .= "            org.jednotka: ". $mess->dmDm->dmRecipientOrgUnit ." [". $mess->dmDm->dmRecipientOrgUnitNum ."]\n";//  =
                        $popis .= "\n";
                        $popis .= "Status: ". $mess->dmMessageStatus ." - ". ISDS_Spisovka::stavZpravy($mess->dmMessageStatus) ."\n";
                        $dt_dodani = strtotime($mess->dmDeliveryTime);
                        $dt_doruceni = strtotime($mess->dmAcceptanceTime);
                        $popis .= "Datum a čas dodání   : ". date("j.n.Y G:i:s",$dt_dodani) ." (". $mess->dmDeliveryTime .")\n";//  =
                        $popis .= "Datum a čas doručení : ". date("j.n.Y G:i:s",$dt_doruceni) ." (". $mess->dmAcceptanceTime .")\n";//  =
                        $popis .= "Přiblížná velikost všech příloh : ". $mess->dmAttachmentSize ."kB\n";//  =


                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleLaw ."\n";//  =
                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleYear ."\n";//  =
                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitleSect ."\n";//  =
                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitlePar ."\n";//  =
                        //$popis .= "ID datové zprávy: ". $mess->dmDm->dmLegalTitlePoint ."\n";//  =

                        $zprava = array();
                        $zprava['poradi'] = $this->Epodatelna->getMax();
                        $zprava['rok'] = date('Y');
                        $zprava['isds_signature'] = $z->dmID;
                        $zprava['predmet'] = $annotation;
                        $zprava['popis'] = $popis;
                        $zprava['odesilatel'] = $z->dmSender .', '. $z->dmSenderAddress;
                        //$zprava['odesilatel_id'] = $z->dmAnnotation;
                        $zprava['adresat'] = $config['ucet'] .' ['. $config['idbox'] .']';
                        $zprava['prijato_dne'] = new DateTime();
                        $zprava['doruceno_dne'] = new DateTime($z->dmAcceptanceTime);
                        $zprava['prijal_kdo'] = $user->id;
                        //$zprava['prijal_info'] = serialize($user->identity);

                        $zprava['sha1_hash'] = '';

/*
dmEncodedContent = obsah
dmMimeType = application/pdf
dmFileMetaType = main
dmFileGuid =
dmUpFileGuid =
dmFileDescr = odpoved_OVM.pdf
dmFormat =
 */
                        $prilohy = array();
                        if ( isset($mess->dmDm->dmFiles->dmFile) ) {
                            foreach( $mess->dmDm->dmFiles->dmFile as $index => $file ) {
                                $prilohy[] = array(
                                    'name'=>$file->dmFileDescr,
                                    'size'=>strlen($file->dmEncodedContent),
                                    'mimetype'=> $file->dmMimeType,
                                    'id'=>$index
                                );                                
                            }                        
                        }
                        $zprava['prilohy'] = serialize($prilohy);

                        //$zprava['evidence'] = $z->dmAnnotation;
                        //$zprava['dokument_id'] = $z->dmAnnotation;
                        $zprava['stav'] = 0;
                        $zprava['stav_info'] = '';

                        //print_r($zprava);
                        //exit;

                        if ( $epod_id = $this->Epodatelna->insert($zprava) ) {

                            /* Ulozeni podepsane ISDS zpravy */
                            $data = array(
                                'filename'=>'ep_isds_'.$epod_id .'.zfo',
                                'dir'=>'EP-I-'. sprintf('%06d',$zprava['poradi']).'-'.$zprava['rok'],
                                'typ'=>'5',
                                'popis'=>'Podepsaný originál ISDS zprávy z epodatelny '.$zprava['poradi'].'-'.$zprava['rok']
                                //'popis'=>'Emailová zpráva'
                            );

                            $signedmess = $isds->SignedMessageDownload($z->dmID);

                            if ( $file_o = $UploadFile->uploadEpodatelna($signedmess, $data) ) {
                                // ok
                            } else {
                                $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                                // false
                            }

                            /* Ulozeni reprezentace zpravy */
                            $data = array(
                                'filename'=>'ep_isds_'.$epod_id .'.bsr',
                                'dir'=>'EP-I-'. sprintf('%06d',$zprava['poradi']).'-'.$zprava['rok'],
                                'typ'=>'5',
                                'popis'=>'Byte-stream reprezentace ISDS zprávy z epodatelny '.$zprava['poradi'].'-'.$zprava['rok']
                                //'popis'=>'Emailová zpráva'
                            );

                            if ( $file = $UploadFile->uploadEpodatelna(serialize($mess), $data) ) {
                                // ok
                                $zprava['stav_info'] = 'Zpráva byla uložena';
                                $zprava['file_id'] = $file->id;
                                $this->Epodatelna->update(
                                        array('stav'=>1,
                                              'stav_info'=>$zprava['stav_info'],
                                              'file_id'=>$file->id,
                                            ),
                                        array(array('id=%i',$epod_id))
                                );
                            } else {
                                $zprava['stav_info'] = 'Reprezentace zprávy se nepodařilo uložit';
                                // false
                            }

                        } else {
                            $zprava['stav_info'] = 'Zprávu se nepodařilo uložit';
                        }

                        $tmp[] = $zprava;
                        unset($zprava);
                        //break;
                    }
                    
                }

                return ( count($tmp)>0 )?$tmp:null;
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->flashMessage('Nepodařilo se připojit k ISDS schránce "'. $config['ucet'] .'"!
                                  ISDS chyba: '. $e->getMessage(),'warning');
            return null;
        }
    }

    public function zkontrolujOdchoziISDS($zprava = null)
    {
        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();
        
        $ep_zpravy = array();
        $now = getdate();
        $od = mktime(0,0,0,$now['mon'],$now['mday']-1,$now['year']);
        $do = mktime(0,0,0,$now['mon'],$now['mday']+1,$now['year']);
        
        if ( is_null($zprava) ) {
            // Nacti zpravy, ktere nemaji datum doruceni
            $args = array(
                'where' => array('ep.epodatelna_typ=1','ep.isds_signature IS NOT NULL','ep.prijato_dne=ep.doruceno_dne')
            );
            $epod = $this->Epodatelna->seznam($args)->fetchAll();
            if ( count($epod)>0 ) {
                foreach ($epod as $zprava) {
                    $datum = strtotime($zprava->prijato_dne);
                    if ( $od > ($datum-36000) ) $od = $datum-36000;
                    if ( $do < ($datum+36000) ) $do = $datum+36000;
                    
                    $ep_zpravy[ $zprava->isds_signature ] = array(
                        'id_mess' => $zprava->isds_signature,
                        'epodatelna_id' => $zprava->id,
                        'datum_odeslani' => $zprava->prijato_dne,
                        'datum_doruceni' => $zprava->doruceno_dne,
                        'poradi' => $zprava->poradi,
                        'rok' => $zprava->rok                        
                    );
                }
            }
        } else {
            $datum = strtotime($zprava->prijato_dne);
            $od = $datum - 36000;
            $do = $datum + 36000;
                    
            $ep_zpravy[ $zprava->isds_signature ] = array(
                'id_mess' => $zprava->isds_signature,
                'epodatelna_id' => $zprava->id,
                'datum_odeslani' => $zprava->prijato_dne,
                'datum_doruceni' => $zprava->doruceno_dne,
                'poradi' => $zprava->poradi,
                'rok' => $zprava->rok
            );            
        }
               
        if ( count($ep_zpravy) == 0 ) return false; // neni co kontrolovat        
        
        $config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
        $config_data = $config->toArray();
        $config = $config_data['isds'][0];
        
        $isds = new ISDS_Spisovka();

        try {
            $isds->pripojit($config);
        }
        catch (Exception $e) {
            $this->flashMessage('Nepodařilo se připojit k ISDS schránce "'. $config['ucet'] .'"!
                                  ISDS chyba: '. $e->getMessage(),'warning');
            return null;
        }
       
            $zpravy = $isds->seznamOdeslanychZprav($od,$do);
            
            if ( count( $zpravy )>0 ) {
                $tmp = array();
                $user = Environment::getUser()->getIdentity();

                $storage_conf = Environment::getConfig('storage');
                eval("\$UploadFile = new ".$storage_conf->type."();");

                foreach($zpravy as $mess) {

                    if ( !isset($ep_zpravy[ $mess->dmID ]) ) continue;
                    
                    $annotation = empty($mess->dmAnnotation)?"(Datová zpráva č. ".$mess->dmID.")":$mess->dmAnnotation;

                    $popis  = '';
                    $popis .= "ID datové zprávy    : ". $mess->dmID ."\n";// = 342682
                    $popis .= "Věc, předmět zprávy : ". $annotation ."\n";//  = Vaše datová zpráva byla přijata
                    $popis .= "\n";
                    $popis .= "Číslo jednací odesílatele   : ". $mess->dmSenderRefNumber ."\n";//  = AB-44656
                    $popis .= "Spisová značka odesílatele : ". $mess->dmSenderIdent ."\n";//  = ZN-161
                    $popis .= "Číslo jednací příjemce     : ". $mess->dmRecipientRefNumber ."\n";//  = KAV-34/06-ŘKAV/2010
                    $popis .= "Spisová značka příjemce    : ". $mess->dmRecipientIdent ."\n";//  = 0.06.00
                    $popis .= "\n";
                    $popis .= "Do vlastních rukou? : ". (!empty($mess->dmPersonalDelivery)?"ano":"ne") ."\n";//  =
                    $popis .= "Doručeno fikcí?     : ". (!empty($mess->dmAllowSubstDelivery)?"ano":"ne") ."\n";//  =
                    $popis .= "Zpráva určena pro   : ". $mess->dmToHands ."\n";//  =
                    $popis .= "\n";
                    $popis .= "Odesílatel:\n";
                    $popis .= "            ". $mess->dbIDSender ."\n";//  = hjyaavk
                    $popis .= "            ". $mess->dmSender ."\n";//  = Město Milotice
                    $popis .= "            ". $mess->dmSenderAddress ."\n";//  = Kovářská 14/1, 37612 Milotice, CZ
                    $popis .= "            ". $mess->dmSenderType ." - ". ISDS_Spisovka::typDS($mess->dmSenderType) ."\n";//  = 10
                    $popis .= "            org.jednotka: ". $mess->dmSenderOrgUnit ." [". $mess->dmSenderOrgUnitNum ."]\n";//  =
                    $popis .= "\n";
                    $popis .= "Příjemce:\n";
                    $popis .= "            ". $mess->dbIDRecipient ."\n";//  = pksakua
                    $popis .= "            ". $mess->dmRecipient ."\n";//  = Společnost pro výzkum a podporu OpenSource
                    $popis .= "            ". $mess->dmRecipientAddress ."\n";//  = 40501 Děčín, CZ
                    $popis .= "            org.jednotka: ". $mess->dmRecipientOrgUnit ." [". $mess->dmRecipientOrgUnitNum ."]\n";//  =
                    $popis .= "\n";
                    $popis .= "Status: ". $mess->dmMessageStatus ." - ". ISDS_Spisovka::stavZpravy($mess->dmMessageStatus) ."\n";
                    $dt_dodani = strtotime($mess->dmDeliveryTime);
                    $dt_doruceni = strtotime($mess->dmAcceptanceTime);
                    $popis .= "Datum a čas dodání   : ". date("j.n.Y G:i:s",$dt_dodani) ." (". $mess->dmDeliveryTime .")\n";//  =
                    if ( $dt_doruceni == 0) {
                        $popis .= "Datum a čas doručení : (příjemce zprávu zatím nepřijal)\n";//  =    
                    } else {
                        $popis .= "Datum a čas doručení : ". date("j.n.Y G:i:s",$dt_doruceni) ." (". $mess->dmAcceptanceTime .")\n";//  =                    
                    }                    
                    $popis .= "Přiblížná velikost všech příloh : ". $mess->dmAttachmentSize ."kB\n";//  =

                    $zprava = array();
                    $zprava['popis'] = $popis;
                    if ( !empty($mess->dmAcceptanceTime) ) {
                        $zprava['doruceno_dne'] = new DateTime($mess->dmAcceptanceTime);
                    }
                    $zprava['sha1_hash'] = '';

                    $epod_id = $ep_zpravy[$mess->dmID]['epodatelna_id'];
                        $this->Epodatelna->update($zprava,array(array('id=%i',$epod_id)));
    
                            /* Ulozeni podepsane ISDS zpravy */
                            $data = array(
                                'filename'=>'ep_isds_'.$epod_id .'.zfo',
                                'dir'=>'EP-O-'. sprintf('%06d',$ep_zpravy[$mess->dmID]['poradi']).'-'.$ep_zpravy[$mess->dmID]['rok'],
                                'typ'=>'5',
                                'popis'=>'Podepsaný originál ISDS zprávy z epodatelny '.$ep_zpravy[$mess->dmID]['poradi'].'-'.$ep_zpravy[$mess->dmID]['rok']
                            );

                            $signedmess = $isds->SignedSentMessageDownload($mess->dmID);

                            if ( $file_o = $UploadFile->uploadEpodatelna($signedmess, $data) ) {
                                // ok
                            } else {
                                $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                                // false
                            }

                            /* Ulozeni reprezentace zpravy */
                            $data = array(
                                'filename'=>'ep_isds_'.$epod_id .'.bsr',
                                'dir'=>'EP-O-'. sprintf('%06d',$ep_zpravy[$mess->dmID]['poradi']).'-'.$ep_zpravy[$mess->dmID]['rok'],
                                'typ'=>'5',
                                'popis'=>'Byte-stream reprezentace ISDS zprávy z epodatelny '.$ep_zpravy[$mess->dmID]['poradi'].'-'.$ep_zpravy[$mess->dmID]['rok']
                            );

                            if ( $file = $UploadFile->uploadEpodatelna(serialize($mess), $data) ) {
                                // ok
                                $zprava['stav_info'] = 'Zpráva byla uložena';
                                $zprava['file_id'] = $file->id;
                                $this->Epodatelna->update(
                                        array('stav'=>1,
                                              'stav_info'=>$zprava['stav_info'],
                                              'file_id'=>$file->id,
                                            ),
                                        array(array('id=%i',$epod_id))
                                );
                            } else {
                                $zprava['stav_info'] = 'Reprezentace zprávy se nepodařilo uložit';
                                // false
                            }

                        $tmp[] = $zprava;
                        unset($zprava);
                        //break;
                        
                    }
                }
                
                return ( count($tmp)>0 )?$tmp:null;
    }
    
    // Vrátí počet nových zpráv nebo řetězec s popisem chyby
    
    private function zkontrolujEmail($config)
    {
        if ( is_null($this->Epodatelna) ) $this->Epodatelna = new Epodatelna();

        $imap = new ImapClient();
        $email_mailbox = '{'. $config['server'] .':'. $config['port'] .''. $config['typ'] .'}'. $config['inbox'];

        $success = $imap->connect($email_mailbox,$config['login'],$config['password']);
        if (!$success) {
            $msg = 'Nepodařilo se připojit k emailové schránce "'. $config['ucet'] .'"!<br />
                    IMAP chyba: '. $imap->error();            
            return $msg;
        }
        
        if (!$imap->count_messages()) {
            //  nejsou žádné zprávy k přijetí
            $imap->close();
            return 0;
        }

        $tmp = array();
        $user = Environment::getUser()->getIdentity();

        $storage_conf = Environment::getConfig('storage');
        eval("\$UploadFile = new ".$storage_conf->type."();");

        $zpravy = $imap->get_head_messages();
        
        foreach($zpravy as $z) {
            // kontrola existence v epodatelny
            if ( ! $this->Epodatelna->existuje($z->message_id ,'email') ) {
                // nova zprava, ktera neni nahrana v epodatelne

                // Nacteni kompletni zpravy
                $mess = $imap->get_message($z->id_part);
                
                $popis = '';
                foreach ($mess->texts as $zpr) {
                    if($zpr->subtype == "HTML") {
                        $zpr->text_convert = str_ireplace("<br>", "\n", $zpr->text_convert);
                        $zpr->text_convert = str_ireplace("<br />", "\n", $zpr->text_convert);
                        $popis .= htmlspecialchars($zpr->text_convert);
                    } else {
                        $popis .= htmlspecialchars($zpr->text_convert);
                    }
                    $popis .= "\n\n";
                }

                if ( empty($z->from_address) ) {
                    $predmet = empty($z->subject)?"[Bez předmětu] Emailová zpráva":$z->subject;
                } else {
                    $predmet = empty($z->subject)?"[Bez předmětu] Emailová zpráva od ".$z->from_address:$z->subject;
                }


                $zprava = array();
                $zprava['poradi'] = $this->Epodatelna->getMax();
                $zprava['rok'] = date('Y');
                $zprava['email_signature'] = $z->message_id;
                $zprava['predmet'] = $predmet;
                $zprava['popis'] = $popis;
                $zprava['odesilatel'] = $z->from_address;
                //$zprava['odesilatel_id'] = $z->dmAnnotation;
                $zprava['adresat'] = $config['ucet'] .' ['. $config['login'] .']';
                $zprava['prijato_dne'] = new DateTime();
                $zprava['doruceno_dne'] = new DateTime( date('Y-m-d H:i:s',$z->udate) );
                $zprava['prijal_kdo'] = $user->id;
                //$zprava['prijal_info'] = serialize($user->identity);

                $zprava['sha1_hash'] = sha1($mess->source);

                $prilohy = array();
                if( isset($mess->attachments) ) {
                    foreach ($mess->attachments as $pr) {
                        $prilohy[] = array(
                            'name'=>$pr->filename,
                            'size'=>$pr->size,
                            'mimetype'=> FileModel::mimeType($pr->filename),
                            'id'=>$pr->id_part
                        );
                    }
                }
                $zprava['prilohy'] = serialize($prilohy);

                //$zprava['evidence'] = $z->dmAnnotation;
                //$zprava['dokument_id'] = $z->dmAnnotation;
                $zprava['stav'] = 0;
                $zprava['stav_info'] = '';
                //$zprava['source'] = $z;
                //unset($mess->source);
                //$zprava['source'] = $mess;
                $zprava['file_id'] = null;

                // Test na dostupnost epodpisu
                if ( $config['only_signature'] == true ) {
                    // pouze podepsane - obsahuje el.podpis
                    if ( count($mess->signature)>0 ) {
                        if ( $config['qual_signature'] == true ) {
                            // pouze kvalifikovane
                            $esign = new esignature();
                            $esign->setCACert(LIBS_DIR .'/email/ca_certifikaty');
                            $tmp_email = CLIENT_DIR .'/temp/emailtest_'. sha1($mess->message_id).'.tmp';
                            file_put_contents($tmp_email,$mess->source);
                            $esigned = $esign->verifySignature($tmp_email, $esign_cert, $esign_status);
                            if ( @$esigned['cert_info']['CA_is_qualified'] == 1 ) {
                                // obsahuje - pokracujeme
                            } else {
                                // neobsahuje kvalifikovany epodpis
                                $zprava['stav_info'] = 'Emailová zpráva byla odmítnuta. Neobsahuje kvalifikovaný elektronický podpis';
                                $this->Epodatelna->insert($zprava);
                                continue;
                            }
                        }
                    } else {
                        // email neobsahuje epodpis
                        $zprava['stav_info'] = 'Emailová zpráva byla odmítnuta. Neobsahuje elektronický podpis';
                        $this->Epodatelna->insert($zprava);
                        continue;
                    }
                }                        
                
                if ( $epod_id = $this->Epodatelna->insert($zprava) ) {

                    $data = array(
                        'filename'=>'ep_email_'.$epod_id .'.eml',
                        'dir'=>'EP-I-'. sprintf('%06d',$zprava['poradi']).'-'.$zprava['rok'],
                        'typ'=>'5',
                        'popis'=>'Emailová zpráva z epodatelny '.$zprava['poradi'].'-'.$zprava['rok']
                        //'popis'=>'Emailová zpráva'
                    );

                    if ( $file = $UploadFile->uploadEpodatelna($mess->source, $data) ) {
                        // ok
                        $zprava['stav_info'] = 'Zpráva byla uložena';
                        $zprava['file_id'] = $file->id;
                        $this->Epodatelna->update(
                                array('stav'=>1,
                                      'stav_info'=>$zprava['stav_info'],
                                      'file_id'=>$file->id
                                    ),
                                array(array('id=%i',$epod_id))
                        );
                    } else {
                        $zprava['stav_info'] = 'Originál zprávy se nepodařilo uložit';
                        // false
                    }
                } else {
                    $zprava['stav_info'] = 'Zprávu se nepodařilo uložit';
                }

                $tmp[] = $zprava;
                unset($zprava);

            }
        }
        
        $imap->close();
        return count($tmp);
    }

    public function nactiISDS($file_id)
    {
        $storage_conf = Environment::getConfig('storage');
        eval("\$DownloadFile = new ".$storage_conf->type."();");

        if ( strpos($file_id,"-") !== false ) {
            list($file_id, $part) = explode("-",$file_id);
        }

        $FileModel = new FileModel();
        $file = $FileModel->getInfo($file_id);
        $res = $DownloadFile->download($file,1);
        if ( $res >= 1 ) {
            return null;
        } else {
            return $res;
        }
        

    }

    public function nactiEmail($file_id, $output = 0)
    {

        $storage_conf = Environment::getConfig('storage');
        eval("\$DownloadFile = new ".$storage_conf->type."();");

        if ( strpos($file_id,"-") !== false ) {
            list($file_id,$part) = explode("-",$file_id);
        }

        $FileModel = new FileModel();
        $file = $FileModel->getInfo($file_id);
        $res = $DownloadFile->download($file,2);

        if ( $output == 1 ) {
            return $res;
        }

        $tmp = array();
        // Kontrola epodpisu
        $esign = new esignature();
        $esign->setCACert(LIBS_DIR .'/email/ca_certifikaty');
        $esigned = $esign->verifySignature($res, $esign_cert, $esign_status);

        //Debug::dump($esigned); exit;
        $tmp['signature']['cert'] = @$esigned['cert'];
        $tmp['signature']['cert_info'] = @$esigned['cert_info'];
        $tmp['signature']['status'] = @$esigned['status'];
        $tmp['signature']['signed'] = @$esigned['return'];

        //$imap = new ImapClient();
        $imap = new ImapClientFile();

        if ( $imap->open($res) ) {
            $zprava = $imap->get_head_message(0);
            $tmp['zprava'] = $zprava;
        } else {
            $tmp['zprava'] = null;
        }

        return $tmp;
    }

    public function actionIsdsovereni()
    {
        $this->template->error = 0;
        $this->template->vysledek = "";
        $epodatelna_id = $this->getParam('id');
        if ( $epodatelna_id ) {
            $Epodatelna = new Epodatelna();
            $epodatelna_info = $Epodatelna->getInfo($epodatelna_id);

            if ( $epodatelna_info ) {
                if ( !empty( $epodatelna_info->file_id ) ) {
                    $FileModel = new FileModel();
                    $file = $FileModel->fetchRow(array(array("nazev=%s",'ep-isds-'.$epodatelna_id.'.zfo'))  )->fetch();
                    if ( $file ) {
                    
                        // Nacteni originalu DS
                        $storage_conf = Environment::getConfig('storage');
                        eval("\$DownloadFile = new ".$file->real_type."();");
                        $source = $DownloadFile->download($file,1);
                        
                        if ( $source ) {
                                
                            $isds = new ISDS_Spisovka();                           
                            try {
                                $isds->pripojit();
                                if ( $isds->AuthenticateMessage( $source ) ) {
                                    $this->template->vysledek = "Datová zpráva byla ověřena a je platná.";
                                } else {
                                    $this->template->error = 4;
                                    $this->template->vysledek = "Datová zpráva byla ověřena, ale není platná!".
                                                                "<br />".
                                                                'ISDS zpráva: '. $isds->error();
                                }
                                    
                            } catch (Exception $e) {
                                $this->template->error = 3;
                                $this->template->vysledek = "Nepodařilo se připojit k ISDS schránce!".
                                                                "<br />".
                                                                'chyba: '. $e->getMessage();
                            }
                        }
                    }
                }
            } else {
                $this->template->vysledek = "Nebyla nalezena zpráva!";
                $this->template->error = 1;
            }
        } else {
            $this->template->vysledek = "Neplatný parametr!";
            $this->template->error = 1;
        }
        
        $is_ajax = $this->getParam("is_ajax");
        if ( $is_ajax ) {
            $this->setLayout(FALSE);
        }        
        
    }

}
