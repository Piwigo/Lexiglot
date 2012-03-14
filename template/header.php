<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2012 Damien Sorel       http://www.strangeplanet.fr |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

echo '<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>'.(!empty($page['window_title']) ? $page['window_title'].' | ' : null).' '.strip_tags($conf['install_name']).'</title>
  
  <!-- default css & js -->
  <link type="text/css" rel="stylesheet" media="screen" href="template/style.css">
  <link type="text/css" rel="stylesheet" media="screen" href="template/'.(defined('IN_ADMIN') ? 'admin' : 'public').'.css">
  <link type="text/css" rel="stylesheet" media="screen" href="template/js/jquery.ui/jquery.ui.custom.css">
  <link type="text/css" rel="stylesheet" media="screen" href="template/js/jquery.tiptip.css">
  <script type="text/javascript" src="template/js/jquery.min.js"></script>
  <script type="text/javascript" src="template/js/jquery.ui.custom.min.js"></script>
  <script type="text/javascript" src="template/js/jquery.tiptip.min.js"></script>
  
  <!-- special css & js -->
  '.$page['header'].'
  
  <!-- jquery code -->
  <script type="text/javascript">
  $(document).ready(function() {
    '.$page['script'].'
  });
  </script>
</head>

<body>';

if ($login_box)
{
  echo '
<div id="the_header">
  <div id="login"><div>';
  if (is_guest()) 
  {
    echo '
    Welcome <b>guest</b> | <a href="user.php?login">Login</a> '.($conf['allow_registration'] ? '<i>or</i> <a href="user.php?register">Register</a>' : null);
  } 
  else 
  { 
    echo '
    Logged as :
    <b><a href="profile.php">'.$user['username'].'</a></b> (<i>'.$user['status'].'</i>) 
    | <a href="index.php?action=logout">Logout</a>';
  }
    echo '
    '.(is_manager() || is_admin() ? ' | <a href="admin.php">Administration</a>' : null).'
  </div></div>
  <div id="title">
    <a href="index.php">'.$conf['install_name'].'</a>
    '.(!empty($page['title']) ? ' | <a href="'.get_url_string().'"><i>'.$page['title'].'</i></a>' : null).'
  </div>
</div><!-- the_header -->';
}

echo '
<div id="the_page">
<noscript>
<div class="ui-state-warning" style="padding: 0.7em;margin-bottom:10px;">
  <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
  <b>JavaScript is not activated, some major functions may not work !</b>
</div>
</noscript>

'.$page['begin'];

?>