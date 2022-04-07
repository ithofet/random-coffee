<?php
require_once "DataBaseAuth.php";
require_once "EventDB.php";
require_once "UsersDB.php";
require_once "MeetingHistoryDB.php";

class AdminList extends DataBaseAuth
{
    private function generateText(int $status): string
    {
        switch ($status) {
            case 0:
                return "Список команд:
/help, /ls, /menu - справка (список команд)
";
            case 1:
                return $this->generateText(($status - 1)) . "/approve - подтверждение новых заявок
/statistic - статистика собрания
";
            case 2:
                return $this->generateText(($status - 1)) . "/start_event - начать раунд
/send_notify - разослать уведомление о предстоящем раунде
/new_editor - назначить редактора
/delete_editor - разжаловать редактора
";
            default:
                return $this->generateText(($status - 1)) . "/new_admin - назначить администратора
/delete_admin - разжаловать администратора
/admin_list - список пользователей с доступом
/transfer_ownership - передать управление";
        }
    }

    private function getButton(string $text): array
    {
        return
            array(
                "action" => array(
                    "type" => "text",
                    "label" => "$text",
                    "payload" => ""
                ),
                "color" => "secondary"
            );
    }

    private function generateButtons(int $status): array
    {
        $arr = array();
        for ($i = 0; $i <= $status; $i++) {
            switch ($i) {
                case 0:
                    array_push($arr,
                        array($this->getButton("/help")));
                    break;
                case 1:
                    array_push($arr, array($this->getButton("/approve")),
                        array($this->getButton("/statistic")));
                    break;
                case 2:
                    array_push($arr, array($this->getButton("/start_event")),
                        array($this->getButton("/send_notify")),
                        array($this->getButton("/new_editor"),
                            $this->getButton("/delete_editor")));
                    break;
                case 3:
                    array_push($arr, array($this->getButton("/new_admin"),
                        $this->getButton("/delete_admin")),
                        array($this->getButton("/admin_list")),
                        array($this->getButton("/transfer_ownership")));
                    break;
            }
        }
        return $arr;
    }

    private function approve(Sender $vk)
    {
        $db = new EventDB();
        $num = $db->countNeedAllow();
        if ($num > 0) {
            $keyboard = array("one_time" => true,
                "buttons" =>
                    array(
                        array(
                            $this->getButton("/yes"),
                            $this->getButton("/always yes")
                        ),
                        array(

                            $this->getButton("/no"),
                            $this->getButton("/ban"),
                        )
                    )
            );
            $id = (int)$db->needAllow();
            $vk->send("Количество новых заявок: $num\n@id$id", $keyboard);
        } else {
            $vk->send("Новых заявок нет!");
        }
    }

    public function addEditor(int $id, int $self): string
    {
        $this->rmUser($id);
        if ($this->getDb()->exec("INSERT INTO adminList (chat_id, degree, appointedBy) VALUES ($id, 1, $self)"))
            return "Пользователь успешно добавлен";
        else
            return "Ошибка";
    }

    public function rmUser(int $id): string
    {
        if ($this->getDb()->exec("DELETE FROM adminList WHERE chat_id LIKE $id"))
            return "Пользователь успешно удален";
        else
            return "Ошибка";
    }

    public function addAdmin(int $id, int $self): string
    {
        $this->rmUser($id);
        if ($this->getDb()->exec("INSERT INTO adminList (chat_id, degree, appointedBy) VALUES ($id, 2, $self)"))
            return "Пользователь успешно добавлен";
        else
            return "Ошибка";
    }

    public function editOwner(int $id, int $self): string
    {
        $this->rmUser($id);
        $this->rmUser($self);
        $this->addAdmin($self, $self);
        if ($this->getDb()->exec("INSERT INTO adminList (chat_id, degree, appointedBy) VALUES ($id, 3, $self)"))
            return "Владелец успешно сменен";
        else
            return "Ошибка";
    }

    private function locSender(int $a, int $b)
    {
        $before = "Привет! Это проект Random coffee.\\nВот ссылка на твоего собеседника: @id";
        $after = "\\nЖелаю круто провести время! Удачи!";
        return [
            [$a, $before . $b . $after],
            [$b, $before . $a . $after]
        ];
    }

    private $used;
    private $g;
    private $mt;

    private function dfs(int $v): bool
    {

        if (array_key_exists($v, $this->used))
            return false;
        $this->used[$v] = true;
        foreach ($this->g[$v] as $u) {
            if (!array_key_exists($u, $this->mt) || $this->dfs($this->mt[$u])) {
                $this->mt[$u] = $v;
                return true;
            }
        }
        return false;
    }

    private function startEvent(Sender $vk)
    {
        $event = new EventDB();
        $users = $event->getUsers();
        if ($users == false)
            return;
        $loner = null;
        shuffle($users);
        if (count($users) % 2) {
            $loner = array_pop($users);
        }

        $globalCounter = 0;
        $offset = count($users) / 2;
        do {
            $globalCounter++;
            shuffle($users);
            $halfOf = array_chunk($users, $offset);
            $group1 = $halfOf[0];
            $group2 = $halfOf[1];

            $meetings = new MeetingHistoryDB();
            for ($i = 0; $i < count($group1); $i++) {
                $blackList = $meetings->blackList($group1[$i]);
                $keys = array_values(array_diff($group2, $blackList));
                $this->g[$group1[$i]] = $keys;
            }

            $this->mt = array();
            $cnt = 0;
            $numUsers = count($users);
            foreach ($group1 as $i) {
                $this->used = array();
                if ($this->dfs($i))
                    $cnt++;
            }

        } while (count($this->mt) < count($group1) && $globalCounter < 5);

        $history = new MeetingHistoryDB();
        $state = new UsersDB();
        $messages = array();
        foreach ($this->mt as $key => $value) {
            $id1 = $key;
            $id2 = $value;
            $newPairs = $this->locSender($id1, $id2);
            array_push($messages, $newPairs[0], $newPairs[1]);
            $history->addMeet($id1, $id2);
            $state->newMeet($id1);
            $state->newMeet($id2);
        }
        $vk->pairSend($messages);

        $this->getDb()->exec("TRUNCATE event");
        if ($loner !== null) {
            $vk->send("Не смог найти пару для @id$loner\n");
        }
        $vk->send("Сообщения отправлены");
    }

    public function newCommand(string $line, Sender $vk)
    {
        $text = "";
        switch ($line) {
            case "/statistic":
                $event = new EventDB();
                $arr = $event->statistic();
                $text = "📊Статистика:
🏁Всего: {$arr[0]}
✅Подтверждено: {$arr[1]}
⚠Ожидают: {$arr[2]}
❌Отклонено: {$arr[3]}";
                $vk->send($text);
                break;
            case "/approve":
                $this->approve($vk);
                break;
            case "/always yes":
                $user = new UsersDB();
                $res = $vk->getLastId();
                if ($res != 0)
                    $user->premium($res);
            case "/yes":
                $a = new EventDB();
                $res = $vk->getLastId();
                if ($res != 0) {
                    $a->accessUser($res);
                    $this->approve($vk);
                    $vk->sendTo($res, "Ваша заявка успешно подтверждена!");
                } else {
                    $vk->send("Неизвестная команда");
                }
                break;
            case "/ban":
                $user = new UsersDB();
                $res = $vk->getLastId();
                if ($res != 0)
                    $user->ban($res);
            case "/no":
                $a = new EventDB();
                $res = $vk->getLastId();
                if ($res != 0) {
                    $a->denialUser($res);
                    $this->approve($vk);
                } else {
                    $vk->send("Неизвестная команда");
                }
                break;
            case "/menu":
            case "/ls":
            case "/help":
                $text = $this->generateText($vk->adminPosition());
                $keyboard = array("one_time" => true,
                    "buttons" => $this->generateButtons($vk->adminPosition())
                );
                $vk->send($text, $keyboard);
                break;
            case "/admin_list":
                $arr = $this->getDb()->query("SELECT * FROM adminList")->fetchAll();
                $text = "Список подчиненных:\n";
                for ($i = 0; $i < count($arr); $i++) {
                    $text .= "Пользователь: @id{$arr[$i][1]}\n";
                    $text .= "Должность: ";
                    switch ($arr[$i][2]) {
                        case 1:
                            $text .= "Редактор";
                            break;
                        case 2:
                            $text .= "Администратор";
                            break;
                        case 3:
                            $text .= "Владелец";
                            break;
                    }
                    $text .= "\nБыл добавлен пользователем: @id{$arr[$i][3]}\n{$arr[$i][4]}\n\n";
                }
                $vk->send($text);
                break;
            case "/start_event":
                if ($vk->adminPosition() >= 2 && $vk->getLastMessage() == "Подтвердите") {
                    $this->startEvent($vk);
                } elseif ($vk->adminPosition() >= 2) {
                    $vk->send("Подтвердите", array("one_time" => true, "buttons" => array(
                        array($this->getButton("/start_event")),
                        array($this->getButton("/menu"))
                    )));
                } else {
                    $vk->send("Access denied");
                }
                break;
            case "/send_notify":
                $users = new UsersDB();
                $keyboard = array("one_time" => true,
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
                                "label" => "Отписаться",
                                "payload" => ""
                            ),
                            "color" => "secondary"
                        ))
                    )
                );
                $text = 'Скорее прими участие в новом раунде рандом кофе!';
                $vk->multiSend($text, $users->getNotifyIds(), $keyboard);
                break;
            case "/new_editor":
                if ($vk->adminPosition() >= 2)
                    $vk->send("Введите ссылку на страницу нового редактора");
                else
                    $vk->send("Access denied");
                break;
            case "/delete_editor":
                if ($vk->adminPosition() >= 2)
                    $vk->send("Введите ссылку на страницу для удаления редактора");
                else
                    $vk->send("Access denied");
                break;
            case "/new_admin":
                if ($vk->adminPosition() >= 3)
                    $vk->send("Введите ссылку на страницу нового администратора");
                else
                    $vk->send("Access denied");
                break;
            case "/delete_admin":
                if ($vk->adminPosition() >= 3)
                    $vk->send("Введите ссылку на страницу для удаления администратора");
                else
                    $vk->send("Access denied");
                break;
            case "/transfer_ownership":
                if ($vk->adminPosition() >= 3)
                    $vk->send("Введите ссылку на страницу нового управляющего ботом");
                else
                    $vk->send("Access denied");
                break;
            default:
                $vk->send("Неизвестная команда");
                break;
        }
    }


}