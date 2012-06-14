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

defined('PATH') or die('Hacking attempt!');
isset($local_user) or die('Hacking attempt!');

$my_languages =  !empty($local_user['my_languages']) ? '"'.implode_array(create_permissions_array($local_user['my_languages'])).'"' : 'NULL';
$all_languages = implode_array(create_permissions_array(array_keys($conf['all_languages'])));
$all_sections =  implode_array(create_permissions_array(array_keys($conf['all_sections'])));

switch ($local_user['status'].'->'.$new_status)
{
  // * to manager (check manage_perms)
  case 'translator->manager':
  case 'admin->manager':
    array_push($sets, 'manage_perms = IFNULL(manage_perms, \''.$conf['default_manager_perms'].'\')');
    break;
    
  // visitor to manager (languages/sections depend on config & check manage_perms)
  case 'visitor->manager':
    array_push($sets, 'manage_perms = \''.$conf['default_manager_perms'].'\'');
    
  // visitor to translator (languages/sections depend on config)
  case 'visitor->translator':
    if ($conf['user_default_language'] == 'all')
    {
      array_push($sets, 'languages = "'.$all_languages.'"');
    }
    else if ($conf['user_default_language'] == 'own')
    {
      array_push($sets, 'languages = '.$my_languages .'');
    }
    if ($conf['user_default_section'] == 'all')
    {
      array_push($sets, 'sections = "'.$all_sections.'"');
    }
    break;
    
  // * to admin (all languages/sections)
  case 'visitor->admin':
  case 'translator->admin':
  case 'manager->admin':
    array_push($sets, 'languages = "'.$all_languages.'"');
    array_push($sets, 'sections = "'.$all_sections.'"');
    break;
    
  // * to visitor (none languages/sections)
  case 'translator->visitor':
  case 'manager->visitor':
  case 'admin->visitor':
    array_push($sets, 'manage_perms = NULL');
    array_push($sets, 'languages = NULL');
    array_push($sets, 'sections = NULL');
    break;
    
  // others, do nothing
  case 'manager->translator':
  case 'admin->translator':
    break;
}

?>