<?php
$utilhelper = new utilHelper();
$filehelper = new fileHelper();
$dbhelper   = new dbHelper();

$utilhelper::initStdout();
$utilhelper::setMode();

$utilhelper::writeStdout('main', $utilhelper::getInfoCode(), 'Start user_upload.php');

$params = $utilhelper->getParams($argv);
if ($utilhelper->isError($params)) {
  $utilhelper::writeStdout('main', $utilhelper::getErrorCode(), 'Command Option is wrong! Please try it again.');
  return;
}

$ishelp = $utilhelper->findArrayObjectbyAttr($params, 'help');
if (!is_null($ishelp) && $ishelp === 'novalue') {
  $utilhelper->getHelp();
  return;
}

if ($utilhelper->findArrayObjectbyAttr($params, 'create_table') !== 'novalue') {
  $rows = $filehelper->readFile($utilhelper->findArrayObjectbyAttr($params, 'file'), 'csv');
  if ($utilhelper->isError($rows)) {
    $utilhelper::writeStdout('main', $utilhelper::getErrorCode(), 'File may not exist or file format is wrong! Please try it again.');
    return;
  }
}

$con = $dbhelper->connect($utilhelper->findArrayObjectbyAttr($params, 'u'), $utilhelper->findArrayObjectbyAttr($params, 'p'), $utilhelper->findArrayObjectbyAttr($params, 'h'));
if ($utilhelper->isError($con)) {
  $utilhelper::writeStdout('main', $utilhelper::getErrorCode(), 'Unable to access DB! Please try it again.');
  return;
}

$create = $dbhelper->create('', '', $con, $utilhelper->findArrayObjectbyAttr($params, 'dry_run'));
if ($utilhelper->isError($create)) {
  $utilhelper::writeStdout('main', $utilhelper::getErrorCode(), 'Fail to create Table! Please try it again.');
  return;
}

if (!$params || $utilhelper->findArrayObjectbyAttr($params, 'create_table') !== 'novalue') {
  $inserts = $dbhelper->inserts($rows, '', $con, $utilhelper->findArrayObjectbyAttr($params, 'dry_run'));
  if ($utilhelper->isError($inserts)) {
    $utilhelper::writeStdout('main', $utilhelper::getErrorCode(), 'Fail to insert! Please try it again.');
    return;
  }
}
$utilhelper::writeStdout('main', $utilhelper::getInfoCode(), 'End user_upload.php');
$utilhelper::closeStdout();

/*
    Class: Base
    Desc: Basic class for all other classes including common functions and variables such as config
*/
class Base {
  protected static $mode;

  protected static $Dev = 1;
  protected static $Live = 2;

  protected static $stdoutclose = false;
  protected static $stdout = '';

  protected static $Error = -1;
  protected static $Info = 4;
  protected static $Success = 3;

  private static $_file = 'users.csv';
  private static $_database = 'test';
  private static $_table = 'users';
  private static $_u = 'root';
  private static $_p = 'root';
  private static $_h = 'localhost';
  private static $_columns = "name VARCHAR(30) NOT NULL, surname VARCHAR(30) NOT NULL, email VARCHAR(30) NOT NULL UNIQUE";

  public static function setMode($_mode=null) {
    if (is_null($_mode) || empty($_mode)) {
      Base::$mode = Base::$Dev;
    } else {
      Base::$mode = $_mode;
    }
  }

  public static function getMode() {
    return Base::$mode;
  }

  protected static function getDefaultDatabase() {
    return Base::$_database;
  }
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

  public static function getErrorCode() {
    return Base::$Error;
  }
  public static function getInfoCode() {
    return Base::$Info;
  }

  public static function initStdout() {
    Base::$stdout = fopen('php://stdout', 'w');
    Base::$stdoutclose = true;
  }

  public static function writeStdout($_class, $_type, $_msg) {
    if (Base::$stdoutclose) {
      $date = date('Y-m-d H:i:s');
      switch ($_type) {
        case Base::$Error :
          $type = 'Error';
          break;
        case Base::$Info :
          $type = 'Info';
          break;
        default:
          $type = 'Unknown';
      }
      if ($type !== 'Info' || ($type === 'Info' && Base::$mode !== Base::$Live)) {
        fwrite(Base::$stdout, "$date [$type][$_class] $_msg" . PHP_EOL);
      }
    }
  }

  public static function closeStdout() {
    if (Base::$stdoutclose) {
      fclose(Base::$stdout);
      Base::$stdoutclose = false;
    }
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
    $this::writeStdout('fileHelper',  $this::$Info, 'Start parsing CSV file');
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
    $this::writeStdout('fileHelper',  $this::$Info, 'End parsing CSV file');
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
    $this::writeStdout('fileHelper',  $this::$Info, 'Start readFile');
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
        $this::writeStdout('fileHelper',  $this::$Info, 'End readFile');
        return $data;
      } else {
        throw new Exception('File open failed.');
      }
    } catch ( Exception $e ) {
      $this::writeStdout('fileHelper', $this::$Error, $e->getMessage());
      return $this::$Error;
    }
  }
}
/*
    Class: dbhelper
    Desc: Related to db actions, such as connection, create and insert
*/
class dbHelper extends Base {
  /**
  * create table
  * 
  * @access private 
  * @param $_table:string table name
  * @param $_columns:string columns for table
  * @param $_con:object  db connection object 
  * 
  * @return :int
  */ 
  private function _create($_table, $_columns, $_con) {
    $sql = "CREATE TABLE $_table (
      id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      $_columns
    )";
    if ($_con->query($sql) === TRUE) {
      return $this::$Success;
    } else {
      return $this::$Error;
    }
  }

  /**
  * drop table
  * 
  * @access private 
  * @param $_table:string table name
  * @param $_columns:string columns for table
  * @param $_con:object  db connection object 
  * 
  * @return :int
  */ 
  private function _drop($_table, $_columns, $_con) {
    $sql = "Drop TABLE $_table";
    if ($_con->query($sql) === TRUE) {
      return $this::$Success;
    } else {
      return $this::$Error;
    }
  }

  /**
  * create wrapper for creating table
  * 
  * @access public 
  * @param $_table:string table name
  * @param $_columns:string columns for table
  * @param $_con:object  db connection object 
  * 
  * @return :boolean
  */ 
  private function _insert($_data, $_columns, $_table, $_con) {
    $sql = "INSERT INTO $_table ($_columns)
    VALUES ($_data)";
    if ($_con->query($sql) === TRUE) {
      return $this::$Success;
    } else {
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

    $con = mysqli_connect($h, $u, $p, $this->getDefaultDatabase());
    if (!$con || $con->connect_error) {
      die('Could not connect: ' . mysql_error());
      $this::writeStdout('dbHelper', $this::$Error, 'Could not connect DB');
    } else {
      return $con;
    }
  }

  /**
  * create wrapper for creating table, which drop table first then create table
  * 
  * @access public 
  * @param $_table:string table name
  * @param $_columns:string columns for table
  * @param $_con:object  db connection object 
  * 
  * @return $result:int
  */ 
  function create($_table=null, $_columns=null, $_con, $_dryrun=null) {
    if (is_null($_table) || empty($_table)) {
      $_table = $this::getDefaultTable();
    }
    if (is_null($_columns) || empty($_columns)) {
      $_columns = $this::getDefaultColumns();
    }

    if ($_dryrun === 'novalue') {
      $result = $this::$Success;
    } else {
      $result = $this->_drop($_table, $_columns, $_con, $_dryrun);
    }
    if ($result !== $this::$Error) {
      $this::writeStdout('dbHelper', $this::$Info, 'Drop Table is completed! Table: '.$_table);
    } else {
      $this::writeStdout('dbHelper', $this::$Info, 'Drop Table is Failed! Table: '.$_table);
    }

    if ($_dryrun === 'novalue') {
      $result = $this::$Success;
    } else {
      $result = $this->_create($_table, $_columns, $_con, $_dryrun);
    }
    if ($result !== $this::$Error) {
      $this::writeStdout('dbHelper', $this::$Info, 'Create Table is completed! Table: '.$_table);
    } else {
      $this::writeStdout('dbHelper', $this::$Info, 'Create Table is Failed! Table: '.$_table);
    }
    return $result;
  }

 /**
 * insert wrapper to insert data into db, which includes validation and data modification
 * 
 * @access public 
 * @param $_data:array row data 
 * @param $_table:string table name
 * @param $_con:object  db connection object 
 * 
 * @return :boolean
 */ 
  function inserts($_data, $_table=null, $_con, $_dryrun=null) {
    $columns = '';
    $rows    = [];
    $isError = false;
    if (is_null($_table) || empty($_table)) {
      $_table = $this::getDefaultTable();
    }
    for ($i = 0; $i < count($_data); $i++) {
      $isError = false;
      $row = "";
      for ($j = 0; $j < count($_data[$i]); $j++) {

        if (!utilHelper::vaildatedata($_data[$i][$j])) {
          $this::writeStdout('dbHelper', $this::$Error, 'Insert is failed! Data may be the wrong. Field: '.trim($_data[$i][$j]->name).' Failed Data: '.$_data[$i][$j]->value);
          $isError = true;
          break;
        }
        if ($i === 0) {
          $columns .= trim($_data[$i][$j]->name);
        }
        $row .= "'".utilHelper::modifydata($_data[$i][$j])."'";
        if ($j < (count($_data[$i])-1)) {
          if ($i === 0) $columns .= ",";
          $row .= ",";
        }
      }
      if (!$isError) {
        if ($_dryrun === 'novalue') {
          $suc = $this::$Success;
        } else {
          $suc = $this->_insert($row, $columns, $_table, $_con);
        }
        if ($suc !== $this::$Error) {
          $this::writeStdout('dbHelper', $this::$Info, 'Insert is completed! Data: '.$row);
        } else {
          $this::writeStdout('dbHelper', $this::$Info, 'Insert is failed! Data may be the wrong. Data: '.$row);
        }
      }
    }
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
 * @return :boolean
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
          if (isset($obj->value)) {
            $result = $obj->value;
          } else {
            $result = 'novalue';
          }
          break;
      }
    }
    return $result;
  }

 /**
 * validation values
 * currently only have email vaildation
 * 
 * @access public 
 * @param $_data:object name / value
 * @return :boolean
 */
  static function vaildatedata($_data) {
    if (is_null($_data) || empty($_data) || !is_object($_data)) {
      return false;
    }
    if (trim($_data->name) === 'email') {
      if (!filter_var($_data->value, FILTER_VALIDATE_EMAIL)) {
        return false;
      }
    }
    return true;
  }

 /**
 * modify values
 * currently only have capitalize if name include name string
 * 
 * @access public 
 * @param $_data:object name / value
 * @return :boolean
 */
  static function modifydata($_data) {
    if (strpos($_data->name, 'name') !== false) {
      return ucfirst(strtolower(trim($_data->value)));
    }
    return $_data->value;
  }

  function getHelp() {
    $content = "
      1. Command line options
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

      2. Mode
      Current mode is Dev mode, and if change Live mode, only Error log type will display

      3. Validation
      Email Validation use FILTER_VALIDATE_EMAIL
    ";
    echo $content;
    return true;
  }
}
?>