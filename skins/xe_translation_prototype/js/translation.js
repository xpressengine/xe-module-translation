jQuery(function($){
	
	var eArr = [];
	var tracks,sp,tw,sw;

	tracks = $('tr div.track');
	

	if(tracks.length>0){
		tw = $(".track").css("width").replace(/px$/,'') - 1;

		var i = 0;
		while(i < tracks.length){
			tw = $(tracks[i]).width();

			sp = $(tracks[i]).find('span');
			var m = 0;
			var w_add = 0;
			while(m<sp.length){
				var el = $(sp[m]);
				w = parseInt(tw*(el.html().replace(/%$/,'')*.01));
	
				if( w < 25){
					w_add += 25 - w;
					w = (25 + 'px');
				}else{
					if(w_add > 0){
						w = w - w_add;
						w_add = 0;
					}
					w = (w + 'px'); 
				}

				if(m == sp.length-1){
					if(w_add > 0){
						for(j=m-1;j>=0;j--){
							w2 = $(sp[j]).width();
							if(w2 >25){
								new_width = (w2 - w_add) + 'px';
								$(sp[j]).css('width',new_width);
							}
						}
					}
				}

				el.css('width',w);
				m++;
			}
			i++;
		} // End of while [i]
		
		$('tbody tr:last-child>td,tbody tr:last-child>th').css('border-bottom','none');
		$('thead th:last-child').css('border-right','none');
	}
 });