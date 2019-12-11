<?php

namespace Webpractik\Main\Response;


use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Webpractik\Main\Orm\CertOrderTable;
use Webpractik\Main\Tools;

class SearchEmail extends Response
{
    private $phone = '';
    
    public function handler()
    {
        try {
            $this->setParams();
            $result = \Webpractik\Main\Orm\FeedbackFormTable::query()
                ->filter(['=PROPERTY_PHONE' => $this->phone])->first();
            if($result) {
                $email = (string)$result->fields['PROPERTY_EMAIL_VALUE'];
            }else{
                $result = CertOrderTable::query()
                    ->filter(['=PROPERTY_PHONE' => $this->phone])->first();
                $email = (string)$result->fields['PROPERTY_EMAIL_VALUE'];
            }
            $this->response->addParam('email', $email)->sendSuccess();
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
    }
    
    private function setParams()
    {
        $this->phone = Tools::clearPhone((string)$this->request->get('phone'));
        if (strlen($this->phone) < 1) {
            throw new ArgumentException('Не передан номер телефона', 'FAIL');
        }
        
    }
}
