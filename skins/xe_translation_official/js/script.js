jQuery(function($){
	// select language comboBox
	var combo = $('div.lang_select');
	var list_li = combo.find('ul.lang_list>li');

	var wpx = combo.find('ul.lang_list').css('width');
	var w = Number(wpx.replace(/px$/,''));
	combo.find('>div').css('width',(w+8)+'px');
	combo.find('a').css({'display':'inline-block','width':(w-10) + 'px'});

	//combo.find('ul.lang_list').css('display','none');
	list_li.mouseover(function(){$(this).css('background-color','#dedede')});
	list_li.mouseout(function(){$(this).css('background-color','transparent')});
	var combo_tf = $('div.lang_select>div');
	combo.click(listToggle);
	//combo.find('a').click(change_lang);

	function listToggle()
	{
		var sel = $(this);

		var state;

		(sel.find('>div').hasClass('on'))?state = true:state = false;

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
	function change_lang()
	{
		var lnk = $(this);
		var p = lnk.parents('.lang_select')
		var field = p.find('div.selected>span.label');
		field.html(lnk.html());
		var banner = p.find('div.selected>span.ico');
		banner.removeClass().addClass(lnk.attr('class') + ' ico');
		return true;
	}
});