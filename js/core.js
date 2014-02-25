$(document).ready(function(){
	$("#claimFormSubmit").click(function(){
		$("div.form-group").removeClass("has-error");
		$("p.help-block").html("").hide();
		
		// Проверки
		var err = 0;
		
		if(!$("#nickname").val())
		{
			err++;
			addError('nickname', 'Введите Ваш ник');
		}
		
		if(!(/^(STEAM_|VALVE_)[0-9]:[0-9]:[0-9]{5,12}$/i).test($("#steamid").val()))
		{
			err++;
			addError('steamid', 'Неверно введен SteamID');
		}
		
		if(!(/^([a-z0-9_-]+.)*[a-z0-9_-]+@([a-z0-9][a-z0-9-]*[a-z0-9].)+[a-z]{2,4}$/i).test($("#email").val()))
		{
			err++;
			addError('email', 'Неверно введена почта');
		}
		
		if(!$("#adminNick").val())
		{
			err++;
			addError('adminNick', 'Введите ник админа');
		}
		
		if(!$("#reason").val())
		{
			err++;
			addError('reason', 'Введите причину');
		}
		
		if($("#history").val().length < 10)
		{
			err++;
			addError('history', 'Напишите несколько слов');
		}
		
		if(!$("#captcha").val())
		{
			err++;
			addError('captcha', 'Введите код проверки');
		}
		
		if(err > 0)
		{
			return $().toastmessage('showToast', {
				text		: 'Исправьте ошибки',
				stayTime	: 2500,
				sticky		: false,
				position	: 'top-center',
				type		: 'error'
			});
		}
		
		
		$.post("", $("#claimForm").serialize(), function(data)
		{
			data = $.parseJSON(data);
			if(data.error)
				addError(data.input, data.text);
			else
				$(".form-control").val("");
			
			return $().toastmessage('showToast', {
				text		: data.text,
				stayTime	: 3000,
				sticky		: false,
				position	: 'top-center',
				type		: data.type
			});
		});
		return false;
	});
	
	$("#installForm").submit(function(){
		$("div.form-group").removeClass("has-error");
		$("p.help-block").html("").hide();
		var err = 0;
		if(!$("#dbhost").val())
		{
			err++;
			addError('dbhost', 'Введите адрес сервера');
		}
		if(!$("#dbuser").val())
		{
			err++;
			addError('dbuser', 'Введите имя пользователя');
		}
		if(!$("#dbpassword").val())
		{
			err++;
			addError('dbpassword', 'Введите пароль');
		}
		if(!$("#dbname").val())
		{
			err++;
			addError('dbname', 'Введите название базы');
		}
		if(!(/^[a-z]{2,4}$/i).test($("#dbprefix").val()))
		{
			err++;
			addError('dbprefix', 'Неверно введен префикс таблиц. Разрешено от 2 до 4 латинских букв.');
		}
		
		if(err > 0)
		{
			var toast = $().toastmessage('showToast', {
				text		: 'Исправьте ошибки',
				stayTime	: 2500,
				sticky		: false,
				position	: 'top-center',
				type		: 'error'
			});
			return false;
		}
		$.post("index.php?page=install", $("#installForm").serialize(), function(data){eval(data);});
		return false;
	});
});

function addError(element, msg)
{
	$("#" + element).closest("div.form-group").addClass("has-error");
	$("#" + element + "-msg").html(msg).removeClass("hidden").show();
}