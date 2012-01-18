<?php
/**
 * @class  translationController
 * @author NHN (developers@xpressengine.com)
 * @brief  translation module Controller class
 **/

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

		if(!$obj->translation_project_srl){
			//check dueplicated project name
			$transModel = &getModel('translation');
			$pro_info = $transModel->getProjectByName($obj->project_name);
			if($pro_info){
				$returnUrl = getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationProjectList','scope',$obj->scope);
				$msg_code = 'Project insert unseccessful, the project name can not be dueplicated.';
				$this->stop($msg_code);
				header('location:'.$returnUrl);
				return;
			}

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
			if($obj->to_add_file){
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationRegFile','translation_project_srl',$obj->translation_project_srl);
			}else{
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationProjectList','scope',$obj->scope);
			}
			header('location:'.$returnUrl);
			return;
		}

	}

	/**
	 * @brief insert and update file information
	 **/
	function procTranslationInsertFile(){

		if($this->module_info->module != "translation") return new Object(-1, "msg_invalid_request");
        $logged_info = Context::get('logged_info');

		// only logged user can insert project
		if(!$logged_info) return $this->stop('msg_invalid_request');

		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_info->module_srl;
		$obj->translation_file_srl = $obj->translation_file_srl;
		$obj->translation_project_srl = $obj->translation_project_srl;
		$obj->member_srl = $logged_info->member_srl;
		$obj->file_name = $obj->uploaded_file['name'];
	    $obj->file_path = $obj->file_path;
		$obj->file_type = 'normal';

		if(!$obj->translation_project_srl) return new Object(-1, "Please select a project");

		// check the uploaded file is a xml file or not
		if($obj->upload_option == 'upload_f' && $obj->uploaded_file){
			$file_pieces = explode(".", $obj->file_name);
			$file_prefix = $file_pieces[0];
			$file_suffix = $file_pieces[1];

			// only xml file can be uploaded
			if($file_suffix != 'xml') return new Object(-1, "msg_invalid_request");

			if(in_array($file_prefix,array('lang','info','skin')))  $obj->file_type = $file_prefix;
		}
		if($obj->upload_option == "url_f" && $obj->file_url){
			$obj->file_name = basename($obj->file_url);
			$file_pieces = explode(".", $obj->file_name);

			$file_prefix = $file_pieces[0];
			$file_suffix = $file_pieces[1];

			// only xml file can be uploaded
			if($file_suffix != 'xml') return new Object(-1, "msg_invalid_request");
			if(in_array($file_prefix,array('lang','info','skin')))  $obj->file_type = $file_prefix;
		}

		$oTranslationModel = &getModel('translation');
		$xml_file =  $_FILES['uploaded_file'];

		// if the file not exists
		if(!$obj->translation_file_srl){
			$obj->translation_file_srl = getNextSequence();
			if($obj->upload_option == 'upload_f' && $obj->uploaded_file){
				$target_filename = $this->insertXmlFile($this->module_info->module_srl,$obj->translation_project_srl,$obj->translation_file_srl, $obj->file_name, $xml_file['tmp_name'] );
			}
			if($obj->upload_option == 'url_f' && $obj->file_url){
				$target_filename = $this->insertXMLFileByUrl($this->module_info->module_srl,$obj->translation_project_srl,$obj->translation_file_srl, $obj->file_name, $obj->file_url);
			}
			if(!file_exists($target_filename)) return new Object(-1, "Please uploaded the valid xml file.");
			$obj->target_file = $target_filename;

			// insert XML contents to xe_translation_contents table
			$this->insertXMLContents($obj->target_file, $obj->translation_file_srl,$obj->translation_project_srl);

			// DB query, insert file
			$output = executeQuery('translation.insertFile', $obj);
			if(!$output->toBool()) { return $output;}
			$msg_code = 'success_registed';

		}else{
			$file_info = $oTranslationModel->getFile($obj->translation_file_srl);
			// DB query, update project
			if(!$obj->uploaded_file && !$obj->file_url){
				$obj->file_name = $file_info->file_name;
				$obj->file_type = $file_info->file_type;
				$obj->target_file = $file_info->target_file;
			}else{
				if($obj->upload_option == 'upload_f' && $obj->uploaded_file){
					FileHandler::removeFile($file_info->target_file);
					$target_filename = $this->insertXmlFile($this->module_info->module_srl,$obj->translation_project_srl,$obj->translation_file_srl, $obj->file_name, $xml_file['tmp_name'] );
				}
				if($obj->upload_option == 'url_f' && $obj->file_url){
					//FileHandler::removeFile($file_info->target_file);
					$target_filename = $this->insertXMLFileByUrl($this->module_info->module_srl,$obj->translation_project_srl,$obj->translation_file_srl, $obj->file_name, $obj->file_url);
				}

				if(!file_exists($target_filename)) return new Object(-1, "Please uploaded the valid xml file.");
				$obj->target_file = $target_filename;

				// delete and insert XML contents to xe_translation_contents table
				$this->deleteXMLContents($file_info->translation_file_srl);
				$this->insertXMLContents($obj->target_file, $file_info->translation_file_srl,$obj->translation_project_srl);
			}
			$output = executeQuery('translation.updateFile', $obj);
			if(!$output->toBool()) { return $output; }
			$msg_code = 'success_updated';
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTransContent','translation_file_srl',$obj->translation_file_srl,'member_srl', $obj->member_srl);
			header('location:'.$returnUrl);
			return;
		}

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
	function deleteXMLContents($translation_file_srl){
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
		if(!$logged_info) return $this->stop('Invalid Request, please login first.');

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

}