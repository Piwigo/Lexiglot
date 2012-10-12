{$TABSHEET_ADMIN}

{include file="messages.tpl"}


<form action="{$F_ACTION}" method="post" id="check_commit">
<fieldset class="common">
  <legend>{$DATA|@count} commit(s) {if $COMMIT_TITLE}• {$COMMIT_TITLE}{/if}</legend>
  
  <table class="main" style="margin-bottom:10px;">
  {counter assign=i value=0}
  {foreach from=$DATA item=commit}
    <tr><td class="title commit" colspan="2">
      <b>Project :</b> {$lex->project_name($commit.project)} — <b>Language :</b> {$lex->language_name($commit.language)}
      <span style="float:right;"><label><input type="checkbox" name="exclude[]" value="{$commit.project}{$commit.language}"> Exlude</label></span>
    </td></tr>
    
    <tr>
      <td class="marge"></td>
      <td><table class="files">
      {foreach from=$commit.files item=file}
        <tr><td class="title file" colspan="2"><b>File :</b> {$file.name} {if $file.is_new}<span class="green">(new file)</span>{/if}</td></tr>
        
        {if $file.is_plain}
          <tr>
            <td class="marge"></td>
            <td><table class="rows">
              <tr class="{$file.rows[0].status} {if $i is odd}odd{else}even{/if}">
                <td colspan="2"><pre>{$file.rows[0].row_value}</pre></td>
              </tr>
            </table></td>
          </tr>
          {counter}
        {else}
          <tr>
            <td class="marge"></td>
            <td><table class="rows">
            {foreach from=$file.rows item=row}
              <tr class="{$row.status} {if $i is odd}odd{else}even{/if}">
                <td><pre>{$row.key}</pre></td>
                <td><pre>{$row.row_value}</pre></td>
              </tr>
              {counter}
            {/foreach}
            </table></td>
          </tr>
        {/if}
      {/foreach}
      </table></td>
    </tr>
    
    <tr><td class="message" colspan="2"><b>Message :</b> 
      [{$commit.project}] {if $commit.is_new}Add{else}Update{/if} language {$commit.language}, thanks to : {' & '|implode:$commit.users}</td></tr>
      
  {/foreach}
  </table>
  
  <div class="centered">
  {if $commit_config.delete_obsolte}
    <input type="hidden" name="delete_obsolete" value="1">
  {/if}
  {if $commit_config.filters.project}
    <input type="hidden" name="filter_project" value="1">
    <input type="hidden" name="project_id" value="{$commit_config.filters.project}">
  {/if}
  {if $commit_config.filters.language}
    <input type="hidden" name="filter_language" value="1">
    <input type="hidden" name="language_id" value="{$commit_config.filters.language}">
  {/if}
  {if $commit_config.filters.user}
    <input type="hidden" name="filter_user" value="1">
    <input type="hidden" name="user_id" value="{$commit_config.filters.user}">
  {/if}
    <input type="hidden" name="mode" value="{$commit_config.mode}">
    
    <input type="submit" name="init_commit" class="blue big" value="Launch">
    <input type="reset" class="red" value="Cancel">
  </div>
</fieldset>
</form>

<table class="legend">
  <tr>
    <td><span class="new">&nbsp;</span> Added strings</td>
    <td><span class="edit">&nbsp;</span> Modified strings</td>
    <td><span class="missing">&nbsp;</span> Obsolete strings</td>
  </tr>
</table>


{footer_script}
$("input[type='reset']").click(function() {ldelim}
  window.location.href = "{$F_ACTION}";
});
{/footer_script}