<?php
    /**
     * @class  translationAdminController
     * @author NHN (developers@nhn.com)
     * @brief  translation module admin controller class
     **/

    class translationAdminController extends translation {

        /**
         * @brief initialization
         **/
        function init() {
        }

		function procTranslationAdminInsertTranslation(){
			// get module model/module controller
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // get variables from admin page form
            $args = Context::getRequestVars();
            $args->module = 'translation';
            $args->mid = $args->translation_name;
            unset($args->translation_name);

			// set up addtional variables
			$args->default_lang = $args->default_lang;

			// if module_srl exists
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // insert/update translation module, depending on whether module_srl exists or not 
            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
				
				// make module folder for saving the files
				$file_folder = './files/translation_files/'.$output->get('module_srl');
				FileHandler::makeDir($file_folder);

				// make cache folder for downloading files
				$cache_folder = './files/cache/translation/'.$output->get('module_srl');
				FileHandler::makeDir($cache_folder);

                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage($msg_code);

        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'module_srl', $output->get('module_srl'), 'act', 'dispTranslationAdminTranslationInfo');
				header('location:'.$returnUrl);
				return;
			}

		}

		function procTranslationAdminDeleteTranslation(){
			$module_srl = Context::get('module_srl');
			if(!$module_srl)  return new Object(-1,'msg_invalid_request');

			$obj->module_srl = $module_srl;

			$output = executeQuery('translation.deleteProjectsByModule', $obj);
			if(!$output->toBool()) { return $output;}
			$output = executeQuery('translation.deleteFileByModule', $obj);
			if(!$output->toBool()) { return $output;}
			$output = executeQuery('translation.deleteContentByModule', $obj);
			if(!$output->toBool()) { return $output;}

			// delete module folder
			$file_folder = './files/translation_files/'.$module_srl;
			FileHandler::removeDir($file_folder);

			// delete cache folder
			$cache_folder = './files/cache/translation/'.$module_srl;
			FileHandler::removeDir($cache_folder);

			$oModuleController = &getController('module');
			$output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $this->add('module','translation');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');

        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'module_srl', $output->get('module_srl'), 'act', 'dispTranslationAdminContent');
				header('location:'.$returnUrl);
				return;
			}
		}
    }
?>
