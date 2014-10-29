<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2013 Damien Sorel       http://www.strangeplanet.fr |
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

/**
 * This page is called by a SVN hook to update a particular repository
 * Or by a cron job to update all repositories
 */

define('LEXIGLOT_PATH', '../');
define('IN_AJAX', 1);
include(LEXIGLOT_PATH . 'include/common.inc.php');


// specific repo
if (!empty($_GET['repo']))
{
  $query = '
SELECT id FROM '.PROJECTS_TABLE.'
  WHERE svn_url LIKE(\''.mres($_GET['repo']).'%\')
    AND last_update < NOW() - INTERVAL 1 MINUTE
;';

  $repos = array_from_query($query, 'id');
}
// x oldest repos
else if (!empty($_GET['auto']) && is_numeric($_GET['auto']))
{
  $query = '
SELECT id FROM '.PROJECTS_TABLE.'
  WHERE last_update < NOW() - INTERVAL 1 HOUR
  LIMIT '.intval($_GET['auto']).'
;';

  $repos = array_from_query($query, 'id');
}


if (!empty($repos))
{
  foreach ($repos as $repo_id)
  {
    svn_update($conf['local_dir'].$repo_id, $conf['all_projects'][$repo_id]);
  }
  
  $query = '
UPDATE '.PROJECTS_TABLE.'
  SET last_update = NOW()
  WHERE id IN(\''.implode('\',\'', $repos).'\')
;';
  $db->query($query);
}

?>