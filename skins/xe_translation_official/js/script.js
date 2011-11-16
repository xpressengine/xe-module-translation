jQuery(function($){
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

	var voteBts = votes.find('span.btn_vote');
	voteBts.each(function(ind, btEl){
		btEl.onclick = function(eventObj){
			var btObj = $(btEl);
			var params = [];

		    params['translation_content_srl'] = btObj.attr('data');
		    var callBack = function(){
				location.reload();
		    }
		    exec_xml('translation','procVoteItem', params, callBack);
		}

	});
})
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
			return;
		case 'next':
			idx = (idx == length - 1)?idx=length - 1:idx+=1;
			tBlock.children('div.item').show();
			tBlock.children('div.edit').hide();
			tBlock.eq(idx).find('div.item').hide();
			tBlock.eq(idx).find('div.edit').show();
			return;
		default:

	}
	return false;
}