<?php
class XMLContext {

	public $_xmlContext = array();
	public $_phraseXml;
	public $_fileType;
	public $_xml;
	public $_rootName;
	public $_defaultLang;
	public $_file;
	public $_options_value;

	const FILE_TYPE_SKIN = 1;
	const FILE_TYPE_LANG = 2;
	const FILE_TYPE_INFO = 3;
	const FILE_TYPE_OTHER = 4;

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

		// 'xml:lang' trans to 'xml_lang' as a xml file attribute
		$this->_file = preg_replace('/xml:lang/i','xml_lang',$this->_file);

		$this->_defaultLang = $defaultLang;
		$this->_xml = simplexml_load_string($this->_file);
		$this->_fileType = $this->_getFileType($file);
		$this->_xmlContext = $this->_parseFile($this->_xml, $this->_rootName);
	}

	/**
     * To set the root element's node name param $this->_rootName
     * @author zhangjin
     * @createTime 2011-10-31 16:46:58
     * @param $file filepath & name
     * @return bool (if set rootName success return true,else false)
     */
	private function _setRootName($file){
		$xmlStr = file_get_contents($file);
		if(!(preg_match('/\?>\s*<([^>\s]*)\s*.*>/', $xmlStr, $match))){
			return false;
		}
		$this->_rootName = $match[1];
		return true;
	}

	/**
     * To find out the prase xml file
     * @author zhangjin
     * @createTime 2011-10-31 16:46:58
     * @param
     * @return array the context param
     */
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
		//set attributes
		$data['attr'] = $xmlObj->attributes();

		//set xpath
		$data['xpath'] = $xpath;

		//if is LangLine set addition column :isLangLine as true
		//and set value as string
		if($isLangLine){
			$data['isLangLine'] = true;
			$data['value'] = strval($xmlObj);
			return $data;
		}

		//if has children nodes storage value column as SimpleXMLElement type ,others as string type
		$arr = explode('/',$xpath);
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
	*	iteration prase the xml file to storage the xml file as a array list as the following sturction:
	*		array(
	*				nodeIndexNumber => array(
	*					xpath => nodeXpath(if node element has 'name' param the node path as nodeName[nameValue] form),
	*					value => nodeValue or node's inner xml object(SimpleXMLElement type),
	*					atrr => node atrributes (SimpleXMLElement type)
	*				))
	*
	*/
	private function _parseFile($xmlObj, $rootName){
		static $xpath;
		$data = array();
		$xpath = !empty($xpath) ? $xpath.'/'.$rootName:$rootName;

		$isLangLine = false;
		foreach($xmlObj->attributes() as $atrrName => $atrrValue){
			if($atrrName == 'xml_lang'){
				$isLangLine = true;
			}

			//set xpath:if has name,id,value attribute set xpath as nodeName[nameValue]
			if($atrrName == 'name' || $atrrName == 'id' || $atrrName == 'value'){
				$xpath = $this->getDbContentName($xpath, $atrrValue);
				$rootName .= '('.$atrrValue.')';
			}
		}

		// info and skin file may contain options tag
		if($this->_fileType == self::FILE_TYPE_SKIN || $this->_fileType == self::FILE_TYPE_INFO){
			if(strpos($xpath,'options')){
				if($rootName != '('.$atrrValue.')'){
					if($rootName == 'value'){
						$xpath = $this->getDbContentName($xpath, strval($xmlObj));
						$this->_options_value = strval($xmlObj);
					}else{
						foreach($xmlObj as $nodeName => $subNode){
							if($nodeName == 'value'){
								$this->_options_value = strval($subNode);
							}
						}
					}
					if($rootName == 'name'){
						if($this->_options_value){
							$xpath = $this->getDbContentName($xpath, $this->_options_value);
							$rootName .= '('.$this->_options_value.')';
						}
					}
				}
			}
		}

		array_push($data, $this->_storageNode($xmlObj, $xpath, $isLangLine));

		foreach($xmlObj as $nodeName => $subNode){
			$data = array_merge($data, $this->_parseFile($subNode, $nodeName));
		}

		//remove the right rootName from the xpath
		$xpath = explode($rootName, $xpath);
		array_pop($xpath);
		$xpath = implode($rootName, $xpath);
		$xpath = substr($xpath, 0, strlen($xpath)-1);
		return $data;
	}

	private function _startTag($tagInfoArr){
		$xpath = $tagInfoArr['xpath'];
		$xpath = $this->_getXpath($xpath);
		$arr = explode('/', $xpath);
		$nodeName = array_pop($arr);
		$output = sprintf('<%s',$nodeName);
		foreach($tagInfoArr['attr'] as $aName => $avalue){
			if($aName == "xml_lang"){
				$aName = 'xml:lang';
			}
			$output .= sprintf(' %s="%s"' , $aName, $avalue);
		}
		$output .= sprintf('>');
		return $output;
	}

	private function _startTagNewLine($tagInfoArr){
		$xpath = $tagInfoArr['xpath'];
		$xpath = $this->_getXpath($xpath);
		$arr = explode('/', $xpath);
		$nodeName = array_pop($arr);
		$output = sprintf('<%s',$nodeName);
		foreach($tagInfoArr['attr'] as $aName => $avalue){
			if($aName == "xml_lang"){
				$aName = 'xml:lang';
			}
			$output .= sprintf(' %s="%s"' , $aName, $avalue);
		}
		$output .= sprintf(">\n");
		return $output;
	}

	private function _fillContent($content){
		if($content instanceof SimpleXMLElement){
			return '';
		}
		return '<![CDATA['.$content.']]>';
	}

	private function _endTag($tagName){
		$output = sprintf("</%s>\n",$tagName);
		return $output;
	}

	private function _addLangLine($parseArr, $valueArr){

		$output = '';
		$paramN = null;
		foreach($parseArr['attr'] as $key => $value){
			if($key == 'name'){
				$paramN = $value;
			}
		}
		$dbNodeName = $this->getDbContentName($parseArr['xpath'], $paramN);

		$xpath = $this->_getXpath($parseArr['xpath']);

		$arr = explode('/', $xpath);
		$nodeName = array_pop($arr);
		foreach($valueArr as $vkey => $vArr){
			if($vArr['content_node'] == $dbNodeName){
				/*foreach($parseArr['attr'] as $key => $value){
					if($key == 'xml_lang'){
						$parseArr['attr'][$key] = strval($vArr['lang']);
					}
				}*/
				$parseArr['attr'][$key] = strval($vArr['lang']);
				$output .= $this->_startTag($parseArr);
				$output .= $this->_fillContent($vArr['content']);
				$output .= $this->_endTag($nodeName);
			}
		}
		return $output;
	}

	private function _updateLangLine($parseArr, $valueArr){
		$output = '';
		$paramN = null;
		foreach($parseArr['attr'] as $key => $value){
			if($key == 'name'){
				$paramN = $value;
			}
		}
		$dbNodeName = $this->getDbContentName($parseArr['xpath'], $paramN);

		$xpath = $this->_getXpath($parseArr['xpath']);

		$arr = explode('/', $xpath);
		$nodeName = array_pop($arr);
		foreach($valueArr as $vkey => $vArr){
			if($vArr['content_node'] == $dbNodeName && $vArr['lang'] == $parseArr['attr']['xml_lang']){
				foreach($parseArr['attr'] as $key => $value){
					if($key == 'xml_lang'){
						$parseArr['attr'][$key] = strval($vArr['lang']);
					}
				}
				$output .= $this->_fillContent($vArr['content']);
				$vArr['update'] = 1;
			}
		}
		return $output;
	}

	//remove '(nameValue)' in xpath
	private function _getXpath($xpath){
		/*preg_match_all('/(\(.*\))/',$xpath,$match);

		if(empty($match)){
			return $xpath;
		}
		foreach($match[0] as $key => $value){
			$xpath = explode($value, $xpath);
			$xpath = implode('',$xpath);
		}
		return $xpath;*/

		$xpath =  preg_replace('/\([^)]*\)|[()]/', "", $xpath);
		return $xpath;
	}

	/**
     * Get the XML file from the context param
     * @author zhangjin
     * @createTime 2011-10-31 16:46:58
     * @param $valueArr array the data from database
     * @return string the xml file
     */
	public function getXmlFile($valueArr = array()){
		$stackNodes = array();
		$context = $this->_xmlContext;
		$output = "<?xml version='1.0' encoding='UTF-8'?>\n";
		foreach($context as $key => $parseArr){
			$curXpath = $this->_getXpath($parseArr['xpath']);

			$arr = explode('/', $curXpath);

			$nodeName = array_pop($arr);

			/*var_Dump($stackNodes);
			var_Dump($curXpath);
			var_Dump($parseArr['xpath']);*/

			while(count($stackNodes) >= count(explode('/',$curXpath))){
				$nN = array_pop($stackNodes);
				$output .= $this->_endTag($nN);
			}


			if(!isset($parseArr['isLangLine'])){
				if($this->_hasSubNode($parseArr['value'])){
					$output .= $this->_startTagNewLine($parseArr);
				}else{
					$output .= $this->_startTag($parseArr);
				}
			}

			/*if(isset($parseArr['isLangLine'])){
				$update_line = $this->_updateLangLine($parseArr,$valueArr);
				$output .= $update_line;
				if(!$update_line) $output .= $this->_fillContent($parseArr['value']);
			}else{
				$output .= $this->_fillContent($parseArr['value']);
			}*/

			$nextNode = $context[$key+1];
			$nextPath = $this->_getXpath($nextNode['xpath']);

			if($curXpath != $nextPath){
				if(isset($parseArr['isLangLine'])){
					$add_line = $this->_addLangLine($parseArr,$valueArr);
					$output .= $add_line;
				}else{
					$output .= $this->_fillContent($parseArr['value']);
				}
			}

			if(!isset($parseArr['isLangLine'])){
				if($this->_hasSubNode($parseArr['value'])){
					$stackNodes[] = $nodeName;
				}else{
					$output .= $this->_endTag($nodeName);
				}
			}


			/*$nextNode = $context[$key+1];
			$nextPath = $this->_getXpath($nextNode['xpath']);
			if($curXpath != $nextPath){
				if(isset($parseArr['isLangLine'])){
					$add_line = $this->_addLangLine($parseArr,$valueArr);
					$output .= $add_line;
				}
			}*/

			$lastPath = $curXpath;
		}

		while(count($stackNodes) > 0){
			$nN = array_pop($stackNodes);
			$output .= $this->_endTag($nN);
		}
		return $output;
	}

	/*
	*	get file type by path of file
	*
	*/
	private function _getFileType($filePath){
		$fName = $this->_getFileName($filePath);

		if(strtolower($fName) == 'skin.xml'){
			return self::FILE_TYPE_SKIN;
		}
		if(strtolower($fName) == 'lang.xml'){
			return self::FILE_TYPE_LANG;
		}
		if(strtolower($fName) == 'info.xml'){
			return self::FILE_TYPE_INFO;
		}
		return self::FILE_TYPE_OTHER;
	}

	private function _getFileName($pFileName){
		//$arr = explode(DIRECTORY_SEPARATOR, $pFileName);
		$arr = explode('/', $pFileName);
		$name = array_pop($arr);
		return $name;
	}

	/**
     * Get the db content node column value
     * @author zhangjin
     * @createTime 2011-10-31 16:46:58
     * @param $xpath the parent xpath which is storaged in db's content node column
     * @return string the xpath
     */
	public function getDbContentName($xpath,$nameValue = null){
		 if(empty($nameValue)){
		 	return $xpath;
		 }
		 return $xpath.'('.$nameValue.')';
	}
}