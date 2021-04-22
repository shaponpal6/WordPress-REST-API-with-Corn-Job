<?php

class PDXSyncViewApartmentsRender {

    const LIST_LAYOUT_ROW = 'row';
    const LIST_LAYOUT_GRID = 'grid';

    /**
     * Shortcode callbac.
     * 
     * [pdx_apartments types='assignment,rent']
     */
    static public function shortcode($atts){
        $types = isset($atts['types']) && $atts['types'] ? explode(',', $atts['types']) : [];
        $list_layout = isset($atts['list_layout']) && $atts['list_layout'] ? $atts['list_layout'] : '';
        $cols = isset($atts['cols']) && $atts['cols'] ? $atts['cols'] : '';
        return self::render($types, $list_layout, $cols);
    }

    /**
     * @param array $types Types or apartments to list
     * @param string $list_layout Layout for list view: row|grid
     * @param int $cols Number of columns to use for grid layout: 1 ... 10
     */
    static public function render($types, $list_layout = '', $cols = 3){
        
        $apartment_id = preg_replace('/^([0-9]+)\-.*/', '$1'
            , get_query_var(PDXSync::GET_VAR_APARTMENT, 0));

            

        if($apartment_id){

            $show_flat_number =  get_option(PDXSync::OPTION_SINGLE_SHOW_FLAT_NUMBER);

            // show single apartment
            $apartment = PdxSyncPdxHandler::getApartment($apartment_id);
			 $aplink=$apartment->getListUrl();
            if($aplink == 'https://bulevardinkotimeklarit.fi/myyntikohteet/'){
                $myyntiko='https://bulevardinkotimeklarit.fi/myytavana_hakutulokset/';
            }
             else if($aplink == 'https://bulevardinkotimeklarit.fi/vuokrakohteet/'){
                $myyntiko='https://bulevardinkotimeklarit.fi/vuokrattavana-hakutulokset/';
            }
            
			
            $ret = 
                ($apartment ? '<div class="pdx-back-link"><a href="' . $myyntiko . '" for="'.$aplink.'">'
                 . __('Paluu kaikki kohteet', 'pdx-sync') . '</a></div>' : '')
                . PDXSyncViewApartmentRender::renderApartment($apartment, $show_flat_number);
        }
        else{
            // list apartments
            $limit = 0;
            $offset = 0;

            $show_flat_number =  get_option(PDXSync::OPTION_LIST_SHOW_FLAT_NUMBER);

            // print_r('$show_flat_number');
             

            $apartments = PdxSyncPdxHandler::getApartments($limit, $offset, $types);
            // echo '<pre>';
            // print_r( $apartments);
            
            
            
            $cols = ($cols && $cols > 0 && $cols <= 10) ? $cols : 3;
             

            $ret = '<div class="view-apartments'
                . ' layout-' . ($list_layout ? $list_layout : 'row')
                . ' per-row-' . $cols . '">';
				 $temp_array = array();
            foreach($apartments as $apartment){
				//    $date = strtotime($apartment->get('modifieddate'));
				   $date = mt_rand();
                    
                //    echo('------------11111---------');
                   $temp_array[$date] = self::renderApartmentRow($apartment, $show_flat_number);
                   
                switch($list_layout){
                    default:
                    case self::LIST_LAYOUT_ROW: {
                        $ret.= self::renderApartmentRow($apartment, $show_flat_number);
                    } break;
                    case self::LIST_LAYOUT_GRID: {
                        $ret.= self::renderApartmentGrid($apartment, $show_flat_number);
                    } break;
                }
            }
			      krsort($temp_array);
            $counter = 1;
            foreach($temp_array as $key => $appartment){
                if($counter <= 3){
                    $ret.= $appartment;
                    $counter++;
                }
            }
            $ret.= '</div>';

            // echo '<pre>';
            // print_r($apartments);
            // print_r($temp_array);
            // print_r($ret);
            // exit();
        }

        return $ret;
    }

    /**
     * @param object $apartment Single apartment object
     * @param bool $show_flat_number If true flat number is shown, if false only flat staircase letter
     * 
     * @return string Returns html for showing a single apartment
     */
    static protected function renderApartmentGrid($apartment, $show_flat_number = false){
        
        $is_rental = $apartment->isRental();
        $showings = $apartment->get('showings');
        // $pictures = $apartment->get('pictures');
        $pictures = (array) $apartment->get('estateagentcontactpersonpictureurl');
        $is_new_house = ($apartment->get('newHouses', true) == 'K');

        // create picture object for accessing picture thumbnails
        $picture_obj = isset($pictures[0]) ? new PDXSyncPicture($pictures[0]) : false;

        $new_house_label = '';
        if($is_new_house){
            $structure = PdxSyncPdxItem::getStructure();
            $new_house_label = $structure['newHouses']['show'];
        }

        $year_of_building = $apartment->has('yearofbuildingoriginal')
            ? $apartment->get('yearofbuildingoriginal')
            : $apartment->get('yearofbuilding');

        $ret = '<div class="apartment">'
            . '<a class="apartment-link"'
                . ' href="' . esc_attr( $apartment->getUrl() ) . '"'
                . '>'
            . '<div class="header">'
                . "<h3>" . $apartment->getStreetAddress($show_flat_number) . "</h3>"
                . '<h5>' . ($apartment->has('Region') ? $apartment->get('Region') . ', ' : '') . $apartment->get('City') . '</h5>'
            . '</div>'

            . ($picture_obj ? '<div class="image">'
                . '<img src="' . $picture_obj->getCropped(400, 220) . '">'
                . self::renderShowings($showings)
                . '</div>' : '')

            . '<div class="info">'
                . '<div class="space-between">'
                    . '<div class="price">'.$apartment->get(($is_rental ? 'RentPerMonth' : 'UnencumberedSalesPrice')).'</div>'
                    . ( $apartment->has('LivingArea') ? '<div class="area">' . $apartment->get('LivingArea') . '</div>' : '')
                . '</div>'
                .'<div class="room-types">' . $apartment->get('RoomTypes') . '</div>'
                . '<div class="property-type">'
                    . ( $is_new_house ? '<b>' . $new_house_label . ':</b> ' : '')
                    . $apartment->get('type')
                    . ($year_of_building ? ', ' . $year_of_building : '')
                . '</div>'
            . '</div>' // info end
            ;

        $ret.= '</a>'
            . '</div>'; // apartment end

        return $ret;
    }

    /**
     * @param object $apartment Single apartment object
     * @param bool $show_flat_number If true flat number is shown, if false only flat staircase letter
     * 
     * @return string Returns html for showing a single apartment
     */
    static protected function renderApartmentRow($apartment, $show_flat_number = false){
        
        
        
        $is_rental = $apartment->isRental();
        $showings = $apartment->get('showings', true);
        // $pictures = $apartment->get('pictures');
        $pictures = (array) $apartment->get('estateagentcontactpersonpictureurl');


        // create picture object for accessing picture thumbnails
        // $picture_obj = isset($pictures[0]) ? new PDXSyncPicture($pictures[0]) : false;
        $picture_obj = isset($pictures[0]) ? new PDXSyncPicture($pictures[0]) : false;

        // echo('<pre>');
        // echo('---------------------|||>');
        // print_r($pictures);
        // echo('<br/>');
        // print_r($pictures2);
        // echo('<br/>');
        // print_r((array) $pictures2);
        // echo('<br/>');
        // print_r($pictures2[0]);
        // echo('<br/>');
        // print_r($picture_obj);
        // print_r($picture_obj->getCropped(1200, 400));
        // print_r($apartment->getUrl());
        // echo('<||---------------------');
        // exit();

        $ret = '<div class="apartment">'
            . '<a class="apartment-link"'
            . ($picture_obj ? ' style="background-image: url(' . $picture_obj->getCropped(1200, 400) . ');"'
                     : '')
                . ' href="' . esc_attr( $apartment->getUrl() ) . '"'
                . '>'

            . '<div class="info">'
                ."<h2>" . $apartment->getStreetAddress($show_flat_number) . "</h2>"
                . '<h3>' . ($apartment->has('Region') ? $apartment->get('Region') : $apartment->get('City')) . '</h3>'
                .'<div class="room-types">' . $apartment->get('RoomTypes') . '</div>'
                . ($apartment->has('LivingArea') ? '<div class="area">' . $apartment->get('LivingArea') . '</div>' : '')
                .'<div class="price">'.$apartment->get(($is_rental ? 'RentPerMonth' : 'UnencumberedSalesPrice')).'</div>'
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