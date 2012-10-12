<p class="caption">
  {$lex->project_name($PROJECT)} &raquo; {$lex->language_flag($LANGUAGE)} {$lex->language_name($LANGUAGE)} &raquo; {$FILE}
  <a class="floating_link" href="javascript:window.close();">Close this window</a>
  <span class="floating_link">&nbsp;|&nbsp;</span>
  <a class="floating_link" href="{$DISPLAY_URI}">{if $DISPLAY=='plain'}View normal{else}View plain{/if}</a>
</p>

<form id="diffs">
<fieldset class="common">
  <legend>File content</legend>
  {if $DISPLAY=='plain'}
    {footer_script}
    $("pre").css("height", $(window).height()-140);
    {/footer_script}
    <pre style="white-space:pre-wrap;overflow-y:scroll;">{$CONTENT}</pre>
  {else}
    {footer_script}
    $("iframe").css("height", $(window).height()-140);
    {/footer_script}
    <iframe src="{$FILE_URI}" style="width:100%;margin:0;"></iframe>
  {/if}
</fieldset>
</form>