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

// +-----------------------------------------------------------------------+
// |                         SEND MESSAGE
// +-----------------------------------------------------------------------+
if (isset($_POST['send_message']))
{
  if (!verify_ephemeral_key(@$_POST['key']))
  {
    array_push($page['errors'], 'Invalid/expired form key');
    print_page();
  }
  
  if (strlen($_POST['subject']) < 10) array_push($page['errors'], 'Subject is too short');
  if (strlen($_POST['message']) < 10) array_push($page['errors'], 'Message is too short');
  if (strlen($_POST['message']) > 2000) array_push($page['errors'], 'Message is too long. max: 2000 chars');
  
  if (empty($page['errors']))
  {
    // get user mail
    $query = '
SELECT
  '.$conf['user_fields']['email'].' as email,
  '.$conf['user_fields']['username'].' as username
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['id'].' = '.mres($_POST['user_id']).'
;';
    $to = mysql_fetch_assoc(mysql_query($query));

    $content = 
$user['username'].' from '.strip_tags($conf['install_name']).' has sent you a message. You can reply to him by replying to this e-mail.

The message reads as follows:
-----------------------------------------------------------------------

'.$_POST['message'].'

-----------------------------------------------------------------------

Lexiglot - A PHP based translation tool';

    $args = array(
      'from' => format_email($user['email'], $user['username']),
    );

    if (send_mail($to['email'], $_POST['subject'], $content, $args))
    {
      array_push($page['infos'], 'Mail sended to <i>'.$to['username'].'</i>');
      unset($_POST);
    }
    else
    {
      array_push($page['errors'], 'An error occured');
    }
  }
}

// +-----------------------------------------------------------------------+
// |                         SAVE PROFILE
// +-----------------------------------------------------------------------+
if (isset($_POST['save_profile']))
{
  if (!verify_ephemeral_key(@$_POST['key']))
  {
    array_push($page['errors'], 'Invalid/expired form key');
    print_page();
  }
  
  $sets_user = $sets_infos = array();
  
  if ($conf['allow_profile'])
  {
    // save mail
    $mail_error = validate_mail_address($user['id'], $_POST['email']);
    if ($mail_error != '')
    {
      array_push($page['errors'], $mail_error);
    }
    else
    {
      array_push($sets_user, $conf['user_fields']['email'].' = "'.mres($_POST['email']).'"');
      $user['email'] = $_POST['email'];
    }
    // save password
    if (!empty($_POST['password_new']))
    {
      if (empty($_POST['password']) or $conf['pass_convert']($_POST['password']) != $user['password'])
      {
        array_push($page['errors'], 'Please enter your current password, password not changed.');
      }
      else if ($_POST['password_new'] != $_POST['password_confirm'])
      {
        array_push($page['errors'], 'Please confirm your new password, password not changed.');
      }
      else
      {
        array_push($sets_user, $conf['user_fields']['password'].' = "'.$conf['pass_convert']($_POST['password_new']).'"');
      }
    }
  }
  
  // save my languages
  if (!empty($_POST['my_languages']))
  {
    array_push($sets_infos, 'my_languages = "'.mres(implode(',', $_POST['my_languages'])).'"');
    $user['my_languages'] = $_POST['my_languages'];
  }
  else
  {
    array_push($sets_infos, 'my_languages = NULL');
    $user['my_languages'] = array();
  }
  // save number of rows
  if ( empty($_POST['nb_rows']) or !preg_match('#^[0-9]+$#', $_POST['nb_rows']) )
  {
    array_push($page['errors'], 'The number of rows rows must be a non-null integer.');
  }
  else
  {
    array_push($sets_infos, 'nb_rows = '.$_POST['nb_rows']);
    $user['nb_rows'] = $_POST['nb_rows'];
  }
  // save email privacy
  array_push($sets_infos, 'email_privacy = "'.$_POST['email_privacy'].'"');
  
  // write in db
  if (!empty($sets_user))
  {
    $query = '
UPDATE '.USERS_TABLE.'
  SET
    '.implode(",    \n", $sets_user).'
  WHERE '.$conf['user_fields']['id'].' = '.$user['id'].'
;';
    mysql_query($query);
  }
  if (!empty($sets_infos))
  {
    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET
    '.implode(",    \n", $sets_infos).'
  WHERE user_id = '.$user['id'].'
;';
    mysql_query($query);
  }
  
  // reload the profile
  $user = build_user($user['id']);
  
  array_push($page['infos'], 'Profile saved.');
}

// +-----------------------------------------------------------------------+
// |                         PROFILE PAGE
// +-----------------------------------------------------------------------+
if ( isset($_GET['user_id']) )
{
  if ($_GET['user_id'] == $conf['guest_id'])
  {
    $_GET['user_id'] = 0;
  }
  
  $local_user = build_user($_GET['user_id']);
  
  if (!$local_user)
  {
    array_push($page['errors'], 'Wrong user id!');
  }
  else
  {
    $page['window_title'] = $page['title'] = $local_user['username'];
  }
}
else if (!is_guest())
{
  $local_user = $user;
  define('PRIVATE_PROFILE', true);
  
  $page['window_title'] = $page['title'] = 'Profile';

  if (isset($_GET['new']))
  {
    array_push($page['infos'], '<b>Welcome on '.strip_tags($conf['install_name']).' !</b> Your registration is almost complete...<br>
      Before all please say us what languages you can speak, this way your registration as translator will be faster.
      ');
  }
}
else
{
  redirect('index.php');
}
  
if ($local_user)
{
  /* public profile */
  echo '
  <form action="" method="post"> 
    <fieldset class="common">
      <legend>Public profile</legend>
      <table class="login">
        <tr>
          <td>Username :</td>
          <td>'.$local_user['username'].'</td>
        </tr>
        <tr>
          <td>Status :</td>
          <td><i>'.$local_user['status'].'</i></td>
        </tr>';
        if ($local_user['email_privacy'] == 'public')
        {
          echo '
          <tr>
            <td>E-mail :</td>
            <td><i><a href="mailto:'.$local_user['email'].'">'.$local_user['email'].'</a></i></td>
          </tr>';
        }
        echo '
        <tr>
          <td>Languages assigned :</td>
          <td>';
          $f=1;
          foreach ($local_user['languages'] as $lang)
          {
            if(!$f)echo ', ';$f=0;
            echo '
            <a href="'.get_url_string(array('language'=>$lang), true, 'language').'" title="'.get_language_name($lang).'" class="clean">'.get_language_flag($lang, 'name').'</a>';
          }
          echo '</td>
        </tr>
        <tr>
          <td>Projects assigned :</td>
          <td>';
          $f=1;
          foreach ($local_user['sections'] as $section)
          {
            if(!$f)echo ', ';$f=0;
            echo '
            <a href="'.get_url_string(array('section'=>$section), true, 'section').'" class="clean">'.get_section_name($section).'</a>';
          }
          echo '</td>
        </tr>';
      if ($local_user['status'] == 'manager')
      {
        echo '
        <tr>
          <td>Projects managed :</td>
          <td>';
          $f=1;
          foreach ($local_user['manage_sections'] as $section)
          {
            if(!$f)echo ', ';$f=0;
            echo '
            <a href="'.get_url_string(array('section'=>$section), true, 'section').'" class="clean">'.get_section_name($section).'</a>';
          }
          echo '</td>
        </tr>';
      }
      echo '
      </table>
    </fieldset>';
    
    /* registration */
    if ( defined('PRIVATE_PROFILE') and $conf['allow_profile'] )
    {
      echo '
      <fieldset class="common">
        <legend>Registration</legend>
        <table class="login">
          <tr>
            <td><label for="email">Email address :</label></td>
            <td><input type="text" name="email" id="email" size="25" maxlength="64" value="'.$local_user['email'].'"></td>
          </tr>
          <tr>
            <td><label for="password_new">New password :</label></td>
            <td><input type="password" name="password_new" id="password_new" size="25" maxlength="32"> <i>(leave blank to not change)</i></td>
          </tr>
          <tr>
            <td><label for="password_confirm">Confirm password :</label></td>
            <td><input type="password" name="password_confirm" id="password_confirm" size="25" maxlength="32"></td>
          </tr>
          <tr>
            <td><label for="password">Current password :</label></td>
            <td><input type="password" name="password" id="password" size="25" maxlength="32"></td>
          </tr>
        </table>
      </fieldset>';
    }
    
    /* preference */
    if (defined('PRIVATE_PROFILE'))
    {
      echo '    
      <fieldset class="common">
        <legend>Preferences</legend>
        <table class="login">
          <tr>
            <td>Languages I speak :</td>
            <td>
            '.(isset($_GET['new']) ? '<div class="ui-state-warning ui-corner-all" style="padding:5px;"> <span class="ui-icon ui-icon-info" style="float:left; margin:3px 5px 0 0;"></span>' : null).'
              <select id="my_languages" name="my_languages[]" multiple="multiple" data-placeholder="Select languages..." style="width:500px;">';
              foreach ($conf['all_languages'] as $row)
              {
                if ($row['id'] == $conf['default_language']) continue;
                echo '
                <option'.(in_array($row['id'],$user['my_languages']) ? ' selected="selected"' : null).' value="'.$row['id'].'" style="color:#111 !important;">'.$row['name'].'</option>';
              }
              echo '
              </select>
              <br>
              <i>(please note this only for information, this doesn\'t change languages you have access to)</i>
            '.(isset($_GET['new']) ? '</div>' : null).'
            </td>
          </tr>
          <tr>
            <td><label for="nb_rows">Number of rows per page :</label></td>
            <td><input type="text" name="nb_rows" id="nb_rows" size="3" maxlength="3" value="'.$user['nb_rows'].'"></td>
          </tr>
          <tr>
            <td>Email visibility :</td>
            <td>
              <label><input type="radio" name="email_privacy" value="public" '.($user['email_privacy']=='public' ? 'checked="checked"' : null).'> All registered users can view my email and send me messages</label><br>
              <label><input type="radio" name="email_privacy" value="hidden" '.($user['email_privacy']=='hidden' ? 'checked="checked"' : null).'> Only admins can view my email and registered users can send me messages through Lexiglot</label><br>
              <label><input type="radio" name="email_privacy" value="private" '.($user['email_privacy']=='private' ? 'checked="checked"' : null).'> Only admins can view my email and send me messages</label>
            </td>
          </tr>
          <tr>
            <td><input type="hidden" name="key" value="'.get_ephemeral_key(2).'"></td>
            <td><input type="submit" name="save_profile" value="Submit" class="blue"></td>
          </tr>
        </table>
      </fieldset>';
    
      load_jquery('chosen');  
      $page['script'].= '
      $("#my_languages").chosen();';
    }
    
    /* activity */
    $query = '
SELECT 
    COUNT(*) AS total,
    LEFT(last_edit, 10) as day
  FROM '.ROWS_TABLE.' 
  WHERE user_id = '.$local_user['id'].'
  GROUP BY day
  ORDER BY last_edit ASC
;';
    $plot = hash_from_query($query);
    
    if (count($plot) > 0)
    {
      echo '
      <fieldset class="common">
        <legend>Activity</legend>
        <div id="highstock" style="height: 350px;"></div>
      </fieldset>';
    
      $json = array();
      foreach ($plot as $row)
      {
        list($year, $month, $day) = explode('-', $row['day']);
        if (!isset($json[0])) $json[0] = null;
        $json[0].= '['.mktime(0, 0, 0, $month, $day, $year).'000, '.$row['total'].'],';
      }
      
      // version displaying separated languages
      // $json = array();
      // foreach ($plot as $row)
      // {
        // list($year, $month, $day) = explode('-', $row['day']);
        // if (!isset($json[ $row['lang'] ])) $json[ $row['lang'] ] = null;
        // $json[ $row['lang'] ].= '['.mktime(0, 0, 0, $month, $day, $year).'000, '.$row['total'].'],';
      // }
      
      load_jquery('highstock', false);
      $page['script'].= '
      $(function() {
        window.chart = new Highcharts.StockChart({
          chart : {
            renderTo : "highstock",
          },

          rangeSelector : {
            buttons: [
              {type: "month", count: 1, text: "1 month"}, 
              {type: "month", count: 3, text: "3 months"}, 
              {type: "month", count: 6, text: "6 months"}, 
              {type: "year", count: 1, text: "1 year"}, 
              {type: "year", count: 2, text: "2 years"}, 
              {type: "all", text: "All"}
            ],
            buttonTheme: { width: 80},
            selected : 0,
          },
          
          scrollbar: {
            barBackgroundColor: "#999",
            barBorderRadius: 7,
            barBorderWidth: 0,
            rifleColor: "#333",
            buttonBackgroundColor: "#999",
            buttonBorderWidth: 0,
            buttonBorderRadius: 7,
            buttonArrowColor: "#333",
            trackBackgroundColor: "none",
            trackBorderWidth: 1,
            trackBorderRadius: 8,
            trackBorderColor: "#CCC",
          },
          
          navigator: {
            handles: {
              backgroundColor: "#999",
              borderColor: "#555",
            },
          },
          
          series : [';
          foreach ($json as $lang => $data)
          {
            $page['script'].= '
            {
              name : "'.$lang.'",
              data : ['.$data.'],
            },';
          }
          $page['script'].= '
          ],
        });
      });';
    
      /*$query = '
SELECT 
    lang,
    section, 
    LEFT(last_edit, 10) as date,
    COUNT(CONCAT(lang, section, LEFT(last_edit, 10))) as count
  FROM '.ROWS_TABLE.'
  WHERE 
    user_id = '.$local_user['id'].'
  GROUP BY CONCAT(lang, section, LEFT(last_edit, 10))
  ORDER BY last_edit DESC
  LIMIT 0,10
;';
      $recent = hash_from_query($query, null);
      
      echo '
      <fieldset class="common">
        <legend>Activity</legend>
        <table class="common">';
          foreach ($recent as $row)
          {
            echo '
            <tr>
              <td>'.format_date($row['date'],0,0).'</td>
              <td><b>'.$row['count'].'</b> string(s)</td>
              <td><i>'.get_section_name($row['section']).'</i></td>
              <td><i>'.get_language_name($row['lang']).'</i></td>
            </tr>';
          }
          if (!count($recent))
          {
            echo '
            <tr><td>No recent activity</td></tr>';
          }
        echo '
        </table>
      </fieldset>';*/
    }
    
    /* contact */
    if ( 
      !defined('PRIVATE_PROFILE') and 
      (
        is_admin() or 
        ( !is_guest() and $local_user['email_privacy'] != 'private' ) 
      )
    )
    {
      echo'
      <fieldset class="common">
        <legend>Send e-mail</legend>
          <table class="login">
          <tr>
            <td>Subject :</td>
            <td><input type="text" name="subject" style="width:500px;" value="'.@$_POST['subject'].'"></td>
          </tr>
          <tr>
            <td>Message :</td>
            <td><textarea name="message" style="width:500px;" rows="6" maxsize="70">'.@$_POST['message'].'</textarea></td>
          </tr>
          <tr>
            <td></td>
            <td>Pease note that by using this form, your e-mail address will be disclosed to the recipient.</td>
          </tr>
          <tr>
            <td><input type="hidden" name="key" value="'.get_ephemeral_key(2).'"><input type="hidden" name="user_id" value="'.$local_user['id'].'"></td>
            <td><input type="submit" name="send_message" value="Send" class="blue"></td>
          </tr>
        </table>
      </fieldset>';
      
      load_jquery('autoresize', false);
      $page['script'].= '
      $("textarea").autoResize({
        maxHeight:2000,
        extraSpace:11
      });';
    }
    
  echo '
  </form>';
}

load_jquery('tiptip');

$page['script'].= '
  $(".flag").parent("a").css("cursor", "help").tipTip({ 
    maxWidth:"600px",
    delay:200,
    defaultPosition:"top"
  });';

print_page();
?>