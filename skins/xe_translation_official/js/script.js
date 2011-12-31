jQuery(function($){
	// select language comboBox
	var combo = $('div.lang_select');
	var list_li = combo.find('ul.lang_list>li');
	list_li.mouseover(function(){$(this).css('background-color','#dedede')});
	list_li.mouseout(function(){$(this).css('background-color','transparent')});
	$('.lang_select .selected').click(listToggle);
	function listToggle()
	{
		var sel = $(this).parent().find($("ul"));
		
		sel.slideToggle();
		$(this).toggleClass('on');
	}
	

});