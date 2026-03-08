<?php
$url = "http://localhost/cefi_reservation/api/get_chatbot_calendar.php?api_key=CEFI_CHATBOT_2026";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "SUCCESS: API Responded 200.\n";
    $data = json_decode($response, true);
    if ($data) {
        echo "Valid JSON received.\n";
        echo "Upcoming Occasions found: " . count($data['special_occasions']) . "\n";
        foreach($data['special_occasions'] as $occ) {
            echo "- " . $occ['title'] . " (" . $occ['type'] . ") on " . $occ['occasion_date'] . "\n";
        }
    } else {
        echo "FAILURE: Invalid JSON received.\n";
        echo $response;
    }
} else {
    echo "FAILURE: HTTP Code $httpCode.\n";
}
?>
