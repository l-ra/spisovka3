<?php

/**
 * Description of Subject
 *
 * @author Pavel Laštovička
 */
class Subject extends DBEntity
{

    const TBL_NAME = 'subjekt';

    /**
     * @param array $data
     * @return Subject
     */
    public static function create(array $data)
    {
        $data['date_created'] = new DateTime();
        $data['user_created'] = self::getUser()->id;

        return parent::create($data);
    }

    public function __toString()
    {
        return Subjekt::displayName($this->getData());
    }
}
