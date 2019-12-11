<?php

namespace Webpractik\Main\Booking;


use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Webpractik\Main\Tools;

class GetDailyShedule extends Response
{
    private $date     = '20.09.2018';
    private $arQuests = [];
    
    public function handler()
    {
        $result = [];
        try {
            $this->setParams();
            $this->arQuests = Tools::getArQuests();
            foreach ($this->arQuests as $quest) {
                $promoInfo = Tools::getPromo($quest['ID'], $this->date . '15:00');
                
                $sheduleManager = new \Webpractik\Main\Booking\SheduleManager();
                $result[]       = [
                    'id'        => $quest['ID'],
                    'name'      => $quest['NAME'],
                    'link'      => $quest['LINK'],
                    'age'       => $quest['AGE_VAL'],
                    'isPromo'   => $promoInfo['IS_ACTION'],
                    'promoText' => $promoInfo['ACTION_PREVIEW'],
                    'maxPlayers'=> $sheduleManager->getMaxPlayerCnt($quest['ID']),
                    'times'     => $sheduleManager->getDailyShedule($quest['ID'], $this->date),
                ];
            }
            $this->response->addParam('events', $result)->sendSuccess();
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
        $this->response->addParam('events', $result)->sendSuccess();
    }
    
    private function setParams()
    {
        $this->date = $this->request->get('date');
        if (strlen($this->date) == 0) {
            throw new ArgumentException('Не передана дата', 'FAIL_DATE');
        }
        
    }
    
}
