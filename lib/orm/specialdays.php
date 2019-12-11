<?php

namespace Webpractik\Main\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DaysTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_DATE date optional
 * <li> UF_TYPE int optional
 * </ul>
 *
 * @package Bitrix\Days
 **/
class SpecialDaysTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'special_days';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            'ID'      => [
                'data_type'    => 'integer',
                'primary'      => true,
                'autocomplete' => true,
                'title'        => Loc::getMessage('DAYS_ENTITY_ID_FIELD'),
            ],
            'DATE'    => [
                'data_type'   => 'date',
                'column_name' => 'UF_DATE',
                'title'       => Loc::getMessage('DAYS_ENTITY_UF_DATE_FIELD'),
            ],
            'TYPE' => [
                'data_type' => 'integer',
                'column_name' => 'UF_TYPE',
                'title'     => Loc::getMessage('DAYS_ENTITY_UF_TYPE_FIELD'),
            ],
        ];
    }
}