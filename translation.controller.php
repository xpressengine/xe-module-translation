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
			$obj->translation_project_srl = getNextSequence();
			// DB query, insert project
			$output = executeQuery('translation.insertProject', $obj);
			$msg_code = 'success_registed';
		}else{
			$obj->translation_project_srl = $obj->translation_project_srl;
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
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationProjectList','member_srl',$obj->member_srl);
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
		$obj->translation_file_srl = $obj->translation_file_srl;
		$obj->translation_project_srl = $obj->translation_project_srl;
		$obj->member_srl = $logged_info->member_srl;
		$obj->file_name = $obj->uploaded_file['name'];
	    $obj->file_path = $obj->file_path;
		$obj->file_type = 'normal';

		// check the uploaded file is a xml file or not
		if($obj->uploaded_file){
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
			$target_filename = $this->insertXmlFile($this->module_info->module_srl,$obj->translation_project_srl,$obj->translation_file_srl, $obj->file_name, $xml_file['tmp_name'] );
			if(!file_exists($target_filename)) return new Object(-1, "msg_invalid_request");
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
			if(!$obj->uploaded_file){
				$obj->file_name = $file_info->file_name;
				$obj->file_type = $file_info->file_type;
				$obj->target_file = $file_info->target_file;
			}else{
				FileHandler::removeFile($file_info->target_file);
				$target_filename = $this->insertXmlFile($this->module_info->module_srl,$obj->translation_project_srl,$obj->translation_file_srl, $obj->file_name, $xml_file['tmp_name'] );
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
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTransContent','translation_file_srl',$obj->translation_file_srl);
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

	private function _voteItem($tsrl){
		$args->translation_content_srl = $tsrl;
		$oTransModel = &getModel('translation');
		$output = $oTransModel->getContentBySrlArr(array($tsrl));
		if(empty($output->data)){
			return;
		}
		$args->recommended_count= $output->data[0]->recommended_count + 1;
		$output = executeQueryArray('translation.updateVoteItem',$args);
		return $output;
	}

	function procVoteItem(){
		if($this->module_info->module != "translation") return new Object(-1, "msg_invalid_request");
		$logged_info = Context::get('logged_info');
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
				if($key == 'translation_project_srl' || $key == 'translation_file_srl' || $key=='content_node'){
					$insertNode->$key = $value;
				}
			}
			$insertNode->translation_content_srl = getNextSequence();
			$insertNode->content = $contentValue;

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
			$msg_code = 'success_registed';
			$returnUrl = Context::get('error_return_url') ? Context::get('error_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTransContent');
		}else{
			return $flag;
		}
		$this->setMessage($msg_code);

		header('location:'.$returnUrl);
	}


}