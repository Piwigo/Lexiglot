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
include(PATH.'include/common.inc.php');

$page['header'].= '
<link type="text/css" rel="stylesheet" media="screen" href="template/public.css">';

// +-----------------------------------------------------------------------+
// |                         SEND REQUEST FOR NEW LANGUAGE
// +-----------------------------------------------------------------------+
if (isset($_POST['request_language']))
{
  if (!verify_ephemeral_key(@$_POST['key']))
  {
    array_push($page['errors'], 'Invalid/expired form key');
    print_page();
  }
  
  if (empty($_POST['language'])) array_push($page['errors'], 'Language name is empty');
  if (strlen(@$_POST['message']) > 2000) array_push($page['errors'], 'Message is too long. max: 2000 chars');
  
  if (empty($page['errors']))
  {
    // get admin emails
    $query = '
SELECT
  '.$conf['user_fields']['email'].' as email,
  '.$conf['user_fields']['username'].' as username
  FROM '.USERS_TABLE.' as u
    INNER JOIN '.USER_INFOS_TABLE.' as i
     ON u.'.$conf['user_fields']['id'].' = i.user_id
  WHERE i.status = "admin"
;';
    $to = hash_from_query($query);
    array_walk($to, function(&$k,$v){$k=format_email($k['email'],$k['username']);});
    
    // send mail
    $subject = '['.strip_tags($conf['install_name']).'] New language request';

    $content = 
$user['username'].' from '.strip_tags($conf['install_name']).' has just sent a request for the creation of a new language : '.strip_tags($_POST['language']).'.';

    if (!empty($_POST['message']))
    {
      $content .='

Here is his mssage :
-----------------------------------------------------------------------

'.$_POST['message'].'

-----------------------------------------------------------------------';
    }

    $args = array(
      'from' => format_email($user['email'], $user['username']),
    );

    if (send_mail(implode(',',$to), $subject, $content, $args))
    {
      array_push($page['infos'], 'Request sended');
      unset($_POST);
    }
    else
    {
      array_push($page['errors'], 'An error occured');
    }
  }
}

// +-----------------------------------------------------------------------+
// |                         REQUEST A NEW LANGUAGE
// +-----------------------------------------------------------------------+
if ( isset($_GET['request_language']) and is_translator() and $conf['user_can_add_language'] )
{
  echo '
  <form method="post" action="">
  <fieldset class="common">
    <legend>Request a new language</legend>
    
    <div class="ui-state-highlight" style="padding: 0.7em;margin-bottom:10px;font-weight:bold;">
      Here is a full list of available languages. If you can\'t find your, feel free to send us a message, we will consider your request as soon as possible.
    </div>
  
    <ul id="languages" class="list-cloud">';

    // languages list
    foreach (array_keys($conf['all_languages']) as $lang)
    {
      echo '
      <li>'.get_language_name($lang).' '.get_language_flag($lang).'</li>';
    }
    
    echo '
    </ul>
  </fieldset>
  
  <fieldset>
    <table class="login">
      <tr>
        <td>Language name :</td>
        <td><input type="text" name="language" value="'.@$_POST['language'].'"></td>
      </tr>
      <tr>
        <td>Message (optional) :</td>
        <td><textarea name="message" cols="50" rows="5">'.@$_POST['message'].'</textarea></td>
      </tr>
      <tr>
        <td><input type="hidden" name="key" value="'.get_ephemeral_key(2).'"></td>
        <td><input type="submit" name="request_language" value="Send" class="blue"></td>
      </tr>
    </table>
  </fieldset>
  </form>';
}
else
{
  array_push($page['errors'], 'Access denied.');
}

print_page();
?>