jQuery(function($){
	var eArr = [];
	var track,w;
	eArr = $('tr .track>span');
	
  	var i = 0;
	track = $(".track").css("width").replace(/px$/,'');
	//alert($(".percentage").css("width"));
	track = track - 1;
	while (i < eArr.length){
		w = track*($(eArr[i]).html().replace(/%$/,'')*.01) + 'px';
		$(eArr[i]).css('width',w);

		
		i++;
		
	}
	
	$('tbody tr:last-child>td,tbody tr:last-child>th').css('border-bottom','none');
	$('thead th:last-child').css('border-right','none');
 });
 
