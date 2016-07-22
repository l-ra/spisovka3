<?php

class DokumentOdeslani extends BaseModel
{

    protected $name = 'dokument_odeslani';

    // Pouzito v sestavach
    public static function datumy_odeslani(array $dokument_ids)
    {
        $result = dibi::query("SELECT dokument_id, MAX(datum_odeslani) AS datum_odeslani FROM [:PREFIX:dokument_odeslani] WHERE dokument_id IN %in GROUP BY dokument_id ",
                        $dokument_ids)->fetchPairs('dokument_id', 'datum_odeslani');

        return count($result) ? $result : array();
    }

    public function odeslaneZpravy($dokument_id)
    {

        $sql = array(
            'distinct' => null,
            'from' => array($this->name => 'ds'),
            'cols' => array('dokument_id', 'subjekt_id', 'datum_odeslani', 'zpusob_odeslani_id', 'user_id', 'zprava', 'cena', 'hmotnost', 'cislo_faxu', 'ds.stav%sql' => 'stav_odeslani', 'druh_zasilky', 'ds.poznamka%sql' => 'poznamka_odeslani'),
            'leftJoin' => array(
                'subjekt' => array(
                    'from' => array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => array('*')
                ),
                'zpusob_odeslani' => array(
                    'from' => array($this->tb_zpusob_odeslani => 'odes'),
                    'on' => array('odes.id=ds.zpusob_odeslani_id'),
                    'cols' => array('nazev' => 'zpusob_odeslani_nazev')
                ),
            ),
            'order_by' => array('ds.datum_odeslani', 's.nazev_subjektu', 's.prijmeni', 's.jmeno')
        );


        if (is_array($dokument_id)) {
            $sql['where'] = array(array('dokument_id IN %in', $dokument_id));
        } else {
            $sql['where'] = array(array('dokument_id=%i', $dokument_id));
        }

        $subjekty = array();
        $result = $this->selectComplex($sql)->fetchAll();
        if (count($result) > 0) {
            foreach ($result as $subjekt_index => $subjekt) {
                $subjekty[$subjekt->dokument_id][$subjekt_index] = $subjekt;
                $subjekty[$subjekt->dokument_id][$subjekt_index]->druh_zasilky = unserialize($subjekty[$subjekt->dokument_id][$subjekt_index]->druh_zasilky);
            }

            if (!is_array($dokument_id)) {
                return $subjekty[$dokument_id];
            } else {
                return $subjekty;
            }
        } else {
            return null;
        }
    }

    public function get($id)
    {

        $sql = array(
            'distinct' => false,
            'from' => array($this->name => 'ds'),
            'cols' => array('dokument_id', 'ds.id' => 'dokodes_id', 'subjekt_id', 'datum_odeslani', 'zpusob_odeslani_id', 'user_id', 'zprava', 'cena', 'hmotnost', 'cislo_faxu', 'ds.stav%sql' => 'stav_odeslani', 'druh_zasilky', 'poznamka'),
            'leftJoin' => array(
                'zpusob_odeslani' => array(
                    'from' => array($this->tb_zpusob_odeslani => 'odes'),
                    'on' => array('odes.id=ds.zpusob_odeslani_id'),
                    'cols' => array('nazev' => 'zpusob_odeslani_nazev')
                ),
                'dokument' => array(
                    'from' => array($this->tb_dokument => 'dok'),
                    'on' => array('dok.id=ds.dokument_id'),
                    'cols' => array('nazev' => 'dok_nazev', 'jid' => 'dok_jid', 'cislo_jednaci' => 'dok_cislo_jednaci', 'poradi' => 'dok_poradi')
                ),
            ),
            'order_by' => array('ds.datum_odeslani', 's.nazev_subjektu', 's.prijmeni', 's.jmeno')
        );


        $sql['where'] = array(array('ds.id=%i', $id));

        $result = $this->selectComplex($sql)->fetch();
        if ($result) {
            $result->druh_zasilky = unserialize($result->druh_zasilky);
            return $result;
        } else {
            return null;
        }
    }

    public function kOdeslani($volba_razeni, $hledani, $filtr = null)
    {

        switch ($volba_razeni) {
            case 'datum_desc':
                $razeni = array('ds.datum_odeslani' => 'DESC', 's.nazev_subjektu', 's.prijmeni', 's.jmeno');
                break;
            case 'cj':
                $razeni = array('dok_cislo_jednaci');
                break;
            case 'cj_desc':
                $razeni = array('dok_cislo_jednaci' => 'DESC');
                break;
            default:
                $razeni = array('ds.datum_odeslani', 's.nazev_subjektu', 's.prijmeni', 's.jmeno');
        }

        $sql = array(
            'from' => array($this->name => 'ds'),
            'cols' => array('dokument_id', 'ds.id' => 'dokodes_id', 'subjekt_id', 'datum_odeslani', 'zpusob_odeslani_id', 'user_id', 'zprava', 'cena', 'hmotnost', 'cislo_faxu', 'stav', 'druh_zasilky', 'ds.poznamka' => 'poznamka_odeslani'),
            'leftJoin' => array(
                'subjekt' => array(
                    'from' => array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => array('*')
                ),
                'zpusob_odeslani' => array(
                    'from' => array($this->tb_zpusob_odeslani => 'odes'),
                    'on' => array('odes.id=ds.zpusob_odeslani_id'),
                    'cols' => array('nazev' => 'zpusob_odeslani_nazev')
                ),
                'dokument' => array(
                    'from' => array($this->tb_dokument => 'dok'),
                    'on' => array('dok.id=ds.dokument_id'),
                    'cols' => array('nazev' => 'dok_nazev', 'jid' => 'dok_jid', 'cislo_jednaci' => 'dok_cislo_jednaci', 'poradi' => 'dok_poradi')
                ),
                'o2user' => array(
                    'from' => array($this->tb_user => 'o2user'),
                    'on' => array('o2user.id = ds.user_id'),
                    'cols' => array()
                ),
                'osoba' => array(
                    'from' => array($this->tb_osoba => 'osoba'),
                    'on' => array('osoba.id=o2user.osoba_id'),
                    'cols' => array(
                        'prijmeni' => 'user_prijmeni', 'jmeno' => 'user_jmeno', 'titul_pred' => 'user_titul_pred', 'titul_za' => 'user_titul_za'
                    )
                ),
            ),
            'order' => $razeni
        );


        $sql['where'] = array(array('ds.stav=1'));

        if ($filtr !== null) {
            $sql['where'][] = array('ds.zpusob_odeslani_id=3');
        }

        if (!empty($hledani))
            $sql['where_or'] = array(
                array('CONCAT(s.nazev_subjektu,s.prijmeni) LIKE %s', '%' . $hledani . '%'),
                array('osoba.prijmeni LIKE %s', '%' . $hledani . '%'),
                array('cislo_jednaci LIKE %s', '%' . $hledani . '%'),
            );

        if (is_array($hledani))
            $sql['where'] = [['ds.id in %in', $hledani]];

        $result = $this->selectComplex($sql)->fetchAll();
        if (count($result) == 0)
            return null;

        $dokumenty = array();
        foreach ($result as $index => $row) {
            if (is_array($filtr)) {
                // filtruj podle druhu zasilky
                $a_result = null;
                $a_druh_db = unserialize($row->druh_zasilky);
                if (is_array($a_druh_db))
                    $a_result = array_intersect($a_druh_db, $filtr);
                if (empty($a_result))
                    continue;
            }

            $druh_zasilky = unserialize($row->druh_zasilky);
            $row->druh_zasilky = $druh_zasilky;

            if ($filtr === "balik") {
                if (!$druh_zasilky) {
                    // nelze detekovat - radeji vyradime
                    continue;
                } else if (!in_array(3, $druh_zasilky)) {
                    // vyradime cokoli co neni balik
                    continue;
                } else if (in_array(1, $druh_zasilky)) {
                    // je to sice balik, ale obycejny - vyradime
                    continue;
                } else if (count($druh_zasilky) < 2) {
                    // samotny balik je obycejny balik - vyradime
                    continue;
                }
            } else if ($filtr === "doporucene") {
                if (!$druh_zasilky) {
                    // nelze detekovat - radeji vyradime
                    continue;
                } else if (in_array(3, $druh_zasilky)) {
                    // baliky jsou ze hry
                    continue;
                } else if (!in_array(2, $druh_zasilky)) {
                    // vyradime cokoli co nema v sobe 2 - doporucene
                    continue;
                }
            }

            $dokumenty[$index] = $row;
        }

        // vysledek musi mit indexy 0, 1, 2, ...
        $result = array();
        foreach ($dokumenty as $d)
            $result[] = $d;
        return $result;
    }

    public function getDokumentID($id_dok_odes)
    {

        $row = $this->select(array(array('id=%i', $id_dok_odes)))->fetch();
        if ($row) {
            return $row->dokument_id;
        }
        return null;
    }

    /**
     * 
     * @param int $id
     * @return boolean
     */
    public function odeslano($id)
    {
        if (empty($id))
            return false;
        $info = $this->get($id);
        if (!$info)
            return false;
        
        $row = ['stav' => 2, 'datum_odeslani' => new DateTime()];

        $ok = $this->update($row, array(array('id=%i', $id)));
        if ($ok) {
            $Log = new LogModel();
            $Log->logDokument($info->dokument_id, LogModel::DOK_ODESLAN,
                    "Dokument odeslán " . $info->zpusob_odeslani_nazev);
        }
        
        return $ok;
    }

    public function vraceno($id)
    {

        if (empty($id)) {
            return null;
        }

        $row = array();
        $row['stav'] = 3;
        $row['datum_odeslani'] = new DateTime();

        $info = $this->get($id);
        if (!$info)
            return null;
        
        $Log = new LogModel();
        $Log->logDokument($info->dokument_id, LogModel::DOK_NEODESLAN,
                "Dokument nebyl odeslán " . $info->zpusob_odeslani_nazev);

        return $this->update($row, array(array('id=%i', $id)));
    }

    public function ulozit($row)
    {

        if (!is_array($row)) {
            return null;
        }

        //$row = array();
        //$row['dokument_id'] = $dokument_id;
        //$row['subjekt_id'] = $subjekt_id;
        //$row['zpusob_odeslani_id'] = $typ;
        //$row['epodatelna_id'] = $typ;
        //$row['datum_odeslani'] = $typ;
        if (empty($row['zpusob_odeslani_id']))
            $row['zpusob_odeslani_id'] = null;
        if (empty($row['epodatelna_id']))
            $row['epodatelna_id'] = null;
        $row['user_id'] = self::getUser()->id;
        $row['date_created'] = new DateTime();


        return $this->insert($row);
    }

}
