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

// string delimiter
$conf['quote'] = "'";
$conf['eol'] = "\n";

// plain text types
$conf['plain_types'] = array('html', 'htm', 'txt');

// flags dir
$conf['flags_dir'] = PATH.'flags/';

// local dir
$conf['local_dir'] = PATH.'local/';

// guest id in the users_table
$conf['guest_id'] = 1;

// user fields, for combine with other application
$conf['user_fields'] = array(
  'id' => 'id',
  'username' => 'username',
  'password' => 'password',
  'email' => 'email'
  );
  
// additional fields to complete when a new user register (only for a external users table)
$conf['additional_user_infos'] = array();

$conf['minimum_progress_for_language_reference'] = 0.5;
  
// session prefix
$conf['session_prefix'] = 'lexiglot_';

// the name of the cookie used to stay logged
$conf['remember_me_name'] = $conf['session_prefix'].'remember_me';

// time of validity for "remember me" cookies, in seconds.
$conf['remember_me_length'] = 864000;

// function to hash the user password into the database
$conf['pass_convert'] = create_function('$s', 'return md5($s);');

// a php snippet to execute before include language files
$conf['exec_before_file'] = null;

// a file copied in all new folders created by Lexiglot
$conf['copy_file_to_repo'] = null;

// email used for send automated mails, can be a dummy email
$conf['system_email'] = 'Lexiglot <noreply@'.$_SERVER['HTTP_HOST'].'>';

// how to navigate : sections, languages, both
$conf['navigation_type'] = 'both';

// cache validation time
$conf['stats_cache_life'] = 172800;

// status, /!\ don't modify /!\
$conf['status'] = array(
  'guest'       => 1,
  'visitor'     => 2,
  'translator'  => 3,
  'manager'     => 4,
  'admin'       => 5,
  );

?>