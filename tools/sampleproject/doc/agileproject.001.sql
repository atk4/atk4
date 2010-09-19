-- This is your inital structure for the prject. Put all tables here and also
-- insert some data into your tables
--
-- Remember that it's recommended to have "id int not null primary key auto_increment"
-- on all tables. Also if you are not sure about filed length, use 255.
--
create table user (
		id int not null primary key auto_increment,
		email varchar(255),
		name varchar(255),
		surname varchar(255)
		);

