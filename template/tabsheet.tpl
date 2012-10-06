<div id="tabsheet">
  <ul>
  {foreach from=$tabsheet item=sheet}
    <li class="{if $sheet.SELECTED}selected{/if} {if count($tabsheet)==1}alone{/if}">
      <a href="{$sheet.URL}" title="{$sheet.TITLE}">{$sheet.NAME}</a>
    </li>
  {/foreach}
  </ul>
</div>