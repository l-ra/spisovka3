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

    public static $test_mode = 0;

    protected function setHeaderMailer(Nette\Mail\Message $mail)
    {
        $app_info = new VersionInformation();
        $mail->setHeader('X-Mailer', Nette\Utils\Strings::webalize($app_info->name, '. ', false));
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

        $mail_source = $mail->generateMessage();

        $config = (new Spisovka\ConfigEpodatelna())->get();
        $config = $config->odeslani;        
        if ($config['podepisovat']) {
            $esign = new esignature();
            if (!$esign->setUserCert($config['cert'], $config['cert_pass']))
                throw new Exception('E-mail nelze podepsat. Neplatný certifikát!');
           
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
            $mess1 = implode(Nette\Mail\Message::EOL . Nette\Mail\Message::EOL, $in_parts);

            $headers_array = $mail->headers;
            if (!empty($headers_array['From']))
                $headers_array['From'] = $mail->getEncodedHeader('From');

            $mail_source = $esign->signMessage($mess1, $headers_array);

            if (is_null($mail_source))
                throw new Exception('E-mail se nepodařilo podepsat.');

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
            $body = $mess_part[1];
        } else {            
            $parts = explode(Nette\Mail\Message::EOL . Nette\Mail\Message::EOL, $mail_source, 2);
            $headers = $parts[0];
            $body = $parts[1];
        }
        
        if ($test_mode === 1) {
            // test - neodesila se
            // vytvor adresar, pokud neexistuje
            if (!file_exists(TEMP_DIR . '/test_emails')) {
                $oldumask = umask(0);
                mkdir(TEMP_DIR . '/test_emails', 0777);
                umask($oldumask);
            }
            @file_put_contents(TEMP_DIR . '/test_emails/' . date('Y-m-d-H-i-s') . "-$to.eml",
                            $headers . "\n\n" . $body);

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
            $body = str_replace(Nette\Mail\Message::EOL, "\n", $body);
            $headers = str_replace(Nette\Mail\Message::EOL, "\n", $headers);
        }

        $res = mail($to, $subject, $body, $headers);
        restore_error_handler();

        if (!$res)
            throw new Exception('E-mail se nepodařilo odeslat.');

        return true;
    }

}
