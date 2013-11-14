<?php
class Csvcontext {

	private $_fileType;
	private $_fileName;
	private $_fileHandle;
	private $_transArr;
	private $_originalLan;
	private $_transLan;

	function __construct($file, $defaultLang = 'ko', $transLang = 'zh-CN'){
		if(!file_exists($file)){
			//throw new Exception ("file don't exist!");
			return;
		}

		$this->_fileHandle = fopen($file, 'rb');
		if(!$this->_fileHandle){
            return;
		}
		$this->_file = $file;
		$this->_fileType = 'csv';
		$this->_originalLan = $defaultLang;
		$this->_transLan = $transLang;
		$this->_transArr = $this->_parseFile();

	}

    function getContext(){
        return $this->_transArr;
    }

	/*
	*	iteration prase the xml file to storage the xml file as a array list as the following sturction:
	*		array(
	*				nodeIndexNumber => array(
	*					xpath => nodeXpath(if node element has 'name' param the node path as nodeName[nameValue] form),
	*					value => nodeValue or node's inner xml object(SimpleXMLElement type),
	*				))
	*
	*/
	private function _parseFile(){
	    $rData = array();
	    $line = 1;
		$origin_lang = $this->_originalLan;
		$trans_lang = $this->_transLan;

        while($data = fgets($this->_fileHandle, 100000)){
            $data = explode(',', $data);
            if(count($data) != 2){
                return;
            }

			$isToCsv = false;
			if($line == 1){
				$this->_originalLan = $this->_iconvCsv(trim($data[0]), $isToCsv);
				$this->_transLan = $this->_iconvCsv(trim($data[1]), $isToCsv);
			}
			if($line != 1){
				$rData[] = array(
							   'xpath' => $line,
							   'value' => $this->_iconvCsv($data[0], $isToCsv),
							   'lang'  => $this->_originalLan,
						   );
				$rData[] = array(
							   'xpath' => $line,
							   'value' => $this->_iconvCsv($data[1], $isToCsv),
							   'lang'  => $this->_transLan,
						   );
			}
            $line++;
        }

		return $rData;
	}

	public function getCSVFile($fPathName, $origArr = array(), $targetArr = array()){
        if(file_exists($fPathName)){
            return;
        }
        //create a new empty file
        file_put_contents($fPathName,'');
        ksort($origArr);
        ksort($targetArr);
        foreach($origArr as $key => $oData){
            $key = $oData['content_node'];
            $tData = array();
            foreach($targetArr as $key => $search){
                if($search['content_node'] == $oData['content_node']){
                    $tData = $search;
                    break;
                }
            }
            if(empty($tData)){
                continue;
            }
            $fileContent = implode(',', array(
                                            $oData['content'],
                                            $tData['content'],
                                         )
                           );
            $isToCsv = true;
            $fileContent = $this->_iconvCsv(trim($fileContent), $isToCsv);
            if(file_exists($fPathName)){
                file_put_contents($fPathName, "\r\n", FILE_APPEND);
            }
            file_put_contents($fPathName, $fileContent, FILE_APPEND);
        }
	}

    function getSourceLang(){
        return $this->_originalLan;
    }

	private function _iconvCsv($content, $isToCSV = true){
	    if($content == ''){
	        return;
	    }

        if($isToCSV){
            $content = iconv('UTF-8','GBK',$content);
        }
        else{
            $content = iconv('GBK','UTF-8',$content);
        }
		return $content;
	}     

}