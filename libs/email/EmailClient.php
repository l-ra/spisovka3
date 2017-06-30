<?php

namespace Spisovka;

class EmailClient
{

    /** @var ImapClient Object */
    protected $imap_client;
    protected $mime_parser;
    protected $imap_structure;

    public function __construct($filename)
    {
        try {
            $this->imap_client = new ImapClient();
            $this->imap_client->open($filename);
        } catch (Exception $e) {
            $e->getMessage();
            // fallback to emulation
            $this->imap_client = null;
            $this->mime_parser = new MimeParser($filename);
        }
    }

    /**
     * @return array Description
     */
    public function getAttachments()
    {
        $result = [];
        if ($this->imap_client) {
            $structure = $this->getImapStructure();
            $attachments = $this->imap_client->get_attachments($structure);

            $result = [];
            foreach ($attachments as $part_number => $at) {
                $filename = $at->dparameters['filename'];
                if (strpos($filename, '=?') === 0) {
                    $a = imap_mime_header_decode($filename);
                    $filename = $a[0]->text;
                    $charset = $a[0]->charset;
                    $filename = iconv($charset, 'utf-8', $filename);
                }
                $size = $at->bytes;
                if ($at->encoding == ENCBASE64)
                    $size = floor($size * 3 / 4 * 73 / 74);
                $result[] = ['id' => $part_number, 'name' => $filename, 'size' => $size];
            }
        }
        else if ($this->mime_parser) {
            $result = $this->mime_parser->getAttachments();
        }

        return $result;
    }

    /**
     * @param string $id part number
     * @return array
     */
    public function getPart($id)
    {
        if ($this->imap_client) {
            $imap = $this->imap_client;
            $data = $imap->fetch_body_part(1, $id);

            $part = $this->getImapStructure();
            $part_numbers = explode('.', $id);
            foreach ($part_numbers as $pn) {
                $part = $part->parts[$pn - 1];
            }
            $data = $imap->decode_data($data, $part);

            $filename = $part->dparameters['filename']; // Content-Disposition
            if (!$filename) {
                // pri poruseni MIME standardu v e-mailu zkusime jeste tuto moznost
                $filename = $part->parameters['name'];
            }
        } else if ($this->mime_parser) {
            $part = $this->mime_parser->getPart($id);
            if (!$part)
                throw new \Exception("Získání části mailu číslo $id selhalo.");
            $data = $part['Body'];
            $filename = $part['FileName'];
        }

        return ['data' => $data, 'file_name' => $filename];
    }

    public function isSigned()
    {
        if ($this->imap_client) {
            $structure = $this->getImapStructure();
            return $this->imap_client->is_signed($structure);
        } else
            return $this->mime_parser->isSigned();
    }

    /**
     * IMAP extension only
     */
    protected function getImapStructure()
    {
        if (!$this->imap_structure)
            $this->imap_structure = $this->imap_client->get_message_structure(1);

        return $this->imap_structure;
    }

    /**
     * @return string|null  plain text body if any
     */
    public function findPlainText()
    {
        if ($this->imap_client) {
            $structure = $this->getImapStructure();
            return $this->imap_client->find_plain_text(1, $structure);
        }

        return $this->mime_parser->findPlainText();
    }

}

/**
 * Wrapper for Manuel Lemos's class
 */
class MimeParser
{

    /** @var mime_parser_class implementation */
    protected $impl;
    protected $filename;
    protected $attachments;
    protected $decoded;
    protected $body_decoded = false;

    public function __construct($filename)
    {
        $this->filename = $filename;

        $this->impl = $mp = new \mime_parser_class();
        $mp->mbox = 0;
        $mp->decode_bodies = 1;
        $mp->ignore_syntax_errors = 1;
        $mp->track_lines = 1;
    }

    protected function getDecoded($need_body = false)
    {
        if (!$this->decoded)
            $this->decode($need_body);
        else if ($need_body && !$this->body_decoded)
            $this->decode(true);

        return $this->decoded;
    }

    protected function decode($including_body)
    {
        $parameters = ['File' => $this->filename];
        if (!$including_body)
            $parameters['SkipBody'] = 1;

        $mp = $this->impl;
        $decoded = null;
        if (!$mp->Decode($parameters, $decoded))
            throw new \Exception('Chyba při dekódování e-mailu: ' . $mp->error . ' na pozici ' . $mp->error_position);

        $this->decoded = $decoded = $decoded[0];
        if ($including_body)
            $this->body_decoded = true;

        return $decoded;
    }

    public function getAttachments()
    {
        $this->attachments = [];
        $data = $this->getDecoded();
        $this->findAttachments($data);
        return $this->attachments;
    }

    protected function findAttachments($part)
    {
        if ($part['Parts'])
            foreach ($part['Parts'] as $p) {
                $this->findAttachments($p);
            }

        if (isset($part['FileDisposition']))
            if ($part['FileDisposition'] == "attachment") {
                $filename = $part['FileName'];
                if (!empty($part['FileNameCharacterSet']))
                    $filename = iconv($part['FileNameCharacterSet'], 'utf-8', $filename);
                $this->attachments[] = ['id' => $part['BodyPart'],
                    'name' => $filename, 'size' => $part['BodyLength']];
            }
    }

    public function getPart($id)
    {
        $data = $this->getDecoded(true);
        return $this->findPart($id, $data);
    }

    protected function findPart($id, $part)
    {
        if ($part['Parts'])
            foreach ($part['Parts'] as $p) {
                $found = $this->findPart($id, $p);
                if ($found)
                    return $found;
            }

        if (isset($part['BodyPart']) && $part['BodyPart'] == $id)
            return $part;

        return null;
    }

    public function isSigned()
    {
        $data = $this->getDecoded();
        $content_type = $data['Headers']['content-type:'];

        return strpos($content_type, 'multipart/signed') !== false;
    }

    /**
     * @param array $part internal parameter
     * @return string|null
     */
    public function findPlainText($part = null)
    {
        if (!$part)
            return $this->findPlainText($this->getDecoded(true));

        $content_type = '';
        if (isset($part['Headers']['content-type:']))
            $content_type = $part['Headers']['content-type:'];
        if (strpos($content_type, 'text/plain') !== false)
            if (isset($part['Body']))
                return $part['Body'];

        foreach ($part['Parts'] as $part) {
            $result = $this->findPlainText($part);
            if ($result)
                return $result;
        }

        return null;
    }

}
