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

class Tabsheet
{
  private $id;
  private $sheets;
  private $param_name;
  
  function __construct($id, $param)
  {
    $this->id = $id;
    $this->param_name = $param;
  }
  
  function add($id, $name, $title=null, $url_reject=array())
  {
    if (!empty($id))
    {
      $this->sheets[$id] = array(
        'NAME' => $name,
        'TITLE' => $title,
        'URL' => get_url_string(array($this->param_name=>$id), $url_reject),
        'SELECTED' => false,
        );
    }
  }
  
  function select($id)
  {
    $this->sheets[$id]['SELECTED'] = true;
  }
  
  function render($return=false)
  {
    global $template;
    $template->set_filename('tabsheet', 'tabsheet.tpl');
    $template->assign('tabsheet', $this->sheets);
    
    if ($return)
    {
      $template->assign_var_from_handle('TABSHEET_'.$this->id, 'tabsheet');
    }
    else
    {
      $template->parse('tabsheet');
    }
  }
}

?>