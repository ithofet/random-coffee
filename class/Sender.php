<?php
require_once "vendor/autoload.php";
require_once "auth/keys.php";
require_once "Database/DataBaseAuth.php";

use \VK\Client\VKApiClient;

class Sender extends DataBaseAuth
{
    use keys;

    private $vk;
    private $chat_id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->vk = new VKApiClient('5.101');
        $this->chat_id = $id;
    }

    public function getLastMessage(): string
    {
        try {
            $arr = $this->vk->messages()->getHistory(
                $this->TOKEN(), array(
                    'user_id' => $this->chat_id,
                    'count' => 2,
                    'group_id' => $this->GROUP_ID()
                )
            )['items'][1];
            if ($arr["from_id"] > 0)
                return "";
            else
                return $arr['text'];
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * @param string $line
     * @return int
     */
    public function convertURLtoId(string $line): int
    {
        try {
            preg_match("/vk\.com\/[0-9a-z_.]+|@[0-9a-z_.]+/", $line, $arr);
            if (!$arr)
                return 0;
            $arr = $arr[0];
            $arr = str_replace("@", "", $arr);
            $arr = str_replace("vk.com/", "", $arr);

            return $this->vk->users()->get($this->TOKEN(), array('user_ids' => $arr))[0]['id'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @param string $text
     * @param array $keyboard
     */
    public function send(string $text, array $keyboard = null)
    {
        if ($keyboard == null) {
            if ($this->adminPosition())
                $line = "/menu";
            else
                $line = "Участвую";
            $keyboard = array("one_time" => true, "buttons" =>
                array(
                    array(
                        array(
                            "action" => array(
                                "type" => "text",
                                "label" => "$line",
                                "payload" => ""
                            ),
                            "color" => "secondary"
                        )

                    )));
        }
        $this->sendTo($this->chat_id, $text, $keyboard);
    }

    public function sendTo(int $id, string $text, array $keyboard = array("one_time" => true, "buttons" => array()))
    {
        $this->vk->messages()->send(
            $this->TOKEN(), array(
                'message' => $text,
                'user_id' => $id,
                'peer_id' => $id,
                'keyboard' => json_encode($keyboard),
                'random_id' => 0
            )
        );
    }

    /**
     * @return int
     */
    public function getLastId(): int
    {
        $arr = $this->getLastMessage();
        if (preg_match('/vk\.com\/[0-9a-z_.]+|@[0-9a-z_.]+/', $arr, $matches) == false)
            return 0;
        preg_match('/[0-9]+/', $matches[0], $arr);
        return $arr[0];
    }

    /**
     * @return int
     * 0 - not admin
     * 1 - editor
     * 2 - admin
     * 3 - owner
     */
    public function adminPosition(): int
    {
        $base = $this->getDb()->query("SELECT * FROM adminList WHERE chat_id LIKE " . $this->chat_id);
        $a = $base->fetch()[2];
        if (!$a)
            return 0;
        return $a;
    }
}