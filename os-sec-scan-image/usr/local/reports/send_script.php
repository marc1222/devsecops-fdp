<?php
// This script is prepared to be used inside GitLab runner among the pipeline

function getsendSecret() {
    $secret = FALSE;
    $secret_file = "/usr/local/reports/send_report_secret.txt";
    if (is_file($secret_file) ) {
        $secret = file_get_contents($secret_file);
    }
    return $secret;
}

$usage = "php send_script.php";

$server_url                 = getenv('APP_ENDPOINT');
$web_page_to_send           = $server_url."receive.php";

$secret                     = getsendSecret();
$project                    = getenv('PROJECT_NAME');
$type                       = getenv('TYPE');  //(OS/APP)
$repo_url                   = getenv('REPO');
$upload_path                = getenv('UPLOAD_DIR');
$file_report_with_full_path = $upload_path."/".$type."SecScanReport.html";
$file_stats_with_full_path  = $upload_path."/stats".$type.'.json';
$details                    = (isset($argv[1])) ? $argv[1] : '';

$post_request = array(
     "auth"     =>  $secret,
     "project"  =>  $project,
     "type"     =>  $type,
     "url"      =>  $repo_url,
     "details"  =>  $details,
	 "report"   =>  curl_file_create($file_report_with_full_path), // for php 5.5
	 "stats"    =>  curl_file_create($file_stats_with_full_path) // for php 5.5
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $web_page_to_send);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_request);
$result = curl_exec($ch);
if (curl_errno($ch)) echo curl_error($ch);
//else echo "Curl ResponseCode: ".curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch); ?>