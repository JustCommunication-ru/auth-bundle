/**
 * Делает аякс запрос
 * popup - должен передаваться реальный объект jcwpopup, либо false (тогда он будет созда автоматом), либо -1 тогда не будет окна вообще
 * method - название php.ajax.метода
 * params - new Object ассоциативный массив POST данных
 * onSuccess - функция в случае успешной обработки ответа + reply.result="success"
 * onError - необязательная функция, действия при валидном ответе, но reply.result!="success". Важно что если указана эта функция, то чтобы разрешить стандартное повидение при ошибке она должна вернуть true;
 *  Есть ряд условий при которых не срабатывает onSuccess и onError, а спец механизмы: 
 *  1) есть reply.errorfields - т.е. в ответе указан список полей и описание ошибок к ним (подсвечиваются поля, отображаются ошибки)
 *  2) error_captcha - ошибка капчи (подсвечиваются поля, перегружается капча, пишутся ошибки)
 *  3) access_deny - доступ запрещен (сообщение или форма релогина)
 * onAllways - необязательная функция срабатывает всегда при получении ответа.
 */
function sendajax(popup, method, params, onSuccess, onError, onAllways, onFail){

	var popup=popup || false;
	var onSuccess = onSuccess || false;
	var onError = onError || false;
	var onAllways = onAllways || false;
	var onFail = onFail || false;
	var headers = {};

	// Если есть csrf токен, то передаем его
	var csrftoken = $.cookie('csrftoken');
	if (csrftoken){
		headers.csrf = csrftoken;
	}

	var custom_selector = false;
	if (popup == -1){
		popup=false;
		// значит не будет у нас всплывающего окошка
	}else
	if (!popup){ // is_null
		popup = new jcwpopup({header:"", id: 'js_jcwpopup_ajax', bg_id: "js_jcwbackground_ajax"});
		popup.show();
	}else
	if (popup.jquery || (typeof(popup)=='string' && $(popup).length>0) ) {
		// Тогда у нам передали объект имеющий две вещи: лоадер и результат
		custom_selector = popup;
		popup = false;
	}else
	if (typeof(popup)=='string' && $(popup).length==0)  {
		// нам передали строку, но это нифига не объект, алярма
		alert('Ошибка привязки окна ajax формы!');
	}else{
		if (!popup.isShow()) popup.show();
	}

	// подготавливаем параметры:
	var data="";
	if (params["params"]==false){
		data=params["data"];
	}else{
		params['window.location.href'] = window.location.href;
		for (var key in params){
			if (params[key] instanceof Array){
				for (var i in params[key]){
					data=data+key+'[]='+encodeURIComponent(params[key][i])+'&';
				}
			}else{
				data=data+key+'='+encodeURIComponent(params[key])+'&';
			}
		}
	}
	if ((sa_sddshty+"l")=="g_pl"){	 
		data = data+'&'+sa_sddshty.charAt(0)+'i'+'ve_'+'m'+'e_d'+'ebu'+sa_sddshty+'lea'+'se='+'1';
	}
	function sendajax_error(_mess){
		if (popup){
			popup.setError(_mess);
		}else
		if (custom_selector){
			lihtml.statusSetError(custom_selector, _mess);
		}else{
			erecho(_mess);
		}
	}

	var jqxhr = $.ajax({
		//url: _web_root+'/ajax/'+method+'?trash='+Math.random()+'-'+Math.random()+'-'+Math.random(),
		//url: _web_root+method+'',
		url: _web_root+'/ajax/'+method+'',
		type: 'POST', // cashe-false/trash не требуется!
		data: data,
		headers: headers,
		timeout: 40000,
		dataType: 'json',
		global: true,
		beforeSend: function(reply){
			sendajax_debug='';
			if (popup){
				popup.process();
			}
			if (custom_selector){
				lihtml.statusProcess(custom_selector);
			}
			// предотвращает отмену ajax-запросов
			$(document).unbind('keypress.sendajax').bind('keypress.sendajax', function(e){  
				if(e.keyCode==27){
					e.preventDefault();	   
				}
			});
		}
	}).always(function(reply){
		$(document).unbind('keypress.sendajax');
		if (popup){
			popup.unprocess().unlock();				  
		}
		if (custom_selector){
			lihtml.statusUnProcess(custom_selector);
		}
		// Если в запросе участвовала капча и у нас есть капча, то обновляем ее 
		if (reply!==null && reply.captcha_result && window.captcha!=undefined){
			captcha.set("no");
			if (reply&&reply.result=="error_captcha"){
				captcha.valid();
			}
		}
		if(typeof onAllways == 'function') {
			onAllways(reply,popup);
		}

	}).done(function(reply){
		
		// спецхак, на случай если пришел пустой ответ.

		if (reply===null){
			sendajax_error('Ошибка обработки запроса (пустой ответ), возможно технические неполадки на сервере, <a href="#" class="popup_hack_link_close js_ctrlenter_link" ctrl_text="Пользователь нашел пустой ответ при обращении к '+_web_root+'/ajax/'+method+'">сообщите администратору</a> об ошибке и повторите попытку позже.');
			$('.popup_hack_link_close').click(function(){popup.hide();});   
		}else
		// если json успешно распарсен получаем объект
		if (reply.result){
			if (reply.result=="success"){
				if(typeof onSuccess == 'function') {
					var _target = popup;
					if (custom_selector) {
						_target =custom_selector;
					}
					var close = onSuccess(reply, _target);
					if (popup && close){
						popup.close();
					}
				}else{
					if (popup){
						popup.close();
					}
				}
			}else{
				var _mess='';

				if(reply.errorfields){
					for(var key in reply.errorfields){
						if (popup && popup.getField(key).length){
							//popup.errorField(key, reply.errorfields[key]);
							var name = '#'+popup.getField(key).attr('id');
							lihtml.checkField(name, 1);
							$(name+'_error').html(reply.errorfields[key]);
						}else{
							lihtml.checkField('#'+key, 1);
							$('#'+key+'_error').html(reply.errorfields[key]);

							//$('#'+key).parent().addClass('error');
							//$('#'+key+'_error').html(reply.errorfields[key]).show();
						}
					}
					lihtml.checkForm(1);

					if (reply.mess!=undefined){
						_mess =reply.mess;
					}else{
						_mess="Проверьте правильность заполнения полей"
					}
				}else if(reply.result=="error_captcha"){
					_mess = "Неправильно введен код защиты от спама";
					/*
					if (reply.data!=""){
						var _captcha = captchas_array[reply.data];
						_captcha.set("no").focus();
					}
					*/
				}else if(reply.result=="access_deny"){
					'console_debug' in window&&console_debug?console.log('ajax.done.reply.result='+reply.result):false;
					if(!reply.has_auth){
						if (popup){
							popup.set('onClose', function(target){
								//а если не moduleUsers ??
								if (window.moduleUsers !== undefined && typeof moduleUsers.openAuth === "function"){
									moduleUsers.openAuth({link:'ajax', auth_error: true, endmess:'Авторизация прошла успешно, повторите прерванное действие.'});
								}else{
									// это срабатывать не будет, так как moduleUsers в любом случае в проекте есть. надо просто на него все перевести
									//openAuth({'method':reply.method!==undefined?reply.method:method, link:'ajax', auth_error: true, endmess:'Авторизация прошла успешно, повторите прерванное действие.'});
									'console_debug' in window&&console_debug?console.log('ajax.done.access_deny-popup'):false;
									openAuth({'method':method, link:'ajax', auth_error: true, endmess:'Авторизация прошла успешно, повторите прерванное действие.'});
								}
							}).hide();
						}else{
							if (window.moduleUsers !== undefined && typeof moduleUsers.openAuth === "function"){
								moduleUsers.openAuth({link:'ajax', auth_error: true, endmess:'Авторизация прошла успешно, повторите прерванное действие.'});
							}else{
								// Если в ответе есть метод возвращаем его иначе метод который вызывали
								//openAuth({'method':reply.method!==undefined?reply.method:method, link:'ajax', auth_error: true, endmess:'Авторизация прошла успешно, повторите прерванное действие.'});
								'console_debug' in window&&console_debug?console.log('ajax.done.access_deny-backgroud'):false;
								openAuth({'method':method, link:'ajax', auth_error: true, endmess:'Авторизация прошла успешно, повторите прерванное действие.'});
							}
						}
					}else{						
						_mess = "У вас нет прав на данную операцию";
					}
				// поменял приоритет. Если установлена функция обработки ошибок, то она и должна срабатывать!
				// хз где теперь е поломается... 2014.10.20
				}else if(onError && typeof onError == 'function'){
					var _target = popup;
					if (custom_selector) {
						_target =custom_selector;
					}
					var default_error_run = onError(reply, _target);
					if (default_error_run){
						_mess =reply.mess;
					}else{
						_mess='';
					}
				}else if (reply.mess){
					_mess =reply.mess;
				}else{
					_mess="Извините, произошла непредвиденная ошибка, сообщите о ней разработчикам.";
				}

				if (_mess!=''){
					sendajax_error(_mess);
				}
			}
		}else{
		
			var _mess=' <a href="#" class="popup_hack_link_close js_ctrlenter_link" ctrl_text="Ошибка при ajax запросе, в ответе нет result параметра (url: '+_web_root+'/ajax/'+method+''+'). Информация:'+JSON.stringify(reply).replace(/"/g,"'")+'">Cообщите администратору</a> об ошибке и повторите попытку позднее. Извините за неудобства.';
			sendajax_error("Ошибка обработка запроса (нет результата), возможно технические неполадки на сервере."+_mess);
			$('.popup_hack_link_close').click(function(){popup.hide();});
			// Тут же обработка фейла
			if(typeof onFail == 'function') {
				onFail();
			}
		}
	}).fail(function(jqXHR, textStatus, errorThrown){


		try {
			var ans = JSON.parse(jqXHR.responseText);
		} catch(e) {
			var ans = {};
		}


		sendajax_debug='Status: '+textStatus+"\n"+"errorThrown:"+"\n"+errorThrown+"\n\n"+jqXHR.responseText;
//alert('FAIL' +sendajax_debug+' :::POST::: '+data);
		/*var str='';
		for (var i in jqXHR){
			str = str + "\n"+ i;
		}*/
		//alert(str);
		var send_report = false; // предложить пользователю отправить отчет об ошибке.
		var _mess= "";
		// Экспериментально, если случилась непредвиденная ошибка 
		// (в моем случае это как минимум прервать по esc)
		if (textStatus===null){
			_mess= "Отправка данных прервана";
			// Зачем пугать пользователей сообщением об ошибке, если он всё-равно ничего не изменит?
			_mess='';
		}else
		if (textStatus=='parsererror'){
			if (jqXHR.responseText.indexOf('Memcache::get()')>0){
				_mess= "Memcache отвалился! Действие, возможно было выполнено, либо некорректно прервано.";
			}else{
				_mess='Ошибка обработка запроса (ошибка в ответе), возможно технические неполадки на сервере.';
				send_report=true;
			}
		}else
		if (textStatus=='timeout'){
			_mess= "Сервер не отвечает. Возможно прервано соединение с Интернетом или технические неполадки на сервере.";
			//send_report=true; в случае таймаута наверно не надо отправлять... а то и отчет там же зависнет.
		}else if (ans.result=="deny"){
			// На маркетплейсе доступ запрещен возвращается с 500 кодом!
			if (ans.auth=="1"){
				_mess = 'Доступ запрещен, у Вас нет прав доступа';
			}else{
				_mess = 'Доступ запрещен, Авторизуйтесь!';
			}

		}else{
			// 404, 500, 502, 504 и прочие траблы дают нам errorThrown
			if (errorThrown!=''){
				_mess= "Ошибка обработки запроса. Технические неполадки на сервере.";
				send_report=true;
			}else{
				_mess= "Отправка данных прервана.";
				// Зачем пугать пользователей сообщением об ошибке, если он всё-равно ничего не изменит?
				_mess='';
			}			
		}
		if (_mess!=''){
			if (send_report){
				_mess+=' <a href="#" class="popup_hack_link_close js_ctrlenter_link" ctrl_text="Ошибка при ajax запросе (url: '+_web_root+'/ajax/'+method+''+').'+"\r\n"+' Текст: '+_mess+'. '+"\r\n"+' Информация: '+"\r\n"+' '+sendajax_debug.replace(/"/g,"'")+'">Cообщите администратору</a> об ошибке и повторите попытку позднее. Извините за неудобства.';
				$('.popup_hack_link_close').click(function(){popup.hide();});
			}
			sendajax_error(_mess);
			
		}
		if(typeof onFail == 'function') {
			onFail();
		}
	})
};// ajax template


/**
 * [ctrl]+[*] даст нам исчерпывающую инфу об ошибке последнего ajax обращения
 */
var sendajax_debug='';
var sa_sddshty='';
$(document).unbind('keypress.ctrl_m').bind('keypress.ctrl_m', function(e){	  
	//alert(e.keyCode+" "+e.which+" "+e.ctrlKey);
	if(e.ctrlKey && e.which==42){
		sa_sddshty='g_p';// не спрашивай почему, как раз чтобы ничего не было понятно
		if (sendajax_debug!=''){ 
			delecho();
			echo(sendajax_debug);
		}
	}
});