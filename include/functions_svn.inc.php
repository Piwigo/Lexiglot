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
 * /!\ don't know how to process yet /!\
 * @return bool
 */
function svn_check_connection()
{
  return true;
}

/**
 * commit a file or directory
 * @param string file
 * @param text description
 * @return string message or bool
 */
function svn_commit($file, $msg)
{
  global $conf, $page;
  exec($conf['svn_path'].' commit "'.$file.'" --message "'.str_replace('"',"'",$msg).'" --username '.$conf['svn_user'].' --password '.$conf['svn_password'].' 2>&1', $out);
  
  if ( ($i = array_pos('Committed revision', $out)) !== false )
  {
    return $out[$i];
  }
  else if (array_pos('Could not authenticate to server', $out) !== false)
  {
    array_push($page['errors'], 'Could not authenticate to SVN server, please check your configuration.');
    return false;
  }
  else
  {
    array_push($page['errors'], 'svn: An unknown error occured.');
    return false;
  }
}

/**
 * checkout a file or directory
 * @param string server path
 * @param string local path
 * @return string message or bool
 */
function svn_checkout($server, $local)
{
  global $conf, $page;
  exec($conf['svn_path'].' checkout "'.$server.'" "'.$local.'" --username '.$conf['svn_user'].' --password '.$conf['svn_password'].' 2>&1', $out);
  
  if ( ($i = array_pos('Checked out revision', $out)) !== false )
  {
    return $out[$i];
  }
  else if ( ($i = array_pos('doesn\'t exist', $out)) !== false)
  {
    array_push($page['errors'], $out[$i]);
    return false;
  }
  else if (array_pos('Could not authenticate to server', $out) !== false)
  {
    array_push($page['errors'], 'Could not authenticate to SVN server, please check your configuration.');
    return false;
  }
  else if ( ($i = array_pos("Can't make directory", $out)) !== false)
  {
    array_push($page['errors'], $out[$i]);
    return false;
  }
  else
  {
    array_push($page['errors'], 'svn: An unknown error occured.');
    return false;
  }
}

/**
 * switch a directory
 * @param string server path
 * @param string local path
 * @param string relocate (for repository changes)
 * @return string message or bool
 */
function svn_switch($server, $local, $relocate=false)
{
  global $conf, $page;
  exec($conf['svn_path'].' switch '.($relocate!=false?'--relocate "'.$relocate.'" ':null).'"'.$server.'" "'.$local.'" --username '.$conf['svn_user'].' --password '.$conf['svn_password'].' 2>&1', $out);
  
  if ( ($i = array_pos('Updated to revision', $out)) !== false )
  {
    return $out[$i];
  }
  else if ($out == array())
  {
    return 'Switched';
  }
  else if ($out[0] == 'svn: Target path does not exist')
  {
    array_push($page['errors'], $out[0].' ('.$server.')');
    return false;
  }
  else if (array_pos('Could not authenticate to server', $out) !== false)
  {
    array_push($page['errors'], 'Could not authenticate to SVN server, please check your configuration.');
    return false;
  }
  else
  {
    array_push($page['errors'], 'svn: An unknown error occured.');
    return false;
  }
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
  exec($conf['svn_path'].' revert "'.$file.'" '.($recursive ? '--recursive' : '').' 2>&1', $out);
  return $out;
}

/**
 * add file or directory, recursively if needed
 * @param string local path
 * @return array
 */
function svn_add($file)
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
    $current = $conf['local_dir'];
    // the aim is to search the first node not added yet
    foreach ($path as $folder)
    {
      $out = array();
      if ($folder.'/' != $conf['local_dir'])
      {
        $current.= $folder.'/';
        exec($conf['svn_path'].' add "'.$current.'" 2>&1', $out);
        // here we stop at first add because svn add is recursive
        if (array_pos('A         ', $out) !== false)
        {
          return $out;
        }
      }
    }
  }
  else
  {
    exec($conf['svn_path'].' add "'.$file.'" 2>&1', $out);
    return $out;
  }
}

?>