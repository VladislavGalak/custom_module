<?php

namespace Webpractik\Main;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\FileTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Webpractik\Main\Orm\CertificateTable;
use Webpractik\Main\Orm\CertificateTypeTable;
use Webpractik\Main\Orm\CertOrderTable;
use Webpractik\Main\Orm\FeedbackFormTable;
use Webpractik\Main\Orm\FeedbackShkolota;
use Webpractik\Main\Orm\PromocodeTable;
use Webpractik\Main\Orm\Propm4Table;
use Webpractik\Main\Orm\Props4Table;
use Webpractik\Main\Orm\QuestTypeTable;
use Webpractik\Main\Orm\SheduleTable;
use Webpractik\Main\Orm\SpecialDaysTable;

/**
 * Вспомогательный класс с инструментами для разработки
 *
 * Class Tools
 * @package Webpractik\Main
 */
class Tools
{
    const TYPE_WEEKEND    = 69;
    const QUEST_IBLOCK_ID = 4;
    
    
    /**
     * Метод возвращает сивольный код элемента
     *
     * @param int $id
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getElementCodeById(int $id): string
    {
        $dbRes = \Bitrix\Iblock\ElementTable::query()
            ->setSelect(['CODE'])
            ->where('ID', $id)
            ->exec();
        $code  = $dbRes->fetch()['CODE'];
        if ($code) {
            return $code;
        } else {
            return '';
        }
    }
    
    /**
     * Метод возвращает сивольный id элемента
     * @param string $code
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getElementIdByCode(string $code): int
    {
        $dbRes = \Bitrix\Iblock\ElementTable::query()
            ->setSelect(['ID'])
            ->where('CODE', $code)
            ->exec();
        $id    = $dbRes->fetch()['ID'];
        if ($id) {
            return $id;
        } else {
            return 0;
        }
    }
    
    
    /**
     * Получить спиоск квестов, в котором ключами являются ID квестов
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getArQuests(): array
    {
        $agePropRef    = new ReferenceField('AGE', Propm4Table::class,
            Join::on('this.ID', 'ref.IBLOCK_ELEMENT_ID')
                ->where('ref.IBLOCK_PROPERTY_ID', 4)
        );
        $simplePropRef = new ReferenceField('SIMPLE_PROPS', Props4Table::class,
            Join::on('this.ID', 'ref.IBLOCK_ELEMENT_ID')
        );
        
        $ageVal = new ExpressionField('AGE_VAL', "GROUP_CONCAT(DISTINCT %s SEPARATOR '/')", 'AGE.VALUE');
        $link   = new ExpressionField(
            'LINK',
            'CONCAT("/quests/detail/", %s, "/")',
            ['CODE'],
            ['title' => 'Ссылка']
        );
        $isKidsQuest = new ExpressionField('FOR_KIDS', 'IF (!ISNULL(%s), "Y", "N")',['SIMPLE_PROPS.PROPERTY_20']);
        $dbRes  = ElementTable::query()
            ->setSelect(['ID', 'NAME', 'CODE'])
            ->addSelect($ageVal)
            ->addSelect($link)
            ->addSelect('SIMPLE_PROPS.PROPERTY_19', 'LOCATION')
            ->addSelect('SIMPLE_PROPS.PROPERTY_32', 'HEAD_IMG')
            ->addSelect('SIMPLE_PROPS.PROPERTY_33', 'BODY_IMG')
            ->addSelect('FOR_KIDS')
            ->registerRuntimeField($agePropRef)
            ->registerRuntimeField($simplePropRef)
            ->registerRuntimeField($isKidsQuest)
            ->where('IBLOCK_ID', self::QUEST_IBLOCK_ID)
            ->where('ACTIVE', 'Y')
            ->setGroup(['ID', 'NAME'])
            ->addOrder('NAME')
            ->exec();
        $result = [];
        while ($quest = $dbRes->fetch()) {
            $result[$quest['ID']] = $quest;
        };
        
        //рандомная сортировка из сесии
        $sort      = self::getSort();
        $tmpResult = [];
        foreach ($sort as $id) {
            foreach ($result as $key => $item) {
                if ($id == $item['ID']) {
                    $tmpResult[$key] = $item;
                }
                
            }
        }
        $result = $tmpResult;
        if (count($result) == 0) {
            throw new ArgumentException('Не удалось получить список квестов', 'FAIL_OTHER');
        }
        
        return $result;
    }
    
    /**
     * Проверяет является день выходным днем
     *
     * @param string $date
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function isWeekend(string $date): bool
    {
        $result = false;
        
        $dateObj = new DateTime($date, 'd.m.Y');
        $dayNum  = $dateObj->format('w');
        $dateStr = $dateObj->format('d.m.Y');
        $dbRes   = SpecialDaysTable::query()
            ->setSelect(['ID', 'TYPE'])
            ->whereBetween('DATE', new DateTime($dateStr . '00:00'), new DateTime($dateStr . '23:59'))
            ->exec();
        $specDay = $dbRes->fetch();
        if ($specDay) {
            if ($specDay['TYPE'] == self::TYPE_WEEKEND) {
                return true;
            } else {
                return false;
            }
            
        } else {
            
            if ($dayNum == 0 || $dayNum == 6) {
                $result = true;
            }
        }
        return $result;
    }
    
    /**
     * Имя текущего пользователя
     * @return string
     */
    public static function userName(): string
    {
        global $USER;
        $name = $USER->GetFullName();
        if (is_null($name)) {
            $name = '';
        }
        return $name;
    }
    
    /**
     * Получить фото текущего пользователя
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function userPhoto(): string
    {
        global $USER;
        $fileTableRef = new ReferenceField('FILE', FileTable::class, Join::on('this.PERSONAL_PHOTO', 'ref.ID'));
        $dbRes        = UserTable::query()
            ->registerRuntimeField($fileTableRef)
            ->addSelect('FILE.SUBDIR', 'fsubdir')
            ->addSelect('FILE.FILE_NAME', 'fname')
            ->where('ID', $USER->GetID())
            ->exec();
        $result       = $dbRes->fetch();
        
        $path = '/local/static/build/img/svg/person-icon.svg';
        if ($result) {
            $path = '/upload/' . $result['fsubdir'] . '/' . $result['fname'];
        }
        return $path;
    }
    
    /**
     * Id текущего пользователя
     * @return int
     */
    public static function userId(): int
    {
        global $USER;
        $id = $USER->GetID();
        if (is_null($id)) {
            $id = 0;
        }
        return $id;
    }
    
    /**
     * Получить типы сертификатов
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCertificateTypes(): array
    {
        $result = [];
        $dbRes  = CertificateTypeTable::query()
            ->setSelect(['ID', 'NAME', 'VALUE', 'FULL_PRICE'])
            ->where('ACTIVE', 1)
            ->addOrder('NAME')
            ->exec();
        while ($arType = $dbRes->fetch()) {
            $result[$arType['ID']] = $arType;
        }
        return $result;
    }
    
    /**
     * Проверить наличие акций дл
     *
     * @param int    $questId
     * @param string $dateTime
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getPromo(int $questId, string $dateTime): array
    {
        $result = [
            'IS_ACTION'      => false,
            'ACTION_PREVIEW' => '',
            'ACTION_TEXT'    => '',
        ];
        if ($questId < 1 || strlen($dateTime) < 1) {
            return $result;
        }
        $date  = new DateTime($dateTime);
        $dbRes = PromocodeTable::query()
            ->setSelect(['PLASHKA', 'QUESTS', 'TEXT'])
            ->where('PROMOCODE', '')
            ->where('ACTIVE_FROM', '<', $date)
            ->where('ACTIVE_TO', '>', $date)
            ->addOrder('ACTIVE_TO', 'DESC')
            ->exec();
        while ($arFields = $dbRes->fetch()) {
            if (in_array($questId, unserialize($arFields['QUESTS']))) {
                $result['IS_ACTION']      = true;
                $result['ACTION_PREVIEW'] = $arFields['PLASHKA'];
                $result['ACTION_TEXT']    = $arFields['TEXT'];
                break;
            }
            
        }
        return $result;
    }
    
    /**
     * Метод позваоляет сохранить заявку с сайта в бд
     * @param array $arData
     * @throws \Arrilot\BitrixModels\Exceptions\ExceptionFromBitrix
     */
    public static function saveFeedbackToDb(array $arData)
    {
        FeedbackFormTable::create($arData);
    }
    
    /**
     * Метод позваоляет сохранить заявку на сертификат с сайта в бд
     * @param array $arData
     * @throws \Arrilot\BitrixModels\Exceptions\ExceptionFromBitrix
     */
    public static function saveCertOrderToDb(array $arData)
    {
        CertOrderTable::create($arData);
        
    }
    
    public static function saveClassFeedbackToDb(array $arData)
    {
        FeedbackShkolota::create($arData);
    }
    
    /**
     * Очищает телефон от служебных символов
     *
     * @param string $phone
     * @return string
     */
    public static function clearPhone(string $phone): string
    {
        return str_replace([' ', '(', ')', '-', '+'], '', $phone);
    }
    
    /**
     * @param string $certNum
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function makeCertificateUsed(string $certNum)
    {
        if (strlen($certNum) > 3) {
            $dbRes = CertificateTable::query()
                ->setSelect(['CERT_ID'])
                ->where('CERT_NUM', $certNum)
                ->exec();
            if ($result = $dbRes->fetch()) {
                $arData = ['USED' => 1];
                CertificateTable::update($result['CERT_ID'], $arData);
            }
        }
    }
    
    /**
     * Получить маппинг соответствия id квеста => id типа
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMappingQuestType(): array
    {
        $prop4ref     = new ReferenceField('PROPS4', Props4Table::class, Join::on('this.ID', 'ref.IBLOCK_ELEMENT_ID'));
        $questTypeRef = new ReferenceField('QUEST_TYPES', QuestTypeTable::class, Join::on('this.IBLOCK_ELEMENT_PROPS4_QUEST_TYPE_ID', 'ref.XML_ID'));
        $dbRes        = ElementTable::query()
            ->setSelect(['ID'])
            ->addSelect('PROPS4.QUEST_TYPE_ID')
            ->addSelect('QUEST_TYPES.ID', 'TYPE_ID')
            ->where('IBLOCK_ID', self::QUEST_IBLOCK_ID)
            ->registerRuntimeField($prop4ref)
            ->registerRuntimeField($questTypeRef)
            ->exec();
        
        $arMapping = [];
        while ($arFields = $dbRes->fetch()) {
            $arMapping[$arFields['ID']] = $arFields['TYPE_ID'];
        }
        return $arMapping;
    }
    
    /**
     * генерирует случайную соритровку квестов и записывает их в сессию
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function generateRandSort()
    {
        $randSort = new \Bitrix\Main\Entity\ExpressionField("RAND", "RAND()");
        $dbRes    = ElementTable::query()
            ->addSelect('ID')
            ->registerRuntimeField($randSort)
            ->where('IBLOCK_ID', self::QUEST_IBLOCK_ID)
            ->where('ACTIVE', 'Y')
            ->setOrder(['RAND'])
            ->exec();
        $result   = [];
        while ($item = $dbRes->fetch()) {
            $result[] = $item['ID'];
        }
        
        $_SESSION['QUEST_SORT'] = $result;
    }
    
    /**
     * Возвращает сортировку из сессии
     * @return mixed
     */
    public static function getSort()
    {
        return $_SESSION['QUEST_SORT'];
    }
    
    /**
     * @param int $bookingId
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getBookingInfo(int $bookingId): array
    {
        $dbRes = SheduleTable::query()
            ->setSelect(['DATE_RESERVE'])
            ->where('BOOKING_ID', $bookingId)
            ->exec();
        return $dbRes->fetch();
    }
}
