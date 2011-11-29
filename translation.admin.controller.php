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
    }
?>
