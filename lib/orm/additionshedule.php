<?php
namespace Webpractik\Main\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class SheduleTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_DATE datetime optional
 * <li> UF_QUEST int optional
 * <li> UF_TIME string optional
 * </ul>
 *
 * @package Bitrix\Shedule
 **/

class AdditionSheduleTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'additional_shedule';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('SHEDULE_ENTITY_ID_FIELD'),
            ),
            'DATE' => array(
                'data_type' => 'datetime',
                'column_name' => 'UF_DATE',
                'title' => Loc::getMessage('SHEDULE_ENTITY_UF_DATE_FIELD'),
            ),
            'QUEST' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_QUEST',
                'title' => Loc::getMessage('SHEDULE_ENTITY_UF_QUEST_FIELD'),
            ),
            'TIME' => array(
                'data_type' => 'text',
                'column_name' => 'UF_TIME',
                'title' => Loc::getMessage('SHEDULE_ENTITY_UF_TIME_FIELD'),
            ),
        );
    }
}
