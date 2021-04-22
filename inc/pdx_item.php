<?php

class PdxSyncPdxItem{
	
	const TABLE = 'pdxsyncitems';
	const TABLE_VERSION = 1;

	const TYPE_ASSIGNMENT = 'assignment';
	const TYPE_RENT = 'rent';
	
	protected $data = [];
	protected $structure = false;

	/**
	 * Saves item to db.
	 * 
	 * @return bool Returns true if item was saved successfully or fasle if not
	 */
	public function save(){
		global $wpdb;
		$ret = false;

		

		if($this->data){
			
			$table = PdxSyncDbStructure::getTableName(self::TABLE);
			
			$data = $this->data;
			$data = $this->handleFiles($data);
			// print_r($data);
            //exit();
			$res = $wpdb->insert( 
					$table
					, $data
					, $this->getFormatArr($data) 
				);
			
			$ret = true;
		}

		return $ret;
	}

	/**
	 * Updates loaded item with passed data
	 * 
	 * @param array $data Data in the format as it is stored in the DB
	 * 
	 * @return bool Returns true on success or false on failure
	 */
	public function update($data){
		global $wpdb;
		$ret = false;
		// echo '<pre>';
		// print_r($this->data);
		// 	exit();

		if($this->data && isset($this->data["id"]) && $this->data["id"]){
			
			$table = PdxSyncDbStructure::getTableName(self::TABLE);

			$data = $this->handleFiles($data, $this->data);
			

			$wpdb->update($table
					, $data
					, array('id' => $this->data['id'])
					, $this->getFormatArr($data)
					, array('%d')
				);
		}

		return $ret;
	}



	public function update2($data, $id){
		global $wpdb;
		$ret = false;
		// echo '<pre>';
		// print_r($this->data);
		// 	exit();

		if($this->data && isset($id) && $id > 0){
			
			$table = PdxSyncDbStructure::getTableName(self::TABLE);

			$data = $this->handleFiles($data, $this->data);
			

			$wpdb->update($table
					, $data
					, array('id' => $id)
					, $this->getFormatArr($data)
					, array('%d')
				);

			return $data;
		}

		return $ret;
	}

	/**
	 * @return bool Returns true if item was deleted or false if not
	 */
	public function delete(){
		global $wpdb;
		$ret = false;
		if($this->data && isset($this->data['id']) && $this->data['id']){

			$this->removeFiles();

			$wpdb->delete( PdxSyncDbStructure::getTableName(self::TABLE), array( 'id' => $this->data['id'] ), array( '%d' ) );

			$ret = true;
		}
		return $ret;
	}

	/**
	 * Removes all files that belong to active item
	 * 
	 * @return bool Returns true on success or false on failuser
	 */
	protected function removeFiles(){
		$ret = false;
		if($this->data){
			$this->loadStructure();
			foreach($this->structure as $field => $conf){
				switch($conf['type']){
					case 'image':
					case 'file':
						$db_field = $this->getDbFieldName($field, $conf);
						$field_data = $this->data[$db_field];
						PdxSyncFileHandler::removeFile($field_data);

					break;
					case 'images':
						$db_field = $this->getDbFieldName($field, $conf);
						$field_data = $this->data[$db_field];
						$data[$db_field] = PdxSyncFileHandler::removeMultipleFiles($field_data);
					break;
				}
			}
		}
		return $ret;
	}

	/**
	 * Saves files from passed data to /uploads/ directory. If $old_data
	 * is passed field fields are compared with it and unused files are removed.
	 * 
	 * @param array $data Item data in db format
	 * @param array $old_data Data of item before saving the new data
	 * 
	 * @return array Returns $data with fiel field values updated
	 */
	protected function handleFiles($data, $old_data = false){
		$this->loadStructure();

		if($data){
			foreach($this->structure as $field => $conf){
            
				switch($conf['type']){
					case 'image':
					case 'file':
						$db_field = $this->getDbFieldName($field, $conf);
						
						$url = isset($data[$db_field]) ? $data[$db_field] : false;
						$old_file = $old_data && isset($old_data[$db_field]) ? $old_data[$db_field] : false;

						$data[$db_field] = PdxSyncFileHandler::saveFile($url, $old_file);
						
					break;
					case 'images':
						$db_field = $this->getDbFieldName($field, $conf);
						$old_file = $old_data && isset($old_data[$db_field]) ? $old_data[$db_field] : false;
						$data[$db_field] = PdxSyncFileHandler::saveMultipleFiles($data[$db_field], $old_file);
						
					break;
				}
			}
			
		}
		// print_r($data);
		// exit();

		return $data;
	}

	/**
	 * @param array $data Array containing data that the format should be for
	 * 
	 * @return array Returns array with formats for passed data
	 */
	protected function getFormatArr($data){
		$ret = [];
		$structure = self::getStructure();

		$type_map = [];
		foreach($structure as $field => $conf){
			$db_field = $this->getDbFieldName($field, $conf);
			$type = '%s';
			switch($conf['type']){
                case "int":
                    if(isset($conf["decimals"]) && $conf["decimals"]){
						$type = '%f';
                    }
                    else{
						$type = '%d';
                    }
                break;
			}
			
			$type_map[$db_field] = $type;
		}

		foreach($data as $db_field => $value){
			$ret[] = isset($type_map[$db_field]) ? $type_map[$db_field] : '%s';
		}

		return $ret;
	}

	/**
	 * @param string $field Field name from pdx
	 * @param array $field_conf Field configuration
	 */
	protected function getDbFieldName($field, $field_conf){

		return isset($field_conf["db_field"]) && $field_conf["db_field"] ? $field_conf["db_field"]
					: PdxSyncDbStructure::toDbFieldName($field);
	}

	/**
	 * @param string $field Field name as it is defined in the tables structure (mostly same as in XML)
	 * @param bool $raw If true value is returned as it is stored in the db
	 * 
	 * @return string Returns requested fields data
	 */
	public function get($field, $raw = false){
		

		$this->loadStructure();
		$conf = & $this->structure[$field];
		

		$db_name = $this->getDbFieldName($field, $conf);
		$ret = isset($this->data[$db_name]) ? $this->data[$db_name] : '';
		
		if(!$raw){

			switch($conf['type']){
				case 'image':
				case 'file':
					if($ret){
						$ret = PdxSyncFileHandler::getFileInfo($ret);
					}
				break;
				case 'images':
					if($ret){
						$ret = PdxSyncFileHandler::getMultipleFileInfo($ret);
					}
				break;
				case 'select':
					$ret = $conf['defined_arr'][$ret];
				break;
				case 'text':
					$ret = PDXSyncFormatHelper::formatUnits($ret);
				break;
				case 'textarea':
					$ret = PDXSyncFormatHelper::formatUnits($ret);
					$ret = PDXSyncFormatHelper::formatParagraphs($ret);
				break;
			}

			switch($field){
				case 'showings':{

					// time limit for when showings are over
					$now = time();

					$showings = array();

					// loop trough all possible showing date fields and get values
					for($n=1;$n<=2;$n++){

						$sdate = $this->get('ShowingDate' . $n);
						// echo ('<pre>');
						

						
						// no date
						if($sdate == "0000-00-00"){
							continue;
						}
						
						// set date to early morning
						$showing_time = strtotime($sdate . ' 23:59:59');

						
						
						// skip if current time is later than end of showing date day
						if($showing_time<$now){
							continue;
						};
						
						// add time as formated string to showings arr
						$showing_arr = array(
										'date' => date_i18n( get_option( 'date_format' ), strtotime($sdate) )
										, 'start' => $this->get('ShowingStartTime' . $n)
										, 'end' => $this->get('ShowingEndTime' . $n)
										, 'explanation' => $this->get('ShowingDateExplanation' . $n)
										);
										
						$showings[] = $showing_arr;
					}
					$ret = $showings;

					// echo ('<pre>');
					// echo ('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>');
					// var_dump($ret);
					// exit();

				} break; // showings end

				case 'address': {
					// address without flat number
					$ret = $this->getStreetAddress().", ".$this->get("PostalCode")." ".$this->get("City");
				} break; // address end

				case 'address_number': {
					// address with flat number
					$show_flat_number = true;
					$ret = $this->getStreetAddress($show_flat_number).", ".$this->get("PostalCode")." ".$this->get("City");
				} break; // address end

			}// switch $field end

			if(isset($conf["decimals"]) && $conf["decimals"]){
				$ret = number_format($ret, $conf["decimals"], ',', ' ');
				// remove extra decimal zeros
				$ret = preg_replace('/,0+$/', '', $ret);
			}

			if(isset($conf['unit']) && $conf['unit']){
				
				$extensions = [
						'm2' => 'm<sup>2</sup>'
						, 'EUR' => '&euro;'
						, 'EUR_kk' => '&euro;/' . __('kk', 'pdx-sync')
						, 'EUR_p' => '&euro;/' . __('p', 'pdx-sync')
						, 'EUR_vko' => '&euro;/' . __('vko', 'pdx-sync')
						, 'EUR_vkonloppu' => '&euro;/' . __('vkonloppu', 'pdx-sync')
						, 'EUR_v' => '&euro;/' . __('v', 'pdx-sync')
					];

				$ret.= ' ' . $extensions[$conf['unit']];
			}
			else if (isset($conf['unit_from']) && $this->has($conf['unit_from'])){
				$ret.= ' ' . $this->get($conf['unit_from']);
			}

		}

		return $ret;
	}

	/**
	 * @return bool Returns true if field has value or false if not
	 */
	public function has($field){
		$this->loadStructure();
		$conf = $this->structure[$field];
		$value = $this->get($field);
		switch($conf["type"]){
			default:
				$ret = is_array($value) && $value || (trim($value) != '');
			break;
			case "image":
				$ret = is_array($value) && isset($value['path']) && $value['path'];
			break;
			case "images":
				$ret = is_array($value) && count($value) > 0;
			break;
//			case "file":
			case "int":
				$ret = (ceil($value) != 0);
			break;
			case "date":
				$ret = ($value != '0000-00-00');
			break;
			case "datetime":
				$ret = ($value != '0000-00-00 00:00:00');
			break;
		}

		// check if other field has same value and in that case hide
		if($ret && isset($conf['if_not_same']) && $conf['if_not_same']){
			if($this->get($field, true) == $this->get($conf['if_not_same'], true)){

				// related field value is same. Treat this field as no content
				$ret = false;
			}
		}

		if($ret && isset($conf['blacklist_values']) && $conf['blacklist_values'] && is_array($conf['blacklist_values'])){
			$value = strtolower($this->get($field, true));
			foreach($conf['blacklist_values'] as $check_val){
				if($value == $check_val){
					// value found in blacklist
					$ret = false;
					break;
				}
			}
		}

		// hide current field if related field has value
		if($ret && isset($conf['if_not']) && $conf['if_not']){
			if($this->has($conf['if_not'])){
				$ret = false;
			}
		}

		return $ret;
	}

	/**
	 * @return string Returns url where the apartment can be viewed
	 */
	public function getUrl(){
		
		$ret = '';

		$page_id = get_option($this->isRental()
			? PDXSync::OPTION_PDX_RENTAL_PAGE : PDXSync::OPTION_PDX_SALE_PAGE);
		
		if($page_id){

			// get parameter links
			$get_links = !get_option('permalink_structure');

			$ret = get_permalink($page_id);
			if($get_links){
				$ret.= (strpos($ret, '?') === false ? '?' : '&')
					. PDXSync::GET_VAR_APARTMENT . '='
					. sanitize_title($this->get('id') . '-' . $this->get('StreetAddress'));
			}
			else{
				$ret.= sanitize_title($this->get('id') . '-' . $this->get('StreetAddress') . '/');
			}
		}

		return $ret;
	}

	/**
	 * @return string Returns url where same type apartments are listed
	 */
	public function getListUrl(){
		$page_id = get_option($this->isRental() 
			? PDXSync::OPTION_PDX_RENTAL_PAGE : PDXSync::OPTION_PDX_SALE_PAGE);
		return $page_id ? get_permalink($page_id) : '';
	}

	/**
	 * @param bool $show_flat_number If true flat number is shown, if false only flat staircase letter is shown
	 * 
	 * @return string Returns item street address
	 */
	public function getStreetAddress($show_flat_number = false){
		$ret = trim($this->get("StreetAddress"));
		
		$flat_number = '';
        if($this->has('FlatNumber')){
            $flat_number = $this->get('FlatNumber');
        }
        else if($this->has('pdx_flat_number')){
            $flat_number = $this->get('pdx_flat_number');
		}

		if($flat_number){
			$flat_number = trim($flat_number);

			if($show_flat_number){
				// include flat number in address

				$parts = preg_split('/\s+/', $flat_number);
				$first_part_orig = preg_replace('/[0-9]+$/', '', $parts[0]);
				$first_part = strtoupper($first_part_orig);

				$addr_last_part = strtoupper(preg_replace('/.+\s([^\s]+)$/', '\\1', $ret));

				// check if address does not end with ' A'
				if($addr_last_part == $first_part){
					// cut away last part from address if it is same as
					// first part of flat number
					$ret = trim(substr($ret, 0, -strlen($addr_last_part)));
				}
				else{
					$flat_number_len = strlen($flat_number);
					if(substr($ret, -$flat_number_len) == $flat_number){
						$ret = trim(substr($ret, 0, -$flat_number_len));
					}
				}
				$ret.= ' ' . $flat_number;
			}
			else{
				if(!is_numeric($flat_number)){
					
					$exclude_words = [
						'LT' // Liiketila
					];

					// Address: Kristianinkatu 1 A
					// FlatNumber: A 7
					// Dagmarinkatu 9b B (B 9)
					$parts = preg_split('/\s+/', $flat_number);
					$first_part_orig = preg_replace('/[0-9]+$/', '', $parts[0]);
					$first_part = strtoupper($first_part_orig);

					$addr_last_part = strtoupper(preg_replace('/.+\s([^\s]+)$/', '\\1', $ret));

					if(!preg_match('/^[0-9]+[A-Z]+$/', $addr_last_part)){
						// address ends with 9b, nothing else should be added

						if(!in_array($first_part, $exclude_words)){

							// check if address does not end with ' A'
							if($addr_last_part != $first_part){
								$ret.= ' ' . $first_part_orig;
							}
						}
					}
				}
			}
		}
		
		return $ret;
	}

	/**
	 * @return bool returns true if active item is rental item or false if not
	 */
	public function isRental(){
		return ($this->get('pdx_object', true) == self::TYPE_RENT);
	}

	/**
	 * @return array Returns data as it is stored in the db
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * @param array $data Data in the format as it is stored in the DB
	 * 
	 * @return void
	 */
	public function setDbData($data){
		$this->data = $data;
	}

	/**
	 * Sets pdx item data from data returned from pdx endpoint and converted to array
	 * 
	 * @param array $data Import data
	 * 
	 * @return void
	 */
	public function setImportData($data){
		$structure = self::getStructure();

		foreach($structure as $field => $conf){

			$value = "";
			$db_field = $this->getDbFieldName($field, $conf);
			
			// attr selection
			if(isset($conf["attr"])){
				$tag = $field;
				$path = "";
				if(isset($conf["tag"]) && $conf["tag"]){
					$tag = $conf["tag"];
				}
				if(isset($conf["tag"]) && $conf["tag"] == ""){
					// get value from root element
					$path = $conf["attr"];
				}
				else{
					// get value from element with defined tag
					$path = $tag . "." . $conf["attr"];
				}
				$value = $this->selectPath($data, $path, "");
			}
			else{
				if(isset($conf["path"])){
					// get value from path
					$value = $this->selectPath($data, $conf["path"]);
				}
				else if(isset($data[$field])){
					// get value from tag
					$value = $data[$field];
				}
			}

			switch($conf['type']){
				case 'date':
					if($value){
						// check if date is in dd.mm.yyyy format
						if(preg_match('/([0-9]{2})\.([0-9]{2})\.([0-9]{4})/', $value, $m)){
							$value = $m[3] . '-' . $m[2] . '-' . $m[1];
						}
					}
				break;
			}

			if(is_array($value)){
				if(isset($value[0])){
					// if field has attributes the value is stored in 0 key
					$value = $value[0];
				}
				else{
					// arrays should not be used as values
					$value = '';
				}
			}

			if(isset($conf["field_prefix"]) && $conf["field_prefix"]){
				// field is multi image field (Picture1, ...)
				$value = array();
				for($n = 1; $n <= $conf["num_fields"]; $n++){
					if(isset($data[$conf["field_prefix"] . $n]) && $data[$conf["field_prefix"] . $n]){
						$value[] = $data[$conf["field_prefix"] . $n];
					}
				}
			}

			$this->data[$db_field] = $value;
		}
	}

	/**
	 * @param array $data Array containing data
	 * @param string $path Dot separated path: path.to.data
	 * @param mixed $default Default value to return if data was not found
	 * 
	 * @return mixed Returns value from path or === false if not found
	 */
	protected function selectPath($data, $path, $default = false){
		$ret = $data;

		$path_arr = explode(".", $path);
		$not_found = false;
		foreach($path_arr as $k){
			if(isset($ret[$k])){
				$ret = $ret[$k];
			}
			else{
				// key not found. stop looking and return false
				$not_found = true;
				break;
			}
		}

		return $not_found ? $default : $ret;
	}

	/**
	 * Loads field structure to $this->structure
	 */
	protected function loadStructure(){
		
		if(!$this->structure){
			$this->structure = self::getStructure();
		}
		
	}


	/**
	 * @return array Returns PDX item structure definition
	 */
	static public function getStructure(){
		
		$noyes_arr=array(
			"E"=>__('Ei', 'pdx-sync')
			,"K"=>__('Kyllä', 'pdx-sync')
			,"hide"=>"# ".__('Piiloita', 'pdx-sync')
		);

		$type_arr=array(
			"KT"=>__('Kerrostalo', 'pdx-sync')
			,"OT"=>__('Omakotitalo', 'pdx-sync')
			,"RT"=>__('Rivitalo', 'pdx-sync')
			,"PT"=>__('Paritalo', 'pdx-sync')
			,"ET"=>__('Erillistalo', 'pdx-sync')
			,"MO"=>__('Mökki tai huvila', 'pdx-sync')
			,"LH"=>__('Lomahuoneisto', 'pdx-sync')
			,"UL"=>__('Loma-asunto ulkomailla', 'pdx-sync')
			,"LO"=>__('Lomaosake', 'pdx-sync')
			,"OKTT"=>__('Omakotitalotontti', 'pdx-sync')
			,"VT"=>__('Vapaa-ajan tontti', 'pdx-sync')
			,"RTT"=>__('Rivitalotontti', 'pdx-sync')
			,"TO"=>__('Tontti', 'pdx-sync')
			,"AP"=>__('Autopaikka', 'pdx-sync')
			,"AT"=>__('Autotalli', 'pdx-sync')
			,"MAT"=>__('Maatila', 'pdx-sync')
			,"MET"=>__('Metsätila', 'pdx-sync')
			,"MU"=>__('MU', 'pdx-sync')
			,"TOT"=>__('Toimistotila', 'pdx-sync')
			,"LT"=>__('Liiketila', 'pdx-sync')
			,"VART"=>__('Varastotila', 'pdx-sync')
			,"TUT"=>__('Tuotantotila', 'pdx-sync')
			,"PUUT"=>__('Puutalo-osake', 'pdx-sync')
			,"LUHT"=>__('Luhtitalo', 'pdx-sync')
			,"hide"=>"# ".__('Piiloita', 'pdx-sync')
		);

		$realEstateType_arr = [
			"OSAKE" => __('Osake', 'pdx-sync')
			, "KIINTEISTO" => __('Kiinteistö', 'pdx-sync')
		];

		$site_arr=array(
			"O"=>__('Oma', 'pdx-sync')
			,"V"=>__('Vuokratontti', 'pdx-sync')
			,"hide"=>"# ".__('Piiloita', 'pdx-sync')
		);

		$Shore_arr = [
			"OR" => __('OR', 'pdx-sync')
			, "OV" => __('OV', 'pdx-sync')
			, "RO" => __('RO', 'pdx-sync')
			, "ER" => __('ER', 'pdx-sync')
			, "ET" => __('ET', 'pdx-sync')
		];

		$moh_arr=array(
			"OM"=>__('Omistusasunto', 'pdx-sync')
			,"OO"=>__('Omistusoikeus', 'pdx-sync')
			,"AO"=>__('Asumisoikeus', 'pdx-sync')
			,"VU"=>__('Vuokra', 'pdx-sync')
			,"UU"=>__('Uudiskohde', 'pdx-sync')
			,"hide"=>"# ".__('Piiloita', 'pdx-sync')
		);

		$moh_rent_type_arr = [
			"MAIN" => __('Päävuokralainen', 'pdx-sync')
			, "SUB" => __('Alivuokralainen', 'pdx-sync')
			,"hide"=>"# ".__('Piiloita', 'pdx-sync')
		];
		
		$int_yesno_arr = array(
			"1" => __('Kyllä', 'pdx-sync')
			,"0" => __('Ei', 'pdx-sync')
		);

		$object_type_arr = [
				self::TYPE_ASSIGNMENT => __('Myyntikohde', 'pdx-sync')
				, self::TYPE_RENT => __('Vuokrakohde', 'pdx-sync')
			];

		$ret = array(
			"Key"=>array("type"=>"text","show"=>__('Kohteen yksilöivä tunniste', 'pdx-sync'), "db_field" => "itemkey", "length" => 30, 'system' => true)
			,"Heading"=>array("type"=>"textarea","show"=>__('Otsikko', 'pdx-sync'))
			,"Description"=>array("type"=>"textarea","show"=>__('Kuvaus', 'pdx-sync'), "length" => 2000)
			,"DescriptionEnglish"=>array("type"=>"textarea","show"=>__('Kohteen sekä taloyhtiön esittely tiedot englanniksi', 'pdx-sync'), "length" => 4000)
			,"property_name"=>array("type"=>"text","show"=>__('Tilan nimi', 'pdx-sync'))
			,"StreetAddress"=>array("type"=>"text","show"=>__('Katuosoite', 'pdx-sync'), "length" => 100, 'system' => true)
			,"PostalCode"=>array("type"=>"text","show"=>__('Postinumero', 'pdx-sync'), "length" => 6, 'system' => true)
			,"OtherPostCode"=>array("type"=>"text","show"=>__('Toinen postinumero', 'pdx-sync'), "length" => 6)
			,"City"=>array("type"=>"text","show"=>__('Kaupunki', 'pdx-sync'), "length" => 50, 'system' => true)
			,"Region"=>array("type"=>"text","show"=>__('Osoitteen tarkenne', 'pdx-sync'), "length" => 100)
			,"Country"=>array("type"=>"text","show"=>__('Maa', 'pdx-sync'), "length" => 50, 'blacklist_values' => ['suomi'])
			,"pdx_address2"=>array("type"=>"text","show"=>__('Toinen osoite', 'pdx-sync'))
			,"pdx_flat_number"=>array("type"=>"text","show"=>__('Huoneiston numero', 'pdx-sync'), 'if_not_same' => 'FlatNumber')
			,"pdx_region"=>array("type"=>"text","show"=>__('Maakunta', 'pdx-sync'))
			,"FlatNumber"=>array("type"=>"text","show"=>__('Huoneiston numero', 'pdx-sync'), "length" => 20)
			,"FloorLocation"=>array("type"=>"text","show"=>__('Kerros', 'pdx-sync'), "length" => 50)
			
			,"LivingArea"=>array("type"=>"int","show"=>__('Asumispinta-ala', 'pdx-sync'), "unit" => "m2", "length" => 12, "decimals" => 2)
			,"TotalArea"=>array("type"=>"int","show"=>__('Kokonaispinta-ala', 'pdx-sync'), "unit" => "m2", "length" => 12, "decimals" => 2)
			,"FloorArea"=>array("type"=>"int","show"=>__('Kerrosala', 'pdx-sync'), "unit" => "m2", "length" => 12, "decimals" => 2)
			,"ResidentialApartmentArea"=>array("type"=>"int","show"=>__('Asuinhuoneisto pinta-ala', 'pdx-sync'), "unit" => "m2", "length" => 12, "decimals" => 2)
			,"AreaAdditional"=>array("type"=>"int","show"=>__('Muu ala', 'pdx-sync'), "length" => 6, "decimals" => 1, 'if_not_same' => 'pdx_area_additional')
			,"AreaInformation"=>array("type"=>"textarea","show"=>__('Lisätietoja pinta-alasta', 'pdx-sync'))
			,"EstateArea"=>array("type"=>"text","show"=>__('Maatila, metsätila tms pinta-ala', 'pdx-sync'), "length" => 500)
			,"ForestAmount"=>array("type"=>"text","show"=>__('Metsän yhteismäärä', 'pdx-sync'), "length" => 500)
			,"LandArea"=>array("type"=>"text","show"=>__('Maan yhteismäärä', 'pdx-sync'), "length" => 500)
			,"pdx_area_additional"=>array("type"=>"int","show"=>__('Muu ala', 'pdx-sync'), "length" => 12, "decimals" => 2, "unit" => "m2")
			,"SiteArea"=>array("type"=>"int","show"=>__('Tontin pinta-ala', 'pdx-sync'), "length" => 12, "unit" => "m2", "decimals" => 2)
			
			,"RoomTypes"=>array("type"=>"text","show"=>__('Huoneistoselitelmä', 'pdx-sync'), "length" => 200)
			,"NumberOfRooms"=>array("type"=>"int","show"=>__('Huoneiden lukumäärä', 'pdx-sync'), "length" => 2)
			,"OtherSpaceDescription"=>array("type"=>"textarea","show"=>__('Tilojen tarkempi kuvaus', 'pdx-sync'), "length" => 2000)
			,"GeneralCondition"=>array("type"=>"text","show"=>__('Yleiskunto', 'pdx-sync'), "length" => 500)
			,"ConditionDescription"=>array("type"=>"text","show"=>__('Kunnon lisätiedot', 'pdx-sync'), "length" => 500)
			,"UnencumberedSalesPrice"=>array("type"=>"int","show"=>__('Velattoman myyntihinnan', 'pdx-sync'), "if_not_same" => "SalesPrice", "length" => 15, "unit" => "EUR", "decimals" => 2)
			,"SalesPrice"=>array("type"=>"int","show"=>__('Myyntihinta', 'pdx-sync'), "length" => 15, "decimals" => 2, "unit" => "EUR")
			,"MaintenanceFee"=>array("type"=>"int","show"=>__('Hoitovastike', 'pdx-sync'), "length" => 15, "unit" => "EUR_kk", "decimals" => 2)
			,"WaterFeeExplanation"=>array("type"=>"text","show"=>__('Vesimaksun suoritustapa', 'pdx-sync'), "length" => 400)
			,"type"=>array("type"=>"select","defined_arr"=>$type_arr,"show"=>__('Rakennuksen tyyppi', 'pdx-sync'), "tag" => "", "attr" => "type")
			, "realEstateType" => ["type"=>"select","defined_arr"=>$realEstateType_arr,"show"=>__('Kohde on', 'pdx-sync'), "tag" => "", "attr" => "realEstateType"]
			,"newHouses"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Uudiskohde', 'pdx-sync'), "tag" => "", "attr" => "newHouses")
			,"newApartmentReserved"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Uudisasunto varattu', 'pdx-sync'), "tag" => "", "attr" => "newApartmentReserved")
			,"TargetHoliday"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Lomakohde', 'pdx-sync'), "attr" => "value")
			,"TargetNew"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Uudiskohde', 'pdx-sync'), "attr" => "value")
			,"HousingCompanyName"=>array("type"=>"text","show"=>__('Taloyhtiön nimi', 'pdx-sync'), "length" => 100)
			,"HousingCompanyKey"=>array("type"=>"text","show"=>__('Taloyhtiön tunnus', 'pdx-sync'), "length" => 30)
			,"HousingCompanyNumber"=>array("type"=>"text","show"=>__('Taloyhtiön työnumero', 'pdx-sync'), "length" => 30)
			,"YearOfBuilding"=>array("type"=>"text","show"=>__('Rakennusvuoden lisätieto', 'pdx-sync'), "length" => 500)
			,"YearOfBuildingOriginal"=>array("type"=>"int","show"=>__('Alkuperäinen rakennusvuosi', 'pdx-sync'), "length" => 4, "tag" => "YearOfBuilding", "attr" => "original")
			,"TimeOfCompletion"=>array("type"=>"date","show"=>__('Valmistumispäivämäärä', 'pdx-sync'))
			,"Estate"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Kiinteistö', 'pdx-sync'), "attr" => "type")
			,"EstateTax"=>array("type"=>"text","show"=>__('Kiinteistövero', 'pdx-sync'), "length" => 30)
			,"NumberOfApartments"=>array("type"=>"int","show"=>__('Huoneistojen lukumäärä', 'pdx-sync'), "length" => 3)
			,"OfficeArea"=>array("type"=>"int","show"=>__('Liikehuoneistopinta-ala', 'pdx-sync'), "length" => 12, "unit" => "m2", "decimals" => 2)
			,"NumberOfOffices"=>array("type"=>"int","show"=>__('Liikehuoneistojen lukumäärä', 'pdx-sync'), "length" => 3)
			,"CommonAreas"=>array("type"=>"text","show"=>__('Yhteiset tilat', 'pdx-sync'), "length" => 200)
			,"Sauna"=>array("type"=>"text","show"=>__('Sauna', 'pdx-sync'), "length" => 50)
			,"OwnSauna"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Oma sauna', 'pdx-sync'), "tag" => "Sauna", "attr" => "own")
			,"SaunaCommon"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Yhteiskäyttöinen sauna', 'pdx-sync'), "tag" => "Sauna", "attr" => "common")
			,"SaunaDescription"=>array("type"=>"textarea","show"=>__('Sauna', 'pdx-sync'))
			,"Lift"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Lisätietoa hissistä', 'pdx-sync'), "length" => 50)
			,"LiftValue"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Hissi', 'pdx-sync'), "tag" => "Lift", "attr" => "value")
			,"BuildingMaterial"=>array("type"=>"text","show"=>__('Rakennusmateriaalit', 'pdx-sync'), "length" => 200)
			,"Foundation"=>array("type"=>"text","show"=>__('Perustus', 'pdx-sync'), "length" => 200)
			,"WallConstruction"=>array("type"=>"text","show"=>__('Seinissä käytetyt rakennusmateriaalit', 'pdx-sync'), "length" => 200)
			,"RoofType"=>array("type"=>"text","show"=>__('Katon tyyppi', 'pdx-sync'), "length" => 200)
			,"RoofMaterial"=>array("type"=>"text","show"=>__('Katon rakennusmateriaalit', 'pdx-sync'), "length" => 200)
			,"AntennaSystem"=>array("type"=>"text","show"=>__('Antennijärjestelmä', 'pdx-sync'), "length" => 50)
			,"Site"=>array("type"=>"select","defined_arr"=>$site_arr,"show"=>__('Tontin tyyppi', 'pdx-sync'), "attr" => "type")
			,"SiteRent"=>array("type"=>"text","show"=>__('Tontin vuokra', 'pdx-sync'), "length" => 50)
			,"Shore"=>array("type"=>"select","defined_arr"=>$Shore_arr,"show"=>__('Ranta', 'pdx-sync'), "attr" => "type")
			, "WaterFrontType" => array("type"=>"text","show"=>__('Rantatyyppi', 'pdx-sync'))
			,"Latitude"=>array("type"=>"text","show"=>__('Leveysaste', 'pdx-sync'), "system" => true)
			,"Longitude"=>array("type"=>"text","show"=>__('Pituusaste', 'pdx-sync'), "system" => true)
			,"X-coordinate"=>array("type"=>"text","show"=>__('X-koordinaatti', 'pdx-sync'), "system" => true)
			,"Y-coordinate"=>array("type"=>"text","show"=>__('Y-koordinaatti', 'pdx-sync'), "system" => true)
			,"pdx_coordinates"=>array("type"=>"text","show"=>__('Kordinaatit', 'pdx-sync'), "system" => true)

			,"ShowingAgentEmail1"=>array("type"=>"text","show"=>__('Esittelijän sähköpostiosoite', 'pdx-sync'), "length" => 255, 'system' => true)
			,"ShowingAgentPhone1"=>array("type"=>"text","show"=>__('Esittelijän puhelinnumero', 'pdx-sync'), "length" => 255, 'system' => true)
			,"ShowingAgentName1"=>array("type"=>"text","show"=>__('Esittelijän nimi', 'pdx-sync'), "length" => 255, 'system' => true)
			,"ShowingAgentImage1"=>array("type"=>"image","show"=>__('Esittelijän kuva', 'pdx-sync'), 'system' => true)

			,"ShowingAgentEmail2"=>array("type"=>"text","show"=>__('Esittelijän sähköpostiosoite', 'pdx-sync'), "length" => 255, 'system' => true)
			,"ShowingAgentPhone2"=>array("type"=>"text","show"=>__('Esittelijän sähköposti', 'pdx-sync'), "length" => 255, 'system' => true)
			,"ShowingAgentName2"=>array("type"=>"text","show"=>__('Esittelijän nimi', 'pdx-sync'), "length" => 255, 'system' => true)
			,"ShowingAgentImage2"=>array("type"=>"image","show"=>__('Esittelijän kuva', 'pdx-sync'), 'system' => true)

			,"PresentationFile"=>array("type"=>"file","show"=>__('Esite', 'pdx-sync'))

			, "DateWhenAvailable" => ["type" => "date", "show" => __('Vapautumispäivämäärä', 'pdx-sync')]
			,"BecomesAvailable"=>array("type"=>"text","show"=>__('Vapautuminen', 'pdx-sync'), "length" => 200)

			// EstateAgentContactPersons not included
			,"EstateAgentTitle"=>array("type"=>"text","show"=>__('Kiinteistövälittäjän yhteyshenkilön titteli', 'pdx-sync'), "length" => 200, 'system' => true)
			,"EstateAgentContactPersonEmail"=>array("type"=>"text","show"=>__('Yhteydenottosähköpostiosoite', 'pdx-sync'), 'system' => true)
			,"EstateAgentContactPerson"=>array("type"=>"text","show"=>__('Välittäjä', 'pdx-sync'), "length" => 50, 'system' => true)
			,"EstateAgentTelephone"=>array("type"=>"text","show"=>__('Kiinteistövälittäjän puhelinnumero', 'pdx-sync'), 'if_not_same' => 'EstateAgentContactPersonTelephone', "length" => 50, 'system' => true)
			,"EstateAgentContactPersonTelephone"=>array("type"=>"text","show"=>__('Välittäjän puhelinnumero', 'pdx-sync'), "length" => 255, 'system' => true)
			,"EstateAgentContactPersonEmail"=>array("type"=>"text","show"=>__('Yhteydenottosähköpostiosoite', 'pdx-sync'), "length" => 50, 'system' => true)
			,"ElectronicBrochureRequestEmail"=>array("type"=>"text","show"=>__('ElectronicBrochureRequestEmail', 'pdx-sync'), "length" => 50, "system" => true)
			,"ElectronicBrochureRequestUrl"=>array("type"=>"text","show"=>__('Sähköisen esitteen tilaus', 'pdx-sync'), "length" => 500, "system" => true)
			,"DescriptionBrochure"=>array("type"=>"text","show"=>__('Esitteen lisätietoja', 'pdx-sync'))
			,"ContactInfoBoxHtml"=>array("type"=>"text","show"=>__('Yhteydenotto-osoite', 'pdx-sync'), "length" => 500)
			,"ContactRequestEmail"=>array("type"=>"text","show"=>__('Yhteydenottosähköpostiosoite', 'pdx-sync'), "length" => 50, "multiple" => true, "system" => true)
			,"EstateAgentPicture"=>array("type"=>"image","show"=>__('Kuva', 'pdx-sync'), "system" => true)
			,"EstateAgentContactPersonPictureUrl"=>array("type"=>"image","show"=>__('Välittäjän kuva', 'pdx-sync'), 'system' => true)

			// Company
			,"CompanyName"=>array("type"=>"text","show"=>__('Toimipisteen nimi', 'pdx-sync'), "length" => 255, "path" => "Company.Name", "system" => true)
			,"CompanyAddress"=>array("type"=>"text","show"=>__('Toimipisteen osoite', 'pdx-sync'), "length" => 255, "path" => "Company.Address", "system" => true)
			,"CompanyZipcode"=>array("type"=>"text","show"=>__('Toimipisteen postinumero', 'pdx-sync'), "length" => 255, "path" => "Company.Zipcode", "system" => true)
			,"CompanyCity"=>array("type"=>"text","show"=>__('Toimipisteen kaupunki', 'pdx-sync'), "length" => 255, "path" => "Company.City", "system" => true)
			,"CompanyPhone"=>array("type"=>"text","show"=>__('Toimipisteen puhelinnumero', 'pdx-sync'), "length" => 255, "path" => "Company.Phone", "system" => true)
			,"CompanyEmail"=>array("type"=>"text","show"=>__('Toimipisteen sähköposti', 'pdx-sync'), "length" => 255, "path" => "Company.Email", "system" => true)

			,"SupplementaryInformation"=>array("type"=>"textarea","show"=>__('Lisätiedot', 'pdx-sync'), "length" => 2000)
			,"KitchenAppliances"=>array("type"=>"textarea","show"=>__('Keittiön varustetaso', 'pdx-sync'), "length" => 2000)
			,"StorageSpace"=>array("type"=>"textarea","show"=>__('Säilytystilat', 'pdx-sync'), "length" => 1000)
			,"BathroomAppliances"=>array("type"=>"textarea","show"=>__('Kylpyhuoneen varustetaso', 'pdx-sync'), "length" => 2000)
			,"Direction"=>array("type"=>"text","show"=>__('Suunta', 'pdx-sync'), "length" => 100)
			,"DirectionOfWindows"=>array("type"=>"text","show"=>__('Ikkunoiden suunta', 'pdx-sync'), "length" => 100)
			,"View"=>array("type"=>"text","show"=>__('Näkymät ikkunoista', 'pdx-sync'), "length" => 100)
			,"BuildingPlanSituation"=>array("type"=>"text","show"=>__('Kaavoitustilanne', 'pdx-sync'), "length" => 100)
			,"Heating"=>array("type"=>"text","show"=>__('Lämmitystekniikka', 'pdx-sync'), "length" => 200)
			,"Balcony"=>array("type"=>"text","show"=>__('Parvekkeen kuvaus', 'pdx-sync'), "length" => 300)
			,"BalconyValue"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('Parveke', 'pdx-sync'), "tag" => "Balcony", "attr" => "value")
			,"BecomesAvailable"=>array("type"=>"text","show"=>__('Vapautuminen', 'pdx-sync'))
			,"with_furniture"=>array("type"=>"text","show"=>__('Vuokrataan kalustettuna', 'pdx-sync'))
			,"BasicRenovations"=>array("type"=>"textarea","show"=>__('Tehdyt peruskorjaukset', 'pdx-sync'), "length" => 500)
			,"Services"=>array("type"=>"textarea","show"=>__('Palvelut', 'pdx-sync'), "length" => 200)
			,"Connections"=>array("type"=>"textarea","show"=>__('Liikenneyhteydet', 'pdx-sync'), "length" => 200)
			,"FinancingFee"=>array("type"=>"int","show"=>__('Rahoitusvastike', 'pdx-sync'), "length" => 15, "unit" => "EUR_kk", "decimals" => 2)
			,"WaterFee"=>array("type"=>"int","show"=>__('Vesimaksu', 'pdx-sync'), "length" => 15, "unit" => "EUR_kk", "decimals" => 2)
			,"UseOfWater"=>array("type"=>"text","show"=>__('Käyttövesi', 'pdx-sync'), "length" => 300)
			,"SewerSystem"=>array("type"=>"text","show"=>__('Viemäri', 'pdx-sync'))
			,"VentilationSystem"=>array("type"=>"text","show"=>__('Ilmastointijärjestelmä', 'pdx-sync')) // missing from specs
			,"Floor"=>array("type"=>"textarea","show"=>__('Pintamateriaalit', 'pdx-sync'), "length" => 400)
			,"FutureRenovations"=>array("type"=>"textarea","show"=>__('Tulevat korjaukset', 'pdx-sync'), "length" => 500)
			,"Disponent"=>array("type"=>"text","show"=>__('Isännöitsijä', 'pdx-sync'), "length" => 100)
			,"DisponentAddress"=>array("type"=>"text","show"=>__('Isännöitsijän osoite', 'pdx-sync'), "length" => 80)
			,"DisponentPhone"=>array("type"=>"text","show"=>__('Isännöitsijän puhelinnumero', 'pdx-sync'), "length" => 80)
			,"RealEstateManagement"=>array("type"=>"text","show"=>__('Kiinteistön hoito', 'pdx-sync'), "length" => 50)
			,"RealEstateID"=>array("type"=>"text","show"=>__('Kiinteistötunnus', 'pdx-sync'), "length" => 30)
			,"HeatingCosts"=>array("type"=>"int","show"=>__('Arvioidut lämmityskustannukset', 'pdx-sync'), "length" => 15, "unit" => "EUR_kk", "decimals" => 2)
			,"ElectricUse"=>array("type"=>"text","show"=>__('Sähkölämmitys kustannukset', 'pdx-sync'), "length" => 80)
			,"OilUse"=>array("type"=>"text","show"=>__('Öljynkulutus kustannukset', 'pdx-sync'), "length" => 80)
			,"Encuberances"=>array("type"=>"text","show"=>__('Rasitteet/oikeudet', 'pdx-sync'), "length" => 255)
			,"Encumbrances"=>array("type"=>"text","show"=>__('Rasitteet', 'pdx-sync'), "length" => 200)
			,"VirtualPresentation"=>array("type"=>"text","show"=>__('Virtuaaliesittely', 'pdx-sync'), "length" => 200)
			,"VideoPresentationURL"=>array("type"=>"text","show"=>__('Videoesittely', 'pdx-sync'), "length" => 300, 'if_not_same' => 'VideoClip')
			,"VideoClip"=>array("type"=>"text","show"=>__('Videoesittely', 'pdx-sync'), "length" => 200)
			,"TargetNewLink"=>array("type"=>"text","show"=>__('Uudiskohdeesittely', 'pdx-sync'), "length" => 200)
			,"MoreInfoUrl"=>array("type"=>"text","show"=>__('Lisätietoja kohteesta', 'pdx-sync'), "length" => 500)
			,"ApplicationUrl"=>array("type"=>"text","show"=>__('Vuokrahakemus tai asumisoikeushakemus', 'pdx-sync'), "length" => 500)
			,"ShowLeadForm"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__('ShowLeadForm', 'pdx-sync'), "system" => true)
			,"OikotieID"=>array("type"=>"text","show"=>__('Oikotie kohdenumero', 'pdx-sync'), "length" => 30)
			,"MagazineIdentifier"=>array("type"=>"text","show"=>__('Lehden tunniste', 'pdx-sync'), "length" => 20)
			
			,"HousingCompanyFee"=>array("type"=>"int","show"=>__('Yhtiövastike', 'pdx-sync'), "length" => 15, "unit" => "EUR_kk", "decimals" => 2)
			,"OtherFees"=>array("type"=>"text","show"=>__('Muut maksut', 'pdx-sync'), "length" => 70)
			,"CleaningFee"=>array("type"=>"int","show"=>__('Puhtaanapito', 'pdx-sync'), "length" => 15, "decimals" => 2, 'unit_from' => 'CleaningFeeUnit', 'if_not' => 'Sanitation')
			,"CleaningFeeUnit"=>array("type"=>"text","show"=>__('Puhtaanapidon yksikkö', 'pdx-sync'), "tag" => "CleaningFee", "attr" => "unit", "system" => true)
			,"Sanitation"=>array("type"=>"text","show"=>__('Puhtaanapito', 'pdx-sync')) // Puhtaanapito
			,"ChargeFee"=>array("type"=>"int","show"=>__('Käyttövastike', 'pdx-sync'), "length" => 15, "unit" => "EUR_KK", "decimals" => 2)
			,"TotalFee"=>array("type"=>"int","show"=>__('Yhteenlasketut hoito ja rahoitus -vastikkeet', 'pdx-sync'), "length" => 9, "decimals" => 2)
			,"SiteRepurchacePrice"=>array("type"=>"int","show"=>__('Lunastettavan vuokratontin lunastusosuus', 'pdx-sync'), "length" => 15, "unit" => "EUR", "decimals" => 2)
			,"SiteCondomiumFee"=>array("type"=>"int","show"=>__('Tontinvuokravastike', 'pdx-sync'), "length" => 11, "decimals" => 2)
			,"ModeOfFinancing"=>array("type"=>"text","show"=>__('Rahoitusmuoto', 'pdx-sync'), "length" => 400)
			,"ShareOfDebt85"=>array("type"=>"int","show"=>__('85 %:n velkaosuus', 'pdx-sync'), "length" => 15, "unit" => "EUR", "decimals" => 2)
			,"ShareOfDebt70"=>array("type"=>"int","show"=>__('70 %:n velkaosuus', 'pdx-sync'), "length" => 15, "unit" => "EUR", "decimals" => 2)
			,"ShareOfLiability"=>array("type"=>"int","show"=>__('Velkaosuus', 'pdx-sync'), "length" => 15, "unit" => "EUR", "decimals" => 2)
			,"PropertyBlockRedemptionPrice"=>array("type"=>"int","show"=>__('Tontin lunastushinta', 'pdx-sync'), "length" => 13, "unit" => "EUR", "decimals" => 2)
			,"RightOfRedemption"=>array("type"=>"text","show"=>__('Lunastuspykälä', 'pdx-sync'))
			,"DebtPart"=>array("type"=>"int","show"=>__('Velkaosuus', 'pdx-sync'), "length" => 13, "decimals" => 2)
			,"HousingLoans"=>array("type"=>"int","show"=>__('Pitkäaikaiset lainat', 'pdx-sync'), "length" => 11, "decimals" => 2)
			,"HousingLoansDate"=>array("type"=>"date","show"=>__('Lainojen eräpäivä', 'pdx-sync'))
			,"HousingPosessions"=>array("type"=>"text","show"=>__('Taloyhtiön omistukset', 'pdx-sync'), "length" => 80)
			,"HousingIncome"=>array("type"=>"text","show"=>__('Taloyhtiön tuotot', 'pdx-sync'), "length" => 30)
			,"SiteCondominiumFee"=>array("type"=>"int","show"=>__('Lunastettavan vuokratontin vastike', 'pdx-sync'), "length" => 15, "decimals" => 2, "unit" => "EUR_KK")
			,"contingency"=>array("type"=>"int","show"=>__('Sopimussakko', 'pdx-sync'), "length" => 10, "decimals" => 2)
			,"Status"=>array("type"=>"text","show"=>__('Toimeksiannon tila', 'pdx-sync'), 'system' => true)

			// Trade element not implemented

/*			// following fields may be used if TradeBid is K
			,"TradeBid"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__("TradeBid", 'pdx-sync'), "attr" => "value", "system" => true)
			,"HighestBid"=>array("type"=>"int","show"=>__("HighestBid", 'pdx-sync'), "length" => 15, "decimals" => 2)

			// following fields may be used if OnlineOffer id K
			,"OnlineOffer"=>array("type"=>"select","defined_arr"=>$noyes_arr,"show"=>__("OnlineOffer", 'pdx-sync'), "system" => true)
			,"OnlineOfferLogo"=>array("type"=>"picture","show"=>__("OnlineOfferLogo", 'pdx-sync'))
			,"OnlineOfferUrl"=>array("type"=>"text","show"=>__("OnlineOfferUrl", 'pdx-sync'))
			,"OnlineOfferHighestBid"=>array("type"=>"int","show"=>__("OnlineOfferHighestBid", 'pdx-sync'), "length" => 15, "decimals" => 2)
			*/
			
			,"rc_energyclass"=>array("type"=>"text","show"=>__('Energiatodistuksen kuvaus', 'pdx-sync'))
			,"rc_energy_flag"=>array("type"=>"text","show"=>__('Energiatodistus', 'pdx-sync'))
			,"rc-energy-flag"=>array("type"=>"text","show"=>__('Energiatodistus', 'pdx-sync'))
			,"rc-energyclass"=>array("type"=>"text","show"=>__('Energiatodistuksen kuvaus', 'pdx-sync'), "length" => 200)
			,"asbestos_mapping"=>array("type"=>"text","show"=>__('Asbestikartoitus tehty', 'pdx-sync'))
			,"property_limitation"=>array("type"=>"text","show"=>__('Käyttö ja luovutusrajoitukset', 'pdx-sync'))
			,"agreement_restrictions"=>array("type"=>"text","show"=>__('Muut rajoitteet', 'pdx-sync'))
			,"redemption_carslot"=>array("type"=>"text","show"=>__('Lunastuspykälä autopaikat', 'pdx-sync'))
			,"pdx_carslotfee"=>array("type"=>"int","show"=>__('Autopaikkamaksu', 'pdx-sync'), "length" => 15, "decimals" => 2)
			,"site_number"=>array("type"=>"text","show"=>__('Tontin numero', 'pdx-sync'))
			,"property_regno"=>array("type"=>"text","show"=>__('Rakennusnro R.No', 'pdx-sync'))
			,"FlatShareNos"=>array("type"=>"text","show"=>__('Osakkeiden numerot', 'pdx-sync'), "length" => 80)
			,"pdx_flat_share_count"=>array("type"=>"int","show"=>__('Osakemäärä', 'pdx-sync'), "length" => 11)
			,"pdx_target_extra"=>array("type"=>"text","show"=>__('Muiden tietojen lisätietoja', 'pdx-sync'), "length" => 200)
			,"pdx_property_extra"=>array("type"=>"text","show"=>__('Kiinteistön lisätietoja', 'pdx-sync'), "length" => 200)
			,"pdx_area_carslot1"=>array("type"=>"int","show"=>__('Lisä Pinta-ala määre 1', 'pdx-sync'), "length" => 5, "decimals" => 1)
			,"pdx_area_carslot2"=>array("type"=>"int","show"=>__('Lisä Pinta-ala määre 2', 'pdx-sync'), "length" => 5, "decimals" => 1)
			,"pdx_area_carslot3"=>array("type"=>"int","show"=>__('Lisä Pinta-ala määre 3', 'pdx-sync'), "length" => 5, "decimals" => 1)
			,"EstateDivision"=>array("type"=>"text","show"=>__('Toimistotilan jakaminen', 'pdx-sync'), "length" => 500)
			,"SpecialTarget"=>array("type"=>"text","show"=>__('Erikoiskohde', 'pdx-sync'))
			
			,"Mortgages"=>array("type"=>"text","show"=>__('Kiinnitykset', 'pdx-sync'), "length" => 100)
			,"MunicipalDevelopment"=>array("type"=>"text","show"=>__('Kunnallistekniikka', 'pdx-sync'), "length" => 200)
			,"Grounds"=>array("type"=>"textarea","show"=>__('Maasto', 'pdx-sync'), "length" => 200)
			,"ModeOfHabitation"=>array("type"=>"select","defined_arr"=>$moh_arr,"show"=>__('Asumis-omistusmuoto', 'pdx-sync'), "attr" => "type")
			,"ModeOfHabitationRentType"=>array("type"=>"select","defined_arr"=>$moh_rent_type_arr,"show"=>__('Vuokralaisen tyyppi', 'pdx-sync'), "tag" => "ModeOfHabitation", "attr" => "rentType")
			,"BuildingRights"=>array("type"=>"textarea","show"=>__('Rakennusoikeudet', 'pdx-sync'), "length" => 200)
			,"BuildingPlanInformation"=>array("type"=>"textarea","show"=>__('Kaavoitustiedot', 'pdx-sync'), "length" => 200)
			,"ParkingSpace"=>array("type"=>"textarea","show"=>__('Pysäköintitilat', 'pdx-sync'), "length" => 200)
			,"HonoringClause"=>array("type"=>"text","show"=>__('Lunastuspykälä', 'pdx-sync'), "length" => 30)
			,"HonouringClause"=>array("type"=>"text","show"=>__('Lunastuspykälä', 'pdx-sync'), "length" => 80)
			,"ContractDate"=>array("type"=>"date","show"=>__('Sopimuksen tekopäivämäärä', 'pdx-sync'), 'system' => true)
			,"DealDate"=>array("type"=>"date","show"=>__('Kauppapäivämäärä', 'pdx-sync'))
			
			// renting details
			,"RentPerMonth"=>array("type"=>"int","show"=>__('Kuukausivuokra', 'pdx-sync'), "length" => 15, "unit" => "EUR_kk", "decimals" => 2)
			,"RentPerDay"=>array("type"=>"int","show"=>__('Päivävuokra', 'pdx-sync'), "length" => 15, "unit" => "EUR_p", "decimals" => 2)
			,"RentPerWeek"=>array("type"=>"int","show"=>__('Viikkovuokra', 'pdx-sync'), "length" => 15, "unit" => "EUR_vko", "decimals" => 2)
			,"RentPerWeekEnd"=>array("type"=>"int","show"=>__('Viikonloppuvuokra', 'pdx-sync'), "length" => 15, "unit" => "EUR_vkonloppu", "decimals" => 2)
			,"RentPerYear"=>array("type"=>"int","show"=>__('Vuosivuokra', 'pdx-sync'), "length" => 15, "unit" => "EUR_v", "decimals" => 2)
			,"LeaseHolder"=>array("type"=>"text","show"=>__('Maanvuokraaja', 'pdx-sync'), "length" => 50)
			,"TermOfLease"=>array("type"=>"textarea","show"=>__('Vuokra-aika', 'pdx-sync'), "length" => 500)
			,"RentSecurityDeposit"=>array("type"=>"text","show"=>__('Vuokratakuu', 'pdx-sync'))
			,"RentingTerms"=>array("type"=>"textarea","show"=>__('Vuokrauksen erityisehdot', 'pdx-sync'), "length" => 4000)
			,"ApartmentRentIncome"=>array("type"=>"int","show"=>__('Vuokra-tulot', 'pdx-sync'), "length" => 12, "unit" => "EUR_KK", "decimals" => 2)
			,"RentComission"=>array("type"=>"int","show"=>__('Välityspalkkio', 'pdx-sync'), "length" => 15, "unit" => "EUR", "decimals" => 2)
			,"RentSecurityDeposit"=>array("type"=>"int","show"=>__('Vuokratakuu', 'pdx-sync'), "length" => 15, "unit" => "EUR", "decimals" => 2)
			,"RentIncrease"=>array("type"=>"text","show"=>__('Vuokran korottaminen', 'pdx-sync'), "length" => 500)
			
			// showing dates
			,"ShowingDate1"=>array("type"=>"date","show"=>__("Näyttöpäivä 1", 'pdx-sync'), 'system' => true)
			,"ShowingStartTime1"=>array("type"=>"text","show"=>__("Näyttöaika 1 alkaa", 'pdx-sync'), "length" => 5, 'system' => true)
			,"ShowingEndTime1"=>array("type"=>"text","show"=>__("Näyttöaika 1 päättyy", 'pdx-sync'), "length" => 5, 'system' => true)
			,"ShowingDateExplanation1"=>array("type"=>"text","show"=>__('Näyttöajan 1 seliteteksti', 'pdx-sync'), "length" => 400, 'system' => true)
			,"ShowingDate2"=>array("type"=>"date","show"=>__("Näyttöpäivä 2", 'pdx-sync'), 'system' => true)
			,"ShowingStartTime2"=>array("type"=>"text","show"=>__("Näyttöaika 2 alkaa", 'pdx-sync'), "length" => 5, 'system' => true)
			,"ShowingEndTime2"=>array("type"=>"text","show"=>__("Näyttöaika 1 päättyy", 'pdx-sync'), "length" => 5, 'system' => true)
			,"ShowingDateExplanation2"=>array("type"=>"text","show"=>__('Näyttöajan 2 seliteteksti', 'pdx-sync'), "length" => 400, 'system' => true)
			,"DrivingInstructions"=>array("type"=>"text","show"=>__('Ajo-ohjeet', 'pdx-sync'), "length" => 200)
			
			,"pdx_object"=>array("type"=>"select","defined_arr"=>$object_type_arr,"show"=>__('PDX:n objektin nimi', 'pdx-sync'), "system" => true)
			,"sorting"=>array("type"=>"int","show"=>__('Huoneistojen järjestys', 'pdx-sync'), "length" => 11, "system" => true)
			,"ModifiedDate"=>array("type"=>"date","show"=>__('Muokkauspäivämäärä', 'pdx-sync'), 'system' => true)
			,"added"=>array("type"=>"datetime","show"=>__('Lisätty', 'pdx-sync'))
			,"pictures"=>array("type"=>"images", "show"=>__('Kuvat', 'pdx-sync')
				, "field_prefix" => "Picture", "num_fields" => 30, "system" => true)
		);

		return $ret;
	}
}
