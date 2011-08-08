<?php

/**
 * Sends e-mails via the PHP internal mail() function.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Nette\Mail
 */
class ESSMailer extends Object implements IMailer
{

    /**
     * Sends e-mail.
     * @param  Mail
     * @return void
     */
    public function send(Mail $mail)
    {
    	$tmp = clone $mail;
        $message_signed = 0;

        $tmp->setHeader('Subject', NULL);
	$tmp->setHeader('To', NULL);
        $linux = strncasecmp(PHP_OS, 'win', 3);
        
        $from = $tmp->getFrom();

        if ( is_null($from) ) {
            if ( !empty($tmp->config['jmeno']) ) {
                $tmp->setFrom($tmp->config['email'],$tmp->config['jmeno']);
            } else {
                $tmp->setFrom($tmp->config['email']);
            }
        }

        if ( $tmp->signed == 1 ) {

            if ( $tmp->config['typ_odeslani'] == 1 ) {
                
                $esign = new esignature();
                if ( file_exists($tmp->config['cert_key']) ) {
                    $cert_status = $esign->setUserCert($tmp->config['cert'], $tmp->config['cert_key'], $tmp->config['cert_pass']);
                } else {
                    $cert_status = $esign->setUserCert($tmp->config['cert'], null, $tmp->config['cert_pass']);
                }

                $cert_info = $esign->getInfo();
                if ( is_array($cert_info) ) {

                    //echo "<pre>";

                    $mail_source = $tmp->generateMessage();
                    //echo $mail_source;
                    $in_parts = explode(Mail::EOL . Mail::EOL, $mail_source, 2);
                    $header_parts = explode(Mail::EOL,$in_parts[0]);
                    foreach( $header_parts as $iheader => $header ) {
                        if ( strpos($header,'X-Mailer') !== false ) { unset( $header_parts[$iheader] ); }
                        if ( strpos($header,'Date') !== false ) { unset( $header_parts[$iheader] ); }
                        if ( strpos($header,'Message-ID') !== false ) { unset( $header_parts[$iheader] ); }
                        if ( strpos($header,'From') !== false ) { unset( $header_parts[$iheader] ); }
                        if ( strpos($header,'Content-type') !== false ) { unset( $header_parts[$iheader] ); }
                    }
                    $in_parts[0] = implode(Mail::EOL,$header_parts);
                    $mess = implode(Mail::EOL . Mail::EOL, $in_parts);

                    $headers = $tmp->headers;
                    $headers['From'] = $tmp->getEncodedHeader('From');

                    //echo "<pre>";
                    //print_r($headers);
                    //echo "\n\n---------------------------\n\n";
                    //print_r($mess);

                    $mail_source = $esign->signMessage( $mess, $headers);

                    if ( is_null($mail_source) ) {
                        throw new InvalidStateException('Email se nepodarilo podepsat.');
                        $mail_source = $tmp->generateMessage();
                    } else {
                        $message_signed = 1;
                    }
                } else {
                    throw new InvalidStateException('Email nelze podepsat. Neplatný certifikat!');
                    $mail_source = $tmp->generateMessage();
                }
            } else {
                $mail_source = $tmp->generateMessage();
            }
            
        } else {
            $mail_source = $tmp->generateMessage();
        }


        if ( $message_signed == 1 ) {
            $mess_part = explode("\n\n", $mail_source, 2);
            $part_header = explode("\n",$mess_part[0]);
            $headers = array();
            foreach ($part_header as $index => $row) {
                $row_part = explode(":", $row, 2);
                if(count($row_part)<1) {
                    $headers['Content-Type'] = $row;
                } else {
                    $headers[$row_part[0]] = @$row_part[1];
                }
            }
            unset($headers['Content-Transfer-Encoding']);
            $header = "";
            foreach ($headers as $key => $value) {
                $header .= $key .": ". $value . Mail::EOL;
            }
            $mess   = $mess_part[1];
        } else {
            $parts = explode(Mail::EOL . Mail::EOL, $mail_source, 2);
            $linux = strncasecmp(PHP_OS, 'win', 3);
            $header = ($linux ? str_replace(Mail::EOL, "\n", $parts[0]) : $parts[0]);
            $mess = ($linux ? str_replace(Mail::EOL, "\n", $parts[1]) : $parts[1]);
        }


        //echo "<pre>";
        //echo $header;
        //echo "\n\n-----------------------------\n\n";
        //echo $mess;
        //exit;

	Tools::tryError();

        $is_test = $this->is_test();
        
        if ( is_string($is_test) ) {
            $tmp_mail  = "To: ". $is_test . Mail::EOL;
            $tmp_mail .= "X-Swift-To: ". $mail->getEncodedHeader('To') . Mail::EOL;
        } else {
            $tmp_mail  = "To: ". $mail->getEncodedHeader('To') . Mail::EOL;
        }
        $tmp_mail .= "Subject: ". $mail->getEncodedHeader('Subject') . Mail::EOL;
        $tmp_mail .= $header;
        $tmp_mail .= $linux ? "\n" : Mail::EOL ;
        $tmp_mail .= $mess;

        //$tmp_mail .= $mail_source;

        @file_put_contents(CLIENT_DIR .'/temp/tmp_email.eml', $tmp_mail);

        /*echo "<pre>";
        echo CLIENT_DIR .'/temp/tmp_email.eml';
        echo "\n\n-----------------------------\n\n";
        echo $tmp_mail;
        exit;*/

        
        if ( is_string($is_test) ) {
            // test - odesila se na danou adresu
            $res = mail(
		$is_test,
		$mail->getEncodedHeader('Subject'),
		$linux ? str_replace(Mail::EOL, "\n", $mess) : $mess,
		$linux ? str_replace(Mail::EOL, "\n", $header) : $header
            );              
        } else if ( $is_test === -1 ) {
            // test - neodesila se
            $res = 1;
            if (!file_exists(CLIENT_DIR .'/temp/test_emails') ) {
                $oldumask = umask(0);
                mkdir(CLIENT_DIR .'/temp/test_emails', 0777);
                umask($oldumask);
            }
            @file_put_contents(CLIENT_DIR .'/temp/test_emails/'. date('Y-m-d-H-i-s') .'-'. $mail->getEncodedHeader('To') .'.eml', $tmp_mail);
        } else {
            // normalni
            $res = mail(
		$mail->getEncodedHeader('To'),
		$mail->getEncodedHeader('Subject'),
		$linux ? str_replace(Mail::EOL, "\n", $mess) : $mess,
		$linux ? str_replace(Mail::EOL, "\n", $header) : $header
            );            
        }

	if (Tools::catchError($msg)) {
            throw new InvalidStateException($msg);
        } elseif (!$res) {
            throw new InvalidStateException('Email se nepodařilo odeslat.');
	}
        
        return true;
    }

    /**
     * Detekce zpusobu odeslani (normal/test_send/test_nosend)
     * 
     * 0  - normalni odeslani
     * string(adresa) - testovaci odeslani na danou adresu
     * -1 - odeslani do souboru - zadne odeslani emailu
     * 
     * @return int 
     */
    protected function is_test()
    {
        
        $typ = 0;
        //$typ = -1;
        //$typ = "tomvan@email.cz";
        
        return $typ;
    }
    
}
