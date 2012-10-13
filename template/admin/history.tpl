{include file="messages.tpl"}

{$ROW_POPUP}


{* <!-- search rows --> *}
<form action="{$F_ACTION}" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>User</th>
      <th>Language</th>
      <th>Project</th>
      <th>Status</th>
      <th>Entries</th>
      <th></th>
    </tr>
    <tr>
      <td>
        <select name="user_id">
          <option value="-1" {if -1==$SEARCH.user_id}selected="selected"{/if}>-------</option>
        {foreach from=$USERS item=row}
          <option value="{$row.id}" {if $row.id==$SEARCH.user_id}selected="selected"{/if}>{$row.username}</option>
        {/foreach}
        </select>
      </td>
      <td>
        <select name="language">
          <option value="-1" {if -1==$SEARCH.language}selected="selected"{/if}>-------</option>
        {foreach from=$CONF.all_languages item=row}
          <option value="{$row.id}" {if $row.id==$SEARCH.language}selected="selected"{/if}>{$row.name}</option>
        {/foreach}
        </select>
      </td>
      <td>
        <select name="project">
          <option value="-1" {if -1==$SEARCH.project}selected="selected"{/if}>-------</option>
        {foreach from=$displayed_projects item=row}
          <option value="{$row.id}" {if $row.id==$SEARCH.project}selected="selected"{/if}>{$row.name}</option>
        {/foreach}
        </select>
      </td>
      <td>
        <select name="status">
          <option value="-1" {if -1==$SEARCH.status}selected="selected"{/if}>-------</option>
          <option value="new" {if 'new'==$SEARCH.status}selected="selected"{/if}>Added</option>
          <option value="edit" {if 'edit'==$SEARCH.status}selected="selected"{/if}>Modified</option>
          <option value="done" {if 'done'==$SEARCH.status}selected="selected"{/if}>Commited</option>
        </select>
      </td>
      <td>
        <input type="text" size="3" name="limit" value="{$SEARCH.limit}">
      </td>
      <td>
        <input type="submit" name="search" class="blue" value="Search">
        <input type="submit" name="erase_search" class="red tiny" value="Reset">
      </td>
    </tr>
  </table>
</fieldset>
</form>

{* <!-- rows list --> *}
<form action="{$F_ACTION}" method="post" id="last_modifs">
<fieldset class="common">
  <legend>History</legend>
  
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="language">Language</th>
        <th class="project">Project</th>
        <th class="file">File</th>
        <th class="user">User</th>
        <th class="date">Date</th>
        <th class="value">Content</th>
        <th class="actions"></th>
      </tr>
    </thead>
    <tbody>
    {foreach from=$ROWS item=row}
      <tr class="{$row.status}">
        <td class="chkb">
          <input type="checkbox" name="select[]" value="{$row.id}">
        </td>
        <td class="language">
          <a href="{$lex->language_url($row.language)}">{$row.language_name}</a>
        </td>
        <td class="project">
          <a href="{$lex->project_url($row.project)}">{$row.project_name}</a>
        </td>
        <td class="file">
          {$row.file_name}
        </td>
        <td class="user">
          <a href="{$lex->user_url($row.user_id)}">{$row.username}</a>
        </td>
        <td class="date">
          <span style="display:none;">{$row.time}</span>{$row.date}
        </td>
        <td class="value">
          <pre class="row_value" title="{$row.row_name|escape:html}">{$row.trucated_value}</pre>
        </td>
        <td class="actions">
          <a href="{$row.delete_uri}" title="Delete this row"><img src="template/images/cross.png" alt="[x]"></a>
        </td>
      </tr>
    {foreachelse}
      <tr>
        <td colspan="8"><i>No results</i></td>
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
    <option value="delete_rows">Delete</option>
    <option value="mark_as_done">Mark as commited</option>
  </select>
  
  <span id="action_delete_rows" class="action-container">
    <label><input type="checkbox" name="confirm_deletion" value="1"> Are you sure ?</label>
  </span>
  
  <span id="action_apply" class="action-container">
    <input type="submit" name="apply_action" class="blue" value="Apply">
  </span>
</fieldset>
</form>


<table class="legend">
  <tr>
    <td><span>&nbsp;</span> Commited strings</td>
    <td><span class="new">&nbsp;</span> Added strings</td>
    <td><span class="edit">&nbsp;</span> Modified strings</td>
  </tr>
</table>



{combine_script id="jquery.tablesorter" path="template/js/jquery.tablesorter.min.js" load="footer"}
{combine_script id="jquery.tiptip" path="template/js/jquery.tiptip.min.js" load="footer"}
{combine_css path="template/js/jquery.tablesorter.css"}
{combine_css path="template/js/jquery.tiptip.css"}

{footer_script}{literal}
$(".row_value").tipTip({ 
  maxWidth:"600px",
  delay:200,
  defaultPosition:"left"
});

$("#last_modifs table").tablesorter({
  sortList: [[5,1]],
  headers: { 0: {sorter: false}, 6: {sorter: false}, 7: {sorter: false} },
  widgets: ["zebra"]
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
  }
  else {
    $("#permitAction").show();
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
{/literal}{/footer_script}