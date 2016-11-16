CREATE TABLE `credential` (
  `provider` VARCHAR(255),
  `uid`      VARCHAR(255),
  `user`     INT(11)
);

CREATE TABLE `user` (
  `id`       INT(11),
  `nick`     VARCHAR(100),
  `birthday` DATETIME,
  `active`   INT(1),
  `gender`   ENUM ('M', 'F')
);