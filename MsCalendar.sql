CREATE TABLE IF NOT EXISTS /*_*/mscal_names (
	ID int NOT NULL auto_increment,
	Cal_Name varchar(255) NOT NULL,
	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS /*_*/mscal_list (
	ID int NOT NULL auto_increment,
	Date DATE NOT NULL,
	Text_ID int NOT NULL,
	Day_of_Set int NOT NULL DEFAULT 1,
	Cal_ID int NOT NULL,
	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS /*_*/mscal_content (
	ID int NOT NULL auto_increment,
	Text varchar(255) NOT NULL,
	Start_Date DATE NOT NULL,
	Duration int NOT NULL,
	Yearly int NOT NULL,
	PRIMARY KEY (ID)
);
