<?php

/**
 * Description of imap
 *
 * @author Tomas Vancura
 */

class ImapClient {

    private $_server;
    private $_port;
    private $_type;
    private $_login;
    private $_password;
    private $_folder;
    private $_mailbox;
    private $_stream = null;
    private $_charset = "utf-8";

    protected $texts = array();
    protected $attachments = array();
    protected $signatures = array();
    protected $_messages = array();



    /**
     * Pripojeni k postovnimu serveru
     * @param string $mailbox pripojovaci sekvence
     * @param string $user prihlasovaci jmeno k uctu
     * @param string $password prihlasovaci heslo k uctu
     * @return resource
     */
    function connect($mailbox,$user,$password) {

        if ( function_exists('imap_open') ) {
            @imap_timeout(IMAP_OPENTIMEOUT,10);
            if ( @imap_open($mailbox,$user,$password ,OP_PROTOTYPE) ) {
                if ($this->_stream = @imap_open($mailbox,$user,$password)) {
                    return $this->_stream;
                } else {
                    //throw new InvalidArgumentException('Nelze se připojit k serveru '. $mailbox .'!');
                    return null;
                }
            } else {
                throw new InvalidArgumentException('Nelze se připojit k serveru '. $mailbox .'!');
                return null;
            }
        } else {
            throw new InvalidArgumentException('Na tomto serveru není přítomna podpora IMAP.');
            return null;
        }
    }

    /**
     * Ukoceni spojeni s postovnim serverem
     */
    function close() {
        if (!is_null($this->_stream)) {
            @imap_close($this->_stream);
        }
    }

    public function error() {
        return preg_replace('/\{(.*?)\}INBOX/', '', imap_last_error());
    }


    /**
     * Vraci pocet emailovych zprav ve schrance
     * @return int
     */
    function count_messages() {

        if(!is_null($this->_stream)) {
            return @imap_num_msg($this->_stream);
        } else {
            return null;
        }

    }

    /**
     * Vraci detailni seznam emailovych zprav
     * @return array
     */
    function get_messages() {
        if (!is_null($this->_stream)) {
            $message = array();
            $count = $this->count_messages();
            for ($i=1;$i<=$count;$i++) {
                $mess = $this->get_message($i);
                if ( $mess ) {
                    $message[$i] = $mess;
                }
                unset($mess);                
            }
            $this->add_message($message);
            return $message;
        } else {
            return null;
        }
    }

    /**
     * vraci zakladni seznam emailovych zprav
     * @return array
     */
    function get_head_messages() {
        if (!is_null($this->_stream)) {
            $message = array();
            $count = $this->count_messages();
            for ($i=1;$i<=$count;$i++) {
                $mess = $this->get_head_message($i);
                if ( $mess ) {
                    $message[$i] = $mess;
                }
                unset($mess);
            }
            //$this->add_message($message);
            return $message;
        } else {
            return null;
        }
    }

    function get_message($id_message) {
        if (!is_null($this->_stream)) {

            /* Header */
            $mail_header = @imap_header($this->_stream,$id_message);
            $mail = $this->parse_message_header($mail_header);
            
            if ( is_null($mail) ) return null;
            
            /* Body */
            $mail_body = @imap_fetchstructure($this->_stream, $id_message);
            $mail->body = $this->parse_message_body($id_message,$mail_body);

            $mail->source = $this->source_message($id_message);

            $mail->texts = $this->texts;
            $this->texts = array();
            $mail->attachments = $this->attachments;
            $this->attachments = array();
            $mail->signature = $this->signatures;
            $this->signatures = array();


            return $mail;
        } else {
            return null;
        }
    }

    function get_head_message($id_message) {
        if (!is_null($this->_stream)) {

            /* Header */
            $mail_header = @imap_header($this->_stream,$id_message);
            $mail = $this->parse_message_header($mail_header);

            if ( is_null($mail) ) return null;
            
            $mail->body = null;
            $this->texts = array();
            $this->attachments = array();
            $this->signatures = array();
            //$mail->source = $this->source_message($id_message);            
            return $mail;
        } else {
            return null;
        }
    }


    function source_message($id_message) {
        if (!is_null($this->_stream)) {

            /* Header */
            $mail_header = imap_fetchheader($this->_stream,$id_message);
            /* Body */
            $mail_body = imap_body($this->_stream, $id_message);
            $mail = $mail_header ."". $mail_body;
            return $mail;
        } else {
            return null;
        }
    }

    private function parse_message_body($id_message,$body) {

        switch ($body->type) {
            case "0": $parse = $this->parse_message_body_plain($id_message,$body); break;
            case "1": $parse = $this->parse_message_body_multipart($id_message,$body); break;
            case "2": $parse = $this->parse_message_body_message($id_message,$body); break;
            case "3": $parse = $this->parse_message_body_application($id_message,$body); break;
            case "4": $parse = $this->parse_message_body_audio($id_message,$body); break;
            case "5": $parse = $this->parse_message_body_image($id_message,$body); break;
            case "6": $parse = $this->parse_message_body_video($id_message,$body); break;
            case "7": $parse = $this->parse_message_body_other($id_message,$body); break;
            default: $parse = "kuk"; break;
        }
        $parse->source = $body;
        return $parse;

    }

    private function parse_message_body_plain($id_message,$body) {

        $data = new stdClass();
        if($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }
        $data->subtype = @$body->subtype;
        $data->size = (isset($body->bytes))?$body->bytes:0;
        $data->encoding = @$body->encoding;
        $data->id_part = $id_message;

        $text = $this->decode_text($data);
        $data->text = $text;
        if(isset($param['CHARSET'])) {
            $data->charset = $param['CHARSET'];
            $data->text_convert = iconv($param['CHARSET'], $this->_charset,$text);
        } else {
            $data->charset = "default";
            $data->text_convert = $text;
        }
        
        $this->add_text($data);
        return $data;
    }

    private function parse_message_body_message($id_message,$body) {

        $data = new stdClass();
        if($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes))?$body->bytes:0;
        $data->id_part = $id_message;

        $parts = explode(".",$id_message);
        if(count($parts)>1) {
            $id_part = $parts[0];
            for ($m=1;$m<count($parts);$m++) {
                $parts[$m] = $parts[$m] + 1;
            }
            unset($parts[0]);
            $sub_part = implode(".", $parts);
            //echo "$id_message = $id_part - $sub_part \n";
            //$text = imap_fetchbody($this->_stream,$id_part,"$sub_part");
        } else if (count($parts)==1) {
            //$text = imap_body($this->_stream,$id_message);
        } else {
            //$text = "";
        }
        /*
        $data->text = $text;
        if(isset($param['CHARSET'])) {
            $data->charset = $param['CHARSET'];
            $data->text_convert = iconv($param['CHARSET'], $this->_charset,$text);
        } else {
            $data->charset = "default";
            $data->text_convert = $text;
        }
        */
        //$this->_texts[] = $data;
        return $data;
    }

    private function parse_message_body_multipart($id_message,$body) {
        
        $data = new stdClass();
        if($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }
        
        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes))?$body->bytes:0;
        $data->id_part = $id_message;

        $parts_array = array();
        foreach ($body->parts as $idp => $part) {
            $subid_message = $id_message .".".$idp;
            $parts_array[] = $this->parse_message_body($subid_message, $part);
        }
        $data->parts = $parts_array;

        return $data;
    }

    private function parse_message_body_application($id_message,$body) {
        $data = new stdClass();
        if($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }
        
        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes))?$body->bytes:0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        if($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            //$data->filename = $this->decode_header($body->dparameters[0]->value);
        }

        $data->attachments = array();
        if($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $data->filename = '';
        if ( isset($param['NAME']) ) {
            $data->filename = $this->decode_header($param['NAME']);
        }

        //if($body->subtype == "X-PKCS7-SIGNATURE") {
        if($data->filename == "smime.p7s") {
            $data = $this->decode_signature($data);
            $this->add_signature($data);
        } else {
            $this->add_attachment($data);
        }


        
        return $data;
    }

    private function parse_message_body_audio($id_message,$body) {
        $data = new stdClass();
        if($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes))?$body->bytes:0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        if($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            $data->filename = $this->decode_header($body->dparameters[0]->value);
        }

        $data->prilohy = array();
        if($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $this->add_attachment($data);
        return $data;

    }

    private function parse_message_body_image($id_message,$body) {
        $data = new stdClass();
        if($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes))?$body->bytes:0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        $data->filename = '';
        if ( isset($param['NAME']) ) {
            $data->filename = $this->decode_header($param['NAME']);
        }
        if($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            if(!isset($body->dparameters[0]->value)) {
                $id = str_replace("<","",$body->id);
                $id = str_replace(">","",$id);
                $ida = explode("@",$id);
                //$data->filename = $ida[0];
            } else {
                //$data->filename = $this->decode_header($body->dparameters[0]->value);
            }

        }

        $data->prilohy = array();
        if($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $this->add_attachment($data);
        return $data;

    }

    private function parse_message_body_video($id_message,$body) {
        $data = new stdClass();
        if($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes))?$body->bytes:0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        if($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            $data->filename = $this->decode_header($body->dparameters[0]->value);
        }

        $data->prilohy = array();
        if($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $this->add_attachment($data);
        return $data;

    }

    private function parse_message_body_other($id_message,$body) {
        $data = new stdClass();
        if($body->ifparameters == 1) {
            $param = array();
            foreach ($body->parameters as $p) {
                $param[$p->attribute] = $p->value;
            }
            $data->param = $param;
        }

        //$data->source = $body;
        $data->subtype = $body->subtype;
        $data->size = (isset($body->bytes))?$body->bytes:0;
        $data->id_part = $id_message;
        $data->encoding = $body->encoding;

        if($body->ifdisposition == 1) {
            $data->disposition = $body->disposition;
            $data->filename = $this->decode_header($body->dparameters[0]->value);
        }

        $data->prilohy = array();
        if($body->ifdparameters == 1) {
            foreach ($body->dparameters as $dpar) {
                $data->attachments[$dpar->attribute] = $this->decode_header($dpar->value);
            }
        }

        $this->add_attachment($data);
        return $data;

    }

    private function parse_message_header($message) {

        if ( is_null($message) ) { return null; }
        //if ( empty($message->toaddress) ) { return null; }

        if ( empty($message->message_id) ) {
            // neobsahuje message_id - vygenerujeme vlastni
            $mid = sha1(@$message->subject ."#". $message->udate ."#". @$message->fromaddress ."#". @$message->toaddress ."#". @$message->size);
            $message->message_id = "<$mid@mail>";
        }        
        
        $tmp = new stdClass();
        /* subject */
        if ( empty($message->subject) ) {
            $tmp->subject = "(bez předmětu)";
        } else {
            $tmp->subject = $this->decode_header($message->subject);
        }        

        $tmp->message_id = $message->message_id;
        $tmp->id_part = trim($message->Msgno);

        /* Address */
        if(isset($message->to)) {
            $tmp->to = $this->get_address($message->to);
            $tmp->to_address = $this->decode_header($message->toaddress);
        } else {
            $tmp->to = null;
            $tmp->to_address = "";
        }

        if(isset($message->from)) {
            $tmp->from = $this->get_address($message->from);
            $tmp->from_address = $this->decode_header($message->fromaddress);
        } else {
            $tmp->from = null;
            $tmp->from_address = "";
        }

        if(isset($message->cc)) {
            $tmp->cc = $this->get_address($message->cc);
            $tmp->cc_address = $this->decode_header($message->ccaddress);
        }
        if(isset($message->bcc)) {
            $tmp->bcc = $this->get_address($message->bcc);
            $tmp->bcc_address = $this->decode_header($message->bccaddress);
        }
        if(isset($message->reply_to)) {
            $tmp->reply_to = $this->get_address($message->reply_to);
            if(isset($message->reply_toadress)) {
                $tmp->reply_to_address = $this->decode_header($message->reply_toadress);
            }
        }

        /* Date */
        $tmp->udate = $message->udate;
        $tmp->size = $message->Size;
        $tmp->source = $message;


        return $tmp;
        
    }

    private function get_address($address) {
        $address_array = array();
        if(is_null($address)) {
            return null;
        }
        foreach ($address as $item) {
            $tmp = new stdClass();
            $tmp->personal = (isset($item->personal))?$this->decode_header($item->personal):"";
            
            if (!empty($item->host) && !empty($item->mailbox)) {
                $tmp->email = $item->mailbox ."@". $item->host;
            } else if ( empty($item->host) && !empty($item->mailbox) ) {
                $tmp->email = $item->mailbox;
            } else {
                $tmp->email = "";
                //continue;
            }
            
            if(!empty($tmp->personal)) {
                $tmp->string = $tmp->personal ." <". $tmp->email .">";
            } else {
                $tmp->string = $tmp->email;
            }
            $address_array[] = $tmp;
            unset($tmp);
        }
        if(count($address_array)==1) {
            return $address_array[0];
        } else {
            return $address_array;
        }
    }

    private function generate_mailbox_string() {
        
    }

    private function add_text($text) {
        $this->texts[] = $text;
    }

    private function add_attachment($attachment) {
        $this->attachments[] = $attachment;
    }

    private function add_signature($signature) {
        $this->signatures[] = $signature;
    }

    private function add_message($message) {
        $this->_messages[] = $message;
    }

    public function decode_text($part) {

        //echo "\nPPP: ". $part->id_part ." = ";

        $parts = explode(".",$part->id_part);
        $type="NONE";
        if(count($parts)>1) {
            $id_part = $parts[0];
            for ($m=1;$m<count($parts);$m++) {
                $parts[$m] = $parts[$m] + 1;
            }
            unset($parts[0]);
            $sub_part = implode(".", $parts);
            $resource = imap_fetchbody($this->_stream,$id_part,"$sub_part");
            $type = "FETCHBODY";
        } else if (count($parts)==1) {
            $id_part = $part->id_part;
            $resource = imap_body($this->_stream,$id_part);
            $type = "BODY";
        } else {
            $resource = null;
            $type = "NULL";
        }

        //echo " $id_part - $sub_part = $type ";

        if(isset($part->encoding)) {
            //echo " = ". $part->encoding ." = ";
            switch ($part->encoding) {
                case 4: $resource = imap_qprint($resource) ; break;
            }
        }

        return $resource;


    }

    public function decode_part($part) {

        //echo "\nPPP: ". $part->id_part ." = ";

        $parts = explode(".",$part->id_part);
        $type="NONE";
        if(count($parts)>1) {
            $id_part = $parts[0];
            for ($m=1;$m<count($parts);$m++) {
                $parts[$m] = $parts[$m] + 1;
            }
            unset($parts[0]);
            $sub_part = implode(".", $parts);
            $resource = imap_fetchbody($this->_stream,$id_part,"$sub_part");
            $type = "FETCHBODY";
        } else if (count($parts)==1) {
            $id_part = $part->id_part;
            $resource = imap_body($this->_stream,$id_part);
            $type = "BODY";
        } else {
            $resource = null;
            $type = "NULL";
        }

        //echo " $id_part - $sub_part = $type ";

        if(isset($part->encoding)) {
            //echo " = ". $part->encoding ." = ";
            switch ($part->encoding) {
                case 0: $resource = imap_8bit($resource) ; break;
                case 1: $resource = imap_8bit($resource) ; break;
                case 2: $resource = imap_binary($resource) ; break;
                case 3: $resource = imap_base64($resource) ; break;
                case 4: $resource = imap_qprint($resource) ; break;
                case 5: $resource = imap_base64($resource) ; break;
            }
        }

        return $resource;


    }

    public function decode_signature($part) {

        $parts = explode(".",$part->id_part);
        $type="NONE";
        if(count($parts)>1) {
            $id_part = $parts[0];
            for ($m=1;$m<count($parts);$m++) {
                $parts[$m] = $parts[$m] + 1;
            }
            unset($parts[0]);
            $sub_part = implode(".", $parts);
            $resource = imap_fetchbody($this->_stream,$id_part,"$sub_part");
            $type = "FETCHBODY";
        } else if (count($parts)==1) {
            $id_part = $part->id_part;
            $resource = imap_body($this->_stream,$id_part);
            $type = "BODY";
        } else {
            $resource = null;
            $type = "NULL";
        }

        $part->signature = "-----BEGIN CERTIFICATE-----\n".$resource."-----END CERTIFICATE-----\n";

        return $part;
    }

    private function decode_header($string,$charset=null) {

        if(is_null($charset)) $charset = $this->_charset;

        if (function_exists('imap_mime_header_decode')) {
            $parse = "";
            $elements = imap_mime_header_decode($string);
            for ($i=0; $i<count($elements); $i++) {
                if($elements[$i]->charset != "default") {
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

    public function save_attachment($part,$to) {

        if(file_exists($to)) {
            if( is_writable($to) ) {

                $data = $this->decode_part($part);
                $file = $to ."/". $part->filename;
                $fp = fopen($file,"wb");
                    fwrite($fp,$data);
                fclose($fp);
                return true;

            } else {
                return -1;
            }
        } else {
            return false;
        }

    }

    public function save_message($id_message,$save_to="",$filename=null) {
        if (!is_null($this->_stream)) {

            /* Header */
            $mail_header = imap_fetchheader($this->_stream,$id_message);
            /* Body */
            $mail_body = imap_body($this->_stream, $id_message);

            $mail = $mail_header ."". $mail_body;

            if(is_null($filename)) {
                $mail_header = imap_header($this->_stream,$id_message);
                $filename = $mail_header->message_id .".mbs";
                $filename = str_replace("<", "", $filename);
                $filename = str_replace(">", "", $filename);
            }
            $file = $save_to ."/". $filename;
            $fp = fopen($file,"wb");
                fwrite($fp,$mail);
            fclose($fp);

            return $filename;
        } else {
            return null;
        }
    }


    function delete_message($id_mess) {
        if(is_null($id_mess) || !is_numeric($id_mess)) {
            return false;
        }
        $ret = imap_delete($this->_stream, $id_mess);
        imap_expunge($this->_stream);
        return $ret;
    }

    /**
     * Nacteni souboru emailoveho formatu
     * @param string $file cesta k souboru formatu *.eml
     * @return resource
     */
    function open($file) {

        if ( function_exists('imap_open') ) {
            if ( file_exists($file) ) {

                if ( !class_exists('mime_parser_class') ) {
                    require_once LIBS_DIR .'/email/rfc822_addresses.php';
                    require_once LIBS_DIR .'/email/mime_parser.php';
                }

                $imap_mime = new mime_parser_class();
                $imap_mime->mbox = 0;
                $imap_mime->decode_bodies = 1;
                $imap_mime->ignore_syntax_errors = 1;
                $imap_mime->track_lines = 1;
                $parameters=array(
                    'File'=>$file,
                    'SkipBody'=>1
                );

                if(!$imap_mime->Decode($parameters, $decoded))
                {
                    throw new InvalidArgumentException('Chyba při dekódování MIME zprávy: '.$imap_mime->error.' na pozici '.$imap_mime->error_position);
                    return null;
                    //if($imap_mime->track_lines
                    //    && $imap_mime->GetPositionLine($imap_mime->error_position, $line, $column))
                    //  echo ' line '.$line.' column '.$column;
                    //  echo "\n";
                }
                else
                {
                    $zprava = $decoded[0]; unset($decoded);
                    
                    print_r($zprava);
                }


                
            } else {
                throw new InvalidArgumentException('Soubor "'.$file.'" neexistuje nebo se nenachází na požadovaném místě!');
                return null;
            }
        } else {
            throw new InvalidArgumentException('Na tomto serveru není přítomna podpora IMAP.');
            return null;
        }
    }



}
?>
