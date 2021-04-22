<?php

class PDXSyncPicture{
    protected $data = false;
    
    public function __construct($data = false){
        $this->setData($data);
    }

    /**
     * @param array $data Image data
     * 
     * @return void
     */
    public function setData($data){
        $this->data = $data;
    }

    /**
     * @param int $width Image width
     * @param int $height Image height
     * @param bool $cover If true image is scaled so it covers width & height, else conain is used
     * @param bool $server_path If true server path is returned instead of url
     * 
     * @return string Returns url or path to image with requested dimensions
     */
    public function getResized($width, $height = 0, $cover = false, $server_path = false){
        return $this->getModified($width, $height, $cover, false, $server_path);
    }

    /**
     * @param int $width Image width
     * @param int $height Image height
     * @param bool $cover If true image is scaled so it covers width & height, else conain is used
     * 
     * @return array Returns array containing [width => .., height => ..] for active image
     */
    public function getResizedDimensions($width, $height, $cover = false){
        return $this->fitSize(
            $this->data['dimensions'][0]
            , $this->data['dimensions'][1]
            , $width, $height, $cover);
    }

    /**
     * @param int $width Image width
     * @param int $height Image height
     * @param bool $server_path If true server path is returned instead of url
     * 
     * @return string Returns url or path to image with requested dimensions
     */
    public function getCropped($width, $height = 0, $server_path = false){
        return $this->getModified($width, $height, false, true, $server_path);
    }

    /**
     * @param int $width Image width
     * @param int $height Image height
     * @param bool $cover If true image is scaled so it covers width & height, else conain is used
     * @param bool $crop If true image is cropped to passed dimensions
     * @param bool $server_path If true server path is returned instead of url
     * 
     * @return string Returns url or path to image with requested dimensions
     */
    protected function getModified($width, $height = 0, $cover = false, $crop = false, $server_path = false){
        $ret = '';
        $upload_dir = wp_upload_dir();
       
        $data = (array) $this->data;
        if(is_array($this->data)){
            return $this->data['url'];
        }
        // $data = json_decode(json_encode($this->data), true);
        $data = json_decode($this->data, true);
        

        //   echo('<pre>');
        //  print_r($this->data);
        //  echo('<br/>');
        //  print_r($data['file']);
        //  echo('<br/>');

        if($this->data){

            $thumb_dir = self::getImageDir($data['rel_path']);

            //  print_r($thumb_dir);
        //  echo('<br/>');

            $thumb_file = $width . 'x' . $height
                . '_' . ($cover ? 'cover' : 'contain')
                . '_' . ($crop ? 'c' : 'nc')
                . '.' . PdxSyncFileHandler::ext($data['rel_path'])
                ; 

                // print_r($thumb_file);
            // echo('<br/>');

            
            $thumb_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . dirname($data['rel_path'])
                            . DIRECTORY_SEPARATOR  . $thumb_dir . DIRECTORY_SEPARATOR
                            . $thumb_file;

            //                  print_r($thumb_path);
            // echo('<<< <br/>');
            
            if(!file_exists($thumb_path)){

                // generate thumbnail file
                $image = wp_get_image_editor( $data['rel_path'] );

                if ( ! is_wp_error( $image ) ) {

                    if($crop){
                        $new_size = [
                            'width' => $width
                            , 'height' => $height
                        ];
                    }
                    else{
                        $new_size = $getResizedDimensions($width, $height, $cover);
                    }

                    $image->resize( $new_size['width'], $new_size['height'], $crop );
                    $image->save( $thumb_path );
                }
                else{
                    if(file_exists($data['rel_path'])){
                        
                        // Iamge editor could not be loaded but the image exists.
                        // The server probably does not support resizing.
                        // Return path to full version of image as fallback
                        $thumb_path = $data['path'];
                        $thumb_file = basename($data['path']);
                        $thumb_dir = "";
                    }
                }
            }

            if($server_path){
                $ret = $thumb_path;
            }
            else{
                $ret = $upload_dir['baseurl'] . '/' . preg_replace('/[^\/]+$/', '', $data['rel_url'])
                            . ($thumb_dir ?  $thumb_dir . '/' : '')
                            . $thumb_file
                            ;
            }
        }



        // Custom dev by shapon
        // new
        $ret = $upload_dir['baseurl'] . '/' . preg_replace('/[^\/]+$/', '', $data['rel_url'])
                            . $data['file'];

     
        //  echo('.....................');
         //echo($ret);
        //  print_r($ret);
        // exit();

        return $ret;
    }

  /**
   * Fits passed original size inside new size.
   * If $fit_outside is true original size is fitted to cover whole new size.
   *
   * @param  int $original_width  Original width
   * @param  int $original_height Original height
   * @param  int $new_width       New width or false
   * @param  int $new_height      New height or false
   * @param bool $fit_outside     If true widths are fit outside instead of inside
   * @param bool $allow_upscale   If true upscaling of original dimensions are allowed
   *
   * @return array Returns array containing width and height
   */
   public function fitSize($original_width, $original_height, $new_width, $new_height, $fit_outside = false, $allow_upscale = false){
    // fit inside new width and height
    if($new_width && $new_height){
        // fit original dimensions inside new dimensions
        if(
            (!$fit_outside && $original_width / $original_height > $new_width / $new_height)
            || ($fit_outside && $original_width / $original_height < $new_width / $new_height)
            ){
                // original wider than new. use new width as constraint
                $new_height = false;
            }
            else{
                // original higher than new. use height as constraint
                $new_width = false;
            }
        }
        if(!$new_width){
            // scale both width and height if new height is larger than original height because images are not upscaled
            $scale = !$allow_upscale && $new_height > $original_height ? $original_height / $new_height : 1;
            // scale according to new_height
            $ret = [
                "width" => $original_width * ($new_height / $original_height) * $scale
                , "height" => $new_height * $scale
            ];
        }
        else if(!$new_height){
            // scale both width and height if new width is larger than original width because images are not upscaled
            $scale = !$allow_upscale && $new_width > $original_width ? $original_width / $new_width : 1;
            // scale according to new_width
            $ret = [
                "width" => $new_width * $scale
                , "height" => $original_height * ($new_width / $original_width) * $scale
            ];
        }
        
        $ret["width"] = round($ret["width"]);
        $ret["height"] = round($ret["height"]);
        
        return $ret;
    }

    /**
     * @param array $info File field data as array
     * 
     * @return bool Returns true on success or false on failure
     */
    static public function removeThumbnails($info){
        $ret = false;
        $thumb_dir = dirname($info['path']) . DIRECTORY_SEPARATOR . self::getImageDir($info['path'])
                        . DIRECTORY_SEPARATOR;
        if(is_dir($thumb_dir)){
            $files = glob($thumb_dir . '*');
            foreach($files as $file){
                if(is_file($file)){
                    unlink($file);
                }
            }
            $ret = rmdir($thumb_dir);
        }
        return $ret;
    }

    /**
     * @param string $image_path Path where main image is stored
     * 
     * @return string Returns dir where the images thumbnails are stored
     */
    static protected function getImageDir($image_path){
        $file = pathinfo($image_path, PATHINFO_BASENAME);
        return str_replace('.', '_', $file);
    }
}