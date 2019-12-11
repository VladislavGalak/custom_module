<?php

namespace Webpractik\Main\Response;

use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;

/**
 * Class AddReview
 * @package Webpractik\Main\Response
 */
class AddReview extends Response
{
    const REVIEW_IBLOCK = 5;
    const ADMIN_ID      = 1;
    
    public function handler()
    {
        try {
            $this->checkPost();
            if ($this->response->getErrors()) {
                $this->response->setFail()->send();
            }
            $this->addReview();
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
        
    }
    
    /**
     * валидация входных данных
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    private function checkPost()
    {
        if (!$this->request->getPost('name')) {
            throw new ArgumentException('Введите имя', 'FAIL');
        }
        if (!$this->request->getPost('phone')) {
            throw new ArgumentException('Не введен номер телефона', 'FAIL');
        }
        if (!$this->request->getPost('text')) {
            throw new ArgumentException('Напишите отзыв', 'FAIL');
        }
    }
    
    /**
     * добавление отзыва в бд
     */
    private function addReview()
    {
        $el = new \CIBlockElement;
        
        $PROP          = [];
        $PROP['PHONE'] = $this->request->getPost('phone');
        $PROP['QUEST'] = ['VALUE' => $this->request->getPost('quest')];
        
        $arLoadProductArray = [
            'MODIFIED_BY'       => self::ADMIN_ID,
            'IBLOCK_SECTION_ID' => false,
            'IBLOCK_ID'         => self::REVIEW_IBLOCK,
            'PROPERTY_VALUES'   => $PROP,
            'NAME'              => $this->request->getPost('name'),
            'ACTIVE'            => 'N',
            'ACTIVE_FROM'       => date('d.m.Y'),
            'PREVIEW_TEXT'      => $this->request->getPost('text'),
        ];
        
        if ($file = $this->request->getFile('file')) {
            $file['description']                   = $file['name'];
            $file['COPY_FILE']                     = 'Y';
            $arLoadProductArray['PREVIEW_PICTURE'] = $file;
        }
        
        if ($itemId = $el->Add($arLoadProductArray)) {
            $this->response->sendSuccess();
        } else {
            throw new ArgumentException($el->LAST_ERROR, 'FAIL');
        }
    }
}
