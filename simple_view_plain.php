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

define('PATH', './');
include(PATH.'include/common.inc.php');

$page['header'].= '
<style type="text/css">#the_page { margin-top:20px; width:580px; }</style>';

// +-----------------------------------------------------------------------+
// |                         PAGE OPTIONS
// +-----------------------------------------------------------------------+
// language
if ( !isset($_GET['language']) or !array_key_exists($_GET['language'], $conf['all_languages']) )
{
  array_push($page['errors'], 'Undefined or unknown language. <a class="floating_link" href="javascript:window.close();">Close</a>');
  print_page();
}
// project
if ( !isset($_GET['project']) or !array_key_exists($_GET['project'], $conf['all_projects']) )
{
  array_push($page['errors'], 'Undefined or unknown project. <a class="floating_link" href="javascript:window.close();">Close</a>');
  print_page();
}
// display
if ( isset($_GET['display']) and in_array($_GET['display'], array('plain','normal')) )
{
  $page['display'] = $_GET['display'];
}
else
{
  $page['display'] = 'plain';
}

$page['language'] = $_GET['language'];
$page['project'] = $_GET['project'];
$page['files'] = explode(',', $conf['all_projects'][$_GET['project']]['files']);

// file
if ( !isset($_GET['file']) or !in_array($_GET['file'], $page['files']) )
{
  array_push($page['errors'], 'Undefined or unknown file.');
  print_page(false);
}

$page['file'] = $_GET['file'];

// +-----------------------------------------------------------------------+
// |                         GET FILE
// +-----------------------------------------------------------------------+
$_LANG = load_language($page['project'], $page['language'], $page['file']);
$_LANG = $_LANG[ $page['file'] ];


// +-----------------------------------------------------------------------+
// |                         DISPLAY FILE
// +-----------------------------------------------------------------------+  
echo '
<p class="caption">
  <a class="floating_link" href="javascript:window.close();">Close this window</a> <span class="floating_link">&nbsp;|&nbsp;</span>
  '.get_project_name($page['project']).' &raquo; '.get_language_flag($page['language']).' '.get_language_name($page['language']);

if ($page['display'] == 'plain')
{
  echo '
  <a class="floating_link" href="'.get_url_string(array('display'=>'normal')).'">View normal</a>';
}
else
{
  echo'
  <a class="floating_link" href="'.get_url_string(array('display'=>'plain')).'">View plain</a>';
}
  echo '
</p>';

echo '
<form id="diffs">
<fieldset class="common">
  <legend>File content</legend>';
  if ($page['display'] == 'plain')
  {
    echo '
    <script type="text/javascript">$(document).ready(function(){$("pre").css("height", $(window).height()-150);});</script>
    <pre style="white-space:pre-wrap;overflow-y:scroll;">'.htmlspecialchars($_LANG['row_value']).'</pre>';
  }
  else
  {
    echo '
    <script type="text/javascript">$(document).ready(function(){$("iframe").css("height", $(window).height()-150);});</script>
    <iframe src="'.$conf['local_dir'].$page['project'].'/'.$page['language'].'/'.$page['file'].'" style="width:100%;margin:0;"></iframe>';
  }
echo '
</fieldset>
</form>';

print_page(false);
?>