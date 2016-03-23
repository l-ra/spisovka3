<?php

/**
 * Třída pro upozorňování uživatelů
 *
 * @author Pavel Laštovička
 */
class Notifications
{

    const RECEIVE_DOCUMENT = 'receive_document';

    /**
     * 
     * @param int $user_id
     * @param string $notification_type
     * @param mixed $additional_info
     * @return int  error code (0 = success)
     */
    static public function notifyUser($user_id, $notification_type, $additional_info)
    {
        if (!self::isNotificationEnabled($notification_type))
            return 1;

        if (!self::isUserNotificationEnabled($notification_type, $user_id))
            return 2;

        $email_notification = in_array($notification_type, [self::RECEIVE_DOCUMENT]);
        if ($email_notification) {
            $email = self::getUserEmail($user_id);
            if (!$email)
                return 3;
            
            $recipient_name = self::getUserName($user_id);
            self::sendEmail($email, $recipient_name, $notification_type, $additional_info);            
        }
        
        return 0;
    }

    static public function isNotificationEnabled($notification_type)
    {
        // Pokud programátor opomene definovat výchozí hodnotu, použij "ne"
        return Settings::get('notification_enabled_' . $notification_type, false);
    }

    static public function enableNotification($notification_type, $enabled = true)
    {
        Settings::set('notification_enabled_' . $notification_type, $enabled);        
    }
    
    static public function isUserNotificationEnabled($notification_type, $user_id = null)
    {
        // Na úrovni uživatele je výchozí hodnota "ano"
        $key = 'notification_enabled_' . $notification_type;
        if ($user_id === null)
            return UserSettings::get($key, true);
        
        $o = new OtherUserSettings($user_id);
        return $o->_get($key, true);
    }

    /**
     * Umožní uživateli deaktivovat jeho osobní upozornění
     * @param string $notification_type
     * @param bool $enabled
     */
    static public function enableUserNotification($notification_type, $enabled = true)
    {
        UserSettings::set('notification_enabled_' . $notification_type, $enabled);        
    }
    
    /**
     * 
     * @param int $user_id
     * @return string  email address
     */
    static protected function getUserEmail($user_id)
    {
        $person = Person::fromUserId($user_id);
        return $person->email;
    }

    static protected function getUserName($user_id)
    {
        $person = Person::fromUserId($user_id);
        return Osoba::displayName($person);
    }
    
    static protected function sendEmail($email, $recipient_name, $notification_type, $additional_info)
    {
        $subject = "Upozornění ze Spisové služby";
        
        $client_config = GlobalVariables::get('client_config');
        $org_name = $client_config->urad->plny_nazev ? : $client_config->urad->nazev;
        $template = "Dobrý den,\n\n"
                . "<message>\n\n"
                . "Vaše Spisová služba\n"
                . "$org_name";
        
        switch ($notification_type) {
            case self::RECEIVE_DOCUMENT:
                $ref_number = $additional_info['reference_number'];
                if (empty($ref_number))
                    $ref_number = '(nemá přiděleno č.j.)';
                $message = "byl Vám předán dokument č.j. {$ref_number}\n"
                . "s názvem \"{$additional_info['document_name']}\".";
                break;

            default:
                // invalid argument. exit
                return;
        }

        $template = str_replace('<message>', $message, $template);        

        $mail = new ESSMail;
        $mail->setFromConfig();
        $mail->addTo($email, $recipient_name);
        $mail->setSubject($subject);
        $mail->setBody($template);
        
        $mailer = new ESSMailer();
        $mailer->send($mail);
    }
}
