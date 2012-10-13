{include file="messages.tpl"}

<p class="legend">
  <a href="{$lex->project_url($PROJECT)}">{$lex->project_name($PROJECT)}</a>
  {* {if $TALK_URI}<a href="{$TALK_URI}" class="floating_link">Talk</a>{/if} *}
</p>

<ul id="languages" class="list-cloud {if $USE_LANGUAGE_STATS}w-stats{/if}">
{assign var=category value=0}
{foreach from=$languages item=row}{strip}
  {if $USE_LANGUAGE_CATS}
    {if not empty($row.CATEGORY_ID) and $category!=$row.CATEGORY_ID}
      {assign var=category value=$row.CATEGORY_ID}
      <h3>{$row.CATEGORY_NAME}</h3>
    {elseif empty($row.CATEGORY_ID) and $category!=0}
      {assign var=category value=0}
      <h3>Other</h3>
    {/if}
  {/if}
  
  {/strip}<li {if $row.IS_NEW}class="new"{/if}>
    <a href="{$row.URL}">{strip}
      {$row.NAME} {$row.FLAG}
      {if $row.ID == $CONF.default_language}<i>(source)</i>{/if}
      {$row.PROGRESS_BAR}
    {/strip}</a>
  </li>
{/foreach}

{if $LANGUAGE_NOT_TRANSLATED}
  <li class="add">
    <b>{$LANGUAGE_NOT_TRANSLATED} language(s) not translated</b> <a href="#"><img src="template/images/bullet_add.png" alt="+"> Add a new language</a>
  </li>
{/if}
</ul>

{if $LANGUAGE_NOT_TRANSLATED}
<div id="dialog-form" title="Translate another project" style="display:none;">
  {ui_message type="highlight" icon="info" content="You can only add languages you have permission to translate.<br>
      Can't see the language you wish to translate ? Please <a href='"|cat:$REQUEST_LANGUAGE_URL|cat:"'>send us a request</a>."}
  <form action="{$SELF_URL}" method="post" style="text-align:center;">
    Select a language : 
    <select name="language">
      <option value="-1">----------</option>
      {html_options options=$language_available}
    </select>
    <input type="hidden" name="add_language" value="1">
  </form>
</div>
{/if}

{if $PROGRESS_BAR}
  {ui_message type="highlight" icon="signal" content="<b>Project progression :</b> "|cat:$PROGRESS_BAR}
{/if}

{if $PROJECT_URL}
  {ui_message type="highlight" icon="extlink" content="<b>Website :</b> <a href='"|cat:$PROJECT_URL|cat:"'>"|cat:$PROJECT_URL|cat:"</a>"}
{/if}


{footer_script}{literal}
$("#dialog-form").dialog({
  autoOpen: false, modal: true, resizable: false,
  height: 200, width: 440,
  show: "clip", hide: "clip",
  buttons: {
    "Add": function() { $("#dialog-form form").submit(); },
    "Cancel": function() { $(this).dialog("close"); }
  }
});

$(".add a").click(function() {
  $("#dialog-form").dialog("open");
  return false;
});
{/literal}{/footer_script}