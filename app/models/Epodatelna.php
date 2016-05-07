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
