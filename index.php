<?php
header("Content-type: text/plain; charset=UTF-8");

# input parameter from GET request
$humid = $_GET['humid'];
$temp = $_GET['temp'];
$abs_hum = $_GET['abs_hum'];
$CO2 = $_GET['co2'];
$baro = $_GET['baro'];

# setting parameters
$datetime = date("Y/m/d_H:i:s");
$string = $datetime . "," . $humid . "," . $temp . "," . $abs_hum . "," . $CO2 . "," . $baro . "\n";
$header = "datetime,humidity[%],temperature[C],absolute_humidity[g/m3],CO2_density[ppm],barometer[Pa]\n";
$lines = array();

# setting Slack URL
$url = file_get_contents("webhook.txt");
$url = str_replace(array("\r","\n"),"",$url);

# check output file
if (!(file_exists("output.csv"))){
	$fs = fopen("output.csv","w");
	fwrite($fs,$header);
	fwrite($fs,$string);
	fclose($fs);
} else {
	# read output file for 1000 lines
	$fs = fopen("output.csv","r");
	$i = 0;
	$line = fgets($fs);
	while (($line = fgets($fs))){
		if ($i > 999) {
			break;
		}
		$lines[$i] = $line;
		$i = $i + 1;
	}
	fclose($fs);

	# write data to output file
	$fs = fopen("output.csv","w");
	fwrite($fs,$header);
	fwrite($fs,$string);
	foreach ($lines as &$row){
		fwrite($fs,$row);
	}
	fclose($fs);
}

# create message
$message = "";

# sensor error check
if ($humid == "" || $temp == "" || $abs_hum == "" || $CO2 == "" || $baro == "") {
	$message = $message . "センサーエラーです。\n";
}

# alart for humidity
if ($abs_hum > 15){
	$message = $message . "部屋の湿度が高いです。\n絶対湿度は" . $abs_hum . "です。\n";
} elseif ($abs_hum < 4){
	$message = $message . "部屋が乾燥しています。\n絶対湿度は" . $abs_hum . "です。\n";
}

# alart for CO2 density
if ($CO2 > 2000){
	$message = $message . "部屋の二酸化炭素濃度が高いです。換気してください。\n二酸化炭素濃度は" . $CO2 . "ppmです。\n";
}

# create payload
$payload = [
	"text" => $message
];

# check in house or not
$IP = "192.168.179.11";
$port = "5555";
$check_url = $IP . ":" . $port . "/get";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $check_url);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$check = curl_exec($ch);

if (strpos($check,"0") !== false){

	# send to slack
	if ($message !== ""){
		$ch = curl_init();
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query(array(
				'payload' => json_encode($payload)
			))
		);
		curl_setopt_array($ch, $options);
		curl_exec($ch);
		curl_close($ch);
	}
}

echo $check;

?>
