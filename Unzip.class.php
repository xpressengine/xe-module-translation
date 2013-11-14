<?php
class Unzip {
	private $_file;
	private $_filePathName;

	function __construct($file){
		if(!file_exists($file)){
			//throw new Exception ("file don't exist!");
			return;
		}

		$this->_filePathName = $file;
		$this->_fileType = $this->_getFileType($file);
	}

	/**
     * To get substring from right
     * @author zhangjin
     * @createTime 2011-12-16 14:41:19
     * @param $str string the String to be substred
     * @param $length int the number count from right to cut
     * @return string the substring
     */
	private function _substrRight($str, $length)
	{
		$strLen = strlen($str);
		if($strLen <= $length)
		{
			return $str;
		}
		else
		{
			return substr($str, $strLen-$length, $length);
		}
	}

	private function _getFNameNoExt($fName){
		$ext = $this->_getFileExt($fName);
		if(empty($ext)){
			return $fName;
		}
		$strLen = strlen($fName);
		$extLen = strlen($ext);
		return substr($fName,0,$strLen-$extLen-1);
	}

	private function _saveFile($content, $savePath, $pName, $compress)
	{
		$fileName = $this->_getFileName($pName);

		//no '/' at last word to added
		$lastStr = $this->_substrRight($savePath, 1);
		if($lastStr != DIRECTORY_SEPARATOR){
			$savePath .= '/';
		}

		$fPathName = $savePath.$pName;

		//get the save path without filename
		$path = $fPathName;
		if($compress != 'stored'){
			$path = dirname($path);
		}

		//if direction not exist to create the path
		if(!file_exists($path)){
			mkdir($path, 0755, true);
			chmod($path, 0755);
		}
		if($compress == 'stored'){
			return;
		}

		//save file
		while(file_exists($fPathName)){
			$ext = $this->_getFileExt($fileName);
			$name = $this->_getFNameNoExt($fileName);
			$fileName = $name.time().'.'.$ext;
			$fPathName = $path.$fileName;
		}
		file_put_contents($fPathName, $content);
		return $fPathName;
	}

	/**
     * To Unzip the file
     * @author zhangjin
     * @createTime 2011-12-16 9:46:36
     * @param savePath string the path to save the zip files
     * @return array the unzip file info(name,size,csize,compress)
     */
	public function unZipFile($savePath = './')
	{
		$file = $this->_filePathName;
		$zip = zip_open($file);
		if(!$zip)
		{
			return;
		}

		$infoArr = array();
	    while ($res = zip_read($zip)) {
	    	$index = count($infoArr);
	    	$name = zip_entry_name($res);
	    	$ext = $this->_getFileExt($name);

	    	$infoArr[$index]['name'] = $name;
	    	$infoArr[$index]['size'] = zip_entry_filesize($res);
	    	$infoArr[$index]['csize'] = zip_entry_compressedsize($res);
	    	$infoArr[$index]['compress'] = zip_entry_compressionmethod($res);

	        $buf = '';
	        if (zip_entry_open($zip, $res, "r")) {
	            $buf = zip_entry_read($res, $infoArr[$index]['size']);
	            zip_entry_close($res);
	        }
	        $oName = $infoArr[$index]['name'];
	        $compress = $infoArr[$index]['compress'];
	        $infoArr[$index]['fpathName'] = $this->_saveFile($buf,$savePath,$oName,$compress);
	    }
		return $infoArr;
	}

	/*
	*	get file type by path of file
	*
	*/
	private function _getFileType($filePath){
		$fExt = $this->_getFileExt($filePath);
		return $fExt;
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