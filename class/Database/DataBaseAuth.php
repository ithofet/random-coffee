<?php

class DataBaseAuth
{
    protected function getDb()
    {
        return new PDO("mysql:host=<YOUR HOST>;dbname=randomCoffee",'<YOUR DB USERNAME>','<YOUR DB PASSWORD>');
    }
}