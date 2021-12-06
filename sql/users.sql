use randomCoffee;
CREATE TABLE users
(
    id        INT     NOT NULL AUTO_INCREMENT PRIMARY KEY,
    chat_id   INT(11) NOT NULL,
    confidant INT     NOT NULL DEFAULT 0,
    regdate   DATETIME         DEFAULT NOW(),
    numOfMeet INT     NOT NULL DEFAULT 0
);