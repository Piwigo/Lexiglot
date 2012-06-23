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

/**
 * This page is called by jQuery ro perform some AJAX admin actions
 */

define('PATH', '../');
define('IN_AJAX', 1);
include(PATH.'include/common.inc.php');


if (!isset($_POST['action']))
{
  close_ajax('error', 'Undefined action');
}

switch ($_POST['action'])
{
  // GET PROJECT EDIT FORM
  case 'get_project_form':
  {
    $query = '
SELECT *
  FROM '.PROJECTS_TABLE.'
  WHERE id = "'.$_POST['project_id'].'"
;';
    $row = mysql_fetch_assoc(mysql_query($query));
    
    $content = '
<table class="project_edit">
  <tr>
    <td class="title">Name :</td>
    <td><input type="text" name="projects['.$row['id'].'][name]" value="'.$row['name'].'" size="20"></td>
    <td class="files title">Files :</td>
  </tr>
  <tr>
    <td class="title">Directory :</td>
    <td><input type="text" name="projects['.$row['id'].'][directory]" value="'.$row['directory'].'" size="55"></td>
    <td rowspan="5" class="files"><textarea name="projects['.$row['id'].'][files]" style="width:470px;height:120px;">'.$row['files'].'</textarea></td>
  </tr>
  <tr>
    <td class="title">Rank :</td>
    <td><input type="text" name="projects['.$row['id'].'][rank]" value="'.$row['rank'].'" size="2"></td>
  </tr>
  <tr>
    <td class="title">Category :</td>
    <td><input type="text" name="projects['.$row['id'].'][category_id]" class="category" '.(!empty($row['category_id']) ? 'value=\'[{"id": '.$row['category_id'].'}]\'' : null).'></td>
  </tr>
  <tr>
    <td class="title">URL :</td>
    <td><input type="text" name="projects['.$row['id'].'][url]" value="'.$row['url'].'" size="55"></td>
  </tr>
  <tr>
    <td><input type="hidden" name="active_project" value="'.$row['id'].'"></td>
    <td><input type="submit" name="save_project" class="blue" value="Save"></td>
  </tr>
</table>';

    close_ajax('success', $content);
  }
  
  // GET LANGUAGE EDIT FORM
  case 'get_language_form':
  {
    $query = '
SELECT *
  FROM '.LANGUAGES_TABLE.'
  WHERE id = "'.$_POST['language_id'].'"
;';
    $row = mysql_fetch_assoc(mysql_query($query));
    
    $content = '
<table class="project_edit">
  <tr>
    <td class="title">Name :</td>
    <td><input type="text" name="languages['.$row['id'].'][name]" value="'.$row['name'].'" size="20"></td>
  </tr>
  <tr>
    <td class="title">Flag :</td>
    <td>
      <input type="file" name="flags-'.$row['id'].'" size="40">
      '.(!empty($row['flag']) ? '<a href="'.get_url_string(array('page'=>'languages','delete_flag'=>$row['id']), array(), 'admin').'" title="Delete the flag" style="margin-right:10px;"><img src="template/images/cross.png" alt="x"></a>' : null).'
    </td>
  </tr>
  <tr>
    <td class="title">Rank :</td>
    <td><input type="text" name="languages['.$row['id'].'][rank]" value="'.$row['rank'].'" size="2"></td>
  </tr>
  <tr>
    <td class="title">Category :</td>
    <td><input type="text" name="languages['.$row['id'].'][category_id]" class="category" '.(!empty($row['category_id']) ? 'value=\'[{"id": '.$row['category_id'].'}]\'' : null).'></td>
  </tr>
  <tr>
    <td class="title">Reference :</td>
    <td>
      <select name="languages['.$row['id'].'][ref_id]">
          <option value="" '.(null==$row['ref_id']?'selected="selected"':'').'>(default)</option>';
          foreach ($conf['all_languages'] as $lang)
          {
            if ($lang['id'] == $row['id']) continue;
            $content.= '
          <option value="'.$lang['id'].'" '.($lang['id']==$row['ref_id']?'selected="selected"':'').'>'.$lang['name'].'</option>';
          }
        $content.= '
      </select>
    </td>
  </tr>
  <tr>
    <td><input type="hidden" name="active_language" value="'.$row['id'].'"></td>
    <td><input type="submit" name="save_language" class="blue" value="Save"></td>
  </tr>
</table>';

    close_ajax('success', $content);
  }
  
  default:
    close_ajax('error', 'Bad parameters');
}


function close_ajax($errcode, $data=null)
{
  echo json_encode(array('errcode'=>$errcode, 'data'=>$data));
  close_page();
}

?>