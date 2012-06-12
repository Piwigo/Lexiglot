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

/**
 * generates href and onclick params for a popup link
 * @return string
 */
function js_popup($url, $title=null, $height=600, $width=480, $top=0, $left=0)
{
  return 'href="'.$url.'" target="_blank"
    onclick="window.open(this.href, \''.$title.'\', \'height='.$height.', width='.$width.', top='.$top.', left='.$left.', toolbar=yes, menubar=yes, location=no, resizable=yes, scrollbars=yes, status=no\');return false;"';
}

/**
 * add a jquery plugin and its css
 * @param string plugin name
 * @param bool load css
 */
function load_jquery($name, $css=true)
{
  global $page;
  if ($css) 
  {
    $page['header'].= '
  <link type="text/css" rel="stylesheet" media="screen" href="template/js/jquery.'.$name.'.css">';
  }
  $page['header'].= '
  <script type="text/javascript" src="template/js/jquery.'.$name.'.min.js"></script>';
}

/**
 * cut a string and add an elipsis and a jQueryUI dialog for full string
 * @param string
 * @param int limit
 * @param bool add popup link
 * @return string
 */
function cut_string($string, $limit, $popup=true)
{
  global $page;
  
  if ( strlen(str_replace("\r\n", "\n", $string)) > $limit )
  {
    if ($popup)
    {
      $md5 = md5($string);
    
      $page['script'].= '
      $("#content-'.$md5.'").dialog({
        autoOpen: false, modal:true,
        width: 600, height: 600,
        hide:"clip", show:"clip",
        buttons: { "Close": function() { $( this ).dialog( "close" ); } }
      });
      
      $("#link-'.$md5.'").click(function() {
        $("#content-'.$md5.'").dialog( "open" );
        return false;
      });';

      $page['begin'].= '
      <div id="content-'.$md5.'" style="white-space:pre-wrap;display:none;">'.$string.'</div>';

      return substr($string, 0, $limit).'...<br><a id="link-'.$md5.'" href="#">Show full text</a>';
    }
    else
    {
      return substr($string, 0, $limit).'...';
    }
  }
  else
  {
    return $string;
  }
}

/**
 * count lines needed for a textarea (including line-breaks)
 * @param string
 * @param int chars per line
 * @return int >= 1
 */
function count_lines($string, $chars_per_line)
{
  if (empty($string)) return 1;
  
  $count = 0;
  $lines = explode("\n", $string);
  foreach ($lines as $line)
  {
    $lenght = strlen($line);
    $count+= ceil($lenght/$chars_per_line);
  }
  
  unset($lines);
  return $count > 1 ? $count : 1;
}

/**
 * generate needed parameters for pagination system
 * @param int total entries
 * @param int entries a page
 * @param string page get param name
 * @return array
 */
function compute_pagination($total, $entries, $param, $needed_pos=null)
{
  $paging['TotalEntries'] = $total;
  $paging['Entries'] = $entries;
  
  $paging['TotalPages'] = ceil($paging['TotalEntries']/$paging['Entries']);
  $paging['Page'] = 
    isset($_GET[$param]) 
      ? (
        intval($_GET[$param]) < $paging['TotalPages']
          ? max(intval($_GET[$param]), 1)
          : max($paging['TotalPages'], 1)
      )
      : (
        !empty($needed_pos)
          ? (
            $needed_pos%$paging['Entries'] != 0
              ? floor($needed_pos/$paging['Entries']) + 1
              : floor($needed_pos/$paging['Entries'])
          )
          : 1
      )
    ;
  
  $paging['Start'] = ($paging['Page']-1) * $paging['Entries'];
  return $paging;
}

/**
 * write paging system
 * @param array paging from compute_pagination function
 * @return string
 */
function display_pagination($paging, $param='page')
{
  if ($paging['TotalPages'] <= 1) return null;
  $content = null;
  
  if ($paging['Page'] == 1)
  {
    $content.= '<span class="page disabled">&laquo;</span>';
  }
  else
  {
    $content.= '<a class="page" href="'.get_url_string(array($param=>$paging['Page']-1)).'">&laquo;</a>';
  }
  
  if ($paging['TotalPages'] <= 9) // less than 10 page
  {
    for ($i=1; $i<=$paging['TotalPages']; $i++)
      $content .= paging_link($i, $paging['Page'], $param);
      
  } 
  else // more than 10 pages
  {
    if ($paging['Page'] <= 5) // 5 first elements : [1 2 3 4 5 6 ... n-1 n]
    {
      for ($i=1; $i<=6; $i++)
        $content .= paging_link($i, $paging['Page'], $param);
      $content .= '<span class="dot">...</span>';
      for ($i=$paging['TotalPages']-1; $i<=$paging['TotalPages']; $i++)
        $content .= paging_link($i, $paging['Page'], $param);
    }
    else if ($paging['Page'] >= $paging['TotalPages']-4) // 5 lasts elements : [1 2 ... n-5 n-4 n-3 n-2 n-1 n]
    {
      for ($i=1; $i<=2; $i++)
        $content .= paging_link($i, $paging['Page'], $param);
      $content .= '<span class="dot">...</span>';
      for ($i=$paging['TotalPages']-5; $i<=$paging['TotalPages']; $i++)
        $content .= paging_link($i, $paging['Page'], $param);
    }
    else // common case : [1 2 ... x-1 x x+1 ... n-1 n]
    {
      for ($i=1; $i<=2; $i++)
        $content .= paging_link($i, $paging['Page'], $param);
      $content .= '<span class="dot">...</span>';
      for ($i=$paging['Page']-1; $i<=$paging['Page']+1; $i++)
        $content .= paging_link($i, $paging['Page'], $param);
      $content .= '<span class="dot">...</span>';
      for ($i=$paging['TotalPages']-1; $i<=$paging['TotalPages']; $i++)
        $content .= paging_link($i, $paging['Page'], $param);
    }
  }
  
  if ($paging['Page'] == $paging['TotalPages'])
  {
    $content.= '<span class="page disabled">&raquo;</span>';
  }
  else
  {
    $content.= '<a class="page" href="'.get_url_string(array($param=>$paging['Page']+1)).'">&raquo;</a>';
  }
  
  return $content;
}    

function paging_link($i, $page, $param='page')
{
  return '<a class="page '.($i == $page ? 'active' : null).'" href="'.get_url_string(array($param=>$i)).'">'.$i.'</a>'; 
}

/**
 * simplify a string to insert it into an URL
 * @param string
 */
function str2url($str)
{
  $raw = $str;

  $str = remove_accents($str);
  $str = preg_replace('/[^a-z0-9_\s\'\:\/\[\],-]/','',strtolower($str));
  $str = preg_replace('/[\s\'\:\/\[\],-]+/',' ',trim($str));
  $res = str_replace(' ','_',$str);

  if (empty($res))
  {
    $res = str_replace(' ','_', $raw);
  }

  return $res;
}

/**
 * encode a string in utf8 if not encoded yet
 */
function proper_utf8($string)
{
  if (!seems_utf8($string))
  {
    return utf8_encode($string);
  }
  return htmlspecialchars($string);
}

/**
 * custom version of htmlentities that leaves html tags intact
 * http://stackoverflow.com/questions/4776035/convert-accents-to-html-but-ignore-tags/4776054#4776054
 * @param string
 */
function html_special_chars($string)
{
  return htmlspecialchars_decode(htmlentities($string, ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES);
}

/**
 * sends an email with PHP mail() function, with Lexiglot information
 * @param:
 *   - to (list separated by comma).
 *   - subject
 *   - content
 *   - args: function params of mail function:
 *       o from [default 'noreply@domain']
 *       o cc [default empty]
 *       o bcc [default empty]
 *       o notification [default empty]
 *       o content_format [default value 'text/plain']
 *   - save in database
 * @return boolean
 */
function send_mail($to, $subject, $content, $args = array(), $additional_infos=null)
{
  global $conf;
  
  // check inputs
  if (empty($to) and empty($args['cc']) and empty($args['bcc']))
  {
    return false;
  }

  if (empty($subject) or empty($content))
  {
    return false;
  }

  if (empty($args['from']))
  {
    $args['from'] = $conf['system_email'];
  }

  if (empty($args['content_format']))
  {
    $args['content_format'] = 'text/plain';
  }

  // format subject
  $subject = trim(preg_replace('#[\n\r]+#s', null, $subject));
  $subject = '=?UTF-8?B?'.base64_encode($subject).'?='; // deal with utf-8

  // headers
  $headers = 'From: '.$args['from']."\n";
  $headers.= 'Reply-To: '.$args['from']."\n";

  if (!empty($args['cc']))
  {
    $headers.= 'Cc: '.implode(',', $args['cc'])."\n";
  }

  if (!empty($args['bcc']))
  {
    $headers.= 'Bcc: '.implode(',', $args['bcc'])."\n";
  }

  if (!empty($args['notification']))
  {
    $headers.= 'Disposition-Notification-To: '.$args['notification']."\n";
    $headers.= 'Return-Receipt-To: '.$args['notification']."\n";
  }

  $headers.= 'X-Mailer: Lexiglot'."\n";
  $headers.= 'MIME-Version: 1.0'."\n";
  $headers.= 'Content-Type: '.$args['content_format'].'; charset="utf-8"'."\n";

  // content
  if ($args['content_format'] == 'text/plain')
  {
    $content = htmlspecialchars($content);
  }
  
  $content = wordwrap($content, 70, "\n");
  
  // send mail
  $result = mail($to, $subject, $content, $headers);
  
  if ( $result and !empty($additional_infos) )
  {
    $query = '
INSERT INTO '.MAIL_HISTORY_TABLE.' (
    send_date,
    from_mail,
    to_mail,
    subject
  )
  VALUES (
    NOW(),
    "'.$args['from'].'",
    "'.$to.'",
    "'.mres($additional_infos).'"
  )
;';
    mysql_query($query);
  }
  
  return $result;
}

function format_email($mail, $name=null)
{
  if ($name == null)
    return $mail;
  else
    return $name.' <'.$mail.'>';
}

/**
 * Returns true if the string appears to be encoded in UTF-8. (from wordpress)
 * @param string Str
 */
function seems_utf8($Str) { # by bmorel at ssi dot fr
  for ($i=0; $i<strlen($Str); $i++) {
    if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
    elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
    elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
    elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
    elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
    elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
    else return false; # Does not match any model
    for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
      if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
      return false;
    }
  }
  return true;
}

/**
 * Remove accents from a UTF-8 or ISO-859-1 string (from wordpress)
 * @param string - an UTF-8 or ISO-8859-1 string
 */
function remove_accents($string)
{
  if ( !preg_match('/[\x80-\xff]/', $string) )
    return $string;

  if (seems_utf8($string)) {
    $chars = array(
    // Decompositions for Latin-1 Supplement
    "\xc3\x80"=>'A', "\xc3\x81"=>'A', "\xc3\x82"=>'A', "\xc3\x83"=>'A',
    "\xc3\x84"=>'A', "\xc3\x85"=>'A', "\xc3\x87"=>'C', "\xc3\x88"=>'E',
    "\xc3\x89"=>'E', "\xc3\x8a"=>'E', "\xc3\x8b"=>'E', "\xc3\x8c"=>'I',
    "\xc3\x8d"=>'I', "\xc3\x8e"=>'I', "\xc3\x8f"=>'I', "\xc3\x91"=>'N',
    "\xc3\x92"=>'O', "\xc3\x93"=>'O',  "\xc3\x94"=>'O', "\xc3\x95"=>'O',
    "\xc3\x96"=>'O', "\xc3\x99"=>'U', "\xc3\x9a"=>'U', "\xc3\x9b"=>'U',
    "\xc3\x9c"=>'U', "\xc3\x9d"=>'Y', "\xc3\x9f"=>'s', "\xc3\xa0"=>'a',
    "\xc3\xa1"=>'a', "\xc3\xa2"=>'a', "\xc3\xa3"=>'a', "\xc3\xa4"=>'a',
    "\xc3\xa5"=>'a', "\xc3\xa7"=>'c', "\xc3\xa8"=>'e', "\xc3\xa9"=>'e',
    "\xc3\xaa"=>'e', "\xc3\xab"=>'e', "\xc3\xac"=>'i', "\xc3\xad"=>'i',
    "\xc3\xae"=>'i', "\xc3\xaf"=>'i', "\xc3\xb1"=>'n', "\xc3\xb2"=>'o',
    "\xc3\xb3"=>'o', "\xc3\xb4"=>'o', "\xc3\xb5"=>'o', "\xc3\xb6"=>'o',
    "\xc3\xb9"=>'u', "\xc3\xba"=>'u', "\xc3\xbb"=>'u', "\xc3\xbc"=>'u',
    "\xc3\xbd"=>'y', "\xc3\xbf"=>'y',
    // Decompositions for Latin Extended-A
    "\xc4\x80"=>'A', "\xc4\x81"=>'a', "\xc4\x82"=>'A', "\xc4\x83"=>'a',
    "\xc4\x84"=>'A', "\xc4\x85"=>'a', "\xc4\x86"=>'C', "\xc4\x87"=>'c',
    "\xc4\x88"=>'C', "\xc4\x89"=>'c', "\xc4\x8a"=>'C', "\xc4\x8b"=>'c',
    "\xc4\x8c"=>'C', "\xc4\x8d"=>'c', "\xc4\x8e"=>'D', "\xc4\x8f"=>'d',
    "\xc4\x90"=>'D', "\xc4\x91"=>'d', "\xc4\x92"=>'E', "\xc4\x93"=>'e',
    "\xc4\x94"=>'E', "\xc4\x95"=>'e', "\xc4\x96"=>'E', "\xc4\x97"=>'e',
    "\xc4\x98"=>'E', "\xc4\x99"=>'e', "\xc4\x9a"=>'E', "\xc4\x9b"=>'e',
    "\xc4\x9c"=>'G', "\xc4\x9d"=>'g', "\xc4\x9e"=>'G', "\xc4\x9f"=>'g',
    "\xc4\xa0"=>'G', "\xc4\xa1"=>'g', "\xc4\xa2"=>'G', "\xc4\xa3"=>'g',
    "\xc4\xa4"=>'H', "\xc4\xa5"=>'h', "\xc4\xa6"=>'H', "\xc4\xa7"=>'h',
    "\xc4\xa8"=>'I', "\xc4\xa9"=>'i', "\xc4\xaa"=>'I', "\xc4\xab"=>'i',
    "\xc4\xac"=>'I', "\xc4\xad"=>'i', "\xc4\xae"=>'I', "\xc4\xaf"=>'i',
    "\xc4\xb0"=>'I', "\xc4\xb1"=>'i', "\xc4\xb2"=>'IJ', "\xc4\xb3"=>'ij',
    "\xc4\xb4"=>'J', "\xc4\xb5"=>'j', "\xc4\xb6"=>'K', "\xc4\xb7"=>'k',
    "\xc4\xb8"=>'k', "\xc4\xb9"=>'L', "\xc4\xba"=>'l', "\xc4\xbb"=>'L',
    "\xc4\xbc"=>'l', "\xc4\xbd"=>'L', "\xc4\xbe"=>'l', "\xc4\xbf"=>'L',
    "\xc5\x80"=>'l', "\xc5\x81"=>'L', "\xc5\x82"=>'l', "\xc5\x83"=>'N',
    "\xc5\x84"=>'n', "\xc5\x85"=>'N', "\xc5\x86"=>'n', "\xc5\x87"=>'N',
    "\xc5\x88"=>'n', "\xc5\x89"=>'N', "\xc5\x8a"=>'n', "\xc5\x8b"=>'N',
    "\xc5\x8c"=>'O', "\xc5\x8d"=>'o', "\xc5\x8e"=>'O', "\xc5\x8f"=>'o',
    "\xc5\x90"=>'O', "\xc5\x91"=>'o', "\xc5\x92"=>'OE', "\xc5\x93"=>'oe',
    "\xc5\x94"=>'R', "\xc5\x95"=>'r', "\xc5\x96"=>'R', "\xc5\x97"=>'r',
    "\xc5\x98"=>'R', "\xc5\x99"=>'r', "\xc5\x9a"=>'S', "\xc5\x9b"=>'s',
    "\xc5\x9c"=>'S', "\xc5\x9d"=>'s', "\xc5\x9e"=>'S', "\xc5\x9f"=>'s',
    "\xc5\xa0"=>'S', "\xc5\xa1"=>'s', "\xc5\xa2"=>'T', "\xc5\xa3"=>'t',
    "\xc5\xa4"=>'T', "\xc5\xa5"=>'t', "\xc5\xa6"=>'T', "\xc5\xa7"=>'t',
    "\xc5\xa8"=>'U', "\xc5\xa9"=>'u', "\xc5\xaa"=>'U', "\xc5\xab"=>'u',
    "\xc5\xac"=>'U', "\xc5\xad"=>'u', "\xc5\xae"=>'U', "\xc5\xaf"=>'u',
    "\xc5\xb0"=>'U', "\xc5\xb1"=>'u', "\xc5\xb2"=>'U', "\xc5\xb3"=>'u',
    "\xc5\xb4"=>'W', "\xc5\xb5"=>'w', "\xc5\xb6"=>'Y', "\xc5\xb7"=>'y',
    "\xc5\xb8"=>'Y', "\xc5\xb9"=>'Z', "\xc5\xba"=>'z', "\xc5\xbb"=>'Z',
    "\xc5\xbc"=>'z', "\xc5\xbd"=>'Z', "\xc5\xbe"=>'z', "\xc5\xbf"=>'s',
    // Euro Sign         // GBP (Pound) Sign
    "\xe2\x82\xac"=>'E', "\xc2\xa3"=>'');

    $string = strtr($string, $chars);
  } else {
    // Assume ISO-8859-1 if not UTF-8
    $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
      .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
      .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
      .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
      .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
      .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
      .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
      .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
      .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
      .chr(252).chr(253).chr(255);

    $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

    $string = strtr($string, $chars['in'], $chars['out']);
    $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
    $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
    $string = str_replace($double_chars['in'], $double_chars['out'], $string);
  }

  return $string;
}

?>