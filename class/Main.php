<?php
require_once "ServerHandler.php";

class Main
{
    public function start()
    {
        ob_start();
        header('HTTP/1.1 200 OK');
        $handler = new ServerHandler();
        $data = json_decode(file_get_contents('php://input'));
        $handler->parse($data);
        echo('ok');
    }
}