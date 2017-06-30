<?php

namespace Spisovka;

class Dokument extends BaseModel
{

    protected $name = 'dokument';

    /**
     * Seznam ID dokumentu
     */
    public function seznam(array $args = array())
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
            //$order = array('dokument_id'=>'DESC');
            $order = array('d.podaci_denik_rok' => 'DESC', 'd.podaci_denik_poradi' => 'DESC', 'd.poradi' => 'DESC');
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
            'from' => array($this->name => 'd'),
            'cols' => array('id'),
            'where' => $where,
            'where_or' => $where_or,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
            'leftJoin' => array()
        );

        if (isset($args['cols']))
            $sql['cols'] = array_merge($sql['cols'], $args['cols']);
        if (isset($args['leftJoin']))
            $sql['leftJoin'] = array_merge($sql['leftJoin'], $args['leftJoin']);

        return $this->selectComplex($sql);
    }

    /**
     * Seznam ID dokumentu
     */
    public function seznamKeSkartaci(array $args = array())
    {
        /*
         * Metodika nejdelsiho skartacniho roku:
         * - vypocteme skartacni rok ze tri mist
         *   - dokument - skartacni_lhuta + datum_spousteci_udalosti 
         *   - spis - skartacni_lhuta + datum_uzavreni
         *   - ostatnich dokumentu spisu - skartacni_lhuta + datum_spousteci_udalosti
         * - vybereme nejdelsi skartacni rok
         * - tento se pouzije pro podminku NOW() > skartacni_rok
         * 
         */

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
            //$order = array('dokument_id'=>'DESC');
            $order = array('d.podaci_denik_rok' => 'DESC', 'd.podaci_denik_poradi' => 'DESC', 'd.poradi' => 'DESC');
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
            'from' => array($this->name => 'd'),
            'cols' => array('id'),
            'where' => $where,
            'where_or' => $where_or,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
            'leftJoin' => array(
                'spisy' => array(
                    'from' => array($this->tb_spis => 'spis'),
                    'on' => array('spis.id = d.spis_id'),
                    'cols' => null
                ),
                'dokument2' => array(
                    'from' => array($this->tb_dokument => 'd2'),
                    'on' => array('d2.spis_id = spis.id'),
                    'cols' => null
                ),
                'typ_dokumentu' => array(
                    'from' => array($this->tb_dokumenttyp => 'dtyp'),
                    'on' => array('dtyp.id = d.dokument_typ_id'),
                    'cols' => null
                )
            )
        );

        if (isset($args['leftJoin'])) {
            $sql['leftJoin'] = array_merge($sql['leftJoin'], $args['leftJoin']);
        }

        $sql['group'] = 'd.id';
        $sql['having'] = array("NOW() > MAX(GREATEST(
COALESCE(DATE_ADD(d.datum_spousteci_udalosti, INTERVAL d.skartacni_lhuta YEAR), '0'),
COALESCE(DATE_ADD(spis.datum_uzavreni, INTERVAL spis.skartacni_lhuta YEAR), '0'),
COALESCE(DATE_ADD(d2.datum_spousteci_udalosti, INTERVAL d2.skartacni_lhuta YEAR), '0')
))");

        // return DibiResult
        return $this->selectComplex($sql);
    }

    /**
     * Seznam dokumentu bez zivotniho cyklu
     *
     * @param <type> $args
     * @return <type>
     */
    public function seznamKlasicky($args = null)
    {

        if (isset($args['where'])) {
            $where = $args['where'];
        } else {
            $where = array(array('stav<100'));
        }

        if (isset($args['order'])) {
            $order = $args['order'];
        } else {
            $order = array('podaci_denik_rok' => 'DESC', 'podaci_denik_poradi' => 'DESC');
        }

        if (isset($args['offset'])) {
            $offset = $args['offset'];
        } else {
            $offset = null;
        }

        if (isset($args['limit'])) {
            $limit = $args['limit'];
        } else {
            $limit = null;
        }


        $select = $this->select($where, $order, $offset, $limit);

        $rows = $select->fetchAll();

        return ($rows) ? $rows : NULL;
    }

    public function hledat($query)
    {
        $args = array(
            'where_or' => array(
                array('d.nazev LIKE %s', '%' . $query . '%'),
                array('d.popis LIKE %s', '%' . $query . '%'),
                array('d.cislo_jednaci LIKE %s', '%' . $query . '%'),
                array('d.jid LIKE %s', '%' . $query . '%')
            )
        );
        return $args;
    }

    public function seradit(&$args, $typ = null)
    {

        switch ($typ) {
            case 'stav':
                $args['order'] = array('d.stav');
                break;
            case 'stav_desc':
                $args['order'] = array('d.stav' => 'DESC');
                break;
            case 'cj':
                // optimalizace SQL, na sloupci cislo_jednaci ted mame index
                // $args['order'] = array('d.podaci_denik_rok', 'd.podaci_denik_poradi', 'd.poradi');
                $args['order'] = ['d.cislo_jednaci'];
                break;
            case 'cj_desc':
                // $args['order'] = array('d.podaci_denik_rok' => 'DESC', 'd.podaci_denik_poradi' => 'DESC', 'd.poradi' => 'DESC');
                $args['order'] = ['d.cislo_jednaci' => 'DESC'];
                break;
            case 'jid':
                $args['order'] = array('d.id');
                break;
            case 'jid_desc':
                $args['order'] = array('d.id' => 'DESC');
                break;
            case 'vec':
                $args['order'] = array('d.nazev');
                break;
            case 'vec_desc':
                $args['order'] = array('d.nazev' => 'DESC');
                break;
            case 'dvzniku':
                $args['order'] = array('d.datum_vzniku');
                break;
            case 'dvzniku_desc':
                $args['order'] = array('d.datum_vzniku' => 'DESC');
                break;
            case 'skartacni_znak':
                $args['order'] = array('d.skartacni_znak', 'd.podaci_denik_rok', 'd.podaci_denik_poradi');
                break;
            case 'skartacni_znak_desc':
                $args['order'] = array('d.skartacni_znak' => 'DESC', 'd.podaci_denik_rok' => 'DESC', 'd.podaci_denik_poradi' => 'DESC');
                break;
            case 'spisovy_znak':
                $args['leftJoin']['sznak'] = array(
                    'from' => array($this->tb_spisovy_znak => 'sznak'),
                    'on' => array('sznak.id=d.spisovy_znak_id'),
                    'cols' => null
                );
                $args['order'] = array('sznak.nazev', 'd.podaci_denik_rok', 'd.podaci_denik_poradi');
                break;
            case 'spisovy_znak_desc':
                $args['leftJoin']['sznak'] = array(
                    'from' => array($this->tb_spisovy_znak => 'sznak'),
                    'on' => array('sznak.id=d.spisovy_znak_id'),
                    'cols' => null
                );
                $args['order'] = array('sznak.nazev' => 'DESC', 'd.podaci_denik_rok' => 'DESC', 'd.podaci_denik_poradi' => 'DESC');
                break;
            case 'prideleno':
                $args['leftJoin']['wf_user'] = array(
                    'from' => array($this->tb_user => 'wf_user'),
                    'on' => array('wf_user.id = d.owner_user_id'),
                    'cols' => null
                );
                $args['leftJoin']['wf_osoba'] = array(
                    'from' => array($this->tb_osoba => 'oso'),
                    'on' => array('oso.id=wf_user.osoba_id'),
                    'cols' => null
                );
                $args['order'] = array('oso.prijmeni', 'oso.jmeno');
                break;
            case 'prideleno_desc':
                $args['leftJoin']['wf_user'] = array(
                    'from' => array($this->tb_user => 'wf_user'),
                    'on' => array('wf_user.id = d.owner_user_id'),
                    'cols' => null
                );
                $args['leftJoin']['wf_osoba'] = array(
                    'from' => array($this->tb_osoba => 'oso'),
                    'on' => array('oso.id=wf_user.osoba_id'),
                    'cols' => null
                );
                $args['order'] = array('oso.prijmeni' => 'DESC', 'oso.jmeno' => 'DESC');
                break;

            default:
                break;
        }
        return $args;
    }

    private function _datum_param_to_sql($name, $params, &$args)
    {
        $tableref = $name === 'datum_odeslani' ? 'dok_odeslani' : 'd';

        if (isset($params[$name]) && !empty($params[$name])) {
            $date = $params[$name];
            if (isset($params["{$name}_cas"]) && !empty($params["{$name}_cas"]))
                $date .= ' ' . $params["{$name}_cas"];

            new \DateTime($date);
            $args['where'][] = array("$tableref.$name = %d", $date);

            // neni mozne pozadovat presne datum a zaroven rozmezi data
            return;
        }

        // FIX pro sestavy
        // bohuzel formular sestav uklada do databaze vsechny parametry,
        // tedy i ty, ktere uzivatel nezada
        if (empty($params["{$name}_od"]))
            unset($params["{$name}_od"]);
        if (empty($params["{$name}_do"]))
            unset($params["{$name}_do"]);

        if (isset($params["{$name}_od"]) || isset($params["{$name}_do"])) {
            if (isset($params["{$name}_od"]) && isset($params["{$name}_do"])) {
                $date_from = $params["{$name}_od"];
                $date_to = $params["{$name}_do"];

                if (isset($params["{$name}_cas_od"]))
                    $date_from .= ' ' . $params["{$name}_cas_od"];

                if (isset($params["{$name}_cas_do"]))
                    $date_to .= ' ' . $params["{$name}_cas_do"];
                else {
                    $stamp = strtotime($params["{$name}_do"]);
                    if ($stamp)
                        $date_to = date("Y-m-d", $stamp + 86400);
                }

                $args['where'][] = array("$tableref.$name BETWEEN %t AND %t",
                    new \DateTime($date_from), new \DateTime($date_to));
            }
            else if (isset($params["{$name}_od"])) {
                $date_from = $params["{$name}_od"];

                if (isset($params["{$name}_cas_od"]))
                    $date_from .= ' ' . $params["{$name}_cas_od"];

                $args['where'][] = array("$tableref.$name >= %t", new \DateTime($date_from));
            }
            else if (isset($params["{$name}_do"])) {
                $date_to = $params["{$name}_do"];

                if (isset($params["{$name}_cas_do"]))
                    $date_to .= ' ' . $params["{$name}_cas_do"];
                else {
                    $stamp = strtotime($params["{$name}_do"]);
                    if ($stamp)
                        $date_to = date("Y-m-d", $stamp + 86400);
                }

                $args['where'][] = array("$tableref.$name < %t", new \DateTime($date_to));
            }
        }
    }

    private function joinSubjekt(&$args)
    {
        $args['leftJoin']['dok_subjekt'] = array(
            'from' => array($this->tb_dok_subjekt => 'd2sub'),
            'on' => array('d2sub.dokument_id = d.id'),
            'cols' => null
        );
        $args['leftJoin']['subjekt'] = array(
            'from' => array($this->tb_subjekt => 's'),
            'on' => array('s.id = d2sub.subjekt_id'),
            'cols' => null
        );
    }

    public function paramsFiltr($params)
    {
        $args = array();

        if (!empty($params['nazev']))
            $args['where'][] = array('d.nazev LIKE %s', '%' . $params['nazev'] . '%');

        // popis
        if (!empty($params['popis'])) {
            $args['where'][] = array('d.popis LIKE %s', '%' . $params['popis'] . '%');
        }

        // cislo jednaci
        if (!empty($params['cislo_jednaci']))
            $args['where'][] = array('d.cislo_jednaci LIKE %s', '%' . $params['cislo_jednaci'] . '%');

        // spisova znacka - nazev spisu
        if (!empty($params['spisova_znacka'])) {
            $args['leftJoin']['spis'] = array(
                'from' => array($this->tb_spis => 'spis'),
                'on' => array('spis.id = d.spis_id'),
                'cols' => null);
            $args['where'][] = array('spis.nazev LIKE %s', '%' . $params['spisova_znacka'] . '%');
        }

        // typ dokumentu
        if (isset($params['dokument_typ_id'])) {
            if ($params['dokument_typ_id'] != '0') {
                $args['where'][] = array('d.dokument_typ_id = %i', $params['dokument_typ_id']);
            }
        }

        // zpusob doruceni
        if (isset($params['zpusob_doruceni_id'])) {
            if ($params['zpusob_doruceni_id'] != '0') {
                $args['where'][] = array('d.zpusob_doruceni_id = %i', $params['zpusob_doruceni_id']);
            }
        }

        if (isset($params['typ_doruceni'])) {
            if ($params['typ_doruceni'] != '0') {

                $args['leftJoin']['zpusob_doruceni'] = array(
                    'from' => array($this->tb_epodatelna => 'epod'),
                    'on' => array('epod.dokument_id = d.id', 'epod.odchozi = 0'),
                    'cols' => null
                );
                switch ($params['typ_doruceni']) {
                    case 1: // epod
                        $args['where'][] = array('epod.id IS NOT NULL');
                        break;
                    case 2: // email
                        $args['where'][] = array("epod.typ = 'E'");
                        break;
                    case 3: // isds
                        $args['where'][] = array("epod.typ = 'I'");
                        break;
                    case 4: // mimo epod
                        $args['where'][] = array('epod.id IS NULL');
                        break;
                    default:
                        break;
                }
            }
        }

        // CJ odesilatele
        if (!empty($params['cislo_jednaci_odesilatele'])) {
            $args['where'][] = array('d.cislo_jednaci_odesilatele LIKE %s', '%' . $params['cislo_jednaci_odesilatele'] . '%');
        }

        // cislo doporuceneho dopisu
        if (!empty($params['cislo_doporuceneho_dopisu'])) {
            $args['where'][] = array('d.cislo_doporuceneho_dopisu LIKE %s', '%' . $params['cislo_doporuceneho_dopisu'] . '%');
        }
        // pouze doporucene
        if (isset($params['cislo_doporuceneho_dopisu_pouze'])) {
            if ($params['cislo_doporuceneho_dopisu_pouze']) {
                $args['leftJoin']['zpusob_odeslani'] = array(
                    'from' => array($this->tb_dok_odeslani => 'dok_odeslani'),
                    'on' => array('dok_odeslani.dokument_id=d.id'),
                    'cols' => null
                );
                $args['where'][] = array("(d.cislo_doporuceneho_dopisu <> '') OR FIND_IN_SET('2', dok_odeslani.druh_zasilky)");
            }
        }

        $what = 'vzniku';
        try {
            $this->_datum_param_to_sql('datum_vzniku', $params, $args);
            $what = 'vyřízení';
            $this->_datum_param_to_sql('datum_vyrizeni', $params, $args);
            $what = 'odeslání';
            $this->_datum_param_to_sql('datum_odeslani', $params, $args);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '\DateTime') === false)
                throw $e;

            throw new \Exception("Neplatné kritérium data/času $what dokumentu.");
        }

        // pocet listu
        if (isset($params['pocet_listu']))
            $args['where'][] = array('d.pocet_listu = %i', $params['pocet_listu']);
        if (isset($params['pocet_listu_priloh']))
            $args['where'][] = array('d.pocet_listu_priloh = %i', $params['pocet_listu_priloh']);

        // stav dokumentu
        if (!empty($params['stav_dokumentu'])) {
            if ($params['stav_dokumentu'] == DocumentWorkflow::STAV_VYRIZEN_NESPUSTENA) {
                // vyrizene - 4,5,6
                $args['where'][] = array('d.stav IN (%i,%i,%i)', DocumentWorkflow::STAV_VYRIZEN_NESPUSTENA,
                    DocumentWorkflow::STAV_VYRIZEN_SPUSTENA, DocumentWorkflow::STAV_PREDAN_DO_SPISOVNY);
            } else {
                $args['where'][] = array('d.stav = %i', $params['stav_dokumentu']);
            }
        }

        // lhuta
        if (isset($params['lhuta']))
            $args['where'][] = array('d.lhuta = %i', $params['lhuta']);

        // poznamka k dokumentu
        if (!empty($params['poznamka']))
            $args['where'][] = array('d.poznamka LIKE %s', '%' . $params['poznamka'] . '%');

        // zpusob vyrizeni
        if (!empty($params['zpusob_vyrizeni']))
            $args['where'][] = array('d.zpusob_vyrizeni_id = %i', $params['zpusob_vyrizeni']);

        // zpusob odeslani
        if (isset($params['zpusob_odeslani'])) {
            if (!empty($params['zpusob_odeslani']) || $params['zpusob_odeslani'] != '0') {

                $args['leftJoin']['zpusob_odeslani'] = array(
                    'from' => array($this->tb_dok_odeslani => 'dok_odeslani'),
                    'on' => array('dok_odeslani.dokument_id=d.id'),
                    'cols' => null
                );

                $args['where'][] = array('dok_odeslani.zpusob_odeslani_id = %i', $params['zpusob_odeslani']);
            }
        }

        // datum odeslani
        // zde se resi jen spojeni tabulek, tvorba db dotazu je o par radek nahore
        if (!empty($params['datum_odeslani']) || !empty($params['datum_odeslani_od']) || !empty($params['datum_odeslani_do'])) {

            $args['leftJoin']['zpusob_odeslani'] = array(
                'from' => array($this->tb_dok_odeslani => 'dok_odeslani'),
                'on' => array('dok_odeslani.dokument_id=d.id'),
                'cols' => null
            );
        }

        // druh zasilky
        if (isset($params['druh_zasilky'])) {
            $druh = $params['druh_zasilky'];
            if ($druh) {
                $args['leftJoin']['zpusob_odeslani'] = array(
                    'from' => array($this->tb_dok_odeslani => 'dok_odeslani'),
                    'on' => array('dok_odeslani.dokument_id=d.id'),
                    'cols' => null
                );
                foreach ($druh as $value) {
                    $args['where'][] = array('FIND_IN_SET(%i, dok_odeslani.druh_zasilky)', $value);
                }
            }
        }

        if (!empty($params['spisovy_znak_prazdny']))
            $args['where'][] = array('d.spisovy_znak_id IS NULL');
        if (!empty($params['spisovy_znak_id']))
            $args['where'][] = array('d.spisovy_znak_id = %i', $params['spisovy_znak_id']);

        if (!empty($params['ulozeni_dokumentu'])) {
            $args['where'][] = array('d.ulozeni_dokumentu LIKE %s', '%' . $params['ulozeni_dokumentu'] . '%');
        }
        if (!empty($params['poznamka_vyrizeni'])) {
            $args['where'][] = array('d.poznamka_vyrizeni LIKE %s', '%' . $params['poznamka_vyrizeni'] . '%');
        }
        if (!empty($params['skartacni_znak'])) {
            $args['where'][] = array('d.skartacni_znak = %s', $params['skartacni_znak']);
        }
        if (!empty($params['spousteci_udalost'])) {
            $args['where'][] = array('d.spousteci_udalost_id = %s', $params['spousteci_udalost']);
        }
        if (!empty($params['spousteci_udalost_id'])) {
            $args['where'][] = array('d.spousteci_udalost_id = %i', $params['spousteci_udalost_id']);
        }

        if (!empty($params['vyrizeni_pocet_listu'])) {
            $args['where'][] = array('d.vyrizeni_pocet_listu = %i', $params['vyrizeni_pocet_listu']);
        }
        if (!empty($params['vyrizeni_pocet_priloh'])) {
            $args['where'][] = array('d.vyrizeni_pocet_priloh = %i', $params['vyrizeni_pocet_priloh']);
        }
        if (!empty($params['subjekt_type'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.type = %s', $params['subjekt_type']);
        }
        if (!empty($params['subjekt_nazev'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array(
                's.nazev_subjektu LIKE %s OR', '%' . $params['subjekt_nazev'] . '%',
                's.ic LIKE %s OR', '%' . $params['subjekt_nazev'] . '%',
                "CONCAT(s.jmeno,' ',s.prijmeni) LIKE %s OR", '%' . $params['subjekt_nazev'] . '%',
                "CONCAT(s.prijmeni,' ',s.jmeno) LIKE %s", '%' . $params['subjekt_nazev'] . '%'
            );
        }
        if (!empty($params['subjekt_ic'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.ic LIKE %s', '%' . $params['ic'] . '%');
        }
        if (!empty($params['adresa_ulice'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.adresa_ulice LIKE %s', '%' . $params['adresa_ulice'] . '%');
        }
        if (!empty($params['adresa_cp'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.adresa_cp LIKE %s', '%' . $params['adresa_cp'] . '%');
        }
        if (!empty($params['adresa_co'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.adresa_co LIKE %s', '%' . $params['adresa_co'] . '%');
        }
        if (!empty($params['adresa_mesto'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.adresa_mesto LIKE %s', '%' . $params['adresa_mesto'] . '%');
        }
        if (!empty($params['adresa_psc'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.adresa_psc LIKE %s', '%' . $params['adresa_psc'] . '%');
        }
        if (!empty($params['adresa_stat'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.adresa_stat = %s', $params['adresa_stat']);
        }
        if (!empty($params['subjekt_email'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.email LIKE %s', '%' . $params['subjekt_email'] . '%');
        }
        if (!empty($params['subjekt_telefon'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.telefon LIKE %s', '%' . $params['subjekt_telefon'] . '%');
        }
        if (!empty($params['subjekt_isds'])) {
            $this->joinSubjekt($args);
            $args['where'][] = array('s.id_isds LIKE %s', '%' . $params['subjekt_isds'] . '%');
        }


        if (isset($params['prideleno_osobne']) && $params['prideleno_osobne']) {
            $user_id = self::getUser()->id;
            if (!isset($params['prideleno'])) {
                $params['prideleno'] = array();
            }
            $params['prideleno'][] = $user_id;
        }
        if (isset($params['predano_osobne']) && $params['predano_osobne']) {
            $user_id = self::getUser()->id;
            if (!isset($params['predano'])) {
                $params['predano'] = array();
            }
            $params['predano'][] = $user_id;
        }
        if (isset($params['prideleno'])) {
            if (count($params['prideleno']) > 0 && is_array($params['prideleno'])) {
                $args['where'][] = array('d.owner_user_id IN %in', $params['prideleno']);
            }
        }
        if (isset($params['predano'])) {
            if (count($params['predano']) > 0 && is_array($params['predano'])) {
                $args['where'][] = array('d.owner_user_id IN %in', $params['predano']);
            }
        }

        // Tyto parametry znamenaji prideleno / predano na MOJI org. jednotku
        if (isset($params['prideleno_na_organizacni_jednotku']) && $params['prideleno_na_organizacni_jednotku']) {
            $oj_id = OrgJednotka::dejOrgUzivatele();
            if ($oj_id) {
                if (!isset($params['prideleno_org']))
                    $params['prideleno_org'] = array();
                $params['prideleno_org'][] = $oj_id;
            }
        }
        if (isset($params['predano_na_organizacni_jednotku']) && $params['predano_na_organizacni_jednotku']) {
            $oj_id = OrgJednotka::dejOrgUzivatele();
            if ($oj_id) {
                if (!isset($params['predano_org']))
                    $params['predano_org'] = array();
                $params['predano_org'][] = $oj_id;
            }
        }

        if (isset($params['prideleno_org'])) {
            if (count($params['prideleno_org']) > 0 && is_array($params['prideleno_org'])) {
                $args['where'][] = array('d.owner_orgunit_id IN %in', $params['prideleno_org']);
            }
        }
        if (isset($params['predano_org'])) {
            if (count($params['predano_org']) > 0 && is_array($params['predano_org'])) {
                $args['where'][] = array('d.owner_orgunit_id IN %in', $params['predano_org']);
            }
        }

        return $args;
    }

    /**
     * $pouze_dokumenty_na_osobu - uživatelská volba, která je součástí filtru
     */
    public function fixedFiltr($nazev, $bez_vyrizenych, $pouze_dokumenty_na_osobu)
    {
        $user = self::getUser();
        $user_id = $user->id;
        $isVedouci = $user->isVedouci();

        $org_id = OrgJednotka::dejOrgUzivatele();
        $org_jednotka = array();
        if ($isVedouci && $org_id !== null)
            $org_jednotka = OrgJednotka::childOrg($org_id);
        else if ($org_id !== null && $user->isAllowed('Dokument', 'cist_moje_oj'))
            $org_jednotka[] = $org_id;
        $vidi_vsechny_dokumenty = self::uzivatelVidiVsechnyDokumenty();

        $vse_zahrnuje_kprevzeti = true;

        $args = array();  // priprav navratovou hodnotu

        switch ($nazev) {
            case 'vse':
                $a = [];
                if (!$vse_zahrnuje_kprevzeti)
                    break;
            case 'kprevzeti':
                $sql = "d.forward_user_id = $user_id";
                if (!$pouze_dokumenty_na_osobu && $org_id !== null && $user->isAllowed('Dokument',
                                'menit_moje_oj'))
                    $sql .= " OR d.forward_orgunit_id = $org_id";
                if ($nazev == 'vse')
                    $kprevzeti = $sql;
                else
                    $a = [$sql];
                break;

            case 'predane':
                $a = ['d.is_forwarded'];
                break;

            case 'nove':
                $a = ['d.stav = ' . DocumentWorkflow::STAV_NOVY];
                break;

            case 'kvyrizeni':
                $a = ['d.stav = ' . DocumentWorkflow::STAV_VYRIZUJE_SE];
                break;

            case 'vyrizene':
                $a = ['d.stav >= ' . DocumentWorkflow::STAV_VYRIZEN_NESPUSTENA];
                break;

            case 'zapujcene':
                $a = ['d.stav = ' . DocumentWorkflow::STAV_ZAPUJCEN];
                break;

            case 'doporucene':
                $podminka = array("(d.cislo_doporuceneho_dopisu <> '') OR FIND_IN_SET('2', dok_odeslani.druh_zasilky)"
                );
                break;

            case 'predane_k_odeslani':
                $podminka = array("dok_odeslani.stav = 1");
                break;

            case 'odeslane':
                $podminka = array("dok_odeslani.stav = 2");
                break;

            default:
                // Neexistujici filtr - zobraz prazdny seznam dokumentu
                $a = [0];
                break;
        }


        switch ($nazev) {

            case 'kprevzeti'; // specialni pripad
                break;

            case 'doporucene':
            case 'predane_k_odeslani':
            case 'odeslane':

                $args['leftJoin'] = array('zpusob_odeslani' => array(
                        'from' => array($this->tb_dok_odeslani => 'dok_odeslani'),
                        'on' => array('dok_odeslani.dokument_id = d.id'),
                        'cols' => null
                ));

                $a = [$podminka];
            // propadni dolu

            case 'predane':
            case 'nove':
            case 'kvyrizeni':
            case 'vyrizene':
            case 'zapujcene':
            case 'vse':
                if ($pouze_dokumenty_na_osobu)
                    $a[] = array('d.owner_user_id = %i', $user_id);
                else if ($vidi_vsechny_dokumenty)
                    ;
                else if (count($org_jednotka) > 1)
                    $a[] = array('d.owner_user_id = %i OR d.owner_orgunit_id IN %in',
                        $user_id, $org_jednotka);
                else if (count($org_jednotka) == 1)
                    $a[] = array('d.owner_user_id = %i OR d.owner_orgunit_id = %i',
                        $user_id, $org_jednotka[0]);
                else
                    $a[] = array('d.owner_user_id = %i', $user_id);
                break;
        }

        if ($bez_vyrizenych)
            $a[] = 'd.stav <= ' . DocumentWorkflow::STAV_VYRIZUJE_SE;

        if (isset($kprevzeti) && !empty($a)) {
            $a = $this->crunchWhereConditions($a);
            $cond1 = array_shift($a);
            $cond2 = $kprevzeti;
            $new_cond = "$cond1 OR $cond2";
            array_unshift($a, $new_cond);
            $a = [$a];
        }

        $args['where'] = $a;
        return $args;
    }

    protected function crunchWhereConditions(array $a)
    {
        $s = '';
        $params = [];
        foreach ($a as $el) {
            $s2 = is_array($el) ? $el[0] : $el;
            if ($s)
                $s .= ' AND ';
            $s .= "($s2)";
            if (is_array($el)) {
                array_shift($el);
                $params = array_merge($params, $el);
            }
        }

        array_unshift($params, $s);
        return $params;
    }

    public function spisovnaFiltr($params)
    {
        if (strpos($params, 'stav_') === 0) {
            $p = ['stav_dokumentu' => substr($params, 5)];
        } else if (strpos($params, 'skartacni_znak_') === 0) {
            $p = ['skartacni_znak' => substr($params, strlen('skartacni_znak_'))];
        } else if (strpos($params, 'zpusob_vyrizeni_') === 0) {
            $p = ['zpusob_vyrizeni' => substr($params, strlen('zpusob_vyrizeni_'))];
        } else
        // filtr "vse" nebo chyba
            $p = null;

        $ret = $p ? $this->paramsFiltr($p) : [];
        $display_borrowed = Settings::get('spisovna_display_borrowed_documents');
        if (!$display_borrowed)
            $ret['where'][] = 'd.stav != ' . DocumentWorkflow::STAV_ZAPUJCEN;

        return $ret;
    }

    public function sestavaOmezeniOrg($args)
    {
        $user = self::getUser();
        $user_id = $user->id;
        $isVedouci = $user->isVedouci();
        $vidi_vsechny_dokumenty = self::uzivatelVidiVsechnyDokumenty();

        if (!$vidi_vsechny_dokumenty) {

            $org_jednotka_id = OrgJednotka::dejOrgUzivatele();

            $org_jednotky = array();
            if ($org_jednotka_id === null)
                ;
            else if ($isVedouci)
                $org_jednotky = OrgJednotka::childOrg($org_jednotka_id);
            else if ($user->isAllowed('Dokument', 'cist_moje_oj'))
                $org_jednotky = array($org_jednotka_id);

            if (count($org_jednotky) > 1)
                $args['where'][] = array('d.owner_user_id=%i OR d.owner_orgunit_id IN %in',
                    $user_id, $org_jednotky);
            else if (count($org_jednotky) == 1)
                $args['where'][] = array('d.owner_user_id=%i OR d.owner_orgunit_id = %i',
                    $user_id, $org_jednotky[0]);
            else
                $args['where'][] = array('d.owner_user_id=%i', $user_id);
        }

        return $args;
    }

//    protected function spisovnaOmezeniOrg($args)
//    {
//        // Pracovník spisovny vidí všechny dokumenty ve spisovně
//        // Jakmile je dokument ve spisovně, přidělení dokumentu na uživatele a org. jednotku ztrácí význam
//        return $args;
//    }

    public function filtrSpisovka($args)
    {
        if (!isset($args['where']))
            $args['where'] = [];

        $args['where'][] = 'd.stav < ' . DocumentWorkflow::STAV_VE_SPISOVNE
                . ' OR d.stav = ' . DocumentWorkflow::STAV_ZAPUJCEN;
        return $args;
    }

    /** Filtr pro seznam všech dokumentů ve spisovně
     * 
     * @param array $args
     * @return array
     */
    public function filtrSpisovna($args)
    {
        if (!isset($args['where']))
            $args['where'] = [];

        $args['where'][] = array('d.stav >= ' . DocumentStates::STAV_VE_SPISOVNE);

        return $args;
    }

    /**
     * Ze seznamu nejsou odfiltrovany dokumenty, na ktere existuje zadost o zapujceni
     * @param array $args
     * @return array
     */
    public function filtrSpisovnaLzeZapujcit($args)
    {
        if (!isset($args['where']))
            $args['where'] = [];

        $args['where'][] = array('d.stav IN %in',
            DocumentStates::getSourceStates(DocumentStates::STAV_ZAPUJCEN));

        return $args;
    }

    public function filtrSpisovnaPrijem($args)
    {
        if (!isset($args['where']))
            $args['where'] = [];

        $args['where'][] = array(
            'd.stav = %i AND d.spis_id IS NULL', DocumentWorkflow::STAV_PREDAN_DO_SPISOVNY);

        return $args;
    }

    public function filtrSpisovnaKeskartaci($args)
    {
        if (!isset($args['where']))
            $args['where'] = [];

        $args['where'][] = 'd.stav = 7';

        return $args;
    }

    public function filtrSpisovnaSkartace($args)
    {
        if (!isset($args['where']))
            $args['where'] = [];

        $args['where'][] = array('d.stav = 8');

        return $args;
    }

    // $detail - nyní slouží pouze jako parametr pro nahrávání informace o přílohách
    public function getInfo($dokument_id, $details = null)
    {
        if ($details === null)
            $details = "";
        if (!is_string($details))
            throw new \InvalidArgumentException(__METHOD__ . "() - neplatný argument");
        $details = explode(',', $details);

        $sql = array(
            'from' => array($this->name => 'dok'),
            'cols' => array('*', 'id' => 'dokument_id', '%sql YEAR(dok.datum_spousteci_udalosti)+1+dok.skartacni_lhuta' => 'skartacni_rok'),
            'leftJoin' => array(
                'typ_dokumentu' => array(
                    'from' => array($this->tb_dokumenttyp => 'dtyp'),
                    'on' => array('dtyp.id = dok.dokument_typ_id'),
                    'cols' => array('nazev' => 'typ_nazev', 'popis' => 'typ_popis', 'smer' => 'typ_smer')
                ),
                'spisy' => array(
                    'from' => array($this->tb_spis => 'spis'),
                    'on' => array('spis.id = dok.spis_id'),
                    'cols' => array('id' => 'spis_id', 'nazev' => 'nazev_spisu', 'popis' => 'popis_spisu')
                ),
                'epod' => array(
                    'from' => array($this->tb_epodatelna => 'epod'),
                    'on' => array('epod.dokument_id = dok.id', 'epod.odchozi = 0'),
                    'cols' => array('identifikator', 'typ' => 'epod_typ', 'id' => 'epodatelna_id')
                ),
                'spisovy_znak' => array(
                    'from' => array($this->tb_spisovy_znak => 'spisznak'),
                    'on' => array('spisznak.id = dok.spisovy_znak_id'),
                    'cols' => array('id' => 'spisznak_id', 'nazev' => 'spisznak_nazev', 'popis' => 'spisznak_popis', 'skartacni_znak' => 'spisznak_skartacni_znak', 'skartacni_lhuta' => 'spisznak_skartacni_lhuta', 'spousteci_udalost_id' => 'spisznak_spousteci_udalost_id')
                ),
                'zpusob_doruceni' => array(
                    'from' => array($this->tb_zpusob_doruceni => 'zdoruceni'),
                    'on' => array('zdoruceni.id = dok.zpusob_doruceni_id'),
                    'cols' => array('nazev' => 'zpusob_doruceni')
                ),
                'zpusob_vyrizeni' => array(
                    'from' => array($this->tb_zpusob_vyrizeni => 'zvyrizeni'),
                    'on' => array('zvyrizeni.id = dok.zpusob_vyrizeni_id'),
                    'cols' => array('nazev' => 'zpusob_vyrizeni')
                ),
            ),
            'order_by' => array('dok.id' => 'DESC')
        );

        $sql['where'] = array(array('dok.id = %i', $dokument_id));

        $result = $this->selectComplex($sql);
        if (!count($result))
            return null;
        $dokument = $result->fetch();

        $prideleno = new \stdClass();
        $osoba = Person::fromUserId($dokument->owner_user_id);
        $prideleno->jmeno = $osoba->displayName();
        if (isset($dokument->owner_orgunit_id)) {
            $ou = new OrgUnit($dokument->owner_orgunit_id);
            $prideleno->orgjednotka = $ou;
        }
        $dokument->prideleno = $prideleno;

        $spis = null;
        if (!empty($dokument->spis_id)) {
            $spis = new \stdClass();
            $spis->id = $dokument->spis_id;
            unset($dokument->spis_id);
            $spis->nazev = $dokument->nazev_spisu;
            unset($dokument->nazev_spisu);
            $spis->popis = $dokument->popis_spisu;
            unset($dokument->popis_spisu);
        }
        if (isset($spis)) {
            $dokument->spis = $spis;
            // puvodne spisovka pitome umoznovala zaradit dokument do vice spisu najednou
            // zustava zde kvuli kompatibilite
            $dokument->spisy = array($spis->id => $spis);
        }

        $typ = new \stdClass();
        $typ->id = $dokument->dokument_typ_id;
        unset($dokument->dokument_typ_id);
        $typ->nazev = $dokument->typ_nazev;
        unset($dokument->typ_nazev);
        $typ->popis = $dokument->typ_popis;
        unset($dokument->typ_popis);
        $typ->smer = $dokument->typ_smer;
        unset($dokument->typ_smer);
        $dokument->typ_dokumentu = $typ;

        // definuj "epod_typ", pokud dokument nebyl vytvořen e-podatelnou
        if (!isset($dokument->epod_typ))
            $dokument->epod_typ = '';

        // refactoring aplikace - prilis mnoho vyskytu k nahrazeni
        $dokument->stav_dokumentu = $dokument->stav;

        // lhuta
        $dokument->lhuta_stav = 0;
        if (!empty($dokument->lhuta)) {
            $datum_vzniku = strtotime($dokument->datum_vzniku);
            $dokument->lhuta_do = $datum_vzniku + ($dokument->lhuta * 86400);
            $rozdil = $dokument->lhuta_do - time();

            if ($rozdil < 0) {
                $dokument->lhuta_stav = 2;
            } else if ($rozdil <= 432000) {
                $dokument->lhuta_stav = 1;
            }
        } else {
            $dokument->lhuta_do = 'neurčeno';
            $dokument->lhuta_stav = 0;
        }
        if ($dokument->stav >= DocumentWorkflow::STAV_VYRIZEN_NESPUSTENA)
            $dokument->lhuta_stav = 0;

        // spisovy znak
        $dokument->spisovy_znak = $dokument->spisznak_nazev;
        $dokument->spisovy_znak_popis = $dokument->spisznak_popis;
        $dokument->spisovy_znak_skart_znak = $dokument->spisznak_skartacni_znak;
        $dokument->spisovy_znak_skart_lhuta = $dokument->spisznak_skartacni_lhuta;
        $dokument->spisovy_znak_udalost = $dokument->spisznak_spousteci_udalost_id;
        $dokument->spisovy_znak_udalost_nazev = SpisovyZnak::spousteci_udalost($dokument->spisznak_spousteci_udalost_id,
                        10);
        $dokument->spisovy_znak_udalost_stav = '';
        $dokument->spisovy_znak_udalost_dtext = '';

        $spousteci_udalost = SpisovyZnak::spousteci_udalost($dokument->spousteci_udalost_id, 8);
        if (isset($spousteci_udalost->nazev)) {
            $dokument->spousteci_udalost_nazev = $spousteci_udalost->nazev;
            $dokument->spousteci_udalost_stav = $spousteci_udalost->stav;
            $dokument->spousteci_udalost_dtext = $spousteci_udalost->poznamka_k_datumu;
        } else {
            $dokument->spousteci_udalost_nazev = '';
            $dokument->spousteci_udalost_stav = 0;
            $dokument->spousteci_udalost_dtext = '';
        }

        if (strpos($dokument->cislo_jednaci, "odpoved_") !== false)
            $dokument->cislo_jednaci = "";

        // nacteni extra informaci, pokud je pozadovano
        $doc = new Document($dokument_id);
        if (in_array('subjekty', $details))
            $dokument->subjekty = $doc->getSubjects();

        if (in_array('soubory', $details)) {
            $Dokrilohy = new DokumentPrilohy();
            $dokument->prilohy = $Dokrilohy->prilohy($dokument_id);
        }

        if (in_array('odeslani', $details)) {
            $DokOdeslani = new DokumentOdeslani();
            $dokument->odeslani = $DokOdeslani->odeslanyDokument($dokument_id);
        }

        return $dokument;
    }

    public function getMax()
    {
        $result = $this->select(null, array('id' => 'DESC'), null, 1);
        $row = $result->fetch();
        return ($row) ? ($row->id + 1) : 1;
    }

    public function getMaxPoradi($cjednaci)
    {
        if (empty($cjednaci))
            return 1;

        $result = $this->select(array(array('cislo_jednaci_id=%i', $cjednaci)),
                array('poradi' => 'DESC'), null, 1);
        $row = $result->fetch();
        return ($row) ? ($row->poradi + 1) : 1;
    }

    /**
     * 
     * @param type $data
     * @return int ID
     */
    public function vytvorit($data)
    {
        // [P.L.] 2015-09-17  Tuto vetev kodu uz jsem nemel cas prepsat
        //     ale toto snad nenapacha tolik skody jako byvaly kod
        //     pro zmenu existujiciho zaznamu v databazi
        if (empty($data['cislo_jednaci_id'])) {
            $data['cislo_jednaci_id'] = null;
        } else {
            $data['cislo_jednaci_id'] = (int) $data['cislo_jednaci_id'];
        }
        if (empty($data['zpusob_doruceni_id'])) {
            $data['zpusob_doruceni_id'] = null;
        } else {
            $data['zpusob_doruceni_id'] = (int) $data['zpusob_doruceni_id'];
        }
        if (empty($data['zpusob_vyrizeni_id'])) {
            $data['zpusob_vyrizeni_id'] = null;
        } else {
            $data['zpusob_vyrizeni_id'] = (int) $data['zpusob_vyrizeni_id'];
        }
        if (empty($data['spousteci_udalost_id'])) {
            $data['spousteci_udalost_id'] = null;
        } else {
            $data['spousteci_udalost_id'] = (int) $data['spousteci_udalost_id'];
        }
        if (empty($data['spisovy_znak_id'])) {
            $data['spisovy_znak_id'] = null;
        } else {
            $data['spisovy_znak_id'] = (int) $data['spisovy_znak_id'];
        }
        if (empty($data['datum_vzniku'])) {
            $data['datum_vzniku'] = null;
        }
        if (isset($data['pocet_listu']) && $data['pocet_listu'] === '')
            $data['pocet_listu'] = null;
        if (isset($data['pocet_listu_priloh']) && $data['pocet_listu_priloh'] === '')
            $data['pocet_listu_priloh'] = null;

        if (isset($data['vyrizeni_pocet_listu'])) {
            if (empty($data['vyrizeni_pocet_listu'])) {
                $data['vyrizeni_pocet_listu'] = 0;
            } else {
                $data['vyrizeni_pocet_listu'] = (int) $data['vyrizeni_pocet_listu'];
            }
            if (empty($data['vyrizeni_pocet_priloh'])) {
                $data['vyrizeni_pocet_priloh'] = 0;
            } else {
                $data['vyrizeni_pocet_priloh'] = (int) $data['vyrizeni_pocet_priloh'];
            }
        }

        if (isset($data['skartacni_lhuta']) && empty($data['skartacni_lhuta']) && $data['skartacni_lhuta']
                != 0)
            $data['skartacni_lhuta'] = null;

        $user_id = self::getUser()->id;
        $data['date_created'] = new \DateTime();
        $data['user_created'] = $user_id;
        $data['date_modified'] = new \DateTime();
        $data['user_modified'] = $user_id;
        $data['owner_user_id'] = $user_id;
        $org_id = OrgJednotka::dejOrgUzivatele();
        if ($org_id !== null)
            $data['owner_orgunit_id'] = $org_id;

        $data['stav'] = isset($data['stav']) ? $data['stav'] : 1;
        $data['jid'] = '';

        $document = Document::create($data);

        $app_id = GlobalVariables::get('app_id');
        $document->jid = "OSS-{$app_id}-ESS-{$document->id}";
        $document->save();

        return $document->id;
    }

    public function odstranit_rozepsane()
    {
        $where = array('stav = 0',
            array('user_created = %i', self::getUser()->id)
        );

        $seznam = $this->seznamKlasicky(array('where' => $where));
        if (count($seznam) > 0) {
            foreach ($seznam as $dokument) {
                $dokument_id = $dokument->id;

                $doc = new Document($dokument_id);
                $doc->delete();
            }
        }

        return true;
    }

    /**
     * Vyhleda dokumenty se stejnym cislem jednacim (prichozi-odpoved)
     * Plati pouze pro prioraci
     * 
     * Pouziva se k detekci, zda existuje odpoved, a ke spolecne uprave 
     * obou dokumentu v zalezitosti vyrizovani.
     * 
     * @param string $cislo_jednaci cislo jednaci dokumentu
     * @param int $dokument_id id dokumentu, ktery se vylouci ze seznamu
     * @return boolean 
     */
// Presunuto do entity Document
//     
//    public function existujeSeStejnymCJ($cislo_jednaci, $dokument_id)
//    {
//        if (empty($cislo_jednaci))
//            throw new \InvalidArgumentException();
//
//        $where = [['cislo_jednaci = %s', $cislo_jednaci]];
//        $where[] = ['id != %i', $dokument_id];
//
//        $res = $this->select($where);
//        return count($res) != 0;
//    }

    public function spojitAgrs($args1, $args2)
    {
        $tmp = array();

        if (count($args1) > 0) {
            foreach ($args1 as $args1_index => $args1_value) {
                if (isset($args2[$args1_index])) {
                    $tmp[$args1_index] = array_merge($args1_value, $args2[$args1_index]);
                    unset($args2[$args1_index]);
                } else {
                    $tmp[$args1_index] = $args1_value;
                }
            }
        }

        if (count($args2) > 0) {
            foreach ($args2 as $args2_index => $args2_value) {
                $tmp[$args2_index] = $args2_value;
            }
        }

        return $tmp;
    }

    public static function zpusobVyrizeni($select)
    {
        $result = dibi::query('SELECT [id], [nazev] FROM [:PREFIX:zpusob_vyrizeni] WHERE [stav] != 0')->fetchPairs();

        switch ($select) {
            case 1:
                return $result;

            case 3:
                $result[0] = 'jakýkoli způsob vyřízení';
                ksort($result);
                return $result;

            case 4:
                $tmp = [];
                foreach ($result as $id => $nazev)
                    $tmp['zpusob_vyrizeni_' . $id] = $nazev;
                return $tmp;

            default: // neni pouzito
                return $result;
        }
    }

    /**
     * Vrátí seznam způsobů doručení, volitelně s přidanou jednou položkou
     * @param int $select  2 = při editaci metadat. 3 = při hledání
     * @return array
     */
    public static function zpusobDoruceni($select)
    {
        $result = dibi::query('SELECT [id], [nazev] FROM [:PREFIX:zpusob_doruceni]')->fetchPairs();

        if ($select == 2) {
            // definujeme výchozí hodnotu (listinná podoba) při vytvoření dokumentu
            // hodnotu "není zadán" již nelze později při editaci metadat nastavit
            unset($result[1]); // odeber položky mailem a datovou schránkou
            unset($result[2]);
        } else if ($select == 3) {
            $result[0] = 'jakýkoli způsob doručení';
        }

        ksort($result);
        return $result;
    }

    /**
     * @param int $select  Jediná použitá hodnota je 3
     * @return array
     */
    public static function zpusobOdeslani($select)
    {
        $result = dibi::query('SELECT [id], [nazev] FROM [:PREFIX:zpusob_odeslani]')->fetchPairs();

        if ($select == 3)
            $result[0] = 'jakýkoli způsob odeslání';

        ksort($result);
        return $result;
    }

    protected static function uzivatelVidiVsechnyDokumenty()
    {
        // takto to bylo ve starem systemu
        // return ACL::isInRole('admin,podatelna,skartacni_dohled'); 
        return self::getUser()->isAllowed('Dokument', 'cist_vse');
    }

}
