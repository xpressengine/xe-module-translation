<?php
class XMLContext {

	private $_xmlContext;
	private $_phraseXml;
	private $_fileType;
	private $_xml;
	private $_rootName;
	private $_defaultLang;
	private $_file;

	function __construct($file , $defaultLang){
		if(!file_exists($file)){
			//throw new Exception ("file don't exist!");
			return;
		}
		if(!$this->_setRootName($file)){
			//throw new Exception ("xml file error!");
			return;
		}
		$this->_file = file_get_contents($file);
		$this->_file = preg_replace('/xml:lang/i','xml_lang',$this->_file);

		$this->_defaultLang = $defaultLang;
		$this->_xml = simplexml_load_string($this->_file);
		$this->_fileType = $this->_getFileType();
		$this->_xmlContext = $this->_praseFile($this->_xml, $this->_rootName);
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

	private function _hasSubNode($xmlObj){
		if(!$xmlObj instanceof SimpleXMLElement){
			return false;
		}
		$hasChildren = false;
		foreach($xmlObj->children() as $key => $value){
			$hasChildren = true;
			break;
		}
		return $hasChildren;
	}

	private function _storageNode($xmlObj, $xpath, $isLangLine){
		$data['attr'] = $xmlObj->attributes();
		$data['xpath'] = $xpath;
		if($isLangLine){
			$data['isLangLine'] = true;
			$data['value'] = strval($xmlObj);
			return $data;
		}
		$arr = explode('_',$xpath);
		$nodeName = array_pop($arr);
		$hasChildren = $this->_hasSubNode($xmlObj);

		if(!$hasChildren){
			$data['value'] = strval($xmlObj);
		}else{
			$data['value'] = $xmlObj;
		}
		return $data;
	}

	/*
	*	prase the xml file to find  translation lang line info
	*
	*/
	private function _praseFile($xmlObj, $rootName){
		static $xpath;
		$data = array();
		$xpath = !empty($xpath) ? $xpath.'_'.$rootName:$rootName;
		$isLangLine = false;
		foreach($xmlObj->attributes() as $atrrName => $atrrValue){
			if($atrrName == 'xml_lang' && strval($atrrValue) == $this->_defaultLang){
				$isLangLine = true;
			}
		}
		array_push($data, $this->_storageNode($xmlObj, $xpath, $isLangLine));

		foreach($xmlObj as $nodeName => $subNode){
			$data = array_merge($data, $this->_praseFile($subNode, $nodeName));
		}
		$xpathArr = explode('_', $xpath);
		array_pop($xpathArr);
		$xpath = implode('_',$xpathArr);
		return $data;
	}

	private function _startTag($tagInfoArr){
		$arr = explode('_', $tagInfoArr['xpath']);
		$nodeName = array_pop($arr);
		$output = sprintf('<%s',$nodeName);
		foreach($tagInfoArr['attr'] as $aName => $avalue){
			if($aName == "xml_lang"){
				$aName = 'xml:lang';
			}
			$output .= sprintf(' %s="%s" ' , $aName, $avalue);
		}
		$output .= sprintf('>');
		return $output;
	}

	private function _fillContent($content){

		if($content instanceof SimpleXMLElement){
			return '';
		}
		return '<![CDATA['.$content.']]>';
	}

	private function _endTag($tagName){
		$output = sprintf('</%s>',$tagName);
		return $output;
	}

	private function _addLangLine($parseArr, $valueArr){
		$output = '';
		foreach($valueArr as $vkey => $vArr){
			if($vArr['content_node'] == $parseArr['attr']){
				$output .= $this->_startTag($parseArr);
				$output .= $this->_fillContent($vArr['content']);
				$output .= $this->_endTag($nodeName);
			}
		}
		return $output;
	}

	public function getXmlFile($valueArr = array(),$context = null){
		$stackNodes = array();
		if($context === null){
			$context = $this->_xmlContext;
		}
		$output = '<?xml version="1.0" encoding="UTF-8"?>';
		foreach($context as $key => $parseArr){
			$curXpath = $parseArr['xpath'];
			$arr = explode('_', $curXpath);
			$nodeName = array_pop($arr);

			while(count($stackNodes) >= count(explode('_',$curXpath))){
				$nN = array_pop($stackNodes);
				$output .= $this->_endTag($nN);
			}

			$output .= $this->_startTag($parseArr);
			$output .= $this->_fillContent($parseArr['value']);

			if($this->_hasSubNode($parseArr['value'])){
				$stackNodes[] = $nodeName;
			}else{
				$output .= $this->_endTag($nodeName);
			}

			if(isset($parseArr['isTransLine'])){
				$output .= $this->_addLangLine($parseArr,$valueArr);
			}
			$lastPath = $curXpath;
		}

		while(count($stackNodes) > 0){
			$nN = array_pop($stackNodes);
			$output .= $this->_endTag($nN);
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
}