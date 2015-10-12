<?php

class TreeModel extends BaseModel
{

    protected $name;
    protected $nazev = "nazev";
    protected $nazev_sekvence = "nazev";
    protected $primary = 'id';

    /* public function  __construct($table) {

      $this->name = $table;

      parent::__construct();
      } */

    public function getInfo($id)
    {

        $row = $this->select(array(array('id=%i', $id)));
        $result = $row->fetch();
        return $result;
    }

    public function nacti($parent_id = null, $child = true, $sort_by_name = true, $params = null)
    {

        $sql = array(
            'from' => array($this->name => 'tb'),
            'cols' => array('*', "%sqlLENGTH(tb.sekvence) - LENGTH(REPLACE(tb.sekvence, '.', ''))" => 'uroven'),
            'leftJoin' => array()
        );

        if ($child) {
            if (!empty($parent_id)) {

                $sql['leftJoin'] = array(
                    'parent' => array(
                        'from' => array($this->name => 'tbp'),
                        'on' => array(array('tbp.id=%i', $parent_id))
                    )
                );
                $sql['where'] = array(
                    array("tb.sekvence LIKE CONCAT(tbp.sekvence,'.%')"),
                    array("tb.id <> %i", $parent_id)
                );
            }
        } else {
            if (!empty($parent_id)) {
                $sql['where'] = array(array('tb.parent_id=%i', $parent_id));
            } else {
                $sql['where'] = array(array('tb.parent_id IS NULL'));
            }
        }

        if (!$child) {
            $sql['order'] = array('tb.nazev');
        } else if ($sort_by_name) {
            $sql['order'] = array('tb.sekvence_string');
        } else {
            $sql['order'] = array('tb.sekvence');
        }

        if (is_array($params)) {
            if (isset($params['where'])) {
                if (isset($sql['where'])) {
                    $sql['where'] = array_merge($sql['where'], $params['where']);
                } else {
                    $sql['where'] = $params['where'];
                }
            }
            if (isset($params['order']))
                $sql['order'] = $params['order'];
            if (isset($params['leftJoin']))
                $sql['leftJoin'] = array_merge($sql['leftJoin'], $params['leftJoin']);
        }

        $result = $this->selectComplex($sql);
        if (isset($params['paginator'])) {
            return $result;
        } else {
            $rows = $result->fetchAll();
            return ($rows) ? $rows : NULL;
        }
    }

    /**
     * Vytvoří uspořádaný seznam položek pro select box
     * @param int $type   0 - 
     *                    1 -
     *                    2 - výběr spis. znaku
     *                    3 - nepoužito
     *                    10 - nepoužito
     *                    11 - vrať pole objektů
     * @param int $id
     * @param int $parent_id
     * @param array $params
     * @return array
     */
    public function selectBox($type = 0, $id = null, $parent_id = null, $params = null)
    {

        $result = array();
        $parent_sekvence = null;

        if ($type >= 10 && !empty($parent_id)) {
            $type = $type - 10;
            $null_id = $parent_id;
        } else {
            if (!empty($parent_id)) {
                $null_id = $parent_id;
            } else {
                $null_id = 0;
            }
        }

        if ($type == 1) {
            $result[$null_id] = '(hlavní větev)';
        } else if ($type == 2) {
            $result[$null_id] = 'vyberte z nabídky ...';
        } else if ($type == 3) {
            $result[$null_id] = 'všechny ...';
        }

        $rows = $this->nacti($parent_id, true, true, $params);
        if (count($rows)) {
            foreach ($rows as $row) {

                if ($row->id == $id) {
                    $parent_sekvence = $row->sekvence;
                    continue;
                }

                if ($row->id == $parent_id) {
                    $parent_sekvence = $row->sekvence;
                    continue;
                }

                if (!empty($parent_sekvence) && strpos($row->sekvence, $parent_sekvence) !== false) {
                    continue;
                }

                $popis = "";
                if (!empty($row->popis)) {
                    $popis = " - " . \Nette\Utils\Strings::truncate($row->popis, 90);
                }
                if ($type == 10) {
                    $result[$row->id] = $row->{$this->nazev} . $popis;
                } else if ($type == 11) {
                    $result[$row->id] = $row;
                } else if ($type == 2) {
                    $html = str_repeat("...", $row->uroven) . ' ' . $row->{$this->nazev} . $popis;
                    $el = \Nette\Utils\Html::el('option')->value($row->id)->setHtml($html);
                    if ($row->stav == 0)
                        $el->disabled(true); 
                    $result[$row->id] = $el;
                } else {
                    $result[$row->id] = str_repeat("...", $row->uroven) . ' ' . $row->{$this->nazev} . $popis;
                }
            }
        }

        return $result;
    }

    public function vlozitH($data)
    {

        if (empty($data['parent_id']))
            $data['parent_id'] = null;

        dibi::begin();
        try {
            $sekvence_string = isset($data['sekvence_string']) ? $data['sekvence_string'] : $data[$this->nazev_sekvence];
            unset($data['sekvence_string']);

            // 1. clasic insert
            $id = $this->insert($data);

            // 2. update tree
            $parent_id = $data['parent_id'];
            $data_tree = array();
            if (empty($parent_id) || $parent_id == 0) {
                // is root node
                $data_tree['sekvence'] = $id;
                $data_tree['sekvence_string'] = $sekvence_string . '.' . $id;
            } else {
                // is subnode
                $parent = $this->select(array(array('id=%i', $parent_id)))->fetch();
                if (!$parent) {
                    dibi::rollback();
                    throw new InvalidArgumentException("TreeModel::vlozitH() - záznam ID $parent_id neexistuje.");
                }

                $data_tree['sekvence'] = $parent->sekvence . '.' . $id;
                $data_tree['sekvence_string'] = $parent->sekvence_string . '#' . $sekvence_string . '.' . $id;
            }
            $this->update($data_tree, array(array('id=%i', $id)));

            dibi::commit();
            return $id;
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    public function upravitH($data, $id)
    {

        // 0. control param
        if (empty($id) || !is_numeric($id))
            throw new InvalidArgumentException('TreeModel::upravitH() - neplatný parameter "id"');

        // 1. clasic update
        dibi::begin();
        try {

            $sekvence_string = isset($data['sekvence_string']) ? $data['sekvence_string'] : $data[$this->nazev_sekvence];
            unset($data['sekvence_string']);

            $info = $this->select(array(array('id=%i', $id)))->fetch();

            if (isset($data['spisovy_znak_format'])) {
                $part = explode(".", $info->{$this->nazev_sekvence});
                if (count($part) > 0) {
                    foreach ($part as $pi => $pn) {
                        if (is_numeric($pn)) {
                            $part[$pi] = sprintf("%04d", $pn);
                        }
                    }
                }

                $info_nazev_sekvence = implode(".", $part);
                unset($data['spisovy_znak_format']);
            } else {
                $info_nazev_sekvence = $info->{$this->nazev_sekvence};
            }

            $parent_id_old = null;
            if (isset($data['parent_id_old']) && !empty($data['parent_id_old'])) {
                $parent_id_old = $data['parent_id_old'];
            }
            unset($data['parent_id_old']);

            if ($data['parent_id'] == 0)
                $data['parent_id'] = null;

            $this->update($data, array(array('id=%i', $id)));

            // 2. update tree
            $parent_id = $data['parent_id'];

            if (empty($parent_id) && empty($parent_id_old)) {
                $parent_id = 999;
                $parent_id_old = 999;
            }

            $data_tree = array();

            if (empty($parent_id) && !empty($parent_id_old)) {
                // is root node

                $parent_old = $this->select(array(array('id=%i', $parent_id_old)))->fetch();
                if (!$parent_old) {
                    dibi::rollback();
                    throw new InvalidArgumentException("TreeModel::upravitH() - záznam ID $parent_id_old neexistuje.");
                }

                $data_tree['sekvence'] = $id;
                $data_tree['sekvence_string'] = $sekvence_string . '.' . $id;
                $this->update($data_tree, array(array('id=%i', $id)));

                // change child nodes
                $data_node = array();
                $data_node['sekvence%sql'] = "REPLACE(sekvence,'" . $parent_old->sekvence . '.' . $id . "','" . $id . "')";
                $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'" . $parent_old->sekvence_string . "#" . $info_nazev_sekvence . "." . $id . "','" . $sekvence_string . "." . $id . "')";

                $this->update($data_node,
                        array(array("sekvence LIKE %s", $parent_old->sekvence . '.' . $id . ".%")));
            } else if ($parent_id != $parent_id_old && empty($parent_id_old)) {
                // change parent from root
                $parent_new = $this->select(array(array('id=%i', $parent_id)))->fetch();
                if (!$parent_new) {
                    dibi::rollback();
                    throw new InvalidArgumentException("TreeModel::upravitH() - záznam ID $parent_id neexistuje.");
                }

                $data_tree['sekvence'] = $parent_new->sekvence . '.' . $id;
                $data_tree['sekvence_string'] = $parent_new->sekvence_string . '#' . $sekvence_string . '.' . $id;
                $this->update($data_tree, array(array('id=%i', $id)));

                // change child nodes
                $data_node = array();
                $data_node['sekvence%sql'] = "REPLACE(sekvence,'" . $id . "','" . $parent_new->sekvence . '.' . $id . "')";
                $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'" . $info_nazev_sekvence . "." . $id . "','" . $parent_new->sekvence_string . "#" . $sekvence_string . "." . $id . "')";
                $this->update($data_node, array(array("sekvence LIKE %s", $id . ".%")));
            } else if ($parent_id != $parent_id_old) {
                // change parent
                $parent_old = $this->select(array(array('id=%i', $parent_id_old)))->fetch();
                $parent_new = $this->select(array(array('id=%i', $parent_id)))->fetch();
                if (!$parent_new) {
                    dibi::rollback();
                    throw new InvalidArgumentException("TreeModel::upravitH() - záznam ID $parent_id neexistuje.");
                }

                $data_tree['sekvence'] = $parent_new->sekvence . '.' . $id;
                $data_tree['sekvence_string'] = $parent_new->sekvence_string . '#' . $sekvence_string . '.' . $id;
                $this->update($data_tree, array(array('id=%i', $id)));

                // change child nodes
                $data_node = array();
                $data_node['sekvence%sql'] = "REPLACE(sekvence,'" . $parent_old->sekvence . '.' . $id . "','" . $parent_new->sekvence . '.' . $id . "')";
                $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'" . $parent_old->sekvence_string . "#" . $info_nazev_sekvence . "." . $id . "','" . $parent_new->sekvence_string . "#" . $sekvence_string . "." . $id . "')";
                $this->update($data_node,
                        array(array("sekvence LIKE %s", $parent_old->sekvence . '.' . $id . ".%")));
            } else {
                // nochange parent

                $data_node = array();
                $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'" . $info_nazev_sekvence . "." . $info->id . "','" . $sekvence_string . "." . $info->id . "')";
                $this->update($data_node,
                        array(array("sekvence_string LIKE %s", "%" . $info_nazev_sekvence . "." . $info->id . "%")));
            }

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    /* Vraci: true - uspech
      false - neuspech, selhala kontrola cizího klíče
      Nebo hodí výjimku

      delete_children true - podrizene uzly se smazou (pokud je to mozne)
      funguje jenom pro jednu uroven potomku!
      false - podrizene uzly se presunou pod noveho rodice

      Je volano nyni pouze z modelu spisoveho znaku.
     */

    public function odstranitH($id, $delete_children)
    {
        $info = $this->getInfo($id);
        if (!$info)
            return false;

        if ($delete_children) {

            dibi::begin();
            try {
                $this->delete(array("sekvence LIKE %s", $info->sekvence . ".%"));
                $this->delete(array("id=%i", $id));
                dibi::commit();
                return true;
            } catch (Exception $e) {
                dibi::rollback();
                if ($e->getCode() == 1451)
                    return false;
                throw $e;
            }
        } else {

            dibi::begin();
            try {
                $data_node = array();
                if (empty($info->parent_id)) {
                    // parent is root
                    $data_node['sekvence%sql'] = "REPLACE(sekvence,'" . $info->sekvence . ".','')";
                    $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'" . $info->sekvence_string . "#','')";
                } else {
                    $parent_info = $this->getInfo($info->parent_id);
                    // change child nodes
                    $data_node['sekvence%sql'] = "REPLACE(sekvence,'" . $info->sekvence . "','" . $parent_info->sekvence . "')";
                    $data_node['sekvence_string%sql'] = "REPLACE(sekvence_string,'" . $info->sekvence_string . "','" . $parent_info->sekvence_string . "')";
                }
                //Nette\Diagnostics\Debugger::dump($data_node); exit;

                $this->update($data_node,
                        array(array("sekvence LIKE %s", $info->sekvence . ".%")));

                $this->update(array('parent_id' => $info->parent_id),
                        array("parent_id = " . $info->id));

                $this->delete(array("id=%i", $id));
                dibi::commit();
                return true;
            } catch (Exception $e) {
                dibi::rollback();
                if ($e->getCode() == 1451)
                    return false;
                throw $e;
            }
        }
    }

    private function make_string($pass_len = 8)
    {
        $salt = 'abcdefghijklmnopqrstuvwxyz';
        $salt = strtoupper($salt);
        $salt_len = strlen($salt);
        /* function make_seed()
          {
          list($usec, $sec) = explode(' ', microtime());
          return (float) $sec + ((float) $usec * 100000);
          } */
        mt_srand(make_seed());
        $pass = '';
        for ($i = 0; $i < $pass_len; $i++) {
            $pass .= substr($salt, mt_rand() % $salt_len, 1);
        }
        return $pass;
    }

}
