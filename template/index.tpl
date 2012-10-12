{include file="messages.tpl"}

{if $CONF.intro_message}
  {ui_message type="highlight" icon="comment" content=$CONF.intro_message}
{/if}

{if $languages}
  <p class="caption">Choose a language{if $CONF.navigation_type == 'both'}...{/if}</p>

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
    
    {/strip}<li>
      <a href="{$row.URL}">{strip}
        {$row.NAME} {$row.FLAG}
        {if $row.ID == $CONF.default_language}<i>(source)</i>{/if}
        {$row.PROGRESS_BAR}
      {/strip}</a>
    </li>
  {/foreach}
  
  {if $ADD_LANGUAGE_URL}
    <li class="add">
      <a href="{$ADD_LANGUAGE_URL}"><img src="template/images/bullet_add.png" alt="+"> Request a new language</a>
    </li>
  {/if}
  </ul>
{/if}

{if $projects}
  <p class="caption">{if $CONF.navigation_type == 'both'}... or choose a project{else}Choose a project{/if}</p>

  <ul id="projects" class="list-cloud {if $USE_PROJECT_STATS}w-stats{/if}">
  {assign var=category value=0}
  {foreach from=$projects item=row}{strip}
    {if $USE_PROJECT_CATS}
      {if not empty($row.CATEGORY_ID) and $category!=$row.CATEGORY_ID}
        {assign var=category value=$row.CATEGORY_ID}
        <h3>{$row.CATEGORY_NAME}</h3>
      {elseif empty($row.CATEGORY_ID) and $category!=0}
        {assign var=category value=0}
        <h3>Other</h3>
      {/if}
    {/if}
    
    {/strip}<li>
      <a href="{$row.URL}">{strip}
        {$row.NAME} {$row.FLAG}
        {$row.PROGRESS_BAR}
      {/strip}</a>
    </li>
  {/foreach}
  </ul>
{/if}