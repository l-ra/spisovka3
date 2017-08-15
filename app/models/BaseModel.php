<?php

namespace Spisovka;

use Nette;

abstract class BaseModel extends Nette\Object
{

    /** @var string object name */
    protected $name;

    /** @var bool primary key is an autoincrement column */
    protected $autoIncrement = true;

    /** names of commonly used tables
     *  pozůstatek z doby, kdy tabulky měly prefix, takže název tabulky nebyl konstantní
     */
    protected $tb_dok_odeslani = 'dokument_odeslani';
    protected $tb_dok_subjekt = 'dokument_to_subjekt';
    protected $tb_dokument = 'dokument';
    protected $tb_dokumenttyp = 'dokument_typ';
    protected $tb_epodatelna = 'epodatelna';
    protected $tb_orgjednotka = 'orgjednotka';
    protected $tb_osoba = 'osoba';
    protected $tb_spis = 'spis';
    protected $tb_spisovy_znak = 'spisovy_znak';
    protected $tb_spousteci_udalost = 'spousteci_udalost';
    protected $tb_subjekt = 'subjekt';
    protected $tb_user = 'user';
    protected $tb_zpusob_doruceni = 'zpusob_doruceni';
    protected $tb_zpusob_odeslani = 'zpusob_odeslani';
    protected $tb_zpusob_vyrizeni = 'zpusob_vyrizeni';

    /**
     * Někteří potomci volají parent::__construct()
     */
    public function __construct()
    {
        
    }

    /**
     * @return User
     */
    public static function getUser()
    {
        return Nette\Environment::getUser();
    }

    /**
     * Selects rows from the table in specified order
     * @param array $where
     * @param array $order
     * @param array $offset
     * @param array $limit
     * @return DibiResult
     */
    public function select($where = NULL, $order = NULL, $offset = NULL, $limit = NULL)
    {
        $args = array('SELECT * FROM %n', $this->name);
        if (isset($where)) {
            if (!is_array($where))
                $where = [$where];
            array_push($args, 'WHERE %and', $where);
        }
        if (isset($order))
            array_push($args, 'ORDER BY %by', $order);
        if (isset($limit))
            array_push($args, 'LIMIT %i', $limit);
        if (isset($offset))
            array_push($args, 'OFFSET %i', $offset);

        return dibi::query($args);
    }

    /**
     * Slozitejsi dotaz s moznym spojovanim tabulek
     * @param array $param
     * @return DibiResult
     */
    public function selectComplex($param)
    {
        $distinct = '';
        if (isset($param['distinct']) && $param['distinct'])
            $distinct = 'DISTINCT';
        if (isset($param['from'])) {
            if (count($param['from']) > 1) {
                // vice fromu
            } else {
                $from_key = key($param['from']);
                if (is_numeric($from_key)) {
                    $from_index[0] = $param['from'][0];
                } else {
                    $from_index[0] = $param['from'][$from_key];
                }
                $from = $param['from'];
            }
        } else {
            $from_index[0] = $this->name;
            $from = $this->name;
        }
        if (isset($param['where']))
            $where = $param['where'];
        if (isset($param['where_or']))
            $where_or = $param['where_or'];
        if (isset($param['order']))
            $order = $param['order'];
        if (isset($param['order_sql']))
            $order_sql = $param['order_sql'];
        if (isset($param['offset']))
            $offset = $param['offset'];
        if (isset($param['limit']))
            $limit = $param['limit'];
        if (isset($param['cols'])) {
            $cols = $param['cols'];
        } else {
            if (isset($from_index)) {
                $cols = array($from_index[0] . '.*');
            } else {
                $cols = array('*');
            }
        }

        if (isset($param['having']))
            $having = $param['having'];
        if (isset($param['group']))
            $group = $param['group'];

        if (isset($param['leftJoin'])) {
            $leftJoin = array();
            if (array_key_exists('from', $param['leftJoin'])) {
                // jeden join
                $from_key = key($param['leftJoin']['from']);
                $from_value = $param['leftJoin']['from'][$from_key];
                if (is_numeric($from_key)) {
                    $lj_index = $from_value;
                } else {
                    $lj_index = $from_value;
                }

                if (isset($param['leftJoin']['cols'])) {
                    foreach ($param['leftJoin']['cols'] as $ljc_key => $ljc_value) {
                        if (is_numeric($ljc_key)) {
                            $param['leftJoin']['cols'][$ljc_key] = $lj_index . '.' . $ljc_value;
                        } else {
                            unset($param['leftJoin']['cols'][$ljc_key]);
                            $param['leftJoin']['cols'][$lj_index . '.' . $ljc_key] = $ljc_value;
                        }
                    }
                    if (isset($cols)) {
                        $cols = array_merge($cols, $param['leftJoin']['cols']);
                    } else {
                        $cols = $param['leftJoin']['cols'];
                    }
                }
                if (isset($param['leftJoin']['where'])) {
                    if (isset($where)) {
                        $where = array_merge($where, $param['leftJoin']['where']);
                    } else {
                        $where = $param['leftJoin']['where'];
                    }
                }
                if (isset($param['leftJoin']['where_or'])) {
                    if (isset($where_or)) {
                        $where_or = array_merge($where, $param['leftJoin']['where_or']);
                    } else {
                        $where_or = $param['leftJoin']['where_or'];
                    }
                }

                $leftJoin[0] = array('LEFT JOIN %n', $param['leftJoin']['from'], 'ON %and', $param['leftJoin']['on']);
            } else {
                // vice joinu
                foreach ($param['leftJoin'] as $index => $lJoin) {
                    $from_key = key($lJoin['from']);
                    $from_value = $lJoin['from'][$from_key];
                    if (is_numeric($from_key)) {
                        $lj_index = $from_value;
                    } else {
                        $lj_index = $from_value;
                    }

                    if (isset($lJoin['cols'])) {
                        foreach ($lJoin['cols'] as $ljc_key => $ljc_value) {
                            if (is_numeric($ljc_key)) {
                                $lJoin['cols'][$ljc_key] = $lj_index . '.' . $ljc_value;
                            } else {
                                unset($leftJoin['cols'][$ljc_key]);
                                $lJoin['cols'][$lj_index . '.' . $ljc_key] = $ljc_value;
                            }
                        }
                        if (isset($cols)) {
                            $cols = array_merge($cols, $lJoin['cols']);
                        } else {
                            $cols = $lJoin['cols'];
                        }
                    }
                    if (isset($lJoin['where'])) {
                        if (isset($where)) {
                            $where = array_merge($where, $lJoin['where']);
                        } else {
                            $where = $lJoin['where'];
                        }
                    }
                    if (isset($param['leftJoin']['where_or'])) {
                        if (isset($where_or)) {
                            $where_or = array_merge($where, $param['leftJoin']['where_or']);
                        } else {
                            $where_or = $param['leftJoin']['where_or'];
                        }
                    }

                    $leftJoin[$index] = array('LEFT JOIN %n', $lJoin['from'], 'ON %and', $lJoin['on']);
                }
            }
        }

        if (isset($cols)) {
            $cols_string = '';
            $cols_string_a = array();
            foreach ($cols as $key => $value) {
                if (is_numeric($key)) {
                    // $value;
                    if (strpos($value, '.') !== false) {
                        list($ctab, $ccol) = explode('.', $value);
                        $cols_string_a[] = "`$ctab`.`$ccol`";
                    } else {
                        $cols_string_a[] = "`" . $from_index[0] . "`.`$value`";
                    }
                } else if (strpos($key, '%sql') !== false) {
                    $key = str_replace('%sql', '', $key);
                    $cols_string_a[] = $key . ' AS ' . $value;
                } else {
                    // $key as $value = [key2] AS alias
                    if (strpos($key, '.') !== false) {
                        list($ctab, $ccol) = explode('.', $key);
                        $cols_string_a[] = "`$ctab`.`$ccol` AS $value";
                    } else {
                        //$cols_string_a[] = "`".$from_index[0]."`.`$key` AS $value";
                    }
                }
            }
            $cols_string = implode(', ', $cols_string_a);
        } else {
            $cols_string = "`" . $from_index[0] . "`.`*`";
        }

        $query = array("SELECT $distinct %sql", $cols_string);

        array_push($query, 'FROM %n', isset($from) ? $from : $this->name);

        if (isset($leftJoin))
            foreach ($leftJoin as $lf) {
                array_push($query, '%sql', $lf);
            }

        if (isset($where_or))
            if (isset($where)) {
                $where[] = array(array('%or', $where_or));
            } else {
                array_push($query, 'WHERE %or', $where_or);
            }

        if (isset($where))
            array_push($query, 'WHERE %and', $where);
        if (isset($group))
            array_push($query, 'GROUP BY %n', $group);
        if (isset($having))
            array_push($query, 'HAVING %and', $having);
        if (isset($order))
            array_push($query, 'ORDER BY %by', $order);
        if (isset($order_sql))
            array_push($query, 'ORDER BY ' . $order_sql);
        if (isset($limit))
            array_push($query, 'LIMIT %i', $limit);
        if (isset($offset))
            array_push($query, 'OFFSET %i', $offset);

        return dibi::query($query);
    }

    /**
     * Inserts a new row
     * @param array $values to insert
     * @return
     */
    public function insert($values)
    {
        return dibi::insert($this->name, $values)
                        ->execute($this->autoIncrement ? dibi::IDENTIFIER : null);
    }

    /**
     * Updates a row
     * @param array $values to insert
     * @param array $where
     * @return boolean
     */
    public function update($values, $where)
    {
        // ochrana pred zmenou cele tabulky kvuli chybe v kodu
        if ($where === null)
            return false;

        if (!is_array($where))
            $where = array($where);
        else if (!is_array(current($where)))
            $where = array($where);


        dibi::update($this->name, $values)->where($where)
                ->execute();
        return true;
    }

    /**
     * Delete rows
     * @param array $where
     * @return
     */
    public function delete($where)
    {
        if (is_null($where))
            return null;

        if (!is_array($where)) {
            $where = array($where);
        } elseif (!is_array(current($where)))
            $where = array($where);

        return dibi::delete($this->name)->where($where)->execute();
    }

}
