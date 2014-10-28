{include file="messages.tpl"}


<form action="{$F_ACTION}" method="post" id="config">
  <fieldset class="common">
    <legend>Engine configuration</legend>
    
    <table class="common">
      <tr>
        <td>Installation name :</td>
        <td><textarea name="install_name" cols="60" rows="2">{$CONF.install_name}</textarea></td>
      </tr>
      <tr>
        <td>Homepage message :</td>
        <td><textarea name="intro_message" cols="60" rows="2">{$CONF.intro_message}</textarea></td>
      </tr>
      <tr>
        <td>Default language :</td>
        <td><input type="text" name="default_language" value="{$CONF.default_language}"></td>
      </tr>
      <tr>
        <td>Var name :</td>
        <td><input type="text" name="var_name" value="{$CONF.var_name}"></td>
      </tr>
      <tr>
        <td>Allow modification of default language :</td>
        <td><input type="checkbox" name="allow_edit_default" value="1" {if $CONF.allow_edit_default}checked="checked"{/if}></td>
      </tr>
      <tr>
        <td>Delete strings after commit :</td>
        <td><input type="checkbox" name="delete_done_rows" value="1" {if $CONF.delete_done_rows}checked="checked"{/if}></td>
      </tr>
      <tr>
        <td>Display statistics :</td>
        <td><input type="checkbox" name="use_stats" value="1" {if $CONF.use_stats}checked="checked"{/if}></td>
      </tr>
      <tr>
        <td>Display talk pages :</td>
        <td><input type="checkbox" name="use_talks" value="1" {if $CONF.use_talks}checked="checked"{/if}></td>
      </tr>
    </table>
  </fieldset>

  <fieldset class="common">
    <legend>Users configuration</legend>
    
    <table class="common">
      <tr>
        <td>Read access for guests :</td>
        <td><input type="checkbox" name="access_to_guest" value="1" {if $CONF.access_to_guest}checked="checked"{/if}></td>
      </tr>
      <tr>
        <td>Allow new users :</td>
        <td><input type="checkbox" name="allow_registration" value="1" {if $CONF.allow_registration}checked="checked"{/if}></td>
      </tr>
      <tr>
        <td>Allow users to change their password and mail :</td>
        <td><input type="checkbox" name="allow_profile" value="1" {if $CONF.allow_profile}checked="checked"{/if}></td>
      </tr>
      <tr>
        <td>Translators can add languages and projects (according to their rights) :</td>
        <td><input type="checkbox" name="user_can_add_language" value="1" {if $CONF.user_can_add_language}checked="checked"{/if}></td>
      </tr>
      </table>
  </fieldset>
  
  <fieldset class="common">
    <legend>Permissions</legend>
    
    <table class="common">
      <tr>
        <td>A new translator has access to :</td>
        <td>
          <label><input type="radio" name="user_default_language" value="all" {if $CONF.user_default_language=='all'}checked="checked"{/if}> All languages</label>
          <label><input type="radio" name="user_default_language" value="own" {if $CONF.user_default_language=='own'}checked="checked"{/if}> His languages</label>
          <label><input type="radio" name="user_default_language" value="none" {if $CONF.user_default_language=='none'}checked="checked"{/if}> No language</label>
        </td>
      </tr>
      <tr>
        <td>A new translator has access to :</td>
        <td>
          <label><input type="radio" name="user_default_project" value="all" {if $CONF.user_default_project=='all'}checked="checked"{/if}> All projects</label>
          <label><input type="radio" name="user_default_project" value="none" {if $CONF.user_default_project=='none'}checked="checked"{/if}> No project</label>
        </td>
      </tr>
      <tr>
        <td>A new language is accessible to :</td>
        <td>
          <label><input type="radio" name="language_default_user" value="all" {if $CONF.language_default_user=='all'}checked="checked"{/if}> All translators</label>
          <label><input type="radio" name="language_default_user" value="none" {if $CONF.language_default_user=='none'}checked="checked"{/if}> No translator</label>
        </td>
      </tr>
      <tr>
        <td>A new project is accessible to :</td>
        <td>
          <label><input type="radio" name="project_default_user" value="all" {if $CONF.project_default_user=='all'}checked="checked"{/if}> All translators</label>
          <label><input type="radio" name="project_default_user" value="none" {if $CONF.project_default_user=='none'}checked="checked"{/if}> No translator</label>
        </td>
      </tr>
    </table>
  </fieldset>
  
  <fieldset class="common">
    <legend>Add this text at the begining of new PHP files</legend>

    <div style="text-align:center;">
      (must be formatted as a <a href="http://php.net/manual/en/language.basic-syntax.comments.php">PHP comment</a>)<br>
      <textarea name="new_file_content" style="width:80%;color:#008000;" rows="5">{$CONF.new_file_content}</textarea>
    </div>
  </fieldset>
  
  <div class="centered">
  	<input type="submit" name="save_config" value="Save" class="blue big">
  </div>
</form>


{combine_script id="jquery.autoresize" path="template/js/jquery.autoresize.min.js" load="footer"}

{footer_script}{literal}
$("textarea").autoResize({
  maxHeight:2000,
  extraSpace:5
});
{/literal}{/footer_script}