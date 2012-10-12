{$TABSHEET_ADMIN}

{include file="messages.tpl"}


<p class="caption">Manage permissions for user #{$local_user.id} : {$local_user.username}</p>

<form action="{$F_ACTION}" method="post" id="permissions">

{* <!-- languages --> *}
{if $lex->is_admin()}
  <fieldset class="common">
    <legend>Languages</legend>
    {if $local_user.status!='guest'}<p class="caption">Check the main language of this user</p>{/if}
    
    <ul id="available_languages" class="language-container">
      <h5>Authorized languages <span id="authorizeAllLanguage" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>
    {foreach from=$user_languages item=row}
      <li id="list_{$row.id}" class="language">
        {if $local_user.status!='guest'}<input type="radio" name="main_language" value="{$row.id}" {if $row.is_main}checked="checked"{/if}>{/if}
        {$lex->language_flag($row.id)} <span style="width:{$LANGUAGE_SIZE_1}px;">{$row.name}</span>
        {if $USE_LANGUAGE_RANK}<i>{$row.rank}</i>{/if}
      </li>
    {/foreach}
    </ul>

    <ul id="unavailable_languages" class="language-container">
      <h5>Forbidden languages <span id="forbidAllLanguage" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>
    {foreach from=$all_languages item=row}
      <li id="list_{$row.id}" class="language">
        {if $local_user.status!='guest'}<input type="radio" name="main_language" value="{$row.id}" style="display:none;">{/if}
        {$lex->language_flag($row.id)} <span style="width:{$LANGUAGE_SIZE_2}px;">{$row.name}</span>
        {if $USE_LANGUAGE_RANK}<i>{$row.rank}</i>{/if}
      </li>
    {/foreach}
    </ul>
  </fieldset>
{/if}

{* <!-- projects --> *}
  <fieldset class="common">
    <legend>Projects</legend>
    {if $local_user.is_manager}<p class="caption">Check projects this user can manage</p>{/if}
    
    <ul id="available_projects" class="project-container">
      <h5>Authorized projects <span id="authorizeAllProject" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>
    {foreach from=$user_projects item=row}
      <li id="list_{$row.id}" class="project" {if not $row.is_movable}style="display:none;"{/if} title="{$row.name}">
        {if $local_user.is_manager}<input type="checkbox" name="manage_projects[]" value="{$row.id}" {if $row.is_managed}checked="checked"{/if}>{/if}
        <span style="width:{$PROJECT_SIZE_1}px;">{$row.name}</span>
        {if $USE_PROJECT_STATS}<b style="color:{$row.stats_color};">{$row.stats_value}%</b>{/if}
        {if $USE_PROJECT_RANK}<i>{$row.rank}</i>{/if}
      </li>
    {/foreach}
    </ul>

    <ul id="unavailable_projects" class="project-container">
      <h5>Forbidden projects <span id="forbidAllProject" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>
    {foreach from=$all_projects item=row}
      <li id="list_{$row.id}" class="project" {if not $row.is_movable}style="display:none;"{/if} title="{$row.name}">
        {if $local_user.is_manager}<input type="checkbox" name="manage_projects[]" value="{$row.id}" style="display:none;">{/if}
        <span style="width:{$PROJECT_SIZE_2}px;">{$row.name}</span>
        {if $USE_PROJECT_STATS}<b style="color:{$row.stats_color};">{$row.stats_value}%</b>{/if}
        {if $USE_PROJECT_RANK}<i>{$row.rank}</i>{/if}
      </li>
    {/foreach}
    </ul>
  </fieldset>

{* <!-- manage perms --> *}
{if $local_user.is_manager}
  <fieldset class="common">
    <legend>Manager permissions</legend>
    
    <label><input type="checkbox" name="manage_perms[can_add_projects]" value="1" {if $local_user.manage_perms.can_add_projects}checked="checked"{/if}> Can add projects</label><br>
    <label><input type="checkbox" name="manage_perms[can_delete_projects]" value="1" {if $local_user.manage_perms.can_delete_projects}checked="checked"{/if}> Can delete projects</label><br>
    <label><input type="checkbox" name="manage_perms[can_change_users_projects]" value="1" {if $local_user.manage_perms.can_change_users_projects}checked="checked"{/if}> Can change users projects</label><br>
  </fieldset>
{/if}

  <div class="centered">
    <input type="hidden" name="user_id" value="{$local_user.id}">
    <input type="submit" name="save_perm" class="blue big" value="Save">
    <input type="reset" onClick="location.href='{$CANCEL_URI}';" class="red" value="Cancel">
  </div>
</form>


{footer_script}{literal}
function save_datas(form) {    
  $("#available_languages > li").each(function() {
    $(form).append('<input type="hidden" name="available_languages[]" value="'+ $(this).attr('id').replace('list_','') +'">');
  });
  $("#available_projects > li").each(function() {
    $(form).append('<input type="hidden" name="available_projects[]" value="'+ $(this).attr('id').replace('list_','') +'">');
  });
}

function update_height(row) {
  $("#available_"+ row).css("height", "auto");
  $("#unavailable_"+ row).css("height", "auto");
  var max = Math.max($("#available_"+ row).height(), $("#unavailable_"+ row).height());
  $("#available_"+ row).css("height", max);
  $("#unavailable_"+ row).css("height", max);
}

/* trigger form submission */
$("form#permissions").submit(function() {
  save_datas(this);
});
{/literal}{/footer_script}

{if $lex->is_admin()}
  {footer_script}{literal}
  /* move languages */
  $("li.language input").bind("click", function (e) {
    e.stopPropagation();
  });
  
  $("#unavailable_languages").delegate("li.language", "click", function() {
    $(this).fadeOut("fast", function() {
      $(this).children("input").show();
      $(this).children("span").css("width", {/literal}{$LANGUAGE_SIZE_1}{literal});
      $(this).appendTo("#available_languages").fadeIn("fast", function(){
        update_height("languages");
      });
    });
  });
  $("#available_languages").delegate("li.language", "click", function() {
    $(this).fadeOut("fast", function() {
      $(this).children("input").hide();
      $(this).children("span").css("width", {/literal}{$LANGUAGE_SIZE_2}{literal});
      $(this).appendTo("#unavailable_languages").fadeIn("fast", function(){
        update_height("languages");
      });
    });
  });
  
  /* all languages */
  $("#authorizeAllLanguage").click(function() {
    $("#unavailable_languages li").each(function() {
      $(this).fadeOut("fast", function() {
        $(this).children("input").show();
        $(this).children("span").css("width", {/literal}{$LANGUAGE_SIZE_1}{literal});
        $(this).appendTo($("#available_languages")).fadeIn("fast");
      });
    }).promise().done(function() { 
      update_height("languages");
    });
  });
  $("#forbidAllLanguage").click(function() {
    $("#available_languages li").each(function() {
      $(this).fadeOut("fast", function() {
        $(this).children("input").hide();
        $(this).children("span").css("width", {/literal}{$LANGUAGE_SIZE_2}{literal});
        $(this).appendTo($("#unavailable_languages")).fadeIn("fast");
      });
    }).promise().done(function() { 
      update_height("languages");
    });
  });
  
  update_height("languages");
  {/literal}{/footer_script}
{/if}

{footer_script}{literal}
/* move projects */
$("li.project input").bind("click", function (e) {
  e.stopPropagation();
});

$("#unavailable_projects").delegate("li.project", "click", function() {
  $(this).fadeOut("fast", function() {
    $(this).children("input").show();
    $(this).children("span").css("width", {/literal}{$PROJECT_SIZE_1}{literal});
    $(this).appendTo("#available_projects").fadeIn("fast", function(){
      update_height("projects");
    }); 
  });
});
$("#available_projects").delegate("li.project", "click", function() {
  $(this).fadeOut("fast", function() {
    $(this).children("input").hide();
    $(this).children("span").css("width", {/literal}{$PROJECT_SIZE_2}{literal});
    $(this).appendTo("#unavailable_projects").fadeIn("fast", function(){
      update_height("projects");
    });
  });
});

/* all projects */
$("#authorizeAllProject").click(function() {
  $("#unavailable_projects li").each(function() {
    if ($(this).css("display") != "none") {
      $(this).fadeOut("fast", function() {
        $(this).children("input").show();
        $(this).children("span").css("width", {/literal}{$PROJECT_SIZE_1}{literal});
        $(this).appendTo($("#available_projects")).fadeIn("fast");
      });
    }
  }).promise().done(function() { 
    update_height("projects");
  });
});
$("#forbidAllProject").click(function() {
  $("#available_projects li").each(function() {
    if ($(this).css("display") != "none") {
      $(this).fadeOut("fast", function() {
        $(this).children("input").hide();
        $(this).children("span").css("width", {/literal}{$PROJECT_SIZE_2}{literal});
        $(this).appendTo($("#unavailable_projects")).fadeIn("fast");
      });
    }
  }).promise().done(function() { 
    update_height("projects");
  });
});

update_height("projects");
{/literal}{/footer_script}