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

define('LEXIGLOT_PATH', './');
include(LEXIGLOT_PATH . 'include/common.inc.php');


// +-----------------------------------------------------------------------+
// |                         SEND REQUEST FOR NEW LANGUAGE
// +-----------------------------------------------------------------------+
if (isset($_POST['request_language']))
{
  if (!verify_ephemeral_key(@$_POST['key']))
  {
    array_push($page['errors'], 'Invalid/expired form key');
  }
  else 
  {
    if (empty($_POST['language']))
    {
      array_push($page['errors'], 'Language name is empty');
    }
    if (strlen(@$_POST['message']) > 2000)
    {
      array_push($page['errors'], 'Message is too long. max: 2000 chars');
    }
  }
  
  if (empty($page['errors']))
  {
    // send mail
    $subject = '['.strip_tags($conf['install_name']).'] New language request';

    $content = 
$user['username'].' from '.strip_tags($conf['install_name']).' has just sent a request for the creation of a new language : <b>'.strip_tags($_POST['language']).'</b>.';

    if (!empty($_POST['message']))
    {
      $content .='<br>
<br>
Here is his message :<br>
-----------------------------------------------------------------------<br>
<br>
'.$_POST['message'].'<br>
<br>
-----------------------------------------------------------------------<br>';
    }

    $args = array(
      'from' => format_email($user['email'], $user['username']),
      'content_format' => 'text/html',
    );
    
    $result = send_mail(
      get_admin_email(), 
      $subject, $content, $args,
      'Language request for '.strip_tags($_POST['language'])
      );

    if ($result)
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
// |                         SAVE TALK
// +-----------------------------------------------------------------------+
else if (isset($_POST['save_talk']))
{
  if (!verify_ephemeral_key(@$_POST['key'], '', false))
  {
    array_push($page['errors'], 'Invalid/expired form key');
  }
  else 
  {
    $id = $_GET[ $_POST['type'] ];
    
    $query = '
INSERT INTO '.TALKS_TABLE.'(
    type,
    id,
    content,
    last_edit
  )
  VALUES (
    "'.$_POST['type'].'",
    "'.$id.'",
    "'.mres($_POST['content']).'",
    NOW()
  )
  ON DUPLICATE KEY UPDATE
    content = "'.mres($_POST['content']).'",
    last_edit = NOW()
;';
    $db->query($query);
  }
}


// +-----------------------------------------------------------------------+
// |                         REQUEST A NEW LANGUAGE
// +-----------------------------------------------------------------------+
if ( isset($_GET['request_language']) and is_translator() and $conf['user_can_add_language'] )
{
  foreach (array_keys($conf['all_languages']) as $lang)
  {
    $template->append('all_languages', array(
      'NAME' => get_language_name($lang),
      'FLAG' => get_language_flag($lang),
      ));
  }
  
  $template->assign(array(
    'KEY' => get_ephemeral_key(2),
    'request' => array(
      'LANGUAGE' => @$_POST['language'],
      'CONTENT' => @$_POST['message'],
      ),
    ));
  
  $template->close('lang_request');
}

// +-----------------------------------------------------------------------+
// |                         TALKS
// +-----------------------------------------------------------------------+
else if ( isset($_GET['talk']) and $conf['use_talks'] )
{
  $editable = false;
  
  if (isset($_GET['language']))
  {
    $page['type'] = 'language';
    $page['item'] = $_GET['language'];
    
    if (is_translator($page['item'], null))
    {
      $editable = true;
    }
  }
  else if (isset($_GET['project']))
  {
    $page['type'] = 'project';
    $page['item'] = $_GET['project'];
    
    if (is_translator(null, $page['item']))
    {
      $editable = true;
    }
  }
  else
  {
    redirect('index.php');
  }
  
  $query = '
SELECT content
  FROM '.TALKS_TABLE.'
  WHERE
    type = "'.$page['type'].'"
    AND id = "'.$page['item'].'"
;';
  $result = $db->query($query);
  
  $content = '<h2>This talk is empty</h2> <p>Fell free to add notes about the translation.</p>';
  if ($result->num_rows)
  {
    list($content) = $result->fetch_row();
  }
  
  $template->assign(array(
    'TYPE' => $page['type'],
    'ITEM' => $page['item'],
    'EDITABLE' => $editable,
    'CONTENT' => $content,
    ));
  
  if ($editable)
  {
    $template->assign(array(
    'SECRET_KEY' => get_ephemeral_key(4),
    'CURRENT_DATE' => format_date(date('Y-m-d')),
    ));
  }
    
  $template->close('talks');
}
else
{
  redirect('index.php');
}

?>