<?php
    /**
     * @class  translation
     * @author NHN (developers@xpressengine.com)
     * @brief  translation module high class
     **/
	require('XMLContext.class.php');

    class translation extends ModuleObject {

        var $skin = "xe_translation_official"; ///< skin name

        /**
         * @brief module installation
         **/
        function moduleInstall() {
            // action forward get module controller and model
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            return new Object();
        }

        /**
         * @brief check update method
         **/
        function checkUpdate() {
			$oModuleModel = &getModel('module');
            return false;
        }

        /**
         * @brief update module
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            return new Object(0, 'success_updated');
        }

		function moduleUninstall() {
			$output = executeQueryArray("translation.getAllTranslation");
			if(!$output->data) return new Object();
			set_time_limit(0);
			$oModuleController =& getController('module');
			foreach($output->data as $faq)
			{
				$oModuleController->deleteModule($faq->module_srl);
			}
			return new Object();
		}

        /**
         * @brief create cache file
         **/
        function recompileCache() {
        }

    }
?>
