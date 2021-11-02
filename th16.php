<?php
session_start();
ini_set('display_errors', 1);

/*
	#яжФИНН - Автор
*/

$project_name 		= 'TH16 & DS18B20';

$sonoff_IP			= '192.168.1.52'; // IP адрес Вашего Sonoff TH16
$refresh_interval 	= 5; 	// seconds
$start_temp			= 24; 	// Температура для включения Sonoff
$stop_temp			= 25; 	// Температура для выключения Sonoff

///////////////////////////////////
// <---  То что находится ниже, можно изменять под себя. Для тех кто не умеет, лучше не трогать -->

$sonoff_sensor		= STATUS10($sonoff_IP);
$sonoff_state		= RESULT($sonoff_IP);

if($sonoff_state == "ON")
	$sonoff_state_text = '<b class="text-success">ON</b>';
else
	$sonoff_state_text = '<b class="text-danger">'.$sonoff_state.'</b>';

if(isset($_POST['update']))
{

	if(isset($_POST['device_stop']))
	{
		on_off($sonoff_IP, "OFF");
		exit;
	}

	if($_POST['stop_temp'] > $_POST['start_temp'])
	{
		if( $sonoff_sensor['Temperature'] < $_POST['start_temp'] and $sonoff_state == "OFF" ){
			$sonoff_state = on_off($sonoff_IP, "ON");
		} else if( $sonoff_sensor['Temperature'] > $_POST['stop_temp'] and $sonoff_state == "ON" ){
			$sonoff_state = on_off($sonoff_IP, "OFF");
		}

	} else {
		echo json_encode(['ERROR' => 'Не правильно заданы температуры']);
		exit;
	}
	
	$return = [
		'cur_time' 		=> date("H:i:s"),
		'cur_temp' 		=> $sonoff_sensor['Temperature'],
		'cur_state' 	=> $sonoff_state_text
	];
	
	echo json_encode($return);
	exit;
} else {
	on_off($sonoff_IP, "OFF");
}

function STATUS10($url)
{

	$url 		= 'http://'.$url.'/cm?cmnd=status%2010';
	$ch 		= curl_init();
	$timeout 	= 2;

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

	$json = json_decode(curl_exec($ch));
	curl_close($ch);
	
	$arr 	= [];
	$arr['Temperature'] = '<span class="text-danger">Offline / Sonoff не обнаружен.</span>';
	
	if( isset($json->StatusSNS->SI7021))
	{
		if( isset($json->StatusSNS->SI7021->Temperature))
			$arr['Temperature'] = $json->StatusSNS->SI7021->Temperature;
		if( isset($json->StatusSNS->SI7021->Humidity))
			$arr['Humidity'] = $json->StatusSNS->SI7021->Humidity;
		
	} elseif( isset($json->StatusSNS->DS18B20))
	{
		if( isset($json->StatusSNS->DS18B20->Temperature))
			$arr['Temperature'] = $json->StatusSNS->DS18B20->Temperature;
	}
	
	return $arr;
}

function RESULT($url)
{
	
	$url 		= 'http://'.$url.'/cm?cmnd=power';
	$ch 		= curl_init();
	$timeout 	= 1;

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

	$json = json_decode(curl_exec($ch));
	curl_close($ch);
	//print_r($result);
	//exit;
	//$json = json_decode(@file_get_contents('http://'.$url.'/cm?cmnd=power'));

	if(isset($json->POWER)){
		return $json->POWER;
	} else {
		return '<span class="text-danger">Offline / Sonoff не обнаружен.</span>';
	}
}

// <-- ON/OFF
function on_off($laite, $state){
	
	$return = '';
	if($state == 'ON'){
		$url = 'http://'.$laite.'/cm?cmnd=Power%20ON';
		$return = 'ON';
	}
	if($state == 'OFF'){
		$url = 'http://'.$laite.'/cm?cmnd=Power%20OFF';
		$return = 'OFF';
	}
	
	$ch 		= curl_init();
	$timeout 	= 1;

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

	$json = json_decode(curl_exec($ch));
	curl_close($ch);
	
	return $return;
}
?>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

		<title><?=$project_name?></title>
	</head>
	<style>
		input[type="number"]{ font-size: 150%; }
	</style>
	<body class="">
		<div class="container-lg container-fluid" style="margin-top:100px">
		  <div class="row">
		   	<div class="col-sm-8 offset-sm-2">
		   		<center><h1><?=$project_name?></h1></center>
		   		<h4>IP адрес устройства: <span class="text-success"><?=$sonoff_IP?></span></h4>
				<h4>Текущее время: <span class="text-success" id="cur_time">--:--:--</span></h4>
				<h4>Температура на датчике: <span class="text-success" id="cur_temp"><?=$sonoff_sensor['Temperature']?></span></h4>
				<h4>Состояние Sonoff: <span class="text-success" id="cur_state"><?=$sonoff_state_text?></span></h4>
				
				<p>
					<div class="row">
						<div class="col-sm-6">
							<h2>Включать ниже чем:</h2>
							<input type="number" id="start_temp" class="form-control input-lg" value="<?=$start_temp?>">
						</div>
						<div class="col-sm-6">
							<h2>Выключать выше чем:</h2>
							<input type="number" id="stop_temp" class="form-control input-lg" value="<?=$stop_temp?>">
						</div>
					</div>
				</p>
				<center><button class="btn btn-lg btn-success start">СТАРТ</button></center>
			</div>
		  </div>
		</div>
	</body>
</html>

<script type="text/javascript">
$(document).ready(function(){

	var interval;

	$(document).delegate(".stop", "click", function(){
		clearInterval(interval);
		$(this).removeClass('btn-danger stop').addClass('btn-success start').text('СТАРТ');

		$.ajax({
			url: '#',
			type: 'POST',
			data: { update : true, device_stop : true },
			success: function(data){
				data = JSON.parse(data);
				//console.log(data);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				console.log(XMLHttpRequest);
			}
		});
		
	});
  
	$(document).delegate(".start", "click", function(){
		$(this).removeClass('btn-success start').addClass('btn-danger stop').text('СТОП');
		geter();
		interval = setInterval(geter, <?=$refresh_interval*1000?>);
	});
	
	function geter()
	{
		var start_temp 	= parseFloat($('#start_temp').val());
		var stop_temp 	= parseFloat($('#stop_temp').val());

		$.ajax({
			url: '#',
			type: 'POST',
			data: { update : true, start_temp : start_temp, stop_temp : stop_temp },
			success: function(data){
				data = JSON.parse(data);
				//console.log(data);
				if(data['ERROR'])
				{
					$('#cur_time').html('<h2 class="text-danger">' + data['ERROR'] + '</h2>');
					return false;
				}
				if(data['cur_time'])
				{
					$('#cur_time').html(data['cur_time']);
					$('#cur_temp').html(data['cur_temp']);
					$('#cur_state').html(data['cur_state']);
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				console.log(XMLHttpRequest);
			}
		});
		
	}
  
});
</script>
