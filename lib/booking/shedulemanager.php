<?php

namespace Webpractik\Main\Booking;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;
use Webpractik\Main\Orm\AdditionSheduleTable;
use Webpractik\Main\Orm\TimePriceTable;
use Webpractik\Main\Orm\WeekdaySheduleTable;
use Webpractik\Main\Orm\WeekendSheduleTable;
use Webpractik\Main\Tools;

class SheduleManager
{
    const MONDAY    = 83;
    const TUESDAY   = 84;
    const WEDNESDAY = 85;
    const THURSDAY  = 86;
    const FRIDAY    = 87;
    const SATURDAY  = 88;
    const SUNDAY    = 89;
    
    const QUEST_TYPE_1  = 58;
    const QUEST_TYPE_2  = 59;
    const QUEST_TYPE_3  = 70;
    const QUEST_TYPE_4  = 71;
    const QUEST_TYPE_5  = 72;
    const QUEST_TYPE_6  = 73;
    const QUEST_TYPE_7  = 74;
    const QUEST_TYPE_8  = 75;
    const QUEST_TYPE_9  = 76;
    const QUEST_TYPE_10 = 77;
    
    const WEEKDAY_EXAMPLE     = '16.08.2018';
    const WEEKEND_EXAMPLE     = '18.08.2018';
    const DEFAULT_DATE_ACTIVE = '01.01.2018';
    
    public $questTypes = [];
    
    public $days = [
        0 => self::SUNDAY,
        1 => self::MONDAY,
        2 => self::TUESDAY,
        3 => self::WEDNESDAY,
        4 => self::THURSDAY,
        5 => self::FRIDAY,
        6 => self::SATURDAY,
    ];
    
    private $currentDate;
    
    /**
     * SheduleManager constructor.
     */
    public function __construct()
    {
        $this->questTypes = Tools::getMappingQuestType();
    }
    
    
    /**
     * Получить расписание на день для квеста
     *
     * @param int $questId
     * @param     $date
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDailyShedule(int $questId, $date): array
    {
        $this->currentDate = new DateTime();
        $activeDay         = $this->getDateActive($questId, $date);
        if (Tools::isWeekend($date)) {
            $query = WeekendSheduleTable::query();
        } else {
            $query = WeekdaySheduleTable::query();
        }
        $query->setSelect(['QUEST_ID', 'TIME', 'EXCLUDE_DAYS'])
            ->where('QUEST_ID', $questId)
            ->addOrder('TIME');
        
        //фильтр по активности
        if ($activeDay !== self::DEFAULT_DATE_ACTIVE) {
            $query->where('ACTIVE_FROM', '>=', new DateTime($activeDay . ' ' . '08:59'));
            $query->whereNotNull('ACTIVE_FROM');
        } else {
            $query->whereNull('ACTIVE_FROM');
        }
        
        $dbRes   = $query->exec();
        $shedule = [];
        
        
        while ($arItem = $dbRes->fetch()) {
            $tmpDateOrigin = new DateTime($date . ' ' . $arItem['TIME']);
            $weekDay       = $tmpDateOrigin->format('w');
            $hour          = (int)$tmpDateOrigin->format('G');
            if ($hour < 9) {//рабочий день считается с 9 до 9 сделующего дня, то есть время с 0 до 9 считается сдедующим днем а не текущим поэтому прибавляем 1 день
                $tmpDate = new DateTime($tmpDateOrigin->add('1 day')->format('d.m.Y G:i'));
            } else {
                $tmpDate = new DateTime($tmpDateOrigin->format('d.m.Y G:i'));
            }
            
            $excludeDays = unserialize($arItem['EXCLUDE_DAYS']);
            if ($excludeDays) {
                if (in_array($this->days[$weekDay], $excludeDays)) {
                    $shedule[] = [
                        'time'   => $arItem['TIME'],
                        'cost'   => '___',
                        'active' => false,
                    ];
                    continue;
                }
            }
            
            $shedule[] = [
                'time'   => $arItem['TIME'],
                'cost'   => $this->getCost($questId, $arItem['TIME'], $date, Tools::isWeekend($date)),
                'active' => $tmpDate < $this->currentDate ? false : $this->isTimeReserved($questId, $tmpDate->format('d.m.Y G:i')),
            ];
        }
        
        $times = $this->getAdditinalTimesForQuest($questId, $date);
        foreach ($times as $time) {
            $tmpDateOrigin = new DateTime($date . ' ' . $time);
            $hour          = (int)$tmpDateOrigin->format('G');
            if ($hour < 9) {//рабочий день считается с 9 до 9 сделующего дня, то есть время с 0 до 9 считается сдедующим днем а не текущим поэтому прибавляем 1 день
                $tmpDate = new DateTime($tmpDateOrigin->add('1 day')->format('d.m.Y G:i'));
            } else {
                $tmpDate = new DateTime($tmpDateOrigin->format('d.m.Y G:i'));
            }
            $shedule[] = [
                'time'   => $time,
                'cost'   => $this->getCost($questId, $time, $date, Tools::isWeekend($date)),
                'active' => $tmpDate < $this->currentDate ? false : $this->isTimeReserved($questId, $tmpDate->format('d.m.Y G:i')),
            ];
        }
        
        //сортировка расписания
        for ($i = 0; $i < count($shedule); $i++) {
            if ((int)explode(':', $shedule[$i]['time'])[0] < 9) {
                array_push($shedule, $shedule[$i]);
                unset($shedule[$i]);
            }
        }
        return array_values($shedule);
    }
    
    /**
     * Получить минимальную стоимость на человека
     *
     * @param int    $questId
     * @param string $time
     * @param bool   $isWeekend
     * @param string $date
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getCost(int $questId, string $time, string $date, $isWeekend = false): int
    {
        if ($isWeekend) {
            return $this->getMinPriceForWeekend($questId, $time, $date);
        } else {
            return $this->getMinPriceForWeekday($questId, $time, $date);
        }
        
    }
    
    /**
     * Получить минимальную стоимость на человека в будний день(с учетом вохможных акций)
     *
     * @param int    $questId
     * @param string $time
     * @param string $date
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getMinPriceForWeekday(int $questId, string $time, string $date = self::WEEKDAY_EXAMPLE): int
    {
        $calculator = new BookingCalculator($date . ' ' . $time, $this->getMaxPlayerCnt($questId), $questId);
        $calculator->getActionByDate($calculator->getBasePrice());
        return $calculator->getOptimalPriceByPerson();
    }
    
    /**
     * олучить минимальную стоимость на человека в выходной день(с учетом вохможных акций)
     *
     * @param int    $questId
     * @param string $time
     * @param string $date
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getMinPriceForWeekend(int $questId, string $time, string $date = self::WEEKEND_EXAMPLE): int
    {
        $calculator = new BookingCalculator($date . ' ' . $time, $this->getMaxPlayerCnt($questId), $questId);
        $calculator->getActionByDate($calculator->getBasePrice());
        return $calculator->getOptimalPriceByPerson();
    }
    
    /**
     * Проверить доступно ли для бронирование время
     *
     * @param int    $questId
     * @param string $datetime
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function isTimeReserved(int $questId, string $datetime): bool
    {
        $bookingManager = new BookingManager($questId, $datetime, $this->getMaxPlayerCnt($questId));
        return !$bookingManager->isTimeReserved($questId, $datetime);
    }
    
    /**
     * Получить дату начала активности расписания для квеста, если она установлена
     *
     * @param int    $questId
     * @param string $datetime
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDateActive(int $questId, string $datetime): string
    {
        $date   = new DateTime($datetime);
        $dbRes  = WeekdaySheduleTable::query()
            ->setSelect(['ACTIVE_FROM'])
            ->where('QUEST_ID', $questId)
            ->where('ACTIVE_FROM', '<=', $date)
            ->whereNotNull('ACTIVE_FROM')
            ->addOrder('ACTIVE_FROM', 'DESC')
            ->exec();
        $result = $dbRes->fetch()['ACTIVE_FROM'];
        if (!$result) {
            $result = new DateTime(self::DEFAULT_DATE_ACTIVE);
        }
        return $result->format("d.m.Y");
    }
    
    /**
     * Возвращает время расписания(с учетом начала активности, конечно же)
     *
     * @param int    $questId
     * @param string $date
     * @param bool   $isWeekend
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getTimesForQuest(int $questId, string $date, bool $isWeekend, bool $startFromZero = false): array
    {
        $times      = [];
        $dateActive = $this->getDateActive($questId, $date);
        $strTime    = $startFromZero == true ? '00:00' : '09:00';
        $dateFrom   = new DateTime($dateActive . ' ' . $strTime);
        
        if ($dateActive == self::DEFAULT_DATE_ACTIVE) {
            $dateFilter = Query::filter()
                ->whereNull('ACTIVE_FROM');
        } else {
            $dateFilter = Query::filter()
                ->where('ACTIVE_FROM', '>=', $dateFrom)
                ->whereNotNull('ACTIVE_FROM');
        }
        if ($isWeekend) {
            $query = WeekendSheduleTable::query();
        } else {
            $query = WeekdaySheduleTable::query();
        }
        if ($startFromZero) {
            $query->addOrder('TIME');
        }
        
        $dbRes = $query
            ->setSelect(['TIME'])
            ->where('QUEST_ID', $questId)
            ->where($dateFilter)
            ->addOrder('ACTIVE_FROM')
            ->exec();
        
        while ($result = $dbRes->fetch()) {
            $times[] = $result['TIME'];
        }
        
        $addTimes = $this->getAdditinalTimesForQuest($questId, $date);
        foreach ($addTimes as $time) {
            $times[] = $time;
        }
        
        return $times;
    }
    
    /**
     * П
     *
     * @param int    $questId
     * @param string $date
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getAdditinalTimesForQuest(int $questId, string $date): array
    {
        
        $times    = [];
        $dateFrom = new DateTime($date . ' ' . '00:00');
        $dateTo   = new DateTime($date . ' ' . '23:59');
        $dateFrom->add('1 day');
        $dateTo->add('1 day');
        $dateFilter = Query::filter()
            ->whereBetween('DATE', $dateFrom, $dateTo);
        $dbRes      = AdditionSheduleTable::query()
            ->setSelect(['TIME'])
            ->where('QUEST', $questId)
            ->where($dateFilter)
            ->exec();
        while ($result = $dbRes->fetch()) {
            $times[] = $result['TIME'];
        }
        return $times;
    }
    
    /**
     * мердж массивов со временем с исключением дублей и сортировкой
     *
     * @param $arTimes
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getTimes($arTimes): array
    {
        $commonArTime = [];
        foreach ($arTimes as $arTime) {
            foreach ($arTime as $time) {
                $commonArTime[] = $time;
            }
        }
        sort($commonArTime);
        if (count($commonArTime) < 1) {
            throw new ArgumentException('Ошибка построения расписания', 'FAIL_SHEDULE_BUILDING');
        }
        $commonArTime = array_unique($commonArTime);
        for ($i = 0; $i < count($commonArTime); $i++) {
            if ((int)explode(':', $commonArTime[$i])[0] < 9) {
                array_push($commonArTime, $commonArTime[$i]);
                unset($commonArTime[$i]);
            }
        }
        foreach ($commonArTime as $key => $time) {
            if (is_null($time)) {
                unset($commonArTime[$key]);
            }
            
        }
        return $commonArTime;
    }
    
    /**
     * Поулчить максимально доступное киличество игроков для квеста
     *
     * @param int $questId
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getMaxPlayerCnt(int $questId): int
    {
        $dbRes = TimePriceTable::query()
            ->addSelect(new ExpressionField('CNT', 'MAX(%s)', 'PLAYERS_CNT'))
            ->where('TYPE_PRICE', $this->questTypes[$questId])
            ->exec();
        $count = (int)$dbRes->fetch()['CNT'];
        if ($count < 2) {
            throw new ArgumentException('Не удалось обнаружить доступное количество игроков для квеста', 'FAIL_OTHER');
        }
        
        return $count;
    }
    
}
