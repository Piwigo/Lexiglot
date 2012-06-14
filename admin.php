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

define('PATH', './');
define('IN_ADMIN', 1);
include(PATH.'include/common.inc.php');
include(PATH.'admin/include/functions.inc.php');

// check rights
if ( !is_manager() and !is_admin() )
{
  array_push($page['errors'], 'Your are not allowed to view this page. <a href="user.php?login">Login</a>.');
  print_page();
}

if (is_manager())
{
  array_push($page['infos'], 'As a project(s) manager you can only view information relative to your project(s).');
}

// +-----------------------------------------------------------------------+
// |                         LOCATION
// +-----------------------------------------------------------------------+
// admin pages
if (is_admin())
{
  $pages = array(
    'history' => 'History', 
    'commit' => 'Commit',
    'users' => 'Users',
    'projects' => 'Projects',
    'languages' => 'Languages', 
    'mail' => 'Mail archive',
    'config' => 'Configuration',
    'maintenance' => 'Maintenance',
    );
  $sub_pages = array(
    'user_perm' => 'User permissions',
    );
}
// manager pages
else if (is_manager())
{
  $pages = array(
    'history' => 'History', 
    'commit' => 'Commit',
    'projects' => 'Projects',
    );
    
  if ($user['manage_perms']['can_change_users_projects'])
  {
    $pages['users'] = 'Users';
    $sub_pages = array(
      'user_perm' => 'User permissions',
      );
  }
}
    

if ( isset($_GET['page']) and array_key_exists($_GET['page'], array_merge($pages, $sub_pages)) )
{
  $page['page'] = $_GET['page'];
}
else
{
  $page['page'] = 'history';
}

// +-----------------------------------------------------------------------+
// |                         PAGE OPTIONS
// +-----------------------------------------------------------------------+
// page title
$page['window_title'] = $page['title'] = 'Admin';

// tabsheet
$tabsheet['param'] = 'page';
$tabsheet['selected'] = $page['page'];
foreach ($pages as $file => $name)
{
  $tabsheet['tabs'][ $file ] = array($name, null, true);
}
if ( !array_key_exists($page['page'], $pages) )
{
  $tabsheet['tabs'][ $page['page'] ] = array($sub_pages[ $page['page'] ]);
}

// +-----------------------------------------------------------------------+
// |                         MAIN
// +-----------------------------------------------------------------------+
include(PATH.'admin/'.$page['page'].'.php');

print_page();
?>