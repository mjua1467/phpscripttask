# phpscripttask
# About
This is php script test to read csv file and import it into database.

It has four classes inside in the single file as the followings:
- Base: Basic class for all other classes including common functions and variables such as config
- utilHelper: Util class, which has useful functions which help other classes such as email validation 
- fileHelper: related to file read/write actions, including parse file contents and return array by file types
- dbHelper: Related to db actions, such as connection, create and insert

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
