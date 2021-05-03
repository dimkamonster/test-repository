use slim_project;
CREATE TABLE IF NOT EXISTS movie (
                                       id int PRIMARY KEY AUTO_INCREMENT,
                                       title varchar(1024) NULL DEFAULT NULL,
                                       link varchar(2048) NULL DEFAULT NULL,
                                       description text NULL DEFAULT NULL,
                                       pub_date DATETIME NULL DEFAULT NULL,
                                       image BLOB NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;