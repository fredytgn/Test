CREATE TABLE geodata (
Id char(36) NOT NULL,
Type smallint(6) DEFAULT NULL,
Description varchar(200) DEFAULT NULL,
Url varchar(400) DEFAULT NULL,
Location point DEFAULT NULL,
PRIMARY KEY (Id)
);