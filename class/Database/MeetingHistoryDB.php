<?php
require_once "DataBaseAuth.php";

class MeetingHistoryDB extends DataBaseAuth
{
    /**
     * @param int $a
     * @param int $b
     */
    public function addMeet(int $a, int $b)
    {
        $this->getDb()->exec("INSERT INTO meetingHistory (user1, user2) VALUES ($a, $b)");
    }

    /**
     * @param int $id
     * @return array
     */
    public function blackList(int $id): array
    {
        $a1 = $this->getDb()->query("SELECT user2 from meetingHistory WHERE user1 LIKE $id")->fetchAll(PDO::FETCH_COLUMN);
        $a2 = $this->getDb()->query("SELECT user1 from meetingHistory WHERE user2 LIKE $id")->fetchAll(PDO::FETCH_COLUMN);
        return array_merge($a1, $a2);
    }
}