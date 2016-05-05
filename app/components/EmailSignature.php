<?php

namespace Spisovka\Components;

/**
 * Description of EmailSignature
 */
class EmailSignature extends \Nette\Application\UI\Control
{

    /**
     *  Záznam z tabulky "epodatelna".
     * @var DibiRow 
     */
    protected $message;
    protected $storage;

    public function __construct($message, $storage)
    {
        parent::__construct();

        if (is_int($message)) {
            $m = new \Epodatelna();
            $message = $m->getInfo($message);
        }
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
            echo "Email není podepsán.";
            return;
        }
        
        $model = new \Epodatelna;
        $filename = $model->getMessageSource($this->message->id, $this->storage);
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
        $model = new \Epodatelna;
        $filename = $model->getMessageSource($this->message->id, $this->storage);

        $imap = new \ImapClient();
        $imap->open($filename);
        $structure = $imap->get_message_structure(1);
        $is_signed = $imap->is_signed($structure);
        $imap->close();
        
        $model->update(['email_signed' => $is_signed], [['id = %i', $this->message->id]]);
        
        return $is_signed;
    }
}
