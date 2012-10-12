{$TABSHEET_ADMIN}

{include file="messages.tpl"}


{* <!-- create a new user --> *}
{if $lex->is_admin()}
  <form action="{$F_ACTION}" method="post" {if $EXTERNAL_USERS}style="float:left;width:70%;"{/if}>
  <fieldset class="common">
    <legend>Create a new user</legend>
    
    <table class="search">
      <tr>
        <th>Username <span class="red">*</span></th>
        <th>Password <span class="red">*</span></th>
        <th>Email <span class="red">*</span></th>
        <th></th>
      </tr>
      <tr>
        <td><input type="text" name="username"></td>
        <td><input type="password" name="password"></td>
        <td><input type="text" name="email"></td>
        <td><input type="submit" name="add_user" class="blue" value="Add"></td>
      </tr>
    </table>
    
  </fieldset>
  </form>

  {* <!-- add user from external table --> *}
  {if $EXTERNAL_USERS}
  <form action="{$F_ACTION}" method="post" style="float:left;width:30%;">
  <fieldset class="common">
    <legend>Add an user from external table</legend>
    
    <table class="search">
      <tr>
        <th>Username</th>
        <th></th>
        <th>Id</th>
        <th></th>
      </tr>
      <tr>
        <td><input type="text" name="username"></td>
        <td>or</td>
        <td><input type="text" name="id" size="5"></td>
        <td><input type="submit" name="add_external_user" class="blue" value="Add"></td>
      </tr>
    </table>
    
  </fieldset>
  </form>
  <div style="clear:both;"></div>
  {/if}
{/if}

{* <!-- search users --> *}
<form action="{$F_ACTION}" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>Username</th>
      <th>Status</th>
      <th>Language</th>
      <th>Project</th>
      <th>Entries</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="username" value="{$SEARCH.username}"></td>
      <td>
        <select name="status">
          <option value="-1" {if -1==$SEARCH.status}selected="selected"{/if}>-------</option>
          <option value="guest" {if 'guest'==$SEARCH.status}selected="selected"{/if}>Guest</option>
          <option value="visitor" {if 'visitor'==$SEARCH.status}selected="selected"{/if}>Visitor</option>
          <option value="translator" {if 'translator'==$SEARCH.status}selected="selected"{/if}>Translator</option>
          <option value="manager" {if 'manager'==$SEARCH.status}selected="selected"{/if}>Manager</option>
          <option value="admin" {if 'admin'==$SEARCH.status}selected="selected"{/if}>Admin</option>
        </select>
      </td>
      <td>
        <select name="language">
          <option value="-1" {if -1==$SEARCH.language}selected="selected"{/if}>-------</option>
          <option value="n/a" {if 'n/a'==$SEARCH.language}selected="selected"{/if}>-- none assigned --</option>
        {foreach from=$CONF.all_languages item=row}
          <option value="{$row.id}" {if $row.id==$SEARCH.language}selected="selected"{/if}>{$row.name}</option>
        {/foreach}
        </select>
      </td>
      <td>
        <select name="project">
          <option value="-1" {if -1==$SEARCH.project}selected="selected"{/if}>-------</option>
          <option value="n/a" {if 'n/a'==$SEARCH.project}selected="selected"{/if}>-- none assigned --</option>
          {if $lex->is_manager()}<option value="n/a/m" {if 'n/a/m'==$SEARCH.project}selected="selected"{/if}>-- none of mine --</option>{/if}
        {foreach from=$displayed_projects item=row}
          <option value="{$row.id}" {if $row.id==$SEARCH.project}selected="selected"{/if}>{$row.name}</option>
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

{* <!-- users list --> *}
<form id="users" action="{$F_ACTION}{$NAV_PAGE}" method="post">
<fieldset class="common">
  <legend>Manage</legend>
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="user">Username</th>
        <th class="email">Email</th>
        <th class="date">Registration date</th>
        <th class="language lang-tip" title="Spoken">Languages</th>
        <th class="status">Status</th>
        <th class="lang lang-tip" title="Assigned">Languages</th>
        <th class="project">Projects</th>
        <th class="actions"></th>
      </tr>
    </thead>
    <tbody>
    {foreach from=$USERS item=row}
      <tr class="{$row.status} {if $row.highlight}highlight{/if}">
        <td class="chkb">
          <input type="checkbox" name="select[]" value="{$row.id}">
        </td>
        <td class="user">
          {if $row.id!=$CONF.guest_id}<a href="{$lex->user_url($row.id)}">{$row.username}</a>{else}{$row.username} {/if}
        </td>
        <td class="email">
          {if $row.email}<a href="mailto:{$row.email}">{$row.email}</a>{/if}
        </td>
        <td class="date">
          <span style="display:none;">{$row.time}</span>{$row.date}
        </td>
        <td class="language">
          {$row.my_languages_tooltip}
        </td>
        <td class="status">
        {if $lex->is_admin()}
          <span style="display:none;">{$row.status}</span>
          <select name="status[{$row.id}" data="{$row.id}" {if $row.status_disabled}disabled="disabled"{/if}>
            {if $row.id==$CONF.guest_id}<option value="guest" selected="selected">Guest</option>{/if}
            <option value="visitor" {if 'visitor'==$row.status}selected="selected"{/if}>Visitor</option>
            <option value="translator" {if 'translator'==$row.status}selected="selected"{/if}>Translator</option>
            <option value="manager" {if 'manager'==$row.status}selected="selected"{/if}>Manager</option>
            <option value="admin" {if 'admin'==$row.status}selected="selected"{/if}>Admin</option>
          </select>
        {else}
          {$row.status}
        {/if}
        </td>
        <td class="language">
          {$row.languages_tooltip}
        </td>
        <td class="project">
          {$row.projects_tooltip}
        </td>  
        <td class="actions">
        {if $row.manage_uri}
          <a href="{$row.manage_uri}" title="Manage permissions"><img src="template/images/user_edit.png"></a>
        {/if}
        {if $row.delete_uri}
          <a href="{$row.delete_uri}" title="Delete this user" onclick="return confirm('Are you sure?');"><img src="template/images/cross.png" alt="[x]"></a>
        {else}
          <span style="display:inline-block;width:16px;">&nbsp;</span>
        {/if}
        </td>
      </tr>
    {foreachelse}
      <tr>
        <td colspan="9"><i>No results</i></td>
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
    <option value="send_email">Send email</option>
  {if $lex->is_admin()}
    <option value="delete_users">Delete users</option>
    <!--<option value="change_status">Change status</option>-->
    <option value="add_language">Assign a language</option>
    <option value="remove_language">Unassign a language</option>
  {/if}
    <option value="add_project">Assign a project</option>
    <option value="remove_project">Unassign a project</option>
  </select>
  
  <span id="action_send_email" class="action-container">
    <a href="mailto:">Send</a>
  </span>
  
  <span id="action_delete_users" class="action-container">
    <label><input type="checkbox" name="confirm_deletion" value="1"> Are you sure ?</label>
  </span>
  
  <span id="action_change_status" class="action-container">
    <select name="batch_status">
      <option value="-1">-------</option>
      <option value="visitor">Visitor</option>
      <option value="translator">Translator</option>
      <option value="manager">Manager</option>
    </select>
  </span>
  
  <span id="action_add_language" class="action-container">
    <select name="language_add">
      <option value="-1">-------</option>
    {foreach from=$CONF.all_languages item=row}
      <option value="{$row.id}">{$row.name}</option>
    {/foreach}
    </select>
  </span>
  
  <span id="action_remove_language" class="action-container">
    <select name="language_remove">
      <option value="-1">-------</option>
    {foreach from=$CONF.all_languages item=row}
      <option value="{$row.id}">{$row.name}</option>
    {/foreach}
    </select>
  </span>
  
  <span id="action_add_project" class="action-container">
    <select name="project_add">
      <option value="-1">-------</option>
    {foreach from=$CONF.all_projects item=row}
      <option value="{$row.id}">{$row.name}</option>
    {/foreach}
    </select>
  </span>
  
  <span id="action_remove_project" class="action-container">
    <select name="project_remove">
      <option value="-1">-------</option>
    {foreach from=$CONF.all_projects item=row}
      <option value="{$row.id}">{$row.name}</option>
    {/foreach}
    </select>
  </span>
  
  <span id="action_apply" class="action-container">
    <input type="submit" name="apply_action" class="blue" value="Apply">
  </span>
</fieldset>

</form>


{combine_script id="jquery.tablesorter" path="template/js/jquery.tablesorter.min.js" load="footer"}
{combine_script id="jquery.tiptip" path="template/js/jquery.tiptip.min.js" load="footer"}
{combine_css path="template/js/jquery.tablesorter.css"}
{combine_css path="template/js/jquery.tiptip.css"}

{if $lex->is_admin()}
  {footer_script}{literal}
  $("#users select[name^='status']").change(function() {
    $("#users").append('<input type="hidden" name="save_status" value="'+ $(this).attr('data') +'">').submit();
  });
  {/literal}{/footer_script}
{/if}

{footer_script}{literal}
$(".expand").css("cursor", "help").tipTip({ 
  maxWidth:"800px",
  delay:200,
  defaultPosition:"left"
});

$(".lang-tip").css("cursor", "help").tipTip({
  delay:200,
  defaultPosition:"top"
});

$("#users table").tablesorter({
  sortList: [[1,0]],
  headers: { 0: {sorter: false}, 4: {sorter: false}, 6: {sorter: false}, 7: {sorter: false}, 8: {sorter: false} },
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
  
  if ($("select[name=selectAction]").attr("value") == "send_email") {
    updateEmailLink();
  }

  if (nbSelected == 0) {
    $("#permitAction").hide();
    $("#save_status").show();
  }
  else {
    $("#permitAction").show();
    $("#save_status").hide();
  }
}

function updateEmailLink() {
  var link = "mailto:";
  $("td.chkb input[type=checkbox]:checked").each(function() {
    mail = $(this).parent("td.chkb").nextAll("td.email").children("a").html();
    if (mail) link+= mail+";";
  });
  $("#action_send_email").children("a").attr("href", link);
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
  
  if ($(this).attr("value") == "send_email") {
    updateEmailLink();
  }

  if ($(this).val() != -1 && $(this).val() != "send_email") {
    $("#action_apply").show();
  }
  else {
    $("#action_apply").hide();
  }
});

$("td.user").click(function() {
  $checkbox = $(this).prev("td.chkb").children("input");
  $checkbox.attr("checked", !$checkbox.attr("checked"));
});
{/literal}{/footer_script}