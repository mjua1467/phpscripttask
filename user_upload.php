<?php
$utilhelper = new utilHelper();
$filehelper = new fileHelper();
$dbhelper   = new dbHelper();

$params = $utilhelper->getParams($argv);
if ($utilhelper->isError($params)) {
  //ToDo: set error messge format
  echo "this is error";
  return;
}

$rows = $filehelper->readFile($utilhelper->findArrayObjectbyAttr($params, 'file'), 'csv');
if ($utilhelper->isError($rows)) {
  //ToDo: set error messge format
  echo "this is error from reading file";
  return;
}

$con = $dbhelper->connect($utilhelper->findArrayObjectbyAttr($params, 'u'), $utilhelper->findArrayObjectbyAttr($params, 'p'), $utilhelper->findArrayObjectbyAttr($params, 'h'));
if ($utilhelper->isError($con)) {
  //ToDo: set error messge format
  echo "this is error from db connection";
  return;
}

$create = $dbhelper->create('', '', $con);
if ($utilhelper->isError($create)) {
  //ToDo: set error messge format
  echo "this is error from db create";
  return;
}

if (!$params) {
  $inserts = $dbhelper->inserts($rows, '', $con);
  if ($utilhelper->isError($inserts)) {
    //ToDo: set error messge format
    echo "this is error from db insert";
    return;
  }
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
  private static $_table = 'users11';
  private static $_u = 'root';
  private static $_p = 'root';
  private static $_h = 'localhost';
  private static $_columns = "name VARCHAR(30) NOT NULL, surname VARCHAR(30) NOT NULL, email VARCHAR(30) NOT NULL";

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
  protected static function getDefaultColumns() {
    return Base::$_columns;
  }
}
/*
    Class: fileHelper
    Desc: related to file read/write actions, including parse file contents and return array by file types
*/
class fileHelper extends Base {
  /**
  * get file extension by file name 
  * 
  * @access private 
  * @param $_filename:string 
  * @return filetype: false or file extension
  */ 
  private function _getFileExtension($_filename) {
    $ext = strrpos($_filename,".");
    return $ext===false ? false : substr($_filename,$ext+1);
  }

  /**
  * parse csv file
  * 
  * @access private 
  * @param $_handle:csvdata 
  * @return row:array
  */ 
  private function _parseCSV($_handle) {
    $column = [];
    $rows   = [];
    $cont   = 1;
    while (($data = fgetcsv($_handle, 1000, ",")) !== FALSE) {
      $row = [];
      if (is_null($data) || empty($data) || !is_array($data) || ($cont > 1 && count($data) < count($column))) continue;

      for ($i=0; $i < count($data); $i++) {
        if ($cont === 1) {
          $column[] = $data[$i];
        } else {
          $obj = new stdClass();
          $obj->name  = $column[$i];
          $obj->value = $data[$i];
          $row[] = $obj;
        }
      }
      if ($cont > 1) {
        $rows[] = $row;
      }
      $cont++;
    }
    return $rows;
  }

  /**
  * read file
  * 
  * @access public 
  * @param $_file:string 
  * @return row:array
  */ 
  function readFile($_file=null) {
    $file = $_file;
    if (is_null($file)) {
      $file = $this->getDefaultFile();
    }
    $type = $this->_getFileExtension($file);
    try {
      if (!file_exists($file)) {
        throw new Exception('File not found.');
      }
      if (($handle = fopen($file, "r")) !== FALSE) {
        $data = [];
        if ($type === 'csv') {
          $data = $this->_parseCSV($handle);
        }
        fclose($handle);
        return $data;
      } else {
        throw new Exception('File open failed.');
      }
    } catch ( Exception $e ) {
      //ToDo: set error messge format
      return $this::$Error;
    } 
  }
}
/*
    Class: dbhelper
    Desc: Related to db actions, such as connection, create and insert
*/
class dbHelper extends Base {
  /*
      firstname VARCHAR(30) NOT NULL,
      lastname VARCHAR(30) NOT NULL
  */
  private function _create($_table, $_columns, $_con) {
    $sql = "CREATE TABLE IF NOT EXISTS $_table (
      id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      $_columns
    )";
    if ($_con->query($sql) === TRUE) {
      echo "New Table created successfully";
    } else {
      echo "Error: " . $sql . "<br>" . $_con->error;
      return $this::$Error;
    }
  }

  private function _insert($_data, $_columns, $_table, $_con) {
    $sql = "INSERT INTO $_table ($_columns)
    VALUES ($_data)";
    if ($_con->query($sql) === TRUE) {
      echo "New record created successfully";
    } else {
      echo "Error: " . $sql . "<br>" . $_con->error;
      return $this::$Error;
    }
  }

  /**
  * Extract command or value for command and return the result 
  * 
  * @access private 
  * @param $_param:string 
  * @return object or string
  */ 
  function connect($_u=null, $_p=null, $_h=null) {
    $u = $_u;
    $p = $_p;
    $h = $_h;
    if (is_null($u)) {
      $u = $this::getDefaultUser();
    }
    if (is_null($p)) {
      $u = $this::getDefaultPassword();
    }
    if (is_null($h)) {
      $u = $this::getDefaultHost();
    }

    $con = mysqli_connect($h, $u, $p, 'test');
    if (!$con || $con->connect_error) {
      die('Could not connect: ' . mysql_error());
    } else {
      return $con;
    }
  }

  function create($_table=null, $_columns=null, $_con) {
    if (is_null($_table) || empty($_table)) {
      $_table = $this::getDefaultTable();
    }
    if (is_null($_columns) || empty($_columns)) {
      $_columns = $this::getDefaultColumns();
    }
    $this->_create($_table, $_columns, $_con);
  }

  function inserts($_data, $_table=null, $_con) {
    $columns = '';
    $rows    = [];
    $isError = false;
    if (is_null($_table) || empty($_table)) {
      $_table = $this::getDefaultTable();
    }
    for ($i = 0; $i < count($_data); $i++) {
      for ($j = 0; $j < count($_data[$i]); $j++) {
        $row = "";
        /*
        if (!utilHelper::vaildate($_data[$i][$j])) {
          $isError = true;
          break;
        }
        */
        if ($i === 1) {
          $columns .= "'".trim($_data[$i][$j]->name)."'";
        }
        $row .= "'".trim($_data[$i][$j]->value)."'";
        if ($j < (count($_data[$i])-1)) {
          if ($i === 1) $columns .= ",";
          $row .= ",";
        }
      }
      /*
      if ($isError) {
        break;
      }
      */
      $rows[] = $row;
    }
    echo $columns;
    echo "\n\n";
    print_r($rows);

    //$this->_insert($data, $_table, $_con);
  }
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

/**
 * find value from array object
 * 
 * @access public 
 * @param $_arr:array $_name: object name attribute
 * @return $result:any (value from object value attribute)
 */
  function findArrayObjectbyAttr($_arr, $_name) {
    $result = null;
    foreach($_arr as $obj) {
      if ($_name == $obj->name) {
          $result = $obj->value;
          break;
      }
    }
    return $result;
  }
}
?>