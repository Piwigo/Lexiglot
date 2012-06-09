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
// section
if ( !isset($_GET['section']) or !array_key_exists($_GET['section'], $conf['all_sections']) )
{
  array_push($page['errors'], 'Undefined or unknown section. <a class="floating_link" href="javascript:window.close();">Close</a>');
  print_page();
}

$page['language'] = $_GET['language'];
$page['section'] = $_GET['section'];
$page['files'] = explode(',', $conf['all_sections'][$_GET['section']]['files']);

// file
if ( !isset($_GET['file']) or !in_array($_GET['file'], $page['files']) )
{
  array_push($page['errors'], 'Undefined or unknown file.');
  print_page(false);
}

$page['file'] = $_GET['file'];

// +-----------------------------------------------------------------------+
// |                         GET ROWS
// +-----------------------------------------------------------------------+

$_LANG = load_language($page['section'], $page['language'], $page['file']);


// +-----------------------------------------------------------------------+
// |                         DISPLAY ROWS
// +-----------------------------------------------------------------------+  
echo '
<p class="caption">
  <a class="floating_link" href="javascript:window.close();">Close this window</a>
  '.get_section_name($page['section']).' &raquo; '.get_language_flag($page['language']).' '.get_language_name($page['language']).'
</p>';

echo '
<form id="diffs">
<fieldset class="common">
  <table class="common">';
  $i=0;
  foreach ($_LANG as $key => $row)
  {
    echo '
    <tr class="'.($i%2==0?'odd':'even').'">
      <td><pre>'.htmlspecialchars($key).'</pre></td>
      <td><pre>'.htmlspecialchars($row['row_value']).'</pre></td>
    </tr>';
    $i++;
  }
  echo '
  </table>
</fieldset>
</form>';


print_page(false);
?>