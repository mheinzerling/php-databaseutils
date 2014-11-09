CREATE TABLE IF NOT EXISTS `credential` (
  `provider` VARCHAR(255) NOT NULL,
  `uid`      VARCHAR(255) NOT NULL,
  `user`     INT(11) DEFAULT NULL,
  PRIMARY KEY (`provider`, `uid`),
  KEY `fk_credential_user_id` (`user`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =latin1;

CREATE TABLE IF NOT EXISTS `user` (
  `id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `nick`     VARCHAR(100) NOT NULL,
  `birthday` DATETIME DEFAULT NULL,
  `active`   INT(1)       NOT NULL DEFAULT '0',
  `gender`   ENUM('m', 'f') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_nick` (`nick`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =latin1
  AUTO_INCREMENT =2;


ALTER TABLE `credential`
ADD CONSTRAINT `credential_ibfk_1` FOREIGN KEY (`user`) REFERENCES `user` (`id`)
  ON UPDATE CASCADE;