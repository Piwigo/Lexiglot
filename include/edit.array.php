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

// +-----------------------------------------------------------------------+
// |                         SAVE ROWS
// +-----------------------------------------------------------------------+
if ( isset($_POST['submit']) and $is_translator )
{
  // note that the key never expires, translation can take a while!
  if (!verify_ephemeral_key(@$_POST['key'], __FILE__, false))
  {
    array_push($page['errors'], 'Invalid/expired form key');
    print_page();
  }
  
  foreach ($_POST['rows'] as $row)
  {
    $key = $row['row_name'];
    $text = $row['row_value'];
    
    // test if the new value is really new (in file and in database)
    if (  
      !empty($text) and 
      ( 
        ( 
          ( !isset($_LANG[$key]) or $text != $_LANG[$key]['row_value'] ) 
          and ( !isset($_LANG_db[$key]) or $text != $_LANG_db[$key]['row_value'] ) 
        )
        or ( isset($_LANG_db[$key]) and $text != $_LANG_db[$key]['row_value'] )
      )
    )
    {
      $query = '
INSERT INTO `'.ROWS_TABLE.'`(
    lang,
    section,
    file_name,
    row_name,
    row_value,
    user_id,
    last_edit,
    status
  )
  VALUES(
    "'.$page['language'].'",
    "'.$page['section'].'",
    "'.$page['file'].'",
    "'.mres($key).'",
    "'.mres($text).'",
    "'.$user['id'].'",
    NOW(),
    "'.( isset($_LANG[$key]) ? 'edit' : 'new' ).'" 
  )
  ON DUPLICATE KEY UPDATE
    last_edit = NOW(),
    row_value = "'.mres($text).'",
    status = IF(status="done","edit",status)
;';
      mysql_query($query);
    }
  }
  
  make_stats($page['section'], $page['language']);
  $_SESSION['page_infos'][] = 'Strings saved';
  redirect();   
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
if (isset($_POST['erase_search']) or !isset($_GET['ks']))
{
  unset_session_var('edit_search');
  unset($_POST);
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
  // $_DIFFS = search_fulltext($_DIFFS, $search['needle'], $search['where']);
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
    ( !isset($_LANG[$key]) and !isset($_LANG_db[$key]) )
    or $page['display'] == 'all'
    or $in_search
  )
  {
    // skip arrays (too complicated)
    if (is_array($row['row_value'])) continue;
    
    // for row_value, database has priority
    $_DIFFS[$key] = isset($_LANG_db[$key]) 
                    ? $_LANG_db[$key] 
                    : (
                      isset($_LANG[$key]) 
                      ? $_LANG[$key] 
                      : array()
                      );
    // keep trace of source value
    $_DIFFS[$key]['original'] = is_source_language($page['language']) ? $row['row_name'] : $row['row_value'];
    $_DIFFS[$key]['row_name'] = $row['row_name'];
  }
  
  if ( isset($_LANG[$key]) or isset($_LANG_db[$key]) )
  {
    $translated++;
  }
  $total++;
}

// statistics
$_STATS = ($total != 0) ? min($translated/$total, 1) : 0;

if ($in_search)
{
  $_DIFFS = search_fulltext($_DIFFS, $search['needle'], $search['where']);
}


// +-----------------------------------------------------------------------+
// |                         PAGINATION
// +-----------------------------------------------------------------------+
$paging['TotalEntries'] = count($_DIFFS);
$paging['Entries'] = isset($_GET['entries']) ? intval($_GET['entries']) : $user['nb_rows'];
$paging['TotalPages'] = ceil($paging['TotalEntries']/$paging['Entries']);
$paging['Page'] = 
   isset($_GET['page']) ? 
    (
      $_GET['page'] > $paging['TotalPages'] ? 
        (
          $paging['TotalPages'] == 0 ? 
            1 : $paging['TotalPages']
        ) :
        (
          intval($_GET['page']) == 0 ? 
            1 : intval($_GET['page'])
        )
    ) : 1;
$paging['Start'] = ($paging['Page']-1) * $paging['Entries'];

$_DIFFS = array_slice($_DIFFS, $paging['Start'], $paging['Entries'], true);


// +-----------------------------------------------------------------------+
// |                         DISPLAY ROWS
// +-----------------------------------------------------------------------+  
// legend
echo '
<div class="pagination">'.pagination($paging).'</div>
<div id="display_buttons">
  <a href="'.get_url_string(array('display'=>'all'), array('page','ks')).'" class="all '.($page['display']=='all'?'active display':null).'">All</a>
  <a href="'.get_url_string(array('display'=>'missing'), array('page','ks')).'" class="missing '.($page['display']=='missing'?'active display':null).'">Untranslated</a>
  <a href="#" class="search '.($page['display']=='search'?'active display':null).'">Search</a>
</div>
<div style="clear:both;"></div>';

// search field
echo '
<form method="post" action="'.get_url_string(array('ks'=>null), array('page')).'" id="diffs_search" style="'.(!$in_search ? 'display:none;' : null).'">
<fieldset class="common">
  <input type="text" name="needle" size="50" value="'.$search['needle'].'">
  &nbsp;&nbsp;Where ? 
    <label><input type="radio" name="where" value="row_value" '.($search['where']=='row_value' ? 'checked="checked"' : null).'> Translations</label> 
    <label><input type="radio" name="where" value="original" '.($search['where']=='original' ? 'checked="checked"' : null).'> Source</label>
  &nbsp;&nbsp;<input type="submit" name="search" value="Search" class="blue">
</fieldset>
</form>';

// strings list
echo '
<form method="post" action="" id="diffs" style="margin-top:10px;">
<fieldset class="common">
  <!--<legend>'.($in_search ? 'Search results' : ($page['display'] == 'all' ? 'All strings' : 'Untranslated strings')).'</legend>-->
  
  
  <table class="common">';
  $i=1;
  foreach ($_DIFFS as $key => $row)
  {
    // make sure to initialize the var
    $text = isset($row['row_value']) ? $row['row_value'] :null;
    
    // 'edit' status is displayed as 'new' on front-end
    $status = !isset($row['row_value'])
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
    $original = $in_search && $search['where'] == 'original'
             ? highlight_search_result(htmlspecialchars($row['original']), array_merge(array($search['needle']), $search['words'])) 
             : htmlspecialchars($row['original']);

    echo '
    <tr class="main '.($i%2!=0?'odd':'even').' '.$status.'">
      <td class="original"><pre>'.$original.'</pre></td>
      <td class="translation">
        <textarea name="rows['.$i.'][row_name]" style="display:none;">'.proper_utf8($key).'</textarea>';
        if ($is_translator)
        { // textarea with dynamic height, highlight is done in javascript
          echo '
          <textarea name="rows['.$i.'][row_value]" style="height:'.max(count_lines(!empty($text)?$text:$row['original'], 68)*1.1, 2.1).'em;" tabindex="'.$i.'">'.proper_utf8($text).'</textarea>';
        }
        else if (!empty($text))
        { // highlight value in case of read-only display
          echo '
          <pre>'.($in_search && $search['where'] == 'row_value' ? highlight_search_result(htmlspecialchars($text), array_merge(array($search['needle']), $search['words']))  : htmlspecialchars($text)).'</pre>';
        }
        else if (is_visitor())
        {
          echo '
          <p class="login">Not translated yet.</p>';
        }
        else
        {
          echo '
          <p class="login">You <a href="user.php?login">have to login</a> to add a translation.</p>';
        }
      echo '
      </td>
      <td class="details">
        '.(!is_source_language($page['language']) ? '<a href="#" class="expand tiptip" title="Details" data="'.$i.'"><img src="template/images/magnifier_zoom_in.png" alt="[+]"></a>' : null).'
        '.($is_translator ? '<a href="#" class="save tiptip" title="Save this string" data="'.$i.'"><img src="template/images/disk.png" alt="save"></a>' : null).'
      </td>
    </tr>
    <tr class="details '.($i%2!=0?'odd':'even').' '.$status.'" style="display:none;">
      <td class="original">
        <h5>String identifier :</h5>
        <pre>'.htmlspecialchars($key) /* only place where we display $lang keys */.'</pre>
      </td>
      <td class="translation"></td>
      <td class="details"></td>
    </tr>';
    
    $i++;
  }
  if (count($_DIFFS) == 0)
  {
    echo '
    <div class="ui-state-warning" style="padding: 0.7em;margin-bottom:10px;">
      <span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.7em;"></span>
      No result
    </div>';
  }
  echo '
  </table>
  
  '.(count($_DIFFS) >= 20 ? '<div class="pagination">'.pagination($paging).'</div>' : null).'
  <div class="centered">
    '.($is_translator && count($_DIFFS) != 0 ? '<input type="hidden" name="key" value="'.get_ephemeral_key(3, __FILE__).'">
    <input type="submit" name="submit" class="blue big" value="Save all" tabindex="'.($i+1).'">' : null).'
  </div>
</fieldset>
</form>

<table class="legend">
  <tr>
    <td><span>&nbsp;</span> Up-to-date strings</td>
    <td><span class="missing">&nbsp;</span> Untranslated strings</td>
    <td><span class="new">&nbsp;</span> Newly translated strings</td>
  </tr>
</table>';

// statistics
if ($conf['use_stats'])
{
  echo '
  <div id="displayStats" class="ui-state-highlight" style="padding: 0em;margin-top:10px;">
    <p style="margin:10px;">
      <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
      <b>File progression :</b> '.display_progress_bar($_STATS, 850, true).'
    </p>
  </div>';
}


// +-----------------------------------------------------------------------+
// |                         SCRIPTS
// +-----------------------------------------------------------------------+
$page['header'].= '
<script type="text/javascript" src="template/js/functions.js"></script>';

$page['script'].= '
// linked table rows follow hover state
$("tr.main").hover(
  function() { $(this).next("tr").addClass("hover"); },
  function() { $(this).next("tr").removeClass("hover"); }
);
$("tr.details").hover(
  function() { $(this).prev("tr").addClass("hover"); },
  function() { $(this).prev("tr").removeClass("hover"); }
);

// toggle search form
$("#display_buttons a.search").click(function() {
  if ("'.$page['display'].'" != "search") {
    if ($(this).hasClass("active")) {
      $("#display_buttons a").removeClass("active");
      $("#display_buttons a.display").addClass("active");
      $("#diffs_search").hide("slow");
    } else {
      $("#display_buttons a").removeClass("active");
      $(this).addClass("active");
      $("#diffs_search").show("slow");
    }
  }
  return false;
});

$("#display_buttons a:not(.search)").click(function() {
  if ($(this).hasClass("'.$page['display'].'")) {
    $("#display_buttons a").removeClass("active");
    $(this).addClass("active");
    $("#diffs_search").hide("slow");
    return false;
  } else {
    return true;
  }
});

// perform ajax request for string details
$("a.expand").click(function() {
  $trigger = $(this);
  $details_row = $(this).parents("tr.main").next("tr.details");
  
  if ($details_row.css("display") == "none") {
    $("a.expand img").attr("src", "template/images/magnifier_zoom_in.png");
    $("tr.details").hide();
    
    $trigger.children("img").attr("src", "template/images/magnifier_zoom_out.png");
    $details_row.show();
    
    $container = $details_row.children("td.translation");
    if ($container.hasClass("loaded") == false)
    {
      row_name = $("textarea[name=\'rows["+ $(this).attr("data") +"][row_name]\']").val();
      $container.html("<p><img src=\"template/images/load16.gif\"> <i>Loading...</i></p>");
    
      $.ajax({
        type: "POST",
        url: "ajax.php",
        data: { "action":"row_log", "section":"'.$page['section'].'", "language":"'.$page['language'].'", "file":"'.$page['file'].'", "key":"'.get_ephemeral_key(0).'", "row_name": utf8_encode(row_name) }
      }).done(function(msg) {
        msg = $.parseJSON(msg);
        if (msg.errcode == "success") {
          $container.addClass("loaded").html("<p>"+ msg.data +"</p>");
        }  else {
          overlayMessage(msg.data, msg.errcode, $trigger);
        }
      });
    }
  } else {
    $trigger.children("img").attr("src", "template/images/magnifier_zoom_in.png");
    $details_row.hide();
  }
  
  return false;
});';
  
if ($is_translator)
{
  $page['script'].= '
  // perform ajax request to save string value
  $("a.save").click(function() {
    $trigger = $(this);
    row_name = $("textarea[name=\'rows["+ $(this).attr("data") +"][row_name]\']").val();
    row_value = $("textarea[name=\'rows["+ $(this).attr("data") +"][row_value]\']").val();
    
    $.ajax({
      type: "POST",
      url: "ajax.php",
      data: { "action":"save_row", "section":"'.$page['section'].'", "language":"'.$page['language'].'", "file":"'.$page['file'].'", "key":"'.get_ephemeral_key(2).'", "row_name": utf8_encode(row_name), "row_value": utf8_encode(row_value) }
    }).done(function(msg) {
      msg = $.parseJSON(msg);
      if (msg.errcode == "success") {
        $trigger.parents("tr.main").removeClass("missing").addClass("new");
        overlayMessage(msg.data, "highlight", $trigger);
      }  else {
        overlayMessage(msg.data, msg.errcode, $trigger);
      }
    });
    
    return false;
  });';
}

if ( $in_search and $search['where'] == 'row_value' )
{
  load_jquery('highlighttextarea');
  
  $page['script'].= '
  $("textarea:visible").highlightTextarea({
    words: ["'.implode('","', array_merge(array($search['needle']), $search['words'])).'"],
    caseSensitive: false,
    resizable: true
  });';
  
  $block_autoresize = true; // autoResize must be blocked, incompatibel with highlightTextarea
}

?>