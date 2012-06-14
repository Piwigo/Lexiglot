<?php

// http://www.php.net/manual/en/function.quoted-printable-encode.php#106078
function quoted_printable_encode($str)
{
  $lp = 0;
  $ret = '';
  $hex = "0123456789ABCDEF";
  $length = strlen($str);
  $str_index = 0;
 
  while ($length--)
  {
    if ( ($c = $str[$str_index++]) == "\015" and $str[$str_index] == "\012" and $length > 0 )
    {
      $ret.= "\015";
      $ret.= $str[$str_index++];
      $length--;
      $lp = 0;
    } 
    else
    {
      if ( ctype_cntrl($c)
        or ord($c) == 0x7f
        or ord($c) & 0x80
        or $c == '='
        or ( $c == ' ' and $str[$str_index] == "\015" )
      )
      {
        if ( ($lp += 3) > 75)
        {
          $ret.= '=';
          $ret.= "\015";
          $ret.= "\012";
          $lp = 3;
        }
        $ret .= '=';
        $ret .= $hex[ord($c) >> 4];
        $ret .= $hex[ord($c) & 0xf];
      }
      else
      {
        if ( (++$lp) > 75)
        {
          $ret.= '=';
          $ret.= "\015";
          $ret.= "\012";
          $lp = 1;
        }
        $ret.= $c;
      }
    }
  }

  return $ret;
}

?>