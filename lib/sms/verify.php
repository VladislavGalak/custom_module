<?php

namespace Webpractik\Main\Sms;

use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Webpractik\Main\Sms;

class Verify extends Response
{
    private $smsCode    = '';
    
    /**
     * Основной метод обработчика
     */
    public function handler()
    {
        try {
            $this->smsCode = $this->request->get('smsCode');
            if (strlen($this->smsCode) < 1 || $this->smsCode != Sms::getLastCode()) {
                throw new ArgumentException('Неверный код из смс', 'WRONG_SMS_CODE');
            }
            
            $this->response->sendSuccess();
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
    }
}
