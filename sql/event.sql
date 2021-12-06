use randomCoffee;
CREATE TABLE event
(
    id       INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    chat_id  INT(11) NOT NULL,
    admitted INT(11) NOT NULL DEFAULT 0
);