<?php

namespace Webpractik\Main\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class LoyaltyCardTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'loyalty_card';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'CARD_ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'column_name' => 'ID',
                'title' => Loc::getMessage('CARD_ENTITY_ID_FIELD'),
            ),
            'CODE' => array(
                'data_type' => 'text',
                'column_name' => 'UF_CODE',
                'title' => Loc::getMessage('CARD_ENTITY_UF_CODE_FIELD'),
            ),
            'DISCOUNT' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_DISCOUNT',
                'title' => Loc::getMessage('CARD_ENTITY_UF_DISCOUNT_FIELD'),
            ),
            'PHONE' => array(
                'data_type' => 'text',
                'column_name' => 'UF_PHONE',
                'title' => Loc::getMessage('CARD_ENTITY_UF_PHONE_FIELD'),
            ),
            'OWNNER' => array(
                'data_type' => 'text',
                'column_name' => 'UF_OWNNER',
                'title' => Loc::getMessage('CARD_ENTITY_UF_OWNNER_FIELD'),
            ),
            'EMISSION_DATE' => array(
                'data_type' => 'datetime',
                'column_name' => 'UF_EMISSION_DATE',
                'title' => Loc::getMessage('CARD_ENTITY_UF_EMISSION_DATE_FIELD'),
            ),
            'ACTIVE' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_ACTIVE',
                'title' => Loc::getMessage('CARD_ENTITY_UF_ACTIVE_FIELD'),
            ),
            'EXCHANGE' => array(
                'data_type' => 'text',
                'column_name' => 'UF_EXCHANGE',
                'title' => Loc::getMessage('CARD_ENTITY_UF_EXCHANGE_FIELD'),
            ),
            'LOCATION' => array(
                'data_type' => 'text',
                'column_name' => 'UF_LOCATION',
                'title' => Loc::getMessage('CARD_ENTITY_UF_LOCATION_FIELD'),
            ),
            'COMMENT' => array(
                'data_type' => 'text',
                'column_name' => 'UF_COMMENT',
                'title' => Loc::getMessage('CARD_ENTITY_UF_COMMENT_FIELD'),
            ),
            'EMAIL' => array(
                'data_type' => 'text',
                'column_name' => 'UF_EMAIL',
                'title' => Loc::getMessage('CARD_ENTITY_UF_COMMENT_FIELD'),
            ),
        );
    }
}
