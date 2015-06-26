<?php
global $Parser_xml_name;
global $Parser_xml_path;
global $Parser_xml_depth;
global $Parser_xml_length;
global $Parser_xml_current_record;
global $Parser_xml_current_key;
global $Parser_xml_record_path;
global $Parser_xml_record_path_len;
global $Parser_xml_done;
global $Parser_xml_analysis_last_path;
global $Parser_xml_analysis_path;
global $Parser_xml_analysis_depth;
global $Parser_xml_analysis_length;
global $Parser_xml_analysis_records;
global $Parser_valid_format;
global $Parser_record_handler;
global $Parser_format_data;
global $Parser_error_message;
global $Parser_filename;
global $Parser_stringPtr;
global $Parser_stringOfs;
global $Parser_stringDat;
global $Parser_stringLen;

function Parser_xml_startTag($parser, $name, $attribs) {
	global $Parser_xml_name;
	global $Parser_xml_path;
	global $Parser_xml_depth;
	global $Parser_xml_length;
	global $Parser_xml_current_record;
	global $Parser_xml_current_key;
	global $Parser_xml_record_path;
	global $Parser_xml_record_path_len;
	$Parser_xml_name = $name;
	$Parser_xml_depth++;
	$Parser_xml_length[$Parser_xml_depth] = strlen($Parser_xml_path);
	$Parser_xml_path .= $name . "/";
	if($Parser_xml_path == $Parser_xml_record_path) {
		$Parser_xml_current_record = array();
		$Parser_xml_current_key    = $Parser_xml_name;
	} else {
		$Parser_xml_current_key = substr($Parser_xml_path, $Parser_xml_record_path_len, -1);
	}
	if($Parser_xml_current_key) {
		$key = $Parser_xml_current_key;
		$ofs = 0;
		while(isset($Parser_xml_current_record[$Parser_xml_current_key])) {
			$ofs++;
			$Parser_xml_current_key = $key . "@" . $ofs;
		}
		$Parser_xml_current_record[$Parser_xml_current_key] = "";
		if(is_array($attribs)) {
			foreach($attribs as $attrib => $value) {
				$Parser_xml_current_record[$Parser_xml_current_key . "-" . $attrib] = $value;
			}
		}
	}
}
function Parser_xml_cdata($parser, $cdata) {
	global $Parser_xml_name;
	global $Parser_xml_path;
	global $Parser_xml_current_record;
	global $Parser_xml_current_key;
	global $Parser_xml_record_path;
	global $Parser_xml_record_path_len;
	if(is_array($Parser_xml_current_record)) {
		if($Parser_xml_current_key) {
			$Parser_xml_current_record[$Parser_xml_current_key] .= $cdata;
		}
	}
}
function Parser_xml_endTag($parser, $name) {
	global $Parser_xml_path;
	global $Parser_xml_depth;
	global $Parser_xml_length;
	global $Parser_xml_current_record;
	global $Parser_xml_current_key;
	global $Parser_xml_record_path;
	global $Parser_xml_done;
	global $Parser_record_handler;
	if(($Parser_xml_path == $Parser_xml_record_path) && !$Parser_xml_done) {
		$Parser_xml_done = call_user_func($Parser_record_handler,$Parser_xml_current_record);
		unset($Parser_xml_current_record);
	}
	$Parser_xml_current_key = "";
	$Parser_xml_path        = substr($Parser_xml_path, 0, $Parser_xml_length[$Parser_xml_depth]);
	$Parser_xml_depth--;
}
function Parser_xml_parse() {
	global $Parser_format_data;
	global $Parser_error_message;
	global $Parser_filename;
	global $Parser_xml_path;
	global $Parser_xml_depth;
	global $Parser_xml_length;
	global $Parser_xml_current_record;
	global $Parser_xml_current_key;
	global $Parser_xml_record_path;
	global $Parser_xml_record_path_len;
	global $Parser_xml_done;
	$parser = @xml_parser_create();
	if(!$parser) {
		$Parser_error_message = "call to xml_parser_create() failed";
		return false;
	}
	xml_set_element_handler($parser, "Parser_xml_startTag", "Parser_xml_endTag");
	xml_set_character_data_handler($parser, "Parser_xml_cdata");
	$Parser_xml_path            = "";
	$Parser_xml_depth           = 0;
	$Parser_xml_length          = array();
	$Parser_xml_current_record  = array();
	$Parser_xml_current_key     = "";
	$Parser_xml_record_path     = $Parser_format_data[1];
	$Parser_xml_record_path_len = strlen($Parser_xml_record_path);
	$Parser_xml_done            = 0;
	$fp                              = Parser_fopen($Parser_filename, "r");
	if(!$fp) {
		$Parser_error_message = "could not open " . $Parser_filename;
		return false;
	}
	$first = true;
	while(!Parser_feof($fp) && !$Parser_xml_done) {
		$xml = Parser_fread($fp, 2048);
		if($first) {
			$xml   = ltrim($xml);
			$first = false;
		}
		xml_parse($parser, $xml, false);
	}
	if(Parser_feof($fp)) {
		xml_parse($parser, "", true);
	}
	Parser_fclose($fp);
	return true;
}

function Parser_xml_analysis_startTag($parser, $name, $attribs) {
	global $Parser_xml_analysis_last_path;
	global $Parser_xml_analysis_path;
	global $Parser_xml_analysis_depth;
	global $Parser_xml_analysis_length;
	global $Parser_xml_analysis_records;
	$Parser_xml_analysis_depth++;
	$Parser_xml_analysis_length[$Parser_xml_analysis_depth] = strlen($Parser_xml_analysis_path);
	$Parser_xml_analysis_path .= $name . "/";
	if(!isset($Parser_xml_analysis_records[$Parser_xml_analysis_path])) {
		$Parser_xml_analysis_records[$Parser_xml_analysis_path]["count"] = 1;
	}
	if($Parser_xml_analysis_path == $Parser_xml_analysis_last_path) {
		$Parser_xml_analysis_records[$Parser_xml_analysis_path]["count"]++;
	}
}
function Parser_xml_analysis_cdata($parser, $name) {
}
function Parser_xml_analysis_endTag($parser, $name) {
	global $Parser_xml_analysis_last_path;
	global $Parser_xml_analysis_path;
	global $Parser_xml_analysis_depth;
	global $Parser_xml_analysis_length;
	$Parser_xml_analysis_last_path = $Parser_xml_analysis_path;
	$Parser_xml_analysis_path      = substr($Parser_xml_analysis_path, 0, $Parser_xml_analysis_length[$Parser_xml_analysis_depth]);
	$Parser_xml_analysis_depth--;
}
function Parser_xml_analysis() {
	global $Parser_xml_analysis_last_path;
	global $Parser_xml_analysis_path;
	global $Parser_xml_analysis_depth;
	global $Parser_xml_analysis_length;
	global $Parser_xml_analysis_records;
	global $Parser_error_message;
	global $Parser_filename;
	$Parser_xml_analysis_last_path = "";
	$Parser_xml_analysis_path      = "";
	$Parser_xml_analysis_depth     = 0;
	$Parser_xml_analysis_length    = array();
	$Parser_xml_analysis_records   = array();
	$parser                             = @xml_parser_create();
	if(!$parser) {
		$Parser_error_message = "call to xml_parser_create() failed";
		return false;
	}
	xml_set_element_handler($parser, "Parser_xml_analysis_startTag", "Parser_xml_analysis_endTag");
	xml_set_character_data_handler($parser, "Parser_xml_analysis_cdata");
	$fp = Parser_fopen($Parser_filename, "r");
	if(!$fp) {
		$Parser_error_message = "could not open " . $Parser_filename;
		return false;
	}
	$first = true;
	while(!Parser_feof($fp)) {
		$xml = Parser_fread($fp, 2048);
		if($first) {
			$xml   = ltrim($xml);
			$first = false;
		}
		xml_parse($parser, $xml, false);
	}
	if(Parser_feof($fp)) {
		xml_parse($parser, "", true);
	}
	Parser_fclose($fp);
	$ignore[]                = "ARG";
	$ignore[]                = "CATEGORIES";
	$ignore[]                = "CATEGORY";
	$ignore[]                = "CONTENT";
	$ignore[]                = "DC:SUBJECT";
	$ignore[]                = "FIELD";
	$ignore[]                = "FIELDS";
	$ignore[]                = "OPTIONVALUE";
	$ignore[]                = "PAYMETHOD";
	$ignore[]                = "PRODUCTITEMDETAIL";
	$ignore[]                = "PRODUCTREF";
	$ignore[]                = "SHIPMETHOD";
	$ignore[]                = "TDCATEGORIES";
	$ignore[]                = "TDCATEGORY";
	$repeating_element_count = 0;
	foreach($Parser_xml_analysis_records as $xpath => $data) {
		if($data["count"] > $repeating_element_count) {
			$ok_to_use = TRUE;
			foreach($ignore as $v) {
				if(strpos($xpath, $v) !== FALSE) {
					$ok_to_use = FALSE;
				}
			}
			if($ok_to_use) {
				$repeating_element_xpath = $xpath;
				$repeating_element_count = $data["count"];
			}
		}
	}
	return $repeating_element_xpath;
}


function Parser_start($filename, $callback, $xml_format = "") {
	global $Parser_record_count;
	global $Parser_record_handler;
	global $Parser_format_data;
	global $Parser_error_message;
	global $Parser_filename;
	$Parser_error_message = "";
	if(!$xml_format) {
		$xml_format = Parser_getFormat($filename);
	} else {
		$xml_format = strtoupper($xml_format);
	}
	if(!$xml_format) {
		return false;
	}
	$Parser_format_data    = explode("|", $xml_format);
	$Parser_filename       = $filename;
	$Parser_record_handler = $callback;
	$Parser_record_count   = 0;
	$parse_function             = "Parser_" . strtolower($Parser_format_data[0]) . "_parse";
	if(!function_exists($parse_function)) {
		$Parser_error_message = "invalid format string";
		return false;
	}
	$parse_function();
	if($Parser_error_message) {
		return false;
	} else {
		return true;
	}
}
function Parser_getFormat($filename) {
	global $Parser_error_message;
	global $Parser_filename;
	$Parser_filename = $filename;
	$fp                   = Parser_fopen($Parser_filename, "r");
	if(!$fp) {
		$Parser_error_message = "could not open " . $Parser_filename;
		return false;
	}
	$data             = "";
	$format_base_type = "";
	do {
		$data .= Parser_fread($fp, 64);
		$nlpos  = strpos($data, "\n");
		$length = strlen($data);
	} while(($length < 1024) && !$nlpos && !Parser_feof($fp));
	Parser_fclose($fp);
	if($nlpos) {
		$data = substr($data, 0, $nlpos);
	}
	$data = ltrim($data);
	if(!$format_base_type) {
		if($data[0] == "<") {
			$format_base_type = "xml";
		}
	}
	if(!$format_base_type) {
		if(strpos($data, "?xml")) {
			$format_base_type = "xml";
		}
	}
	if(!$format_base_type) {
		$format_base_type = "csv";
	}
	$analysis_function = "Parser_" . $format_base_type . "_analysis";
	if(function_exists($analysis_function)) {
		$format_parameters = $analysis_function();
	}
	if(!$format_parameters) {
		$Parser_error_message = "autodetect failed";
		return false;
	} else {
		return $format_base_type . "|" . $format_parameters;
	}
}
function Parser_getErrorMessage() {
	global $Parser_error_message;
	return $Parser_error_message;
}
function Parser_createFile($data) {
	global $Parser_error_message;
	$filename = tempnam("", "");
	$fp       = @fopen($filename, "w");
	if(!$fp) {
		$Parser_error_message = "could not create temporary file";
		return "";
	}
	fwrite($fp, $data);
	fclose($fp);
	return $filename;
}

function Parser_fopen(&$filename, $mode) {
	global $Parser_stringPtr;
	global $Parser_stringOfs;
	global $Parser_stringDat;
	global $Parser_stringLen;
	if(substr($filename, 0, 9) == "string://") {
		if(!isset($Parser_stringPtr)) {
			$Parser_stringPtr = 0;
			$Parser_stringOfs = array();
			$Parser_stringDat = array();
			$Parser_stringLen = array();
		}
		$Parser_stringPtr--;
		$Parser_stringOfs[$Parser_stringPtr] = 0;
		$Parser_stringDat[$Parser_stringPtr] = substr($filename, 9);
		$Parser_stringLen[$Parser_stringPtr] = strlen($Parser_stringDat[$Parser_stringPtr]);
		return $Parser_stringPtr;
	} else {
		return @fopen($filename, $mode);
	}
}
function Parser_fread($fp, $length) {
	global $Parser_stringOfs;
	global $Parser_stringDat;
	if($fp < 0) {
		$dat = substr($Parser_stringDat[$fp], $Parser_stringOfs[$fp], $length);
		$Parser_stringOfs[$fp] += $length;
		return $dat;
	} else {
		return @fread($fp, $length);
	}
}
function Parser_fgets($fp, $length) {
	global $Parser_stringOfs;
	global $Parser_stringDat;
	if($fp < 0) {
		$dat = "";
		do {
			$chr = substr($Parser_stringDat[$fp], $Parser_stringOfs[$fp], 1);
			$Parser_stringOfs[$fp]++;
			$dat .= $chr;
		} while(($chr <> "\n") && ($length--));
		return $dat;
	} else {
		return @fgets($fp, $length);
	}
}
function Parser_fgetc($fp) {
	global $Parser_stringOfs;
	global $Parser_stringDat;
	if($fp < 0) {
		$dat = substr($Parser_stringDat[$fp], $Parser_stringOfs[$fp], 1);
		$Parser_stringOfs[$fp]++;
		return $dat;
	} else {
		return @fgetc($fp);
	}
}
function Parser_feof($fp) {
	global $Parser_stringOfs;
	global $Parser_stringLen;
	if($fp < 0) {
		return ($Parser_stringOfs[$fp] > $Parser_stringLen[$fp]);
	} else {
		return @feof($fp);
	}
}
function Parser_fclose($fp) {
	if($fp < 0) {
		return;
	} else {
		return @fclose($fp);
	}
}
?>