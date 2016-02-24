<?php

/**
 * Sends e-mails via the PHP internal mail() function.
 */
class ESSMailer extends Nette\Object implements Nette\Mail\IMailer
{
    /* 0 - normalni odeslani
     * 1 - odeslani do souboru - zadne odeslani emailu
     * string(adresa) - testovaci odeslani na danou adresu
     */

    static $test_mode = 0;

    /**
     * Sends e-mail.
     * @param  Nette\Mail\Message
     * @return void
     */
    public function send(Nette\Mail\Message $mail)
    {
        $tmp = clone $mail;

        $tmp->setHeader('Subject', NULL);
        $tmp->setHeader('To', NULL);

        $config = (new Spisovka\ConfigEpodatelna())->get();
        $config = $config->odeslani[0];
        if ($tmp->signed != 1 || $config['typ_odeslani'] != 1) {
            $mail_source = $tmp->generateMessage();
            $parts = explode(Nette\Mail\Message::EOL . Nette\Mail\Message::EOL, $mail_source, 2);
            $header = $parts[0];
            $mess = $parts[1];
        } else {
            $esign = new esignature();
            if (file_exists($config['cert_key'])) {
                $cert_status = $esign->setUserCert($config['cert'],
                        $config['cert_key'], $config['cert_pass']);
            } else {
                $cert_status = $esign->setUserCert($config['cert'], null,
                        $config['cert_pass']);
            }

            $cert_info = $esign->getInfo();
            if (!is_array($cert_info))
                throw new Exception('Email nelze podepsat. Neplatný certifikát!');

            $mail_source = $tmp->generateMessage();
            $in_parts = explode(Nette\Mail\Message::EOL . Nette\Mail\Message::EOL,
                    $mail_source, 2);
            $header_parts = explode(Nette\Mail\Message::EOL, $in_parts[0]);
            foreach ($header_parts as $iheader => $header) {
                if (strpos($header, 'X-Mailer') !== false) {
                    unset($header_parts[$iheader]);
                }
                if (strpos($header, 'Date') !== false) {
                    unset($header_parts[$iheader]);
                }
                if (strpos($header, 'Message-ID') !== false) {
                    unset($header_parts[$iheader]);
                }
                if (strpos($header, 'From') !== false) {
                    unset($header_parts[$iheader]);
                }
                if (strpos($header, 'Content-type') !== false) {
                    unset($header_parts[$iheader]);
                }
            }
            $in_parts[0] = implode(Nette\Mail\Message::EOL, $header_parts);
            $mess = implode(Nette\Mail\Message::EOL . Nette\Mail\Message::EOL, $in_parts);

            $headers = $tmp->headers;
            $headers['From'] = $tmp->getEncodedHeader('From');

            $mail_source = $esign->signMessage($mess, $headers);

            if (is_null($mail_source))
                throw new Exception('Email se nepodařilo podepsat.');

            $mess_part = explode("\n\n", $mail_source, 2);
            $part_header = explode("\n", $mess_part[0]);
            $headers = array();
            foreach ($part_header as $row) {
                $row_part = explode(":", $row, 2);
                if (count($row_part) < 1) {
                    $headers['Content-Type'] = $row;
                } else {
                    $headers[$row_part[0]] = @$row_part[1];
                }
            }
            unset($headers['Content-Transfer-Encoding']);
            $header = "";
            foreach ($headers as $key => $value) {
                $header .= $key . ": " . $value . Nette\Mail\Message::EOL;
            }
            $mess = $mess_part[1];
        }


        $test_mode = self::$test_mode;
        $to = is_string($test_mode) ? $test_mode : $mail->getEncodedHeader('To');
        $subject = $mail->getEncodedHeader('Subject');

        $tmp_mail = "To: $to\n";
        $tmp_mail .= "Subject: $subject\n";
        $tmp_mail .= $header;
        $tmp_mail .= "\n\n";
        $tmp_mail .= $mess;
        $tmp_mail = str_replace("\r\n", "\n", $tmp_mail);

        // Uschovej posledni odesilany mail pro ucely ladeni
        @file_put_contents(TEMP_DIR . '/tmp_email.eml', $tmp_mail);

        if ($test_mode === 1) {
            // test - neodesila se
            // vytvor adresar, pokud neexistuje
            if (!file_exists(TEMP_DIR . '/test_emails')) {
                $oldumask = umask(0);
                mkdir(TEMP_DIR . '/test_emails', 0777);
                umask($oldumask);
            }
            @file_put_contents(TEMP_DIR . '/test_emails/' . date('Y-m-d-H-i-s') . "-$to.eml",
                            $tmp_mail);

            return true;
        }

        set_error_handler(function($severity, $message) { // zachytávání chyb
            restore_error_handler();
            throw new Exception("Došlo k problému při odesílání mailu: $message");
        }, E_WARNING);
        
        $linux = strncasecmp(PHP_OS, 'win', 3);
        if ($linux) {
            $to = str_replace(Nette\Mail\Message::EOL, "\n", $to);
            $subject = str_replace(Nette\Mail\Message::EOL, "\n", $subject);
            $mess = str_replace(Nette\Mail\Message::EOL, "\n", $mess);
            $header = str_replace(Nette\Mail\Message::EOL, "\n", $header);
        }
        $res = mail($to, $subject, $mess, $header);
        restore_error_handler();

        if (!$res)
            throw new Exception('Email se nepodařilo odeslat.');

        return true;
    }

}
