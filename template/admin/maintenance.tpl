{include file="messages.tpl"}


<div id="maintenance">
  <ul style="float:left;">
    <h5>Environement</h5>
    <li><b>Lexiglot version :</b> {$CONF.version}</li>
    <li><b>PHP :</b> {$PHP_INFO}</li>
    <li><b>MySQL :</b> {$MYSQL_INFO}</li>
  </ul>

  <ul style="float:right;">
    <h5>Database</h5>
    <li><b>Used space :</b> {$DATABASE.used_space} Kio {if $DATABASE.free_space!=0}(waste : {$DATABASE.free_space} Kio, <a href="{$DATABASE.optimize_uri}">Clean</a>){/if}</li>
    <li><b>Users :</b> {$TABLES.user_infos}</li>
    <li><b>Projects :</b> {$TABLES.projects}</li>
    <li><b>Languages :</b> {$TABLES.languages}</li>
    {if not $CONF.delete_done_rows}<li><b>Translations :</b> {$TABLES.rows}</li>{/if}
    <li><b>Categories :</b> {$TABLES.categories} {if $UNUSED_CATS!=0}({$UNUSED_CATS} unused, <a href="{$DELETE_UNUSED_CATS_URI}">Delete</a>){/if}</li>
  </ul>
  
  <ul style="float:left;">
    <h5>Maintenance</h5>
    <li><a href="{$MAKE_STATS_URI}">Update all statistics</a></li>
    {if not $CONF.delete_done_rows}<li><a href="{$DELETE_DONE_ROWS_URI}" onclick="return confirm('Are you sure?');">Delete all commited strings</a></li>{/if}
    <li><a href="{$CLEAN_MAIL_URI}">Clean mail archive</a></li>
  </ul>
  
  <div style="clear:both;"></div>
</div>