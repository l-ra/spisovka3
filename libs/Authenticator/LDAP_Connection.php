<?php

namespace Spisovka;

/**
 * Obecny interface k LDAP pripojeni
 * Pouze nadstavba na PHP funkce
 */
class LDAP_Connection
{

    private $ldap_conn;

    public function __construct()
    {
        if (!function_exists('ldap_connect'))
            throw new \Exception('PHP neobsahuje modul pro LDAP.');
    }

    public function __destruct()
    {
        if ($this->conn)
            ldap_close($this->conn);
    }

    public function connect($server, $port)
    {
        $this->conn = ldap_connect($server, $port);
        if (!$this->conn)
            throw new \Exception('Nelze se připojit k LDAP serveru.');

        ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->conn, LDAP_OPT_REFERRALS, 0);
    }

    protected function error()
    {
        $code = ldap_errno($this->conn);
        $message = ldap_error($this->conn);
        throw new \Exception("Chyba LDAP: $code - $message.", $code);
    }

    public function bind($rdn = null, $password = null)
    {
        if (!ldap_bind($this->conn, $rdn, $password))
            $this->error();
    }

    public function search($base_dn, $filter, $sort = 'sn')
    {
        $result = ldap_search($this->conn, $base_dn, $filter);
        if (!$result)
            $this->error();
        if ($sort)
            ldap_sort($this->conn, $result, $sort);
        return ldap_get_entries($this->conn, $result);
    }

}
