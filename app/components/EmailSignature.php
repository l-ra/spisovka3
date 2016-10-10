<?php

namespace Spisovka\Components;

/**
 * Description of EmailSignature
 */
class EmailSignature extends \Nette\Application\UI\Control
{

    /**
     *  Záznam z tabulky "epodatelna".
     * @var \EpodatelnaMessage 
     */
    protected $message;
    protected $storage;

    public function __construct(\EpodatelnaMessage $message, $storage)
    {
        parent::__construct();

        $this->message = $message;
        $this->storage = $storage;
    }

    /**
     * Renders component.
     * @return void
     */
    public function render()
    {
        $is_signed = $this->message->email_signed;
        if ($is_signed === null)
            $is_signed = $this->checkIfSigned();

        if (!$is_signed) {
            echo "E-mail není podepsán.";
            return;
        }
        
        $filename = $this->message->getEmailFile($this->storage);
        if (!$filename) {
            echo "Nemohu najít soubor s e-mailem.";
            return;
        }
        
        $esig = new \esignature();
        $result = $esig->verifySignature($filename);
        
        $out = $result['message'];
        if ($result['cert']) {
            $cert = $result['cert'];
            $out .= "\n\n";
            $out .= $cert['subject']['CN'] . "\n";
            if (!empty($cert['subject']['O']))
                $out .= $cert['subject']['O'] . "\n";
            $od = date('j.n.Y', $cert['validFrom_time_t']);
            $do = date('j.n.Y', $cert['validTo_time_t']);
            $out .= "Platnost od $od do $do\n";
            $out .= "Certifikát vydal: {$cert['issuer']['O']}";
        }
        
        echo $out;
    }

    protected function checkIfSigned()
    {
        $filename = $this->message->getEmailFile($this->storage);

        $imap = new \ImapClient();
        $imap->open($filename);
        $structure = $imap->get_message_structure(1);
        $is_signed = $imap->is_signed($structure);
        $imap->close();
        
        $this->message->email_signed = $is_signed;
        $this->message->save();
        
        return $is_signed;
    }
}
