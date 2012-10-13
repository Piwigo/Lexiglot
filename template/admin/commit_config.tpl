{include file="messages.tpl"}


<form action="{$F_ACTION}" method="post" id="commit_conf">
<fieldset class="common">
  <!--<legend>What to commit ?</legend>-->
  
  <div id="mode">
    <input type="radio" id="radio1" name="mode" value="all" checked="checked"><label for="radio1">All</label>
    <input type="radio" id="radio2" name="mode" value="filter"><label for="radio2">Filter</label>
  </div>
  
  <ul id="filter" style="display:none;">
    <li class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">
      <span class="ui-button-text">
        <label><span class="ui-icon ui-icon-close" style="float:left;margin:0 5px 0 -5px;"></span>
        <input type="checkbox" name="filter_project" value="1" style="display:none;"> by project</label>
        
        <select name="project_id" style="display:none;">
          <option value="-1">--------</option>
        {foreach from=$displayed_projects item=row}
          <option value="{$row.id}">{$row.name}</option>
        {/foreach}
        </select>
      </span>
    </li>
    <br>
    <li class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">
      <span class="ui-button-text">
        <label><span class="ui-icon ui-icon-close" style="float:left;margin:0 5px 0 -5px;"></span>
        <input type="checkbox" name="filter_language" value="1" style="display:none;"> by language</label>
        
        <select name="language_id" style="display:none;">
          <option value="-1">--------</option>
        {foreach from=$CONF.all_languages item=row}
          <option value="{$row.id}">{$row.name}</option>
        {/foreach}
        </select>
      </span>
    </li>
    <br>
    <li class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">
      <span class="ui-button-text">
        <label><span class="ui-icon ui-icon-close" style="float:left;margin:0 5px 0 -5px;"></span>
        <input type="checkbox" name="filter_user" value="1" style="display:none;"> by user</label>
        
        <select name="user_id" style="display:none;">
          <option value="-1">--------</option>
        {foreach from=$USERS item=row}
          <option value="{$row.id}">{$row.username}</option>
        {/foreach}
        </select>
      </span>
    </li>
  </ul>
  <ul>
    <li class="ui-button ui-widget ui-state-default ui-state-active ui-corner-all ui-button-text-only">
      <span class="ui-button-text">
        <label><span class="ui-icon ui-icon-check" style="float:left;margin:0 5px 0 -5px;"></span>
        <input type="checkbox" name="delete_obsolete" checked="checked" value="1" style="display:none;"> delete obsolete strings</label>
      </span>
    </li>
  </ul>
  
  <div class="centered">
    <input type="hidden" name="check_commit" value="1">
    <input type="submit" name="init_commit" class="blue big" value="Launch">
  </div>
</fieldset>
</form>


{footer_script}{literal}
$("#mode").buttonset();

$("input[name='mode']").change(function() {
  if ($(this).val() == "filter" && $("#filter").css("display") == "none") {
    $("#filter").slideDown("slow");
  }
  else if ($(this).val() == "all" && $("#filter").css("display") != "none") {
    $("#filter").slideUp("slow");
  }
});

$(".ui-button").hover(
  function() { $(this).addClass("ui-state-hover"); },
  function() { $(this).removeClass("ui-state-hover"); }
);

$(".ui-button input[type='checkbox']").change(function() {
  if ($(this).is(":checked")) {
    $(this).parents(".ui-button").addClass("ui-state-active");
    $(this).parents(".ui-button").find(".ui-icon").removeClass("ui-icon-close").addClass("ui-icon-check");
    $(this).parent("label").next("select").show("slide");
  }
  else {
    $(this).parents(".ui-button").removeClass("ui-state-active");
    $(this).parents(".ui-button").find(".ui-icon").removeClass("ui-icon-check").addClass("ui-icon-close");
    $(this).parent("label").next("select").hide("slide");
  }
});
{/literal}{/footer_script}