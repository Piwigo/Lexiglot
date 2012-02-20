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
<link type="text/css" rel="stylesheet" media="screen" href="template/public.css">
<style type="text/css">#the_page { margin-top:20px; width:580px; }</style>';

// +-----------------------------------------------------------------------+
// |                         PAGE OPTIONS
// +-----------------------------------------------------------------------+
// language
if ( !isset($_GET['language']) or !array_key_exists($_GET['language'], $conf['all_languages']) )
{
  array_push($page['errors'], 'Undefined or unknown language.');
  echo '<a href="'.get_url_string(array('section'=>$_GET['section']), 'all', 'section').'">Go Back</a>';
  print_page();
}
// section
if ( !isset($_GET['section']) or !array_key_exists($_GET['section'], $conf['all_sections']) )
{
  array_push($page['errors'], 'Undefined or unknown section.');
  echo '<a href="'.get_url_string(array('language'=>$_GET['language']), 'all', 'language').'">Go Back</a>';
  print_page();
}

$page['language'] = $_GET['language'];
$page['section'] = $_GET['section'];
$page['directory'] = $conf['local_dir'].$page['section'].'/';
$page['files'] = explode(',', $conf['all_sections'][$_GET['section']]['files']);

// file
if ( !isset($_GET['file']) or !in_array($_GET['file'], $page['files']) )
{
  array_push($page['errors'], 'Undefined or unknown file.');
  echo '<a href="javascript:window.close();">Close</a>';
  print_page(false);
}

$page['file'] = $_GET['file'];

// +-----------------------------------------------------------------------+
// |                         GET ROWS
// +-----------------------------------------------------------------------+

$_LANG = load_language_file($page['directory'].$page['language'].'/'.$page['file']);
$_LANG_db = load_language_db($page['language'], $page['file'], $page['section']);


// +-----------------------------------------------------------------------+
// |                         DISPLAY ROWS
// +-----------------------------------------------------------------------+  
echo '
<p class="caption">
  <a class="floating_link" href="javascript:window.close();">Close this window</a>
  '.get_section_name($page['section']).' &raquo; '.get_language_flag($page['language']).' '.get_language_name($page['language']).'
</p>';

// database rows
if (count($_LANG_db))
{
  echo '
  <form id="diffs">
  <fieldset class="common">
    <legend>Database rows</legend>
    <table class="common">';
    $i=0;
    foreach ($_LANG_db as $key => $row)
    {
      if (is_array($row['row_value'])) continue; // we skip arrays (too complicated)
                      
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
}

// file rows
if (count($_LANG))
{
  echo '
  <form id="diffs">
  <fieldset class="common">
    <legend>File rows</legend>
    <table class="common">';
    $i=0;
    foreach ($_LANG as $key => $row)
    {
      if (is_array($row['row_value'])) continue; // we skip arrays (too complicated)
                      
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
}

print_page(false);
?>