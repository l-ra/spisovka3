<?php //netteloader=ImapClientFile

/**
 * Description of imap
 *
 * @author Tomas Vancura
 */

class ImapClientFile {

    private $_stream = null;
    private $_file = null;
    private $_charset = "utf-8";
    private $_mime;

    protected $texts = array();
    protected $attachments = array();
    protected $signatures = array();
    protected $_messages = array();



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

                // temp
                $tmp_dir = CLIENT_DIR .'/temp/mime_email';
                if ( !file_exists($tmp_dir) ) {
                    mkdir($tmp_dir,0777);
                }


                $imap_mime = new mime_parser_class();
                $imap_mime->mbox = 0;
                $imap_mime->decode_bodies = 1;
                $imap_mime->ignore_syntax_errors = 1;
                $imap_mime->track_lines = 1;
                $this->_mime = $imap_mime;
                $parameters=array(
                    'File'=>$file,
                    'SaveBody'=> $tmp_dir,
                    'SkipBody'=>1
                );

                if(!$imap_mime->Decode($parameters, $decoded))
                {
                    //throw new InvalidArgumentException('Chyba při dekódování MIME zprávy: '.$imap_mime->error.' na pozici '.$imap_mime->error_position);
                    return null;
                }
                else
                {
                    $this->_file = $file;
                    $this->_stream = $decoded;
                    return true;
                }

            } else {
                //throw new InvalidArgumentException('Soubor "'.$file.'" neexistuje nebo se nenachází na požadovaném místě!');
                return null;
            }
        } else {
            //throw new InvalidArgumentException('Na tomto serveru není přítomna podpora IMAP.');
            return null;
        }
    }


    /**
     * Ukoceni spojeni s postovnim serverem
     */
    function close() {
        unset($this->_stream);
        return true;
    }

    public function error() {
        return null;
    }


    /**
     * Vraci pocet emailovych zprav v souboru
     * @return int
     */
    function count_messages() {

        if(!is_null($this->_stream)) {
            return count($this->_stream);
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
                $message[$i] = $this->get_message($i);
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
                $message[$i] = $this->get_head_message($i);
            }
            //$this->add_message($message);
            return $message;
        } else {
            return null;
        }
    }

    function get_message($id_message) {
        if (!is_null($this->_stream)) {

            $mail = $this->parse_message_header($this->_stream[$id_message]);

            if($this->_mime->Analyze($this->_stream[$id_message]['Parts'][1], $results)) {

                print_r($results);

                $decode = $this->_mime->DecodePart($results);

                print_r($decode); exit;

                /* Body */
                $mail->body = $this->parse_message_body($this->_stream[$id_message]);

                /* Source */
                $mail->source = $this->source_message($this->_stream[$id_message]);

                $mail->texts = $this->texts;
                $this->texts = array();
                $mail->attachments = $this->attachments;
                $this->attachments = array();
                $mail->signature = $this->signatures;
                $this->signatures = array();

                return $mail;
                    
            } else {
                echo 'MIME message analyse error: '.$mime->error."\n";
                return null;
            }
        } else {
            return null;
        }
    }

    function get_head_message($id_message) {
        if (!is_null($this->_stream)) {

            $mail = $this->parse_message_header($this->_stream[$id_message]);

            if($this->_mime->Analyze($this->_stream[$id_message], $results)) {

                //print_r($results); exit;

                $mail->body = null;
                $mail->texts = "";
                $this->texts = array();
                $mail->attachments = isset($results['Attachments'])?$results['Attachments']:array();
                $this->attachments = array();
                $mail->signature = isset($results['Signature'])?$results['Signature']:array();
                $this->signatures = array();

                return $mail;
            }


            return $mail;
        } else {
            return null;
        }
    }

    function decode_message() {
        if (!is_null($this->_stream)) {
            return $this->_stream;
        } else {
            return null;
        }
    }

    function analyze_message() {
        if (!is_null($this->_stream)) {
            if($this->_mime->Analyze($this->_stream[0], $results)) {
                return $results;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    function source_message($id_message) {
        if (!is_null($this->_stream)) {
            return file_get_contents($this->_file);
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

        $address = $message['ExtractedAddresses'];
        $header = $message['Headers'];


        $tmp = new stdClass();
        /* subject */
        $tmp->subject = $this->decode_header($message['Headers']['subject:']);

        $tmp->message_id = $message['Headers']['message-id:'];
        $tmp->id_part = 1;

        /* Address */
        $tmp->to = $this->get_address($message['ExtractedAddresses']['to:']);
        $tmp->to_address = $this->decode_header($message['Headers']['to:']);

        $tmp->from = $this->get_address($message['ExtractedAddresses']['from:']);
        $tmp->from_address = $this->decode_header($message['Headers']['from:']);

        if(isset($message['ExtractedAddresses']['cc:'])) {
            $tmp->cc = $this->get_address($message['ExtractedAddresses']['cc:']);
            if(isset($message['Headers']['cc:'])) {
                $tmp->cc_address = $this->decode_header($message['Headers']['cc:']);
            } else {
                $tmp->cc_address = null;
            }
        }
        if(isset($message['ExtractedAddresses']['bcc:'])) {
            $tmp->bcc = $this->get_address($message['ExtractedAddresses']['bcc:']);
            if(isset($message['Headers']['bcc:'])) {
                $tmp->bcc_address = $this->decode_header($message['Headers']['bcc:']);
            } else {
                $tmp->bcc_address = null;
            }
        }
        if(isset($message['ExtractedAddresses']['return-path:'])) {
            $tmp->reply_to = $this->get_address($message['ExtractedAddresses']['return-path:']);
            if(isset($message['Headers']['return-path:'])) {
                $tmp->reply_to_address = $this->decode_header($message['Headers']['return-path:']);
            } else {
                $tmp->reply_to_address = null;
            }
        }

        /* Date */
        $tmp->udate = $this->date2unix($message['Headers']['date:']);

        if ( isset($message['BodyLength']) ) {
            $tmp->size = $message['BodyLength'];
        } else {
            $tmp->size = '???';
        }


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
            $tmp->personal = (isset($item['name']))?$this->decode_header($item['name']):"";
            $tmp->email = $item['address'];
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

    private function date2unix($date)
    {
        return @strtotime($date);
        
    }

    function delete_message($id_mess) {
        if(is_null($id_mess) || !is_numeric($id_mess)) {
            return false;
        }
        $ret = imap_delete($this->_stream, $id_mess);
        imap_expunge($this->_stream);
        return $ret;
    }




}
?>
