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

foreach ($page['errors'] as $msg)
{
  echo '
  <div class="ui-state-error" style="padding: 0.7em;margin-bottom:10px;">
    <span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.7em;"></span>
    '.$msg.'
  </div>';
}

foreach ($page['warnings'] as $msg)
{
  echo '
  <div class="ui-state-warning" style="padding: 0.7em;margin-bottom:10px;">
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
    '.$msg.'
  </div>';
}

foreach ($page['infos'] as $msg)
{
  echo '
  <div class="ui-state-highlight" style="padding: 0.7em;margin-bottom:10px;">
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
    '.$msg.'
  </div>';
}
?>