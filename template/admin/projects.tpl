{include file="messages.tpl"}


{* <!-- add project --> *}
{if $CAN_ADD_PROJECT}
<form action="{$F_ACTION}" method="post">
<fieldset class="common">
  <legend>Add a project</legend>
  
  <table class="search">
    <tr>
      <th>Name <span class="red">*</span></th>
      <th>SVN repository <span class="red">*</span></th>
      <th>SVN user <span class="red">*</span></th>
      <th>SVN password <span class="red">*</span></th>
    </tr>
    <tr>
      <td><input type="text" name="name" size="20" value="{$NEW_PROJECT.name}"></td>
      <td><input type="text" name="svn_url" size="50" value="{$NEW_PROJECT.svn_url}"></td>
      <td><input type="text" name="svn_user" size="20" autocomplete="off" value="{$NEW_PROJECT.svn_user}"></td>
      <td><input type="password" name="svn_password" size="20" autocomplete="off" value="{$NEW_PROJECT.svn_password}"></td>
    </tr>
  </table>
  
  <table class="search">
    <tr>
      <th>Files <span class="red">*</span></th>
      <th>Priority</th>
      <th>Category</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="files" size="74" value="{$NEW_PROJECT.files}"></td>
      <td><input type="text" name="rank" size="2" value="{if isset($NEW_PROJECT.rank)}{$NEW_PROJECT.rank}{else}1{/if}"></td>
      <td><input type="text" name="category_id" class="category" value="{$NEW_PROJECT.category_id}"></td>
      <td><input type="submit" name="add_project" class="blue" value="Add"></td>
    </tr>
  </table>
  
</fieldset>
</form>
{/if}

{* <!-- search projects --> *}
<form action="{$F_ACTION}" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>Name</th>
      <th>Priority</th>
      <th>Category</th>
      <th>Entries</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="name" size="20" value="{$SEARCH.name}"></td>
      <td><input type="text" name="rank" size="2" value="{$SEARCH.rank}"></td>
      <td>
        <select name="category_id">
          <option value="-1" {if -1==$SEARCH.category_id}selected="selected"{/if}>-------</option>
        {foreach from=$CATEGORIES item=row}
          <option value="{$row.id}" {if $row.id==$SEARCH.category_id}selected="selected"{/if}>{$row.name}</option>
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

{* <!-- projects list --> *}
<form id="projects" action="{$F_ACTION}{$NAV_PAGE}" method="post">
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
    {foreach from=$DIRS item=row}
      <tr class="main {if $row.highlight}highlight{/if}">
        <td class="chkb">
          <input type="checkbox" name="select[]" value="{$row.id}">
        </td>
        <td class="name">
          <a href="{$lex->project_url($row.id)}">{$row.name}</a>
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
          <a href="#" class="expand" data="{$row.id}" title="Edit this project"><img src="template/images/page_white_edit.png" alt="edit"></a>
          <a href="{$row.make_stats_uri}" title="Refresh stats"><img src="template/images/arrow_refresh.png" alt="refresh"></a>
          {if $row.delete_uri}<a href="{$row.delete_uri}" title="Delete this project" onclick="return confirm('Are you sure?');"><img src="template/images/cross.png" alt="delete"></a>{/if}
        </td>
      </tr>
    {foreachelse}
      <tr>
        <td colspan="6"><i>No results</i></td>
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
    {if $CAN_ADD_PROJECT}<option value="delete_projects">Delete projects</option>{/if}
    <option value="change_rank">Change priority</option>
    <option value="change_category">Change category</option>
  </select>
  
  <span id="action_delete_projects" class="action-container">
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
/* perform ajax request for project edit */
$("a.expand").click(function() {
  $trigger = $(this);
  project_id = $trigger.attr("data");
  $parent_row = $trigger.parents("tr.main");
  $details_row = $parent_row.next("tr.details");
  
  if (!$details_row.length) {
    $("a.expand img").attr("src", "template/images/page_white_edit.png");
    $("tr.details").remove();
    
    $trigger.children("img").attr("src", "template/images/page_edit.png");
    $parent_row.after('<tr class="details" id="details'+ project_id +'"><td class="chkb"></td><td colspan="5"><img src="template/images/load16.gif"> <i>Loading...</i></td></tr>');
    
    $container = $parent_row.next("tr.details").children("td:last-child");

    $.ajax({
      type: "POST",
      url: "admin/ajax.php",
      data: { "action":"get_project_form", "project_id": project_id }
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
$("#projects table").tablesorter({
  sortList: [[2,1],[1,0]],
  headers: { 0: {sorter:false}, 5: {sorter: false} },
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

/* SVN autofill */
$(document).on('change', 'input[name*=svn_url]', function() {
  var svn_url = $(this).val(),
      $table = $(this).closest('table');
  
  if (svn_url) {
    $.ajax({
      type: "POST",
      url: "admin/ajax.php",
      data: { "action":"get_default_svn_user", "svn_url": svn_url }
    }).done(function(msg) {
      msg = $.parseJSON(msg);
      
      if (msg.errcode == "success") {
        $table.find('input[name*=svn_user]').val(msg.data.svn_user);
        $table.find('input[name*=svn_password]').val(msg.data.svn_password);
      }
    });
  }
});
{/literal}{/footer_script}

{if $DEPLOY_PROJECT}
  {footer_script}
  $("a.expand[data='{$DEPLOY_PROJECT}']").trigger("click");
  {/footer_script}
{/if}