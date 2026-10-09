<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/views/View.php
 *
 * Essential base methods and properties needed in every view.
 * Child classes will inherit those definitions so we don't have to
 * declare them separately in each view.
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
 * 				Outi Lehtinen ja Jari Vihtari, 2019
*/

abstract class View {

	// Array for temporary data storage.
	// All data to be shown to the user goes here.
	public $_data;

	// Class constructor.
	// Will be executed when a class is initialized.
	public function __construct() {
		
		// Initialize an array to store temporary data.
		$this->_data = array();
		
	}


	// Function to insert data for all temporary storage.
	// Contents is extracted before usage on templates.
	public function set($name, $value, $strip = true) {

		// Check wheter $value is string and $strip is true.
		if (is_string($value) and $strip) {
			
			// If so, strip html characters from the value.
			$this->_data[$name] = htmlspecialchars($value, ENT_NOQUOTES | ENT_XHTML, "UTF-8");
			
		} else {
			
			// If not, save data as it's.
			$this->_data[$name] = $value;
			
		}

		// Return the inserted string.
		return $this->_data[$name];
		
	}
	
	
	public function get($name, $decode = false) {
		
		// Check wheter is there any data with given name.
		if ($this->_data[$name]) {
			
			// Check wheter $value is string and $decode is false.
			if (is_string($this->_data[$name]) and $decode) {
				
				// Return decoded string.
				return htmlspecialchars_decode($this->_data[$name]);
				
			} else {
			
				// Return plain string.
				return $this->_data[$name];
			}
			
		} else {
			
			// No data found, return false.
			return false;
			
		}
		
	}


	public function header($header = "Header") {
		global $cfg;

		// Shorten directory separator.
		$ds = $cfg['KS']['DIR_SEP'];

		// Absolute path to header file.
		$header_file = $cfg['KS']['BASE_PATH'] . $ds . "pub" . $ds . $header . ".php";

		// If some data has been set, extract them for usage on template.
		if (!is_null($this->_data)) {
			
			// Extract variables from the array.
			extract($this->_data);
			
		}

		// Get initial page title.
		$title = $this->getPageTitle();

		// Check wheter header file exists.
		if (file_exists($header_file)) {
			
			// Include header file.
			include_once $header_file;
			
		} else {
			
			// Throw SysException.
			throw new SysException(_("Requested header template could not found."));
			
		}

		return $this;

	}

	public function navi($navi = "Navi") {
		global $cfg;

		// Shorten directory separator.
		$ds = $cfg['KS']['DIR_SEP'];

		// Absolute path to header file.
		$navi_file = $cfg['KS']['BASE_PATH'] . $ds . "pub" . $ds . $navi . ".php";

		// If some data has been set, extract them for usage on template.
		if (!is_null($this->_data)) {
			
			// Extract variables from the array.
			extract($this->_data);
			
		}

		// Get initial page title.
		$title = $this->getPageTitle();

		// Check wheter header file exists.
		if (file_exists($navi_file)) {
			
			// Include header file.
			include_once $navi_file;
			
		} else {
			
			// Throw SysException.
			throw new SysException(_("Requested header template could not found."));
			
		}

		return $this;

	}

	
	public function popup($popup = "Popup") {
		global $cfg;

		// Shorten directory separator.
		$ds = $cfg['KS']['DIR_SEP'];

		// Absolute path to header file.
		$popup_file = $cfg['KS']['BASE_PATH'] . $ds . "pub" . $ds . $popup . ".php";

		// If some data has been set, extract them for usage on template.
		if (!is_null($this->_data)) {
			
			// Extract variables from the array.
			extract($this->_data);
			
		}

		// Get initial page title.
		$title = $this->getPageTitle();

		// Check wheter header file exists.
		if (file_exists($popup_file)) {
			
			// Include header file.
			include_once $popup_file;
			
		} else {
			
			// Throw SysException.
			throw new SysException(_("Requested popup template could not found."));
			
		}

		return $this;

	}
	
	public function footer($footer = "Footer") {
		global $cfg;
		
		// Get last update from config.
		// To be appended after a initial copyright.
		$updated = $cfg['KS']['LAST_UPDATE'];

		// Shorten directory separator.
		$ds = $cfg['KS']['DIR_SEP'];

		// Absolute path to footer file.
		$footer_file = $cfg['KS']['BASE_PATH'] . $ds . "pub" . $ds . $footer . ".php";

		// If some data has been set, extract them for usage on template.
		if (!is_null($this->_data)) {
			
			// Extract variables from the array.
			extract($this->_data);
			
		}

		// Check wheter footer file exists.
		if (file_exists($footer_file)) {
			
			// Include footer file.
			include_once $footer_file;
			
		} else {
			
			// Throw SysException.
			throw new SysException(_("Requested footer template could not found."));
			
		}

		return $this;

	}

	/*
		Hakusana- tai hakutulosluettelo vasempaan sivupalkkiin / 16.7.2019 OL 
		LeftBar = hakusanalista
		SearchLeftBar = pikahaun hakutuloslista
		AdvSearchLeftBar = tarkennetun haun tuloslista
	*/
	public function leftbar($bar = "LeftBar") {	
		global $cfg;

		//$bar = "LeftBar";
		
		// Shorten directory separator.
		$ds = $cfg['KS']['DIR_SEP'];

		// Absolute path to bar file.
		$bar_file = $cfg['KS']['BASE_PATH'] . $ds . "pub" . $ds . $bar . ".php";

		// If some data has been set, extract them for usage on template.
		if (!is_null($this->_data)) {
			
			// Extract variables from the array.
			extract($this->_data);
			
		}

		// Check wheter left bar exists.
		if (file_exists($bar_file)) {
			
			// Include left bar.
			include_once $bar_file;
			
		} else {
			
			// Throw SysException.
			throw new SysException(_("Requested left bar template could not found."));
			
		}

		return $this;

	}


	public function content($content) {
		global $cfg;

		// Shorten directory separator.
		$ds = $cfg['KS']['DIR_SEP'];

		// Make a path to requested template.
		$content_path = $cfg['KS']['BASE_PATH'] . $ds . "app"
						. $ds . "pages" . $ds . $content . ".php";

		// If some data has been set, extract them for usage on template.
		if (!is_null($this->_data)) {
			
			// Extract variables from the array.
			extract($this->_data);
			
		}

		// Check wheter content file exists.
		if (file_exists($content_path)) {
			
			// Include content file.
			include_once $content_path;
			
		} else {
			
			// Throw SysException.
			throw new SysException(_("Requested content template could not found."));
			
		}

		return $this;

	}


	public function getPageTitle() {
		
		global $cfg;

		// Grab a major title from config.
		$title = dgettext($_SESSION['KS_lang'], "DICT_Title");

		// Check wheter a page related title is set.
		if ($this->get("title", true)) {
			
			// When found, append it after the initial title.
			//$title .= " - " . $this->get("title", true);
			// Vaihdetaan järjestys / 11.6.2020 OL
			$title = $this->get("title", true) . " - " . $title;



		}
		
		return $title;
		
	}
	

	// UUSI FUNKTIO / 18.7.2019 OL
	// Tuottaa pikahaun tuloksista vasemmassa sivupalkissa käytetyn datalist-muodon
	public function datalist($name) {

		// Get the data
		$data = $this->get($name, true);
		
		// Format as datalist
		if ($data) {
			$rivit = array();
			foreach ($data as $row) {
				$rivit[] = '{"id":"' . $row['id'] . '", "ks_id":"' . $row['ks_id'] . '", 
				"tyyppi":"' . $row['tyyppi'] . '", "hakusana":"' . $row['hakusana'] . '", 
				"homonyyminro":"' . $row['homonyyminro'] . '"}';
			}
			$datalist = '{"record":[' . "\n" . implode(",\n", $rivit) . ']}';
		} else {
			// Throw DICTException.
			throw new DICTException(false, _("Variable could not found."));
		}

		// Echo unformatted saved text straight to the browser.
		header('Content-Type: application/json;charset=utf-8');
		echo $datalist;
		
		return $this;
	}

	// UUSI FUNKTIO / 31.1.2020 OL
	// Tuottaa eXistin hakutuloksesta vasemmassa sivupalkissa käytetyn datalist-muodon
	//
	// <exist:result><exist:value>{"xxx:"yyy"}{"nnn": "yyy"}</exist:value></exist:result>
	// => {"record":[{"xxx:"yyy"},{"nnn": "yyy"}]}"
	//
	public function existxml2datalist($name) {
		
		$data = $this->get($name, true);

		if ($data) {

			$datalist = "{\"record\":[";

			// käsitellään XML-muotoinen hakutulos
			$xmlobj = new SimpleXMLElement($data);
			$rescount = count($xmlobj->xpath('//exist:value'));

			$i = 0;
			foreach($xmlobj->xpath('//exist:value') as $name => $value) {

				$datalist .= $value;
				if ($i < $rescount-1) { $datalist .= ", "; }
				$i++;

			}

			$datalist .= "]}";

		} else {

			// Throw DICTException.
			throw new DICTException(false, _("Cannot encode to JSON: variable name could not found."));

		}
		// Echo unformatted saved text straight to the browser.
		header('Content-Type: application/json;charset=utf-8');
		echo $datalist;
		
		return $this;

	}


	/* TARVITAANKO? */

	public function html($name) {
		
		// Echo unformatted saved text straight to the browser.
		echo $this->get($name, true);
		
		return $this;
	}
	
	public function json($name) {

		// Get the data.
		$data = $this->get($name, true);

		if ($data) {
			
			// Apply recursive function to each of the array items.
			array_walk_recursive($data,
				function(&$key, $value) {
					if (is_string($key)) $key = htmlentities($key);
			});
			
			// Encode data to JSON format.
			$json = json_encode($data);
			
			// Echo and decode HTML entities.
			echo html_entity_decode($json);

		} else {

			// Throw DICTException.
			throw new DICTException(false, _("Cannot encode to JSON: variable name could not found."));

		}

	}

	public function xml2json($name) {

		// Get the data.
		$data = $this->get($name, true);

		$json = "{\"record\":[ ";

		if ($data) {

			$rescount = count($data);
			$json = "{\"record\":[ ";
			
			// Apply recursive function to each of the array items.
			//array_walk_recursive($data,
			//	function(&$key, $value) {
			//		if (is_string($key)) $key = htmlentities($key);
			//});
			for($i = 0; $i < $rescount; $i++) {		
				$row = json_encode(simplexml_load_string($data[$i]));
				$json .= $row;
				if ($i < $rescount-1) { $json .= ", "; }
			}
	
			$json .= "]}";

			return $json;
			// Echo and decode HTML entities.
			//echo html_entity_decode($json);

		} else {

			// Throw DICTException.
			throw new DICTException(false, _("Cannot encode to JSON: variable name could not found."));

		}

	}
	
	public function xml($name) {
		
		// Get the data.
		$data = $this->get($name, true);
		
		if ($data) {
			
			// Convert data to XML format.
			$xml = Array2XML::toXML($data);
			
			// Send XML headers and output it.
			header("Content-Type: text/xml");
			echo $xml;
			
		} else {
			
			// Throw DICTException.
			throw new DICTException(false, _("Cannot encode to XML: variable could not found."));
			
		}
		
	}


}

?>
