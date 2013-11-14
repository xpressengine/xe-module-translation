<?php
/**
 * @class  translationController
 * @author NHN (developers@xpressengine.com)
 * @brief  translation module Controller class
 **/
require_once('TFileHandle.class.php');
require_once('Upload.class.php');
require_once('Unzip.class.php');
require_once('Fileparse.class.php');
require_once('Csvcontext.class.php');

class translationController extends translation {

	/**
	 * @brief initialization
	 **/
	function init() {
	}

	/**
	 * @brief insert and update project
	 **/
	function procTranslationInsertProject(){
		// check permission
		if($this->module_info->module != "translation") return new Object(-1, "msg_invalid_request");
        $logged_info = Context::get('logged_info');

		// only admin user can insert project
		if(!$logged_info || $logged_info->is_admin != 'Y') return $this->stop('msg_invalid_request');

		// get form variables submitted
		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_info->module_srl;
		$obj->member_srl = $logged_info->member_srl;
		$obj->project_name = $obj->project_name;
		$obj->project_type = $obj->project_type?$obj->project_type:'normal';

		if(!$obj->translation_project_srl){
			$obj->translation_project_srl = getNextSequence();

			// make project folder for saving the files
			$file_folder = './files/translation_files/'.$this->module_info->module_srl.'/'.$obj->translation_project_srl;
			FileHandler::makeDir($file_folder);

			// make cache folder for downloading
			$cache_folder = './files/cache/translation/'.$this->module_info->module_srl.'/'.$obj->translation_project_srl;
			FileHandler::makeDir($cache_folder);

			// DB query, insert project
			$output = executeQuery('translation.insertProject', $obj);
			$msg_code = 'success_registed';
		}else{
			$obj->translation_project_srl = $obj->translation_project_srl;

			$cache_folder = './files/cache/translation/'.$obj->translation_project_srl;
			FileHandler::removeDir($cache_folder);
			FileHandler::makeDir($cache_folder);

			// DB query, update project
			$output = executeQuery('translation.updateProject', $obj);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool()) { return $output;}

		$this->add('module_srl', $obj->module_srl);
		$this->add('translation_project_srl', $obj->translation_project_srl);

		// output success inserted/updated message
		$this->setMessage($msg_code);

	    if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationProjectList','scope',$obj->scope);
			header('location:'.$returnUrl);
			return;
		}

	}

	//if has effective upload url file in the form
	private function _hasFileUrl(){
		$obj = Context::getRequestVars();
		if(!$obj->file_url || $obj->file_url == 'http://'){
			return FALSE;
		}
		return TRUE;
	}

	private function _getUploadFName(){
		$obj = Context::getRequestVars();
		$option = $obj->upload_option;
		$uploadName = $obj->uploaded_file['name'];
		$fileUrl = $obj->file_url;

		//get fileName : if from form upload get from upload param,
		//if from url use function to get fileName
		$fileName = '';
		if($option == 'upload_f' && $uploadName){
			$fileName = $uploadName;
		}
		elseif($option == 'url_f' && $fileUrl){
			$fileName = TFileHandle::getFileName($fileUrl);
		}
		//if modify the file, and no uploaded file, use the name from the database file info
		elseif($obj->translation_file_srl && !$this->_hasFileUrl() && !$uploadName)
		{
			$oTransModel = &getModel('translation');
			$fInfo = $oTransModel->getFile($obj->translation_file_srl);
			$fileName = TFileHandle::getFileName($fInfo->target_file);
		}
		return $fileName;
	}

	private function _getSvePath($fSrl){
		return sprintf('%sfiles/translation_files/%s/%s/%s',
								_XE_PATH_,
								$this->module_info->module_srl,
								$obj->translation_project_srl,
								getNumberingPath($fSrl));
	}

	private function _uploadFile(){
		$obj = Context::getRequestVars();

		//if is modefy file to remove the old file
		if($obj->translation_file_srl)
		{
			$oTransModel = &getModel('translation');
			$fInfo = $oTransModel->getFile($obj->translation_file_srl);

			//both input(upload input or url input) is empty, than no need to upload ,return the old relative path
			if(!$obj->uploaded_file && !$this->_hasFileUrl()){
				return $fInfo->target_file;
			}
			FileHandler::removeFile($fInfo->target_file);
		}

		//upload the new file
			//to get file srl
		if($fInfo){
			$fSrl = $fInfo->translation_file_srl;
		}
		else{
			$fSrl = getNextSequence();
		}

		$filePath = $this->_getSvePath($fSrl);
		$fileName = $this->_getUploadFName();

		// make direction
		FileHandler::makeDir($filePath);
			//upload the file by form's input
		if($obj->upload_option == 'upload_f' && $obj->uploaded_file){
			$uploadObj = new Upload($filePath);
			$r = $uploadObj->saveFile('uploaded_file', TFileHandle::getFNameNoExt($fileName));
		}
			//upload the file by url
		if($obj->upload_option == 'url_f' && $obj->file_url){
			FileHandler::getRemoteFile($obj->file_url, $filePath.$fileName);
		}

		//if upload success return the relative path which is removed the _XE_PATH_
		//		(ex. '/xePath/file/123/234/456/filename.ext' than return 'file/123/234/456/filename.ext')
		//else return FALSE
		$re = file_exists($filePath.$fileName);
		if($re)
		{
			//remove the _XE_PATH_
			$return = $this->_removeXEPath($filePath.$fileName);
			return $return;
		}
		return FALSE;
	}

	private function _unicode2utf8($str){
	    $str = preg_replace("/\\\\u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
    	return html_entity_decode($str,null,'UTF-8');
	}

	private function _fromUnicode($pFileName, $toCode = 'UTF-8'){
		$rs = file_get_contents($pFileName);
		$rsUtf8 = $this->_unicode2utf8($rs);
		if($toCode != 'UTF-8'){
			$rs = iconv('UTF-8', $toCode, $rsUtf8);
		}
		file_put_contents($pFileName,$rsUtf8);
	}

	private function _parseFile($pFileName){
		if(strpos($pFileName,_XE_PATH_) === FALSE){
			$pFileName = _XE_PATH_.$pFileName;
		}

		//if is file parse,use file parse
		if($this->_isFileParse($pFileName))
		{
			$this->_fromUnicode($pFileName);
			$fileObj = new Fileparse($pFileName);
			$context = $fileObj->getContext();
		}
		elseif($this->_isCsvParse($pFileName))
		{
		    $csvObj = new Csvcontext($pFileName);
		    $context = $csvObj->getContext();
		}
		else
		{
			$oXMLContext = new XMLContext($pFileName, "en");
			$context = $oXMLContext->_xmlContext;
		}
		return $context;
	}

	private function _isCsvParse($pFileName){
		$ext = TFileHandle::getFileExt($pFileName);
		if(in_array($ext, array('csv'))){
			return TRUE;
		}
		return FALSE;
	}

	private function _isFileParse($pFileName, $isReturnExt = FALSE){
		$ext = TFileHandle::getFileExt($pFileName);
		if($isReturnExt){
		    return strtolower($ext);
		}
		if(in_array($ext, Fileparse::getParseFileType())){
			return TRUE;
		}
		return FALSE;
	}

	private function _addContentTable($pFileName, $fSrl, $pSrl=null){
		$context = $this->_parseFile($pFileName);

		$lang_contents = array();
		$logged_info = Context::get('logged_info');
		$objReq = Context::getRequestVars();
		$pSrl = $pSrl?$pSrl:$objReq->translation_project_srl;
		$isFileParse = $this->_isFileParse($pFileName);
		$ext = $this->_isFileParse($pFileName, TRUE);

		//var_Dump($context);

		foreach($context as $key => $val){
			if($ext == 'csv'){
			    $obj->content_node = $val['xpath'];
			    $obj->lang = $val['lang'];
			}
			//is xml parse
			elseif(!$isFileParse){
				if(!$val['attr']['xml_lang']){
					continue;
				}
				$obj->content_node = $val['xpath'];
				$obj->lang = strval($val['attr']['xml_lang']);
				$lang_contents[$obj->content_node][$obj->lang] = $obj->content;
			}
			//is file parse
			elseif($isFileParse){
				$obj->content_node = $val['name'];
				$obj->lang = $val['lang'];
			}

			$obj->translation_content_srl = getNextSequence();
			$obj->module_srl = $this->module_info->module_srl;
			$obj->translation_project_srl = $pSrl;
			$obj->translation_file_srl = $fSrl;
			$obj->member_srl = $logged_info->member_srl;
			$obj->content = strval($val['value']);
			$obj->recommended_count = 0;
			$obj->is_original = 1;

			$output = executeQuery('translation.insertXMLContents', $obj);
			if(!$output->toBool()) {return $output;}
		}

		//xml file parse need add dictionary table
		if(!$isFileParse)
		{
			$this->insertDicContents($lang_contents);
		}
	}

	private function _addContTable($uploadName){
		$obj = Context::getRequestVars();
		$fExt = TFileHandle::getFileExt($obj->file_name);
		$fSrl = $obj->translation_file_srl;
		$pSrl = $obj->translation_project_srl;

		$isUpdate = TRUE;
		if(!$fSrl){
			$isUpdate = FALSE;
			$fSrl = getNextSequence();
		}

		//if no update file ,then do not modify the content table
		if(!$obj->uploaded_file && !$this->_hasFileUrl()){
			return $fSrl;
		}

		//if update: delete the content table's old data
		if($isUpdate){
			// delete and insert contents to xe_translation_contents table
			$this->delContentsByFSrl($fSrl);
		}

		//add new data:file parse or xml parse
		$rs = $this->_addContentTable($uploadName, $fSrl);

		return $fSrl;
	}

	private function _addFileContent($uploadPName){
		$obj = Context::getRequestVars();
		$logInfo = Context::get('logged_info');
		$obj->member_srl = $logInfo->member_srl;
		$obj->target_file = $uploadPName;
		$obj->file_name = TFileHandle::getFileName($uploadPName);
		$obj->file_path = $obj->file_path?$obj->file_path:$obj->target_file; 
		$obj->project_type = $obj->project_type?$obj->project_type:'normal';
		

		//set file type
		$fNameArr = array('lang', 'info', 'skin');
		$fNameNoExt = TFileHandle::getFNameNoExt($obj->file_name);
		$obj->file_type = 'normal';
		if(in_array(strtolower($fNameNoExt), $fNameArr)){
			$obj->file_type = $fNameNoExt;
		}

		$ext = TFileHandle::getFileExt($obj->file_name);
		if($obj->translation_file_type == 'xe'){
			if($ext != 'xml') return new Object(-1, "Please insert a XE XML file");
		}
		if($obj->translation_file_type == 'properties'){
			if($ext == 'properties') $obj->file_type = $ext;
			else  return new Object(-1, "Please insert a Java Properties file");
		}
		if($obj->translation_file_type == 'csv'){
			if($ext == 'csv') $obj->file_type = $ext;
			else  return new Object(-1, "Please insert a CSV file");
		}


		//add translation to the content table
		$fSrl = $this->_addContTable($uploadPName);

		//add file table
		if(!$obj->translation_file_srl){
	
			$obj->translation_file_srl = $fSrl;
				
			// DB query, insert file
			$output = executeQuery('translation.insertFile', $obj);
			if(!$output->toBool()) { return $output;}
		}

		//update file
		if($obj->translation_file_srl){
			$output = executeQuery('translation.updateFile', $obj);
			if(!$output->toBool()) { return $output;}
		}
		return $obj->translation_file_srl;
	}

	private function _addZipFile($uploadPName, $pro_srl){
		$obj = Context::getRequestVars();
		$logInfo = Context::get('logged_info');
		$obj->member_srl = $logInfo->member_srl;
		$obj->target_file = $uploadPName;
		$obj->file_name = TFileHandle::getFileName($uploadPName);
		$obj->project_type = $obj->project_type?$obj->project_type:'normal';
		$obj->translation_project_srl =  $pro_srl;
		$obj->file_path = $obj->file_path?$obj->file_path:$obj->target_file;

		$path_pattern = sprintf('files/translation_files/%s/%s/', $obj->module_srl,$obj->translation_project_srl);
		$obj->file_path = str_replace($path_pattern, '',$obj->file_path);


		//set file type
		$fNameArr = array('lang', 'info', 'skin');
		$fNameNoExt = TFileHandle::getFNameNoExt($obj->file_name);
		$obj->file_type = 'normal';
		if(in_array(strtolower($fNameNoExt), $fNameArr)){
			$obj->file_type = $fNameNoExt;
		}

		$ext = TFileHandle::getFileExt($obj->file_name);
		if($obj->translation_file_type == 'properties'){
			if($ext == 'properties') $obj->file_type = $ext;
		}
		if($obj->translation_file_type == 'csv'){
			if($ext == 'csv') $obj->file_type = $ext;
		}

		//add file table
		if(!$obj->translation_file_srl){
			$obj->translation_file_srl = getNextSequence();
			// DB query, insert file
			$output = executeQuery('translation.insertFile', $obj);
			if(!$output->toBool()) { return $output;}

			Context::set('translation_project_srl', $obj->translation_project_srl);
			$this->_addContentTable($uploadPName,$obj->translation_file_srl,$obj->translation_project_srl);
		}

		//update file
		if($obj->translation_file_srl){
			$output = executeQuery('translation.updateFile', $obj);
			if(!$output->toBool()) { return $output;}
		}
		return $obj->translation_file_srl;
	}

	private function _UnzipFile($fPathName){
		$path = dirname($fPathName);
		$unzipObj = new Unzip($fPathName);
		$zipInfo = $unzipObj->unZipFile($path);
		foreach($zipInfo as $key => $arr){
			//not a data info ,continue;
			if($arr['compress'] == 'stored'){
				continue;
			}
			$fPathArr[] = $arr['fpathName'];
		}
		return $fPathArr;
	}

	/**
	 * @brief insert and update file information
	 **/
	function procTranslationInsertFile(){
		set_time_limit(0);

		//permition check
		$r = $this->_checkPermition();
		if($r !== true){
			return $r;
		}

		$obj = Context::getRequestVars();
		$obj->file_name = $this->_getUploadFName();

		if(!$obj->translation_project_srl) return new Object(-1, "Please select a project");

		$ext = TFileHandle::getFileExt($obj->file_name);

		$fExtArr = Fileparse::getParseFileType();
		array_push($fExtArr, 'xml', 'zip', 'csv');

		// check the uploaded file ext is legal, only choosen files' ext can be uploaded
		if(!in_array(strtolower($ext), $fExtArr)){
			return new Object(-1, "msg_invalid_request");
		}

		$uploadRes = $this->_uploadFile();
		if(!$uploadRes){
			return new Object(-1, "Please uploaded the valid file.");
		}
		$filePath = $uploadRes;

		//if is zip file to unzip
		if($ext == 'zip'){
			$filePath = $this->_UnzipFile(_XE_PATH_.$filePath);
		}

		if(is_array($filePath)){
			foreach($filePath as $fPName){
				$fPName = $this->_removeXEPath($fPName);
				$result = $this->_addFileContent($fPName);
			}
		}
		else{
			$result = $this->_addFileContent($filePath);
		}

		if(is_object($result) && !$result->toBool()) {
			return $result;
		}
		if(is_numeric($result)){
			$obj->translation_file_srl = $result;
		}

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTransContent','translation_file_srl',$obj->translation_file_srl,'member_srl', $obj->member_srl);
		header('location:'.$returnUrl);
	}

	/**
	 * @brief insert and update file information
	 **/
	function procTranslationInsertZipFile(){
		
		//permition check
        $logged_info = Context::get('logged_info');
		if(!$logged_info || $logged_info->is_admin != 'Y') return $this->stop('msg_invalid_request');


		$obj = Context::getRequestVars();
		$obj->file_name = $this->_getUploadFName();

		if(!$obj->project_name) return new Object(-1, "Please input the project name");

		$ext = TFileHandle::getFileExt($obj->file_name);

		// check the uploaded file ext is legal, only choosen files' ext can be uploaded
		if($ext != 'zip'){
			return new Object(-1, "Please upload a valid Zip file");
		}

		// insert project
		$obj->module_srl = $this->module_info->module_srl;
		$obj->member_srl = $logged_info->member_srl;
		$obj->project_name = $obj->project_name;
		$obj->project_type = $obj->project_type?$obj->project_type:'zip';

		$zip_file =  $_FILES['uploaded_file'];

		if(!$obj->translation_project_srl){
			$obj->translation_project_srl = getNextSequence();

			// make project folder for saving the files
			$file_folder = './files/translation_files/'.$this->module_info->module_srl.'/'.$obj->translation_project_srl;
			FileHandler::makeDir($file_folder);


			// make cache folder for downloading
			$cache_folder = './files/cache/translation/'.$this->module_info->module_srl.'/'.$obj->translation_project_srl;
			FileHandler::makeDir($cache_folder);

			// DB query, insert project
			$output = executeQuery('translation.insertProject', $obj);

			// upload zip file
			$target_filename = $this->insertZipFile($this->module_info->module_srl,$obj->translation_project_srl,$obj->file_name, $zip_file['tmp_name'] );
			
			// unzip
			$filePath = $this->_UnzipFile($target_filename);
			if(is_array($filePath)){
				foreach($filePath as $fPName){
					$fPName = $this->_removeXEPath($fPName);
					$result = $this->_addZipFile($fPName,$obj->translation_project_srl);
				}
			}
			else $result = $this->_addZipFile($filePath,$obj->translation_project_srl);

		}else{
			$obj->translation_project_srl = $obj->translation_project_srl;

			// DB query, update project
			$output = executeQuery('translation.updateProject', $obj);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool()) { return $output;}

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationFileList','translation_project_srl',$obj->translation_project_srl,'member_srl', $obj->member_srl);
		header('location:'.$returnUrl);
	

	}
	private function _removeXEPath($fullPName){
		if(strpos($fullPName, _XE_PATH_) === FALSE){
			return $fullPName;
		}
		$relPName = substr($fullPName,strlen(_XE_PATH_));
		return $relPName;
	}

	/**
	 * @brief upload file to XE
	 **/
	function insertXmlFile($module_srl, $translation_project_srl, $translation_file_srl, $file_name, $target_file) {
		$target_path = sprintf('files/translation_files/%s/%s/%s', $module_srl,$translation_project_srl,getNumberingPath($translation_file_srl));
		FileHandler::makeDir($target_path);

		$ext = 'xml';
		$target_filename = sprintf('%s%s', $target_path, $file_name);

		@copy($target_file, $target_filename);

		return $target_filename;
	}

	/**
	 * @brief upload zip file to XE
	 **/
	function insertZipFile($module_srl, $translation_project_srl, $file_name,$target_file) {
		$target_path = sprintf('files/translation_files/%s/%s/', $module_srl,$translation_project_srl);
		FileHandler::makeDir($target_path);

		$ext = 'xml';
		$target_filename = sprintf('%s%s', $target_path, $file_name);

		@copy($target_file, $target_filename);

		return $target_filename;
	}

	/**
	 * @brief upload file to XE based on URL
	 **/
	function insertXMLFileByUrl($module_srl, $translation_project_srl, $translation_file_srl, $file_name, $target_url) {
		$target_path = sprintf('files/translation_files/%s/%s/%s', $module_srl,$translation_project_srl,getNumberingPath($translation_file_srl));
		FileHandler::makeDir($target_path);

		$ext = 'xml';
		$target_filename = sprintf('%s%s', $target_path, $file_name);

		if($target_url){
			$file = fopen($target_url,"rb");
			if($file){
				$ext = end(explode(".",strtolower(basename($target_url))));
				if($ext == 'xml'){
					$newfile = fopen($target_path.$file_name,"wb");
					if($newfile){
						while(!feof($file)){
							fwrite($newfile,fread($file,1024 * 8),1024 * 8);
						}
					}
				}
			}
		}

		return $target_filename;
	}

	/**
	 * @brief insert the file contents to xe_translation_contents table
	 **/
	function insertXMLContents($file, $translation_file_srl,$translation_project_srl){
		$logged_info = Context::get('logged_info');

		$oXMLContext = new XMLContext($file, "en");
		$_xmlContext = $oXMLContext->_xmlContext;

		$lang_contents = array();

		foreach($_xmlContext as $key => $val){
			if($val['attr']['xml_lang']){
				$obj->translation_content_srl = getNextSequence();
				$obj->module_srl = $this->module_info->module_srl;
				$obj->translation_project_srl = $translation_project_srl;
				$obj->translation_file_srl = $translation_file_srl;
				$obj->content_node = $val['xpath'];
				$obj->lang = strval($val['attr']['xml_lang']);
				$obj->member_srl = $logged_info->member_srl;
				$obj->content = strval($val['value']);
				$obj->recommended_count = 0;
				$obj->is_original = 1;
				$lang_contents[$obj->content_node][$obj->lang] = $obj->content;

				// DB query
				$output = executeQuery('translation.insertXMLContents', $obj);
				if(!$output->toBool()) { return $output;}
			}
		}

		$this->insertDicContents($lang_contents);

	}

	/**
	 * @brief insert single English word into translation_ table
	 **/
	function insertDicContents($lang_contents){
		if($lang_contents){
			foreach($lang_contents as $key => $value){
				// count English words
				$wordCount = str_word_count($value['en']);

				// only there is one english word
				if($wordCount == 1){
					$obj->source_lang = 'en';
					$obj->source_content = strval($value['en']);
					foreach($value as $lang_key => $lang_value){
						if($lang_key != 'en'){
							$obj->translation_dictionary_srl = getNextSequence();
							$obj->target_lang = $lang_key;
							$obj->target_content = $lang_value;

							$output = executeQuery('translation.insertDicContents', $obj);
							if(!$output->toBool()) { return $output;}
						}
					}
				}
			}
		}

	}

	/**
	 * @brief delete the file contents from xe_translation_contents table
	 **/
	function delContentsByFSrl($translation_file_srl){
		$obj->translation_file_srl = $translation_file_srl;
		$output = executeQuery('translation.deleteXMLContents', $obj);

		if(!$output->toBool()) {
			return $output;
		}
	}

	function _delFileBySrl($srl){
		$args->translation_file_srl = $srl;
		if($args->translation_project_srl){
			return false;
		}
		$output = executeQuery('translation.deleteContent', $args);
		if(!$output->toBool()) { return $output;}
		$output = executeQuery('translation.deleteFile', $args);
		if(!$output->toBool()) { return $output;}
		return $output;
	}

	/**
	 *	Delete file from project
	 **/
	function delFile(){
		$fSrl = Context::get('translation_file_srl');

		$checkResult = $this->_checkPermition();
		if($checkResult !== true){
			return $checkResult;
		}
		if(!$fSrl){
			return $this->stop('msg_invalid_request');
		}

		$oTransModel = &getModel('translation');
		$output = $oTransModel->getFile($fSrl);

		if(!$output){
			return $this->stop('msg_invalid_request');
		}
		$pSrl = $output->translation_project_srl;


		$output = $this->_delFileBySrl($fSrl);
		if(!$output->toBool()) {
			return $output;
		}

		$logged_info = Context::get('logged_info');
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url')
																: getNotEncodedUrl('', 'mid', $this->module_info->mid,
																					   'act', 'dispTranslationFileList',
																					   'translation_project_srl',$pSrl,
																					   'member_srl',$logged_info->member_srl);
		header('location:'.$returnUrl);
		return;
	}

	private function _voteItem($tsrl){
		$args->translation_content_srl = $tsrl;
		$oTransModel = &getModel('translation');
		$output = $oTransModel->getContentBySrlArr(array($tsrl));
		if(empty($output->data)){
			return;
		}
		$args->recommended_count = $output->data[0]->recommended_count + 1;
		$output = executeQueryArray('translation.updateVoteItem',$args);
		return $output;
	}

	private function _checkPermition(){
		if($this->module_info->module != "translation") return new Object(-1, "msg_invalid_request");
		$logged_info = Context::get('logged_info');

		//only logged user can insert content
		if(!$logged_info) return $this->stop('msg_invalid_request');

		return true;
	}

	function procVoteItem(){
		$checkResult = $this->_checkPermition();
		if($checkResult !== true){
			return $checkResult;
		}

		$tsrl = Context::get('translation_content_srl');
		$output = $this->_voteItem($tsrl);
		if(!$output->toBool()) return $output;
	}



	private function _insertContent($nodeObj){
		$data = array();
		$flag = true;
		$oTransModel = &getModel('translation');
		foreach($nodeObj->content as $key => $value){
			$srl[] = $key;
		}
		$output = $oTransModel->getContentBySrlArr($srl);

		$insertNode = clone $nodeObj;
		foreach($nodeObj->content as $nodeSrl => $contentValue){
			foreach($output->data as $obj){
				if($nodeSrl == $obj->translation_content_srl){
					$sourceObj = $obj;
					break;
				}
			}
			if(empty($sourceObj)){
				continue;
			}
			foreach($sourceObj as $key => $value){
				if($key == 'translation_project_srl'
					|| $key == 'translation_file_srl'
						|| $key == 'content_node'){
					$insertNode->$key = $value;
				}
			}
			$insertNode->translation_content_srl = getNextSequence();
			$insertNode->content = $contentValue;
			$insertNode->is_new_lang = 1;

			$o = executeQueryArray('translation.insertContents', $insertNode);
			if(!$o->toBool()){
				$flag = $o;
			}
		}
		return $flag;
	}

	function procTransInsertContent(){
		if($this->module_info->module != "translation") return new Object(-1, "msg_invalid_request");
		$logged_info = Context::get('logged_info');

		//only logged user can insert content
		if(!$logged_info) return $this->stop('msg_invalid_request');

		$var->module_srl = $this->module_info->module_srl;
		$obj = Context::getRequestVars();
		$var->lang = $obj->target_lang;
		$var->member_srl = $logged_info->member_srl;

		foreach($obj->content as $key => $value){
			$v = trim($value);
			if(!empty($v)){
				$var->content[$key] = $value;
			}
		}

		$flag = true;
		if(!empty($var->content)){

			$flag = $this->_insertContent($var);
		}
		if($flag === true){
			$msg_code = 'instert_translation';
			$returnUrl = Context::get('error_return_url') ? Context::get('error_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTransContent');
		}else{
			return $flag;
		}
		$this->setMessage($msg_code);

		header('location:'.$returnUrl);
	}

	function procTranslationDeleteProject(){

		$obj = Context::getRequestVars();
		if($obj->translation_project_srl){
			$output = executeQuery('translation.deleteProjects', $obj);
			if(!$output->toBool()) { return $output;}
			$output = executeQuery('translation.deleteFileByProject', $obj);
			if(!$output->toBool()) { return $output;}
			$output = executeQuery('translation.deleteContentByProject', $obj);
			if(!$output->toBool()) { return $output;}

			// delete project folder
			$file_folder = './files/translation_files/'.$this->module_info->module_srl.'/'.$obj->translation_project_srl;
			FileHandler::removeDir($file_folder);

			// delete cache folder
			$cache_folder = './files/cache/translation/'.$this->module_info->module_srl.'/'.$obj->translation_project_srl;
			FileHandler::removeDir($cache_folder);
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationProjectList','scope',$obj->scope);
			header('location:'.$returnUrl);
			return;
		}


	}

	function procTranslationInsertDicContent(){
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return $this->stop('msg_invalid_request');

		$obj = Context::getRequestVars();
		if(!$obj->source_lang || !$obj->source_content || !$obj->target_lang || !$obj->target_content) return $this->stop('msg_invalid_request');

		$transModel = &getModel('translation');
		$dic_info = $transModel->getDicBySource($obj->source_lang,$obj->source_content,$obj->target_lang);

		if($obj->mid) $this->module_info->mid = $obj->mid;

		if($dic_info){
			$returnUrl = getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationDictionary','source_lang', $obj->source_lang, 'target_lang', $obj->target_lang, 'translation_title',$obj->translation_title);
			$msg_code = 'Unseccessful, the source language content is already existed.';
			$this->stop($msg_code);
			header('location:'.$returnUrl);
			return;
		}

		$args->translation_dictionary_srl = getNextSequence();
		$args->source_lang = $obj->source_lang;
		$args->source_content = strtolower(strval($obj->source_content));
		$args->target_lang = $obj->target_lang;
		$args->target_content = strtolower(strval($obj->target_content));
		$args->regular = 1;

		$output = executeQuery('translation.insertDicRegularContents', $args);
		if(!$output->toBool()) { return $output;}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationDictionary','source_lang',$obj->source_lang, 'target_lang',$obj->target_lang, 'translation_title',$obj->translation_title);
			header('location:'.$returnUrl);
			return;
		}

	}

	function procTranslationSaveDicContent(){

		//only logged user can insert content
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return $this->stop('msg_invalid_request');

		$obj = Context::getRequestVars();

		if(!empty($obj->content)){
			foreach($obj->content as $translation_dictionary_srl => $target_content){
				$args->translation_dictionary_srl = $translation_dictionary_srl;
				$args->target_content = trim($target_content);
				$args->source_content = trim($obj->source_content[$translation_dictionary_srl]);
				
                if(!empty($args->source_content) && !empty($args->target_content)){
					$output = executeQueryArray('translation.updateDicContent', $args);
					if(!$output->toBool()) { return $output;}
				}
			}
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationDictionary','source_lang', $obj->source_lang, 'target_lang', $obj->target_lang, 'page', $obj->page);
			header('location:'.$returnUrl);
			return;
		}
	}

	function procTranslationDeleteDicContent(){
		$obj = Context::getRequestVars();
		if($obj->translation_dictionary_srl){
			$obj->regular = 1;
			$output = executeQuery('translation.deleteDicContents', $obj);
			if(!$output->toBool()) { return $output;}
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationDictionary');
			header('location:'.$returnUrl);
			return;
		}

	}


}