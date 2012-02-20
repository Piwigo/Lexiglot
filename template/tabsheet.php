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

echo '
<div id="tabsheet">
  <ul>';
  foreach ($tabsheet['tabs'] as $url => $row)
  {
    // $row = array('tab name', 'tab link title', 'url reject')
    $row[1] = !empty($row[1]) ? str_replace('"', "'", $row[1]) : $row[0];
    $row[2] = !empty($row[2]) ? $row[2] : array(); 
    
    echo '<li class="'.($tabsheet['selected']==$url?'selected':null).'">
      <a href="'.get_url_string(array($tabsheet['param']=>$url), $row[2]).'" title="'.$row[1].'">'.$row[0].'</a>
    </li>';
  }
  echo '
  </ul>
</div>';

?>