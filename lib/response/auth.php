<?php

namespace Webpractik\Main\Response;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\UserTable;
use Webpractik\Api\Response;

class Auth extends Response
{
    private $email = '';
    private $pass  = '';
    
    public function handler()
    {
        try {
            $this->setParams();
            global $USER;
            if (!is_object($USER)) {
                $USER = new \CUser;
            }
            $arAuthResult = $USER->Login($this->getLoginByEmail($this->email), $this->pass);
            if ($arAuthResult['TYPE'] == 'ERROR') {
                throw new ArgumentException($arAuthResult['MESSAGE'], 'FAIL');
            }
            $this->response->setSuccess()->send();
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
        $this->email = $this->request->getPost('email');
        if (strlen($this->email) < 1) {
            throw new ArgumentException('Не введен email', 'FAIL');
        }
        
        $this->pass = $this->request->getPost('password');
        if (strlen($this->pass) < 1) {
            throw new ArgumentException('Не введено пароль', 'FAIL');
        }
        
        if (strlen($this->request->getPost('birthday'))>0) {
            throw new ArgumentException('Спам', 'FAIL');
        }
        
        
    }
    
    /**
     * @param $email
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getLoginByEmail($email): string
    {
        $dbRes  = UserTable::query()
            ->setSelect(['LOGIN'])
            ->where('EMAIL', $email)
            ->where('ACTIVE', 'Y')
            ->exec();
        $result = $dbRes->fetch();
        if ($result) {
            return $result['LOGIN'];
        } else {
            return '';
        }
    }
}
