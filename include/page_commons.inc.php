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

defined('LEXIGLOT_PATH') or die ('Hacking attempt!');

global $page, $conf, $user, $template;

$template->set_filenames(array(
  'header' => 'header.tpl',
  'footer' => 'footer.tpl',
  ));
    
$template->assign(array(
  'INSTALL_NAME' => $conf['install_name'],
  'SELF_URL' => get_url_string(),
  'USER' => array(
    'USERNAME' => $user['username'],
    'EMAIL' => $user['email'],
    'STATUS' => $user['status'],
    ),
  'CONF' => $conf,
  ));
  
foreach (array('infos','warnings','errors') as $state)
{
  if (isset($_SESSION['page_'.$state]))
  {
    $page[$state] = array_merge($page[$state], $_SESSION['page_'.$state]);
    unset($_SESSION['page_'.$state]);
  }
}
    
$template->assign(array(
  'page_errors' => $page['errors'],
  'page_warnings' => $page['warnings'],
  'page_infos' => $page['infos'],
  ));
  
?>