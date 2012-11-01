{include file="messages.tpl"}


{* <!-- add lang --> *}
<form action="{$F_ACTION}" method="post" enctype="multipart/form-data">
<fieldset class="common">
  <legend>Add a language</legend>
  
  <table class="search">
    <tr>
      <th>Id. (folder name) <span class="red">*</span></th>
      <th>Name <span class="red">*</span></th>
      <th>Flag</th>
      <th>Reference</th>
      <th>Priority</th>
      <th>Category</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="id" size="15"></td>
      <td><input type="text" name="name" size="20"></td>
      <td>
        <input type="file" name="flag">
        <input type="hidden" name="MAX_FILE_SIZE" value="10240">
      </td>
      <td>
        <select name="ref_id">
          <option value="" selected="selected">(default)</option>
        {foreach from=$CONF.all_languages item=lang}
          <option value="{$lang.id}">{$lang.name}</option>
        {/foreach}
        </select>
      </td>
      <td><input type="text" name="rank" size="2" value="1"></td>
      <td><input type="text" name="category_id" class="category"></td>
      <td><input type="submit" name="add_language" class="blue" value="Add"></td>
    </tr>
  </table>
  
</fieldset>
</form>

{* <!-- search lang --> *}
<form action="{$F_ACTION}" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>Name</th>
      <th>Flag</th>
      <th>Priority</th>
      <th>Category</th>
      <th>Entries</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="name" size="20" value="{$SEARCH.name}"></td>
      <td>
        <select name="flag">
          <option value="-1" {if -1==$SEARCH.flag}selected="selected"{/if}>-------</option>
          <option value="with" {if 'with'==$SEARCH.flag}selected="selected"{/if}>With flag</option>
          <option value="without" {if 'without'==$SEARCH.flag}selected="selected"{/if}>Without flag</option>
        </select>
      </td>
      <td><input type="text" name="rank" size="2" value="{$SEARCH.rank}"></td>
      <td>
        <select name="category">
          <option value="-1" {if -1==$SEARCH.category}selected="selected"{/if}>-------</option>
        {foreach from=$CATEGORIES item=row}
          <option value="{$row.id}" {if $row.id==$SEARCH.category}selected="selected"{/if}>{$row.name}</option>
        {/foreach}
        </select>
      </td>
      <td><input type="text" name="limit" size="3" value="{$SEARCH.limit}"></td>
      <td>
        <input type="submit" name="search" class="blue" value="Search">
        <input type="submit" name="erase_search" class="red tiny" value="Reset">
      </td>
    </tr>
  </table>
</fieldset>
</form>

{* <!-- langs list --> *}
<form id="languages" action="{$F_ACTION}{$NAV_PAGE}" method="post" enctype="multipart/form-data">
<fieldset class="common">
  <legend>Manage</legend>
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="name">Name</th>
        <th class="rank">Priority</th>
        <th class="category">Category</th>
        <th class="users">Translators</th>
        <th class="actions">Actions</th>
      </tr>
    </thead>
    <tbody>
    {foreach from=$LANGS item=row}
      <tr class="main {if $row.highlight}highlight{/if}">
        <td class="chkb">
          <input type="checkbox" name="select[]" value="{$row.id}">
        </td>
        <td class="name">
          <a href="{$lex->language_url($row.id)}">{$lex->language_flag($row.id, 'default')} {$row.name}</a>
        </td>
        <td class="rank">
          {$row.rank}
        </td>
        <td class="category">
          {$row.category_name}
        </td>
        <td class="users">
          <a href="{$row.users_uri}">{$row.total_users}</a>
        </td>
        <td class="actions">
          <a href="#" class="expand" data="{$row.id}" title="Edit this language"><img src="template/images/page_white_edit.png" alt="edit"></a>
          <a href="{$row.make_stats_uri}" title="Refresh stats"><img src="template/images/arrow_refresh.png"></a>
        {if $row.delete_uri}
          <a href="{$row.delete_uri}" title="Delete this language" onclick="return confirm('Are you sure?');"><img src="template/images/cross.png" alt="[x]"></a>
        {else}
          <span style="display:inline-block;margin-left:5px;width:16px;">&nbsp;</span>
        {/if}
        </td>
      </tr>
    {foreachelse}
      <tr>
        <td colspan="6" style="text-align:center;"><i>No results</i></td>
      </tr>
    {/foreach}
    </tbody>
  </table>
  
  <a href="#" class="selectAll">Select All</a> / <a href="#" class="unselectAll">Unselect all</a>
  <div class="pagination">{$PAGINATION}</div>
</fieldset>

<fieldset id="permitAction" class="common" style="display:none;margin-bottom:20px;">
  <legend>Global action <span class="unselectAll">[close]</span></legend>
  
  <select name="selectAction">
    <option value="-1">Choose an action...</option>
    <option disabled="disabled">------------------</option>
    <option value="make_stats">Refresh stats</option>
    <option value="delete_languages">Delete languages</option>
    <option value="change_rank">Change priority</option>
    <option value="change_category">Change category</option>
  </select>
  
  <span id="action_delete_languages" class="action-container">
    <label><input type="checkbox" name="confirm_deletion" value="1"> Are you sure ?</label>
  </span>
  
  <span id="action_change_rank" class="action-container">
    <input type="text" name="batch_rank" size="2">
  </span>
  
  <span id="action_change_category" class="action-container" style="position:relative;top:8px;"> <!-- manually correct the mispositionning of tokeninput block -->
    <input type="text" name="batch_category_id" class="category">
  </span>
  
  <span id="action_apply" class="action-container">
    <input type="submit" name="apply_action" class="blue" value="Apply">
  </span>
</fieldset>
</form>


{combine_script id="functions" path="template/js/functions.js" load="footer"}
{combine_script id="jquery.tablesorter" path="template/js/jquery.tablesorter.min.js" load="footer"}
{combine_script id="jquery.tokeninput" path="template/js/jquery.tokeninput.min.js" load="footer"}
{combine_css path="template/js/jquery.tablesorter.css"}
{combine_css path="template/js/jquery.tokeninput.css"}

{footer_script}{literal}
/* perform ajax request for language edit */
$("a.expand").click(function() {
  $trigger = $(this);
  language_id = $trigger.attr("data");
  $parent_row = $trigger.parents("tr.main");
  $details_row = $parent_row.next("tr.details");
  
  if (!$details_row.length) {
    $("a.expand img").attr("src", "template/images/page_white_edit.png");
    $("tr.details").remove();
    
    $trigger.children("img").attr("src", "template/images/page_edit.png");
    $parent_row.after('<tr class="details" id="details'+ language_id +'"><td class="chkb"></td><td colspan="5"><img src="template/images/load16.gif"> <i>Loading...</i></td></tr>');
    
    $container = $parent_row.next("tr.details").children("td:last-child");

    $.ajax({
      type: "POST",
      url: "admin/ajax.php",
      data: { "action":"get_language_form", "language_id": language_id }
    }).done(function(msg) {
      msg = $.parseJSON(msg);
      
      if (msg.errcode == "success") {
        $container.html(msg.data);
        $container.find("input.category").tokenInput(json_categories, {
          tokenLimit: 1,
          allowCreation: true,
          hintText: ""
        });
      }
      else {
        overlayMessage(msg.data, msg.errcode, $trigger);
      }
    });
  }
  else {
    $details_row.remove();
    $trigger.children("img").attr("src", "template/images/page_white_edit.png");
  }
  
  return false;
});

/* linked table rows follow hover state */
$(document).on("mouseenter", "tr.main", function() { $(this).next("tr.details").addClass("hover"); });
$(document).on("mouseleave", "tr.main", function() { $(this).next("tr.details").removeClass("hover"); });
$(document).on("mouseenter", "tr.details", function() { $(this).prev("tr.main").addClass("hover"); });
$(document).on("mouseleave", "tr.details", function() { $(this).prev("tr.main").removeClass("hover"); });

/* token input for categories */
var json_categories = [{/literal}{$CATEGORIES_JSON}{literal}];
$("input.category").tokenInput(json_categories, {
  tokenLimit: 1,
  allowCreation: true,
  hintText: ""
});

/* tablesorter */
$("#languages table").tablesorter({
  sortList: [[2,1],[1,0]],
  headers: { 0: {sorter: false}, 5: {sorter: false} },
  widgets: ["zebra"]
})
.bind("sortStart", function() { 
  $("tr.details").remove();
  $("a.expand img").attr("src", "template/images/page_white_edit.png");
});

/* actions */
function checkPermitAction() {
  var nbSelected = 0;

  $("td.chkb input[type=checkbox]").each(function() {
     if ($(this).is(":checked")) {
       nbSelected++;
     }
  });

  if (nbSelected == 0) {
    $("#permitAction").hide();
    $("#save_status").show();
  }
  else {
    $("#permitAction").show();
    $("#save_status").hide();
  }
}

$("[id^=action_]").hide();

$("td.chkb input[type=checkbox]").change(function () {
  checkPermitAction();
});

$(".selectAll").click(function() {
  $("td.chkb input[type=checkbox]").each(function() {
     $(this).attr("checked", true);
  });
  checkPermitAction();
  return false;
});
$(".unselectAll").click(function() {
  $("td.chkb input[type=checkbox]").each(function() {
     $(this).attr("checked", false);
  });
  checkPermitAction();
  return false;
});

$("select[name=selectAction]").change(function() {
  $("[id^=action_]").hide();
  $("#action_"+$(this).attr("value")).show();

  if ($(this).val() != -1) {
    $("#action_apply").show();
  }
  else {
    $("#action_apply").hide();
  }
});

$("td.id").click(function() {
  $checkbox = $(this).prev("td.chkb").children("input");
  $checkbox.attr("checked", !$checkbox.attr("checked"));
});
{/literal}{/footer_script}

{if $DEPLOY_LANGUAGE}
  {footer_script}
  $("a.expand[data='{$DEPLOY_LANGUAGE}']").trigger("click");
  {/footer_script}
{/if}