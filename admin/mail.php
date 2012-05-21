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

// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$where_clauses = array('1=1');
$search = array(
  'from_mail' => array('%', null),
  'to_mail' =>   array('%', null),
  'subject' =>   array('%', null),
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
list($total) = mysql_fetch_row(mysql_query($query));

$paging = compute_pagination($total, $search['limit'][1], 'nav');

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
// search rows
echo '
<form action="admin.php?page=mail" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>From</th>
      <th>To</th>
      <th>Subject</th>
      <th>Limit</th>
      <th></th>
    </tr>
    <tr>
      <td>
        <input type="text" size="20" name="from" value="'.$search['from_mail'][1].'">
      </td>
      <td>
        <input type="text" size="20" name="to" value="'.$search['to_mail'][1].'">
      </td>
      <td>
        <input type="text" size="20" name="subject" value="'.$search['subject'][1].'">
      </td>
      <td>
        <input type="text" size="3" name="limit" value="'.$search['limit'][1].'">
      </td>
      <td>
        <input type="submit" name="search" class="blue" value="Search">
        <input type="submit" name="erase_search" class="red tiny" value="Erase">
      </td>
    </tr>
  </table>
</fieldset>
</form>';

// rows list
echo '
<form action="admin.php?page=mail" method="post" id="mail_history">
<fieldset class="common">
  <legend>History</legend>
  
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="from">From</th>
        <th class="to">To</th>
        <th class="date">Date</th>
        <th class="subject">Subject</th>
      </tr>
    </thead>
    <tbody>';
    foreach ($_MAILS as $row)
    {
      echo '
      <tr class="'.($row['from_mail']==$conf['system_email']?'highlight':null).'">
        <td class="from">
          '.htmlspecialchars($row['from_mail']).'
        </td>
        <td class="to">
          '.htmlspecialchars($row['to_mail']).'
        </td>
        <td class="date">
          <span style="display:none;">'.strtotime($row['send_date']).'</span>
          '.format_date($row['send_date'], true, false).'
        </td>
        <td class="subject">
          '.$row['subject'].'
        </td>
      </tr>';
    }
    if (count($_MAILS) == 0)
    {
      echo '
      <tr>
        <td colspan="4"><i>No results</i></td>
      </tr>';
    }
    echo '
    </tbody>
  </table>
  
  <div class="pagination">'.display_pagination($paging, 'nav').'</div>
</fieldset>
</form>

<table class="legend">
  <tr>
    <td><span>&nbsp;</span>User mails</td>
    <td><span class="highlight">&nbsp;</span> System mails</td>
  </tr>
</table>';


// +-----------------------------------------------------------------------+
// |                        SCRIPTS
// +-----------------------------------------------------------------------+
load_jquery('tablesorter');

$page['script'].= '
$("#mail_history table").tablesorter({
  sortList: [[2,1]],
  widgets: ["zebra"]
});';

?>