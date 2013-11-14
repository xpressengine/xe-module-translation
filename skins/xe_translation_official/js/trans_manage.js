jQuery(function($){
	var _root = this;
	_root.mid = mid;
	_root.module_srl = module_srl;
	_root.scope = scope;

	$('body').append('<div id="proWindow" class="proWindow"></div>');

	// Add a new Project
	$('a#pro_add').click(function(){
		var params = [];
		params['module_srl'] = module_srl;
		params['mid'] = mid;
		params['scope'] = scope;
		var callBack = function(ret_obj){
			var htmlStr = ret_obj['page'];
			$('#proWindow').html(htmlStr);
			$('#proWindow').css('display','block');
		};
		 exec_xml('translation','dispTranslationRegProject', params, callBack,new Array('error','message','page'));
	});

	// Modify project
	modifyBts = $('a.btn_p_modify');
	modifyBts.each(function(index, moBtn){
		moBtn.onclick = function(eventObj){
			var btObj = $(moBtn);
			var params = [];

		    params['translation_project_srl'] = btObj.attr('data');
			params['module_srl'] = module_srl;
			params['mid'] = mid;
			params['scope'] = scope;
		    var callBack = function(ret_obj){
				var htmlStr = ret_obj['page'];
				$('#proWindow').html(htmlStr);
				$('#proWindow').css('display','block');
		    };
		    exec_xml('translation','dispTranslationRegProject', params, callBack,new Array('error','message','page'));
		}
	});

	//Delete project
 	deleteBts = $('a.btn_p_delete');
	deleteBts.each(function(index, deBtn){
		deBtn.onclick = function(eventObj){
			var btObj = $(deBtn);
			var params = [];

		    params['translation_project_srl'] = btObj.attr('data');
			params['module_srl'] = module_srl;
			params['mid'] = mid;
			params['scope'] = scope;
		    var callBack = function(ret_obj){
				var htmlStr = ret_obj['page'];
				$('#proWindow').html(htmlStr);
				$('#proWindow').css('display','block');
		    };
		    exec_xml('translation','dispTranslationDeleteProject', params, callBack,new Array('error','message','page'));
		}
	});

	//delete File
	deleteBts = $('a.btn_f_delete');
	deleteBts.each(function(index, deBtn){
		deBtn.onclick = function(eventObj){
			var btObj = $(deBtn);
			var params = [];

			params['translation_file_srl'] = btObj.attr('data');
			params['module_srl'] = module_srl;
			params['mid'] = mid;
			var callBack = function(ret_obj){
				var htmlStr = ret_obj['page'];
				$('#proWindow').html(htmlStr);
				$('#proWindow').css('display','block');
			};
			exec_xml('translation','dispTranslationDeleteFile', params, callBack,new Array('error','message','page'));
		}
	});

	// Add a new Dic content
	$('a#dic_add').click(function(){
		_root.source_lang = source_lang;
		_root.target_lang = target_lang;

		var params = [];
		params['source_lang'] = source_lang;
		params['target_lang'] = target_lang;
		params['translation_title'] = translation_title;
		params['mid'] = mid;
		
		var callBack = function(ret_obj){
			var htmlStr = ret_obj['page'];
			$('#proWindow').html(htmlStr);
			$('#proWindow').css('display','block');
		};
		exec_xml('translation','dispTransAddDicContent', params, callBack,new Array('error','message','page'));
	});

	//delete File
	deleteBts = $('a.btn_dic_delete');
	deleteBts.each(function(index, deBtn){
		deBtn.onclick = function(eventObj){
			var btObj = $(deBtn);
			var params = [];

			params['translation_dictionary_srl'] = btObj.attr('data');
			params['module_srl'] = module_srl;
			params['mid'] = mid;
			var callBack = function(ret_obj){
				var htmlStr = ret_obj['page'];
				$('#proWindow').html(htmlStr);
				$('#proWindow').css('display','block');
			};
			exec_xml('translation','dispTranslationDeleteDic', params, callBack,new Array('error','message','page'));
		}
	});
	
});