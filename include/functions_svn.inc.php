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
 
defined('PATH') or die('Hacking attempt!'); 

/**
 * check the connection to the subversion server
 * /!\ don't know how to proceed yet /!\
 * @return bool
 */
function svn_check_connection()
{
  return true;
}

/**
 * commit a file or directory
 * @param string local path
 * @param text description
 * @return array
 */
function svn_commit($file, $message)
{
  global $conf;
  exec($conf['svn_path'].' commit "'.$file.'" --message "'.str_replace('"',"'",$message).'" --username '.$conf['svn_user'].' --password '.$conf['svn_password'].' 2>&1', $out);
  
  if (($i = array_pos('Committed revision', $out)) !== false)
  {
    $level = 'success';
    $msg = $out[$i];
  } 
  else if (empty($out))
  {
    $level = 'success';
    $msg = 'Nothing to commit';
  }
  else if (($i = array_pos('is not under version control', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[$i];
  }
  else if (array_pos('Could not authenticate to server', $out) !== false)
  {
    $level = 'error';
    $msg = 'Could not authenticate to server';
  }
  else
  {
    $level = 'error';
    $msg = 'An unknown error occured';
    var_dump($out);
  }
  
  return array('level'=>$level, 'msg'=>$msg);
}

/**
 * checkout a file or directory
 * @param string server path
 * @param string local path
 * @param mixed revision
 * @return array
 */
function svn_checkout($server, $local, $revision='HEAD')
{
  global $conf;
  exec($conf['svn_path'].' checkout "'.$server.'" "'.$local.'" --revision "'.$revision.'" --username '.$conf['svn_user'].' --password '.$conf['svn_password'].' 2>&1', $out);
    
  if (($i = array_pos('Checked out revision', $out)) !== false)
  {
    $level = 'success';
    $msg = $out[$i];
  }
  else if (($i = array_pos('Updated to revision', $out)) !== false)
  {
    $level = 'success';
    $msg = $out[$i];
  }
  else if (($i = array_pos('doesn\'t exist', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[$i];
  }
  else if (($i = array_pos('Unable to connect to a repository at', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[i];
  }
  else if (($i = array_pos('is already a working copy for a different URL', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[i];
  }
  else if (($i = array_pos('Can\'t make directory', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[i];
  }
  else if (array_pos('Could not authenticate to server', $out) !== false)
  {
    $level = 'error';
    $msg = 'Could not authenticate to server';
  }
  else
  {
    $level = 'error';
    $msg = 'An unknown error occured';
    var_dump($out);
  }
  
  return array('level'=>$level, 'msg'=>$msg);
}

/**
 * switch or relocate a working directory
 * @param string server path
 * @param string local path
 * @param string relocate path 'from' ($server is 'to')
 * @return array
 */
function svn_switch($server, $local, $relocate=false)
{
  global $conf, $page;
  exec($conf['svn_path'].' switch '.($relocate ? '--relocate "'.$relocate.'" ' : null).'"'.$server.'" "'.$local.'" --username '.$conf['svn_user'].' --password '.$conf['svn_password'].' 2>&1', $out);
  
  if (($i = array_pos('Updated to revision', $out)) !== false)
  {
    $level = 'success';
    $msg = $out[$i];
  }
  else if (($i = array_pos('At revision', $out)) !== false)
  {
    $level = 'success';
    $msg = $out[i];
  }
  else if (empty($out))
  {
    $level = 'success';
    $msg = '\''.$local.'\' relocated from \''.$relocate.'\' to \''.$server.'\'';
  }
  else if (array_pos('is not the same repository as', $out) !== false)
  {
    $level = 'error';
    $msg = '\''.$server.'\' is not the good repository for \''.$local.'\'';
  }
  else if (array_pos('Invalid source URL prefix', $out) !== false)
  {
    $level = 'error';
    $msg = '\''.$server.'\' is not valid source repository for \''.$local.'\'';
  }
  else if (($i = array_pos('Unable to connect to a repository at', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[i];
  }
  else if (($i = array_pos('was not found', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[i];
  }
  else if (($i = array_pos('path not found', $out)) !== false)
  {
    $level = 'error';
    $msg = 'svn: E160013: \''.$server.'\' path not found';
  }
  else if (array_pos('Could not authenticate to server', $out) !== false)
  {
    $level = 'error';
    $msg = 'Could not authenticate to server';
  }
  else
  {
    $level = 'error';
    $msg = 'An unknown error occured';
    var_dump($out);
  }
  
  return array('level'=>$level, 'msg'=>$msg);
}

/**
 * revert modifications done to a file or directory
 * @param string local path
 * @param bool recursive
 * @return array
 */
function svn_revert($file, $recursive=true)
{
  global $conf;
  exec($conf['svn_path'].' revert "'.$file.'" '.($recursive ? '--recursive' : null).' 2>&1', $out);
  
  if (array_pos('Reverted', $out) !== false)
  {
    $level = 'success';
    $msg = 'Reverted \''.$file.'\'';
  }
  else if (empty($out))
  {
    $level = 'success';
    $msg = 'Nothing to revert';
  }
  else if (($i = array_pos('Skipped', $out)) !== false)
  {
    $level = 'warning';
    $msg = $out[i];
  }
  else
  {
    $level = 'error';
    $msg = 'An unknown error occured';
    var_dump($out);
  }
  
  return array('level'=>$level, 'msg'=>$msg);
}

/**
 * add file or directory,
 * @param string local path
 * @param bool recursive
 * @return array
 */
function svn_add($file, $recursive=false)
{
  if ($recursive) return svn_add_recursive($file);
  
  global $conf;  
  exec($conf['svn_path'].' add "'.$file.'" 2>&1', $out);
  
  if (array_pos('A ', $out) !== false)
  {
    $level = 'success';
    $msg = '\''.$file.'\' added';
  } 
  else if (($i = array_pos('is already under version control', $out)) !== false)
  {
    $level = 'warning';
    $msg = $out[$i];
  }
  else if (($i = array_pos('not found', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[$i];
  }
  else if (($i = array_pos('is not a working copy', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[$i];
  }
  else
  {
    $level = 'error';
    $msg = 'An unknown error occured';
    var_dump($out);
  }
  
  return array('level'=>$level, 'msg'=>$msg);
}

/**
 * recursively add a file or directory
 * the aim is to search the first node not added yet
 * @param string local path
 * @return array
 */
function svn_add_recursive($file)
{
  global $conf;
  
  if (strpos($file, '/') !== false)
  {
    $path = explode('/', $file);
  }
  else if (strpos($file, '\\') !== false)
  {
    $path = explode('\\', $file);
  }
  
  if (isset($path))
  {
    $current = null;
    
    foreach ($path as $folder)
    {
      $current.= $folder.'/';
      $out = svn_add($current);
      
      if ($out['level'] == 'success')
      {
        return $out;
      }
    }
    
    return $out;
  }
  else
  {
    return svn_add($file);
  }
}

/**
 * create and add a directory
 * @param string local path
 * @param bool recursive
 * @return array
 */
function svn_mkdir($path, $recursive=true)
{
  global $conf;
  exec($conf['svn_path'].' mkdir '.($recursive ? '--parents' : null).' "'.$path.'" 2>&1', $out);

  if (array_pos('A ', $out) !== false)
  {
    $level = 'success';
    $msg = '\''.$path.'\' added';
  }
  else if (($i = array_pos('Can\'t create directory', $out)) !== false)
  {
    $level = 'error';
    $msg = $out[i];
  }
  else
  {
    $level = 'error';
    $msg = 'An unknown error occured';
    var_dump($out);
  }
  
  return array('level'=>$level, 'msg'=>$msg);
}

?>