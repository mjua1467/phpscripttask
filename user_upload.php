<?php
$utilhelper = new utilHelper();
$filehelper = new fileHelper();
$dbhelper   = new dbHelper();

$params = $utilhelper->getParams($argv);
if ($utilhelper->isError($params)) {
  //ToDo: set error messge format
  echo "this is error";
}

/*
    Class: Base
    Desc: Basic class for all other classes including common functions and variables such as config
*/
class Base {
  protected static $Error = -1;
  protected static $Dev = 1;
  protected static $Live = 2;

  private static $_file = 'users.csv';
  private static $_table = 'users';
  private static $_u = 'root';
  private static $_p = '';
  private static $_h = 'localhost';

  protected static function getDefaultFile() {
    return Base::$_file;
  }
  protected static function getDefaultTable() {
    return Base::$_table;
  }
  protected static function getDefaultUser() {
    return Base::$_u;
  }
  protected static function getDefaultPassword() {
    return Base::$_p;
  }
  protected static function getDefaultHost() {
    return Base::$_h;
  }
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

 /**
 * Extract command or value for command and return the result 
 * 
 * @access private 
 * @param $_param:string 
 * @return object or string
 */ 
  private function _extractCmd($_param) {
    if (substr($_param, 0, 1) === '-') {
      $obj = new stdClass();
      $obj->name = substr($_param, 1);
      if (substr($_param, 0, 2) === '--') {
        $obj->name = substr($_param, 2);
      }
      return $obj;
    } else {
      return $_param;
    }
  }

 /**
 * return array of the command with value
 * 
 * @access public 
 * @param $_argv:array
 * @return $result:array or error
 */ 
  function getParams($_argv=null) {
    $result = [];
    try {
      if (!is_null($_argv) && is_array($_argv) && count($_argv) > 0) {
        for ($i = 1; $i < count($_argv); $i++) {
          $param = $this->_extractCmd($_argv[$i]);
          if (is_object($param)) {
            $result[] = $param;
          } else if (count($result) > 0 && $result[count($result)-1]->name) {
            $result[count($result)-1]->value = $param;
          } else {
            throw new Exception('Parameter Error!');
          }
        }
      }
      return $result;
    } catch (Exception $e) {
      //ToDo: set error messge format
      return $this::$Error;
    }
  }

 /**
 * check whether result is error or not
 * 
 * @access public 
 * @param $_param:any
 * @return boolean
 */
  function isError($_param=null) {
    return !is_null($_param) && is_int($_param) && $this::$Error === $_param ? true : false;
  }
}
?>