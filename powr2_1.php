<?php
session_start();
ini_set('display_errors', 1);

/*
	#яжФИНН - Автор
	
	Для Windows:
	Если не установлен Web Server то читайте шаг номер 1.
	1. Установите XAMPP с пакетами apache и PHP. В гугле легко найти.
	2. Найдите в дирректории xampp папку htdocs и положите в нее этот файл powr2_1.php
	3. Откройте браузер и перейдите по адресу http://localhost/powr2_1.php
	
	Для Linux Debian
	Если не установлен Web Server то читайте шаг номер 1.
	1. В терминале - 
			sudo apt install apache2 php
			sudo chown -R $USER:$USER /var/www
	2. Разместите этот файл в /var/www/html/powr2_1.php
	3. Откройте браузер и перейдите по адресу http://localhost/powr2_1.php
*/

$project_name		= 'Sonoff POW R2';

$pow_r2				= '192.168.1.70'; 	// IP адрес Вашего Sonoff POW R2
$socket_1			= '192.168.1.11'; 	// IP адрес первой розетки Sonoff S26
$socket_2			= '192.168.1.12'; 	// IP адрес второй розетки Sonoff S26
$refresh_interval 	= 5; 				// Update interval (seconds)

///////////////////////////////////
// <---  То что находится ниже, можно изменять под себя. Для тех кто не умеет, лучше не трогать -->
if(isset($_POST['update']))
{

	$pow_r2_energy			= STATUS10($pow_r2);
	$cur_state_socket_1		= STATUS10($socket_1);
	$cur_state_socket_2		= STATUS10($socket_2);

	function EnergyStates($socket_1, $socket_2)
	{
		global $pow_r2_energy, $cur_state_socket_1, $cur_state_socket_2;

		if($cur_state_socket_1['STATUS'] == 'ON' and $cur_state_socket_2['STATUS'] == 'ON')
		{
			$cur_state_socket_1['STATUS'] = on_off($socket_1, "OFF");
			$cur_state_socket_2['STATUS'] = on_off($socket_2, "OFF");
			return true;
		}
		if($pow_r2_energy['ENERGY']['Power'] < 4)
		{
			if($cur_state_socket_1['STATUS'] == 'OFF')
			{
				$cur_state_socket_1['STATUS'] = on_off($socket_1, "ON");
				$cur_state_socket_2['STATUS'] = on_off($socket_2, "OFF");
				return true;
				
			} else if($cur_state_socket_1['STATUS'] == 'ON')
			{
				$cur_state_socket_1['STATUS'] = on_off($socket_1, "OFF");
				$cur_state_socket_2['STATUS'] = on_off($socket_2, "ON");
				return true;
			}

		}
	}

	EnergyStates($socket_1, $socket_2);

	$return = [
		'cur_time' 				=> date("H:i:s"),
		'pow_r2_energy'			=> $pow_r2_energy,
		'cur_state_socket_1' 	=> $cur_state_socket_1['STATUS'],
		'cur_state_socket_2' 	=> $cur_state_socket_2['STATUS']
	];
	
	echo json_encode($return);
	exit;
	
} else {
	on_off($socket_1, "OFF");
	on_off($socket_2, "OFF");
}

function STATUS10($ip)
{

	$url 		= 'http://'.$ip.'/cm?cmnd=status%200';
	$ch 		= curl_init();
	$timeout 	= 10;

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

	$json = json_decode(curl_exec($ch));
	curl_close($ch);
		
	// <-- Another type to get data
	//$json = json_decode(@file_get_contents('http://'.$ip.'/cm?cmnd=status%2010'));

	$return = [
		'STATUS' 	=> 'Offline',
		'ENERGY' 	=> ['Yesterday' => '', 'Today' => '', 'Power' => '']
	];

	if(isset($json->POWER)){
		$return['STATUS'] = $json->POWER;
	}
	
	if( isset($json->StatusSTS->POWER) ){
		$return['STATUS'] = $json->StatusSTS->POWER;
		
		if( isset($json->StatusSNS->ENERGY) )
		{
			$return['ENERGY']['Yesterday'] 	= $json->StatusSNS->ENERGY->Yesterday;
			$return['ENERGY']['Today'] 		= $json->StatusSNS->ENERGY->Today;
			$return['ENERGY']['Power'] 		= $json->StatusSNS->ENERGY->Power;
		}
	}

	return $return;
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
		   		<div style="float:right">Time: <span id="cur_time">--:--:--</span></div>
		   		<span class="text-success"><?=$pow_r2?></span><br>
				<h2>Power now (W): <span class="text-success" id="cur_power">0</span></h2>
				<h2>Status: <span id="cur_state">---</span></h2>
				<hr>
				<p>
					<div class="row">
						<div class="col-sm-6">
							<span class="text-success"><?=$socket_1?></span><br>
							<h2>S1: <span id="cur_state_socket_1">---</span></h2>
						</div>
						<div class="col-sm-6">
							<span class="text-success"><?=$socket_2?></span><br>
							<h2>S2: <span id="cur_state_socket_2">---</span></h2>
						</div>
					</div>
				</p>
				<br>
				<center><button class="btn btn-lg btn-success start">START</button></center>
			</div>
		  </div>
		</div>
	</body>
</html>

<script type="text/javascript">
$(document).ready(function(){

	var interval;

	$(document).delegate(".stop", "click", function(){
		$(this).replaceWith('Please wait..');
		clearInterval(interval);
		window.location.reload();
	});
  
	$(document).delegate(".start", "click", function(){
		var thisBtn = $(this);
		thisBtn.removeClass('btn-success start').addClass('btn-danger stop').text('STOP');
		geter();
		interval = setInterval(geter, <?=$refresh_interval*1000?>);
	});
	
	function geter()
	{
		$.ajax({
			url: '#',
			type: 'POST',
			data: { update : true },
			success: function(data){
				data = JSON.parse(data);
				console.log(data);
				if(data['ERROR'])
				{
					$('#cur_time').html('<h2 class="text-danger">' + data['ERROR'] + '</h2>');
					return false;
				}
				if(data['cur_time'])
				{
					$('#cur_time').html(data['cur_time']);
					$('#cur_state_socket_1').html((data['cur_state_socket_1'] == "ON")? "<b class='text-success'>" + data['cur_state_socket_1'] + "<b>" : "<b class='text-danger'>" + data['cur_state_socket_1'] + "<b>");
					$('#cur_state_socket_2').html((data['cur_state_socket_2'] == "ON")? "<b class='text-success'>" + data['cur_state_socket_2'] + "<b>" : "<b class='text-danger'>" + data['cur_state_socket_2'] + "<b>");
					
					if(data['pow_r2_energy']['ENERGY'])
					{
						$('#cur_state').html((data['pow_r2_energy']['STATUS'] == "ON")? "<b class='text-success'>" + data['pow_r2_energy']['STATUS'] + "<b>" : "<b class='text-danger'>" + data['pow_r2_energy']['STATUS'] + "<b>");
						$("#cur_power").html('<b>' + data['pow_r2_energy']['ENERGY']['Power'] + '</b>');
					}
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				console.log(XMLHttpRequest);
			}
		});
		
	}
  
});
</script>
