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

			// delete and insert content node information to xe_translation_content_node table
			$this->deleteContentNodeInfo($obj->translation_file_srl);
			$this->insertContentNodeInfo($obj->translation_file_srl,$obj->translation_project_srl);

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

				// delete and insert content node information to xe_translation_content_node table
				$this->deleteContentNodeInfo($file_info->translation_file_srl);
				$this->insertContentNodeInfo($file_info->translation_file_srl,$obj->translation_project_srl);
			}
			$output = executeQuery('translation.updateFile', $obj);
			if(!$output->toBool()) { return $output; }
			$msg_code = 'success_updated';
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationFileContent','translation_file_srl',$obj->translation_file_srl);
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

		foreach($_xmlContext as $key => $val){
			if($val['attr']['xml_lang']){
				$obj->translation_content_srl = getNextSequence();
				$obj->translation_project_srl = $translation_project_srl;
				$obj->translation_file_srl = $translation_file_srl;
				$obj->content_node = $val['xpath'];
				$obj->lang = strval($val['attr']['xml_lang']);
				$obj->member_srl = $logged_info->member_srl;
				$obj->content = strval($val['value']);
				$obj->recommended_count = 0;
				$obj->is_original = 1;
				
				// DB query
				$output = executeQuery('translation.insertXMLContents', $obj);
				if(!$output->toBool()) { return $output;}
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

	/**
	 * @brief insert the file contents stats information to xe_translation_content_node table, based on the target lanuage
	 **/
	function insertContentNodeInfo($translation_file_srl, $translation_project_srl,$target_lang=null){
		$obj->translation_file_srl = $translation_file_srl;
		$oTranslationModel = &getModel('translation');
		if(!$target_lang) $target_lang = 'zh-CN'; 

		$content_nodes_list = $oTranslationModel->getFileContentNodes($obj->translation_file_srl);
		$obj->translation_project_srl = $translation_project_srl;

		$isExistLangInfo = $oTranslationModel->isExistLangInfo($obj->translation_file_srl,$target_lang);

		if($isExistLangInfo) {return;}
		
		// get supported language list
		$lang_supported_list = Context::loadLangSupported();

		foreach($content_nodes_list as $key => $content_node){
			foreach($lang_supported_list as $lang_key => $lang_value){
				if($lang_key ==  $target_lang){
					$translation_count_data = $oTranslationModel->getTranslationCount($translation_file_srl,$content_node->content_node, $lang_key);
					$translation_count = intval($translation_count_data->translation_count);
					$recommend_count = 0;
					
					$obj->translation_content_node_srl = getNextSequence();
					$obj->content_node = $content_node->content_node;
					$obj->lang = $lang_key;
					$obj->translation_count = $translation_count;
					$obj->recommend_count = $recommend_count;

					// DB query
					$output = executeQuery('translation.insertContentNodeInfo', $obj);
					if(!$output->toBool()) { return $output;}
					break;
				}
			}
		}
	}

	/**
	 * @brief delete the file contents stats information from xe_translation_content_node table
	 **/
	function deleteContentNodeInfo($translation_file_srl){
		$obj->translation_file_srl = $translation_file_srl;
		$output = executeQuery('translation.deleteContentNodeInfo', $obj);

		if(!$output->toBool()) {
			return $output;
		}
	}

	/**
	 * @brief insert new translations
	 **/
	function procTranslationInsertContent(){

		if($this->module_info->module != "translation") return new Object(-1, "msg_invalid_request");
        $logged_info = Context::get('logged_info');

		// only logged user can insert content
		if(!$logged_info) return $this->stop('msg_invalid_request');
		
		$obj = Context::getRequestVars();
		$args->translation_file_srl = $obj->translation_file_srl;
		$args->lang = $obj->target_lang;
		$args->member_srl = $obj->member_srl;
		$args->recommended_count = 0;
		$args->is_original = 0;


		foreach($obj->content as $key => $val){
			if($val){
				$args->translation_content_srl = getNextSequence();
				$args->content_node = $key;
				$args->content = strval($val);

				$oTranslationModel = &getModel('translation');
				$default_contents = $oTranslationModel->getDefaultTargetContents($args);

				if($default_contents)
					$args->is_new_lang = 0;
				else
					$args->is_new_lang = 1;
				
				$output = executeQuery('translation.insertXMLContents', $args);
				if(!$output->toBool()) { return $output;}

				$this->add('translation_content_srl', $args->translation_content_srl);
			}
		}
	
		// output success inserted/updated message
		$this->setMessage($msg_code);

	    if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispTranslationFileContent','translation_file_srl',$args->translation_file_srl,'translation_project_srl',$obj->translation_project_srl);
			header('location:'.$returnUrl);
			return;
		}
		
	}
}
?>
