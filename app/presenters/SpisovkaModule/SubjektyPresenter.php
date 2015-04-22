<?php

class SubjektyPresenter extends BasePresenter
{

    public function renderVyber()
    {
        $abcPaginator = new AbcPaginator($this, 'abc');
        $abc = $abcPaginator->getParameter('abc');
        $user_config = Nette\Environment::getVariable('user_config');
        $vp = new VisualPaginator($this, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;
        
        $args = array( 'where' => array("stav=1") );
        if ( !empty($abc) )
            $args['where'][] = array("nazev_subjektu LIKE %s OR prijmeni LIKE %s",$abc.'%',$abc.'%');
            
        $Subjekt = new Subjekt();
        $result = $Subjekt->seznam($args);   
        $paginator->itemCount = count($result);
        $this->template->seznam = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
    }

    public function renderAres()
    {
        $ic = $this->getParameter('id',null);
        $ares = new Ares($ic);
        $data = $ares->get();
        echo json_encode($data);
        exit;
    }
    
    public function renderNovy()
    {
        $this->template->title = " - Nový subjekt";
        $this->template->subjektForm = $this['novyForm'];
    }
    
    protected function vytvorFormular()
    {
        $typ_select = Subjekt::typ_subjektu();
        $stat_select = array("" => "Neuveden") + Subjekt::stat();

        $form1 = new Nette\Application\UI\Form();
        $form1->getElementPrototype()->id('subjekt-vytvorit');

        $form1->addSelect('type', 'Typ subjektu:', $typ_select);
        $form1->addText('nazev_subjektu', 'Název subjektu:', 50, 255);
        $form1->addText('ic', 'IČ:', 12, 8);
        $form1->addText('dic', 'DIČ:', 12, 12);

        $form1->addText('jmeno', 'Jméno:', 50, 24);
        $form1->addText('prostredni_jmeno', 'Prostřední jméno:', 50, 35);
        $form1->addText('prijmeni', 'Příjmení:', 50, 35);
        $form1->addText('rodne_jmeno', 'Rodné jméno:', 50, 35);
        $form1->addText('titul_pred', 'Titul před:', 20, 35);
        $form1->addText('titul_za', 'Titul za:', 20, 10);

        $form1->addDatePicker('datum_narozeni', 'Datum narození:', 10);
        $form1->addText('misto_narozeni', 'Místo narození:', 50, 48);
        $form1->addText('okres_narozeni', 'Okres narození:', 50, 48);
        $form1->addText('narodnost', 'Národnost / Stát registrace:', 50, 48);
        $form1->addSelect('stat_narozeni', 'Stát narození:', $stat_select)
                ->setValue('CZE');

        $form1->addText('adresa_ulice', 'Ulice / část obce:', 50, 48);
        $form1->addText('adresa_cp', 'číslo popisné:', 10, 10);
        $form1->addText('adresa_co', 'Číslo orientační:', 10, 10);
        $form1->addText('adresa_mesto', 'Obec:', 50, 48);
        $form1->addText('adresa_psc', 'PSČ:', 10, 10);
        $form1->addSelect('adresa_stat', 'Stát:', $stat_select)
                ->setValue('CZE');

        $form1->addText('email', 'Email:', 50, 250);
        $form1->addText('telefon', 'Telefon:', 50, 150);
        $form1->addText('id_isds', 'ID datové schránky:', 10, 50);

        $form1->addTextArea('poznamka', 'Poznámka:', 50, 6);

        $renderer = $form1->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'dl';
        $renderer->wrappers['label']['container'] = 'dt';
        $renderer->wrappers['control']['container'] = 'dd';

        return $form1;
    }
    
    protected function createComponentNovyForm()
    {
        $form1 = $this->vytvorFormular();
        
        $form1->getElementPrototype()->onsubmit('return false;');      
        $form1->addSubmit('novy', 'Vytvořit');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE);
        
        return $form1;
    }

    protected function createComponentUpravitForm()
    {
        $form1 = $this->vytvorFormular();

        $subjekt = @$this->template->Subjekt;
        $form1->addHidden('id')
                ->setValue(@$subjekt->id);
        
        $form1->addSubmit('upravit', 'Upravit');
        $form1->addSubmit('storno', 'Zrušit')
                 ->setValidationScope(FALSE);
                 
        if ($subjekt !== null) {
            $form1['type']->setValue(@$subjekt->type);
            $form1['nazev_subjektu']->setValue(@$subjekt->nazev_subjektu);
            $form1['ic']->setValue(@$subjekt->ic);
            $form1['dic']->setValue(@$subjekt->dic);

            $form1['jmeno']->setValue(@$subjekt->jmeno);
            $form1['prostredni_jmeno']->setValue(@$subjekt->prostredni_jmeno);
            $form1['prijmeni']->setValue(@$subjekt->prijmeni);
            $form1['rodne_jmeno']->setValue(@$subjekt->rodne_jmeno);
            $form1['titul_pred']->setValue(@$subjekt->titul_pred);
            $form1['titul_za']->setValue(@$subjekt->titul_za);

            $form1['datum_narozeni']->setValue(@$subjekt->datum_narozeni);
            $form1['misto_narozeni']->setValue(@$subjekt->misto_narozeni);
            $form1['okres_narozeni']->setValue(@$subjekt->okres_narozeni);
            $form1['narodnost']->setValue(@$subjekt->narodnost);
            $form1['stat_narozeni']->setValue(@$subjekt->stat_narozeni);

            $form1['adresa_ulice']->setValue(@$subjekt->adresa_ulice);
            $form1['adresa_cp']->setValue(@$subjekt->adresa_cp);
            $form1['adresa_co']->setValue(@$subjekt->adresa_co);
            $form1['adresa_mesto']->setValue(@$subjekt->adresa_mesto);
            $form1['adresa_psc']->setValue(@$subjekt->adresa_psc);
            $form1['adresa_stat']->setValue(@$subjekt->adresa_stat);

            $form1['email']->setValue(@$subjekt->email);
            $form1['telefon']->setValue(@$subjekt->telefon);
            $form1['id_isds']->setValue(@$subjekt->id_isds);

            $form1['poznamka']->setValue(@$subjekt->poznamka);
        }

        return $form1;
    }

    public function actionVytvoritAjax()
    {
        $data = $this->getHttpRequest()->getPost();

        if ( !isset($data['id']) ) {
            echo "#Subjekt nebyl vytvořen! Chyba aplikace.";
            exit;
        }
        $id = $data['id'];
        
        $vytvorit = array(
            'type'=> ( !empty($data['subjekt_typ'][$id])?$data['subjekt_typ'][$id]:"" ),
            'ic'=>'',
            'nazev_subjektu' => ( !empty($data['subjekt_nazev'][$id])?$data['subjekt_nazev'][$id]:"" ),
            'prijmeni' => ( !empty($data['subjekt_prijmeni'][$id])?$data['subjekt_prijmeni'][$id]:"" ),
            'jmeno' => ( !empty($data['subjekt_jmeno'][$id])?$data['subjekt_jmeno'][$id]:"" ),
            'titul_pred' => ( !empty($data['subjekt_titulpred'][$id])?$data['subjekt_titulpred'][$id]:"" ),
            'titul_za' => ( !empty($data['subjekt_titulza'][$id])?$data['subjekt_titulza'][$id]:"" ),
            'adresa_ulice' => ( !empty($data['subjekt_ulice'][$id])?$data['subjekt_ulice'][$id]:"" ),
            'adresa_cp' => ( !empty($data['subjekt_cp'][$id])?$data['subjekt_cp'][$id]:"" ),
            'adresa_mesto' => ( !empty($data['subjekt_mesto'][$id])?$data['subjekt_mesto'][$id]:"" ),
            'adresa_psc' => ( !empty($data['subjekt_psc'][$id])?$data['subjekt_psc'][$id]:"" ),
            'email' => ( !empty($data['subjekt_email'][$id])?$data['subjekt_email'][$id]:"" ),
            'id_isds' => ( !empty($data['subjekt_isds'][$id])?$data['subjekt_isds'][$id]:"" ),
            'adresa_stat' => ( !empty($data['stat'][$id])?$data['stat'][$id]:"" ),
            'telefon'=>'',
        );

        $Subjekt = new Subjekt();

        try {
            // vytvor subjekt
            $subjekt_id = $Subjekt->ulozit($vytvorit);
            $subjekt_info = $Subjekt->getInfo($subjekt_id);
            
            try {
                if (isset($data['dokument_id'])) {
                    // byli jsme zavolani z dokumentu modulu spisovka
                    $DokumentSubjekt = new DokumentSubjekt();
                    $DokumentSubjekt->pripojit($data['dokument_id'], $subjekt_id, 'AO');
                }

                echo $subjekt_info->id ."#". Subjekt::displayName($subjekt_info, 'full');
            }
            catch (Exception $e) {
                echo "#Subjekt byl vytvořen ale nepodařilo se jej připojit k dokumentu.";
            }
        } catch (Exception $e) {
            echo "#Subjekt se nepodařil vytvořit.";
        }

        exit;
    }

}


class Spisovka_SubjektyPresenter extends SubjektyPresenter
{
    public function renderVyber()
    {
        parent::renderVyber();
        $this->template->dokument_id = $this->getParameter('dok_id',null);
    }

    // Volano pouze pres Ajax
    public function renderNacti()
    {
        $dokument_id = $this->getParameter('id',null); // tady jako dokument_id

        $DokumentSubjekt = new DokumentSubjekt();
        $seznam = $DokumentSubjekt->subjekty($dokument_id);
        $this->template->seznamSubjektu = $seznam;
        $this->template->dokument_id = $dokument_id;
    }

    // Volano pouze pres Ajax
    public function actionVybrano()
    {
        try {            
            $subjekt_id = $this->getParameter('id',null);
            $dokument_id = $this->getParameter('dok_id',null);
            $typ = $this->getParameter('typ',null);
            $autocomplete = $this->getParameter('autocomplete',0);
            
            $Subjekt = new Subjekt();
            $subjekt = $Subjekt->getInfo($subjekt_id);            
            if ( $subjekt ) {

                // Propojit s dokumentem
                $DokumentSubjekt = new DokumentSubjekt();
                $DokumentSubjekt->pripojit($dokument_id, $subjekt_id, $typ);

                $Log = new LogModel();
                $Log->logDokument($dokument_id, LogModel::SUBJEKT_PRIDAN,'Přidán subjekt "'. Subjekt::displayName($subjekt,'jmeno') .'"');

                echo '###vybrano###'. $dokument_id;

            } else {
                // chyba            
                echo 'Zvolený subjekt se nepodařilo načíst.';
            }
        }
        catch (Exception $e) {
            echo 'Chyba ' . $e->getCode() . ' - ' . $e->getMessage();
        }
        
        $this->terminate();
    }

    public function renderOdebrat()
    {
        $subjekt_id = $this->getParameter('id',null);
        $dokument_id = $this->getParameter('dok_id',null);

        $DokumentSubjekt = new DokumentSubjekt();
        $param = array( array('subjekt_id=%i',$subjekt_id),array('dokument_id=%i',$dokument_id) );

        if ( $seznam = $DokumentSubjekt->odebrat($param) ) {

            $Log = new LogModel();
            $Subjekt = new Subjekt();
            $subjekt_info = $Subjekt->getInfo($subjekt_id);
            $Log->logDokument($dokument_id, LogModel::SUBJEKT_ODEBRAN,'Odebrán subjekt "'. Subjekt::displayName($subjekt_info,'jmeno') .'"');

            $this->flashMessage('Subjekt byl úspěšně odebrán.');
        } else {
            $this->flashMessage('Subjekt se nepodařilo odebrat. Zkuste to znovu.','warning');
        }
        $this->redirect(':Spisovka:Dokumenty:detail',array('id'=>$dokument_id));


    }

    public function actionSeznamAjax()
    {

        $Subjekt = new Subjekt();

        $seznam = array();

        $term = $this->getParameter('term');

        if ( !empty($term) ) {
            $args = array('where'=>array(array("LOWER(CONCAT_WS('', nazev_subjektu,prijmeni,jmeno,ic,adresa_mesto,adresa_ulice,email,telefon,id_isds)) LIKE LOWER(%s)",'%'.$term.'%'),
                'stav = 1'
            ));
            $seznam_subjektu = $Subjekt->seznam($args);
        } else {
            $seznam_subjektu = $Subjekt->seznam();
        }

        if ( count($seznam_subjektu)>0 ) {
            foreach( $seznam_subjektu as $subjekt ) {
                $seznam[ ] = array(
                    "id"=> $subjekt->id,
                    "value"=> Subjekt::displayName($subjekt,'full'),
                    "nazev"=> Subjekt::displayName($subjekt,'full'),
                    "full"=> Subjekt::displayName($subjekt,'full'),
                    "item"=>$subjekt
                );
            }
        }

        echo json_encode($seznam);

        exit;
    }

    public function actionSeznamtypusubjektu()
    {
        $typy_subjektu = Subjekt::typ_subjektu();
        echo json_encode($typy_subjektu);
        exit;
    }    
    
    public function actionSeznamStatuAjax()
    {
        echo json_encode(Subjekt::stat());
        exit;
    }    

    public function renderUpravit()
    {
        $subjekt_id = $this->getParameter('id',null);
        $dokument_id = $this->getParameter('dok_id',null);

        $model = new Subjekt();
        $subjekt = $subjekt_id === null ? null : $model->getInfo($subjekt_id);

        $this->template->Subjekt = $subjekt;
        $this->template->dokument_id = $dokument_id;
        $this->template->FormUpravit = $this->getParameter('upravit',null);
        $this->template->subjektForm = $this['upravitForm'];
    }

/**
 *
 * Formular a zpracovani pro udaju osoby
 *
 */

    public function renderIsdsid()
    {
        $id = $this->getParameter('id',null);
        
        if ( is_null($id) ) {
            exit;
        }
        
        $isds = new ISDS_Spisovka();
        try {
            $isds->pripojit();
            $filtr['dbID'] = $id;
            $prijemci = $isds->FindDataBoxEx($filtr);
            if ( isset($prijemci->dbOwnerInfo) ) {
                
                $info = $prijemci->dbOwnerInfo[0];
                
                /*echo "<pre>";
                print_r($info);
                echo "</pre>";*/
                
                echo json_encode($info);
            } else {
                echo json_encode(array("error"=>$isds->error()));
            }
            
            exit;
                                    
        } catch (Exception $e) {
            echo json_encode(array("error"=>$e->getMessage()));
            exit;
        }
    }    

    protected function createComponentUpravitForm()
    {
        $form1 = parent::createComponentUpravitForm();

        $form1->getElementPrototype()->onsubmit('return false;');
        $form1->onSuccess[] = array($this, 'upravitFormSucceeded');
        $form1['upravit']->controlPrototype->onclick("return subjektUpravitSubmit();");
        $form1['storno']->controlPrototype->onclick("return subjektUpravitStorno();");
                
        return $form1;
    }
    
    public function upravitFormSucceeded(Nette\Application\UI\Form $form, $data)
    {
        $subjekt_id = $data['id'];

        if (empty($data['stat_narozeni']))
            $data['stat_narozeni'] = null;
        if (empty($data['adresa_stat']))
            $data['adresa_stat'] = null;

        try {
            $Subjekt = new Subjekt();
            $Subjekt->ulozit($data, $subjekt_id);
            
            echo "###zmeneno###";

        } catch (Exception $e) {
            echo "Chyba! Subjekt se nepodařilo upravit.<br/>" . $e->getMessage();
        }
        
        $this->terminate();
    }

    
    protected function createComponentNovyForm()
    {
        $form1 = parent::createComponentNovyForm();
        
        // formulář je odesílán přes Ajax, nelze tedy navázat událost na submit tlačítko
        $form1->onSuccess[] = array($this, 'novyFormSucceeded');
        $form1['novy']->controlPrototype->onclick("return subjektNovySubmit();");
        $form1['storno']->controlPrototype->onclick("return dialogVyberSubjektu();");

        return $form1;
    }

    public function novyFormSucceeded(Nette\Application\UI\Form $form, $data)
    {
        try {
            $Subjekt = new Subjekt();
            $Subjekt->ulozit((array)$data);
            echo "OK";
            
        } catch (Exception $e) {
            echo "Chyba! Subjekt se nepodařilo vytvořit.<br/>" . $e->getMessage();
        }
        $this->terminate();
    }

    // Volano pouze pres Ajax
    // Nevraci zpet informaci (krome HTTP stavoveho kodu), predpoklada se, ze operace se vzdy provede uspesne
    public function actionZmenRezim()
    {
        $subjekt_id = $this->getParameter('id',null);
        $dokument_id = $this->getParameter('dok_id',null);
        $typ = $this->getParameter('typ',null);
        
        // Zmen typ propojeni
        $DokumentSubjekt = new DokumentSubjekt();
        $DokumentSubjekt->zmenit($dokument_id, $subjekt_id, $typ);

        echo 'OK';
        $this->terminate();
    }

}
