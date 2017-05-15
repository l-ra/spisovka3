<?php

class ImapClient
{

    /**
     * @var resource  Imap library handle 
     */
    private $stream = null;

    /**
     * @var string  Do teto znakove sady se bude provadet konverze 
     */
    private $charset = "utf-8";

    /**
     * Pripojeni k postovnimu serveru
     * @param string $mailbox pripojovaci sekvence
     * @param string $user prihlasovaci jmeno k uctu
     * @param string $password prihlasovaci heslo k uctu
     * @return boolean
     */
    public function connect($mailbox, $user, $password)
    {
        if (!function_exists('imap_open'))
            throw new InvalidArgumentException('Na tomto serveru není přítomna podpora IMAP.');

        if (function_exists('mb_convert_encoding'))
            $mailbox = mb_convert_encoding($mailbox, "UTF7-IMAP", "UTF-8");

        // Pozor, tento timeout NEFUNGUJE pro SSL spojeni (php bug)
        imap_timeout(IMAP_OPENTIMEOUT, 10);
        if ($rc = imap_open($mailbox, $user, $password, 0, 0,
                array('DISABLE_AUTHENTICATOR' => 'GSSAPI'))) {
            $this->stream = $rc;
            return true;
        }

        return false;
    }

    public function open($filename)
    {
        if (!function_exists('imap_open'))
            throw new InvalidArgumentException('Na tomto serveru není přítomna podpora IMAP.');

        // @ - nezapisuj do logu chyby, ke kterým bude docházet na určitých operačních systémech
        if ($rc = @imap_open($filename, '', '')) {
            $this->stream = $rc;
            return true;
        }

        throw new Exception("Nemohu otevřít soubor s e-mailem: $filename");
    }

    /**
     * Ukoceni spojeni s postovnim serverem
     */
    function close()
    {
        if (is_resource($this->stream)) {
            // Pokud rucne nevycistime chyby a varovani od c-client knihovny, PHP je vsechny zapise jako Notice do error logu
            $errs = imap_errors();
            unset($errs);
            imap_close($this->stream);
            $this->stream = null;
        }
    }

    function __destruct()
    {
        $this->close();
    }

    public function error()
    {
        return preg_replace('/\{(.*?)\}INBOX/', '', imap_last_error());
    }

    /**
     * Vraci pocet e-mailovych zprav ve schrance
     * @return int
     */
    function count_messages()
    {
        if (is_null($this->stream))
            return null;
        return @imap_num_msg($this->stream);
    }

    /**
     * vraci seznam e-mailovych zprav
     * @return array
     */
    function get_all_messages()
    {
        $messages = array();
        $count = $this->count_messages();
        for ($i = 1; $i <= $count; $i++) {
            $mess = $this->get_message_headers($i);
            $messages[$i] = $mess;
            unset($mess); // free memory immediately
        }

        return $messages;
    }

    public function get_message_headers($id_message)
    {
        $header_info = imap_headerinfo($this->stream, $id_message);
        $header_info = $this->decode_headers($header_info);

        return $header_info;
    }

    public function get_message_structure($id_message)
    {
        $structure = imap_fetchstructure($this->stream, $id_message);
        $this->decode_structure($structure);
        return $structure;
    }

    protected function decode_structure(&$structure)
    {
        if ($structure->ifparameters) {
            $params = [];
            foreach ($structure->parameters as $p)
                $params[strtolower($p->attribute)] = $p->value;
            $structure->parameters = $params;
        }

        if ($structure->ifdparameters) {
            $params = [];
            foreach ($structure->dparameters as $p)
                $params[strtolower($p->attribute)] = $p->value;
            $structure->dparameters = $params;
        }

        if (isset($structure->parts))
            foreach ($structure->parts as &$part)
                $this->decode_structure($part);

        return $structure;
    }

    public function get_body($id_message)
    {
        return imap_body($this->stream, $id_message);
    }

    /**
     * @param int $id_message
     * @param int|string $part_number
     * @return string 
     */
    public function fetch_body_part($id_message, $part_number)
    {
        return imap_fetchbody($this->stream, $id_message, $part_number);
    }

    /**
     * Vrátí zprávu v původním formátu (RFC2822)
     * @param int $id_message
     * @return string
     */
    public function get_raw_message($id_message)
    {
        if (is_null($this->stream))
            return null;

        /* Header */
        $mail_header = imap_fetchheader($this->stream, $id_message);

        /* Body */
        // [P.L.] zkousel jsem eliminovat pozadavek na pamet ve 2x velikosti e-mailu,
        // ale zrejme to je nemozne dosahnout bez oddeleni hlavicky a tela e-mailu.
        return $mail_header . imap_body($this->stream, $id_message);
    }

    /**
     * @param string $string
     * @return string
     */
    protected function decode_header($string)
    {
        $result = "";
        $elements = imap_mime_header_decode($string);
        foreach ($elements as $element) {
            $text = $element->text;
            if ($element->charset != "default")
                $text = @iconv($element->charset, $this->charset, $text);
            $result .= $text;
        }

        return $result;
    }

    protected function decode_headers($header_info)
    {
        $which = ['subject', 'fromaddress', 'toaddress ', 'reply_toaddress', 'ccaddress', 'bccaddress'];
        foreach ($which as $name) {
            if (isset($header_info->$name))
                $header_info->$name = $this->decode_header($header_info->$name);
        }

        $header_info->Msgno = (int) trim($header_info->Msgno);

        return $header_info;
    }

    /**
     * @param string $data
     * @param object $structure
     * @return string
     */
    public function decode_data($data, $structure)
    {
        switch ($structure->encoding) {
            case ENCBASE64: $data = imap_base64($data);
                break;
            case ENCQUOTEDPRINTABLE: $data = imap_qprint($data);
                break;
        }

        if ($structure->ifparameters && isset($structure->parameters['charset'])) {
            $charset = $structure->parameters['charset'];
            if (strcasecmp($charset, 'default') != 0)
                $data = @iconv($charset, $this->charset, $data);
        }

        return $data;
    }

    /**
     * Find message content in plain text format. Don't search attachments.
     * @param int $id_message
     * @param object $structure
     * @return string|null  Found message or null
     */
    public function find_plain_text($id_message, $structure, $section = null)
    {
        if ($structure->type == TYPETEXT && $structure->subtype == "PLAIN") {
            if ($section)
                $text = $this->fetch_body_part($id_message, $section);
            else
                $text = $this->get_body($id_message);
            $text = $this->decode_data($text, $structure);
            return $text;
        }

        if ($structure->type == TYPEMULTIPART) {
            switch ($structure->subtype) {
                case "MIXED":
                case "ALTERNATIVE":
                case "SIGNED":
                    // ve vsech pripadech se jednoduse podivej na prvni cast
                    // u Alternative je text/plain obvykle prvni
                    $section = $section ? "$section.1" : "1";
                    return $this->find_plain_text($id_message, $structure->parts[0], $section);
            }
        }

        return null;
    }

    /**
     *  Vytvori seznam priloh v e-mailu (ne vsech jeho casti )
     * @param type $structure  vysledek funkce get_message_structure
     */
    public function get_attachments($structure, $part_id = '')
    {
        $result = [];

        switch ($structure->type) {
            case TYPEMULTIPART:
                foreach ($structure->parts as $id => $part) {
                    $subpart_id = $part_id ? "$part_id." . strval($id + 1) : strval($id + 1);
                    $ret = $this->get_attachments($part, $subpart_id);
                    if ($ret)
                        $result += $ret;
                }
                break;

            default:
                if ($structure->ifdisposition && $structure->disposition == 'ATTACHMENT') {
                    $result = [$part_id => $structure];
                }
                break;
        }

        return $result;
    }

    public function is_signed($structure)
    {
        return $structure->type == TYPEMULTIPART && $structure->subtype == "SIGNED";
    }

}
