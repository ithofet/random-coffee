use randomCoffee;
CREATE TABLE adminList
(
    id          INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    chat_id     INT(11) NOT NULL,
    degree      INT,
    appointedBy INT,
    addDate     DATETIME DEFAULT NOW()
);