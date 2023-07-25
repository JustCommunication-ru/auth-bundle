/**
 * После разделения библиотеки в корневой остались всякие неклассифицируемые объекты.
 * В начальном представлении scripts.lib в порядке подключения выглядит так:
 * scripts.lib.js
 * scripts.lib.func.js
 * scripts.lib.browser.js
 * scripts.lib.ajax.js
 * scripts.lib.date.js
 * scripts.lib.web.js
 * ...
 * scripts.lib.ready.js
 */
var isIE6, isIE8;if (isIE6==undefined)isIE6=false;if (isIE8==undefined)isIE8=false;
var _web_root = _web_root || ''; //jcnews переменная для сайтов-копий

var framework = {
	setPageStatistic : function(from, to){
		var from = from || '#page_statistic';
		var to = to || '.page_statistic';
		if ($(from).length>0){
			var dl =$(from);
			$(to).html(''+dl.val())
				.dblclick(function(){
					$(this).html(dl.attr("details"));
				});
		}
	}
}
/**
// Обработка "ухода" со страницы
window.onbeforeunload = function(e){  
	alert(1)
}
//*/




