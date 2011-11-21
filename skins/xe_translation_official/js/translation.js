jQuery(function($){
	
	var eArr = [];
	var tracks,sp,tw,sw;
	//eArr = $('tr .track>span');
	tracks = $('tr div.track');
	
	tw = $(".track").css("width").replace(/px$/,'') - 1;

	var i = 0;
	while(i < tracks.length){
		sp = $(tracks[i]).find('span');
		var m = 0;
		var w_add = 0;
		while(m<sp.length){
			var el = $(sp[m]);
			w = tw*(el.html().replace(/%$/,'')*.01);
			if( w < 10){
				w_add = 10 - w;
				w = (10 + 'px');
			}else{
				if(w_add > 0){
					w = w - w_add;
					w_add = 0;
				}
				w = (w + 'px'); 
			}
			el.css('width',w);
			m++;
		}
		i++;
	} // End of while [i]
	
	$('tbody tr:last-child>td,tbody tr:last-child>th').css('border-bottom','none');
	$('thead th:last-child').css('border-right','none');
 });