<?php

class PdxSyncDbStructure{

    const TABLE_VERSION_KEY = 'pdxsync_db_version';

    static public function install(){

        $current_version = get_option( self::TABLE_VERSION_KEY );

        if($current_version != PdxSyncPdxItem::TABLE_VERSION){

            // update DB structure if version has changed
            self::doInstall();

            update_option(self::TABLE_VERSION_KEY, PdxSyncPdxItem::TABLE_VERSION );
        }
    }

    static public function uninstall(){
        global $wpdb;

        delete_option(self::TABLE_VERSION_KEY);

        $table_name = self::getTableName(PdxSyncPdxItem::TABLE);
        $wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name );

    }

    /**
     * @param $table Name of table without 
     * 
     * @return string Returns name of pdx item db table including wp prefix
     */
    static public function getTableName($table){
        global $wpdb;
        return $wpdb->prefix . $table;
    }
    
    static protected function doInstall(){
        global $wpdb;

        $table_name = self::getTableName(PdxSyncPdxItem::TABLE);
        $structure = PdxSyncPdxItem::getStructure();

        $sql = self::structureToSql($table_name, $structure);

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * @param string Field name
     * 
     * @return string Returns WP db compatible field name
     */
    static public function toDbFieldName($field_name){
        return preg_replace("/[^a-z0-9_]/", "", strtolower($field_name));
    }

    /**
     * @param array $structure Array describing database table structure
     * 
     * @return string Returns wp sql for generating the db table
     */
    static protected function structureToSql($table_name, $structure){
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $fields = [];
        
        foreach($structure as $field => $conf){
            
            $db_field = isset($conf["db_field"]) ? $conf["db_field"] : $field;
            $db_field = self::toDbFieldName($db_field);
            $db_type = "";
            switch($conf["type"]){
                case "text":
                case "textarea":
                case "image":
                case "images":
                case "file":
                    $db_type = "text NOT NULL";
                break;
                case "int":
                    if(isset($conf["decimals"]) && $conf["decimals"]){
                        $db_type = "decimal(" . $conf["length"] . "," . $conf["decimals"] . ")";
                    }
                    else{
                        $db_type = "int(" . $conf["length"] . ")";
                    }
                break;
                case "select":
                    $db_type = "varchar(10) NOT NULL";
                break;
                case "date":
                    $db_type = "date NOT NULL";
                break;
                case "datetime":
                    $db_type = "datetime NOT NULL";
                break;
            }

            $fields[] = $db_field . " " . $db_type;
        }

        $sql = "CREATE TABLE " . $table_name . " (
id mediumint(9) NOT NULL AUTO_INCREMENT,
" . implode(",\n", $fields) . ",
PRIMARY KEY  (id)
) $charset_collate;";
        return $sql;
    }
}