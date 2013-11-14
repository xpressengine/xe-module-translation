jQuery(function($){
	var _root = this;
	_root.targetLang = targetLang;
	var edit_ta = $('div.translate');
	edit_ta.find('textarea.resizable').TextAreaResizer();


	var tBlock = $('div.t_item');
	var tItem = tBlock.children('div.item');

	//init
	tBlock.children('div.edit_dic').hide();


	// click item
	tItem.click(function(){
		var idx = tItem.index(this);
		tBlock.children('div.item').show()
		tBlock.children('div.edit_dic').hide();
		$(this).hide();
		tBlock.eq(idx).children('div.edit_dic').show();
		var dicDiv = tBlock.find('div[class^="dic_content_"]');
		findDicTable(srlArr[idx]);
	});

	var btns = tBlock.find('div.btns button');

	btns.click(function(){
		var t = $(this).parents('div.t_item');
		var idx = $('.t_item').index(t);
		var btn_att = btns.hasClass('btn_prev');
		var bIdx = tItem.index(t);
		if($(this).hasClass('btn_prev')){
			acc(idx,'prev');
			return false;
		}else{
			acc(idx,'next');
			return false;
		}

	});
    var firstBt = btns.first();
	if(!firstBt.hasClass('btn_hidden')){
	    firstBt.toggleClass('btn_hidden');
	}
	var lastBt = btns.last();
	if(!lastBt.hasClass('btn_hidden')){
	    lastBt.toggleClass('btn_hidden');
	}
	
	
	
	
	var textAreaObj = edit_ta.find('textarea');
	var srlArr = [];
	var i = 0;
	textAreaObj.each(function(ind, taObj){
		srlArr[i] = $(taObj).attr('name');
		srlArr[i] = srlArr[i].slice(8,srlArr[i].length - 1);
		i++;
	});

	var findDicTable = function(srl){
		srl = srl || srlArr[0];
		var targetLang = _root.targetLang || 'zh-CN';
		if(!targetLang || !srl){
			return;
		}

		var callBack = function(ret_obj){
			var htmlStr = ret_obj['html'];
			var dicObj = $('.dic_content_' + srl);
			if(htmlStr != ''){
				dicObj.toggleClass("glossary");
				dicObj.html(htmlStr);
			}
		};
		var params = [];
		params['translation_content_srl'] = srl;
		params['target_lang'] = targetLang;
		exec_xml('translation','procGetDicInfo', params, callBack, new Array('error','message','html'));
	}
	findDicTable();

	function acc(idx,dir){
		var tBlock = jQuery('div.t_item');
		var length = tBlock.length;
		switch (dir)
		{
			case 'prev':
				idx = (idx == 0)?0:idx-1;
				tBlock.children('div.item').show();
				tBlock.children('div.edit_dic').hide();
				tBlock.eq(idx).find('div.item').hide();
				tBlock.eq(idx).find('div.edit_dic').show();
				findDicTable(srlArr[idx]);
				return;
			case 'next':
				idx = (idx == length - 1)?idx:(idx+1);
				tBlock.children('div.item').show();
				tBlock.children('div.edit_dic').hide();
				tBlock.eq(idx).find('div.item').hide();
				tBlock.eq(idx).find('div.edit_dic').show();
				findDicTable(srlArr[idx]);
				return;
			default:
		}
		//return false;
	}
});