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

switch ($old_status.'->'.$new_status)
{
  // manager to translator (redefine special_perms)
  case 'manager->translator' :
    if (count($local_user['languages']) == 1)
    {
      array_push($sets, 'special_perms = "'.$local_user['languages'][0].'"');
    }
    else
    {
      array_push($sets, 'special_perms = ""');
    }
    break;
  
  // visitor to translator (languages/sections depend on config)
  case 'visitor->translator':
    if ($conf['user_default_language'] == 'all')
    {
      array_push($sets, 'languages = "'.implode(',', array_keys($conf['all_languages'])).'"');
    }
    else if ($conf['user_default_language'] == 'own')
    {
      array_push($sets, 'languages = "'.implode(',', $local_user['my_languages']).'"');
    }
    if ($conf['user_default_section'] == 'all')
    {
      array_push($sets, 'sections = "'.implode(',', array_keys($conf['all_sections'])).'"');
    }
    break;
    
  // visitor to manager (languages/sections depend on config)
  case 'visitor->manager':
    if ($conf['user_default_language'] == 'all')
    {
      array_push($sets, 'languages = "'.implode(',', array_keys($conf['all_languages'])).'"');
    }
    else if ($conf['user_default_language'] == 'own')
    {
      array_push($sets, 'languages = "'.implode(',', $local_user['my_languages']).'"');
    }
    if ($conf['user_default_section'] == 'all')
    {
      array_push($sets, 'sections = "'.implode(',', array_keys($conf['all_sections'])).'"');
    }
  // * to manager (erase special_perms and check manage_perms)
  case 'translator->manager':
  case 'admin->manager':
    array_push($sets, 'manage_perms = IFNULL(manage_perms, \''.DEFAULT_MANAGER_PERMS.'\')');
    array_push($sets, 'special_perms = ""');
    break;
    
  // * to admin (all languages/sections)
  case 'visitor->admin':
  case 'translator->admin':
  case 'manager->admin':
    array_push($sets, 'languages = "'.implode(',', array_keys($conf['all_languages'])).'"');
    array_push($sets, 'sections = "'.implode(',', array_keys($conf['all_sections'])).'"');
    array_push($sets, 'special_perms = ""');
    break;
    
  // * to visitor (none languages/sections)
  case 'translator->visitor':
  case 'manager->visitor':
  case 'admin->visitor':
    array_push($sets, 'languages = ""');
    array_push($sets, 'sections = ""');
    array_push($sets, 'special_perms = ""');
    break;
}

?>