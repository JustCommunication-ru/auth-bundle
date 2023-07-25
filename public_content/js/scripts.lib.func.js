////////////////////////////////////////////////////////////////////////////////
//############################################################################//
//##########Самописные функции, аналоги PHP и просто вспомогательные##########//
//############################################################################//
////////////////////////////////////////////////////////////////////////////////

function scrollTo(selector, noanimate){
	scroll_to(selector, noanimate);
}
function scroll_to(selector, noanimate){
	var noanimate = noanimate || false;
	if ($(selector).length){
		
		// Извращение для клауд-мобайл и блок для 
		if ($('.app-scrollable').length==0 && ($('.special_scroll').length>0 && $('main').length>0 && $('article').length>0)){
		//if ($('header.app-header').length>0 && $('nav.app-tabbar').length>0 && $('main').length>0 && $('article').length>0){
			// -1500-(-1200) показатели скрола зашкаливают за пределы окна, надо их нормировать
			// и скролить не боди, а мэин
			var val = -($('article').offset().top - $(selector).offset().top);
			//echo('scroll_to:main-article:'+(val-50));
			
			if (noanimate){
				$('main').scrollTop(val-50);
			}else{
				$('main').animate({
					scrollTop: val-50
				}, 600);
			}
		}else{
			//echo('scroll_to:global:'+($(selector).offset().top-50));
			//console.log('-3-');
			if (noanimate){
				$('html, body').scrollTop($(selector).offset().top-50);
			}else{
				$('html, body').animate({
					scrollTop: $(selector).offset().top-50
				}, 600);
			}
		}
	}else{
		console.log('scroll_to('+selector+') error, selector not found!');
	}
}

function rand(min, max) {
	return Math.floor(Math.random() * (max - min + 1)) + min;
}

// тут пишут что у этого способа будет лушче распределение
// https://learn.javascript.ru/task/random-int-min-max
//
function randomInteger(min, max) {
  // получить случайное число от (min-0.5) до (max+0.5)
  var rand = min - 0.5 + Math.random() * (max - min + 1);
  return Math.round(rand);
}

/**
 * Аналог php функции in_array
 * @param needle [mixed] элемент массива
 * @param haystack [array] массив
 * @return bool да/нет
 */
function in_array(needle, haystack) {
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	var found = false, key;
	for (key in haystack) {
		if (haystack[key] == needle) {
			found = true;
			break;
		}
	}
	return found;
}

function array_key(needle, haystack) {
	var found = false, key;
	for (key in haystack) {
		if (haystack[key] == needle) {
			found = key;
			break;
		}
	}
	return found;
}

/**
 * Аналог php функции trim
 * Убирает пустые/пробельные символы с обоих концов
 * В 10 раз быстрее чем прежний способ с двух сторон ltrim и rtrim
 * @param str [string] строка
 * @return string новая строка
 */
function trim(str) {	
	return str.replace(/^\s+|\s+$/g, '');
}


function number_format( number, decimals, dec_point, thousands_sep ) {	// Format a number with grouped thousands
	// 
	// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +	 bugfix by: Michael White (http://crestidg.com)

	var i, j, kw, kd, km;

	// input sanitation & defaults
	if( isNaN(decimals = Math.abs(decimals)) ){
		decimals = 2;
	}
	if( dec_point == undefined ){
		dec_point = ",";
	}
	if( thousands_sep == undefined ){
		thousands_sep = ".";
	}

	i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

	if( (j = i.length) > 3 ){
		j = j % 3;
	} else{
		j = 0;
	}

	km = (j ? i.substr(0, j) + thousands_sep : "");
	kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
	//kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
	kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


	return km + kw + kd;
}

	
/**
 * Вызывает зависание скрипта
 * @param ms [int]количество милисекунд на которые надо адски загрузить скрипт
 * @return none
 */
function _pause_(ms){
	var date=new Date();
	curDate=null;
	do{
		var curDate=new Date();
	}while(curDate-date<ms);
};

/**
 * То же что и parseInt, но в случае NaN возвращает 0. Часто использую при обработке подозрительных чисел.
 * @param string value - строка которую надо распарсить
 * @return int
 */
function parseIntNaN(value){
	var _value=parseInt(value);
	if (isNaN(_value))_value=0;
	return _value;
}

/**
 * Заполняет лидирующие символы до нужного количества
 * zero fill lead
 * alert(paddy("658", 7, '*'));
 * 
 * @param string str
 * @param int count
 * @param char pad_char
 * @returns string
 */
function paddy(str, count, pad_char){
	var count = count || 2;
	var pad_char = pad_char || '0';
	// готовим "заполнитель" заданной длинны
	var pad = new Array(1 + count).join(pad_char);
	// соединяем и убираем слева лишнее
	return (pad + str).slice(-pad.length);
}

/**
 * Выполняет проверку на возраст по дате dd.mm.yyyy
 * @param string str - дата dd.mm.yyyy
 * @param int age_min
 * @param int age_max
 * @returns bool
 */
function isage(str, age_min, age_max){   
	var age_min = age_min || 13;
	var age_max = age_max || 81;
	var date_now = new Date();
	var year_now = date_now.getFullYear();
	var date= str;
	if (date.length>=8){
		var date_arr=date.split(".");
		if (date_arr.length==3){
			var dd=parseInt(date_arr[0], 10), mm=parseInt(date_arr[1], 10),  yyyy=parseInt(date_arr[2], 10);
			if (dd>0 && dd<32 && mm>0 && mm<13 && yyyy>=(year_now-age_max) && yyyy<=year_now-age_min){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}else{
		return false;
	}
}

/**
 * Отладочный алерт для объектов
 * @param mixed val
 * @returns alerts
 */
function foralert(val){
	for (var i in val){
		alert(i+" "+val[i]);
	} 
}

/**
 * Функция неизвестного происхождения
 * UNUSED
 */
function specialParseFloat(str){
	// уничтожение лидирующего минуса, а вдруг...
	if (str.indexOf("-")==0){
		str= str.substr(1);
	}
	// Замена всего что не цифра - в точки
	var str = str.replace(/\D/g,".");
	// первое вхождение точки
	var i = str.indexOf(".");

	if (i>=0){
		// если есть точка
		if (str.indexOf(".", i+1)>=0){
			// Если точек более чем одна то ошибка - ноль
			//alert("DVA "+str+"=>0")
			return 0;
		}else{
			// если точка только одна
			//alert("OK_FLOAT "+parseFloat(str));
			return parseFloat(str);
		}
	}else{
		// если точек нет, значит целое
		//alert("OK_INT "+parseInt(str));
		return parseInt(str);
	}
}

/**
 * Проверяет является ли заданная строка валидным емаилом
 * @param string str  - строка мыло
 * @return bool да/нет
 */
function isValidEmail(str/*, strict*/){
 //if ( !strict ) email = email.replace(/^\s+|\s+$/g, '');
	return (/^([a-z0-9_\-]+\.)*[a-z0-9_\-]+@([a-z0-9][a-z0-9\-]*[a-z0-9]\.)+[a-z]{2,4}$/i).test(str);
}

/**
 * 
 * @param {type} str
 * @returns {RegExp}
 */
function isValidRandomCode(str,len){
	var len = len || false;
	if (len===false){
		alert('Ошибка использования функции isValidRandomCode, не указан параметр длины строки');
	}
	//var regexp = new RegExp('/^[a-z0-9]{'+len+'}$/i');
	return (new RegExp('^[a-z0-9]{'+len+'}$', "i")).test(str);
}

/**
 * Валидность пароля
 * @param string str
 * @param int min_length
 * @param int max_length
 * @returns bool
 */
function isValidPass(str, min_length, max_length){
	var min_length = min_length || 4; // для ГА
	var max_length = max_length || 33;
	// так и не понял, надо бэк-слеш ставить или нет
	return (new RegExp('^[-a-zA-Zа-яА-ЯёЁ0-9_\s!@%#*$%^&*()+=?,.]{'+min_length+','+max_length+'}$')).test(str);
}

/**
 * Валидность номера мобильного телефона 
 * примеры: +79028889090 79028889090 89028889090
 * ебанутый жаваскрипт, в отличии от PHP надо скобки поставить и дважды экранировать плюс
 * @param {type} str
 * @returns {Boolean}
 */
function isValidPhone(str){
	return (new RegExp('^(\\+7|7|8)[0-9]{10}$')).test(str);
}

/**
 * Валидность имени
 * @param string str
 * @param int min_length
 * @param int max_length
 * @returns bool
 */
function isValidName(str, min_length, max_length){
	var min_length = min_length || 3;
	var max_length = max_length || 35;
	// \s заменен на пробел, в символьных классах \s не канает
	return (new RegExp('^[-A-zА-яёЁ0-9_ .]{'+min_length+','+max_length+'}$')).test(str);
}


/*
 * Просто вырезает из ссылки часть с хешем (вместе с решеткой)
 */
function locationStripHash(str){
	var str = str || document.location.href;

	var sharp_pos = str.indexOf("#");
	if (sharp_pos==0){
		str='';
	}else if (sharp_pos>0){
		str=str.substr(0,sharp_pos);
	}
	//alert(sharp_pos+" "+str);
	return str;
}
		
//Если с английского на русский, то передаём вторым параметром true.


function translit(text, engToRus) {
	var x,
	rus = "щ   ш  ч  ц  ю  я  ё  ж ъ ы  э а б в г д е з и й к л м н о п р с т у ф х ь".split(/ +/g),
	eng = "sch sh ch ts yu ya yo j . yi e a b v g d e z i y k l m n o p r s t u f h .".split(/ +/g)
	;
	for(x = 0; x < rus.length; x++) {
		text = text.split(engToRus ? eng[x] : rus[x]).join(engToRus ? rus[x] : eng[x]);
		text = text.split(engToRus ? eng[x].toUpperCase() : rus[x].toUpperCase()).join(engToRus ? rus[x].toUpperCase() : eng[x].toUpperCase());	
	}
	//text.replace(/ +/g, '_');
	return text.replace(/ +/g, '_');
}

/**
 * Преобразование текста из html элемента просморта в edit версию для input/textarea
 * @param {type} str
 * @returns {unresolved}
 */
function editVal(str){
	str = str.replace(new RegExp("<br>",'g'), "\n");
	return str;
}
/**
 * Обратное преобразование из редактируемого текста в отображаемое (div/p)
 * @param {string} str
 * @returns {string}
 */
function viewVal(str){
	str = str.replace(new RegExp("\r",'g'), "");
	str = str.replace(new RegExp("\n",'g'), "<br>");
	str = str.replace(new RegExp("\t",'g'), "");
	// В чем разница не пойму:
	//str = str.replace(new RegExp('\s+$','g'), '');
	// работает только так
	str = str.replace(/\s+$/g, '');
	// но так:
	str = str.replace(new RegExp("(<br>)+$",'g'), '');
	str = str.replace(/^\s+|\s+$/g, '');
	//alert(str);
	return str;
}

/**
 * Запускает и выключает анимацию прелоадера для ссылки
 * @param {object} selector обязательно jQuery объект типа $(selector)
 * @returns {Boolean}
 */
function stylizeLoadLink(selector){
	if (!$(selector).hasClass('active-loader')){
		var main_color = '#4D4D4D';
		if ($(selector).hasClass('btn-primary')){
			main_color = '#FFFFFF';
		}
		$(selector).css({'text-decoration':'none'}).html('<span>'+($(selector).html().split("")).join('</span><span>')+'</span>');
		$(selector).addClass('active-loader').css({color:main_color}).attr('cur','0');
		selector.load_handler = setInterval(function(){
			stylizeLoadLinkIterator(selector, main_color);
		}, 100);
	}else{
		//console.log('stop interval');
		clearInterval(selector.load_handler);
		$(selector).html($(selector).text()).removeClass('active-loader')
			.css({color:''}).attr('cur','0');
	};
	return false;
}

/**
 * Обработчик анимации для ссылки, цвета фиксированные, надо предусмотреть изменяемость
 * @param {object} selector обязательно jQuery объект типа $(selector)
 * @returns {Boolean}
 */
function stylizeLoadLinkIterator(selector, main_color){
	var main_color = main_color || '#FA4141';
	var i = parseIntNaN($(selector).attr('cur'));
	var j = (i<$(selector).find('span').length)?i+1:0;
	$(selector).attr('cur', j);
	//console.log(i+' '+j+' '+selector+' '+$(selector).find('span').length+ ' '+$(selector).find('span:eq('+i+')').length);
	$(selector).find('span:eq('+i+')')//.eq(i))
	  .animate({'font-size': '20px;'}, 100)
	  .animate({'color': '#FA4141'}, 100)
	  .delay(50)
	  .animate({'color': main_color}, 500);
}
//----------------------------------------------------------------------
/* array(0 => 'автомобиль', 1=>'автомобиля', 2=>'автомобилей', 3=>'') */
function wordCase(count){
	if ([11,12,13,14].indexOf(count%100)>=0 || [0,5,6,7,8,9].indexOf(count%10)>=0){
		return 2; // нет много автомобилей
	}else if (count%10===1){
		return 0; // вижу один автомобиль
	}else{
		return 1; // два-три-четыре атомобиля (2,3,4)
	}
	
}

function copy(str){
  var tmp   = document.createElement('INPUT'), // Создаём новый текстовой input
      focus = document.activeElement; // Получаем ссылку на элемент в фокусе (чтобы не терять фокус)

  tmp.value = str; // Временному input вставляем текст для копирования

  document.body.appendChild(tmp); // Вставляем input в DOM
  tmp.select(); // Выделяем весь текст в input
  document.execCommand('copy'); // Магия! Копирует в буфер выделенный текст (см. команду выше)
  document.body.removeChild(tmp); // Удаляем временный input
  focus.focus(); // Возвращаем фокус туда, где был
}
/*
document.addEventListener('DOMContentLoaded', e => {
  var input = document.querySelector('#input'),
      bCopy = document.querySelector('#bCopy'),
      log   = document.querySelector('#log');
  
  bCopy.addEventListener('click', e => {
    if(input.value){
      try{
        copy(input.value);
        log.style.color = 'green';
        log.innerHTML = 'Скопировано!';
      }catch(e){
        log.style.color = 'red';
        log.innerHTML = 'Ошибка!';
      }
    }
  });
}
	);)
	*/