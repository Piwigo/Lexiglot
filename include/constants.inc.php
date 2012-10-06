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

defined('LEXIGLOT_PATH') or die('Hacking attempt!');

// database tables
defined('USERS_TABLE') or define('USERS_TABLE', DB_PREFIX.'users');
define('ROWS_TABLE', DB_PREFIX.'rows');
define('USER_INFOS_TABLE', DB_PREFIX.'user_infos');
define('USER_LANGUAGES_TABLE', DB_PREFIX.'user_languages');
define('USER_PROJECTS_TABLE', DB_PREFIX.'user_projects');
define('PROJECTS_TABLE', DB_PREFIX.'projects');
define('STATS_TABLE', DB_PREFIX.'stats');
define('TALKS_TABLE', DB_PREFIX.'talks');
define('LANGUAGES_TABLE', DB_PREFIX.'languages');
define('CONFIG_TABLE', DB_PREFIX.'config');
define('CATEGORIES_TABLE', DB_PREFIX.'categories');
define('MAIL_HISTORY_TABLE', DB_PREFIX.'mail_history');

// folders
define('TEMPLATE_PATH', LEXIGLOT_PATH . 'template/');
define('DATA_LOCATION', LEXIGLOT_PATH . '_data/');

?>