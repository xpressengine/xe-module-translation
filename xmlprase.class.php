<?php
class XMLContext {

	private $_xmlContext;
	private $_phraseXml;
	private $_fileType;
	private $_xml;
	private $_rootName;

	function __construct($file){
		if(!file_exists($file)){
			//throw new Exception ("file don't exist!");
			return;
		}
		if(!$this->_setRootName($file)){
			//throw new Exception ("xml file error!");
			return;
		}
		$this->_xml = simplexml_load_file($file);
		$this->_fileType = $this->_getFileType();
		$this->_xmlContext = $this->_praseFile($this->_xml, $this->_rootName);
var_dump($this->_xmlContext);
	}

	private function _setRootName($file){
		$xmlStr = file_get_contents($file);
		if(!(preg_match('/\?>\s*<([^>]*)\s*.*>/', $xmlStr, $match))){
			return false;
		}
		$this->_rootName = $match[1];
		return true;
	}

	public function getContext(){
		return $this->_xmlContext;
	}

	private function _getNodeInfo($xmlObj,$rootName,&$xpath){
		if($xmlObj instanceof SimpleXMLElement){
			$data['attr'] = $xmlObj->attributes;
			$data['value'] = $xmlObj;
			foreach($xmlObj->attributes as $key => $value){
				if(preg_match("/xml:lang=\"([^\"].+)\"/i")){
					$data['isTransLine'] = true;
				}
			}
		}
		else{
			$data['attr'] = array();
			$data['value'] = $xmlObj;
		}
		$xpath = !empty($xpath) ? $xpath.'_'.$rootName:$rootName;
		$data['xpath'] = $xpath;

		return $data;
	}

	/*
	*	prase the xml file to the construct
	*
	*/
	private function _praseFile($xmlObj,$rootName){
		static $xpath;
		$data = array();
		if($xmlObj instanceof SimpleXMLElement){

			$id = count($data);
			$data[$id] = $this->_getNodeInfo($xmlObj, $rootName, $xpath);


			$nodeArr = get_object_vars($xmlObj);
			foreach($nodeArr as $nodeName => $subNode){
				if($nodeName == '@attributes'){
					continue;
				}
				$data = array_merge($data,$this->_praseFile($subNode, $nodeName));
				$xpathArr = explode('_',$xpath);
				array_pop($xpathArr);
				$xpath = implode('_',$xpathArr);
			}
			return $data;
		}
		if(is_array($xmlObj)){
			foreach($xmlObj as $index => $value){
				$id = count($data);
				$data[$id] = $this->_getNodeInfo($xmlObj, $index, $xpath);
				$data[$id]['value'] = $xmlObj;
				$data = array_merge($data, $this->_praseFile($value, $rootName.'_'.$index));
				$xpathArr = explode('_',$xpath);
				array_pop($xpathArr);
				array_pop($xpathArr);
				$xpath = implode('_',$xpathArr);
			}
			return $data;
		}
		return $data;
	}
	private function _startTag($tagInfoArr){
		$nodeName = array_pop(explode('_', $tagInfoArr['xpath']));
		$output = sprintf('<%s',$nodeName);
		foreach($tagInfoArr['attr'] as $avalue){
			$output .= sprintf(' %s ' , $avalue);
		}
		$output .= sprintf('" >');
		return $output;
	}

	private function _fillContent($content){
		$output = sprintf("%s",$content);
		return $output;
	}

	private function _endTag($tagName){
		$output = sprintf('</%s>',$tagName);
		return $output;
	}

	public function getXmlFile($valueArray){
		$context = $this->_xmlContext;
		$nodeNameLine = array();
		$output = '<?xml version="1.0" encoding="UTF-8"?>';
		foreach($context as $key => $parseArr){
			$nodeName = array_pop(explode('_', $parseArr['xpath']));
			$nodeNameLine = array_push($nodeNameLine , $nodeName);
			$output .= $this->_startTag($parseArr);
			$output .= $this->_fillContent($parseArr['content']);
			while(count(explode('_',$lastPath)) >= count(explode('_',$parseArr['xpath']))){
				$output .= $this->_endTag(array_pop($nodeNameLine));
				$lastPath = $parseArr['xpath'];
			}
			if(isset($parseArr['isTransLine'])){
				foreach($valueArray as $vkey => $vArr){
					if($vArr['content_node'] == $parseArr['attr']){
						$output .= $this->_startTag($parseArr);
						$output .= $this->_fillContent($vArr['content']);
						$output .= $this->_endTag($nodeName);
					}
				}
			}

		}
		return $output;
	}

	/*
	*	get Node unique name by type
	*
	*/
	public function getTransNodeName($type){
	}

	/*
	*	get file type by path of file
	*
	*/
	private function _getFileType(){

		return $this->_fileType;
	}

	private function _nodeIterator($object){
	   $return = NULL;

	   if(is_array($object))
	   {
	       foreach($object as $key => $value)
	           $return[$key] = object2array($value);
	   }
	   else
	   {
	       $var = get_object_vars($object);

	       if($var)
	       {
	           foreach($var as $key => $value)
	               $return[$key] = object2array($value);
	       }
	       else
	           return $object;
	   }

	   return $return;
	}

}