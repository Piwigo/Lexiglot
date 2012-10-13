<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2012 Damien Sorel       http://www.strangeplanet.fr |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+
 
defined('LEXIGLOT_PATH') or die('Hacking attempt!'); 

include_once(LEXIGLOT_PATH . 'include/functions_mysql.inc.php');
include_once(LEXIGLOT_PATH . 'include/functions_core.inc.php');
include_once(LEXIGLOT_PATH . 'include/functions_user.inc.php');
include_once(LEXIGLOT_PATH . 'include/functions_html.inc.php');
include_once(LEXIGLOT_PATH . 'include/functions_svn.inc.php');
include_once(LEXIGLOT_PATH . 'include/functions_stats.inc.php');

/**
 * PHP COMPATIBILITIES
 */
if (!function_exists('json_decode')) // >= 5.2.0
{
  include_once(LEXIGLOT_PATH . 'include/php_compat/json.class.php');
  $json_service = new Services_JSON();
  
  function json_decode($value)
  {
    global $json_service;
    return $json_service->decode($value);
  }
  function json_encode($value)
  {
    global $json_service;
    return $json_service->encode($value);
  }
}


/**
 * create an url from current url
 * @param array add/modify
 * @param array reject ('true' for reject all)
 * @param string filename
 * @return string
 */
function get_url_string($add=array(), $reject=array(), $file=null)
{
  $query_string = '';
  
  if ($file == null) $file = script_basename();
  
  if ($reject !== true)
  {
    $str = $_SERVER['QUERY_STRING'];
    parse_str($str, $vars);
  
    foreach ($reject as $key)
    {
      unset($vars[$key]);
    }
  }
  
  foreach ($add as $key => $value)
  {
    $vars[$key] = $value;
  }
  
  $is_first = true;
  foreach ($vars as $key => $value)
  {
    $query_string.= $is_first ? '?' : '&amp;';
    $query_string.= ($value != null) ? $key.'='.$value : $key;
    $is_first = false;
  }
  
  return $file.'.php'.$query_string;
}

/**
 * get filename (without extension) of the current script
 */
function script_basename()
{
  return basename($_SERVER['SCRIPT_NAME'], '.php');
}

/**
 * get the home url
 * @return string
 */
function get_home_url()
{
  /*$str = $_SERVER['QUERY_STRING'];
  parse_str($str, $vars); 
  return get_url_string(array(), array_keys($vars), 'index');*/
  return 'index.php';
}

/**
 * get the absolute home url
 * @return string
 */
function get_absolute_home_url()
{
  if (isset($_SERVER['HTTPS']) && ( strtolower($_SERVER['HTTPS']) == 'on' or $_SERVER['HTTPS'] == 1 ) )
  {
    $host = 'https://';
  }
  else
  {
    $host = 'http://';
  }
  $host.= $_SERVER['SERVER_NAME'];
  $host.= str_replace(basename($_SERVER['SCRIPT_NAME']), null, $_SERVER['SCRIPT_NAME']);
  return $host;
}

/**
 * perform an http redirection (default is self)
 * @param string url
 */
function redirect($url=null)
{
  if (empty($url))
  {
    $url = get_url_string();
  }
  $url = html_entity_decode($url);
  
  header('Request-URI: '.$url);
  header('Content-Location: '.$url);
  header('Location: '.$url);
  exit;
}

/**
 * check if file exists but is not dir
 * @param file
 * @return bool
 */
function file_exists_strict($file)
{
  return file_exists($file) && !is_dir($file);
}

/**
 * check if a folder is empty (a directory with just '.svn' is empty)
 * @param string path
 * @return bool
 */
function dir_is_empty($dirname)
{
  if (!is_dir($dirname)) return false;
  $dir = scandir($dirname);
  foreach ($dir as $file)
  {
    if (!in_array($file, array('.','..','.svn'))) return false;
  }
  return true;
}

/**
 * persistently stores a variable for the current session
 * @param string var name
 * @param string value
 * @return bool
 */
function set_session_var($var, $value)
{
  global $conf;
  if ( !isset($_SESSION) ) return false;
  
  $_SESSION[ $conf['session_prefix'].$var ] = $value;
  return true;
}

/**
 * retrieves the value of a persistent variable for the current session
 * @param string var name
 * @return mixed
 */
function get_session_var($var)
{
  global $conf;
  if ( !isset($_SESSION) ) return null;
  
  if ( isset($_SESSION[ $conf['session_prefix'].$var ]) )
  {
    return $_SESSION[ $conf['session_prefix'].$var ];
  }
  return null;
}

/**
 * deletes a persistent variable for the current session
 * @param string var name, or array of var names
 * @return bool
 */
function unset_session_var($var)
{
  global $conf;
  if ( !isset($_SESSION) ) return false;
  
  if (!is_array($var))
  {
    $var = array($var);
  }
  foreach ($var as $i)
  {
    unset($_SESSION[ $conf['session_prefix'].$i ]);
  }

  return true;
}

/**
 * generate a "secret key" that is to be sent back when a user posts a form
 * @param int valid_after_seconds - key validity start time from now
 * @param string aditional_data_to_hash
 * @return string
 */
function get_ephemeral_key($valid_after_seconds, $aditionnal_data_to_hash = '')
{
	global $conf;
	$time = round(microtime(true), 1);
  
	return $time.':'.$valid_after_seconds.':'
		.hash_hmac(
			'md5',
			$time.substr($_SERVER['REMOTE_ADDR'],0,5).$valid_after_seconds.$aditionnal_data_to_hash,
			SALT_KEY
      );
}

/**
 * verify the "secret key" sended with the form
 * @param string key
 * @param string aditional_data_to_hash
 * @param boolean expiration
 * @return boolean 
 */
function verify_ephemeral_key($key, $aditionnal_data_to_hash = '', $expiration=true)
{
	global $conf;
	$time = microtime(true);
	$key = explode( ':', @$key );
  
	if ( 
    count($key)!= 3
		or $key[0]>$time-(float)$key[1] // page must have been retrieved more than X sec ago
		or ( $key[0]<$time-3600 and $expiration ) // 60 minutes expiration
		or hash_hmac(
			  'md5', 
        $key[0].substr($_SERVER['REMOTE_ADDR'],0,5).$key[1].$aditionnal_data_to_hash, 
        SALT_KEY
        ) != $key[2] // verify key
	  )
	{
		return false;
	}
  
	return true;
}

/**
 * extract unique values of the specified key in a two dimensional array
 * @param array
 * @param mixed key name
 * @return array
 */
function array_unique_deep(&$array, $key)
{
  $values = array();
  foreach ($array as $k1 => $row)
  {
    foreach ($row as $k2 => $v)
    {
      if ($k2 == $key)
      {
        $values[ $k1 ] = $v;
        continue;
      }
    }
  }
  return array_unique($values);
}

function array_merge_ref(&$array, $pushes)
{
  if (!is_array($pushes)) return;
  $array = array_merge($array, $pushes);
}

/**
 * recursively merge two arrays with overwrites (Arr2 overwrites Arr1)
 * http://www.php.net/manual/en/function.array-merge-recursive.php#102379
 */
function array_merge_recursive_distinct($Arr1, $Arr2)
{
  foreach ($Arr2 as $key => $Value)
  {
    if ( array_key_exists($key, $Arr1) && is_array($Value) )
    {
      $Arr1[$key] = array_merge_recursive_distinct($Arr1[$key], $Arr2[$key]);
    }
    else
    {  
      $Arr1[$key] = $Value;
    }
  }
  return $Arr1;
}

/**
 * explode a string like name1,value1;name2,value2 into an associative array
 * @param string
 * @param char couple seperator
 * @param char name-value separator
 * @return array
 */
function explode_string($string, $sep1=';', $sep2=',')
{
  $string = preg_replace('#[;]$#', '', $string);
  $result = array();
  
  $a = explode($sep1, $string);
  foreach ($a as $s)
  {
     $v = explode($sep2, $s);
     $result[ $v[0] ] = $v[1];
  }
  
  return $result;
}

/**
 * implode an associative array into a string like name1,value1;name2,value2
 * @param array
 * @param char couple seperator
 * @param char name-value separator
 * @return string
 */
function implode_array($array, $sep1=';', $sep2=',') {
  $result = null;
  $first = true;
  
  foreach ($array as $key => $value)
  {
    if (is_array($key)) continue;
    if (!$first) $result.= $sep1;
    $result.= $key.$sep2.$value;
    $first = false;
  }
  
  return $result;
}

/**
 * replace all end-line characters by the configured one
 */
function clean_eol(&$val)
{
  global $conf;
  $val = str_replace(array("\r\n","\r","\n"), $conf['eol'], $val);
}

/**
 * fully delete a directory
 * @param string path
 * @return bool
 */
function rrmdir($dir)
{
  if (!is_dir($dir))
  {
    return false;
  }
  $dir = rtrim($dir, '/');
  $objects = scandir($dir);
  $return = true;
  
  foreach ($objects as $object)
  {
    if ($object !== '.' && $object !== '..')
    {
      $path = $dir.'/'.$object;
      if (filetype($path) == 'dir') 
      {
        rrmdir($path); 
      }
      else 
      {
        chmod($path, 0777);
        $return = $return && @unlink($path);
      }
    }
  }

  chmod($dir, 0777);
  return $return && @rmdir($dir);
} 

/**
 * search a string in array values
 * // http://www.strangeplanet.fr/blog/dev/php-une-fonction-pour-rechercher-dans-un-tableau
 * @param string needle
 * @param array haystack
 * @param bool return all instances
 * @param bool search in PCRE mode
 * @return key or array of keys
 */
function array_pos($needle, &$haystack, $match_all=false, $preg_mode=false)
{
  if ($match_all) $matches = array();
  
  foreach ($haystack as $i => $row)
  {
    if (!is_array($row))
    {
      if (!$preg_mode)
      {
        if (strpos($row, $needle) !== false)
        {
          if (!$match_all) return $i;
          else array_push($matches, $i);
        }
      }
      else
      {
        if (preg_match($needle, $row) === 1)
        {
          if (!$match_all) return $i;
          else array_push($matches, $i);
        }
      }
    }
  }
  
  if ( !$match_all or !count($matches) ) return false;
  return $matches;
}

/**
 * reverse a two dimensional array
 * @param array
 * @return array
 */
function reverse_2d_array(&$array)
{
  $out = array();
  
  foreach ($array as $key1 => $sub)
    foreach ($sub as $key2 => $value)
      $out[$key2][$key1] = $value;
      
  return $out;
}

/**
 * create a simple hash from a multi-dimentional array
 */
function simple_hash_from_array(&$array, $key, $value)
{
  $out = array();
  
  foreach ($array as $row)
  {
    if (isset($row[$key]))
    {
      $out[ $row[$key] ] = @$row[$value];
    }
  }
  
  return $out;
}

/**
 * format datetime to english date
 * @param string date
 * @param boolean show time
 * @return string
 */
function format_date($date, $show_time=false, $show_day=true)
{
  if (strpos($date, '0') == 0)
  {
    return 'N/A';
  }

  $ymdhms = array();
  $tok = strtok( $date, '- :');
  while ($tok !== false)
  {
    $ymdhms[] = $tok;
    $tok = strtok('- :');
  }

  if (count($ymdhms) < 3)
  {
    return false;
  }

  $formated_date = '';
  if ($show_day) 
  {
    $formated_date.= date('l', mktime(12,0,0,$ymdhms[1],$ymdhms[2],$ymdhms[0]));
  }
  $formated_date.= ' '.$ymdhms[2];
  $formated_date.= ' '.date('F', mktime(12,0,0,$ymdhms[1]));
  $formated_date.= ' '.$ymdhms[0];
  if ( $show_time and count($ymdhms) >= 5 )
  {
    $formated_date.= ' '.$ymdhms[3].':'.$ymdhms[4];
  }
  return $formated_date;
}

define('MKGETDIR_NONE', 0);
define('MKGETDIR_RECURSIVE', 1);
define('MKGETDIR_DIE_ON_ERROR', 2);
define('MKGETDIR_PROTECT_INDEX', 4);
define('MKGETDIR_PROTECT_HTACCESS', 8);
define('MKGETDIR_DEFAULT', 7);
/**
 * creates directory if not exists; ensures that directory is writable
 * @param string dir
 * @param int flags combination of MKGETDIR_xxx
 * @return bool
 */
function mkgetdir($dir, $flags=MKGETDIR_DEFAULT)
{
  if ( !is_dir($dir) )
  {    
    if (substr(PHP_OS, 0, 3) == 'WIN')
    {
      $dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);
    }
    
    $umask = umask(0);
    $mkd = @mkdir($dir, 0755, ($flags&MKGETDIR_RECURSIVE) ? true:false );
    umask($umask);
    
    if ($mkd==false)
    {
      !($flags&MKGETDIR_DIE_ON_ERROR) or trigger_error("$dir no write access", E_USER_ERROR);
      return false;
    }
  }
  
  if ( !is_writable($dir) )
  {
    !($flags&MKGETDIR_DIE_ON_ERROR) or trigger_error("$dir no write access", E_USER_ERROR);
    return false;
  }
  
  if( $flags&MKGETDIR_PROTECT_HTACCESS )
  {
    $file = $dir.'/.htaccess';
    file_exists($file) or @file_put_contents( $file, 'deny from all' );
  }
  
  if( $flags&MKGETDIR_PROTECT_INDEX )
  {
    $file = $dir.'/index.htm';
    file_exists($file) or @file_put_contents( $file, 'Not allowed!' );
  }
  
  return true;
}

/**
 * fix_magic_quotes undo what magic_quotes has done. The script was taken
 * from http://www.nyphp.org/phundamentals/storingretrieving.php
 */
function fix_magic_quotes($var = NULL, $sybase = NULL) {
  // if sybase style quoting isn't specified, use ini setting
  if (!isset($sybase)) {
    $sybase = ini_get('magic_quotes_sybase');
  }

  // if no var is specified, fix all affected superglobals
  if (!isset($var)) {
    // if magic quotes is enabled
    if (get_magic_quotes_gpc()) {
      // workaround because magic_quotes does not change $_SERVER['argv']
      $argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : NULL; 

      // fix all affected arrays
      foreach (array('_ENV', '_REQUEST', '_GET', '_POST', '_COOKIE', '_SERVER') as $var) {
        $GLOBALS[$var] = fix_magic_quotes($GLOBALS[$var], $sybase);
      }

      $_SERVER['argv'] = $argv;

      // turn off magic quotes, this is so scripts which are sensitive to
      // the setting will work correctly
      ini_set('magic_quotes_gpc', 0);
    }

    // disable magic_quotes_sybase
    if ($sybase) {
      ini_set('magic_quotes_sybase', 0);
    }

    // disable magic_quotes_runtime
    @set_magic_quotes_runtime(0);
    return TRUE;
  }

  // if var is an array, fix each element
  if (is_array($var)) {
    foreach ($var as $key => $val) {
      $var[$key] = fix_magic_quotes($val, $sybase);
    }

    return $var;
  }

  // if var is a string, strip slashes
  if (is_string($var)) {
    return $sybase ? str_replace ('\'\'', '\'', $var) : stripslashes ($var);
  }

  // otherwise ignore
  return $var;
}

?>