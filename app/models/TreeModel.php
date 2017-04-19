<?php

abstract class TreeModel extends BaseModel
{

    const ORDERING_MAX_LENGTH = 30;

    protected $name;

    /**
     *  Slouží pro generování seznamu pro select box
     */
    protected $column_name = "nazev";

    /**
     *  Slouží pro generování pole sekvence_string
     */
    protected $column_ordering = "nazev";

    public function getInfo($id)
    {
        $row = $this->select(array(array('id = %i', $id)));
        $result = $row->fetch();
        return $result;
    }

    protected function getLevelExpression()
    {
        return "%sqlLENGTH(tb.sekvence) - LENGTH(REPLACE(tb.sekvence, '.', ''))";
    }

    /**
     * @param array $params
     * @return DibiResult
     */
    public function nacti($params = null)
    {
        $sql = array(
            'from' => array($this->name => 'tb'),
            'cols' => array('*', $this->getLevelExpression() => 'uroven'),
            'leftJoin' => array()
        );

        if (!empty($params['parent_id'])) {
            $parent_id = $params['parent_id'];
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

        $sql['order'] = array('tb.sekvence_string');

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
        return $result;
    }

    /**
     * Vytvoří uspořádaný seznam položek pro select box
     * @param int $type   0 - žádné další úpravy
     *                    1 - přidání položky '(hlavní větev)'
     *                    2 - výběr spis. znaku
     * @param array $params    slouží k filtrování položek (např. k výběru složek v tabulce "spisů")
     *                 $params['exclude_id]  slouží k vynechání položky z tímto ID ze seznamu
     *                 $params['parent_id']
     * @return array
     */
    public function selectBox($type = 0, $params = [])
    {
        $result = array();

        if ($type == 1) {
            $result[0] = '(hlavní větev)';
        } else if ($type == 2) {
            $result[0] = 'vyberte z nabídky ...';
        }

        $dibi_result = $this->nacti($params);

        $parent_sekvence = null;
        foreach ($dibi_result as $row) {
            if (isset($params['exclude_id']) && $row->id == $params['exclude_id']) {
                $parent_sekvence = $row->sekvence;
                continue;
            }

            if ($parent_sekvence && strpos($row->sekvence, $parent_sekvence) !== false)
                continue;

            $popis = "";
            if (!empty($row->popis))
                $popis = " - " . \Nette\Utils\Strings::truncate($row->popis, 90);
            $text = str_repeat("...", $row->uroven) . ' ' . $row->{$this->column_name} . $popis;

            if ($type == 2) {
                // spisové znaky
                // vrať pole HTML elementů kvůli funkci znemožnění výběru neaktivních položek
                $option = \Nette\Utils\Html::el('option')->value($row->id)->setHtml($text);
                if ($row->stav == 0)
                    $option->disabled(true);
                $result[$row->id] = $option;
            } else {
                $result[$row->id] = $text;
            }
        }

        return $result;
    }

    /**
     * @param array $data
     * @return int ID vytvoreneho zaznamu
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function vlozitH($data)
    {
        if (empty($data['parent_id']))
            $data['parent_id'] = null;

        dibi::begin();
        try {
            // vlastní $data['sekvence_string'] určuje pouze model spisového znaku
            unset($data['sekvence_string']);

            // Tato pole nemohou být prázdná, ale můžeme je naplnit až po vložení záznamu
            $data['sekvence'] = $data['sekvence_string'] = '?';

            $id = $this->insert($data);

            // Aktualizuj pomocna pole
            $parent_id = $data['parent_id'];
            $update_data = array();
            $sekvence_string = $this->generateSekvenceString($data[$this->column_ordering], $id);
            if (!$parent_id) {
                // is root node
                $update_data['sekvence'] = $id;
                $update_data['sekvence_string'] = $sekvence_string;
            } else {
                // is subnode
                $parent = $this->select([['id=%i', $parent_id]])->fetch();
                if (!$parent)
                    throw new InvalidArgumentException("TreeModel::vlozitH() - záznam ID $parent_id neexistuje.");

                $update_data['sekvence'] = $parent->sekvence . '.' . $id;
                $update_data['sekvence_string'] = $parent->sekvence_string . '#' . $sekvence_string;
            }

            $this->update($update_data, ["id = $id"]);
            dibi::commit();

            return $id;
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    public function upravitH($data, $id)
    {
        if (empty($id) || !is_numeric($id))
            throw new InvalidArgumentException(__METHOD__ . '() - neplatný parameter "id"');

        dibi::begin();
        try {
            $old_record = $this->select([['id = %i', $id]])->fetch();
            $old_sekvence_string = $this->generateSekvenceString($old_record[$this->column_ordering],
                    $id);
            $new_sekvence_string = $this->generateSekvenceString($data[$this->column_ordering],
                    $id);

            if ($data['parent_id'] == 0)
                $data['parent_id'] = null;

            $this->update($data, "id = $id");

            // 2. update tree
            $update1 = $update2 = array();
            $parent_id = (int) $data['parent_id'];
            $old_parent_id = $old_record->parent_id;
            if (empty($parent_id) && empty($old_parent_id))
                $parent_id = $old_parent_id = null;

            if (empty($parent_id) && !empty($old_parent_id)) {
                // the record is now a root node
                $old_parent = $this->select([['id = %i', $old_parent_id]])->fetch();
                if (!$old_parent) {
                    dibi::rollback();
                    throw new InvalidArgumentException(__METHOD__ . "() - záznam ID $old_parent_id neexistuje.");
                }

                $update1['sekvence'] = $id;
                $update1['sekvence_string'] = $new_sekvence_string;
                $this->update($update1, array(array('id = %i', $id)));

                // change child nodes
                $length = strlen("$old_parent->sekvence.");
                $update2['sekvence%sql'] = "SUBSTR(sekvence, $length + 1)";
                $length = strlen("$old_parent->sekvence_string#$old_sekvence_string");
                $update2['sekvence_string%sql'] = "CONCAT('$new_sekvence_string', SUBSTR(sekvence_string, $length + 1))";
                $this->update($update2, [["sekvence LIKE %s", "$old_parent->sekvence.$id.%"]]);
            } else if ($parent_id != $old_parent_id && empty($old_parent_id)) {
                // the record is no longer a root node
                $new_parent = $this->select(array(array('id = %i', $parent_id)))->fetch();
                if (!$new_parent) {
                    dibi::rollback();
                    throw new InvalidArgumentException(__METHOD__ . "() - záznam ID $parent_id neexistuje.");
                }

                $update1['sekvence'] = "$new_parent->sekvence.$id";
                $update1['sekvence_string'] = "$new_parent->sekvence_string#$new_sekvence_string";
                $this->update($update1, array(array('id = %i', $id)));

                // change child nodes
                $length = strlen($id);
                $update2['sekvence%sql'] = "CONCAT('$new_parent->sekvence.$id', SUBSTR(sekvence, $length + 1))";
                $length = strlen($old_sekvence_string);
                $update2['sekvence_string%sql'] = "CONCAT('$new_parent->sekvence_string#$new_sekvence_string', SUBSTR(sekvence_string, $length + 1))";
                $this->update($update2, [["sekvence LIKE %s", "$id.%"]]);
            } else if ($parent_id != $old_parent_id) {
                // new parent
                $old_parent = $this->select(array(array('id = %i', $old_parent_id)))->fetch();
                $new_parent = $this->select(array(array('id = %i', $parent_id)))->fetch();
                if (!$new_parent) {
                    dibi::rollback();
                    throw new InvalidArgumentException(__METHOD__ . "() - záznam ID $parent_id neexistuje.");
                }

                $update1['sekvence'] = "$new_parent->sekvence.$id";
                $update1['sekvence_string'] = "$new_parent->sekvence_string#$new_sekvence_string";
                $this->update($update1, array(array('id = %i', $id)));

                // change child nodes
                $length = strlen("$old_parent->sekvence.$id");
                $update2['sekvence%sql'] = "CONCAT('$new_parent->sekvence.$id', SUBSTR(sekvence, $length + 1))";
                $length = strlen("$old_parent->sekvence_string#$old_sekvence_string");
                $update2['sekvence_string%sql'] = "CONCAT('$new_parent->sekvence_string#$new_sekvence_string', SUBSTR(sekvence_string, $length + 1))";
                $this->update($update2, [["sekvence LIKE %s", "$old_parent->sekvence.$id.%"]]);
            } else {
                // position in tree is unchanged
                if ($old_sekvence_string != $new_sekvence_string) {
                    $parent_sekvence_string = '';
                    if ($parent_id) {
                        $parent = $this->select("id = $old_parent_id")->fetch();
                        $parent_sekvence_string = $parent->sekvence_string . '#';
                    }
                    $update1['sekvence_string'] = $parent_sekvence_string . $new_sekvence_string;
                    $this->update($update1, "id = $id");

                    // change child nodes
                    $length = strlen("$parent_sekvence_string$old_sekvence_string");
                    $update2['sekvence_string%sql'] = "CONCAT('$parent_sekvence_string$new_sekvence_string', SUBSTR(sekvence_string, $length + 1))";
                    $this->update($update2, [["sekvence LIKE %s", "$old_record->sekvence.%"]]);
                }
            }

            dibi::commit();
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

    /** Vraci: true - uspech
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
                $update = array();
                $parent_sekvence = $parent_sekvence_string = '';
                if ($info->parent_id) {
                    $parent = $this->getInfo($info->parent_id);
                    $parent_sekvence = $parent->sekvence . '.';
                    $parent_sekvence_string = $parent->sekvence_string . '#';                    
                }
                $length = strlen("{$info->sekvence}.");
                $update['sekvence%sql'] = "CONCAT('$parent_sekvence', SUBSTR(sekvence, $length + 1))";
                $length = strlen("{$info->sekvence_string}#");
                $update['sekvence_string%sql'] = "CONCAT('$parent_sekvence_string', SUBSTR(sekvence_string, $length + 1))";

                $this->update($update, [["sekvence LIKE %s", $info->sekvence . ".%"]]);

                $this->update(['parent_id' => $info->parent_id], "parent_id = $info->id");

                $this->delete([["id = %i", $id]]);
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

    /**
     * Vygeneruje pole sekvence_string pro jednu úroveň stromu.
     * @param string $string
     * @param int $id
     * @return string 
     */
    protected function generateSekvenceString($string, $id)
    {
        return mb_substr($string, 0, self::ORDERING_MAX_LENGTH) . '.' . $id;
    }

    /**
     * Opraví případné chyby v zobrazení stromu tím, že znovu vytvoří pomocné informace
     * v polích "sekvence" a "sekvence_string".
     * @return boolean
     * @throws Exception
     */
    public function rebuildIndex()
    {
        try {
            dibi::begin();
            $res = dibi::query("SELECT id, parent_id, {$this->column_ordering} AS order_by FROM {$this->name}");
            $data = $res->fetchAssoc('id');
            $processed = [];

            foreach ($data as $id => &$row) {
                if ($row->parent_id !== null)
                    continue;
                $processed[$id] = true;
                $row->sekvence = $id;
                $row->sekvence_string = $this->generateSekvenceString($row->order_by, $id);
            }

            do {
                $found_one = false;
                foreach ($data as $id => &$row) {
                    if (isset($processed[$id]))
                        continue;
                    if (!isset($processed[$row->parent_id]))
                        continue; // parent node has not been processed
                    $processed[$id] = true;
                    $found_one = true;
                    $row->sekvence = $data[$row->parent_id]->sekvence . '.' . $id;
                    $row->sekvence_string = $data[$row->parent_id]->sekvence_string . '#'
                            . $this->generateSekvenceString($row->order_by, $id);
                }
            } while ($found_one);

            /**
             *  $row je teď reference, nelze použít následující!!
             *  foreach ($data as &$row) ...
             *  foreach ($data as $row) ...
             */
            foreach ($data as $id => &$row) {
                dibi::query("UPDATE [{$this->name}] SET [sekvence] = %s, [sekvence_string] = %s WHERE [id] = $id",
                        $row->sekvence, $row->sekvence_string);
            }

            dibi::commit();

            return true;
        } catch (Exception $e) {
            dibi::rollback();
            throw $e;
        }
    }

}
