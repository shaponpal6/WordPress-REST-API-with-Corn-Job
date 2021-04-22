<?php

class PDXSyncViewApartmentRender {

    /**
     * @param bool $show_flat_number
     * 
     * @return array
     */
    static protected function getFieldGrouping($show_flat_number = false){
        return array(
            array(
            "title" => __('Kohteen tiedot', 'pdx-sync')
            ,"fields"=>array(
                    'Heading'
                    , 'property_name'
                    , 'address' . ($show_flat_number ? '_number' : '')
                                 => __('Osoite', 'pdx-sync')
                    ,"Region"
                    , 'pdx_region'
                    , 'Country'
                    , 'pdx_address2'
                    , 'OtherPostCode'
                    ,"FloorLocation"
                    ,"OikotieID"
                    ,"LivingArea"
                    ,"TotalArea"
                    ,"RoomTypes"
                    ,"NumberOfRooms"
                    ,"GeneralCondition"
                    , 'ConditionDescription'
                    ,"BecomesAvailable"// - Vapautuu
                    ,"KitchenAppliances"// - Keittiön varustetasot
                    ,"Balcony"// - Parveke
                    ,"BathroomAppliances"// - Kylpyhuoneen varustetasot
                    ,"SaunaDescription"// Saunan kuvaus
                    ,"StorageSpace"// - Säilytystilatt
                    ,"Floor"// - Lattiamateriaalit
                    ,"DirectionOfWindows"// - Ikkunoiden suunta
                    ,"FutureRenovations"// - Tulevat korjauksett
                    ,"BasicRenovations"// - Peruskorjauksett
                    ,"BuildingRights"
                    ,"OwnSauna"
                    ,"ModeOfHabitation" // asumismuoto
                    ,"Services"// - Palvelutt
                    ,"SupplementaryInformation"// - Lisätiedott
                    ,"RealEstateID"
                    ,"TermOfLease"
                    ,"RentingTerms"
//                    , 'pdx_flat_number'
//                    , 'FlatNumber'
                    , 'realEstateType'
                    , 'newApartmentReserved'
                    , 'TargetHoliday'
                    , 'TargetNew'
                    , 'DateWhenAvailable'
                    )
            )
            ,array(
            "title" => __('Hintatiedot ja muut kustannukset', 'pdx-sync')
            ,"fields"=>array(
                        "UnencumberedSalesPrice"
                        ,"SalesPrice"
                        ,"RentPerMonth"
                        ,"RentSecurityDeposit"
                        ,"HeatingCosts"
                        ,"FinancingFee"// - Rahoitusvastike
                        ,"MaintenanceFee"
                        ,"WaterFee"// - Vesimaksu
                        ,"WaterFeeExplanation"
                        ,"Mortgages"
                        , 'EstateTax'
                        , 'SiteRent'
                        , 'ElectricUse'
                        , 'OilUse'
                        , 'HousingCompanyFee'
                        , 'OtherFees'
                        , 'CleaningFee'
                        , 'Sanitation'
                        , 'ChargeFee'
                        , 'TotalFee'
                        , 'SiteRepurchacePrice'
                        , 'SiteCondomiumFee'
                        , 'ModeOfFinancing'
                        , 'ShareOfDebt85'
                        , 'ShareOfDebt70'
                        , 'PropertyBlockRedemptionPrice'
                        , 'DebtPart'
                        , 'HousingLoans'
                        , 'SiteCondominiumFee'
                        , 'contingency'
                        , 'pdx_carslotfee'
                        , 'RentPerDay'
                        , 'RentPerWeek'
                        , 'RentPerWeekEnd'
                        , 'RentPerYear'
                        , 'ApartmentRentIncome'
                        , 'RentComission'
                        , 'ShareOfLiability'
                        )
            )
            ,array(
            "title" => __('Talon ja tontin tiedot', 'pdx-sync')
            ,"fields"=>array(
                    "type"
                    ,"newHouses"
                    ,"HousingCompanyName"
                    ,"YearOfBuilding"
                    ,"Estate"
                    ,"NumberOfApartments"
                    ,"OfficeArea"
                    ,"NumberOfOffices"
                    ,"CommonAreas"
                    ,"Sauna"
                    ,"ParkingSpace"
                    ,"Lift"
                    ,"BuildingMaterial"
                    ,"RoofType"
                    ,"AntennaSystem"
                    ,"MunicipalDevelopment"
                    ,"SiteArea"
                    ,"Site"
                    ,"RealEstateManagement"// - Kiinteistön hoito
                    ,"Disponent"// - Isännöitsijä
                    ,"BuildingPlanInformation"
                    ,"BuildingPlanSituation"// - Kohteen kaavoitustilanne
                    ,"Connections"// - Liikenneyhteydett
                    ,"Grounds"
                    ,"Heating"// - Lämmitys
                    ,"rc_energy_flag"
                    ,"rc_energyclass"
                    , 'FloorArea'
                    , 'ResidentialApartmentArea'
                    , 'AreaAdditional'
                    , 'pdx_area_additional'
                    , 'AreaInformation'
                    , 'EstateArea'
                    , 'ForestAmount'
                    , 'LandArea'
                    , 'OtherSpaceDescription'
                    , 'YearOfBuildingOriginal'
                    , 'TimeOfCompletion'
                    , 'SaunaCommon'
                    , 'LiftValue'
                    , 'Foundation'
                    , 'WallConstruction'
                    , 'RoofMaterial'
                    , 'Shore'
                    , 'Rantatyyppi'
                    , 'Direction'
                    , 'View'
                    , 'BalconyValue'
                    , 'with_furniture'
                    , 'UseOfWater'
                    , 'SewerSystem'
                    , 'VentilationSystem'
                    , 'rc_energy_flag'
                    , 'rc-energy-flag'
                    , 'rc_energyclass'
                    , 'rc-energyclass'
                    , 'asbestos_mapping'
                    )
            )
        );
    }

    /**
     * @param object $apartment Single apartment object
     * @param bool $show_flat_number If true flat number is shown, if false only flat staircase letter
     * 
     * @return string Returns html for showing a single apartment in full view
     */
    static public function renderApartment($apartment, $show_flat_number = false){
        
        $ret = '<div class="view-apartment">';

        if($apartment){

            $used = [];

            $ret.= "<h1 class='title'>"
                .($apartment->has("Region") ? "<span class='region'>" . $apartment->get("Region").",</span> " : "")
                . "<span class='address'>" . $apartment->getStreetAddress($show_flat_number) . "</span>"
                . ($apartment->has('LivingArea') ? ', ' . $apartment->get('LivingArea') : '')
                ."</h1>";

            // $pictures = $apartment->get('pictures');
            $pictures = (array) $apartment->get('estateagentcontactpersonpictureurl');

            if($pictures){
                $ret.= self::pictureSlider($pictures);
            }

            $ret.= self::renderShowings($apartment->get('showings'));

            $structure = PdxSyncPdxItem::getStructure();

            $field_grouping = self::getFieldGrouping($show_flat_number);

            $used['Description'] = true;
            $ret.= '<div class="description">' . $apartment->get('Description') . '</div>';
            $used['DescriptionEnglish'] = true;
            if($apartment->has('DescriptionEnglish')){
                $ret.= '<div class="description-english">' . $apartment->get('DescriptionEnglish') . '</div>';
            }

            $ret.= '<div class="col-wrap">'
                . '<div class="col1">';

            // list grouped fields
            foreach($field_grouping as $group){
                $ret.= '<h3>' . $group['title'] . '</h3>';

                $ret.= '<table class="info-table"><tbody>';
                foreach($group['fields'] as $k => $field){
                    $label = '';
                    // field label can be overrided by setting key to field name
                    // and value to label
                    if(!is_numeric($k)){
                        $label = $field;
                        $field = $k;
                    }

                    $conf = isset($structure[$field]) ? $structure[$field] : false;
                    
                    if($apartment->has($field) && !(isset($conf['system']) && $conf['system'])){
                        $ret.= '<tr>'
                            . '<td class="label">' . ($label ? $label : $conf['show']) . '</td>'
                            . '<td class="value">' . $apartment->get($field) . '</td>'
                            . '</tr>';
                    }
                    $used[$field] = true;
                }
                $ret.= '</tbody></table>';
            }

            // list remaining fields that wehre not found in grouped fields
            // as other info
            $other_fields = '';
            foreach($structure as $field => $conf){
                if(!isset($used[$field]) && $apartment->has($field)
                    && !(isset($conf['system']) && $conf['system'])){
                    $other_fields.= '<tr>'
                        . '<td class="label">' . $conf['show'] . '</td>'
                        . '<td class="value">' . $apartment->get($field) . '</td>'
                        . '</tr>';
                    
                }
            }
            if($other_fields){
                $ret.= '<h3>' . __('Muut tiedot', 'pdx-sync') . '</h3>'
                    . '<table class="info-table"><tbody>'
                        . $other_fields
                    . '</tbody></table>'
                    ;

            }

            $contact_pic = $apartment->has('EstateAgentContactPersonPictureUrl')
            ? new PDXSyncPicture($apartment->get('EstateAgentContactPersonPictureUrl')) : false;

             //  echo('.....................');
            //print_r($contact_pic);
            //echo('<br/>');
            
            $width = 200;
            $height = 500;
            $dimensions = $contact_pic ? $contact_pic->getResizedDimensions($width, $height) : false;

            $ret.= '</div>' // col1

                . '<div class="col2">'
                    . '<div class="contact-info">'
                        . '<h3>' . __('Ota yhteyttä', 'pdx-sync') . '</h3>'
                        . ($contact_pic ? '<img'
                                . ' src="' . $contact_pic->getResized($width, $height) . '"'
                                . ' width="' . $dimensions['width'] . '"'
                                . ' height="' . $dimensions['height'] . '">' : '')
                        . '<div class="name">' . $apartment->get("EstateAgentContactPerson") . '</div>'
                        . '<div class="contact-title">' . $apartment->get("EstateAgentTitle") . '</div>'
                        . '<div class="contact">' 
                            . '<a href="' . PDXSyncFormatHelper::spamSafe('mailto:' . $apartment->get("EstateAgentContactPersonEmail")) . '">'
                                . PDXSyncFormatHelper::spamSafe($apartment->get("EstateAgentContactPersonEmail"))
                            . "</a><br>"
                        . $apartment->get("EstateAgentTelephone") . '</div>'
                    . '</div>' // contact-info
                . '</div>' // col2

                . '</div>' // col-wrap
                ;

        }
        else{
            $ret = __('Apartment not found.', 'pdx-sync');
        }
        
        

        $ret.= '</div>';
        
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
            $ret.= '<div class="showing-title">' . __('Esittely') . ':</div>';
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

    /**
     * @param array $pictures Array containing pictures
     * 
     * @return string Returns HTML for showing the picture slider
     */
    static protected function pictureSlider($pictures){
                    // $picture_obj = isset($pictures[0]) ? new PDXSyncPicture($pictures[0]) : false;
            // getResized($width, $height = 0, $cover = false, $server_path = false)
        $ret = '<div class="picture-slider">';

        $slides = [];
        $slides_nav = [];

        $width = 900;
        $height = 600;
        $thumb_width = 107;
        $thumb_height = 80;
        foreach($pictures as $picture){
            $picture_obj = new PDXSyncPicture($picture);
            if($picture_obj){
                $dimensions = $picture_obj->getResizedDimensions($width, $height);

                $slides[] = '<li class="glide__slide">'
                        . '<div class="img-wrap"><div class="img-wrap2"><img'
                            . ' src="' . $picture_obj->getResized($width, $height) . '"'
                            . ' width="' . $dimensions['width'] . '"'
                            . ' height="' . $dimensions['height'] . '"'
                            . '></div></div>'
                    . '</li>';
                
                $slides_nav[] = '<li class="glide__slide">'
                . '<img'
                    . ' src="' . $picture_obj->getCropped($thumb_width, $thumb_height) . '"'
                    . ' width="' . $thumb_width . '"'
                    . ' height="' . $thumb_height . '"'
                    . '>'
            . '</li>';
        }
        }

        $ret.= '<div class="screen">
  <div class="glide__track" data-glide-el="track">
    <ul class="glide__slides">
      ' . implode('', $slides) . '
    </ul>
    <div data-glide-el="controls" class="controls">
        <button data-glide-dir="<" class="prev">&#10094;</button>
        <button data-glide-dir=">" class="next">&#10095;</button>
    </div>

  </div>
</div>';

$ret.= '<div class="nav">
<div class="glide__track" data-glide-el="track">
  <ul class="glide__slides">
    ' . implode('', $slides_nav) . '
  </ul>
  <div data-glide-el="controls" class="controls">
      <button data-glide-dir="<" class="prev">&#10094;</button>
      <button data-glide-dir=">" class="next">&#10095;</button>
  </div>

</div>
</div>';

        $ret.= '</div>';

        return $ret;
    }
}