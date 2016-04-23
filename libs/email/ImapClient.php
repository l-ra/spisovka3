<?php

class ImapClient
{

    private $_stream = null;
    private $_charset = "utf-8";
    protected $texts = array();
    protected $attachments = array();
    protected $signatures = array();

    /**
     * Pripojeni k postovnimu serveru
     * @param string $mailbox pripojovaci sekvence
     * @param string $user prihlasovaci jmeno k uctu
     * @param string $password prihlasovaci heslo k uctu
     * @return boolean
     */
    function connect($mailbox, $user, $password)
    {
        if (!function_exists('imap_open'))
            throw new InvalidArgumentException('Na tomto serveru není přítomna podpora IMAP.');

        if (function_exists('mb_convert_encoding'))
            $mailbox = mb_convert_encoding($mailbox, "UTF7-IMAP", "UTF-8");

        // Pozor, tento timeout NEFUNGUJE pro SSL spojeni (php bug)
        imap_timeout(IMAP_OPENTIMEOUT, 10);
        if ($rc = imap_open($mailbox, $user, $password, 0, 0,
                array('DISABLE_AUTHENTICATOR' => 'GSSAPI'))) {
            $this->_stream = $rc;
            return true;
        }

        return false;
    }

    /**
     * Ukoceni spojeni s postovnim serverem
     */
    function close()
    {
        if (is_resource($this->_stream)) {
            // Pokud rucne nevycistime chyby a varovani od c-client knihovny, PHP je vsechny zapise jako Notice do error logu
            $errs = imap_errors();
            unset($errs);
            imap_close($this->_stream);
            $this->_stream = null;
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
     * Vraci pocet emailovych zprav ve schrance
     * @return int
     */
    function count_messages()
    {
        if (is_null($this->_stream))
            return null;
        return @imap_num_msg($this->_stream);
    }

    /**
     * vraci seznam emailovych zprav
     * @return array
     */
    function get_messages()
    {
        if (is_null($this->_stream))
            return null;

        $messages = array();
        $count = $this->count_messages();
        for ($i = 1; $i <= $count; $i++) {
            $mess = $this->get_message_header($i);
            if ($mess) {
                $messages[$i] = $mess;
            }
            unset($mess); // free memory immediately
        }

        return $messages;
    }

    function get_message($id_message)
    {
        $mail = $this->get_message_header($id_message);
        if (is_null($mail))
            return null;

        /* Body */
        $this->texts = array();
        $this->attachments = array();
        $this->signatures = array();
        
        $msg_structure = imap_fetchstructure($this->_stream, $id_message);
        $mail->body = $this->parse_message_body($id_message, $msg_structure);

        $mail->source = $this->source_message($id_message);

        $mail->texts = $this->texts;
        $mail->attachments = $this->attachments;
        $mail->signature = $this->signatures;

        return $mail;
    }

    function get_message_header($id_message)
    {
        if (is_null($this->_stream))
            return null;

        /* Header */
        $mail_header = imap_headerinfo($this->_stream, $id_message);
        $mail = $this->parse_message_header($mail_header);

        return $mail;
    }

    private function source_message($id_message)
    {
        if (is_null($this->_stream))
            return null;

        /* Header */
        $mail_header = imap_fetchheader($this->_stream, $id_message);
        
        /* Body */
        // [P.L.] zkousel jsem eliminovat pozadavek na pamet ve 2x velikosti e-mailu,
        // ale zrejme to je nemozne dosahnout bez oddeleni hlavicky a tela e-mailu.
        return $mail_header . imap_body($this->_stream, $id_message);
    }

    private function parse_message_body($id_message, $structure)
    {
        switch ($structure->type) {
            case "0": $parse = $this->parse_message_body_plain($id_message, $structure);
                break;
            case "1": $parse = $this->parse_message_body_multipart($id_message, $structure);
                break;
            case "2": $parse = $this->parse_message_body_message($id_message, $structure);
                break;
            case "3": $parse = $this->parse_message_body_application($id_message, $structure);
                break;
            case "4": $parse = $this->parse_message_body_audio($id_message, $structure);
                break;
            case "5": $parse = $this->parse_message_body_image($id_message, $structure);
                break;
            case "6": $parse = $this->parse_message_body_video($id_message, $structure);
                break;
            case "7": $parse = $this->parse_message_body_other($id_message, $structure);
                break;
            default: $parse = "kuk";
                break;
        }

        $parse->source = $structure;
        return $parse;
    }

    private function parse_message_body_plain($id_message, $body)
    {
        $data = new stdClass();
        if ($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }
        $data->subtype = @$body->subtype;
        $data->size = (isset($body->bytes)) ? $body->bytes : 0;
        $data->encoding = @$body->encoding;
        $data->id_part = $id_message;

        $text = $this->decode_text($data);
        $data->text = $text;
        if (isset($param['CHARSET'])) {
            $data->charset = $param['CHARSET'];
            $data->text_convert = iconv($param['CHARSET'], $this->_charset, $text);
        } else {
            $data->charset = "default";
            $data->text_convert = $text;
        }

        $this->add_text($data);
        return $data;
    }

    private function parse_message_body_message($id_message, $body)
    {
        $data = new stdClass();
        if ($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes)) ? $body->bytes : 0;
        $data->id_part = $id_message;

        return $data;
    }

    private function parse_message_body_multipart($id_message, $body)
    {
        $data = new stdClass();
        if ($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes)) ? $body->bytes : 0;
        $data->id_part = $id_message;

        $parts_array = array();
        foreach ($body->parts as $idp => $part) {
            $subid_message = $id_message . "." . $idp;
            $parts_array[] = $this->parse_message_body($subid_message, $part);
        }
        $data->parts = $parts_array;

        return $data;
    }

    private function parse_message_body_application($id_message, $body)
    {
        $data = new stdClass();
        if ($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes)) ? $body->bytes : 0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        if ($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            //$data->filename = $this->decode_header($body->dparameters[0]->value);
        }

        $data->attachments = array();
        if ($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $data->filename = '';
        if (isset($param['NAME'])) {
            $data->filename = $this->decode_header($param['NAME']);
        }

        //if($body->subtype == "X-PKCS7-SIGNATURE") {
        if ($data->filename == "smime.p7s") {
            $data = $this->decode_signature($data);
            $this->add_signature($data);
        } else {
            $this->add_attachment($data);
        }

        return $data;
    }

    private function parse_message_body_audio($id_message, $body)
    {
        $data = new stdClass();
        if ($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes)) ? $body->bytes : 0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        if ($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            $data->filename = $this->decode_header($body->dparameters[0]->value);
        }

        $data->prilohy = array();
        if ($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $this->add_attachment($data);
        return $data;
    }

    private function parse_message_body_image($id_message, $body)
    {
        $data = new stdClass();
        if ($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes)) ? $body->bytes : 0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        $data->filename = '';
        if (isset($param['NAME'])) {
            $data->filename = $this->decode_header($param['NAME']);
        }
        if ($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            if (!isset($body->dparameters[0]->value)) {
                $id = str_replace("<", "", $body->id);
                $id = str_replace(">", "", $id);
//                $ida = explode("@", $id);
//                $data->filename = $ida[0];
            } else {
//                $data->filename = $this->decode_header($body->dparameters[0]->value);
            }
        }

        $data->prilohy = array();
        if ($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $this->add_attachment($data);
        return $data;
    }

    private function parse_message_body_video($id_message, $body)
    {
        $data = new stdClass();
        if ($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes)) ? $body->bytes : 0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        if ($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            $data->filename = $this->decode_header($body->dparameters[0]->value);
        }

        $data->prilohy = array();
        if ($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $this->add_attachment($data);
        return $data;
    }

    private function parse_message_body_other($id_message, $body)
    {
        $data = new stdClass();
        if ($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes)) ? $body->bytes : 0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        if ($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            $data->filename = $this->decode_header($body->dparameters[0]->value);
        }

        $data->prilohy = array();
        if ($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $this->add_attachment($data);
        return $data;
    }

    private function parse_message_header($message)
    {
        if (is_null($message)) {
            return null;
        }

        if (empty($message->message_id)) {
            // neobsahuje message_id - vygenerujeme vlastni
            $mid = sha1(@$message->subject . "#" . $message->udate . "#" . @$message->fromaddress . "#" . @$message->toaddress . "#" . @$message->size);
            $message->message_id = "<$mid@mail>";
        }

        $tmp = new stdClass();
        /* subject */
        if (empty($message->subject)) {
            $tmp->subject = "(bez předmětu)";
        } else {
            $tmp->subject = $this->decode_header($message->subject);
        }

        $tmp->message_id = $message->message_id;
        $tmp->id_part = trim($message->Msgno);

        /* Address */
        if (isset($message->to)) {
            $tmp->to = $this->get_address($message->to);
            $tmp->to_address = $this->decode_header($message->toaddress);
        } else {
            $tmp->to = null;
            $tmp->to_address = "";
        }

        if (isset($message->from)) {
            $tmp->from = $this->get_address($message->from);
            $tmp->from_address = $this->decode_header($message->fromaddress);
        } else {
            $tmp->from = null;
            $tmp->from_address = "";
        }

        if (isset($message->cc)) {
            $tmp->cc = $this->get_address($message->cc);
            $tmp->cc_address = $this->decode_header($message->ccaddress);
        }
        if (isset($message->bcc)) {
            $tmp->bcc = $this->get_address($message->bcc);
            $tmp->bcc_address = $this->decode_header($message->bccaddress);
        }
        if (isset($message->reply_to)) {
            $tmp->reply_to = $this->get_address($message->reply_to);
            if (isset($message->reply_toadress)) {
                $tmp->reply_to_address = $this->decode_header($message->reply_toadress);
            }
        }

        /* Date */
        $tmp->udate = $message->udate;
        $tmp->size = $message->Size;
        $tmp->source = $message;

        return $tmp;
    }

    private function get_address($address)
    {
        $address_array = array();
        if (is_null($address)) {
            return null;
        }
        foreach ($address as $item) {
            $tmp = new stdClass();
            $tmp->personal = (isset($item->personal)) ? $this->decode_header($item->personal) : "";

            if (!empty($item->host) && !empty($item->mailbox)) {
                $tmp->email = $item->mailbox . "@" . $item->host;
            } else if (empty($item->host) && !empty($item->mailbox)) {
                $tmp->email = $item->mailbox;
            } else {
                $tmp->email = "";
                //continue;
            }

            if (!empty($tmp->personal)) {
                $tmp->string = $tmp->personal . " <" . $tmp->email . ">";
            } else {
                $tmp->string = $tmp->email;
            }
            $address_array[] = $tmp;
            unset($tmp);
        }
        if (count($address_array) == 1) {
            return $address_array[0];
        } else {
            return $address_array;
        }
    }

    private function add_text($text)
    {
        $this->texts[] = $text;
    }

    private function add_attachment($attachment)
    {
        $this->attachments[] = $attachment;
    }

    private function add_signature($signature)
    {
        $this->signatures[] = $signature;
    }

    private function decode_text($part)
    {
        $parts = explode(".", $part->id_part);
        $type = "NONE";
        if (count($parts) > 1) {
            $id_part = $parts[0];
            for ($m = 1; $m < count($parts); $m++) {
                $parts[$m] = $parts[$m] + 1;
            }
            unset($parts[0]);
            $sub_part = implode(".", $parts);
            $resource = imap_fetchbody($this->_stream, $id_part, "$sub_part");
            $type = "FETCHBODY";
        } else if (count($parts) == 1) {
            $id_part = $part->id_part;
            $resource = imap_body($this->_stream, $id_part);
            $type = "BODY";
        } else {
            $resource = null;
            $type = "NULL";
        }

        //echo " $id_part - $sub_part = $type ";

        if (isset($part->encoding)) {
            //echo " = ". $part->encoding ." = ";
            switch ($part->encoding) {
                case 4: $resource = imap_qprint($resource);
                    break;
            }
        }

        return $resource;
    }

    private function decode_part($part)
    {
        $parts = explode(".", $part->id_part);
        $type = "NONE";
        if (count($parts) > 1) {
            $id_part = $parts[0];
            for ($m = 1; $m < count($parts); $m++) {
                $parts[$m] = $parts[$m] + 1;
            }
            unset($parts[0]);
            $sub_part = implode(".", $parts);
            $resource = imap_fetchbody($this->_stream, $id_part, "$sub_part");
            $type = "FETCHBODY";
        } else if (count($parts) == 1) {
            $id_part = $part->id_part;
            $resource = imap_body($this->_stream, $id_part);
            $type = "BODY";
        } else {
            $resource = null;
            $type = "NULL";
        }

        //echo " $id_part - $sub_part = $type ";

        if (isset($part->encoding)) {
            //echo " = ". $part->encoding ." = ";
            switch ($part->encoding) {
                case 0: $resource = imap_8bit($resource);
                    break;
                case 1: $resource = imap_8bit($resource);
                    break;
                case 2: $resource = imap_binary($resource);
                    break;
                case 3: $resource = imap_base64($resource);
                    break;
                case 4: $resource = imap_qprint($resource);
                    break;
                case 5: $resource = imap_base64($resource);
                    break;
            }
        }

        return $resource;
    }

    private function decode_signature($part)
    {
        $parts = explode(".", $part->id_part);
        $type = "NONE";
        if (count($parts) > 1) {
            $id_part = $parts[0];
            for ($m = 1; $m < count($parts); $m++) {
                $parts[$m] = $parts[$m] + 1;
            }
            unset($parts[0]);
            $sub_part = implode(".", $parts);
            $resource = imap_fetchbody($this->_stream, $id_part, "$sub_part");
            $type = "FETCHBODY";
        } else if (count($parts) == 1) {
            $id_part = $part->id_part;
            $resource = imap_body($this->_stream, $id_part);
            $type = "BODY";
        } else {
            $resource = null;
            $type = "NULL";
        }

        $part->signature = "-----BEGIN CERTIFICATE-----\n" . $resource . "-----END CERTIFICATE-----\n";

        return $part;
    }

    private function decode_header($string, $charset = null)
    {
        if (is_null($charset))
            $charset = $this->_charset;

        if (function_exists('imap_mime_header_decode')) {
            $parse = "";
            $elements = imap_mime_header_decode($string);
            for ($i = 0; $i < count($elements); $i++) {
                if ($elements[$i]->charset != "default") {
                    $text = @iconv($elements[$i]->charset, $charset, $elements[$i]->text);
                } else {
                    $text = $elements[$i]->text;
                }
                $parse .= $text;
            }
            return $parse;
        } else {
            return $string;
        }
    }

}
