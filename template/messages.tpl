{foreach from=$page_errors item=msg}
  <div class="ui-state-error" style="padding: 0.7em;margin-bottom:10px;">
    <span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.7em;"></span>
    {$msg}
  </div>
{/foreach}

{foreach from=$page_warnings item=msg}
  <div class="ui-state-warning" style="padding: 0.7em;margin-bottom:10px;">
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
    {$msg}
  </div>
{/foreach}

{foreach from=$page_infos item=msg}
  <div class="ui-state-highlight" style="padding: 0.7em;margin-bottom:10px;">
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
    {$msg}
  </div>
{/foreach}