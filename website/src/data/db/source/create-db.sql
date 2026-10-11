DROP DATABASE IF EXISTS database_name;
CREATE DATABASE database_name;
USE database_name;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS user;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE user(
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	uuid CHAR(36) NOT NULL UNIQUE,
	username VARCHAR( 32 ) UNIQUE NOT NULL,
	email VARCHAR( 64 ) UNIQUE NOT NULL,
	password VARCHAR( 256 ) NOT NULL,
	token VARCHAR( 512 ) DEFAULT NULL,
	role ENUM( 'owner', 'admin', 'editor' ) NOT NULL DEFAULT 'editor',
	status ENUM( 'confirmed', 'unconfirmed' ) NOT NULL DEFAULT 'unconfirmed',
	date_of_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);
