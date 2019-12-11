<?php

namespace Webpractik\Main\Sms;


use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Webpractik\Main\Sms;

class SendCode extends Response
{
    private $phone = '';
    
    public function handler()
    {
        try {
            $this->setParams();
            $sendResult = Sms::send($this->phone, Sms::makeCode());
            if (in_array('OK', $sendResult)) {
                $this->response->sendSuccess();
            }
            
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
    }
    
    private function setParams()
    {
        $this->phone = $this->request->get('phone');
        if (strlen($this->phone) < 1) {
            throw new ArgumentException('Не передан номер телефона', 'FAIL_DATA');
        }
        
    }
}
