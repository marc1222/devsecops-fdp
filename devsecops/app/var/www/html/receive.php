<?php
// Response code meaning
// 200 -> uploaded OK
// 403 -> error (wrong params or wrong auth or error)

$upload_dir                 = "/var/www/Reports/";
$influx_db                  = getenv('INFLUX_DB');
$server_url                 = getenv('INFLUX_ENDPOINT');

if ( isset($_POST['project']) && isset($_POST['type']) && isset($_POST['url']) && isset($_POST['auth']) && isset($_FILES['report']) ) {
    $auth_secret = getrecvSecret("/etc/secret-volume/passwd");
    $client_auth = $_POST['auth'];
    if ($auth_secret !== FALSE && strcmp($client_auth, $auth_secret) === 0 ) {
        date_default_timezone_set("Europe/Paris");
        $project    = filter_var($_POST['project'], FILTER_SANITIZE_STRING);
        $url        = filter_var($_POST['url'], FILTER_SANITIZE_URL);
        $type       = filter_var($_POST['type'], FILTER_SANITIZE_STRING);
//      $details    = str_replace(',', ' ', filter_var($_POST['details'] ,FILTER_SANITIZE_STRING));
    	$file_name  = $project.'@'.$type.'_'.date("Y-m-d:H-i-s");
        if ( ($res = create_report($_FILES['report']['tmp_name'], $upload_dir.$project.'/', $file_name.'.html', $url)) !== FALSE) {
            header("HTTP/1.1", true, 200);
            echo "https://devsecops.your_site.es/index.php?report=".$file_name.".html\n";
            if ( isset($_FILES['stats']) ) {
                if ( ($res = create_stats($_FILES['stats']['tmp_name'], $upload_dir.$project.'/stats/', $file_name.'.json')) !== FALSE) {
                    if ( ($res = send_stats_influx($upload_dir.$project.'/stats/'.$file_name.'.json')) === FALSE) {
                        echo "Error sending stats to influxDB...\n";
                    }
                }
            }
    	} else header("HTTP/1.1", true, 403);
    } else header("HTTP/1.1", true, 403);
} else header("HTTP/1.1", true, 403);


#Function to get secret
function getrecvSecret($secretfile) {
    $secret = FALSE;
    if (is_file($secretfile) ) {
        $secret = file_get_contents($secretfile);
    }
    return $secret;
}

# Function to create a report file
function create_report($file, $dir, $filename, $url) {
    global $upload_dir;
    if (is_dir($dir) === FALSE) {
        mkdir($dir, 0755);
        $order = intval(file_get_contents($upload_dir.'index.txt')) + 1;
        file_put_contents($upload_dir.'index.txt', $order);
        $rand_color = substr(md5(mt_rand()), 0, 6);
        $text_color = getContrastColor('#'.$rand_color);
        file_put_contents($dir.'/info.txt', $rand_color.'|'.$text_color.'|'.$url.'|'.$order);
    }
    return move_uploaded_file($file, $dir.$filename);
}

# Function to create a statistics file
function create_stats($file, $dir, $filename) {
    if (is_dir($dir) === FALSE) {
        mkdir($dir, 0755);
    }
    return move_uploaded_file($file, $dir.$filename);
}

function getContrastColor($hexColor)
{
        // hexColor RGB
        $R1 = hexdec(substr($hexColor, 1, 2));
        $G1 = hexdec(substr($hexColor, 3, 2));
        $B1 = hexdec(substr($hexColor, 5, 2));
        // Black RGB
        $blackColor = "#000000";
        $R2BlackColor = hexdec(substr($blackColor, 1, 2));
        $G2BlackColor = hexdec(substr($blackColor, 3, 2));
        $B2BlackColor = hexdec(substr($blackColor, 5, 2));
         // Calc contrast ratio
         $L1 = 0.2126 * pow($R1 / 255, 2.2) +
               0.7152 * pow($G1 / 255, 2.2) +
               0.0722 * pow($B1 / 255, 2.2);

        $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
              0.7152 * pow($G2BlackColor / 255, 2.2) +
              0.0722 * pow($B2BlackColor / 255, 2.2);

        $contrastRatio = 0;
        if ($L1 > $L2) {
            $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
        } else {
            $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
        }
        if ($contrastRatio > 5) { // If contrast is more than 5, return black color
            return '000000';
        } else { // if not, return white color.
            return 'FFFFFF';
        }
}

//send stats to influx functions
function send_stats_influx($statfile) {

    $content                    = file_get_contents($statfile);

    //OS
    $trivy  = -1; //APP 2
    $snyk   = -1;
    $dockle = -1;
    //APP
    $shiftleft = -1;
    $depscan = -1;
    $credscan = -1;
    $num_library = -1;


    if ($content !== FALSE) {
        $json = json_decode($content, true);
        if (isset($json["metadata"]["project"]) && isset($json["type"])) {
            $project = str_replace( '-', '_', $json["metadata"]["project"]);
            $type = $json["type"];
            // construir el campo value de la query
            if ($type == "APP") {

                if (isset($json["shiftleft"])) {
                    $shiftleft_data = substr(get_array_values($json["shiftleft"]), 0, -1);
                    send_insert($project, "APP", "shiftleft", $shiftleft_data);
                }

                if (isset($json["credscan"]["secrets"])) {
                    $credscan_data = 'credscan_secrets=' . intval($json["credscan"]["secrets"]) . 'i';
                    send_insert($project, "APP", "credscan", $credscan_data);
                }

                if (isset($json["depscan"])) {
                    $depscan_data =   'depscan_critical=' . intval($json["depscan"]["critical"]) . 'i,'
                                    . 'depscan_high=' . intval($json["depscan"]["high"]) . 'i,'
                                    . 'depscan_medium=' . intval($json["depscan"]["medium"]) . 'i,'
                                    . 'depscan_low=' . intval($json["depscan"]["low"]) . 'i,'
                                    . 'depscan_total=' . intval($json["depscan"]["total"]) . 'i';
                    send_insert($project, "APP", "depscan", $depscan_data);
                }

                if (isset($json["components"])) {
                    $library_data =   'fossa_imports=' . intval($json["components"]["fossa_imports"]) . 'i,'
                                    . 'bom=' . intval($json["components"]["bom"]) . 'i';
                    send_insert($project, "APP", "library", $library_data);
                }

                if (isset($json["trivy"])) {
                    $trivy_data = substr(get_array_values($json["trivy"]), 0, -1);
                    send_insert($project, "APP", "trivy", $trivy_data);
                }

            } elseif ($type == "OS") {

                if (isset($json["trivy"])) {
                    $trivy_data = substr(get_array_values($json["trivy"]), 0, -1);
                    send_insert($project, "OS", "trivy", $trivy_data);
                }

                if (isset($json["snyk"])) {
                    $snyk_data =  'snyk_total=' . intval($json["snyk"]["total"]) . 'i,'
                                . 'snyk_vulnpaths=' . intval($json["snyk"]["vul_paths"]) . 'i';
                    send_insert($project, "OS", "snyk", $snyk_data);

                }

                if (isset($json["dockle"])) {
                    $dockle_data = 'dockle_fatal=' . intval($json["dockle"]["fatal"]) . 'i,'
                                 . 'dockle_warn=' . intval($json["dockle"]["warn"]) . 'i,'
                                 . 'dockle_info=' . intval($json["dockle"]["info"]) . 'i,'
                                 . 'dockle_pass=' . intval($json["dockle"]["pass"]) . 'i';
                    send_insert($project, "OS", "dockle", $dockle_data);
                }

            } else return false;

            return true;
        }
    }
    return false;
}

function send_insert($project, $type, $tool, $values) {
    global $influx_db;
    global $server_url;
    $web_page_to_send = $server_url."write?db=".$influx_db;

    $data = $tool . ',type=' . $type . ',project=' . $project . ' ' . $values;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $web_page_to_send);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "$data");
    $result = curl_exec($ch);
    if (curl_errno($ch)) echo "curl erno: ".curl_error($ch);
    //else echo "Curl ResponseCode: ".curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
}

function get_array_values($array) {
    $values = '';
    foreach ($array as $item) {
        if (isset($item["tool"])) {
            $scan = $item["tool"];
            unset($item["tool"]);
        } else if (isset($item["target"])) {
            $scan = $item["target"];
            unset($item["target"]);
        } else return $values;
        $scan = str_replace(" ", "", $scan);

        foreach ($item as $key => $value) {
            $values = $values . $scan . '_' . $key . '=' . intval($value) . 'i,';
        }
    }
    return $values;
}

?>