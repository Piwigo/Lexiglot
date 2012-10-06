<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>{if $WINDOW_TITLE}{$WINDOW_TITLE} | {/if}{$INSTALL_NAME|strip_tags}</title>
  
  <!-- default css & js -->{strip}
  {combine_css path="template/css/style.css" rank=1}
  {combine_css path="template/js/jquery.ui/jquery.ui.custom.css" rank=100}
  {combine_script id="jquery" path="template/js/jquery.min.js" load="header"}
  {combine_script id="jquery.ui" path="template/js/jquery.ui.custom.min.js" load="footer"}
  {/strip}
  {get_combined_css}
  {get_combined_scripts load="header"}
</head>

<body>

{if not $NO_HEADER}
<div id="the_header">
  <div id="login"><div>
  {if $USER.STATUS == 'guest'}
    Welcome <b>guest</b> | <a href="user.php?login">Login</a> {if $CONF.allow_registration}<i>or</i> <a href="user.php?register">Register</a>{/if}
  {else}
    Logged as :
    <b><a href="profile.php">{$USER.USERNAME}</a></b> (<i>{$USER.STATUS}</i>) 
    | <a href="index.php?action=logout">Logout</a>
  {/if}
  {if $USER.STATUS == 'manager' or $USER.STATUS == 'admin' }
    | <a href="admin.php">Administration</a>
  {/if}
  </div></div>
  
  <div id="title">
    <a href="index.php">{$INSTALL_NAME}</a>
    {if $PAGE_TITLE} | <a href="{$GET_URL_STRING}"><i>{$PAGE_TITLE}</i></a>{/if}
  </div>
</div><!-- the_header -->
{/if}

<div id="the_page">
<noscript>
<div class="ui-state-warning" style="padding: 0.7em;margin-bottom:10px;">
  <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
  <b>JavaScript is not activated, some major functions may not work !</b>
</div>
</noscript>