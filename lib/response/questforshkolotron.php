<?php

namespace Webpractik\Main\Response;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Mail\Event;
use Webpractik\Api\Response;
use Webpractik\Main\Tools;

class QuestForShkolotron extends Response
{
    private $name       = '';
    private $phone      = '';
    private $playersCnt = 0;
    private $arQuestId  = [];
    
    public function handler()
    {
        try {
            $this->setParams();
            $arQuests  = Tools::getArQuests();
            $strQuests = '';
            foreach ($this->arQuestId as $questId) {
                $strQuests .= $arQuests[$questId]['NAME'] . '; ';
            }
            $mess = Event::send([
                "EVENT_NAME" => "FEEDBACK_CLASS_FORM",
                "LID"        => "s1",
                "C_FIELDS"   => [
                    'AUTHOR'       => $this->name,
                    'AUTHOR_PHONE' => $this->phone,
                    'CNT'          => $this->playersCnt,
                    'QUESTS'       => $strQuests,
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
     * Получение и проверка параметров
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    private function setParams()
    {
        $this->name = $this->request->getPost('name');
        if (strlen($this->name) < 1) {
            throw new ArgumentException('Не введено имя', 'FAIL');
        }
        $this->phone = Tools::clearPhone($this->request->getPost('phone'));
        if (strlen($this->phone) < 1) {
            throw new ArgumentException('Некорректный номер телефона', 'FAIL');
        }
        $this->playersCnt = (int)$this->request->getPost('count');
        if ($this->playersCnt < 1) {
            throw new ArgumentException('Некорректное кол-во игроков', 'FAIL');
        }
        $this->arQuestId = explode(';', $this->request->getPost('questId'));
        if (count($this->arQuestId) < 1) {
            throw new ArgumentException('Квест не выбран', 'FAIL');
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
        $PROP['PHONE']  = $this->phone;
        $PROP['COUNT']  = $this->playersCnt;
        $PROP['QUESTS'] = $this->arQuestId;
        $arData         = [
            "MODIFIED_BY"       => 1,
            "IBLOCK_SECTION_ID" => false,
            "PROPERTY_VALUES"   => $PROP,
            "NAME"              => $this->name,
            "ACTIVE"            => "Y",
        ];
        Tools::saveClassFeedbackToDb($arData);
    }
}
