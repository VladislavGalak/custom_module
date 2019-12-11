<?php

namespace Webpractik\Main\Booking;


use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Webpractik\Main\Tools;

class GetWeeklyShedule extends Response
{
    
    private $arQuests = [];
    private $questId  = 9;
    private  $arDate   = [];
    
    /**
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\SystemException
     */
    public function handler()
    {
        try {
            $this->setParams();
            $sheduleManager = new SheduleManager();
            $arTimes        = [];
            $this->arQuests = Tools::getArQuests();
            foreach ($this->arDate as $date) {
                $arTimes[] = $sheduleManager->getTimesForQuest($this->questId, $date, Tools::isWeekend($date));
            }
            $times = $sheduleManager->getTimes($arTimes);
            
            reset($this->arDate);
            
            $result = [
                'times' => array_values($times),
                'id'    => $this->questId,
                'name'  => $this->arQuests[$this->questId]['NAME'],
                'age'   => $this->arQuests[$this->questId]['AGE_VAL'],
                'maxPlayers'=> $sheduleManager->getMaxPlayerCnt($this->questId),
                'days'  => [],
            ];
            foreach ($this->arDate as $date) {
                $arDay = [
                    'date'  => $date,
                    'times' => [],
                ];
                
                $dailyShedule = $sheduleManager->getDailyShedule($this->questId, $date);
                $dailyTimes = [];
                foreach ($dailyShedule as $item) {
                    $dailyTimes[]=$item['time'];
                }
                reset($dailyShedule);
                foreach ($times as $time) {
                    if (!in_array($time, $dailyTimes)) {
                        $arDay['times'][] =
                            [
                                'cost'   => '___',
                                'active' => false,
                            ];
                        continue;
                    }
                    foreach ($dailyShedule as $item) {
                        if ($item['time'] == $time) {
                            $arDay['times'][] =
                                [
                                    'cost'   => $item['cost'],
                                    'active' => $item['active'],
                                ];
                            break;
                        }
                        
                    }
                }
                $result['days'][] = $arDay;
            }
            $this->response->addParam('event', $result)->sendSuccess();
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
    }
    
    /**
     * @throws \Bitrix\Main\ArgumentException
     */
    private function setParams()
    {
        $this->questId = $this->request->get('eventId');
        if ($this->questId < 1) {
            throw new ArgumentException('Неверный Id', 'FAIL_QUEST_ID');
        }
        
        
        if (strlen($this->request->get('date')) < 1) {
            throw new ArgumentException('Не передана дата', 'FAIL_DATE');
        }
        $str      = $this->request->get('date');
        $weekNum  = (int)substr($str, 0, 2) - 1;
        $firstDay = preg_replace('/^.{5}/', '01.01', $str);
        $date     = new \DateTime($firstDay);
        $date->modify('+' . $weekNum . ' week');
        $arDate[] = $date->format('d.m.Y');
        for ($i = 0; $i <= 5; $i++) {
            $date->modify('+1 day');
            $arDate[] = $date->format('d.m.Y');
        }
        $this->arDate = $arDate;
        
    }
}
