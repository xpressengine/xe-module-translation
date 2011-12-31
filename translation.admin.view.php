<?php
    /**
     * @class  translationAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief  translation module admin view class
     **/

    class translationAdminView extends translation {

        function init() {
			// get module_srl if it exists
            $module_srl = Context::get('module_srl');
            if(!$module_srl && $this->module_srl) {
                $module_srl = $this->module_srl;
                Context::set('module_srl', $module_srl);
            }

            // module model class
            $oModuleModel = &getModel('module');

            // get module_info based on module_srl
            if($module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    Context::set('module_srl','');
                    $this->act = 'list';
                } else {
                    ModuleModel::syncModuleToSite($module_info);
                    $this->module_info = $module_info;
                    Context::set('module_info',$module_info);
                }
            }

            if($module_info && $module_info->module != 'translation') return $this->stop("msg_invalid_request");

            // get module category
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            // set the module template path (modules/translation/tpl)
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);

        }
       
		// display translation module admin panel 
	    function dispTranslationAdminContent() {
			$args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 20;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');

			$s_mid = Context::get('s_mid');
			if($s_mid) $args->s_mid = $s_mid;

			$s_browser_title = Context::get('s_browser_title');
			if($s_browser_title) $args->s_browser_title = $s_browser_title;


            $output = executeQueryArray('translation.getTranslationList', $args);
            ModuleModel::syncModuleToSite($output->data);


            // setup module variables, context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('translation_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // set template file
            $this->setTemplateFile('index');
		}

		function dispTranslationAdminTranslationInfo() {
            $this->dispTranslationAdminInsertTranslation();
        }

		/**
         * @brief display insert translation admin page
         **/
        function dispTranslationAdminInsertTranslation() {
			if(!in_array($this->module_info->module, array('admin','translation'))) {
                return $this->alertMessage('msg_invalid_request');
            }

			// get skin list
			$oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

			// get layout list
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layout_list);

			// get supported language list
			$lang_supported_list = Context::loadLangSupported();
			Context::set('lang_supported_list',$lang_supported_list);

			$this->setTemplateFile('translation_insert');
        }
  
		/**
         * @brief display delete trasnslation module page
         **/
        function dispTranslationAdminDeleteTranslation() {
            if(!Context::get('module_srl')) return $this->dispTranslationAdminContent();
            if(!in_array($this->module_info->module, array('admin', 'translation'))) {
                return $this->alertMessage('msg_invalid_request');
            }

            $module_info = Context::get('module_info');
			$args->module_srl = $module_info->module_srl;

            $oTranslationModel = &getModel('translation');

			$total_project_count = $oTranslationModel->getProTotalCount($args->module_srl);
			$module_info->total_project_count = intval($total_project_count->total_project_count);


            Context::set('module_info',$module_info);

            // set template file
            $this->setTemplateFile('translation_delete');
        }

    }

?>
