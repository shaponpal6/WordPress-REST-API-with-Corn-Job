<?php

class PDXSyncViewApartmentsSmallRender {

    /**
     * Shortcode callbac.
     * 
     * [pdx_apartments types='assignment,rent']
     */
    static public function shortcode($atts){
        $types = isset($attr['types']) && $attr['types'] ? explode(',', $attr['types']) : [];
        return self::render($types);
    }

    /**
     * @param array $types Types or apartments to list
     * @param int $limit Number of items to show
     * @param int $offset Number of items to skip from start
     * @param int $items_per_row Number of items to show on one row
     */
    static public function render($types, $limit = 0, $offset = 0, $items_per_row = 1){
        
        $apartments = PdxSyncPdxHandler::getApartments($limit, $offset, $types);
        
        $show_flat_number =  get_option(PDXSync::OPTION_LIST_SHOW_FLAT_NUMBER);

        $ret = '<div class="view-apartments-small apartments-base per-row-' . $items_per_row . '">';
        foreach($apartments as $apartment){
            $ret.= self::renderApartment($apartment, $show_flat_number);
        }
        $ret.= '</div>';

        return $ret;
    }

    /**
     * @param object $apartment Single apartment object
     * @param bool $show_flat_number If true flat number is shown, if false only flat staircase letter
     * 
     * @return string Returns html for showing a single apartment
     */
    static protected function renderApartment($apartment, $show_flat_number = false){
        
        global $wp;

        $is_rental = $apartment->isRental();
        $showings = $apartment->get('showings');
        // $pictures = $apartment->get('pictures');
        $pictures = (array)$apartment->get('estateagentcontactpersonpictureurl');

        // create picture object for accessing picture thumbnails
        $picture_obj = isset($pictures[0]) ? new PDXSyncPicture($pictures[0]) : false;

        $ret = '<div class="apartment">'
            . '<a class="apartment-link"'
                . ' href="' . esc_attr( $apartment->getUrl() ) . '"'
                . '>'
            . '<div class="header">'
                . "<h3>" . $apartment->getStreetAddress($show_flat_number) . "</h3>"
                . '<h5>' . ($apartment->has('Region') ? $apartment->get('Region') : $apartment->get('City')) . '</h5>'
            . '</div>'

            . ($picture_obj ? '<div class="image">'
                . '<img src="' . $picture_obj->getCropped(400, 220) . '">'
                . '</div>' : '')

            . '<div class="info">'
                .'<div class="price">'.$apartment->get(($is_rental ? 'RentPerMonth' : 'UnencumberedSalesPrice')).'</div>'
                .'<div class="area">' . $apartment->get('LivingArea') . '</div>'
                .'<div class="room-types">' . $apartment->get('RoomTypes') . '</div>'
                . self::renderShowings($showings)
            . '</div>' // info end
            ;

        $ret.= '</a>'
            . '</div>'; // apartment end

        return $ret;
    }

    /**
     * @param array $showings Array containing sowings to render
     * 
     * @return string Returns showing as html string
     */
    static protected function renderShowings($showings){
        $ret = '';
        if($showings){
            $ret.= '<div class="showings">';
            $ret.= '<div class="showing-title">' . __('Esittely') . ': </div>';
            foreach($showings as $showing){
                $ret.= '<div class="showing">'
                        . $showing['date']
                        . ' ' . $showing['start']
                        . ($showing['end'] ? ' - ' . $showing['end'] : '')
                    . '</div>';
            }
            $ret.= '</div>';
        }
        return $ret;
    }
}