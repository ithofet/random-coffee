<?php
require_once "DataBaseAuth.php";
require_once "UsersDB.php";

class EventDB extends DataBaseAuth
{
    /**
     * @param int $id
     * @return bool
     */
    public function existUser(int $id): bool
    {
        $base = $this->getDb()->query("SELECT * FROM event WHERE chat_id LIKE $id");
        return $base->fetch() !== false;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function addUser(int $id): bool
    {
        $users = new UsersDB();
        $a = $users->getConfidantStatus($id);
        if ($a < 2) {
            $this->getDb()->exec("INSERT INTO event (chat_id, admitted) VALUES ($id, $a)");
            return $a;
        }
        return false;
    }

    /**
     * @param int $id
     */
    public function deleteUser(int $id)
    {
        $this->getDb()->exec("DELETE FROM event WHERE chat_id LIKE $id");
    }

    /**
     * @return string
     */
    public function needAllow(): string
    {
        return $this->getDb()->query("SELECT chat_id FROM event WHERE admitted LIKE 0")->fetch()[0];
    }

    /**
     * @return int
     */
    public function countNeedAllow(): int
    {
        $a = $this->getDb()->query("SELECT COUNT(chat_id) FROM event WHERE admitted LIKE 0");
        return $a->fetch()[0];
    }

    private function changed(int $id): bool
    {
        return $this->getDb()->query("SELECT COUNT(*) FROM event WHERE chat_id LIKE $id AND confidant LIKE 0")->rowCount();
    }
    /**
     * @param int $id
     */
    public function accessUser(int $id)
    {
        if($this->changed($id))
            $this->getDb()->exec("UPDATE event SET admitted = 1 WHERE chat_id LIKE $id");
    }

    /**
     * @param int $id
     */
    public function denialUser(int $id)
    {
        if($this->changed($id))
            $this->getDb()->exec("UPDATE event SET admitted = -1 WHERE chat_id LIKE $id");
    }

    public function getUsers()
    {
        return $this->getDb()->query("SELECT chat_id FROM event WHERE admitted LIKE 1")->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @return array
     */
    public function statistic(): array
    {
        $total = $this->getDb()->query("SELECT COUNT(*) FROM event")->fetchColumn();
        $confirmed = $this->getDb()->query("SELECT COUNT(*) FROM event WHERE admitted LIKE 1")->fetchColumn();
        $awaits = $this->getDb()->query("SELECT COUNT(*) FROM event WHERE admitted LIKE 0")->fetchColumn();
        $blocked = $this->getDb()->query("SELECT COUNT(*) FROM event WHERE admitted LIKE -1")->fetchColumn();
        return array($total, $confirmed, $awaits, $blocked);
    }
}