<?php

namespace Spisovka;

/**
 * Třída použitá pro komponentu číselníku v administraci.
 */
class Model extends BaseModel
{

    protected $name = '';

    public function __construct($table)
    {

        $this->name = $table;

        parent::__construct();
    }

    public function fetchAll($col = NULL, $order = NULL, $where = NULL, $offset = NULL, $limit = NULL)
    {

        if (!empty($col)) {
            return dibi::query(
                            'SELECT %n ', $col, 'FROM %n', $this->name, '%if', isset($where),
                            'WHERE %and', isset($where) ? $where : array(), '%end', '%if',
                            isset($order), 'ORDER BY %by', $order, '%end', '%if',
                            isset($limit), 'LIMIT %i %end', $limit, '%if', isset($offset),
                            'OFFSET %i %end', $offset
            );
        } else {
            return dibi::query(
                            'SELECT * FROM %n', $this->name, '%if', isset($where),
                            'WHERE %and', isset($where) ? $where : array(), '%end', '%if',
                            isset($order), 'ORDER BY %by', $order, '%end', '%if',
                            isset($limit), 'LIMIT %i %end', $limit, '%if', isset($offset),
                            'OFFSET %i %end', $offset
            );
        }
    }

    public function tableInfo($table = null)
    {
        if (empty($table)) {
            $table = $this->name;
        }
        return dibi::getConnection()->getDatabaseInfo()->getTable($table)->getColumns();
    }

}
