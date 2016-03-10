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

    protected function setHeaderMailer(Nette\Mail\Message $mail)
    {
        $app_info = Nette\Environment::getVariable('app_info');
        $app_info = explode("#", $app_info);
        $mail->setHeader('X-Mailer', Nette\Utils\Strings::webalize($app_info[2], '. ', false));
    }

    /**
     * Sends e-mail.
     * @param  Nette\Mail\Message
     * @return void
     */
    public function send(Nette\Mail\Message $mail)
    {
        $mail = clone $mail;

        $test_mode = self::$test_mode;
        $to = is_string($test_mode) ? $test_mode : $mail->getEncodedHeader('To');
        $subject = $mail->getEncodedHeader('Subject');
        $mail->setHeader('Subject', NULL);
        $mail->setHeader('To', NULL);
        $this->setHeaderMailer($mail);

        $config = (new Spisovka\ConfigEpodatelna())->get();
        // použij první / hlavní účet, pokud by jich v budoucnu mělo být více
        $config = $config->odeslani[0];
        if ($config['podepisovat']) {
            $esign = new esignature();
            $esign->setUserCert($config['cert'], $config['cert_pass']);

            $cert_info = $esign->parseCertificate();
            if (!is_array($cert_info))
                throw new Exception('Email nelze podepsat. Neplatný certifikát!');

            $mail_source = $mail->generateMessage();
            $in_parts = explode(Nette\Mail\Message::EOL . Nette\Mail\Message::EOL,
                    $mail_source, 2);
            $header_parts = explode(Nette\Mail\Message::EOL, $in_parts[0]);
            foreach ($header_parts as $iheader => $headers) {
                if (strpos($headers, 'X-Mailer') !== false) {
                    unset($header_parts[$iheader]);
                }
                if (strpos($headers, 'Date') !== false) {
                    unset($header_parts[$iheader]);
                }
                if (strpos($headers, 'Message-ID') !== false) {
                    unset($header_parts[$iheader]);
                }
                if (strpos($headers, 'From') !== false) {
                    unset($header_parts[$iheader]);
                }
                if (strpos($headers, 'Content-type') !== false) {
                    unset($header_parts[$iheader]);
                }
            }
            $in_parts[0] = implode(Nette\Mail\Message::EOL, $header_parts);
            $mess = implode(Nette\Mail\Message::EOL . Nette\Mail\Message::EOL, $in_parts);

            $headers_array = $mail->headers;
            $headers_array['From'] = $mail->getEncodedHeader('From');

            $mail_source = $esign->signMessage($mess, $headers_array);

            if (is_null($mail_source))
                throw new Exception('Email se nepodařilo podepsat.');

            $mess_part = explode("\n\n", $mail_source, 2);
            $part_header = explode("\n", $mess_part[0]);
            $headers_array = array();
            foreach ($part_header as $row) {
                $row_part = explode(":", $row, 2);
                if (count($row_part) < 1) {
                    $headers_array['Content-Type'] = $row;
                } else {
                    $headers_array[$row_part[0]] = @$row_part[1];
                }
            }
            unset($headers_array['Content-Transfer-Encoding']);
            $headers = "";
            foreach ($headers_array as $key => $value) {
                $headers .= $key . ": " . $value . Nette\Mail\Message::EOL;
            }
            $mess = $mess_part[1];
        } else {
            $mail_source = $mail->generateMessage();
            $parts = explode(Nette\Mail\Message::EOL . Nette\Mail\Message::EOL, $mail_source, 2);
            $headers = $parts[0];
            $mess = $parts[1];
        }
        
        $tmp_mail = "To: $to\n";
        $tmp_mail .= "Subject: $subject\n";
        $tmp_mail .= $headers;
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
            $headers = str_replace(Nette\Mail\Message::EOL, "\n", $headers);
        }

        $res = mail($to, $subject, $mess, $headers);
        restore_error_handler();

        if (!$res)
            throw new Exception('Email se nepodařilo odeslat.');

        return true;
    }

}
