<?php


function makeTextType($lebel, $type='text'){
    $parts = preg_split('/(?=[A-Z])/', $lebel, -1, PREG_SPLIT_NO_EMPTY);
    $lebel = implode(" ",$parts);
    // if($type==='textarea'){}
    return '{ "type": "'.$type.'", "lebel": "'.$lebel.'" }';
}

function makeObjType($lebel, $key1='', $key2=''){
    $parts = preg_split('/(?=[A-Z])/', $lebel, -1, PREG_SPLIT_NO_EMPTY);
    $lebel = implode(" ",$parts);
    $val1 = $key1 == '0' ? 'Value' : $key1;
    $val2 = $key2 === '0' ? 'Value' : ucfirst($key2);
    $str = '{ "type": "obj", "lebel": "'.$lebel.'", "fields":[';
    if($key1 !==''){
        $str .= '{"key": "'.$key1.'",  "type": "text", "lebel": "'.$val1.'" }';
    }
    if($key2 !==''){
        $str .= ',{"key": "'.$key2.'",  "type": "text", "lebel": "'.$val2.'" }';
    }
    $str .= ']}';
    return $str;
}

function makeObjTypeArray($lebel, $fields){
    $parts = preg_split('/(?=[A-Z])/', $lebel, -1, PREG_SPLIT_NO_EMPTY);
    $lebel = implode(" ",$parts);
    $str = '{ "type": "obj", "lebel": "'.$lebel.'", "fields":[';
    $srt2 = '';
    foreach($fields as $field){
        $srt2 .= '{"key": "'.$field.'",  "type": "text", "lebel": "'.ucfirst($field).'" },';
    }
    $str .= rtrim($srt2, ',');
    $str .= ']}';
    return $str;
}
// print_r(makeObjType('TotalArea', 0, 'Unit'));

 $fields = array(
        "Key"=> '{ "type": "text", "lebel": "Unique Key (Don\'t change) " }',
        "MoreInfoUrl"=> '{ "type": "url", "lebel": "More Info Url" }',
        "TotalArea"=> makeObjType('TotalArea', "0", 'Unit'),
        "LivingArea"=> makeObjType('LivingArea', "0", 'Unit'),
        "City"=> '{ "type": "text", "lebel": "City" }',
        "Country"=> '{ "type": "text", "lebel": "Country" }',
        "UnencumberedSalesPrice"=> makeObjType('UnencumberedSalesPrice', "0", 'currency'),
        "RoomTypes"=> '{ "type": "text", "lebel": "Room Types" }',
        "Region"=> '{ "type": "text", "lebel": "Region" }',
        "GeneralCondition"=> makeObjType('GeneralCondition', "0", 'level'),
        "ConditionDescription"=> makeTextType('ConditionDescription', 'textarea'),
        "SupplementaryInformation"=> makeTextType('SupplementaryInformation', 'textarea'),
        "KitchenAppliances"=> makeTextType('KitchenAppliances', 'textarea'),
        "BathroomAppliances"=> makeTextType('BathroomAppliances', 'textarea'),
        "Description"=> makeTextType('Description', 'textarea'),
        "SiteArea"=> makeObjType('SiteArea', "0", 'unit'),
        "UseOfWater"=> makeTextType('UseOfWater'),
        "RealEstateID"=> makeTextType('RealEstateID'),
        "Grounds"=> makeTextType('RealEstateID', 'textarea'),
        "BuildingPlanSituation"=> makeTextType('BuildingPlanSituation'),
        "RoofType"=> makeTextType('RoofType'),
        "Heating"=> makeTextType('Heating'),
        "YearOfBuilding"=> makeObjType('YearOfBuilding', "original"),
        "NumberOfRooms"=> makeTextType('NumberOfRooms'),

        // "SalesPrice"=> makeObjType('SalesPrice', "0", 'currency'),
        // "BecomesAvailable"=> makeTextType('BecomesAvailable'),
        // "Services"=> makeTextType('Services'),
        // "Connections"=> makeTextType('Connections'),
        // "MaintenanceFee"=> makeObjType('MaintenanceFee', 'Unit'),
        // "FinancingFee"=> makeObjType('FinancingFee', "0", 'Unit'),
        // "Mortgages"=> makeTextType('Mortgages', 'number'),
        // "OtherFees"=> makeTextType('OtherFees'),
        // "EstateTax"=> makeTextType('EstateTax'),
        // "WaterFee"=> makeObjType('WaterFee', "0", 'Unit'),
        // "WaterFeeExplanation"=> makeTextType('WaterFeeExplanation'),
        // "VideoPresentationURL"=> makeTextType('VideoPresentationURL', 'url'),
        // "PostalCode"=> makeTextType('PostalCode'),
        // "HousingCompanyName"=> makeTextType('HousingCompanyName'),
        // "VendorIdentifier"=> makeTextType('VendorIdentifier'),
        // "StreetAddress"=> makeTextType('StreetAddress'),
        // "FloorLocation"=> makeTextType('FloorLocation'),
        // "BuildingMaterial"=> makeTextType('BuildingMaterial'),
        // "ContactRequestEmail"=> makeTextType('ContactRequestEmail', 'email'),
        // "EstateAgentContactPersonEmail"=> makeTextType('EstateAgentContactPersonEmail', 'email'),
        // "EstateAgentContactPerson"=> makeTextType('EstateAgentContactPerson'),
        // "EstateAgentTitle"=> makeTextType('EstateAgentTitle'),
        // // "EstateAgentContactPersonPictureUrl"=> makeTextType('EstateAgentContactPersonPictureUrl'), // Image array
        // "EstateAgentContactPersonTelephone"=> makeTextType('EstateAgentContactPersonTelephone'),
        // "EstateAgentTelephone"=>makeTextType('EstateAgentTelephone'),

        // "ShowingDate1"=> makeTextType('ShowingDate1'),
        // "ShowingStartTime1"=>makeTextType('ShowingStartTime1'),
        // "ShowingEndTime1"=> makeTextType('ShowingEndTime1'),
        // "ShowingDateExplanation1"=> makeTextType('ShowingDateExplanation1'),
        // "ShowingDate2"=> makeTextType('ShowingDate2'),
        // "ShowingStartTime2"=> makeTextType('ShowingStartTime2'), 
        // "ShowingEndTime2"=> makeTextType('ShowingEndTime2'),
        // "ShowingDateExplanation2"=> makeTextType('ShowingDateExplanation2'),
        // "OikotieID"=> makeTextType('OikotieID'),
        // "Latitude"=> makeTextType('Latitude'),
        // "Longitude"=> makeTextType('Longitude'),
        // "SewerSystem"=> makeTextType('SewerSystem'),
        // "AreaInformation"=> makeTextType('AreaInformation', 'textarea'),
        // "Heading"=> makeTextType('Heading'),
        // "rc-energyclass"=> makeTextType('rc-energyclas'),
        // "rc-energy-flag"=> makeTextType('rc-energy-flag'),
        // "Sauna"=> makeObjType('Sauna', 'own'),
        // "pdx_id"=> makeTextType('pdx_id'),
        // "pdx_object"=> makeTextType('pdx_object'),
        // "WaterFrontType"=> makeTextType('WaterFrontType'),
        // "VideoClip"=> makeTextType('VideoClip'),
        // "Status"=> makeTextType('Status'),
        // "ContractDate"=> makeTextType('ContractDate', 'datetime'),
        // "ModifiedDate"=> makeTextType('ModifiedDate', 'datetime'),
        // "asbestos_mapping"=> makeTextType('asbestos_mapping'),
        // "property_name"=> makeTextType('property_name'),
        // "property_beachtype"=> makeTextType('property_beachtype'),
        // "pdx_region"=> makeTextType('pdx_region'),
        // "pdx_property_extra"=> makeTextType('pdx_property_extra', 'textarea'),
        // "OnlineOffer"=> makeTextType('OnlineOffer'),
        // "pdx_coordinates"=> makeTextType('pdx_coordinates', 'textarea'),
        // "TargetNew"=> makeObjType('TargetNew', 'value'),
        // "TargetHoliday"=> makeObjType('TargetHoliday', 'value'),
        // "TradeBid"=> makeObjType('TradeBid', 'value'),
        // "Site"=> makeObjType('Site', 'type'),
        // "ModeOfHabitation"=> makeObjType('ModeOfHabitation', 'type'),
        // "Balcony"=> makeObjType('Balcony', 'value'),
        // "Estate"=> makeObjType('Estate', 'type'),
        // "Shore"=> makeObjType('Shore', 'type'),
        // // Image
        // //Caption
        // "Company"=> makeObjTypeArray('Company', array("Name","BusinessId","Address","Zipcode","City","Phone","Email")),
        // "type"=> makeTextType('type'),
        // "newHouses"=> makeTextType('newHouses'),
        // "realEstateType"=> makeTextType('realEstateType')

        
 );

// print_r(makeObjTypeArray('Company', ["Name","BusinessId","Address","Zipcode","City","Phone","Email"]));

function textUI($key, $val='', $lebel=''){
    echo '<div class="apiRow"><lebel for="title">'.$lebel.'</lebel><input class="pdxText regular-text" key="'.$key.'" name="'.$key.'" type="text" value="'.$val.'"></div>';
}

function dateUI($key, $val='', $lebel=''){
    echo '<div class="apiRow"><lebel for="title">'.$lebel.'</lebel><input class="pdxText regular-text" key="'.$key.'" name="'.$key.'" type="datetime" value="'.$val.'"></div>';
}

function urlUI($key, $val='', $lebel=''){
    echo '<div class="apiRow"><lebel for="title">'.$lebel.'</lebel><input class="pdxText regular-text" key="'.$key.'" name="'.$key.'" type="url" value="'.$val.'"></div>';
}

function contentUI($key, $val='', $lebel=''){
    echo '<div class="apiRow"><lebel for="title">'.$lebel.'</lebel><textarea class="pdxText regular-text" key="'.$key.'" name="'.$key.'" rows="4" cols="50">'.$val.'</textarea></div>';
}


function inputUI($key, $type, $lebel='', $val='', $obj=false){
    echo '<div class="apiRow'.($obj ? 'Obj' : "").'">
            <lebel for="'.$type.'-'.$key.'">'.$lebel.'</lebel>
            <input class="pdxText regular-text" data-key="'.$key.'" name="'.$key.'" id="'.$type.'-'.$key.'" type="'.$type.'" value="'.$val.'">
            </div>';
    // if($type==='textarea'){
    //     echo '<div class="apiRow'.($obj ? 'Obj' : "").'">
    //         <lebel for="'.$type.'-'.$key.'">'.$lebel.'</lebel>
    //         <textarea class="pdxText regular-text" data-key="'.$key.'" name="'.$key.'" id="'.$type.'-'.$key.'" type="'.$type.'">
    //         '.$val.'</textarea>
    //         </div>';
    // }else{
        
    // }
}

function makeUI($obj){
    $pdxitem = isset($_GET['pdxitem']) ? $_GET['pdxitem'] : 0;
    if(!$pdxitem) {
        echo '<h4>No Selected Apartment</h4>';
        return;
    }
    $apartment = PdxSyncPdxHandler::getApartment($pdxitem);
    // $response = get_post_meta( '11', '_elementor_page_settings', true );
    // print_r($apartment->get('VendorIdentifier'));
    // print_r($apartment->get('LivingArea'));
    // echo '<br>';
    // print_r(!!$response);
    // print_r($response);
    // echo '<br>';
    // print_r($apartment->get('GeneralCondition'));
    
    echo '<div id="pdxApiContainer">';
    echo '<input type="hidden" id="pdxApiRowId" value="'.$apartment->get('id', true).'">';
    foreach($obj as $key => $value){
        $arr = json_decode($value, true);
        if($arr['type']==='obj'){
            echo '<div class="apiObjContainer">';
            echo '<h4>'.$arr['lebel'].'</h4>';
            echo '<p class="apiOldValue">Previous Value: '. null !== $apartment->get($key) ? $apartment->get($key) : 'No Value'.'</p>';
            echo '<div class="apiObjBox" data-key="'.$key.'">';
            foreach($arr['fields'] as $field){
                echo inputUI($field['key'], $field['type'], $field['lebel'], '', true);
            }
            echo '</p>';
            echo '</div>';
        }else{
            echo inputUI($key, $arr['type'], $arr['lebel'], null !== $apartment->get($key) ? $apartment->get($key) : '');
        }

    }
    echo '</div>';
}

function oldApiView(){
    $apartments = PdxSyncPdxHandler::getApartments();
    
    // $structure = PdxSyncPdxItem::getStructure(); 
    echo '<ul id="pdxApiOldContainer">';
    foreach($apartments as $pdxItem){
        $url = basename($_SERVER['REQUEST_URI']);
        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'pdxitem='.$pdxItem->get('id', true);
        echo '<a class="apiOldRowUrl" href="'.$url.'">';
        echo '<li class="apiOldRow" data-key="'.$pdxItem->get('Key').'">';
        echo '<h4>'.$pdxItem->get('property_name').'</h4>';
        echo '<div class="apiObjBox" >'.$pdxItem->get('RoomTypes').'</div>';
        echo '</li>';
        echo '</a>';
    }
    echo '</ul>';
}



//print_r(json_decode('{ "type": "obj", "lebel": "Total Area", "fields":[{ "key": "0",  "type": "text", "lebel": "value"}]}', true));

?>
<div class="wrap">
<h1>Marge API</h1>
<span id="pdxLog" class="error"></span>
<div class="pdxWraper">
    <section class="apiLeft">
        <?php echo oldApiView(); ?>
    </section>
    <section class="apiRight">
        <?php echo makeUI($fields); ?>
    </section>
</div>


<p class="submit"><input type="submit" name="submit" id="pdxApiSubmit" class="button button-primary" value="Marge API"></p></form>

</div>