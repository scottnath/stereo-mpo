<?php
// Class to convert MPO files
 
class CMPO
{
  // variables with absolute paths of the external tools
  private $et_bin; // exiftool
  private $imk_convert; // imagemagick convert
  private $imk_composite; // imagemagick composite
  
  // constructor - initialize class
  function __construct($et_bin = 'exiftool', $imk_convert = 'convert', 
                       $imk_composite = 'composite')
  {
    $this->et_bin = $et_bin;
    $this->imk_convert = $imk_convert;
    $this->imk_composite = $imk_composite; 
  }
  
  // split MPO file to the left and the right part
  function split($in_mpo, $out_left, $out_right)
  {
    // get left image
    $ret = $this->exec($this->et_bin, array(
                      '-trailer:all=', $in_mpo, '-o', $out_left
                ));
    if ($ret != 0) return false;    
                
    // get right image
    $ret = $this->exec($this->et_bin, array(
                      $in_mpo, '-mpimage2 -b >', $out_right
                ));
    if ($ret != 0) return false;
    
    return true;        
  }
  
  // create anaglyph from the left and from the right image
  function anaglyph($in_left, $in_right, $out_image)
  {
    $ret = $this->exec($this->imk_composite, array(
                      '-stereo 0', $in_right, $in_left, $out_image
                ));
    return $ret == 0;          
  }
  
  // create stereo picture from left and right images
  function stereo($in_left, $in_right, $out_image)
  {
    $ret = $this->exec($this->imk_convert, array(
                      $in_left, $in_right, '+append', $out_image
                ));
    return $ret == 0;      
  }
    
  // helper function to execute external tools
  private function exec($app, $params)
  {
    $ret_code = 0;  
    exec($a = $app.' '.@implode(' ', $params), $result, $ret_code);
    return $ret_code;
  }
}

?>