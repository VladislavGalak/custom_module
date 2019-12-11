<?php

namespace Webpractik\Main\Orm;


use Bitrix\Main,
    Bitrix\Main\Entity,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\Type;

Loc::loadMessages(__FILE__);

/**
 * Class Table
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_CERT_NUM int optional
 * <li> UF_CERT_VALUE int optional
 * </ul>
 *
 * @package Bitrix\
 **/
class CertificateTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'certificates';
    }
    
    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap()
    {
        return [
            new Entity\IntegerField('CERT_ID', [
                'primary'      => true,
                'autocomplete' => true,
                'column_name'  => 'ID',
            ]),
            new Entity\IntegerField('CERT_NUM', [
                'column_name' => 'UF_CERT_NUM',
            ]),
            new Entity\IntegerField('CERT_VALUE', [
                'column_name' => 'UF_CERT_VALUE',
            ]),
            new Entity\IntegerField('FULL_PRICE', [
                'column_name' => 'UF_FULL_PRICE',
            ]),
            new Entity\IntegerField('USED', [
                'column_name' => 'UF_USED',
            ]),
            new Entity\DatetimeField('ACTIVE_TO', [
                'column_name'   => 'UF_ACTIVE_TO',
                'default_value' => new Type\DateTime,
            ]),
            new Entity\TextField('BUYER', [
                'column_name' => 'UF_BUYER',
                'title'       => 'Клиент',
            ]),
            new Entity\TextField('BYER_PHONE', [
                'column_name' => 'UF_BYER_PHONE',
            ]),
            new Entity\TextField('HOLDER_PHONE', [
                'column_name' => 'UF_HOLDER_PHONE',
            ]),
            new Entity\DatetimeField('DEAL_DATE', [
                'column_name'   => 'UF_DEAL_DATE',
                'default_value' => new Type\DateTime,
            ]),
            new Entity\IntegerField('DELIVERY', [
                'column_name' => 'UF_DELIVERY',
            ]),
            new Entity\TextField('DELIVERY_ADDRESS', [
                'column_name' => 'UF_DELIVERY_ADDRESS',
            ]),
            new Entity\TextField('COMMENT', [
                'column_name' => 'UF_COMMENT',
                'title'       => 'Комментарий',
            ]),
            new Entity\IntegerField('PAID', [
                'column_name' => 'UF_PAID',
            ]),
            new Entity\ReferenceField(
                'PAY_TYPE',
                \Webpractik\Main\Orm\UtsTable::class,
                Entity\Query\Join::on('this.PAID', 'ref.UTS_ID')
            ),
        ];
    }
}
