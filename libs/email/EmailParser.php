<?php

/**
 * Třída čte soubor s e-mailem ve formátu RFC 2822.
 * Použitá knihovna je pro e-maily velikosti desítek MB extrémně pomalá a žere RAM.
 */
class EmailParser
{

    private $_charset = "utf-8";  // Znaková sada, kterou používá aplikace
    private $_mime_parser;
    private $_filename;

    /**
     * Nacteni souboru s e-mailem
     * @param string $filename cesta k souboru ve formatu *.eml
     * @return resource
     */
    function open($filename)
    {
        if (!file_exists($filename)) {
            //throw new InvalidArgumentException('Soubor "'.$file.'" neexistuje nebo se nenachází na požadovaném místě!');
            return null;
        }

        $mime_parser = new mime_parser_class();
        $mime_parser->mbox = 0;
        $mime_parser->decode_bodies = 1;
        $mime_parser->ignore_syntax_errors = 1;
        $mime_parser->track_lines = 1;
        $this->_mime_parser = $mime_parser;

        $this->_filename = $filename;

        return true;
    }

    function get_message()
    {
        $decoded = $this->decode_message(false);
        if (!$decoded)
            return null;

        $id_message = 0;
        $mail = $this->get_message_info($decoded[$id_message]);

        $results = [];
        if ($this->_mime_parser->Analyze($decoded[$id_message], $results)) {

            $mail->body = null;
            $mail->texts = "";
            $mail->attachments = isset($results['Attachments']) ? $results['Attachments'] : array();
            $mail->signature = isset($results['Signature']) ? $results['Signature'] : array();
        }

        return $mail;
    }

    function decode_message($include_body = false)
    {
        $parameters = ['File' => $this->_filename,
            'SkipBody' => !$include_body
        ];
        $decoded = [];
        if (!$this->_mime_parser->Decode($parameters, $decoded)) {
            return null;
        }

        return $decoded;
    }

    function analyze_message($decoded_message)
    {
        $results = [];
        if ($decoded_message && $this->_mime_parser->Analyze($decoded_message[0], $results))
            return $results;

        return null;
    }

    private function get_message_info(array $message_info)
    {
        $tmp = new stdClass();
        /* subject */
        $tmp->subject = '';
        if (isset($message_info['Headers']['subject:']))
            $tmp->subject = $this->decode_header($message_info['Headers']['subject:']);

        $tmp->message_id = isset($message_info['Headers']['message-id:']) ? $message_info['Headers']['message-id:']
                    : '';
        $tmp->id_part = 1;

        /* Address */
        $tmp->to = null;
        if (isset($message_info['ExtractedAddresses']['to:']))
            $tmp->to = $this->get_address($message_info['ExtractedAddresses']['to:']);
        $tmp->to_address = '';
        if (isset($message_info['Headers']['to:']))
            $tmp->to_address = $this->decode_header($message_info['Headers']['to:']);

        $tmp->from = null;
        if (isset($message_info['ExtractedAddresses']['from:']))
            $tmp->from = $this->get_address($message_info['ExtractedAddresses']['from:']);
        $tmp->from_address = '';
        if (isset($message_info['Headers']['from:']))
            $tmp->from_address = $this->decode_header($message_info['Headers']['from:']);

        if (isset($message_info['ExtractedAddresses']['cc:'])) {
            $tmp->cc = $this->get_address($message_info['ExtractedAddresses']['cc:']);
            if (isset($message_info['Headers']['cc:'])) {
                $tmp->cc_address = $this->decode_header($message_info['Headers']['cc:']);
            } else {
                $tmp->cc_address = null;
            }
        }
        if (isset($message_info['ExtractedAddresses']['bcc:'])) {
            $tmp->bcc = $this->get_address($message_info['ExtractedAddresses']['bcc:']);
            if (isset($message_info['Headers']['bcc:'])) {
                $tmp->bcc_address = $this->decode_header($message_info['Headers']['bcc:']);
            } else {
                $tmp->bcc_address = null;
            }
        }
        if (isset($message_info['ExtractedAddresses']['return-path:'])) {
            $tmp->reply_to = $this->get_address($message_info['ExtractedAddresses']['return-path:']);
            if (isset($message_info['Headers']['return-path:'])) {
                $tmp->reply_to_address = $this->decode_header($message_info['Headers']['return-path:']);
            } else {
                $tmp->reply_to_address = null;
            }
        }

        /* Date */
        $tmp->udate = @strtotime($message_info['Headers']['date:']);

        if (isset($message_info['BodyLength'])) {
            $tmp->size = $message_info['BodyLength'];
        } else {
            $tmp->size = '???';
        }


        $tmp->source = $message_info;

        return $tmp;
    }

    private function get_address($address)
    {
        $address_array = array();
        if (is_null($address) || empty($address)) {
            return null;
        }
        foreach ($address as $item) {
            $tmp = new stdClass();
            $tmp->personal = "";
            if (isset($item['name'])) {
                $tmp->personal = $item['name'];
                if (isset($item['encoding']))
                    $tmp->personal = @iconv($item['encoding'], $this->_charset, $item['name']);
            }
            $tmp->email = @$item['address'];
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

    /**
     * @param string $string
     * @return string
     */
    private function decode_header($string)
    {
        // [P.L.] tato funkce byla někdy volána s parametrem typu objekt
        // příčinu není snadné odhalit, tedy alespoň zkontrolujeme typ parametru
        if (!is_string($string))
            return '';

        if (function_exists('imap_mime_header_decode')) {
            $parse = "";
            $elements = imap_mime_header_decode($string);
            for ($i = 0; $i < count($elements); $i++) {
                if ($elements[$i]->charset != "default") {
                    $text = @iconv($elements[$i]->charset, $this->_charset, $elements[$i]->text);
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
