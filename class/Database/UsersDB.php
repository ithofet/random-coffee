<?php
require_once "DataBaseAuth.php";

class UsersDB extends DataBaseAuth
{
    /**
     * confidant status:
     * 0 - neutral
     * 1 - always accept
     * 2 - block
     */

    public function getConfidantStatus(int $id): int
    {
        $res = $this->getDb()->query("SELECT * FROM users WHERE chat_id LIKE $id")->fetch()['confidant'];
        if ($res === null) {
            $this->getDb()->exec("INSERT INTO users (chat_id) VALUE ($id)");
            return 0;
        }
        return $res;
    }

    /**
     * @param int $id
     * @return bool
     */
    private function changed(int $id): bool
    {
        return $this->getDb()->query("SELECT COUNT(*) FROM users WHERE chat_id LIKE $id AND confidant LIKE 0")->rowCount();
    }

    /**
     * @param int $id
     */
    public function newMeet(int $id)
    {
        $this->getDb()->exec("UPDATE users SET numOfMeet = numOfMeet + 1 WHERE chat_id LIKE $id");
    }

    /**
     * @param int $id
     */
    public function ban(int $id)
    {
        if ($this->changed($id))
            $this->getDb()->exec("UPDATE users SET confidant = 2 WHERE chat_id LIKE $id");
    }

    /**
     * @param int $id
     */
    public function premium(int $id)
    {
        if ($this->changed($id))
            $this->getDb()->exec("UPDATE users SET confidant = 1 WHERE chat_id LIKE $id");
    }

}