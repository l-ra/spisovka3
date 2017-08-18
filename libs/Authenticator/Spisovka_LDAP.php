<?php

namespace Spisovka;

use Nette;

class Spisovka_LDAP extends LDAP_Connection
{

    protected $params;
    protected $attribute_map = ['uid' => 'username',
        'samaccountname' => 'username', // Microsoft ActiveDirectory
        'sn' => 'prijmeni',
        'givenname' => 'jmeno',
        'personaltitle' => 'titul_pred', // neni ve standardnim schematu
        'mail' => 'email',
        // atributy nize nemaji pro spisovku vyznam
        'title' => 'pozice',
        'telephonenumber' => 'telefon'
    ];

    public function __construct(array $params)
    {
        parent::__construct();
        if (isset($params['attribute_map'])) {
            $this->attribute_map = $params['attribute_map'];
        }
        $this->params = Nette\Utils\ArrayHash::from($params);
        $this->connect($this->params->server, $this->params->port);
    }

    public function verify_user($username, $password)
    {
        $dn = $this->get_user_DN($username);
        try {
            $this->bind($dn, $password);
            return true;
        } catch (\Exception $e) {
            if ($e->getCode() == 49) // LDAP_INVALID_CREDENTIALS
                return false;
            throw $e;
        }
    }

    protected function get_user_DN($username)
    {
        if (empty($this->params->user_search)) {
            $dn = str_replace('%username%', $username, $this->params->user_rdn);
            $dn .= ",{$this->params->base_dn}";
            return $dn;
        }

        if ($this->params->search_dn)
            $this->bind($this->params->search_dn, $this->params->search_password);

        $search = str_replace('%username%', $username, $this->params->user_search);
        $result = $this->search($this->params->base_dn, $search, false);
        if ($result['count'] != 1)
            return false;
        
        return $result[0]['dn'];
    }

    public function get_users()
    {
        if ($this->params->search_dn)
            $this->bind($this->params->search_dn, $this->params->search_password);

        $result = $this->search($this->params->base_dn, $this->params->search_filter);

        $users = $this->parse_users($result);
        return $users;
    }

    protected function parse_users($info)
    {
        $user = array();

        for ($i = 0; $i < $info["count"]; $i++) {
            foreach ($this->attribute_map as $from => $to) {
                if (isset($info[$i][$from][0]))
                    $user[$i][$to] = $info[$i][$from][0];
                elseif (!isset($user[$i][$to]))
                    $user[$i][$to] = null;
            }
        }

        return $user ?: null;
    }

}
