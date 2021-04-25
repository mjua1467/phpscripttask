# phpscripttask
# About
This is php script test to read csv file and import it into database.

It has four classes inside in the single file as the followings:
- Base: Basic class for all other classes including common functions and variables such as config
- utilHelper: Util class, which has useful functions which help other classes such as email validation 
- fileHelper: related to file read/write actions, including parse file contents and return array by file types
- dbHelper: Related to db actions, such as connection, create and insert

# Run
At the first, please change the following default variables for yours.
```
$_database: default database(test) There are no option for the database, so you must change it to run it in your database
$_u: default user Otherwise, you shoud use -u option, for your database
$_h: default host Otherwise, you shoud use -h option, for your database
$_p: default password Otherwise, you shoud use -p option, for your database
```

It can be run with / without options.
If run it without options, it will use default db info and file to run, which will be
same as -file [csv file name]

```
php user_upload.php [with/without options]
```
For command options, please refer to the next section.

# Command line options
      • --file [csv file name] – this is the name of the CSV to be parsed \n
      • --create_table – this will cause the MySQL users table to be built (and no further 
      action will be taken)  \n
      • --dry_run – this will be used with the --file directive in case we want to run the 
      script but not insert into the DB. All other functions will be executed, but the 
      database won't be altered  \n
      • -u – MySQL username  \n
      • -p – MySQL password  \n
      • -h – MySQL host  \n
      • --help – which will output the above list of directives with details. \n  
      Notice: without any command options, it will run with default values
 
# Mode
There are 2 modes which are Dev and Live.
Live mode only show Error log while Dev mode show all type of logs

# Validation
Email Validation use FILTER_VALIDATE_EMAIL

# Table structure
There are 4 columns, and id is primary and email is unique.
+---------+-----------------+------+-----+---------+----------------+
| Field   | Type            | Null | Key | Default | Extra          |
+---------+-----------------+------+-----+---------+----------------+
| id      | int(6) unsigned | NO   | PRI | NULL    | auto_increment |
| name    | varchar(30)     | NO   |     | NULL    |                |
| surname | varchar(30)     | NO   |     | NULL    |                |
| email   | varchar(30)     | NO   | UNI | NULL    |                |
+---------+-----------------+------+-----+---------+----------------+

