<?php

class PdxSyncPdxHandlerXML{

    /**
     * Downloads PDX file from remote server and updates data in local DB
     * 
     * @param string $pdx_xml If passed the xml is used instead of downloading xml from pdx enpoint
     * 
     * @return bool Returns true on success or false on failure
     */
    static public function doSync($pdx_xml = ""){
        // print_r('..............');
        // echo '<pre>';
        

        $pdx_url = get_option(PDXSync::OPTION_PDX_URL);
        $pdx_url = 'https://intra.pdx.fi/bulevardinkotimeklarit/save/out/kotisivut.xml';
        // $pdx_url = 'https://kivi.etuovi.com/ext-api/v1/realties/homepage';
        

        if($pdx_xml){
            $pdx_xml = simplexml_load_string($pdx_xml);
            $pdx_content = self::xmlToArray($pdx_xml);
        }
        else{
            $pdx_content = self::downloadPdx($pdx_url);
        }

        // echo json_decode($pdx_content);
        // $out = array_values($pdx_content);
        //echo json_encode($pdx_content);
        // echo '<pre>';
        // print_r($pdx_content);
        //exit();

        if($pdx_content && is_array($pdx_content) && count($pdx_content) > 0){
            
            // get list of currently locally stored items
            $old = self::getApartments();

            // map by key
            $old_mapped = [];
            if($old){
                foreach($old as $apartment){
                    if(isset($old_mapped[$apartment->get('Key')])){
                        
                        // there are local duplicates. remove them
                        $apartment->delete();
                    }
                    else{
                        $old_mapped[$apartment->get('Key')] = $apartment;
                    }
                }
            }

            // get list of items in pdx file
            $new_mapped = [];
            foreach($pdx_content as $data){

                $apartment = new PdxSyncPdxItem();
                $apartment->setImportData($data);
                $new_mapped[$apartment->get('Key')] = $apartment;
            }

            // remove all items that exists locally but not in pdx file
            foreach($old_mapped as $key => $apartment){
                if(!isset($new_mapped[$key])){
                    // apartment does not exist in pdx anymore
                    $apartment->delete();
                }
            }

            // add or update all items that exist in the pdx file
            foreach($new_mapped as $key => $apartment){
                if(isset($old_mapped[$key])){

                    // update
                    $old_mapped[$key]->update($apartment->getData());
                }
                else{
                    
                    // add
                    $apartment->save();
                }
            }
        }
    }

    /**
     * @param int $limit Max number of items to return
     * @param int $offset Number of items to skip from start
     * @param array $types Array containing apartment types to include: PdxSyncPdxItem::TYPE_[ASSIGNMENT|RENT]
     * 
     * @return array Returns array containing apartment objects
     */
    static public function getApartments($limit = 0, $offset = 0, $types = []){
        
        global $wpdb;
        $ret = [];
        $table = PdxSyncDbStructure::getTableName(PdxSyncPdxItem::TABLE);

        $args = [];
        $where = '';
        if($types){
            $in = [];
            foreach($types as $type){
                $in[] = '%s';
                $args[] = $type;
            }
            $where = ' WHERE pdx_object IN (' . implode(',', $in) . ')';
        }
        $sql = 'SELECT * FROM ' . $table . $where . ' ORDER BY added DESC';

        if($limit){
            $sql.= ' LIMIT %d, %d';
            $args[] = $offset;
            $args[] = $limit;
        }

        if($args){
            $sql = $wpdb->prepare($sql, $args);
        }
        $data = $wpdb->get_results($sql, ARRAY_A);
        
        if($data){
            foreach($data as $item_data){
                $item = new PdxSyncPdxItem();
                $item->setDbData($item_data);
                $ret[] = $item;
            }
        }

        return $ret;
    }

    /**
     * @param string $apartment_id ID of apartment
     * 
     * @return object|bool Returns apartment object if apartment found or false if not
     */
    static public function getApartment($apartment_id){
        global $wpdb;
        $ret = false;
        $table = PdxSyncDbStructure::getTableName(PdxSyncPdxItem::TABLE); 

        $sql = 'SELECT * FROM ' . $table . ' WHERE id = %d ORDER BY added DESC';
        $args = array(intval($apartment_id));
        $sql = $wpdb->prepare($sql, $args);

        $data = $wpdb->get_results($sql, ARRAY_A);
        
        if($data){
            $ret = new PdxSyncPdxItem();
            $ret->setDbData($data[0]);
        }
        return $ret;
    }

    /**
     * Downloads pdx file and converts it to an array.
     * 
     * @param string $url URL to PDX file
     * 
     * @return array|bool Returns pdx file content as array or false if it could not be downloaded
     */
    protected static function downloadPdx($url){
        $ret = false;
        $xml = wp_remote_retrieve_body( wp_remote_get($url) );
        if($xml){
            $xml = simplexml_load_string($xml);
            $ret = self::xmlToArray($xml);
        }
        return $ret;
    }

    static protected function xmlToArray($xml, $flattenValues=true, $flattenAttributes = true, $flattenChildren=true, $valueKey='@value', $attributesKey='@attributes', $childrenKey='@children') {
        $return = array();
        if (!($xml instanceof SimpleXMLElement)) {
            return $return;
        }
        $name = $xml->getName();
        $_value = trim((string) $xml);
        if (strlen($_value) == 0) {
            $_value = null;
        };
    
        if ($_value !== null) {
            if (!$flattenValues) {
                $return[$valueKey] = $_value;
            } else {
                $return = $_value;
            }
        }
    
        $children = array();
        $first = true;
        foreach ($xml->children() as $elementName => $child) {
            $value = self::xmlToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);
            
            if (isset($children[$elementName])) {
                if ($first) {
                    $temp = $children[$elementName];
                    unset($children[$elementName]);
                    $children[] = $temp;
    
                    $first = false;
                }
                $children[] = $value;
            } else {
                $children[$elementName] = $value;
            }
        }
        if (count($children) > 0) {
            if (!$flattenChildren) {
                $return[$childrenKey] = $children;
            } else {
                $return = array_merge($return, $children);
            }
        }
    
        $attributes = array();
        foreach ($xml->attributes() as $name => $value) {
            $attributes[$name] = trim($value);
        }
        if (count($attributes) > 0) {
            if (!$flattenAttributes) {
                $return[$attributesKey] = $attributes;
            } else {
                $return = array_merge((array) $return, $attributes);
            }
        }

        // print_r(':::::::::::::::::::::::::::::::<br/>');
        // print_r($return);
        // print_r('-------------------------------------');
    
        if (is_array($return) && count($return) === 0) {
            return null; //will return null instead of an empty array
        }
    
        return $return;
    }
}

// PdxSyncPdxHandler::doSync();