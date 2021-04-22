<?php

require_once ABSPATH . 'wp-admin/includes/file.php';

class PdxSyncFileHandler{

    /**
     * @param string|array $field_data File field data as json string or array
     * 
     * @return array Returns array contianing basic info about the file
     */
    static public function getFileInfo($field_data){
        $ret = array();
        if($field_data){
            $data = is_array($field_data) ? $field_data : json_decode($field_data, true);

            $upload_dir = wp_upload_dir();

            $ret = array(
                'path' => $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $data['rel_path'] . $data['file']
                , 'url' => $upload_dir['baseurl'] . '/' . $data['rel_url'] . $data['file']
                , 'rel_path' => $data['rel_path'] . $data['file']
                , 'rel_url' => $data['rel_url'] . $data['file']
                , 'extra_info' => isset($data['extra_info']) ? $data['extra_info'] : array()
                , 'dimensions' => $data['dimensions']
            );
        }
        return $ret;
    }

    /**
     * @param string $field_data
     * 
     * @return array Returns array containing info about files
     */
    static public function getMultipleFileInfo($field_data){
        $ret = array();
        if($field_data){
            $data = json_decode($field_data, true);
            if($data){
                foreach($data as $item){
                    $ret[] = self::getFileInfo($item);
                }
            }
        }
        return $ret;
    }

    /**
     * @param string $url File url
     * @param string $ext File extension
     * @param string $file_hash_info Additional info to include in the hash
     * 
     * @return string Returns file name generated from passed info
     */
    static protected function hashFileName($url, $ext, $file_hash_info = ''){
        return md5($url . "-" . $file_hash_info) . "." . $ext;
    }

    /**
     * @param string $url Url where file can be downloaded
     * @param string $old_file_data Previous version of file stored in same field
     * @param string $file_hash_info Info that will be included in the files hash
     *                               used to detect when the file has changed
     * 
     * @return string Returns info about file that should be stored
     */
    static public function saveFile($url, $old_file_data = false, $file_hash_info = ''){
        $ret = $old_file_data ? $old_file_data : '';

        $remove = false;

        $format_info = self::getFormatInfo($url);

        // check if file is supported format
        if($format_info){

            // generate filename based on file url and optional hash info
            $file_name = self::hashFileName($url, $format_info["ext"], $file_hash_info);
            
            $do_save = true;

            if($old_file_data){
                $old_info = json_decode($old_file_data, true);

                // file name has not changed so the file is same as before
                if($file_name == $old_info['original_name']){
                    $do_save = false;
                }
            }

            if($do_save){

                $path_info = self::downloadFile($url, $file_name, $format_info['mime']);

                if($path_info){
                    // store file info
                    $path_info['original_name'] = $file_name;
                    $ret = json_encode($path_info);

                    // allow removing of old file
                    $remove = true;
                }
            }
        }
        else{
            // remove old file
            $remove = true;
        }

        // remove old file
        if($remove && $old_file_data){
            $old_info = json_decode($old_file_data, true);
            $upload_dir = wp_upload_dir(); 
            $old_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $old_info['rel_path']
                             . $old_info['file'];

            if(file_exists($old_path)){
                unlink($old_path);
            }
        }

        return $ret;
    }

    /**
     * @param array $files Array containing files info
     * @param string $old_file_data Previous files field data if available
     * 
     * @return string Returns files field data
     */
    static public function saveMultipleFiles($files, $old_file_data = false){
        $ret = $old_file_data ? $old_file_data : '';

        $remove_arr = array();

        // get names of all existing files
        $existing_map = array();
        if($old_file_data){
            $old_arr = json_decode($old_file_data, true);
            if(is_array($old_arr)){
                foreach($old_arr as $file){
                    $existing_map[$file['original_name']] = $file;
                }
            }
        }

        $new_data = array();
        if(is_array($files)){
            foreach($files as $file){
                // if <PictureN> element has attributes $file is array, else string
                // make sure that $file variable always uses same format
                if(!is_array($file)){
                    $file = [$file];
                }
                $url = $file[0];
                $format_info = self::getFormatInfo($url);

                if($format_info){
                    $file_hash_info = isset($file['timeStamp']) ? $file['timeStamp'] : '';
                    $hash_name = self::hashFileName($url, $format_info["ext"], $file_hash_info);

                    if(isset($existing_map[$hash_name])){
                        // file with same hash exists from earlier. nothing heeds to be done
                        $new_data[$hash_name] = $existing_map[$hash_name];
                    }
                    else{
                        // file does not exist from before. Save it.
                        $info = self::saveFile($url, false, $file_hash_info);

                        if($info){
                            // the info gets encoded before its returned so it should be
                            // decoded here
                            $info = json_decode($info, true);

                            // store extra info with the file info
                            $extra_info = [];
                            unset($file[0]);
                            if(isset($file['timeStamp'])){
                                unset($file['timeStamp']);
                            }
                            if($file){
                                $extra_info = $file;
                            }

                            $info['extra_info'] = $extra_info;

                            // new file was saved successfully
                            $new_data[$hash_name] = $info;
                        }
                    }

                }
            }
        }

        // check for files that existed in old data but not in new data
        $upload_dir = wp_upload_dir(); 
        $upload_base_dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR;
        foreach($existing_map as $hash_name => $info){
            if(!isset($new_data[$hash_name])){

                // file does not exist in new data. remove it
                $old_path = $upload_base_dir . $info['rel_path'] . $info['file'];

                PDXSyncPicture::removeThumbnails(self::getFileInfo($info));

                if(file_exists($old_path)){
                    unlink($old_path);
                }
            }
        }

        // return new data as json string
        $ret = json_encode(array_values($new_data));

        return $ret;
    }

    /**
     * @param string|array $field_data Field data as json string or array
     * 
     * @return bool Returns true on success or false on failure
     */
    static public function removeFile($field_data){
        $ret = false;
        $info = self::getFileInfo($field_data);
        if($info){
            if(file_exists($info['path'])){

                PDXSyncPicture::removeThumbnails($info);

                $ret = unlink($info['path']);
            }
            else{
                // file does not exist. just return true
                $ret = true;
            }
        }
        return $ret;
    }

    /**
     * @param string $field_data Field data (json string)
     * 
     * @return bool Returns true on success or false on failure
     */
    static public function removeMultipleFiles($field_data){
        $ret = false;

        $info = json_decode($field_data, true);
        if($info){
            foreach($info as $file){
                self::removeFile($file);
            }
            $ret = true;
        }

        return $ret;
    }

    /**
     * @param striong $file_name
     * 
     * @return string Returns file extension as lower case
     */
    static public function ext($file_name){
        return strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    }
    
    /**
     * NOTE: Does NOT do any file validation
     * 
     * @param string $url Url where file is downloaded from
     * @param string $file_name Name that file should be stored with
     * @param string $mime Mime type of the file
     * 
     * @return array|bool Returns array containing paths to saved file or false
     */
    static protected function downloadFile($url, $file_name, $mime){
        $ret = false;
        $timeout_seconds = 10; 
        // $ret = [
        //         'file' => ''
        //         , 'rel_path' => ''
        //         , 'rel_url' => '/'
        //         , 'dimensions' => ''
        //     ];
        // return $ret;
        

        // Download file to temp dir
        $temp_file = download_url( $url, $timeout_seconds );
        //print_r(wp_generate_password());
        //print_r($temp_file);
		//exit();

        $format_info = self::getFormatInfo($url);
        
        if ( !is_wp_error( $temp_file )) {

            // Array based on $_FILE as seen in PHP file uploads
            $file = array(
                'name'     => $file_name
                , 'type'     => $mime
                , 'tmp_name' => $temp_file
                , 'error'    => 0
                , 'size'     => filesize($temp_file)
            );

            $overrides = array(
                // Tells WordPress to not look for the POST form
                // fields that would normally be present as
                // we downloaded the file from a remote server, so there
                // will be no form fields
                // Default is true
                'test_form' => false,

                // Setting this to false lets WordPress allow empty files, not recommended
                // Default is true
                'test_size' => true,
            );

            // Move the temporary file into the uploads directory
            $results = wp_handle_sideload( $file, $overrides );

            if ( !empty( $results['error'] ) ) {
                PDXSync::addError($results['error']);
            } else {
                
                // new file saved successfully
                $file_path  = $results['file'];
                $upload_dir = wp_upload_dir(); 
                $ret = [
                    'file' => basename($results['file'])
                    , 'rel_path' => dirname(substr($file_path, strlen($upload_dir['basedir']) + 1))
                                         . DIRECTORY_SEPARATOR
                    , 'rel_url' => substr($upload_dir['url'], strlen($upload_dir['baseurl']) + 1) . '/'
                    , 'dimensions' => self::getImageDimensions($file_path)
                ];

            }
        }
        return $ret;
    }

    /**
     * @param string $file_path Absolute server path to file
     * 
     * @return array|bool Returns array containing [width, height] or false if dimensions could not be determined
     */
    static protected function getImageDimensions($file_path){
        $ret = false;

        $image = wp_get_image_editor( $file_path );

        if ( ! is_wp_error( $image ) ) {

            $size = $image->get_size();
            if($size){
                $ret = [$size['width'], $size['height']];
            }
        }

        return $ret;
    }

    /**
     * @param string $file_name File name or path
     * 
     * @return array|bool Returns array containing file type info or false if unsupported file type
     */
    static protected function getFormatInfo($file_name){
        $ret = false;
        $ext = self::ext($file_name);

        switch($ext){
            case 'jpg':
            case 'jpeg':
                $ret = ['ext' => $ext, 'mime' => 'image/jpeg'];
            break;
            case 'png':
                $ret = ['ext' => $ext, 'mime' => 'image/png'];
            break;
            case 'gif':
                $ret = ['ext' => $ext, 'mime' => 'image/gif'];
            break;
            case 'pdf':
                $ret = ['ext' => $ext, 'mime' => 'application/pdf'];
            break;
        }

        return $ret;
    }
}