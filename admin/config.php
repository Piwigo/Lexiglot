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

if (isset($_POST['save_config']))
{
  $new_conf = array(
    'install_name' =>           $_POST['install_name'],
    'intro_message' =>          $_POST['intro_message'],
    'default_language' =>       $_POST['default_language'],
    'var_name' =>               $_POST['var_name'],
    'delete_done_rows' =>       set_boolean(isset($_POST['delete_done_rows'])),
    'use_stats' =>              set_boolean(isset($_POST['use_stats'])),
    'allow_edit_default' =>     set_boolean(isset($_POST['allow_edit_default'])),
    
    'access_to_guest' =>        set_boolean(isset($_POST['access_to_guest'])),
    'allow_registration' =>     set_boolean(isset($_POST['allow_registration'])),
    'allow_profile' =>          set_boolean(isset($_POST['allow_profile'])),
    'user_can_add_language' =>  set_boolean(isset($_POST['user_can_add_language'])),
    
    'user_default_language' =>  $_POST['user_default_language'],
    'user_default_project' =>   $_POST['user_default_project'],
    'language_default_user' =>  $_POST['language_default_user'],
    'project_default_user' =>   $_POST['project_default_user'],
    
    'new_file_content' =>       $_POST['new_file_content'],
    );
    
  if (function_exists('exec'))
  {
    $new_conf = array_merge($new_conf, array(
      'svn_activated' =>        set_boolean(isset($_POST['svn_activated'])),
      'svn_server' =>           rtrim($_POST['svn_server'], '/').'/',
      'svn_path' =>             $_POST['svn_path'],
      'svn_user' =>             $_POST['svn_user'],
      'svn_password' =>         $_POST['svn_password'],
      ));
      
    if ($new_conf['svn_activated'] == 'true' and $new_conf['svn_server'] != $conf['svn_server'])
    { // we must relocate all working directories
      foreach ($conf['all_projects'] as $key => $row)
      {
        svn_switch($new_conf['svn_server'].$row['directory'], $conf['local_dir'].$row['id'], $conf['svn_server'].$row['directory']);
      }
    }
  }
    
  foreach ($new_conf as $param => $value)
  {
    $query = '
UPDATE '.CONFIG_TABLE.'
  SET value = "'.mres($value).'"
  WHERE param = "'.mres($param).'"
;';
    mysql_query($query);
  }
  
  load_conf_db($conf);
  array_push($page['infos'], 'Configuration saved in the database.');
}

echo '
<form action="admin.php?page=config" method="post" id="config">
  <fieldset class="common">
    <legend>Engine configuration</legend>
    
    <table class="common">
      <tr>
        <td>Installation name :</td>
        <td><textarea name="install_name" cols="60" rows="2">'.$conf['install_name'].'</textarea></td>
      </tr>
      <tr>
        <td>Homepage message :</td>
        <td><textarea name="intro_message" cols="60" rows="2">'.$conf['intro_message'].'</textarea></td>
      </tr>
      <tr>
        <td>Default language :</td>
        <td><input type="text" name="default_language" value="'.$conf['default_language'].'"></td>
      </tr>
      <tr>
        <td>Var name :</td>
        <td><input type="text" name="var_name" value="'.$conf['var_name'].'"></td>
      </tr>
      <tr>
        <td>Allow modification of default_language :</td>
        <td><input type="checkbox" name="allow_edit_default" value="1" '.($conf['allow_edit_default']?'checked="checked"':'').'></td>
      </tr>
      <tr>
        <td>Delete strings after commit :</td>
        <td><input type="checkbox" name="delete_done_rows" value="1" '.($conf['delete_done_rows']?'checked="checked"':'').'></td>
      </tr>
      <tr>
        <td>Display statistics :</td>
        <td><input type="checkbox" name="use_stats" value="1" '.($conf['use_stats']?'checked="checked"':'').'></td>
      </tr>
    </table>
  </fieldset>';
  
if (function_exists('exec'))
{
  echo '
  <fieldset class="common">
    <legend>Subversion configuration</legend>
    
    <table class="common">
      <tr>
        <td>Activate Subversion client :</td>
        <td><input type="checkbox" name="svn_activated" value="1" '.($conf['svn_activated']?'checked="checked"':'').'></td>
      </tr>
      <tr class="svn" '.(!$conf['svn_activated']?'style="display:none;"':'').'>
        <td>Subversion server :</td>
        <td><input type="text" name="svn_server" value="'.$conf['svn_server'].'" size="30"></td>
      </tr>
      <tr class="svn" '.(!$conf['svn_activated']?'style="display:none;"':'').'>
        <td>Subversion path :</td>
        <td><input type="text" name="svn_path" value="'.$conf['svn_path'].'"></td>
      </tr>
      <tr class="svn" '.(!$conf['svn_activated']?'style="display:none;"':'').'>
        <td>Subversion user :</td>
        <td><input type="text" name="svn_user" value="'.$conf['svn_user'].'"></td>
      </tr>
      <tr class="svn" '.(!$conf['svn_activated']?'style="display:none;"':'').'>
        <td>Subversion password :</td>
        <td><input type="text" name="svn_password" value="'.$conf['svn_password'].'"></td>
      </tr>
    </table>
  </fieldset>';
  
  $page['script'].= '
  $("input[name=\'svn_activated\']").change(function () {
    if ($(this).is(":checked")) {
      $("tr.svn").show();
    } else {
      $("tr.svn").hide();
    }
  });';
}
else
{
  array_push($page['warnings'], 'SVN support not available. You can not use <b>exec()</b> function on this server.');
}

echo '
  <fieldset class="common">
    <legend>Users configuration</legend>
    
    <table class="common">
      <tr>
        <td>Read access for guests :</td>
        <td><input type="checkbox" name="access_to_guest" value="1" '.($conf['access_to_guest']?'checked="checked"':'').'></td>
      </tr>
      <tr>
        <td>Allow new users :</td>
        <td><input type="checkbox" name="allow_registration" value="1" '.($conf['allow_registration']?'checked="checked"':'').'></td>
      </tr>
      <tr>
        <td>Allow users to change their password and mail :</td>
        <td><input type="checkbox" name="allow_profile" value="1" '.($conf['allow_profile']?'checked="checked"':'').'></td>
      </tr>
      <tr>
        <td>Translators can add languages and projects (according to their rights) :</td>
        <td><input type="checkbox" name="user_can_add_language" value="1" '.($conf['user_can_add_language']?'checked="checked"':'').'></td>
      </tr>
      </table>
  </fieldset>
  
  <fieldset class="common">
    <legend>Permissions</legend>
    
    <table class="common">
      <tr>
        <td>A new translator has access to :</td>
        <td>
          <label><input type="radio" name="user_default_language" value="all" '.($conf['user_default_language']=='all' ? 'checked="checked"':'').'> All languages</label>
          <label><input type="radio" name="user_default_language" value="own" '.($conf['user_default_language']=='own' ? 'checked="checked"':'').'> His languages</label>
          <label><input type="radio" name="user_default_language" value="none" '.($conf['user_default_language']=='none' ? 'checked="checked"':'').'> No language</label>
        </td>
      </tr>
      <tr>
        <td>A new translator has access to :</td>
        <td>
          <label><input type="radio" name="user_default_project" value="all" '.($conf['user_default_project']=='all' ? 'checked="checked"':'').'> All projects</label>
          <label><input type="radio" name="user_default_project" value="none" '.($conf['user_default_project']=='none' ? 'checked="checked"':'').'> No project</label>
        </td>
      </tr>
      <tr>
        <td>A new language is accessible to :</td>
        <td>
          <label><input type="radio" name="language_default_user" value="all" '.($conf['language_default_user']=='all' ? 'checked="checked"':'').'> All translators</label>
          <label><input type="radio" name="language_default_user" value="none" '.($conf['language_default_user']=='none' ? 'checked="checked"':'').'> No translator</label>
        </td>
      </tr>
      <tr>
        <td>A new project is accessible to :</td>
        <td>
          <label><input type="radio" name="project_default_user" value="all" '.($conf['project_default_user']=='all' ? 'checked="checked"':'').'> All translators</label>
          <label><input type="radio" name="project_default_user" value="none" '.($conf['project_default_user']=='none' ? 'checked="checked"':'').'> No translator</label>
        </td>
      </tr>
    </table>
  </fieldset>
  
  <fieldset class="common">
    <legend>Add this text at the begining of new PHP files</legend>

    <div style="text-align:center;">
      (must be formatted as a <a href="http://php.net/manual/en/language.basic-syntax.comments.php">PHP comment</a>)<br>
      <textarea name="new_file_content" style="width:80%;color:#008000;" rows="5">'.$conf['new_file_content'].'</textarea>
    </div>
  </fieldset>
  
  <div class="centered">
  	<input type="submit" name="save_config" value="Save" class="blue big">
  </div>
</form>';

load_jquery('autoresize');

$page['script'].= '
$("textarea").autoResize({
  maxHeight:2000,
  extraSpace:5
});';

?>