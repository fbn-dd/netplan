<?php

function encodeLPW( $input, $secret)
{
  $retval = '';

  for ($i=0;$i<strlen($input);$i++)
  {
    $retval .= str_pad(dechex(ord($input[$i]) ^ ord($secret[$i%(strlen($secret))])), 2, 0, STR_PAD_LEFT);
  }

  return $retval;
}

function decodeLPW( $crypt, $secret)
{
  $retval = '';

  for ($i=0;$i<strlen($crypt)/2;$i++)
  {
    $retval .= chr(hexdec($crypt[2*$i].$crypt[2*$i+1]) ^ ord($secret[$i%(strlen($secret))]));
  }

  return $retval;
}

?>
