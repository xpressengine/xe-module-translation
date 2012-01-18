<?php
    /**
     * @class  translationView
     * @author NHN (developers@xpressengine.com)
     * @brief  translation module View class
     **/

    class translationView extends translation {
        /**
         * @brief initialize translation view class.
         **/
		function init() {
           /**
             * get skin template_path
             * if it is not found, default skin is xe_contact
             **/
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'xe_translation_official';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }

			$lang_supported_list = Context::loadLangSupported();
			Context::set('lang_supported_list',$lang_supported_list);

			//set view 's default list count/page count
			$this->listCount = $this->module_info->list_count?intval($this->module_info->list_count):10;
			$this->pageCount = $this->module_info->page_count?intval($this->module_info->page_count):10;
            $this->setTemplatePath($template_path);
		}



        /**
         * @brief display translation index page
         **/
        function dispTranslationIndex() {

			$oTransModel = &getModel('translation');

			// statistics inforamtion based on various languages
			$lang_supported_list = Context::loadLangSupported();
			$lang_list = array();
			$mTransTotalCount = $oTransModel->getModuleTranslationTotalCount($this->module_info->module_srl);
			$sort_lang_target = Context::get('sort_lang_target');
			$sort_lang_type = Context::get('sort_lang_type')?Context::get('sort_lang_type'):'asc';

			if($lang_supported_list){
				foreach($lang_supported_list as $lang_key => $lang){
					$mTransCount = $oTransModel->getModuleTranslationLangCount($this->module_info->module_srl,$lang_key);
					$mAppCount = $oTransModel->getModuleTranslationLangCount($this->module_info->module_srl,$lang_key,true);
					$mNoAppCount = $mTransCount - $mAppCount;
					$mNoTransCount = $mTransTotalCount - $mTransCount;
					$mLangLastUpdate = $oTransModel->getModuleLangLastUpdate($this->module_info->module_srl,$lang_key);

					$lang_list[$lang_key]['item']->value = $lang;
					if($mTransTotalCount){
						$lang_list[$lang_key]['item']->perc_approved = number_format($mAppCount/$mTransTotalCount * 100,1);
						$lang_list[$lang_key]['item']->perc_notApproved = number_format($mNoAppCount/$mTransTotalCount * 100,1);
						$lang_list[$lang_key]['item']->perc_notTranslated = number_format($mNoTransCount/$mTransTotalCount * 100,1);
						$lang_list[$lang_key]['item']->last_update = zdate($mLangLastUpdate->last_update,"Y.m.d");
					}else{
						$lang_list[$lang_key]['item']->no_files = true;
					}
					if($sort_lang_target == 'last_update')
						$lang_list[$lang_key]['sort_index'] = $mLangLastUpdate->last_update;
				}
			}

			if($sort_lang_target && $sort_lang_type == 'asc'){
				$this->multi2dSortAsc($lang_list,'sort_index');
				Context::set('sort_lang_type','desc');
			}
			if($sort_lang_target && $sort_lang_type == 'desc'){
				$this->multi2dSortDesc($lang_list,'sort_index');
				Context::set('sort_lang_type','asc');
			}


			// statistics inforamtion based on various projects
			$obj->sort_index = Context::get('sort_pro_target');
			$obj->sort_type = Context::get('sort_pro_type')?Context::get('sort_pro_type'):'asc';
			$obj->module_srl = $this->module_info->module_srl;
			if($obj->sort_type == 'asc'){
				Context::set('sort_pro_type','desc');
			}else{
				Context::set('sort_pro_type','asc');
			}
			$p_list_count = 10;
			$projectList = $oTransModel->getProList($obj,$p_list_count);
			$projectList = $projectList->data;
			$project_list = array();

			if($projectList){
				foreach($projectList as $project_key => $project){
					$pTransTotalCount = $oTransModel->getProjectTranslationTotalCount($project->translation_project_srl);
					$pTransCount = $oTransModel->getProjectLangTranslationCount($project->translation_project_srl,$this->module_info->default_lang);
					$pAppCount = $oTransModel->getProjectLangTranslationCount($project->translation_project_srl, $this->module_info->default_lang, true);
					$pNoAppCount = $pTransCount - $pAppCount;
					$pNoTransCount = $pTransTotalCount - $pTransCount;

					$project_list[$project_key]->translation_project_srl = $project->translation_project_srl;
					$project_list[$project_key]->project_name = $project->project_name;
					if($pTransTotalCount){
						$project_list[$project_key]->perc_approved = number_format($pAppCount/$pTransTotalCount * 100,1);
						$project_list[$project_key]->perc_notApproved = number_format($pNoAppCount/$pTransTotalCount * 100,1);
						$project_list[$project_key]->perc_notTranslated = number_format($pNoTransCount/$pTransTotalCount * 100,1);
						$project_list[$project_key]->last_update = zdate($project->last_update,"Y.m.d");
					}else{
						$project_list[$project_key]->no_files = true;
					}
				}
			}

			// translator ranking
			$translatorList = $oTransModel->getTranslatorRanking($this->module_info->module_srl);
			$translator_list = array();

			if($translatorList){
				foreach($translatorList as $key => $translator){
					$translator_list[$key]->member_srl = $translator->member_srl;
					$translator_list[$key]->nick_name = $translator->nick_name;
					$translator_list[$key]->translation_count = $translator->translation_count?$translator->translation_count:0;
				}
			}

			// recommend ranking
			$reviewerList = $oTransModel->getReviewerRanking($this->module_info->module_srl);
			$reviewer_list = array();

			if($reviewerList){
				foreach($reviewerList as $key => $reviewer){
					$reviewer_list[$key]->member_srl = $reviewer->member_srl;
					$reviewer_list[$key]->nick_name = $reviewer->nick_name;
					$reviewer_list[$key]->total_recommended_count = $reviewer->total_recommended_count?$reviewer->total_recommended_count:0;
				}
			}

			Context::set('lang_list',$lang_list);
			Context::set('project_list',$project_list);
			Context::set('translator_list',$translator_list);
			Context::set('reviewer_list',$reviewer_list);

			// set template_file to be index.html
            $this->setTemplateFile('index');
        }

        /**
         * @brief display translation register project page
         **/
        function dispTranslationRegProject() {

			$translation_project_srl = Context::get('translation_project_srl');
			$module_srl = Context::get('module_srl');
			$mid = Context::get('mid');

			$oTranslationModel =  &getModel('translation');
			$project_info = $oTranslationModel->getProject($translation_project_srl);

			Context::set('project_info', $project_info);
			Context::set('translation_project_srl', $translation_project_srl);
			Context::set('module_srl', $module_srl);
			Context::set('mid', $mid);

			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'xe_translation_official';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }

			$oTemplateHandler = &TemplateHandler::getInstance();
            $result = new Object();
            $result->add('page', $oTemplateHandler->compile($template_path, 'register_project.html'));
			$this->add('page', $result->get('page'));
        }

        /**
         * @brief display translation (project) file list page
         **/
		function dispTransProLangInfo() {

			$oTransModel = &getModel('translation');
			$translation_project_srl = Context::get('translation_project_srl');
			$project_info = $oTransModel->getProject($translation_project_srl);

			$sort_target = Context::get('sort_target');
			$sort_type = Context::get('sort_type')?Context::get('sort_type'):'asc';

			// get supported language list
			$lang_supported_list = Context::loadLangSupported();
			$p_lang_list = array();

			// project translation total count
			$pTransTotalCount = $oTransModel->getProjectTranslationTotalCount($translation_project_srl);

			if($lang_supported_list){
				foreach($lang_supported_list as $lang_key => $lang){
					$pTransCount = $oTransModel->getProjectLangTranslationCount($translation_project_srl,$lang_key);
					$pAppCount = $oTransModel->getProjectLangTranslationCount($translation_project_srl,$lang_key, true);
					$pNoAppCount = $pTransCount - $pAppCount;
					$pNoTransCount = $pTransTotalCount - $pTransCount;
					$pLastUpdate = $oTransModel->getProjectLastUpdate($translation_project_srl,$lang_key);

					$p_lang_list[$lang_key]['item']->translation_project_srl = $translation_project_srl;
					$p_lang_list[$lang_key]['item']->name = $lang;
					$p_lang_list[$lang_key]['item']->trans_count = $pTransCount;
					$p_lang_list[$lang_key]['item']->no_approved_count = $pNoAppCount;

					if($pTransTotalCount){
						$p_lang_list[$lang_key]['item']->perc_approved = number_format($pAppCount/$pTransTotalCount * 100,1);
						$p_lang_list[$lang_key]['item']->perc_notApproved = number_format($pNoAppCount/$pTransTotalCount * 100,1);
						$p_lang_list[$lang_key]['item']->perc_notTranslated = number_format($pNoTransCount/$pTransTotalCount * 100,1);
						$p_lang_list[$lang_key]['item']->last_update = zdate($pLastUpdate->last_update,"Y.m.d");
					}else{
						$p_lang_list[$lang_key]['item']->no_files = true;
					}
					if($sort_target == 'last_update')
						$p_lang_list[$lang_key]['sort_index'] = $pLastUpdate->last_update;
				}
			}


			if($sort_target && $sort_type == 'asc'){
				$this->multi2dSortAsc($p_lang_list,'sort_index');
				Context::set('sort_type','desc');
			}
			if($sort_target && $sort_type == 'desc'){
				$this->multi2dSortDesc($p_lang_list,'sort_index');
				Context::set('sort_type','asc');
			}

			Context::set('p_lang_list',$p_lang_list);
			Context::set('project_info',$project_info);

			// set template_file to be register_project.html
            $this->setTemplateFile('project_info_lang');
		}

        /**
         * @brief display translation (member) project list page
         **/
		function dispTranslationProjectList() {

			$select_lang = Context::get('select_lang')?Context::get('select_lang'):$this->module_info->default_lang;

			// sorting variables
			$obj->sort_index = Context::get('sort_target');
			if(Context::get('sort_type')){
				$obj->sort_type = Context::get('sort_type');
			}else{
				$obj->sort_type = 'asc';
				Context::set('sort_type','asc');
			}
			if($obj->sort_type == 'asc'){
				Context::set('sortType','desc');
			}else if($obj->sort_type == 'desc'){
				Context::set('sortType','asc');
			}

			$oTransModel = &getModel('translation');
			$obj->module_srl = $this->module_info->module_srl;

			//page parameter
			$listCount = $this->listCount;
			$pageCount = $this->pageCount;
			$page = Context::get('page');
            if(!$page) Context::set('page', $page=1);

			// get member_srl
			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl;
			$p_scope = Context::get('scope');
			if($member_srl && $p_scope == 'myProjects'){
				$obj->member_srl = $member_srl;
			}

			$projectList =  $oTransModel->getProList($obj, $listCount, $page, $pageCount);
			Context::set('page_navigation', $projectList->page_navigation);
			$projectList = $projectList->data;

			$project_list = array();
			if($projectList){
				foreach($projectList as $project_key => $project){
					$pTransTotalCount = $oTransModel->getProjectTranslationTotalCount($project->translation_project_srl);
					$pTransCount = $oTransModel->getProjectLangTranslationCount($project->translation_project_srl,$select_lang);
					$pAppCount = $oTransModel->getProjectLangTranslationCount($project->translation_project_srl,$select_lang, true);
					$pNoAppCount = $pTransCount - $pAppCount;
					$pNoTransCount = $pTransTotalCount - $pTransCount;
					$args->translation_project_srl = $project->translation_project_srl;
					$args->module_srl = $this->module_info->module_srl;

					$project_list[$project_key]->translation_project_srl = $project->translation_project_srl;
					$project_list[$project_key]->project_name = $project->project_name;
					$project_list[$project_key]->file_count = $project->files_count;
					$project_list[$project_key]->trans_count = $pTransCount;
					$project_list[$project_key]->no_approved_count = $pNoAppCount;
					$project_list[$project_key]->member_srl = $project->member_srl;
					if($pTransTotalCount){
						$project_list[$project_key]->perc_approved = number_format($pAppCount/$pTransTotalCount * 100,1);
						$project_list[$project_key]->perc_notApproved = number_format($pNoAppCount/$pTransTotalCount * 100,1);
						$project_list[$project_key]->perc_notTranslated = number_format($pNoTransCount/$pTransTotalCount * 100,1);
						$project_list[$project_key]->last_update = zdate($project->last_update,"Y.m.d");
					}else{
						$project_list[$project_key]->no_files = true;
					}

				}
			}

			Context::set('select_lang',$select_lang);
			Context::set('project_list',$project_list);

			// set template_file to be project_list.html
			$this->setTemplateFile('project_list');
		}

        /**
         * @brief display translation (project) file list page
         **/
		function dispTranslationFileList() {

			$oTransModel =  &getModel('translation');

			// get member_srl
            $translation_project_srl = Context::get('translation_project_srl');
			$select_lang = Context::get('select_lang')?Context::get('select_lang'):$this->module_info->default_lang;
			$obj->module_srl = $this->module_info->module_srl;

			// get project inforamtion
			$project_info = $oTransModel->getProject($translation_project_srl);

			if($project_info){
				$pTransTotalCount = $oTransModel->getProjectTranslationTotalCount($project_info->translation_project_srl);
				$pTransCount = $oTransModel->getProjectLangTranslationCount($project_info->translation_project_srl,$select_lang);
				$pAppCount = $oTransModel->getProjectLangTranslationCount($project_info->translation_project_srl,$select_lang, true);
				$pNoAppCount = $pTransCount - $pAppCount;
				$pNoTransCount = $pTransTotalCount - $pTransCount;
				$pLastUpdate = $oTransModel->getProjectLastUpdate($project_info->translation_project_srl,$select_lang);

				$project_info->trans_count = $pTransCount;
				$project_info->no_approved_count = $pNoAppCount;
				if($pTransTotalCount){
					$project_info->perc_approved = number_format($pAppCount/$pTransTotalCount * 100,1);
					$project_info->perc_notApproved = number_format($pNoAppCount/$pTransTotalCount * 100,1);
					$project_info->perc_notTranslated = number_format($pNoTransCount/$pTransTotalCount * 100,1);
					$project_info->last_update = zdate($pLastUpdate->last_update,"Y.m.d");
				}else{
					$project_info->no_files = true;
				}
			}

			// get files inforamtion
			$obj->module_srl = $this->module_info->module_srl;
			$obj->translation_project_srl = $translation_project_srl;

			// sorting variables
			$obj->sort_index = Context::get('sort_target');
			$obj->sort_type = Context::get('sort_type')?Context::get('sort_type'):'asc';

			if(Context::get('sort_type')){
				$obj->sort_type = Context::get('sort_type');
			}else{
				$obj->sort_type = 'asc';
				Context::set('sort_type','asc');
			}
			if($obj->sort_type == 'asc'){
				Context::set('sortType','desc');
			}else if($obj->sort_type == 'desc'){
				Context::set('sortType','asc');
			}
			$obj->lang = Context::get('select_lang');

			//page parameters
			$page = Context::get('page');
			$listCount = $this->listCount;
			$pageCount = $this->pageCount;
            if(!$page) Context::set('page', $page=1);
			$fileList = $oTransModel->getProFile($obj, $listCount, $page, $pageCount);

			Context::set('page_navigation', $fileList->page_navigation);
			$fileList = $fileList->data;

			$file_list = array();
			if($fileList){
				foreach($fileList as $file_key => $file){
					$fTransTotalCount = $oTransModel->getFileTransTotalCount($file->translation_file_srl);
					$fTransCount = $oTransModel->getFileLangTransCount($file->translation_file_srl,$select_lang);
					$fAppCount = $oTransModel->getFileLangTransCount($file->translation_file_srl,$select_lang,true);
					$fNoAppCount = $fTransCount - $fAppCount;
					$fNoTransCount = $fTransTotalCount - $fTransCount;
					$fLastUpdate = $file->last_update;

					$file_list[$file_key]->translation_file_srl = $file->translation_file_srl;
					$file_list[$file_key]->file_path = $file->file_path;
					$file_list[$file_key]->trans_count = $fTransCount;
					$file_list[$file_key]->no_approved_count = $fNoAppCount;
					$file_list[$file_key]->member_srl = $file->member_srl;

					if($fTransTotalCount){
						$file_list[$file_key]->perc_approved = number_format($fAppCount/$fTransTotalCount * 100,1);
						$file_list[$file_key]->perc_notApproved = number_format($fNoAppCount/$fTransTotalCount * 100,1);
						$file_list[$file_key]->perc_notTranslated = number_format($fNoTransCount/$fTransTotalCount * 100,1);
						$file_list[$file_key]->last_update = zdate($fLastUpdate,"Y.m.d");
					}else{
						$file_list[$project_key]->no_contents = true;
					}
				}
			}

			$lang_supported_list = Context::loadLangSupported();
			$total_file_count = $oTransModel->getProFileTotalCount($translation_project_srl);
			$total_file_count = intval($total_file_count->total_file_count);

			// member get project list
			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl;
			$args->module_srl = $this->module_info->module_srl;
			$args->member_srl = $member_srl;
			$project_list = $oTransModel->getMemberProjectList($args);

			Context::set('lang_supported_list',$lang_supported_list);
			Context::set('project_info',$project_info);
			Context::set('file_list',$file_list);
			Context::set('select_lang',$select_lang);
			Context::set('total_file_count',$total_file_count);
			Context::set('project_list',$project_list);

			// set template_file to be file_list.html
			$this->setTemplateFile('file_list');
		}

        /**
         * @brief display translation register file page
         **/
        function dispTranslationRegFile() {

			$translation_project_srl = Context::get('translation_project_srl');
			$translation_file_srl = Context::get('translation_file_srl');
			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl;

			$oTranslationModel =  &getModel('translation');


			if($translation_file_srl){
				$file_info =  $oTranslationModel->getFile($translation_file_srl);
				$translation_project_srl = $file_info->translation_project_srl;
			}

			// get project info
			$project_info = $oTranslationModel->getProject($translation_project_srl);

			// get project list
			$obj->module_srl = $this->module_info->module_srl;
			$obj->member_srl = $member_srl;
			$project_list = $oTranslationModel->getMemberProjectList($obj);

			Context::set('project_list',$project_list);
			Context::set('project_info',$project_info);
			Context::set('file_info',$file_info);

			// set template_file to be register_file.html
            $this->setTemplateFile('file_register');
        }

        function _removeSpecialTag($content){
            return removeHackTag($content);
        }

        function dispTransContent(){
        	$fileSrl = Context::get('translation_file_srl') ? Context::get('translation_file_srl'):null;
        	$projSrl = Context::get('translation_project_srl') ? Context::get('translation_project_srl'):null;
        	$mid = Context::get('mid');
        	$oTransModel = &getModel('translation');

        	$sourceLang = Context::get('source_lang') ? Context::get('source_lang') : $this->module_info->default_lang;
        	$targetLang = Context::get('target_lang') ? Context::get('target_lang') : 'zh-CN';
        	Context::set('source_lang',$sourceLang);
        	Context::set('target_lang',$targetLang);
        	$listCount = Context::get('listCount') ? Context::get('listCount') : $this->listCount;
        	Context::set('listCount',$listCount);
        	$sortType = Context::get('listType') ? Context::get('listType') : 'translation_count';
        	Context::set('listType', $sortType);

			//get the file Info
        	$fileInfo = $oTransModel->getFileInfo($fileSrl, $projSrl);

        	//get project info
        	if($projSrl || $fileInfo){
        		if($projSrl){
        			$projInfo = $oTransModel->getProjInfoBySrl(array($projSrl));
        		}else{
        			$f = current($fileInfo->data);
        	    	$projInfo = $oTransModel->getProjInfoBySrl(array($f->translation_project_srl));
        		}
        	    if($projInfo && $projInfo->data){
        	        Context::set('projInfo',$projInfo->data);
        	    }
        	    if(!$projSrl){
        	    	Context::set('translation_project_srl',$projInfo->data->translation_project_srl);
        	    }
        	}

        	//if has file_srl in the get params, need file_info to show in the page
        	if($fileSrl){
        	    Context::set('fileInfo',current($fileInfo->data));
        	}

			//get Source List
			$page = Context::get('page');
            if(!$page) Context::set('page', $page=1);
        	$sourceList = $oTransModel->getSourceList($sourceLang, $targetLang, $fileSrl, $projSrl, $sortType,
        												$listCount, $page, $this->pageCount);
        	Context::set('page_navigation', $sourceList->page_navigation);
        	if(empty($sourceList->data)){
        		$sourceList->data = array();
        	}

			//get other info :targetInfo, dictionary item
        	$contentNode = array();
        	foreach($sourceList->data as $key => $dataObj){
        		$contentNode[] = $dataObj->content_node;
        		$nodeContent[$dataObj->translation_content_srl] = $this->_removeSpecialTag($dataObj->content);
        	}

        	//get translation_content_srl
			$targetList = $oTransModel->getTargetList($contentNode, $targetLang, $fileSrl, $projSrl);

        	//combine the target info,file info into the source
        	foreach($sourceList->data as $key => &$obj){
        		$obj->targetList = array();

        		$obj->content =$this->_removeSpecialTag( $obj->content);
        		if(!empty($targetList->data)){
	        		foreach($targetList->data as $key2 => &$obj2){
						if( $obj2->content) {
							$obj2->content = $this->_removeSpecialTag($obj2->content);
						}

						if($obj->content_node == $obj2->content_node &&
							$obj->translation_file_srl == $obj2->translation_file_srl){
							$obj->targetList[] = $obj2;
							if($obj2->is_original){
								$obj->targetListTop = $obj2;
							}
						}
	        		}
	        	}
        		if(!$obj->targetListTop && count($obj->targetList)>0){
					$obj->targetListTop = $obj->targetList[0];
        		}
        		$obj->fileInfo = null;
        		if(!empty($fileInfo->data)){
	        		foreach($fileInfo->data as $key3 => $obj3){
	        			if($obj->translation_file_srl == $obj3->translation_file_srl){
	        				$obj->fileInfo = $obj3;
	        				break;
	        			}
	        		}
	        	}

	        	$obj->content_node = preg_replace('/\//','>',$obj->content_node);
        	}

        	Context::set('sourceList', $sourceList->data);

			$url = getUrl('','mid', $mid, 'act',Context::get('act'),'translation_project_srl',$projSrl,
							'translation_file_srl',$fileSrl,
							'source_lang',$sourceLang,
							'target_lang',$targetLang,
							'listType',$sortType);
        	Context::set('listUrl', $url);

        	// get supported language list
			$lang_supported_list = Context::loadLangSupported();
			Context::set('lang_supported_list',$lang_supported_list);

            $this->setTemplateFile('file_content');
        }

        /**
         * @brief display translation file content page
         **/
		function dispTranslationFileContent(){
			$translation_file_srl = Context::get('translation_file_srl');
			$translation_project_srl = Context::get('translation_project_srl');

			$oTranslationModel =  &getModel('translation');
			$oTranslationController = &getController('translation');

			$source_lang = Context::get('source_lang')?Context::get('source_lang'):$this->module_info->default_lang;
			$target_lang = Context::get('target_lang')?Context::get('target_lang'):'ko';

			$isExistLangInfo = $oTranslationModel->isExistLangInfo($translation_file_srl,$target_lang);

			// if the lang infomation is not exist in the translation_content_node table, then insert ContentNodeInfo
			if(!$isExistLangInfo){
				$oTranslationController->insertContentNodeInfo($translation_file_srl,$translation_project_srl,$target_lang);

			}

			$source_contents = $oTranslationModel->getSourceContents($source_lang,$target_lang,$translation_file_srl);

			Context::set('source_contents',$source_contents);
			Context::set('source_lang',$source_lang);
			Context::set('target_lang',$target_lang);

			// set template_file to be register_file.html
            $this->setTemplateFile('file_content');
		}

        /**
         * @brief display delete project page
         **/
		function dispTranslationDeleteProject(){

			$translation_project_srl = Context::get('translation_project_srl');
			$module_srl = Context::get('module_srl');
			$mid = Context::get('mid');

			$oTransModel = &getModel('translation');
			$project_info = $oTransModel->getProject($translation_project_srl);

			Context::set('project_info', $project_info);
			Context::set('translation_project_srl', $translation_project_srl);
			Context::set('module_srl', $module_srl);
			Context::set('mid', $mid);

			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'xe_translation_official';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }

			$oTemplateHandler = &TemplateHandler::getInstance();
            $result = new Object();
            $result->add('page', $oTemplateHandler->compile($template_path, 'delete_project.html'));
			$this->add('page', $result->get('page'));
		}

        /**
         * @brief display delete file page
         **/
		function dispTranslationDeleteFile(){

			$translation_file_srl = Context::get('translation_file_srl');
			$module_srl = Context::get('module_srl');
			$mid = Context::get('mid');

			$oTransModel = &getModel('translation');
			$file_info = $oTransModel->getFile($translation_file_srl);

			Context::set('file_info', $file_info);
			Context::set('translation_file_srl', $translation_file_srl);
			Context::set('module_srl', $module_srl);
			Context::set('mid', $mid);
			
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'xe_translation_official';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }

			$oTemplateHandler = &TemplateHandler::getInstance();
            $result = new Object();
            $result->add('page', $oTemplateHandler->compile($template_path, 'delete_file.html'));
			$this->add('page', $result->get('page'));
		}

		function downloadTranslationFile(){
			$translation_file_srl = Context::get('translation_file_srl');
			$oTranslationModel =  &getModel('translation');
			$file_info = $oTranslationModel->getFile($translation_file_srl);

			if($translation_file_srl){
				$filepath = $file_info->target_file;
				$test = $oTranslationModel->getFileAllContents($translation_file_srl);
			}

			$dir_path = './files/cache/translation/'.$this->module_info->module_srl.'/'.$file_info->translation_project_srl;
			$file_path = $dir_path.'/'.md5(crypt(rand(1000000,900000), rand(0,100))).'.xml';
			$oTranslationModel->writeXml($test,$file_path);
			$oTranslationModel->downloadFile($file_path,$file_info->file_name);
			exit;
		}


		function multi2dSortAsc(&$arr, $key){
			$sort_col = array();
			foreach ($arr as $sub) $sort_col[] = $sub[$key];
			array_multisort($sort_col, $arr);
		}

		function multi2dSortDesc(&$arr, $key){
			$sort_col = array();
			foreach ($arr as $sub) $sort_col[] = $sub[$key];
			array_multisort($sort_col,SORT_DESC, $arr);
		}

    }