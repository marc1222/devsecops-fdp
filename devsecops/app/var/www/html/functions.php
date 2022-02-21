<?php

$upload_dir = "/var/www/Reports/";

# Function to get all directories sorted by mtime
function get_dirs() {
    global $upload_dir;
    if($h = opendir($upload_dir)) {
        $files = array();
        $num_files = 0;
        while(($file = readdir($h)) !== FALSE) {
            if ( $file != "." && $file != ".." && is_dir($upload_dir.$file) ) {
                $back_color = "#ffffff";
                $text_color = "#000000";
                $repo_url = "https://nourl.repo";
                $files[] = stat($upload_dir.$file);
                $files[$num_files]['project'] = $file;
                $info_file = file_get_contents($upload_dir.$file.'/info.txt');
                if ($info_file !== FALSE) {
                    $exploded_info = explode('|', $info_file);
                    $back_color    = $exploded_info[0];
                    $text_color    = $exploded_info[1];
                    $repo_url      = $exploded_info[2];
                    $order         = $exploded_info[3];
                }
                $files[$num_files]['bcolor'] = $back_color;
                $files[$num_files]['tcolor'] = $text_color;
                $files[$num_files]['repo']   = $repo_url;
                $files[$num_files]['order']   = intval($order);
                $num_files++;
            }
        }
        closedir($h);
         usort($files, function($a, $b) { // do the sort
             return $b['mtime'] -  $a['mtime'];
         });
//        usort($files, function($a, $b) { // do the sort
//            return $a['order'] - $b['order'];
//        });
        return $files;
    }
    return false;
}

# Function to get all files inside folder ordered by ctime
function get_dir_content($project) {
    global $upload_dir;
    if($h = opendir($upload_dir.$project)) {
        $files = array();
        $info  = array();
        $num_files = 0;
        while(($file = readdir($h)) !== FALSE) {
            if (is_file($upload_dir.$project.'/'.$file) ) {
                if ($file == 'info.txt') {
                    $info_file = file_get_contents($upload_dir.$project.'/'.$file);
                    if ($info_file !== FALSE) {
                        $exploded_info = explode('|', $info_file);
                        $info['report']  = $file;
                        $info['bcolor'] = $exploded_info[0];
                        $info['tcolor'] = $exploded_info[1];
                        $info['repo']   = $exploded_info[2];
                    }
                } else {
                        $files[$num_files]['date']   = (explode('.', (explode('_', (explode('@', $file)[1]) )[1]) )[0]);
                        $files[$num_files]['report'] = $file;
                }
                $num_files++;
            }
        }
        closedir($h);
        usort($files, function($a, $b) { // do the sort
            return strcmp($b['date'], $a['date']);
        });
        $files[count($files)] = $info;
        return $files;
    }
    return false;
}

# Function to get a file as String
function get_file($filename) {
    return file_get_contents($filename);
}

function print_projects_grid($projects) { ?>
    <div class="breadcrumb">
        <h5 style="margin-bottom:0px!important; font-size: 80%;">Browse projects <small> (Total items: <b><?=count($projects)?></b>)<small></h5>
    </div>
    <div class="row">
    <?php foreach ($projects as $project) { ?>
        <div class="col-sm-4 py-2">
            <div class="card" style="background-color:#<?=$project['bcolor']?>; color:#<?=$project['tcolor']?>;" onclick="location.href='index.php?project=<?=$project['project']?>';">
                    <div class="card-body" style="height:150px;">
                        <h3 class="card-title" style="margin-bottom:1.5rem; font-weight:bold;"><?=$project['project']?></h3>
                        <div style="position:absolute; bottom:0; margin-bottom:30px;">
                            <p class="card-text">Repo: <a href="<?=$project['repo']?>" target="_blank" class="w3-hover-opacity" style="color: inherit !important;"><?=$project['repo']?></a></p>
                        </div>
                    </div>
            </div>
        </div>
    <?php } ?>
    </div>
<?php }

function print_reports_list($reports, $project) {
    $info_file = $reports[count($reports)-1];
    unset($reports[count($reports)-1]); ?>
    <div class="card-body" style="background-color:#<?=$info_file['bcolor']?>; color:#<?=$info_file['tcolor']?>; border-radius:10px; margin-bottom: 25px;">
        <h3><?=$project?></h3>
        <small><p style="margin-bottom:0px;">Remote repository: <a href="<?=$info_file['repo']?>" target="_blank" style="color: inherit !important;"><?=$info_file['repo']?></a></p></small>
    </div>
    <div class="breadcrumb">
        <h5 style="margin-bottom:0px!important; font-size: 80%;">Browse reports <small> (Total items: <b><?=count($reports)?></b>)</small></h5>
    </div>
    <div id="list_init" class="list-group">
    <?php foreach ($reports as $report) { ?>
        <a href="index.php?report=<?=$report['report']?>" class="list-group-item list-group-item-action mylist"><?php pretty_report_name($report['report'])?></a>
    <?php
    } ?>
    </div>
<?php }

function pretty_date_print($date) {
    return $date['hour'].':'.$date['minute'].':'.$date['second'].' '.$date['day'].'-'.$date['month'].'-'.$date['year'];
}

function pretty_report_name($name) { //name => project_type_date.html
    $exploded = explode('@', $name);
    $project = $exploded[0];
    $exploded_report = explode('_', $exploded[1]);
    $type       = $exploded_report[0].' security report';
    $date       = date_parse_from_format("Y-m-d:H-i-s", (explode('.',$exploded_report[1])[0]) );
    echo '<div class="row text-center">
            <div class="col-6"><b>'.pretty_date_print($date).'</b></div>
            <div class="col-6"><b>'.$project.'</b> => '.$type.'</div>
          </div>';
}

function print_report($report_html, $project, $type, $date, $info_content) {
    $date = date_parse_from_format("Y-m-d:H-i-s", $date);
    $info_file = explode('|', $info_content);
    ?>
    <div class="card-body" style="background-color:#<?=$info_file[0]?>; color:#<?=$info_file[1]?>; border-radius:10px; margin-bottom: 25px;">
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-5">
                <h3><b><?=$project?></b></h3>
            </div>
             <div class="col-7">
                <h5><?= pretty_date_print($date)?>&emsp;&emsp;<small><?=$type?></small></h5>
             </div>
        </div>
        <p style="margin-bottom: 10px !important;">Remote repository: <a href="<?=$info_file[2]?>" target="_blank" style="color: inherit !important;"><?=$info_file[2]?></a></p>
        <a style="color:inherit !important; font-size: 60%;" href="index.php?project=<?=$project?>">
            <i class="fa fa-arrow-left"></i> Return to report list
        </a>
        <a id="report_new_window" class="btn btn-info" style="float: right; font-size: 85%; background-color:#<?=$info_file[1]?>33; color:#<?=$info_file[1]?>; border: 2px solid #<?=$info_file[1]?>;">OPEN REPORT NEW TAB</a>
    </div>
    <iframe id="show_report" style="width:inherit; height: 720px; margin-bottom: 25px; border-radius: 7px;" srcdoc='<?=str_replace("'", "\"",$report_html)?>'></iframe>
<?php }

?>