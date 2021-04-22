<?php

class PdxSyncPdxHandler{

    /**
     * Downloads PDX file from remote server and updates data in local DB
     * 
     * @param string $pdx_xml If passed the xml is used instead of downloading xml from pdx enpoint
     * 
     * @return bool Returns true on success or false on failure
     */
    static public function doSync($pdx_xml = ""){

        // return PdxSyncPdxHandlerXML::doSync('');

        // print_r('..............');
        // echo '<pre>';

        // return PdxSyncPdxHandlerXML::doSync('');
        

       // $pdx_url = get_option(PDXSync::OPTION_PDX_URL);
        // $pdx_url = 'https://intra.pdx.fi/bulevardinkotimeklarit/save/out/kotisivut.xml';
        $pdx_url = 'https://kivi.etuovi.com/ext-api/v1/realties/homepage';
        

        if($pdx_xml){
            // $pdx_xml = simplexml_load_string($pdx_xml);
            // $pdx_content = self::xmlToArray($pdx_xml);
            // $pdx_content = self::jsonFormatLikePdxXml($pdx_xml);
            $pdx_content = self::getApartmentsFromDB();
            $pdx_content = self::jsonFormatLikePdxXml($pdx_content);
            $pdx_content = [];
        }
        else{
            $pdx_content = self::downloadPdx($pdx_url);
        }

        // echo json_encode($pdx_content);
        // // print_r($pdx_content);
        // exit();

        if($pdx_content && is_array($pdx_content) && count($pdx_content) > 0){
            
            // get list of currently locally stored items
            $old = self::getApartments();
            // echo '<pre>';
            // print_r($old);
            // echo json_encode($old, JSON_PRETTY_PRINT);
            // exit();
            

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
            //echo '<pre>';
            //print_r($pdx_content);
            $new_mapped = [];
            foreach($pdx_content as $data){
                $apartment = new PdxSyncPdxItem();
                $apartment->setImportData($data);
                $response = get_post_meta( $apartment->get('Key'), 'pdx_meta_key_api', true );
                if($response){
                    $response = json_decode($response, true);
                }
                $modify = [];
                if($response && is_array($response) && count($response) > 0){
                    foreach($response as $key => $value){
                        $modify[$key] = is_array($value) && count($value) > 0 ? json_encode($value) : $value;
                    }
                }
                if(count($modify) > 0){
                    $apartment->setImportData($modify);
                }
                //echo '<pre>';
                // print_r($response);
                //print_r($modify);
                // print_r($data);
                // exit();
                $new_mapped[$apartment->get('Key')] = $apartment;
            }

            // print_r($new_mapped);
            // exit();

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
            // print_r($new_mapped);
            // exit();




        }else{




            // get list of currently locally stored items
            $old = self::getApartments();
            //echo '<pre>';
            //print_r($old);
            // echo json_encode($old, JSON_PRETTY_PRINT);
            // exit();
            

            // map by key
            $old_mapped = [];
            if($old){
                foreach($old as $apartment){
                    if(isset($old_mapped[$apartment->get('Key')])){
                        $apartment->delete();
                    }
                    else{
                        $old_mapped[$apartment->get('Key')] = $apartment;
                    }
                }
            }

            //print_r($old_mapped);

            // get list of items in pdx file
            $new_mapped = [];
            foreach($old as $data){
                $apartment = new PdxSyncPdxItem();
                $apartment->setImportData($data);
                $response = get_post_meta( $apartment->get('Key'), 'pdx_meta_key_api', true );
                if($response){
                    $response = json_decode($response, true);
                }
                $modify = [];
                if($response && is_array($response) && count($response) > 0){
                    foreach($response as $key => $value){
                        $modify[$key] = is_array($value) && count($value) > 0 ? json_encode($value) : $value;
                    }
                }
                if(count($modify) > 0){
                    $apartment->setImportData($modify);
                }
                //echo '<pre>';
                // print_r($response);
                //print_r($modify);
                // print_r($data);
                // exit();
                $new_mapped[$apartment->get('Key')] = $apartment;
            }

            // print_r($new_mapped);
            // exit();

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
            // print_r($new_mapped);
            // exit();


            





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
                //  echo '<pre>';
                //  var_dump($item_data);
                //  echo '-----------<br/>----------------------------------------';
                // var_dump($item);
                // exit();
            }
        }

        // echo '<pre>';
        // var_dump($ret);
        // exit();

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
     * Image URL to object string
     * 
     * @param string $image URL to api file
     * 
     * @return string Returns image content as string 
     */
    protected static function imageObj($img){
        return '{
            "0": "'.$img.'",
            "sourceType": "assignment"
        }';
    }

    /**
     * Downloads pdx file and converts it to an array.
     * 
     * @param string $url URL to PDX file
     * 
     * @return array|bool Returns pdx file content as array or false if it could not be downloaded
     */
    protected static function jsonFormatLikePdxXml($pdx_content){
        // echo json_encode($pdx_content);
    //    echo '<pre/>';
        $ret = array();
        $index = 0;
        if($pdx_content && is_array($pdx_content) && count($pdx_content) > 0){
            foreach($pdx_content as $data){
                // print_r($data);
                $row = array(
                    "Key"=> isset($data['REALTY_UNIQUE_NO']) ? $data['REALTY_UNIQUE_NO'] : $data['itemkey'],
                    "MoreInfoUrl"=> "https://my.matterport.com/show/?m=Kao6fh8V5MC",
                    "TotalArea"=> '{ "0": "511.0", "unit": "m2" }',
                    "LivingArea"=> '{ "0": "462.0", "unit": "m2" }',
                    "City"=> "Espoo",
                    "Country"=> "Suomi",
                    "UnencumberedSalesPrice"=> '{ "0": "3900000.00", "currency": "EUR" }',
                    "RoomTypes"=> "7h, k, rt, 3x kph, sauna/spa, khh, var",
                    "Region"=> "Soukanniemi",
                    "GeneralCondition"=> '{ "0": "Erinomainen", "level": "2" }',
                    "ConditionDescription"=> "Huippukuntoinen ja laadukkaasti rakennettu Lammi-kivitalo",
                    "SupplementaryInformation"=> "Makuuhuoneet 4 kpl. \nAvara yl\u00e4kerran olohuone avautuu pariovista suoraan merelliselle terassille. Olohuoneessa manttelitakka, jossa imurivaraus.\nKodinhoitohuone: Toimiva ja hyvin suunniteltu, paljon s\u00e4ilytystilaa. Khh:n yhteydess\u00e4 walk-in closet. Varustus: pesukone, kuivausrumpu, kuivauskaappi, lattial\u00e4mmitys, lattiakaivo, silitysp\u00f6yt\u00e4/taso, pyykkikaapit, p\u00f6yt\u00e4taso.\nS\u00e4ilytystilat: Alakerrassa suuri varastotila, jossa paljon l\u00e4mmint\u00e4 ja kylm\u00e4\u00e4 s\u00e4ilytystilaa vaatehuoneet, ulkovarasto.\nPintamateriaalit: Sein\u00e4materiaalit: vaaleas\u00e4vyiset, tyylikk\u00e4\u00e4n hillityt, maalattu. Lattiamateriaalit: Miellytt\u00e4v\u00e4\u00e4 ja kulutusta kest\u00e4v\u00e4\u00e4 kivilaattaa.",
                    "KitchenAppliances"=> "Kauniin vaalea ja hyvin suunniteltu keitti\u00f6, jossa kivitasot, kivilaattalattia ja laadukkaat kodinkoneet. Varustus: j\u00e4\u00e4kaappi/pakastin, liesituuletin, mikroaaltouuni, astianpesukone, induktioliesi.",
                    "BathroomAppliances"=> "Kylpyhuoneita ylh\u00e4\u00e4ll\u00e4 kaksi sek\u00e4 erilliset wc:t. Alakerrassa kylpyl\u00e4osasto, jossa sauna, h\u00f6yrysauna ja oleskelutila (varaus poreammeellekin). Alakerrassa my\u00f6s erillinen wc suihkuineen. Varustus: kylpyamme, suihkusein\u00e4, lattial\u00e4mmitys, wc, suihku, peilikaappi, peili. Lattiat laattaa, sein\u00e4t kaakelia.",
                    "Description"=> "Luksusta meren rannalta!\nJykev\u00e4 ja laadukas Lammi- kivitalo arvostetulla alueella Soukanniemess\u00e4. \nTilaa ja laatua, korkeatasoiset materiaalit, upea spa-osasto alakerrassa.\nYl\u00e4kerrassa(tulotaso)kolme makuuhuonetta, ruokasali, tasokkaat kph/wc- tilat, iso terassi avautuu merelle, rauhallinen ty\u00f6huone ja iso tyylik\u00e4s olohuone, josta p\u00e4\u00e4sy terassille.\nAlakerrassa oma huone, johon erillinen kulku alakerran kautta. \nKahden auton talli. \nOma, kaunis tasamaa- ja korkea rinnetontti, 3075m2. \nIkkunoista avautuu avara ja kaunis saariston\u00e4kym\u00e4 merelle. \nSuojaisa ja aidattu piha, s\u00e4hk\u00f6portteineen ja valvontaj\u00e4rjestelmineen luo yksil\u00f6llisyytt\u00e4 ja omaa rauhaa. \nKatso 3D-esittelyvidoa alla olevasta linkist\u00e4; \nhttps://my.matterport.com/show/?m=Kao6fh8V5MC\nYksityisesittelyt luottamuksella / confidential contacts / konfidentiell kontakt ; Kai M\u00e4kinen LKV 0500 577334 ja/tai kai.m\u00e4kinen@bulevardinkotimeklarit.fi \n\n *Kun harkitset asunnon vaihtoa hy\u00f6dynn\u00e4 osaamisemme ja tuhansien asuntokauppojen kokemuksemme. Asunnonarviointi on maksuton, v\u00e4lityspalkkiomme 2,5% sis. alv. Soita ja sovi tapaaminen! \nKai M\u00e4kinen 0500 577334*",
                    "SiteArea"=> '{ "0": "3075", "unit": "m2" }',
                    "UseOfWater"=> "vesiosuuskunta",
                    "RealEstateID"=> "49-455-1-655",
                    "Grounds"=> "Kalliomainen tontti, jolla iso nurmikkoalue ja oma ranta (35m)oma kaunis tasamaa- ja korkea rinnetontti. Avara ja kaunis saariston\u00e4kym\u00e4 merelle. Suojaisa ja aidattu piha, s\u00e4hk\u00f6portteineen ja valvontaj\u00e4rjestelmineen.",
                    "BuildingPlanSituation"=> "Osayleiskaava",
                    "RoofType"=> "Aumakatto, tiili/betonitiili",
                    "Heating"=> "Maal\u00e4mp\u00f6",
                    "YearOfBuilding"=> '{ "original": "2008" }',
                    "NumberOfRooms"=> "7",
                    
                    "SalesPrice"=> '{ "0": "3900000.00", "currency": "EUR" }',
                    "BecomesAvailable"=> "Vapautuminen sopimuksen mukaan",
                    "Services"=> "Soukan ja Suvisaariston palvelut l\u00e4hell\u00e4.",
                    "Connections"=> "Linja-autoyhteys Soukanniementielt\u00e4, n. 100m.",
                    "MaintenanceFee"=> '{ "unit": "EUR/kk" }',
                    "FinancingFee"=> '{ "unit": "EUR/kk" }',
                    "Mortgages"=> "0",
                    "OtherFees"=> "S\u00e4hk\u00f6 120EUR/kk, Posti omaan laatikkoon 152EUR/vuosi.",
                    "EstateTax"=> "1502.00 EUR/v",
                    "WaterFee"=> '{ "0": "38", "unit": "EUR/kk" }',
                    "WaterFeeExplanation"=> "38 EUR/kk. Asukasluvun mukaan.",
                    "VideoPresentationURL"=> "https://my.matterport.com/show/?m=Kao6fh8V5MC",
                    "PostalCode"=> "02360",
                    "HousingCompanyName"=> '',
                    "VendorIdentifier"=> "89",
                    "StreetAddress"=> "Staffanintie 3 b",
                    "FloorLocation"=> "2",
                    "BuildingMaterial"=> "Kivi. Lammi-kivitalo",
                    "ContactRequestEmail"=> "kai.makinen@bulevardinkotimeklarit.fi",
                    "EstateAgentContactPersonEmail"=> "kai.makinen@bulevardinkotimeklarit.fi",
                    "EstateAgentContactPerson"=> "Kai M\u00e4kinen",
                    "EstateAgentTitle"=> "Myyntijohtaja, LKV",
                    "EstateAgentContactPersonPictureUrl"=> "https://cdn.pdx.fi/bulevardinkotimeklarit/image_20180807133448_kaju.jpg",
                    "EstateAgentContactPersonTelephone"=> "0500 577 334",
                    "EstateAgentTelephone"=> "0500 577 334",

                    "ShowingDate1"=> '',
                    "ShowingStartTime1"=> '',
                    "ShowingEndTime1"=> '',
                    "ShowingDateExplanation1"=> '',
                    "ShowingDate2"=> '',
                    "ShowingStartTime2"=> '', 
                    "ShowingEndTime2"=> '',
                    "ShowingDateExplanation2"=> '',
                    "OikotieID"=> "7857983",
                    "Latitude"=> "60.12303",
                    "Longitude"=> "24.67326",
                    "SewerSystem"=> "Umpis\u00e4ili\u00f6",
                    "AreaInformation"=> "P\u00e4\u00e4talon tilat kahdessa tasossa, osa maanpinnan alapuolella. Kahden auton talli 47m2 ja pihavarasto 11m2.",
                    "Heading"=> "Soukanniemen helmi!",
                    "rc-energyclass"=> '',
                    "rc-energy-flag"=> "Ei ole",
                    "Sauna"=> '{ "own": "K" }',
                    "pdx_id"=> "90588",
                    "pdx_object"=> "assignment",
                    "WaterFrontType"=> "Meri",
                    "VideoClip"=> "https://my.matterport.com/show/?m=Kao6fh8V5MC",
                    "Status"=> "Myynniss\u00e4",
                    "ContractDate"=> "06.07.2018",
                    "ModifiedDate"=> "24.11.2020 16:51",
                    "asbestos_mapping"=> "Ei",
                    "property_name"=> "Montebello",
                    "property_beachtype"=> "Oma",
                    "pdx_region"=> "Uusimaa",
                    "pdx_property_extra"=> "S\u00e4hk\u00f6ohjattu ajoportti ja valvontaj\u00e4rjestelm\u00e4 kameroineen.",
                    "OnlineOffer"=> "E",
                    "pdx_coordinates"=> "(60.123030904393474,24.673256619573976)",
                    "TargetNew"=> '{ "value": "E" }',
                    "TargetHoliday"=> '{ "value": "E" }',
                    "TradeBid"=> '{ "value": "E" }',
                    "Site"=> '{ "type": "O" }',
                    "ModeOfHabitation"=> '{ "type": "OM" }',
                    "Balcony"=> '{ "value": "E" }',
                    "Estate"=> '{ "type": "K" }',
                    "Shore"=> '{ "type": "OR" }',
                    // Picture 1 to 16
                    // Thumbnail 1 to 16
                    // Thumbbig 1 to 16
                    // Large 1 to 16
                    // Caption 1 to 16
                    "Caption1"=> "N\u00e4kym\u00e4 avokeitti\u00f6st\u00e4 olohuoneeseen ja tilavalle terassille, josta avoin merin\u00e4kym\u00e4",
                    "Caption2"=> "Pohjakuva suuntaa-antava",
                    "Caption3"=> "Terassi merelle etel\u00e4\u00e4n",
                    "Caption4"=> "Keitti\u00f6/ruokailutali",
                    "Caption5"=> "Olohuone ja eteisen lounge-tila",
                    "Caption6"=> "Yl\u00e4kerran makuuhuone",
                    "Caption7"=> "Yl\u00e4kerran masterbedroom",
                    "Caption8"=> "Yl\u00e4kerran kolmas makuuhuone",
                    "Caption9"=> "Ruokasali, josta upaet n\u00e4kym\u00e4t merelle",
                    "Caption10"=> "Talo ja autotallli suojaisan pihan puolelta, s\u00e4hk\u00f6portti ja aidattu piha lis\u00e4\u00e4 yksityisyytt\u00e4",
                    "Caption11"=> "Omaranta, jossa syvyytt\u00e4 ja tilaa isollekin veneelle",
                    "Caption12"=> "Ilmakuvan\u00e4kym\u00e4",
                    "Caption13"=> "N\u00e4kym\u00e4 merelt\u00e4",
                    "Caption14"=> "Asemakaavakuva",

                    
                    "Company"=> '{
                        "Name": "Bulevardin Kotimeklarit Oy",
                        "BusinessId": "1796707-4",
                        "Address": "Korkeavuorenkatu 2",
                        "Zipcode": "00140",
                        "City": "Helsinki",
                        "Phone": "09 6803 980",
                        "Email": "info@bulevardinkotimeklarit.fi"
                    }',
                    "type"=> "OT",
                    "newHouses"=> "E",
                    "realEstateType"=> "KIINTEISTO"
                );

                // add picture 1 to 16
                // add Thumbnail 1 to 16
                // add Thumbbig 1 to 16
                // add Large 1 to 16
                if(isset($data['IMAGES']) && is_array($data['IMAGES']) && count($data['IMAGES']) > 0){
                    $item = 1;
                    foreach($data['IMAGES'] as $images){
                        if(isset($images['VERSIONS']) && is_array($images['VERSIONS']) && count($images['VERSIONS']) > 0){
                            $versions = $images['VERSIONS'];
                            if(isset($versions['600x'])) $row['Picture'.$item] = self::imageObj($versions['600x']);
                            if(isset($versions['60x60'])) $row['Thumbnail'.$item] = self::imageObj($versions['60x60']);
                            if(isset($versions['234x180'])) $row['Thumbbig'.$item] = self::imageObj($versions['234x180']);
                            if(isset($versions['1200x1200'])) $row['Large'.$item] = self::imageObj($versions['1200x1200']);
                        };
                        $item++;
                    }
                }

                $ret[$index] = array_merge($row, $data);
                $index++;
                // add Caption 1 to 16
            }
        }
        // print_r($ret);

        //  exit();
       

        // print_r($pdx_content);
        
        
        return $ret;
    }

    protected static function getApartmentsFromDB(){
        global $wpdb;
        $ret = [];
        $table = PdxSyncDbStructure::getTableName(PdxSyncPdxItem::TABLE);

        $args = [];
        $where = '';
        // if($types){
        //     $in = [];
        //     foreach($types as $type){
        //         $in[] = '%s';
        //         $args[] = $type;
        //     }
        //     $where = ' WHERE pdx_object IN (' . implode(',', $in) . ')';
        // }
        $sql = 'SELECT * FROM ' . $table . $where . ' ORDER BY added DESC';

        // if($limit){
        //     $sql.= ' LIMIT %d, %d';
        //     $args[] = $offset;
        //     $args[] = $limit;
        // }

        if($args){
            $sql = $wpdb->prepare($sql, $args);
        }
        $data = $wpdb->get_results($sql, ARRAY_A);
        
        if($data){
            foreach($data as $item_data){
                $item = new PdxSyncPdxItem();
                $item->setDbData($item_data);
                $ret[] = $item_data;
                //  echo '<pre>';
                //  var_dump($item_data);
                //  echo '-----------<br/>----------------------------------------';
                // var_dump($item);
                // exit();
            }
        }

        // echo '<pre>';
        // var_dump($ret);
        // exit();

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
        // $json =  wp_remote_get($url);

        $client_id = 'bkotimeklarit';
        $client_secret = 'Aavanelli8';

        $context = stream_context_create(array(
            'http' => array(
                'header' => "Authorization: Basic " . base64_encode("$client_id:$client_secret"),
            ),
        ));

        try{
            // $json = file_get_contents($url, false, $context);
            // print_r(PDX_SYNC__PLUGIN_URL.'inc/data.txt');
            // $json = file_get_contents(PDX_SYNC__PLUGIN_URL.'inc/data.txt');
            $json = file_get_contents(PDX_SYNC__PLUGIN_URL.'inc/data.json');
            // print_r($json);
            if($json){
                $json = str_replace('u00a0', '', str_replace('\\', '', $json));
                $json = json_decode($json, true);
                // $json = simplexml_load_string($json);
                $ret = self::jsonFormatLikePdxXml($json);
            }
        }catch(Exception $e){
            // print_r($e);

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

// PdxSyncPdxHandler::doSync('hhh');