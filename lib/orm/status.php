<?php
namespace Webpractik\Main\Orm;


use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class StatusTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_NAME string optional
 * <li> UF_LEVEL int optional
 * </ul>
 *
 * @package Bitrix\Status
 **/

class StatusTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'booking_status';
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
                'title' => Loc::getMessage('STATUS_ENTITY_ID_FIELD'),
            ),
            'NAME' => array(
                'data_type' => 'text',
                'column_name' => 'UF_NAME',
                'title' => Loc::getMessage('STATUS_ENTITY_UF_NAME_FIELD'),
            ),
            'LEVEL' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_LEVEL',
                'title' => Loc::getMessage('STATUS_ENTITY_UF_LEVEL_FIELD'),
            ),
        );
    }
}
