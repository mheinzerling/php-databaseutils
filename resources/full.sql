CREATE TABLE IF NOT EXISTS `user` (
  `id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `nick`     VARCHAR(100) NOT NULL,
  `birthday` DATETIME              DEFAULT NULL,
  `active`   BOOL         NOT NULL DEFAULT '0',
  `gender`   ENUM ('m', 'f')       DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni_user_nick` (`nick`),
  KEY `idx_user_gender` (`gender`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 2;

CREATE TABLE IF NOT EXISTS `credential` (
  `provider` VARCHAR(255) NOT NULL,
  `uid`      VARCHAR(255) NOT NULL,
  `user`     INT(11) DEFAULT NULL,
  PRIMARY KEY (`provider`, `uid`),
  UNIQUE KEY `uni_credential_provider_user` (`provider`, `user`),
  KEY `idx_credential_provider_uid_user` (`provider`, `uid`, `user`),
  CONSTRAINT `fk_credential_user__user_id` FOREIGN KEY (`user`) REFERENCES `user` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `payload` (
  `payload`   INT(11) DEFAULT NULL,
  `cprovider` VARCHAR(255) NOT NULL,
  `cuid`      VARCHAR(255) NOT NULL,
  CONSTRAINT `fk_payload_cprovider_cuid__credential_provider_uid` FOREIGN KEY (`cprovider`,`cuid`) REFERENCES `credential` (`provider`, `uid`)
    ON UPDATE NO ACTION
    ON DELETE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;