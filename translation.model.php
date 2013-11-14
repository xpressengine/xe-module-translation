<?php
    /**
     * @class  translationModel
     * @author NHN (developers@xpressengine.com)
     * @brief  translation module Model class
     **/

    class translationModel extends module {

		/**
		 * @brief initialization
		 **/
		function init() {
		}

		function addNewTrans($content){
			$args->translation_content_srl = getNextSequence();
			$args->content_node = $key;
			$args->content = strval($content);

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

		/**
		 * @brief get project (member) list
		 **/
		function getProList($args, $listCount = 10, $page = 1, $pageCount = 10){
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

			$args->sort_index = $args->sort_index?$args->sort_index:'translation_project_srl';
			$args->sort_type = $args->sort_type?$args->sort_type:'asc';

			//page paramets
			$args->page = $page;
            $args->list_count = $listCount;
            $args->page_count = $pageCount;

			if($args->member_srl){
				$output = executeQueryArray('translation.getMemberProjectList',$args);
			}else{
				$output = executeQueryArray('translation.getProjectList',$args);
			}

			if(!$output->data) return null;
			else return $output;
		}

		/**
		 * @brief get member project list
		 **/
		function getMemberProjectList($args){
			if(!$args->member_srl || !$args->module_srl) return;

			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

			$args->sort_index = $args->sort_index?$args->sort_index:'translation_project_srl';
			$args->sort_type = $args->sort_type?$args->sort_type:'asc';

			$output = executeQueryArray('translation.getMemberProjectList',$args);
			if(!$output->data) return null;
			else return $output->data;
		}

		/**
		 * @brief get total project count by module srl
		 **/
		function getProTotalCount($module_srl){
			if(!$module_srl) return;
			$args->module_srl = $module_srl;

			$output = executeQuery('translation.getProjectTotalCount',$args);
			if(!$output->data) return null;
			else return $output->data;
		}

		/**
		 * @brief get project info
		 **/
		function getProject($translation_project_srl){
			if(!$translation_project_srl) return;

			$args->translation_project_srl = $translation_project_srl;
			$output = executeQuery('translation.getProject',$args);

			if(!$output->data) return null;
			else return $output->data;
		}

		/**
		 * @brief get file list by project srl
		 **/
		function getProFile($args, $listCount = 5, $page = 1, $pageCount = 10){
			if(!$args->translation_project_srl || !$args->module_srl) return;

			// set sorting variables
			$args->sort_index = $args->sort_index?$args->sort_index:'translation_file_srl';
			$args->sort_type = $args->sort_type?$args->sort_type:'asc';
			$args->lang = $args->lang?$args->lang:'en';

			//page paramets
			$args->page = $page;
            $args->list_count = $listCount;
            $args->page_count = $pageCount;

			$output = executeQueryArray('translation.getProjectFileList',$args);
			if(!$output->data) return null;
			else return $output;
		}

		/**
		 * @brief get file count by project srl
		 **/
		function getProFileTotalCount($translation_project_srl){
			if(!$translation_project_srl) return;
			$args->translation_project_srl = $translation_project_srl;

			$output = executeQuery('translation.getProjectFileTotalCount',$args);
			if(!$output->data) return null;
			else return $output->data;
		}

		/**
		 * @brief get file info
		 **/
		function getFile($translation_file_srl){
			if(!$translation_file_srl) return;

			$args->translation_file_srl = $translation_file_srl;
			$output = executeQuery('translation.getFile',$args);

			if(!$output->data) return null;
			else return $output->data;
		}

		function getFileInfo($fileSrl = null, $projSrl = null){
			if(empty($fileSrl) && empty($projSrl)){
				return array();
			}
			//paramets
			if(!empty($fileSrl)){
				$args->fileSrl = $fileSrl;
			}
			if(!empty($projSrl)){
				$args->projSrl = $projSrl;
			}
			$output = executeQuery('translation.getFileInfo',$args);

			if(count($output->data) == 1){
				$output->data = array($output->data);
			}
			return $output;
		}

		function getTargetList($contentNode = array(), $targetLang = 'zh-Ch', $fileSrl=null, $projSrl = null){
			if(empty($contentNode)){
				return array();
			}
			$args->contentNode = $contentNode;

			//paramets
			if(!empty($fileSrl)){
				$args->fileSrl = $fileSrl;
			}
			if(!empty($projSrl)){
				$args->prjSrl = $projSrl;
			}

			$args->targetLang = $targetLang;

			$output = executeQuery('translation.getTargetLangList',$args);
			if(!is_array($output->data)){
				$output->data = array($output->data);
			}
			return $output;
		}

		function getSourceList($sourceLang = 'ko', $targetLang = 'zh-Ch', $fileSrl=null, $projSrl = null,
								$sortType = null,	$listCount = 1000, $page = 1, $pageCount = 20){
			//paramets
			if(!empty($fileSrl)){
				$args->fileSrl = $fileSrl;
			}
			if(!empty($projSrl)){
				$args->prjSrl = $projSrl;
			}
			$args->sourceLang = $sourceLang;
			$args->targetLang = $targetLang;
			$args->isOriginal = 1;

			//page paramets
			$args->page = $page;
            $args->list_count = $listCount;
            $args->page_count = $pageCount;
            if($sortType == 'translation_count'){
        		$output = executeQuery('translation.getSourceLangList',$args);
        	}else{
        		$output = executeQuery('translation.getSourceListByRev',$args);
        	}
            return $output;
		}

		/**
		 * @brief get content based on the source lang
		 **/
		function getSourceContents($source_lang, $target_lang, $translation_file_srl){

			if(!$translation_file_srl) return;

			$args->translation_file_srl = $translation_file_srl;
			$args->lang = $source_lang;
			$args->is_original = 1;

			$output = executeQuery('translation.getSourceLangList',$args);
			if(!$output->toBool()) {return $output;}

			$source_data = $output->data;
			$source_contents = array();

			if($source_data){
				foreach($source_data as $key => $val){
					$source_contents[$key]['source'] = $val;
					$target_contents = $this->getTargetContents($val->content_node,$target_lang,$translation_file_srl);
					$source_contents[$key]['target'] = $target_contents;
					$target_count = count($target_contents);
					$source_contents[$key]['target_count'] = $target_count;
				}
			}
			$this->multi2dSortAsc($source_contents,'target_count');

			return $source_contents;
		}

		/**
		 * @brief get target lang contens
		 **/
		function getTargetContents($content_node,$target_lang,$translation_file_srl){
			$obj->content_node = $content_node;
			$obj->lang = $target_lang;
			$obj->translation_file_srl = $translation_file_srl;

			$output = executeQuery('translation.getTargetLangFileContents', $obj);

			return $output->data;
		}

		function getFileAllContents($translation_file_srl){
			if(!$translation_file_srl) return;
			$content_nodes = $this->getFileContentNodes($translation_file_srl);

			// get supported language list
			$lang_supported_list = Context::loadLangSupported();

			$valueArr = array();

			if(is_array($content_nodes)){
				foreach($content_nodes as $key => $val){
					$obj->content_node = $val->content_node;
					$obj->translation_file_srl = $translation_file_srl;

					foreach($lang_supported_list as $lang_key => $lang_val){
						$obj->lang = $lang_key;
						$value = $this->getRecommendValue($obj);

						if($value){
							$vArr['content_node'] = $obj->content_node;
							$vArr['lang'] = $obj->lang;
							$vArr['content'] = $value->content;
							$vArr['is_new_lang'] = $value->is_new_lang;

							array_push($valueArr, $vArr);
						}
					}
				}
			}

			$file_info = $this->getFile($translation_file_srl);
			$oXMLContext = new XMLContext($file_info->target_file, "en");

			$xmlContents = $oXMLContext->getXmlFile($valueArr);
			return $xmlContents;
		}

		function getFileContentNodes($translation_file_srl){
			if(!$translation_file_srl) return;
			$args->translation_file_srl = $translation_file_srl;
			$args->is_original = 1;

			$output = executeQueryArray('translation.getFileContentNodes',$args);
			if(!$output->toBool()) {return $output;}

			return $output->data;
		}

		function getRecommendValue($obj){
			$args->translation_file_srl = $obj->translation_file_srl;
			$args->content_node = $obj->content_node;
			$args->lang = $obj->lang;

			//$output = executeQuery('translation.getMaxRecommendCount',$args);
			//if(!$output->toBool()) {$args->max_recomment_count = 0;} else {$args->max_recomment_count = intval($output->data->max_recommended_count);}

			$output = executeQuery('translation.getRecommendValue',$args);
			if(!$output->toBool()) {return null;}

			return $output->data;
		}

		function getDefaultTargetContents($args){
			if(!$args->translation_file_srl || !$args->content_node || !$args->lang) return;

			$obj->translation_file_srl = $args->translation_file_srl;
			$obj->content_node = $args->content_node;
			$obj->lang = $args->lang;
			$obj->is_original = 1;
			$output = executeQuery('translation.getDefaultTargetContents',$obj);

			if(!$output->toBool()) {return null;}
			return $output->data;
		}

		function getTranslationCount($translation_file_srl,$content_node,$lang){
			if(!$translation_file_srl || !$content_node || !$lang) return;

			$obj->translation_file_srl = $translation_file_srl;
			$obj->content_node = $content_node;
			$obj->lang = $lang;

			$output = executeQuery('translation.getTranslationCount',$obj);

			if(!$output->toBool()) {return null;}
			return $output->data;
		}

		function getRecommendedCount($translation_file_srl,$content_node,$lang){
			if(!$translation_file_srl || !$content_node || !$lang) return;

			$obj->translation_file_srl = $translation_file_srl;
			$obj->content_node = $content_node;
			$obj->lang = $lang;

			$output = executeQuery('translation.getRecommendedCount',$obj);

			if(!$output->toBool()) {return null;}
			return $output->data;
		}

		// Statistic Information
		function getModuleTranslationTotalCount($module_srl){
			if(!$module_srl) return;

			$obj->module_srl = $module_srl;

			$output = executeQueryArray('translation.getModuleTranslationTotalCount',$obj);
			if(!$output->toBool()) {return 0;}

			$total_count = 0;
			$count_list = $output->data;
			if($count_list){
				foreach($count_list as $key => $count)
					$total_count += intval($count->content_node_count);
			}

			return $total_count;
		}

		function getModuleTranslationLangCount($module_srl,$lang,$approved = false){
			if(!$module_srl || !$lang) return;

			$obj->module_srl = $module_srl;
			$obj->lang = $lang;

			if($approved){
				$obj->recommended_count = 1;
				$output = executeQueryArray('translation.getModuleTranslationLangApprovedCount',$obj);
			}else{
				$output = executeQueryArray('translation.getModuleTranslationLangCount',$obj);
			}

			if(!$output->toBool()) {return 0;}

			$total_count = 0;
			$count_list = $output->data;

			if($count_list){
				foreach($count_list as $key => $count)
					$total_count += intval($count->content_node_count);
			}

			return $total_count;
		}

		function getModuleLangLastUpdate($module_srl, $lang){
			if(!$module_srl || !$lang) return;

			$obj->module_srl = $module_srl;
			$obj->lang = $lang;

			$output = executeQuery('translation.getModuleLangLastUpdate',$obj);
			if(!$output->toBool()) {return $output;}

			return $output->data;

			$output = executeQueryArray('translation.getModuleLangLatestUpdate',$obj);
			if(!$output->toBool()) {return 0;}

		}


		function getProjectTranslationTotalCount($translation_project_srl){
			if(!$translation_project_srl) return;

			$obj->translation_project_srl = $translation_project_srl;

			$output = executeQueryArray('translation.getProjectTranslationTotalCount',$obj);
			if(!$output->toBool()) {return 0;}

			$total_count = 0;
			$count_list = $output->data;
			if($count_list){
				foreach($count_list as $key => $count)
					$total_count += intval($count->content_node_count);
			}

			return $total_count;
		}

		function getProjectLangTranslationCount($translation_project_srl,$lang,$approved = false){
			if(!$translation_project_srl||!$lang) return;

			$obj->translation_project_srl = $translation_project_srl;
			$obj->lang = $lang;

			if($approved){
				$obj->recommended_count = 1;
				$output = executeQueryArray('translation.getProjectLangTranslationApprovedCount',$obj);
			}else{
				$output = executeQueryArray('translation.getProjectLangTranslationCount',$obj);
			}

			if(!$output->toBool()) {return 0;}

			$total_count = 0;
			$count_list = $output->data;
			if($count_list){
				foreach($count_list as $key => $count)
					$total_count += intval($count->translation_count);
			}

			return $total_count;
		}

		function getProjectLastUpdate($translation_project_srl, $lang = null){
			if(!$translation_project_srl) return;

			$obj->translation_project_srl = $translation_project_srl;

			if($lang){
				$obj->lang = $lang;
				$output = executeQuery('translation.getProjectLangLastUpdate',$obj);
			}else{
				$output = executeQuery('translation.getProjectLastUpdate',$obj);
			}

			if(!$output->toBool()) {return $output;}

			return $output->data;
		}

		function getFileTransTotalCount($translation_file_srl){
			if(!$translation_file_srl) return;

			$obj->translation_file_srl = $translation_file_srl;

			$output = executeQueryArray('translation.getFileTransTotalCount',$obj);
			if(!$output->toBool()) {return 0;}

			$total_count = 0;
			$count_list = $output->data;
			if($count_list){
				foreach($count_list as $key => $count)
					$total_count += intval($count->content_node_count);
			}
			return $total_count;
		}

		function getFileLangTransCount($translation_file_srl,$lang,$approved = false){
			if(!$translation_file_srl||!$lang) return;

			$obj->translation_file_srl = $translation_file_srl;
			$obj->lang = $lang;

			if($approved){
				$obj->recommended_count = 1;
				$output = executeQuery('translation.getFileLangTransApprovedCount',$obj);
			}else{
				$output = executeQuery('translation.getFileLangTransCount',$obj);
			}

			if(!$output->toBool()) {return 0;}

			$total_count = intval($output->data->translation_count);

			return $total_count;
		}

		function getFileLastUpdate($translation_file_srl, $lang = 'en'){
			if(!$translation_file_srl) return;

			$obj->translation_file_srl = $translation_file_srl;
			$obj->lang = $lang;
			$output = executeQuery('translation.getFileLangLastUpdate',$obj);

			if(!$output->toBool()) {return $output;}

			return $output->data;
		}


		function getTranslatorRanking($module_srl,$limit_count = 5){
			if(!$module_srl) return;

			$obj->module_srl = $module_srl;
			$obj->limit_count = $limit_count;

			$output = executeQueryArray('translation.getTranslatorRanking',$obj);
			if(!$output->toBool()) {return 0;}

			return $output->data;

		}

		function getReviewerRanking($module_srl,$limit_count = 5){
			if(!$module_srl) return;

			$obj->module_srl = $module_srl;
			$obj->limit_count = $limit_count;
			$obj->recommended_count = 1;

			$output = executeQueryArray('translation.getReviewerRanking',$obj);
			if(!$output->toBool()) {return 0;}

			return $output->data;

		}

		function multi2dSortAsc(&$arr, $key){
			$sort_col = array();
			foreach ($arr as $sub) $sort_col[] = $sub[$key];
			array_multisort($sort_col, $arr);
		}

		function writeFile($contents, $file_name){

			if(!file_exists($file_name)){
				$fp = fopen($file_name,"wb");
				fclose($fp);}

				$str = file_get_contents($file_name);
				$fp = fopen($file_name,"wb");
				fwrite($fp,$contents);

				fclose($fp);
		}

		function downloadFile($filepath, $filename){
			if(file_exists($filepath)){
				if ($fd = fopen ($filepath, "r")) {
					$fsize = filesize($filepath);
					$path_parts = pathinfo($filepath);

					header("Content-type: text/xml");
			        header("Content-Disposition: attachment; filename=\"$filename\"");
			        header("Expires: 0");
			        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			        header("Cache-Control: no-store, no-cache, must-revalidate");
					header("Cache-Control: public");
			        header("Pragma: public");
			        header("Content-Length: ".$fsize);

					ob_end_clean();
					while(!feof($fd)) {
						$buffer = fread($fd, (1*(1024*1024)));
						echo $buffer;
						flush();
						ob_flush();
					}
				}
				fclose ($fd);
			}
		}

		function procGetDicInfo(){
			$sourceLang = Context::get('translation_content_srl');

			//get dictionary content
			$tsrl = Context::get('translation_content_srl');
			$targetLang = Context::get('target_lang');
			$output = $this->getContentBySrlArr(array($tsrl));

			if(!$output->data){
				return;
			}

			//only english source can have dictionary reference
			if($output->data[0]->lang == 'en'){
				$dicList = $this->getDicList(array($output->data[0]->content),$targetLang);
			}
			Context::set('dicList', $dicList);

			$module_info = $this->module_info;
            $module_path = './modules/'.$module_info->module.'/';
			$skin_path = $module_path.'skins/'.$module_info->skin.'/';
			if(!$module_info->skin || !is_dir($skin_path)) {
				$skin_path = $module_path.'skins/xe_translation_official/';
			}

			$oTemplateHandler = &TemplateHandler::getInstance();
            $result = new Object();
            $result->add('html', $oTemplateHandler->compile($skin_path, 'dic_content.html'));
            $this->add('html', $result->get('html'));
		}

		function getDicList($nodeArr, $targetLang, $sourceLangArr = 'en'){
			$wordArr = array();
			foreach($nodeArr as $srl => &$content){
				$content = str_word_count($content, 1);
				$wordArr = array_merge($wordArr, $content);
			}
			$wordArr = array_unique($wordArr);
			if(empty($wordArr)){
				return;
			}
			$args->source_content = $wordArr;
			$args->source_lang = $sourceLangArr;
			$args->target_lang = $targetLang;
			$output = executeQueryArray('translation.getDicList',$args);
			if(empty($output->data)){
				return;
			}
			$refer = array();
			foreach($output->data as $key => $obj){
				foreach($nodeArr as $srl => $contArr){
					if(!in_array($obj->source_content,$contArr)){
						continue;
					}
					$refer = array_merge($refer,array($obj->source_content => $obj->target_content));
				}
			}
			return $refer;
		}

		function getContentBySrlArr($srlArr){
			$args->translation_content_srl = $srlArr;
			$output = executeQueryArray('translation.getContentList',$args);
			return $output;
		}

		function getCsvContent($args){
		    $output = $this->getContent($args);
		    $args->lang = $args->source_lang;
		    $output1 = $this->getContent($args);
		    if($output1->data){
		        $output->data = array_merge($output1->data, $output->data);
		    }
		    return $output;
		}

		function getContent($args){
			$output = executeQueryArray('translation.getContent',$args);
			return $output;
		}

		function getProjInfoBySrl($projSrlArr = array()){
		    if(!is_array($projSrlArr)){
                return null;
		    }
		    $args->translation_project_srl = $projSrlArr;
		    $output = executeQuery('translation.getProjInfo',$args);
		    return $output;
		}

		function getDicContent($sourceLang = "en", $targetLang = "zh-CN",$listCount = 10, $page = 1, $pageCount = 10, $s_keyword = ""){
			$args->source_lang = $sourceLang;
			$args->target_lang = $targetLang;
			$args->regular = 1;

			$args->page = $page;
			$args->listCount = $listCount;
            $args->page_count = $pageCount;
			$args->s_keyword = $s_keyword;

			$output = executeQueryArray('translation.getDicContent',$args);
			if(!$output->toBool()) {return $output;}

			return $output;
		}

		function getDicBySource($sourceLang, $sourceContent, $targetLang){
			if(!$sourceLang||!$sourceContent||!$targetLang) return;
			$args->source_lang = $sourceLang;
			$args->source_content = $sourceContent;
            $args->target_lang = $targetLang;
			$args->regular = 1;

			$output = executeQueryArray('translation.getDicBySource',$args);
			if(!$output->toBool()) {return $output;}

			return $output->data;
		}

		function getDicBySrl($translation_dictionary_srl){
			if(!$translation_dictionary_srl) return;
			$args->translation_dictionary_srl = $translation_dictionary_srl;
			$args->regular = 1;

			$output = executeQuery('translation.getDicBySrl',$args);
			if(!$output->toBool()) {return $output;}

			return $output->data;
		}

		// create a Zip file for a directory
		function createZip($source_dir, $destination_zip)
		{
			if (!extension_loaded('zip') || !file_exists($source_dir)) {
				return false;
			}

			$zip = new ZipArchive();
			if (!$zip->open($destination_zip, ZIPARCHIVE::CREATE)) {
				return false;
			}

			$source_dir = str_replace('\\', '/', realpath($source_dir));

			if (is_dir($source_dir) === true)
			{
				$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source_dir), RecursiveIteratorIterator::SELF_FIRST);

				foreach ($files as $file)
				{
					$file = str_replace('\\', '/', realpath($file));

					if (is_dir($file) === true)
					{
						$zip->addEmptyDir(str_replace($source_dir . '/', '', $file . '/'));
					}
					else if (is_file($file) === true)
					{
						$zip->addFromString(str_replace($source_dir . '/', '', $file), file_get_contents($file));
					}
				}
			}
			else if (is_file($source_dir) === true)
			{
				$zip->addFromString(basename($source_dir), file_get_contents($source_dir));
			}

			return $zip->close();
		}

		function downloadZipFile($filepath, $filename){
			if(file_exists($filepath)){
				if ($fd = fopen ($filepath, "r")) {
					$fsize = filesize($filepath);
					$path_parts = pathinfo($filepath);

					header("Content-type: application/zip");
			        header("Content-Disposition: attachment; filename=\"$filename\"");
			        header("Expires: 0");
			        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			        header("Cache-Control: no-store, no-cache, must-revalidate");
					header("Cache-Control: public");
			        header("Pragma: public");
			        header("Content-Length: ".$fsize);

					ob_end_clean();
					while(!feof($fd)) {
						$buffer = fread($fd, (1*(1024*1024)));
						echo $buffer;
						flush();
						ob_flush();
					}
				}
				fclose ($fd);
			}
		}

	}
?>
