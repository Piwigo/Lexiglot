{combine_css path="template/css/public.css" rank=10}

<p class="caption">
  <a href="{$PROJECT_URL}">{$PROJECT_NAME}</a> &raquo; <a href="{$LANGUAGE_URL}">{$LANGUAGE_FLAG} {$LANGUAGE_NAME}</a>
  {if $IS_TRANSLATOR}<a class="floating_link notification" style="cursor:pointer;">Send a notification</a> <span class="floating_link">&nbsp;|&nbsp;</span>{/if}
  {if $REFERENCE_POPUP_LINK}<a class="floating_link" {$REFERENCE_POPUP_LINK}>View reference file</a>{/if}
</p>

{if $notifications_users}
<div id="dialog-form" title="Send a notification by mail" style="display:none;">
  {ui_message type="highlight" icon="info" content="You can only send mails to an admin or a translator of this language/project."}
  
  <form action="{$SELF_URL}" method="post">
    <table class="login" style="text-align:left;margin:0 auto;">
      <tr><td>
        Send to :
        <select name="user_id">
          <option value="-1" data="15">---------</option>
        {foreach from=$notifications_users item=row}
          <option value="{$row.ID}" data-rows="{$row.NB_ROWS}">{$row.USERNAME}{$row.STATUS}</option>
        {/foreach}
        </select>
      </td></tr>
      <tr><td>
        <label><input type="checkbox" name="notification" value="1"> ask for disposition notification</label>
      </td></tr>
{* <!--
      <tr><td>
      {if $MODE == "array"}
        <input type="checkbox" name="send_rows" value="1"> include <input type="text" name="nb_rows" size="2" maxlength="3" value="15"> first missing rows of current file in the mail';
      {else}
        <label><input type="checkbox" name="send_rows" value="1"> include the contents of current file in the mail</label>';
      {/if}
      </td></tr>
--> *}
      <tr><td>
        <textarea name="message" rows="6" cols="50"></textarea>
        <input type="hidden" name="key" value="{$SECRET_KEY}">
        <input type="hidden" name="send_notification" value="1">
      </td></tr>
    </table>
  </form>
</div>
{/if}


{$TABSHEET_FILES}

{include file="messages.tpl"}

{include file="edit_"|cat:$MODE|cat:".tpl"}
 {$NO_AUTORESIZE}

{if $IS_TRANSLATOR}
{* <!-- notification popup --> *}

{footer_script}{literal}
$("#dialog-form").dialog({
  autoOpen: false, modal: true, resizable: false,
  height: 320, width: 520,
  show: "clip", hide: "clip",
  buttons: {
    "Send": function() { $("#dialog-form form").submit(); },
    Cancel: function() { $(this).dialog("close"); }
  }
});
$(".notification").click(function() {
  $("#dialog-form").dialog("open");
});
$("select[name='user_id']").change(function() {
  $("input[name='nb_rows']").val($(this).children("option:selected").data("rows"));
});
{/literal}{/footer_script}

{else}
{* <!-- disable inputs --> *}

{footer_script}
$("textarea").prop("disabled", true);
{/footer_script}
{/if}

{if count($_DIFFS) <= 30 and empty($NO_AUTORESIZE)}
{* <!-- Can't use autoResize plugin with too many textareas (browser crashes) and incompatible with highlightTextarea --> *}
{combine_script id="jquery.autoresize" path="template/js/jquery.autoresize.min.js" load="footer"}

{footer_script}{literal}
$("#diffs textarea").autoResize({
  maxHeight:2000,
  extraSpace:11
});
{/literal}{/footer_script}
{/if}