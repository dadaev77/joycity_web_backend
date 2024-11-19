<?php
function sendSMS($phone, $text)
{
    $curl = curl_init();

    $textParams = http_build_query(['mes' => $text]);

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://smsc.ru/sys/send.php?login=Shell%20Promo&psw=12348765&phones=' . $phone . '&' . $textParams . '&sender=TEBOIL-AZS',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);
}

$row = 1;
if (($handle = fopen("file.txt", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, "	")) !== FALSE) {
        $text = "Эл. почта: " . $data[0] . ", Пароль: " . $data[1];
        //var_dump($text);
        sendSMS($data[2], $text);
    }
    fclose($handle);
}