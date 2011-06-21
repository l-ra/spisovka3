<?php //netteloader=BaseModel

abstract class BaseModel extends Object
{

    /** Table constant */
    const USER_TABLE = 'user';
    const OSOBA_TABLE = 'osoba';
    const ROLE_TABLE = 'user_role';
    const USER2ROLE_TABLE = 'user_to_role';
    const OSOBA2USER_TABLE = 'osoba_to_user';

    /** @var string object name */
    protected $name;

    /** @var string primary key name */
    protected $primary;

    /** @var bool autoincrement? */
    protected $autoIncrement = TRUE;

    protected $tb_dokument = 'dokument';
    protected $tb_file = 'file';
    protected $tb_user = 'user';
    protected $tb_osoba = 'osoba';
    protected $tb_orgjednotka = 'orgjednotka';
    protected $tb_spis = 'spis';
    protected $tb_subjekt = 'subjekt';
    protected $tb_subjekt_historie = 'subjekt';
    protected $tb_epodatelna = 'epodatelna';

    protected $tb_logaccess = 'log_access';
    protected $tb_logdokument = 'log_dokument';

    protected $tb_dokumenttyp = 'dokument_typ';

    protected $tb_workflow = 'workflow';
    protected $tb_dokspis = 'dokument_to_spis';
    protected $tb_dok_subjekt = 'dokument_to_subjekt';
    protected $tb_dok_file = 'dokument_to_file';
    
    protected $tb_spousteci_udalost = 'spousteci_udalost';
    protected $user_to_role = 'user_to_role';
    protected $osoba = 'osoba';
    protected $tb_osoba_to_user = 'osoba_to_user';
    protected $tbl_role = 'user_role';

    protected $tb_zpusob_doruceni = 'zpusob_doruceni';
    protected $tb_zpusob_vyrizeni = 'zpusob_vyrizeni';
    protected $tb_zpusob_odeslani = 'zpusob_odeslani';


    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;

        $this->tb_dokument = $prefix . $this->tb_dokument;
        $this->tb_file = $prefix . $this->tb_file;
        $this->tb_user = $prefix . $this->tb_user;
        $this->tb_osoba = $prefix . $this->tb_osoba;
        $this->tb_orgjednotka = $prefix . $this->tb_orgjednotka;
        $this->tb_workflow = $prefix . $this->tb_workflow;
        $this->tb_epodatelna = $prefix . $this->tb_epodatelna;
        $this->tb_spis = $prefix . $this->tb_spis;
        $this->tb_subjekt = $prefix . $this->tb_subjekt;
        $this->tb_subjekt_historie = $prefix . $this->tb_subjekt_historie;
        $this->tb_spousteci_udalost = $prefix . $this->tb_spousteci_udalost;

        $this->tb_dokumenttyp = $prefix . $this->tb_dokumenttyp;
        $this->tb_dokspis = $prefix . $this->tb_dokspis;
        $this->tb_dok_subjekt = $prefix . $this->tb_dok_subjekt;
        $this->tb_dok_file = $prefix . $this->tb_dok_file;
        $this->user_to_role = $prefix . $this->user_to_role;
        $this->osoba = $prefix . $this->osoba;
        $this->tb_osoba_to_user = $prefix . $this->tb_osoba_to_user;
        $this->tbl_role = $prefix . $this->tbl_role;
        $this->tb_logaccess = $prefix . $this->tb_logaccess;
        $this->tb_logdokument = $prefix . $this->tb_logdokument;

        $this->tb_zpusob_doruceni = $prefix . $this->tb_zpusob_doruceni;
        $this->tb_zpusob_vyrizeni = $prefix . $this->tb_zpusob_vyrizeni;
        $this->tb_zpusob_odeslani = $prefix . $this->tb_zpusob_odeslani;

    }


    /**
     * Selects rows from the table in specified order
     * @param array $order
     * @param array $where
     * @param array $offset
     * @param array $limit
     * @return DibiResult
     */
    public function fetchAll($order = NULL, $where = NULL, $offset = NULL, $limit = NULL)
    {
        /*dibi::test(
            'SELECT * FROM %n', $this->name,
            '%if', isset($where), 'WHERE %and', isset($where) ? $where : array(), '%end',
            '%if', isset($order), 'ORDER BY %by', $order, '%end',
            '%if', isset($limit), 'LIMIT %i %end', $limit,
            '%if', isset($offset), 'OFFSET %i %end', $offset
        );*/

        return dibi::query(
            'SELECT * FROM %n', $this->name,
            '%if', isset($where), 'WHERE %and', isset($where) ? $where : array(), '%end',
            '%if', isset($order), 'ORDER BY %by', $order, '%end',
            '%if', isset($limit), 'LIMIT %i %end', $limit,
            '%if', isset($offset), 'OFFSET %i %end', $offset
        );
    }

    public function fetchAllSpecialOrder($order = NULL, $where = NULL, $offset = NULL, $limit = NULL)
    {

        return dibi::query(
            'SELECT * FROM %n', $this->name,
            '%if', isset($where), 'WHERE %and', isset($where) ? $where : array(), '%end',
            '%if', isset($order), 'ORDER BY %sql', $order, '%end',
            '%if', isset($limit), 'LIMIT %i %end', $limit,
            '%if', isset($offset), 'OFFSET %i %end', $offset
        );
    }

    /**
     * Selects rows from the table in specified order
     * @param array $order
     * @param array $where
     * @param array $offset
     * @param array $limit
     * @return DibiResult
     */
    public function fetchAllComplet($param)
    {

        if ( isset($param['distinct']) ) $distinct = $param['distinct'];
        if ( isset($param['from']) ) {
            if ( count($param['from'])>1 ) {
                // vice fromu
            } else {
                
                $from_key = key($param['from']);
                if ( is_numeric($from_key) ) {
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
        if ( isset($param['where']) ) $where = $param['where'];
        if ( isset($param['where_or']) ) $where_or = $param['where_or'];
        if ( isset($param['order']) ) $order = $param['order'];
        if ( isset($param['offset']) ) $offset = $param['offset'];
        if ( isset($param['limit']) ) $limit = $param['limit'];
        if ( isset($param['cols']) ) {
            $cols = $param['cols'];
        } else {
            if ( isset($from_index) ) {
                $cols = array($from_index[0] .'.*');
            } else {
                $cols = array('*');
            }
            
        }

        if ( isset($param['group']) ) $group = $param['group'];

        if ( isset($param['leftJoin']) ) {
            $leftJoin = array();
            if ( array_key_exists('from', $param['leftJoin']) ) {
                // jeden join
                $from_key = key( $param['leftJoin']['from'] );
                $from_value = $param['leftJoin']['from'][$from_key];
                if ( is_numeric($from_key) ) {
                    $lj_index = $from_value;
                } else {
                    $lj_index = $from_value;
                }                            
                
                if ( isset($param['leftJoin']['cols']) ) {
                    foreach($param['leftJoin']['cols'] as $ljc_key => $ljc_value) {
                        if ( is_numeric($ljc_key) ) {
                            $param['leftJoin']['cols'][$ljc_key] = $lj_index .'.'. $ljc_value;
                        } else {
                            unset($param['leftJoin']['cols'][$ljc_key]);
                            $param['leftJoin']['cols'][$lj_index .'.'. $ljc_key] = $ljc_value;
                        }
                    }
                    if ( isset($cols) ) {
                        $cols = array_merge($cols, $param['leftJoin']['cols']);
                    } else {
                        $cols = $param['leftJoin']['cols'];
                    }
                }
                if ( isset($param['leftJoin']['where']) ) {
                    if ( isset($where) ) {
                        $where = array_merge($where, $param['leftJoin']['where']);
                    } else {
                        $where = $param['leftJoin']['where'];
                    }                    
                }
                if ( isset($param['leftJoin']['where_or']) ) {
                    if ( isset($where_or) ) {
                        $where_or = array_merge($where, $param['leftJoin']['where_or']);
                    } else {
                        $where_or = $param['leftJoin']['where_or'];
                    }
                }

                $leftJoin[0] = array('LEFT JOIN %n',$param['leftJoin']['from'],'ON %and',$param['leftJoin']['on']);
            } else {
                // vice joinu
                foreach ($param['leftJoin'] as $index => $lJoin) {
                    $from_key = key( $lJoin['from'] );
                    $from_value = $lJoin['from'][$from_key];
                    if ( is_numeric($from_key) ) {
                        $lj_index = $from_value;
                    } else {
                        $lj_index = $from_value;
                    }                            
                
                    if ( isset($lJoin['cols']) ) {
                        foreach($lJoin['cols'] as $ljc_key => $ljc_value) {
                            if ( is_numeric($ljc_key) ) {
                                $lJoin['cols'][$ljc_key] = $lj_index .'.'. $ljc_value;
                            } else {
                                unset($leftJoin['cols'][$ljc_key]);
                                $lJoin['cols'][$lj_index .'.'. $ljc_key] = $ljc_value;
                            }
                        }
                        if ( isset($cols) ) {
                            $cols = array_merge($cols, $lJoin['cols']);
                        } else {
                            $cols = $lJoin['cols'];
                        }
                    }
                    if ( isset($lJoin['where']) ) {
                        if ( isset($where) ) {
                            $where = array_merge($where, $lJoin['where']);
                        } else {
                            $where = $lJoin['where'];
                        }                    
                    }
                    if ( isset($param['leftJoin']['where_or']) ) {
                        if ( isset($where_or) ) {
                            $where_or = array_merge($where, $param['leftJoin']['where_or']);
                        } else {
                            $where_or = $param['leftJoin']['where_or'];
                        }
                    }
                    $leftJoin[$index] = array('LEFT JOIN %n',$lJoin['from'],'ON %and',$lJoin['on']);
                }
            }

        }

        if( isset($cols) ) {
            $cols_string = '';
            $cols_string_a = array();
            foreach ($cols as $key => $value) {
                if ( is_numeric($key) ) {
                    // $value;
                    if ( strpos($value,'.')!==false ) {
                        list($ctab,$ccol) = explode('.',$value);
                        $cols_string_a[] = "`$ctab`.`$ccol`";
                    } else {
                        $cols_string_a[] = "`".$from_index[0]."`.`$value`";
                    }
                } else {
                    // $key as $value = [key2] AS alias
                    if ( strpos($key,'.')!==false ) {
                        list($ctab,$ccol) = explode('.',$key);
                        $cols_string_a[] = "`$ctab`.`$ccol` AS $value";
                    } else {
                        //$cols_string_a[] = "`".$from_index[0]."`.`$key` AS $value";
                    }
                }

            }
            $cols_string = implode(', ', $cols_string_a);
        } else {
            $cols_string = "`".$from_index[0]."`.`*`";
        }

        //Debug::dump($cols);
        //Debug::dump($cols_string_a);


       $query[] = array('SELECT '.(isset($distinct) ? 'DISTINCT':'').' %sql',$cols_string);
       
        
        if ( isset($from) ) {
            array_push($query, 'FROM %n', $from);
        } else {
            array_push($query, 'FROM %n', $this->name);
        }
        
        if ( isset($leftJoin) ) {
            foreach($leftJoin as $lf) {
                array_push($query,'%sql', $lf);
            }
        }
        if ( isset($where) ){
            array_push($query, 'WHERE %and', $where);
        }
        if ( isset($where_or) ){
            array_push($query, 'WHERE %or', $where_or);
        }
        if ( isset($order) ){
            array_push($query, 'ORDER BY %by', $order);
        }
        if ( isset($group) ){
            array_push($query, 'GROUP BY %n', $group);
        }
        if ( isset($limit) ){
            array_push($query, 'LIMIT %i', $limit);
        }
        if ( isset($offset) ){
            array_push($query, 'OFFSET %i', $offset);
        }

        //Debug::dump($query);
        //dibi::test($query); exit;
        // a nyní předáme pole
        return dibi::query($query);

    }

    /**
     * Selects rows from the table in specified order
     * @param array $order
     * @param array $where
     * @param array $offset
     * @param array $limit
     * @return DibiResult
     */
    public function fetchAllSearch($order = NULL, $where_or = NULL, $offset = NULL, $limit = NULL)
    {
        return dibi::query(
            'SELECT * FROM %n', $this->name,
            '%if', isset($where), 'WHERE %or', isset($where) ? $where : array(), '%end',
            '%if', isset($order), 'ORDER BY %by', $order, '%end',
            '%if', isset($limit), 'LIMIT %i %end', $limit,
            '%if', isset($offset), 'OFFSET %i %end', $offset
        );
    }

    /**
     * Select row from the table in specified where
     * @param array $where
     * @return DibiResult
     */
    public function fetchRow($where = null)
    {

        if ( is_null($where) ) {
            return null;
        } else if ( !is_array($where) ) {
            $where = array($where);
        } else {
            if ( !is_array(current($where)) ) {
                $where = array($where);
            }
        }

        return dibi::query(
            'SELECT * FROM %n', $this->name,
            '%ex', (!empty($where) ? array('WHERE %and', $where) : NULL)
        );
    }

    public function getDataSource($table = null)
    {
        if ( !is_null($table) ) {
            //$prefix = Environment::getConfig('database')->prefix;
            return dibi::dataSource('SELECT * FROM %n', $table);    
        } else {
            return dibi::dataSource('SELECT * FROM %n', $this->name);
        }

    }

    /**
     * Inserts a new row
     * @param array $values to insert
     * @return
     */
    public function insert(array $values)
    {
        //dibi::insert($this->name, $values)
        //    ->test();

        return dibi::insert($this->name, $values)
            ->execute($this->autoIncrement ? dibi::IDENTIFIER : NULL);
    }

    public function insert_basic(array $values)
    {

        return dibi::insert($this->name, $values)
                    ->execute();
    }

    /**
     * Updates a row
     * @param array $values to insert
     * @param array $where
     * @return
     */
    public function update(array $values, $where = null)
    {

        if ( is_null($where) ) {
            return null;
        } else if ( !is_array($where) ) {
            $where = array($where);
        } else {
            if ( !is_array(current($where)) ) {
                $where = array($where);
            }
        }

        return dibi::update($this->name, $values)->where($where)
                    ->execute();
    }

    /**
     * Delete a row
     * @param array $where
     * @return
     */
    public function delete($where = null)
    {

        if ( is_null($where) ) {
            return null;
        } else if ( !is_array($where) ) {
            $where = array($where);
        } else {
            if ( !is_array(current($where)) ) {
                $where = array($where);
            }
        }

        //dibi::delete($this->name)->where($where)->test();
        return dibi::delete($this->name)->where($where)->execute();
    }

    /**
     * Delete all rows
     * @return
     */
    public function deleteAll()
    {
       return dibi::query('TRUNCATE ['.$this->name.'];');
    }

    public static function array2obj($data) {

        if ( is_object($data) ) {
            return $data;
        } else if ( is_array($data) ) {
            $tmp = new stdClass();
            foreach ($data as $key => $value) {
                $tmp->$key = $value;
            }
            return $tmp;
        } else {
            return null;
        }

    }

    public static function obj2array($data) {

        if ( is_array($data) ) {
            return $data;
        } else if ( is_object($data) ) {
            $tmp = array();
            foreach ($data as $key => $value) {
                $tmp[$key] = $value;
            }
            return $tmp;
        } else {
            return null;
        }

    }

    public function fetchPart($array, $offset = 0, $limit = null) {

        if ( count($array)>0 ) {

            if ( is_null($limit) ) {
                $user_config = Environment::getVariable('user_config');
                $limit = isset($user_config->nastaveni->pocet_polozek)?$user_config->nastaveni->pocet_polozek:20;
            }

            if ( count($array) <= $limit ) {
                // pocet je mensi nez minimalni limit
                return $array;
            } else {
                $start = ($offset==0)?0:($offset+1);
                $stop = $offset+$limit;
                $inc = 0;
                foreach ( $array as $index => $value ) {
                    if ( (!(($start <= $inc) && ($inc <= $stop)))  ) {
                        unset($array[$index]);
                    }
                    $inc++;
                }
                
                return $array;
            }
        } else {
            return $array;
        }

    }

}
