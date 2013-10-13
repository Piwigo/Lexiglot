<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2013 Damien Sorel       http://www.strangeplanet.fr |
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


// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$search = array(
  'from_mail' => array('%', ''),
  'to_mail' =>   array('%', ''),
  'subject' =>   array('%', ''),
  'limit' =>     array('=', 50),
  );
  
$where_clauses = session_search($search, 'mail_search', array('limit'));


// +-----------------------------------------------------------------------+
// |                         PAGINATION
// +-----------------------------------------------------------------------+
$query = '
SELECT COUNT(1)
  FROM '.MAIL_HISTORY_TABLE.'
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
;';
list($total) = $db->query($query)->fetch_row();

$paging = compute_pagination($total, get_search_value('limit'), 'nav');


// +-----------------------------------------------------------------------+
// |                         GET ROWS
// +-----------------------------------------------------------------------+
$query = '
SELECT *
  FROM '.MAIL_HISTORY_TABLE.'
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  ORDER BY send_date DESC
  LIMIT '.$paging['Entries'].'
  OFFSET '.$paging['Start'].'
;';
$_MAILS = hash_from_query($query, null);


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
foreach ($_MAILS as $row)
{
  $template->append('mails', array(
    'FROM' => htmlspecialchars($row['from_mail']),
    'TO' => htmlspecialchars($row['to_mail']),
    'TIME' => strtotime($row['send_date']),
    'DATE' => format_date($row['send_date'], true, false),
    'SUBJECT' => $row['subject'],
    'HIGHLIGHT' => $row['from_mail']==$conf['system_email'] ? 'highlight' : null
    ));
}

$template->assign(array(
  'SEARCH' => search_to_template($search),
  'PAGINATION' => display_pagination($paging, 'nav'),
  'F_ACTION' => get_url_string(array('page'=>'mail'), true),
  ));


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('admin/mail');

?>