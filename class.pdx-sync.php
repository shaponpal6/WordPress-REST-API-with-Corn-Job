<?php

class PDXSync {
    const OPTION_GROUP = 'pdxsync';
    const OPTION_PDX_URL = 'pdx_url';
    const OPTION_PDX_SALE_PAGE = 'pdx_sale_page';
    const OPTION_PDX_RENTAL_PAGE = 'pdx_rental_page';
    const OPTION_LIST_SHOW_FLAT_NUMBER = 'pdx_list_show_flat_number';
    const OPTION_SINGLE_SHOW_FLAT_NUMBER = 'pdx_single_show_flat_number';

    const TEXT_DOMAIN = 'pdx-sync';
    const OPTION_LAST_CRON_RUN_TIME = 'pdxsync_last_cron_run_time';
    const OPTION_LAST_CRON_RUN_ERROR = 'pdxsync_last_cron_run_error';
    const AJAX_ACTION_DO_SYNC = 'pdx-sync-do_sync';
    const PDX_META_KEY_API = 'pdx_meta_key_api';
    const AJAX_ACTION_SAVE_API = 'pdx-sync-save-api-data';
    const AJAX_ACTION_MARGE_API = 'pdx-sync-marge-api';

    const CRON_HOURLY = "pdx_sync_hourly";

    const GET_VAR_APARTMENT = 'apartment';

    static $errors = []; 

    static public function init(){
        if ( is_admin() ){
            self::initAdminHooks();
        }
        self::initPublicHooks();
    }

    static public function pluginsLoaded(){
        PdxSyncDbStructure::install();
    }

    static public function pluginMenuInit(){
        add_menu_page(
            __('PDX Sync', 'textdomain'),
            'PDX Sync',
            'manage_options',
            'pdxsync',
            array('PDXSync', 'apiMargePage'),
            'dashicons-image-filter',
            26
        );
        add_submenu_page(
            'pdxsync',
            __('API', 'textdomain'),
            __('API', 'textdomain'),
            'manage_options',
            'pdxMargeAPI',
            array('PDXSync', 'apiMargePage')
        );
        
    }

    static public function pluginAdminInit(){
        
    }

    static public function apiMargePage(){
        include_once PDX_SYNC__WIDGETS_DIR . 'margeApiPage.php';
    }

    static protected function initAdminHooks(){
        add_action( 'admin_menu', ['PDXSync', 'admin_menu']);
        add_action( 'admin_init', ['PDXSync', 'register_settings'] );
        
        // flush rewrite rules when sale or rental page changes
        add_action( 'add_option_' . self::OPTION_PDX_SALE_PAGE, ['PDXSync', 'flushRewriteRules'] );
        add_action( 'add_option_' . self::OPTION_PDX_RENTAL_PAGE, ['PDXSync', 'flushRewriteRules'] );
        add_action( 'update_option_' . self::OPTION_PDX_SALE_PAGE, ['PDXSync', 'flushRewriteRules'] );
        add_action( 'update_option_' . self::OPTION_PDX_RENTAL_PAGE, ['PDXSync', 'flushRewriteRules'] );

        // ajax
        add_action( 'wp_ajax_' . self::AJAX_ACTION_DO_SYNC, ['PDXSync', 'ajaxDoSync'] );
        add_action( 'wp_ajax_' . self::AJAX_ACTION_SAVE_API, ['PDXSync', 'ajaxSaveMargeApiData'] );
        add_action( 'wp_ajax_' . self::AJAX_ACTION_MARGE_API, ['PDXSync', 'ajaxMargeApiNew'] );
    }

    static public function flushRewriteRules(){
        flush_rewrite_rules();
    }

    static public function initWidgets(){
        register_widget( 'PdxSyncViewApartments' );
        add_shortcode( 'pdx_apartments', ['PDXSyncViewApartmentsRender', 'shortcode'] );
    }

    static protected function initPublicHooks(){
        add_action( self::CRON_HOURLY, ['PDXSync', 'cron_hourly'] );
        if ( ! wp_next_scheduled( self::CRON_HOURLY ) ) {
            wp_schedule_event( time(), 'hourly', self::CRON_HOURLY );
        }

        add_action('wp_enqueue_scripts', ['PDXSync', 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', ['PDXSync', 'admin_enqueue_scripts']);

        // url handling for single apartment pages
        add_rewrite_tag('%' . PDXSync::GET_VAR_APARTMENT . '%', '([^&]+)');

        $page_ids = array();
        $sale_page_id = get_option(self::OPTION_PDX_SALE_PAGE);
        if($sale_page_id){
            $page_ids[] = $sale_page_id;
        }
        $rental_page_id = get_option(self::OPTION_PDX_RENTAL_PAGE);
        if($rental_page_id && $rental_page_id != $sale_page_id){
            $page_ids[] = $rental_page_id;
        }
        if($page_ids){
            foreach($page_ids as $page_id){
                $slug = get_post_field( 'post_name', $page_id );
                if($slug){
                    add_rewrite_rule('^' .preg_quote($slug) . '/([0-9]+\-.*)/?'
                        , 'index.php?page_id=' . $page_id . '&' . PDXSync::GET_VAR_APARTMENT . '=$matches[1]'
                        , 'top');
                }
            }
        }

    }

    static public function enqueue_scripts(){

        $css_files = [
            'pdx-sync_style' => 'style.css'
            , 'pdx-sync_glide.core.min.css' => 'glide.core.min.css'
            , 'pdx-sync_glide.theme.min.css' => 'glide.theme.min.css'
        ];

        $js_files = [
            'pdx-sync_glide.min.js' => 'glide.min.js'
            , 'pdx-sync_script.js' => 'script.js'
            , 'pdx-sync_customApi.js' => 'customApi.js'
        ];

        foreach($css_files as $name => $css_file){
            wp_register_style($name , plugins_url( PDX_SYNC__CSS_URL . $css_file, __FILE__) );
            wp_enqueue_style( $name );
        }

        foreach($js_files as $name => $js_file){
            wp_enqueue_script(
                    $name
                    , plugins_url( PDX_SYNC__JS_URL . $js_file, __FILE__)
                );
        }

    }

    static public function admin_enqueue_scripts(){
        $css_files = ['pdx-sync_customApiUI.css' => 'customApiUI.css'];

        $js_files = [
            'pdx-sync_admin.js' => 'admin.js'
            , 'pdx-sync_customApi.js' => 'customApi.js'
        ];

        wp_enqueue_script('jquery');

        foreach($css_files as $name => $css_file){
            wp_register_style($name , plugins_url( PDX_SYNC__CSS_URL . $css_file, __FILE__) );
            wp_enqueue_style( $name );
        }

        foreach($js_files as $name => $js_file){
            wp_enqueue_script(
                    $name
                    , plugins_url( PDX_SYNC__JS_URL . $js_file, __FILE__)
                );
        }
    }

    static public function ajaxSaveMargeApiData(){
        if(isset($_POST['action']) && $_POST['action'] === self::AJAX_ACTION_SAVE_API){
           
            $dd = '{"Key":"20521827","MoreInfoUrl":"https://my.matterport.com/show/?m=Kao6fh8V5MC","City":"Espoo44444444444","Country":"Suomi22222","RoomTypes":"7h, k, rt, 3x kph, sauna/spa, khh, var","Region":"Soukanniemi","TotalArea":{"0":"","Unit":""},"LivingArea":{"0":"","Unit":""}}';
            $key = isset($_POST['key']) ? (int) $_POST['key'] : '20521827' ;
            $data = isset($_POST['data']) ? $_POST['data'] : $dd;
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $response = get_post_meta( $key, self::PDX_META_KEY_API, true );
            if (!!$response) {
                // Replace the old value with the new, or add a key into the db
                $response = update_post_meta( $key, self::PDX_META_KEY_API, $data );
            } else {
                // If the value is already set return true
                $response = add_post_meta( $key, self::PDX_META_KEY_API, $data, true );
                
            }

            $apartment = new PdxSyncPdxItem();
            // $apartment->setImportData($data);
            // $response = get_post_meta( $key, 'pdx_meta_key_api', true );
            $response = $data;
            // print_r($response);
            if($response){
                $response = json_decode($response, true);
            }
            $modify = [];
            $update = '';
            if($response && is_array($response) && count($response) > 0){
                foreach($response as $key => $value){
                    $modify[$key] = is_array($value) && count($value) > 0 ? json_encode($value) : $value;

                }
            }
            if(count($modify) > 0){
                $modify['id'] = '45';
                $apartment->setImportData($modify);
                $update = $apartment->update2($apartment->getData(), $id);
            }

            // update
            // $array = json_decode($data, true);
            // $apartment = new PdxSyncPdxItem();
            // $apartment->setImportData($array);
            // $apartment->update($apartment->getData());
            // PdxSyncPdxHandler::doSync($data);
            // PdxSyncPdxHandler::doSync('marge');
            // echo '<pre>';
            // print_r($response);
            // print_r($modify);
            // print_r($apartment->getData());
            // exit();
            $res = [
                'key2' => self::PDX_META_KEY_API,
                'update' => $update,
                'id' => $id,
                'key' => $key,
                'data' => $data,
                'result' => $response,
                'message' => self::getUpdatedTimeAgo()
            ];
            wp_die(json_encode($res));
        }
    }

    static public function ajaxMargeApiNew(){
        if(isset($_POST['action']) && $_POST['action'] === self::AJAX_ACTION_MARGE_API){
            self::cron_hourly();
            $res = [
                'result' => true
                , 'message' => self::getUpdatedTimeAgo()
            ];
            wp_die(json_encode($res));
        }
    }


    static public function ajaxDoSync(){
        if(isset($_POST['action']) && $_POST['action'] === self::AJAX_ACTION_DO_SYNC){
            self::cron_hourly();
            $res = [
                'result' => true
                , 'message' => self::getUpdatedTimeAgo()
            ];
            wp_die(json_encode($res));
        }
    }

    /** 
     * @param string $message Error message
     */
    static public function addError($message){
        self::$errors[] = $message;
    }

    static public function cron_hourly(){

        // store time when cron was last run
        update_option(self::OPTION_LAST_CRON_RUN_TIME, current_time( 'timestamp' ) );

        PdxSyncPdxHandler::doSync();

        // store errors that occured during last cron run
        update_option(self::OPTION_LAST_CRON_RUN_ERROR, json_encode(self::$errors) );
    }

    static public function register_settings(){
        register_setting( self::OPTION_GROUP, self::OPTION_PDX_URL );
        register_setting( self::OPTION_GROUP, self::OPTION_PDX_SALE_PAGE );
        register_setting( self::OPTION_GROUP, self::OPTION_PDX_RENTAL_PAGE );
        register_setting( self::OPTION_GROUP, self::OPTION_LIST_SHOW_FLAT_NUMBER );
        register_setting( self::OPTION_GROUP, self::OPTION_SINGLE_SHOW_FLAT_NUMBER );
    }
    
    static public function admin_menu() {
        add_options_page( __('PDX Sync asetukset', 'pdx-sync'), __('PDX Sync', 'pdx-sync'), 'manage_options'
                        , 'pdx-sync-options', ['PDXSync', 'admin_options_page'] );
    }

    static public function admin_options_page() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        ?>
<div class="wrap">
    <h1><?php _e('PDX Sync options', 'pdx-sync') ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields( self::OPTION_GROUP ); ?>
        <?php do_settings_sections( self::OPTION_GROUP ); ?>
        <table class="form-table">
    
            <tr valign="top">
            <th scope="row"><?php _e('Shortcode') ?></th>
            <td>
                <p>
                    <?php _e('Lisää lyhytkoodi sivulle jolla haluat että kohteet näytetään.', 'pdx-sync') ?>
                </p>
                <table>
                    <tbody>
                        <tr>
                            <td><?php _e('Näyttää myynti- ja vuokrakohteet samassa listassa', 'pdx-sync') ?></td>
                            <td><code>[pdx_apartments types='assignment,rent']</code></td>
                        </tr>
                        <tr>
                            <td><?php _e('Näyttää myyntikohteet', 'pdx-sync') ?></td>
                            <td><code>[pdx_apartments types='assignment']</code></td>
                        </tr>
                        <tr>
                            <td><?php _e('Näyttää vuokrakohteet', 'pdx-sync') ?></td>
                            <td><code>[pdx_apartments types='rent']</code></td>
                        </tr>
                        <tr>
                            <td><?php _e('Lisäasetukset, list_layout=grid näyttää kohteet taulukkonäkymänä ja cols=3 asettaa taulukkonäkymän sarakemäärän kolmeksi', 'pdx-sync') ?></td>
                            <td><code>[pdx_apartments types='assignment' list_layout=grid cols=3]</code></td>
                        </tr>
                    </tbody>
                </table>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row"><?php _e('Viimeksi päivitetty', 'pdx-sync') ?></th>
            <td><?php
                $errors = get_option( self::OPTION_LAST_CRON_RUN_ERROR );

                // show last cron run time ago
                echo '<span id="updatedtimeago">' . self::getUpdatedTimeAgo() . '</span>';
                echo ' <button class="button button-secondary" type="button" onclick="'
                        . esc_attr('pdxsync_admin.doSync(this, {data: {action: "' . self::AJAX_ACTION_DO_SYNC . '"}});')
                        . '"'
                        . ' data-loading-text="' . esc_attr('Päivitys meneillään...', 'pdx-sync') . '"'
                        . '>'
                        . __('Päivitä nyt', 'pdx-sync')
                    . '</button>';
                
                // list errors from last cron run
                if($errors){
                    $errors = json_decode($errors, true);
                    echo $errors ? '<div>' . __('Virhe tiedon päivityksessä:', 'pdx-sync') . '<br>'
                         . implode('<br>', $errors) . '</div>' : '';
                }
            ?>
            <p class="description"><?php _e('Näyttää milloin asunnot on viimeksi päivitetty pdx-rajapinnan kautta. Päivitys tapahtuu automaattisesti noin tunnin välein. Pävitä nyt painiketta painamalla voi myös päivittää tiedot. Päivitys voi kestää muutaman minuutia.', 'pdx-sync') ?></p>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row"><?php _e('PDX osoite', 'pdx-sync') ?></th>
            <td><input type="text" class="regular-text ltr" name="<?php echo self::OPTION_PDX_URL ?>"
                     value="<?php echo esc_attr( get_option(self::OPTION_PDX_URL) ); ?>" />
                     <p class="description"><?php _e('PDX rajapinnan osoite.', 'pdx-sync') ?></p>
                    </td>
            </tr>

            <tr valign="top">
            <th scope="row"><?php _e('Myyntikohteiden sivu', 'pdx-sync') ?></th>
            <td><?php echo self::getPageSelect(self::OPTION_PDX_SALE_PAGE
                , get_option(self::OPTION_PDX_SALE_PAGE)) ?>
                <p class="description"><?php _e('Sivu jolla myyntikohteet näytetään. Asetusta tarvitaan jotta kohteiden linkit toimisvat oikein.', 'pdx-sync') ?></p>
                </td>
            </tr>

            <tr valign="top">
            <th scope="row"><?php _e('Vuokrakohteiden sivu', 'pdx-sync') ?></th>
            <td><?php echo self::getPageSelect(self::OPTION_PDX_RENTAL_PAGE
                , get_option(self::OPTION_PDX_RENTAL_PAGE)) ?>
                <p class="description"><?php _e('Sivu jolla vuokrakohteet näytetään. Asetusta tarvitaan jotta kohteiden linkit toimisvat oikein.', 'pdx-sync') ?></p>
                </td>
            </tr>
            
            <?php $list_show_flat_number = get_option(self::OPTION_LIST_SHOW_FLAT_NUMBER); ?>
            <tr valign="top">
            <th scope="row"><?php _e('Näytä porrasnumero listassa', 'pdx-sync') ?></th>
            <td>
                <select name="<?php echo self::OPTION_LIST_SHOW_FLAT_NUMBER ?>">
                    <option value=""
                        <?php echo (!$list_show_flat_number ? ' selected="selected"' : ''); ?>
                        ><?php echo esc_html(__('Ei', 'pdx-sync')); ?></option>
                    <option value="1"
                        <?php echo ($list_show_flat_number ? ' selected="selected"' : ''); ?>
                        ><?php echo esc_html(__('Kyllä', 'pdx-sync')); ?></option>
                </select>
                     <p class="description"><?php _e('Näyttää tai piiloittaa porrasnumeron listausnäkymissä.', 'pdx-sync') ?></p>
            </td>
            </tr>
            
            <?php $single_show_flat_number = get_option(self::OPTION_SINGLE_SHOW_FLAT_NUMBER); ?>
            <tr valign="top">
            <th scope="row"><?php _e('Näytä porrasnumero kohdesivulla', 'pdx-sync') ?></th>
            <td>
                <select name="<?php echo self::OPTION_SINGLE_SHOW_FLAT_NUMBER ?>">
                    <option value=""
                        <?php echo (!$single_show_flat_number ? ' selected="selected"' : ''); ?>
                        ><?php echo esc_html(__('Ei', 'pdx-sync')); ?></option>
                    <option value="1"
                        <?php echo ($single_show_flat_number ? ' selected="selected"' : ''); ?>
                        ><?php echo esc_html(__('Kyllä', 'pdx-sync')); ?></option>
                </select>
                     <p class="description"><?php _e('Näyttää tai piiloittaa porrasnumeron yksittäisen kohteen sivulla.', 'pdx-sync') ?></p>
            </td>
            </tr>
    
        </table>
        
        <?php submit_button(); ?>

    </form>
</div>
<?php
    }

    static public function getUpdatedTimeAgo(){
        $run_time = get_option( self::OPTION_LAST_CRON_RUN_TIME );
        return $run_time ? human_time_diff( $run_time, current_time( 'timestamp' ) ).' '.__( 'ago' )
        : '-';
    }

    /**
     * @param string $name Form field name
     * @param string $default_value Default value for field
     * 
     * @return string Returns html for showing page select
     */
    static protected function getPageSelect($name, $default_value){

        $args = array(
            'post_type' => 'page'
            , 'post_status' => array('publish', 'future', 'draft', 'pending', 'private')
        );
        $pages = get_posts($args);

        $ret = '<select onchange="jQuery(this).parent().find(\'.page-id\').val(jQuery(this).val());">'
            . '<option value="">' . esc_html(__('# Ei mikään', 'pdx-sync')) . '</option>'
            ;

        if($pages){
            foreach($pages as $page){
                $ret.= '<option'
                    . ' value="' . esc_attr($page->ID) . '"'
                    . ($default_value == $page->ID ? ' selected="selected"' : '')
                    . '>' . esc_html($page->post_title) . '</option>';
            }
        }

        $ret.= '</select>';

        $ret.= '<input type="text" size="3" class="page-id" name="' . esc_attr($name) . '" value="' . esc_attr($default_value) . '">';

        return $ret;
    }

    static public function install(){

        // run cron jobs when installing so that apartments get synced in case
        // plugin was deactivated and then re-activated
        PDXSync::cron_hourly();
    }

    static public function uninstall(){

        // remove all apartments
        $apartments = PdxSyncPdxHandler::getApartments();
		foreach($apartments as $apartment){
			$apartment->delete();
        }
        
        // remove last sync time property
        delete_option(self::OPTION_LAST_CRON_RUN_TIME);
        delete_option(self::OPTION_LAST_CRON_RUN_ERROR);

        wp_clear_scheduled_hook(self::CRON_HOURLY);

        // remove apartments table
        PdxSyncDbStructure::uninstall();
    }
}

PDXSync::ajaxSaveMargeApiData();