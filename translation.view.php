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

			$oTranslationModel =  &getModel('translation');
			$translation_project_srl = Context::get('translation_project_srl');

			if($translation_project_srl){
				$project_info = $oTranslationModel->getProject($translation_project_srl);
				Context::set('project_info',$project_info);
			}

			$translation_file_srl = Context::get('translation_file_srl');

			if($translation_file_srl){
				$file_info =  $oTranslationModel->getFile($translation_file_srl);
				Context::set('file_info',$file_info);
			}

			$lang_supported_list = Context::loadLangSupported();
			Context::set('lang_supported_list',$lang_supported_list);

			//set view 's default list count/page count
			$this->listCount = 5;
			$this->pageCount = 10;
            $this->setTemplatePath($template_path);
		}

        /**
         * @brief display translation index page
         **/
        function dispTranslationIndex() {

			$oTranslationModel = &getModel('translation');

			// get supported language list
			$lang_supported_list = Context::loadLangSupported();
			$lang_list = array();
			$mTranslationTotalCount = $oTranslationModel->getModuleTranslationTotalCount($this->module_info->module_srl);

			if($lang_supported_list){
				foreach($lang_supported_list as $lang_key => $lang){
					$mCount = $oTranslationModel->getModuleTranslationLangCount($this->module_info->module_srl,$lang_key);
					$mApprovedCount = $oTranslationModel->getModuleTranslationLangCount($this->module_info->module_srl,$lang_key,true);
					$mNotApprovedCount = $mCount - $mApprovedCount;
					$mNotTranslatedCount = $mTranslationTotalCount - $mCount;
					$mLangLastUpdate = $oTranslationModel->getModuleLangLastUpdate($this->module_info->module_srl,$lang_key);

					$lang_list[$lang_key]->value = $lang;
					if($mTranslationTotalCount){
						$lang_list[$lang_key]->perc_approved = number_format($mApprovedCount/$mTranslationTotalCount * 100,2);
						$lang_list[$lang_key]->perc_notApproved = number_format($mNotApprovedCount/$mTranslationTotalCount * 100,2);
						$lang_list[$lang_key]->perc_notTranslated = number_format($mNotTranslatedCount/$mTranslationTotalCount * 100,2);
						$lang_list[$lang_key]->last_update = zdate($mLangLastUpdate->last_update,"Y.m.d");
					}else{
						$lang_list[$lang_key]->no_files = true;
					}
				}
			}

			Context::set('lang_list',$lang_list);

			$projectList = $oTranslationModel->getProjectList($this->module_info->module_srl);
			$project_list = array();

			if($projectList){
				foreach($projectList as $project_key => $project){
					$pTranslationTotalCount = $oTranslationModel->getProjectTranslationTotalCount($project->translation_project_srl) * count($lang_list);
					$pTranslatedCount = $oTranslationModel->getProjectTranslationCount($project->translation_project_srl);
					$pApprovedCount = $oTranslationModel->getProjectTranslationCount($project->translation_project_srl, true);
					$pNotApprovedCount = $pTranslatedCount - $pApprovedCount;
					$pNotTranslatedCount = $pTranslationTotalCount - $pTranslatedCount;
					$pLastUpdate = $oTranslationModel->getProjectLastUpdate($project->translation_project_srl);

					$project_list[$project_key]->translation_project_srl = $project->translation_project_srl;
					$project_list[$project_key]->project_name = $project->project_name;
					if($pTranslationTotalCount){
						$project_list[$project_key]->perc_approved = number_format($pApprovedCount/$pTranslationTotalCount * 100,2);
						$project_list[$project_key]->perc_notApproved = number_format($pNotApprovedCount/$pTranslationTotalCount * 100,2);
						$project_list[$project_key]->perc_notTranslated = number_format($pNotTranslatedCount/$pTranslationTotalCount * 100,2);
						$project_list[$project_key]->last_update = zdate($pLastUpdate->last_update,"Y.m.d");
					}else{
						$project_list[$project_key]->no_files = true;
					}

				}
			}

			Context::set('project_list',$project_list);

			$translatorList = $oTranslationModel->getTranslatorRanking($this->module_info->module_srl);
			$translator_list = array();

			if($translatorList){
				foreach($translatorList as $key => $translator){
					$translator_list[$key]->member_srl = $translator->member_srl;
					$translator_list[$key]->nick_name = $translator->nick_name;
					$translator_list[$key]->translation_count = $translator->translation_count?$translator->translation_count:0;
				}
			}

			Context::set('translator_list',$translator_list);

			$reviewerList = $oTranslationModel->getReviewerRanking($this->module_info->module_srl);
			$reviewer_list = array();

			if($reviewerList){
				foreach($reviewerList as $key => $reviewer){
					$reviewer_list[$key]->member_srl = $reviewer->member_srl;
					$reviewer_list[$key]->nick_name = $reviewer->nick_name;
					$reviewer_list[$key]->total_recommended_count = $reviewer->total_recommended_count?$reviewer->total_recommended_count:0;
				}
			}

			Context::set('reviewer_list',$reviewer_list);

			// set template_file to be index.html
            $this->setTemplateFile('index');
        }

        /**
         * @brief display translation register project page
         **/
        function dispTranslationRegProject() {

			$translation_project_srl = Context::get('translation_project_srl');

			$oTranslationModel =  &getModel('translation');
			$project_info = $oTranslationModel->getProject($translation_project_srl);

			Context::set('project_info',$project_info);

			// set template_file to be register_project.html
            $this->setTemplateFile('register_project');
        }

        /**
         * @brief display translation (member) project list page
         **/
		function dispTranslationProjectList() {

			// get member_srl
            $member_srl = Context::get('member_srl');
			$select_lang = Context::get('select_lang')?Context::get('select_lang'):$this->module_info->default_lang;

			$oTranslationModel =  &getModel('translation');
			$obj->module_srl = $this->module_info->module_srl;

			if($member_srl){
				$obj->member_srl = $member_srl;
				$projectList =  $oTranslationModel->getMemberProjectList($obj);
			}else{
				$projectList =  $oTranslationModel->getProjectList($obj->module_srl);
			}

			$project_list = array();
			if($projectList){
				foreach($projectList as $project_key => $project){
					$pTranslationTotalCount = $oTranslationModel->getProjectTranslationTotalCount($project->translation_project_srl);
					$pTranslatedCount = $oTranslationModel->getProjectLangTranslationCount($project->translation_project_srl,$select_lang);
					$pApprovedCount = $oTranslationModel->getProjectLangTranslationCount($project->translation_project_srl,$select_lang, true);
					$pNotApprovedCount = $pTranslatedCount - $pApprovedCount;
					$pNotTranslatedCount = $pTranslationTotalCount - $pTranslatedCount;

					$project_list[$project_key]->translation_project_srl = $project->translation_project_srl;
					$project_list[$project_key]->project_name = $project->project_name;
					if($pTranslationTotalCount){
						$project_list[$project_key]->perc_approved = number_format($pApprovedCount/$pTranslationTotalCount * 100,2);
						$project_list[$project_key]->perc_notApproved = number_format($pNotApprovedCount/$pTranslationTotalCount * 100,2);
						$project_list[$project_key]->perc_notTranslated = number_format($pNotTranslatedCount/$pTranslationTotalCount * 100,2);
					}else{
						$project_list[$project_key]->no_files = true;
					}
				}
			}

			Context::set('project_list',$project_list);

			// set template_file to be project_list.html
			$this->setTemplateFile('project_list');
		}

        /**
         * @brief display translation (project) file list page
         **/
		function dispTranslationFileList() {

			// get member_srl
            $translation_project_srl = Context::get('translation_project_srl');
			$obj->module_srl = $this->module_info->module_srl;

			if($translation_project_srl){
				$oTranslationModel =  &getModel('translation');
				$project_info = $oTranslationModel->getProject($translation_project_srl);
				Context::set('project_info',$project_info);
				$obj->translation_project_srl = $project_info->translation_project_srl;
				$file_list = $oTranslationModel->getProjectFileList($obj);
			}else{

			}

			Context::set('file_list',$file_list);

			// set template_file to be file_list.html
			$this->setTemplateFile('file_list');
		}

        /**
         * @brief display translation register file page
         **/
        function dispTranslationRegFile() {

			$translation_project_srl = Context::get('translation_project_srl');
			$translation_file_srl = Context::get('translation_file_srl');

			$oTranslationModel =  &getModel('translation');


			if($translation_file_srl){
				$file_info =  $oTranslationModel->getFile($translation_file_srl);
				$translation_project_srl = $file_info->translation_project_srl;
			}

			// get project info
			$project_info = $oTranslationModel->getProject($translation_project_srl);

			// get project list
			$obj->module_srl = $this->module_info->module_srl;
			$project_list = $oTranslationModel->getProjectList($obj->module_srl);

			Context::set('project_list',$project_list);
			Context::set('project_info',$project_info);
			Context::set('file_info',$file_info);

			// set template_file to be register_file.html
            $this->setTemplateFile('file_register');
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
        	$sortType = Context::get('listType') ? Context::get('listType') : 'translation_count';

			//get the file Info
        	$fileInfo = $oTransModel->getFileInfo($fileSrl, $projSrl);

			//get Source List
			$page = Context::get('page');
            if(!$page) Context::set('page', $page=1);
        	$sourceList = $oTransModel->getSourceList($sourceLang, $targetLang, $fileSrl, $projSrl, $sortType,
        												$listCount, $page, $this->pageCount);
        	Context::set('page_navigation', $sourceList->page_navigation);
        	if(empty($sourceList->data)){
        		$sourceList->data = array();
        	}

			//get other info :targetInfo
        	$contentNode = array();
        	foreach($sourceList->data as $key => $dataObj){
        		$contentNode[] = $dataObj->content_node;
        	}

        	//get translation_content_srl
			$targetList = $oTransModel->getTargetList($contentNode, $targetLang, $fileSrl, $projSrl);

        	//combine the target info,file info into the source
        	foreach($sourceList->data as $key => &$obj){
        		$obj->targetList = array();
        		if(empty($targetList->data)){
        			continue;
        		}
        		foreach($targetList->data as $key2 => $obj2){
					if($obj->content_node == $obj2->content_node){
						$obj->targetList[] = $obj2;
						if($obj2->is_original){
							$obj->targetListTop = $obj2;
						}
					}
        		}
        		if(!$obj->targetListTop && count($obj->targetList)>0){
					$obj->targetListTop = $obj->targetList[0];
        		}
        		if(empty($fileInfo->data)){
        			continue;
        		}
        		$obj->fileInfo = null;
        		foreach($fileInfo->data as $key2 => $obj2){
        			if($obj->translation_file_srl == $obj2->translation_file_srl){
        				$obj->fileInfo = $obj2;
        				break;
        			}
        		}
        	}
        	Context::set('sourceList', $sourceList->data);

			$url = getUrl('','mid', $mid, 'act','dispTransContent','translation_project_srl',$projSrl,
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

		function downloadTranslationFile(){
			$translation_file_srl = Context::get('translation_file_srl');
			$oTranslationModel =  &getModel('translation');

			if($translation_file_srl){
				$filepath = $file_info->target_file;
				$test = $oTranslationModel->getFileAllContents($translation_file_srl);
				Context::set('test11',$test);
			}
			Context::set('test11',$test);

			$this->write_txt($test);
			// set template_file to be register_file.html
            $this->setTemplateFile('download');
		}

		function write_txt($contents){

			if(!file_exists("test.xml")){

				$fp = fopen("test.xml","wb");

				fclose($fp);}
				$str = file_get_contents('test.xml');
				$fp = fopen("test.xml","wb");
				fwrite($fp,$contents);

				fclose($fp);

			}

    }



?>
