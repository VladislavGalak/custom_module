<?php

namespace Webpractik\Main\Response;

use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Bitrix\Main\Mail\Event;
use Webpractik\Main\Tools;

class ContactForm extends Response
{
    private $name    = '';
    private $phone   = '';
    private $email   = '';
    private $comment = '';
    
    public function handler()
    {
        try {
            $this->setParams();
            $mess = Event::send([
                "EVENT_NAME" => "FEEDBACK_CONTACT_FORM",
                "LID"        => "s1",
                "C_FIELDS"   => [
                    'AUTHOR'       => $this->name,
                    'AUTHOR_EMAIL' => $this->email,
                    "AUTHOR_PHONE" => $this->phone,
                    "TEXT"         => $this->comment,
                ],
            ]);
            if ($mess->getId() > 0) {
                $this->saveToDb();
                $this->response->sendSuccess();
            } else {
                throw new ArgumentException('Ошибка отправки сообщения', 'FAIL');
            }
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
        
    }
    
    /**
     * установка и проверка параметров
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    private function setParams()
    {
        $this->name = $this->request->getPost('name');
        if (strlen($this->name) < 1) {
            throw new ArgumentException('Введите имя', 'FAIL');
        }
        $this->phone = $this->request->getPost('phone');
        if (strlen($this->phone) < 1) {
            throw new ArgumentException('Не введен номер телефона', 'FAIL');
        }
        $this->email = $this->request->getPost('email');
        if (strlen($this->email) < 1) {
            throw new ArgumentException('Не введен Email', 'FAIL');
        }
        $this->comment = $this->request->getPost('text');
        if (strlen($this->comment) < 1) {
            throw new ArgumentException('Не введено сообщение', 'FAIL');
        }
        if (strlen($this->request->getPost('birthday')) > 0) {
            throw new ArgumentException('Спам', 'FAIL');
        }
    }
    
    /**
     * Сохранить заявку в инфоблок
     */
    private function saveToDb()
    {
        
        $PROP['PHONE']   = $this->phone;
        $PROP['EMAIL']   = $this->email;
        $PROP['MESSAGE'] = $this->comment;
        $arData          = [
            "MODIFIED_BY"       => 1,
            "IBLOCK_SECTION_ID" => false,
            "PROPERTY_VALUES"   => $PROP,
            "NAME"              => $this->name,
            "ACTIVE"            => "Y",
        ];
        Tools::saveFeedbackToDb($arData);
    }
}
