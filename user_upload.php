<?php
$utilhelper = new utilHelper();
$filehelper = new fileHelper();
$dbhelper   = new dbHelper();

/*
    Class: Base
    Desc: Basic class for all other classes including common functions and variables such as config
*/
class Base {

}
/*
    Class: fileHelper
    Desc: related to file read/write actions, including parse file contents and return array by file types
*/
class fileHelper extends Base {

}
/*
    Class: dbhelper
    Desc: Related to db actions, such as connection, create and insert
*/
class dbHelper extends Base {

}
/*
    Class: utilHelper
    Desc: Util class to contain functions which will help other classes such as email vaildation 
*/
class utilHelper extends Base {

}
?>