<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2013 Damien Sorel       http://www.strangeplanet.fr |
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

define('LEXIGLOT_PATH', './');
include(LEXIGLOT_PATH . 'include/common.inc.php');

$template->assign('NO_HEADER', true);
$template->block_html_style(array(), '#the_page { margin-top:20px; width:580px; }');


// +-----------------------------------------------------------------------+
// |                         PAGE OPTIONS
// +-----------------------------------------------------------------------+
// language
if ( !isset($_GET['language']) or !array_key_exists($_GET['language'], $conf['all_languages']) )
{
  array_push($page['errors'], 'Undefined or unknown language. <a class="floating_link" href="javascript:window.close();">Close</a>');
  $template->close('messages');
}
// project
if ( !isset($_GET['project']) or !array_key_exists($_GET['project'], $conf['all_projects']) )
{
  array_push($page['errors'], 'Undefined or unknown project. <a class="floating_link" href="javascript:window.close();">Close</a>');
  $template->close('messages');
}

$page['language'] = $_GET['language'];
$page['project'] = $_GET['project'];
$page['files'] = explode(',', $conf['all_projects'][$_GET['project']]['files']);

// file
if ( !isset($_GET['file']) or !in_array($_GET['file'], $page['files']) )
{
  array_push($page['errors'], 'Undefined or unknown file.');
  $template->close('messages');
}

$page['file'] = $_GET['file'];

$template->assign(array(
  'LANGUAGE' => $page['language'],
  'PROJECT' => $page['project'],
  'FILE' => $page['file'],
  ));

  
// +-----------------------------------------------------------------------+
// |                         DISPLAY FILE
// +-----------------------------------------------------------------------+
$_LANG = load_language($page['project'], $page['language'], $page['file']);

foreach ($_LANG as $key => $row)
{
  $template->append('ROWS', array(
    'key' => htmlspecialchars($key),
    'row_value' => htmlspecialchars($row['row_value']),
    ));
}


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('simple_view');

?>