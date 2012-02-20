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

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>'.(!empty($page['window_title']) ? $page['window_title'].' | ':null).' '.strip_tags($conf['install_name']).'</title>
  
  <link type="text/css" rel="stylesheet" media="screen" href="template/style.css">
  <link type="text/css" rel="stylesheet" media="screen" href="template/js/jquery.ui/jquery.ui.custom.css">
  <link type="text/css" rel="stylesheet" media="screen" href="template/js/jquery.tiptip.css">
  <script type="text/javascript" src="template/js/jquery.min.js"></script>
  <script type="text/javascript" src="template/js/jquery.ui.custom.min.js"></script>
  <script type="text/javascript" src="template/js/jquery.tiptip.min.js"></script>
  
  '.$page['header'].'
  
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
    Welcome <b>guest</b> | <a href="user.php?login">Login</a> '.($conf['allow_registration']?'<i>or</i> <a href="user.php?register">Register</a>':'');
  } 
  else 
  { 
    echo '
    Logged as :
    <b><a href="profile.php">'.$user['username'].'</a></b> (<i>'.$user['status'].'</i>) 
    | <a href="index.php?action=logout">Logout</a>';
  }
    echo '
    '.((is_manager() or is_admin()) ? ' | <a href="admin.php">Administration</a>':null).'
  </div></div>
  <div id="title">
    <a href="index.php">'.$conf['install_name'].'</a>
    '.(!empty($page['title']) ? ' | <i>'.$page['title'].'</i>':'').'
  </div>
</div>';
}

echo '
<div id="the_page">
'.(!empty($page['caption']) ? '<p class="caption">'.$page['caption'].'</p>' : null);
?>