<?php
class Fileparse {

	private $_context;
	private $_defaultLang;
	private $_file;
	private $_filePath;
	private $_commentArr = array('#');
	private $_comment;

	const FILE_TYPE_PROPERTY = 5;
	const FILE_TYPE_OTHER = 4;

	function __construct($file , $defaultLang = 'en'){
		if(!file_exists($file)){
			//throw new Exception ("file don't exist!");
			return;
		}

		$this->_filePath = $file;
		$this->_fileType = $this->_getFileType($file);
		$this->_defaultLang = $defaultLang;
		$this->_context = $this->_parseFile();
	}
	public static function getParseFileType(){
		return array('properties');
	}

	public function getContext(){
		return $this->_context;
	}

	/**
     * To find out the prase file
     * @author zhangjin
     * @createTime 2011-12-15 14:43:10
     * @param
     * @return array the context param
     */
	public function getFileContent(){
		if(!file_exists($this->_file)){
			return '';
		}
		return file_get_contents($this->_file);
	}

	private function _storageData($strContent){
		//if a comment line return NULL
		$commentStr = $this->_commentArr;
		foreach($commentStr as $cStr){
			$preStr = sprintf('/^%s/', $cStr);
			if(preg_match($preStr, $strContent)){
				return NULL;
			}
		}

		//if not a volidate format(someValueName = someValue) return NULL
		if(!preg_match('/(.*)=(.*)/', $strContent, $matches)){
			return NULL;
		}
		$data = array();
		$data['name'] = trim($matches[1]);
		$data['value'] = trim($matches[2]);
		$data['lang'] = $this->_defaultLang;
		return $data;
	}

	/*
	* To parse the file to storage the file as a array list as the following sturction:
	*	array(
	*			nodeIndexNumber => array(
	*				name => name,
	*				value => value
	*			))
	*/
	private function _parseFile(){
		$data = array();

		$handle = fopen($this->_filePath, "r");
		$maxRow = 10000;
		$row = 1;
		$isComment = FALSE;
		while (!feof($handle) && $row <= $maxRow) {
			$oIsComment = $isComment;
		    $buffer = fgets($handle, 4096);
		    $i = count($data);
		    $rs = $this->_storageData($buffer);
		    if($rs){
		    	$data[$i] = $rs;
		    	$isComment = FALSE;
		    }
		    else{
		    	$isComment = TRUE;
		    	$rs = $buffer;
		    }
		    $this->_stoComment($oIsComment,$isComment,$rs);
		    $row++;
		}
		fclose($handle);
		return $data;
	}

	private function _stoComment($oIsComment,$isComment,$line){
		//is comment line
		if($isComment == TRUE){
			@$this->_comment[0] .= $line;
		}
		//form property set line to the comment line
		//ex:
		//	//comment line blar blar			-----last line
		//	propertyName = propertyValue		-----current line
		elseif($oIsComment != $isComment && $isComment == FALSE){
			$this->_comment[$line['name']] = $this->_comment[0];
			$this->_comment[0] = '';
		}
	}

	private function _getComment(){
		return $this->_comment;
	}

	/**
     * Write the file from the database's data
     * @author zhangjin
     * @createTime 2011-12-15 16:20:22
     * @param $valueArr array the data from database
     * @param $pName string the path of file to write
     * @return string the xml file
     */
	public function writeFile($valueArr = array(), $pName = 'file.txt'){
		$stackNodes = array();
		$context = $this->_context;

		foreach($context as $ckey => $cvalue){
			$output = '';

			//add comment
			$nameKey = $cvalue['name'];
			if($this->_comment[$nameKey]){
				$output .= $this->_comment[$nameKey];
			}
			$output .= $cvalue['name'].'=';

			//add properties set
			$foundContent = false;
			foreach($valueArr as $key => $parseArr){
				if($parseArr['content_node'] != $nameKey){
					continue;
				}
				$output .= $parseArr['content']."\r\n";
				$foundContent = true;
			}
			if(!$foundContent) $output .= $cvalue['value']."\r\n";
	
			file_put_contents($pName, $output, FILE_APPEND);
		}

		
		//comment at last of file
		if($this->_comment[0]){
			file_put_contents($pName, $this->_comment[0], FILE_APPEND);
		}
		return;
	}

	/*
	*	get file type by path of file
	*
	*/
	private function _getFileType($filePath){
		$fExt = $this->_getFileExt($filePath);

		if($fExt == 'properties'){
			return self::FILE_TYPE_PROPERTY;
		}
		return self::FILE_TYPE_OTHER;
	}

	private function _getFileExt($pFileName){
        $pos = strpos($pFileName,'?');
        if($pos){
        	$pFileName=substr($pFileName,0,$pos);
        }
        $name = $this->_getFileName($pFileName);
        $ext = explode(".", $name);
        return (count($ext)==1) ? "" : array_pop($ext);
    }

	private function _getFileName($pFileName){
		$arr = explode('/', $pFileName);
		$name = array_pop($arr);
		return $name;
	}
}