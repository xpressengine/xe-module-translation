jQuery(function($){
	// select language comboBox
	var combo = $('div.lang_select');
	var list_li = combo.find('ul.lang_list>li');

	var wpx = combo.find('ul.lang_list').css('width');
	var w = Number(wpx.replace(/px$/,''));
	combo.find('>div').css('width',(w+3)+'px');
	combo.find('a').css({'display':'inline-block','width':(w-15) + 'px'});

	//combo.find('ul.lang_list').css('display','none');
	list_li.mouseover(function(){$(this).css('background-color','#dedede')});
	list_li.mouseout(function(){$(this).css('background-color','transparent')});
	var combo_tf = $('div.lang_select>div');
	combo.click(listToggle).focusout(leave);
	combo.find('a').click(change_lang);
	function change_lang()
	{
		var lnk = $(this);
		var p = lnk.parents('.lang_select')
		var field = p.find('div.selected>span.label');
		field.html(lnk.html());
		var banner = p.find('div.selected>span.ico');
		banner.removeClass().addClass(lnk.attr('class') + ' ico');
	}

	function listToggle()
	{
		var sel = $(this);

		var state;
		($('div.lang_select>div').hasClass('on'))?state = true:state = false;

		if (state){
			sel.find('ul').css('display','none');
			sel.find('div.selected').removeClass('on').addClass('off');
		}else{
			sel.find('ul').css('display','block');

			sel.find('div.selected').removeClass('off').addClass('on');
		}
	}

	function leave()
	{
		combo.find('ul').css('display','none');
		combo_tf.removeClass('on').addClass('off');
	}

	var _root = this;
	_root.targetLang = window.targetLang || '';

	var edit_ta = $('div.translation');
	edit_ta.find('textarea.resizable:not(.processed)').TextAreaResizer();

	var votes = $('div.translation ul');
	votes.find('>li').mouseover(showVote);
	votes.find('>li').mouseleave(hideVote);

	function showVote(){
		$(this).addClass('voteOver');
		$(this).children('span.btn_vote').show();
		};
	function hideVote(){
		$(this).removeClass('voteOver');
	votes.find('span.btn_vote').hide();
	}

	var tBlock = $('div#t_list>div.t_item');
	var tItem = tBlock.children('div.item');

	//init
	tBlock.children('div.item:lt(1)').hide();
	tBlock.children('div.edit:gt(0)').hide();
	tBlock.children('div.item:gt(0)').show();

	// click item
	tItem.click(function(){
		var idx = tItem.index(this);
		tBlock.children('div.item').show();
		tBlock.children('div.edit').hide();
		$(this).hide();
		tBlock.eq(idx).children('div.edit').show();
		var dicDiv = tBlock.find('div[class^="dic_content_"]');
		var classNam = dicDiv.attr('class');
		var reg = /\d+/;
		srl = reg.exec(classNam);
		findDicTable(srl);
	});

	var btns = tBlock.find('div.btns button');
	btns.click(function(){
		var t = $(this).parents('div.t_item');
		var idx = tBlock.index(t);
		var btn_att = btns.hasClass('btn_prev');
		//var bIdx = tItem.index(t);
		if($(this).hasClass('btn_prev')){
			acc(idx,'prev');
			return false;
		}else{
			acc(idx,'next');
			return false;
		}

	});

	btns.first().toggleClass('btn_hidden');
	btns.last().toggleClass('btn_hidden');

	var voteBts = votes.find('span.btn_vote');
	voteBts.each(function(ind, btEl){
		btEl.onclick = function(eventObj){
			var btObj = $(btEl);
			var params = [];

		    params['translation_content_srl'] = btObj.attr('data');
		    var callBack = function(){
		    	var recomCountObj = $(btEl).parent().find('.recomCount');
				var recomCountHtml = recomCountObj.html();
				var reg = /\D*/g;
				var countNum = recomCountHtml.replace(reg, '');
				var refreshNum = parseInt(countNum) + 1;
				var reg = /(\D*)(\d+)(\D*)/;
				refreshStr = recomCountHtml.replace(reg, '$1' + refreshNum + '$3');
				recomCountObj.html(refreshStr);
		    };
		    exec_xml('translation','procVoteItem', params, callBack);
		}

	});

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
	var tBlock = jQuery('div#t_list>div.t_item');
	var length = tBlock.length;

	switch (dir)
	{
		case 'prev':
			idx = (idx == 0)?idx=0:idx-=1;
			tBlock.children('div.item').show();
			tBlock.children('div.edit').hide();
			tBlock.eq(idx).find('div.item').hide();
			tBlock.eq(idx).find('div.edit').show();
			findDicTable(srlArr[idx]);
			return;
		case 'next':
			idx = (idx == length - 1)?idx=length - 1:idx+=1;
			tBlock.children('div.item').show();
			tBlock.children('div.edit').hide();
			tBlock.eq(idx).find('div.item').hide();
			tBlock.eq(idx).find('div.edit').show();
			findDicTable(srlArr[idx]);
			return;
		default:
	}
	return false;
}
});