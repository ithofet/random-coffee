<?php
require_once "vendor/autoload.php";
require_once "Sender.php";
require_once "Database/EventDB.php";
require_once "Database/AdminList.php";
require_once "Database/UsersDB.php";

use VK\CallbackApi\Server\VKCallbackApiServerHandler;

class ServerHandler extends VKCallbackApiServerHandler
{
    private $vk;

    private function getOneButtonKeyboard(string $txt): array
    {
        return array("one_time" => true,
            "buttons" => array(array(array(
                "action" => array(
                    "type" => "text",
                    "label" => "$txt",
                    "payload" => ""
                ),
                "color" => "secondary"
            ))));
    }

    function confirmation(int $group_id, ?string $secret)
    {
        $this->vk = new Sender(0);
        if ($secret === $this->vk->SECRET() && $group_id === $this->vk->GROUP_ID()) {
            echo $this->vk->CONFIRMATION_TOKEN();
            exit();
        }
    }

    public function messageAllow(int $group_id, ?string $secret, array $object)
    {
        $this->vk = new Sender($object['user_id']);
        $this->vk->send('Привет! Приглашаем вас поучаствовать в RandomCoffee!
Нажмите кнопку "Участвую" чтобы присоединиться к нам!', $this->getOneButtonKeyboard("Участвую"));
    }

    public function messageNew(int $group_id, ?string $secret, array $object)
    {
        $id = $object['from_id'];
        $this->vk = new Sender($id);
        $text = trim(mb_strtolower($object['text'], 'UTF-8'));
        switch ($text) {
            case "участвую":
                $db = new EventDB();
                $text = "";
                if ($db->existUser($id))
                    $text = "Вы уже подали заявку";
                else {
                    if ($db->addUser($id))
                        $text = "Вы успешно зарегистрированы!";
                    else
                        $text = "Ваша заявка отправлена на обработку!";
                }
                $this->vk->send("$text\nДля отмены отправьте \"Отмена\"", $this->getOneButtonKeyboard("Отмена"));
                break;
            case "отмена":
                $db = new EventDB();
                $db->deleteUser($id);
                $this->vk->send("Заявка отменена", $this->getOneButtonKeyboard("Участвую"));
                break;
            case "отписаться":
                $db = new UsersDB();
                $db->unsubscribe($id);
                $keyboard = array("one_time" => false,
                    "buttons" => array(array(array(
                        "action" => array(
                            "type" => "text",
                            "label" => "Участвую",
                            "payload" => ""
                        ),
                        "color" => "secondary"
                    )),
                        array(array(
                            "action" => array(
                                "type" => "text",
                                "label" => "Подписаться",
                                "payload" => ""
                            ),
                            "color" => "secondary"
                        ))
                    )
                );
                $this->vk->send('Будем по вам скучать и вновь ждать! 
Чтобы вновь получать сообщения о новом раунде, жмите "Подписаться"!', $keyboard);
                break;
            case "подписаться":
                $db = new UsersDB();
                $db->subscribe($id);
                $this->vk->send("Теперь вы будете в курсе наших событий!", $this->getOneButtonKeyboard("Участвую"));
                break;
            default:
                $admin = new AdminList();
                if ($text[0] === '/' && $this->vk->adminPosition())
                    $admin->newCommand($text, $this->vk);
                elseif (preg_match('/https:\/\/vk\.com\/[0-9a-z_.]+|@[0-9a-z_.]+/', $text, $arr)) {
                    $lastMessage = $this->vk->getLastMessage();
                    $id = $this->vk->convertURLtoId($text);
                    switch ($lastMessage) {
                        case "Введите ссылку на страницу нового редактора":
                            $text = $admin->addEditor($id, $object['from_id']);
                            break;
                        case "Введите ссылку на страницу для удаления администратора":
                        case "Введите ссылку на страницу для удаления редактора":
                            $text = $admin->rmUser($id);
                            break;
                        case "Введите ссылку на страницу нового администратора":
                            $text = $admin->addAdmin($id, $object['from_id']);
                            break;
                        case"Введите ссылку на страницу нового управляющего ботом":
                            $text = $admin->editOwner($id, $object['from_id']);
                            break;
                        default:
                            if ($this->vk->adminPosition())
                                $text = "Неверный формат данных";
                    }
                    $this->vk->send($text);
                }
                break;
        }

    }
}