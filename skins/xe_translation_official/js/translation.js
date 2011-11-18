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
		//var flag = 0;
		
		while(m<sp.length){
			var el = $(sp[m]);
			var isFe = (m == 0);
			var isLe = (m == (sp.length - 1));
			w = tw*(el.html().replace(/%$/,'')*.01);
			if (foe){
				(w < 5)? w = (5 + 'px'):w = (w + 'px'); 
				el.css('text-indent','-1000');
				flag = true;
			} 
			if(flag && !foe){
				w = (w - 10) + 'px';
			}
			el.css('width',w);
			m++;
		}
		i++;
	} // End of while [i]
	
	
	/*
  	var i = 0;
	track = $(".track").css("width").replace(/px$/,'');
	//alert($(".percentage").css("width"));
	track = track - 1;
	alert(eArr.length);
	while (i < eArr.length){
		w = track*($(eArr[i]).html().replace(/%$/,'')*.01)
		if(w < 1 ){
			w = 3 + 'px';
			$(eArr[i]).css('width',w);
		}else{
			w = w + 'px';
			$(eArr[i]).css('width',w);
		}
		
		i++;
		
	}
	*/
	
	
	
	
	
	
	$('tbody tr:last-child>td,tbody tr:last-child>th').css('border-bottom','none');
	$('thead th:last-child').css('border-right','none');
 });