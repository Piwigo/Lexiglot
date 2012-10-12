<p class="caption">
  {$lex->project_name($PROJECT)} &raquo; {$lex->language_flag($LANGUAGE)} {$lex->language_name($LANGUAGE)} &raquo; {$FILE}
  <a class="floating_link" href="javascript:window.close();">Close this window</a>
</p>

<form id="diffs">
<fieldset class="common">
  <table class="common">
  {foreach from=$ROWS item=row name=foo}
    <tr class="{if $smarty.foreach.foo.index is odd}odd{else}even{/if}">
      <td><pre>{$row.key}</pre></td>
      <td><pre>{$row.row_value}</pre></td>
    </tr>
  {/foreach}
  </table>
</fieldset>
</form>