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

defined('LEXIGLOT_PATH') or die('Hacking attempt!');

$unsaved = array();

// +-----------------------------------------------------------------------+
// |                         SAVE ROWS
// +-----------------------------------------------------------------------+
if ( isset($_POST['submit']) and $is_translator )
{
  // note that the key never expires, translation can take a while!
  if (!verify_ephemeral_key(@$_POST['key'], '', false))
  {
    array_push($page['errors'], 'Invalid/expired form key');
  }
  else
  {
    foreach ($_POST['rows'] as $row)
    {
      $key = $row['row_name'];
      $text = $row['row_value'];
      clean_eol($text);
      
      if (
        !empty($text) and 
        ( !isset($_LANG[$key]) or $text!=$_LANG[$key]['row_value'] )
      )
      {
        if ($text!=$conf['equal_to_ref'] && !check_sprintf($_LANG_default[$key]['row_value'], $text))
        {
          $unsaved[$key] = $text;
        }
        else
        {
          $status = isset($_LANG[$key]) ? 'edit' : 'new';
          $query = '
INSERT INTO `'.ROWS_TABLE.'`(
    language,
    project,
    file_name,
    row_name,
    row_value,
    user_id,
    last_edit,
    status
  )
  VALUES(
    "'.$page['language'].'",
    "'.$page['project'].'",
    "'.$page['file'].'",
    "'.mres($key).'",
    "'.mres($text).'",
    "'.$user['id'].'",
    NOW(),
    "'.$status.'" 
  )
  ON DUPLICATE KEY UPDATE
    last_edit = NOW(),
    row_value = "'.mres($text).'",
    status = IF(status="done","edit",status)
;';
          $db->query($query);
          
          $_LANG[$key]['row_value'] = $text;
          $_LANG[$key]['status'] = $status;
        }
      }
    }
    
    make_stats($page['project'], $page['language']);
    
    $page['infos'][] = 'Strings saved';
    
    if (count($unsaved))
    {
      $page['errors'][] = 'Number of "%s" and/or "%d" mismatch in '. count($unsaved) .' strings.';
    }
  }
}

// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$in_search = false;
$search = array(
  'needle' => null,
  'where' => 'row_value'
  );

// erase search
$referer = !empty($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : array('path'=>'edit.php');

if ( isset($_POST['erase_search']) or basename($referer['path']) != 'edit.php' or isset($_GET['erase_search']) )
{
  unset_session_var('edit_search');
  unset($_POST);
  // QUERY_STRING is used by get_url_string, in order to not write everywhere that it must delete the parameter, we remove it here
  $_SERVER['QUERY_STRING'] = preg_replace('#(&?erase_search)#', null, $_SERVER['QUERY_STRING']);
}
// get saved search
else if (get_session_var('edit_search') != null)
{
  $search = unserialize(get_session_var('edit_search'));
}

// get form search
if (isset($_POST['search']))
{
  unset_session_var('edit_search');
  if (!empty($_POST['needle'])) $search['needle'] = $_POST['needle'];
  if (!empty($_POST['where']))  $search['where'] = $_POST['where'];
}

// apply search
if (!empty($search['needle']))
{
  $in_search = true;
  set_session_var('edit_search', serialize($search));
  $search['words'] = get_fulltext_words($search['needle']);
  $page['display'] = 'search';
}


// +-----------------------------------------------------------------------+
// |                         COMPUTE ROWS
// +-----------------------------------------------------------------------+
$_DIFFS = array();
$total = $translated = 0;

foreach ($_LANG_default as $key => $row)
{
  if (
    $in_search                                                     // display search results
    or $page['display']=='all'                                     // display all
    or ( $page['display']=='missing'    and !isset($_LANG[$key]) ) // display missing
    or ( $page['display']=='translated' and isset($_LANG[$key]) )  // display translated
  )
  {
    $_DIFFS[$key] = isset($_LANG[$key]) ? $_LANG[$key] : array();
    // keep trace of source value
    $_DIFFS[$key]['original'] = is_default_language($page['language']) ? $row['row_name'] : $row['row_value'];
    
    if (array_key_exists($key, $unsaved))
    {
      $_DIFFS[$key]['row_value'] = $unsaved[$key];
      $_DIFFS[$key]['error'] = true;
    }
  }
  
  // stats
  if (isset($_LANG[$key]))
  {
    $translated++;
  }
  $total++;
}

// statistics
$_STATS = ($total != 0) ? min($translated/$total, 1) : 0;

if ($conf['use_stats'])
{
  $template->assign('PROGRESS_BAR', display_progress_bar($_STATS, 850, true));
}

if ($in_search)
{
  $_DIFFS = search_fulltext($_DIFFS, $search['needle'], $search['where']);
}

// available for reference
$stats = get_cache_stats($page['project'], null, 'language');
$reference_languages = array();
foreach ($conf['all_languages'] as $row)
{
  if ( isset($stats[ $row['id'] ]) and $stats[ $row['id'] ] > $conf['minimum_progress_for_language_reference'] )
  {
    $row['switch_url'] = get_url_string(array('ref'=>$row['id']));
    $row['selected'] = $row['id']==$page['ref'];
    array_push($reference_languages, $row);
  }
}

$this_lang_ref = get_language_ref($page['language']);
$template->assign(array(
  'reference_languages' => $reference_languages,
  'DISPLAY_REFERENCE_WARNING' => !is_default_language($page['ref']) && $page['ref']!=$this_lang_ref,
  'HAS_REF_LANGUAGE' => !is_default_language($this_lang_ref) && $page['ref']==$this_lang_ref,
  ));
  
  
// +-----------------------------------------------------------------------+
// |                         PAGINATION
// +-----------------------------------------------------------------------+
$paging = compute_pagination(count($_DIFFS), isset($_GET['entries'])?intval($_GET['entries']):$user['nb_rows'], 'page');
$template->assign('PAGINATION', display_pagination($paging));

$_DIFFS = array_slice($_DIFFS, $paging['Start'], $paging['Entries'], true);


// +-----------------------------------------------------------------------+
// |                         DISPLAY ROWS
// +-----------------------------------------------------------------------+  
// toolbar
$template->assign('DISPLAY', array(
  'url_all' => get_url_string(array('display'=>'all','erase_search'=>null), array('page')),
  'url_missing' => get_url_string(array('display'=>'missing','erase_search'=>null), array('page')),
  'mode' => $page['display'],
  ));
  
// search field
$template->assign('SEARCH', array_merge( 
  $search, 
  array('url' => get_url_string(array(), array('page')))
  ));

// strings list
$i = 1;
foreach ($_DIFFS as $key => $row)
{
  $tpl_var = array(
    'i' => $i,
    'ROW_VALUE' => isset($row['row_value']) ? htmlspecialchars_utf8($row['row_value']) : null,
    'error' => isset($row['error']),
    );
  
  
  // 'edit' status is displayed as 'new' on front-end
  $tpl_var['STATUS'] = !isset($row['row_value'])
                        ? 'missing'
                        : (
                          isset($row['status']) // came from db
                          ? (
                            $row['status'] == 'edit'
                            ? 'new'
                            : $row['status'] // 'new' or 'done'
                            )
                          : 'done' // came from file
                          );
  
  // original value can be highlighted
  $tpl_var['ORIGINAL'] = htmlspecialchars_utf8($row['original']);
  $tpl_var['ORIGINAL'] = ($in_search && $search['where']=='original')
                         ? highlight_search_result($tpl_var['ORIGINAL'], array_merge(array($search['needle']), $search['words'])) 
                         : $tpl_var['ORIGINAL'];
                         
  $tpl_var['KEY'] = htmlspecialchars_utf8($key);
  
  if ($is_translator)
  { // textarea with dynamic height, highlight is done in javascript
    $area_lines = count_lines(!empty($text)?$text:$row['original'], 68);
    $tpl_var['FIELD'] = '<textarea name="rows['.$i.'][row_value]" style="height:'.max($area_lines*1.1, 2.1).'em;" tabindex="'.$i.'">'.$tpl_var['ROW_VALUE'].'</textarea>';
  }
  else if (!empty($text))
  { // highlight value in case of read-only display
    $tpl_var['FIELD'] = '<pre>'.(($in_search && $search['where']=='row_value') ? highlight_search_result($tpl_var['ROW_VALUE'], array_merge(array($search['needle']), $search['words'])) : $tpl_var['ROW_VALUE']).'</pre>';
  }
  else if (is_guest())
  {
    $tpl_var['FIELD'] = '<p class="login">You <a href="user.php?login">have to login</a> to add a translation.</p>';
  }
  else
  {
    $tpl_var['FIELD'] = '<p class="login">Not translated yet.</p>';
  }
  
  $template->append('DIFFS', $tpl_var);
  $i++;
}

if ( $in_search and $search['where'] == 'row_value' )
{
  $template->assign('NO_AUTORESIZE', true);
}

?>