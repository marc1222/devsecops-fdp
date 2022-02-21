<?php
   include 'cas_auth.php';
   include 'functions.php';
   error_reporting(0);


?>

<!DOCTYPE html>
<html lang="en">
<header>

    <title>Browse your Security Scan CI reports</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut_icon" type="image/jpg" href="assets/logo.jpg" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lexend+Mega"/>
    <link href="assets/bootstrap.min.css" rel="stylesheet"/> <!-- https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"> <!-- https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css  -->
    <link href="assets/mystyle.css" rel="stylesheet"/>
    <script src="assets/jquery.min.js"></script> <!--https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js -->
    <script src="assets/bootstrap.min.js"></script> <!-- https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js -->
</header>

<body style="background-color: #d5d5d5;">
    <nav class="navbar navbar-inverse navbar-fixed-top" style="margin-bottom:25px;">
      <div class="container-fluid">
        <div class="navbar-header" style="display:flex;">
          <img/>
          <a class="navbar-brand" href="/index.php" style="align-items: center; display: flex;">
            <img src="assets/logo.jpg" alt="Security Scan CI" style="width: 50px; margin-right:20px;"/>
            <b style="font-size:150%;">DevSecOps  -  Security  Scan  CI</b>
          </a>
        </div>
        <ul class="nav navbar-nav navbar-right">
            <li><a href="#" style="margin-right:30px"><i class="fa fa-user"></i> <?=$user?></a><a href="?logout" style="font-size: 80%;"><i class="fa fa-sign-out"></i> Logout</a></li>
        </ul>
      </div>
    </nav>

    <div id="content" class="container" style="padding-bottom: 100px;">

<?php
    //perform login -> if ok -> look for get params -> index.php (projects grid)
    //                                              -> index.php?project=project_name (project reports)
    //                                              -> index.php?report=project@report (report viewer)
    //              -> not ok -> redirect to sso login till compltete -> login error managed by APP
    if ($login == true) {
        if ( isset($_GET['report']) ) { //print report /> project@type_date.html --> mapping report project_type_date.html
            $report = filter_var($_GET['report'],FILTER_SANITIZE_STRING);
            $exploded = explode('@', $report);
            $project = $exploded[0];
            $exploded_report = explode('_', $exploded[1]);
            $info_content = get_file($upload_dir.$project.'/info.txt');
            $content = get_file($upload_dir.$project.'/'.$report);
            if ($content !== FALSE) {
                print_report($content, $project, $exploded_report[0].' security report', (explode('.',$exploded_report[1])[0]), $info_content);
            } else { ?> <!-- report not found -->
                <div><h1 class="text-center"> 404 - Report named  <?=$report?> not found</h1></div>
            <?php }
        } else if ( isset($_GET['project']) ) { //reports list
                  $project = filter_var($_GET['project'], FILTER_SANITIZE_STRING);
                  $reports = get_dir_content($project);
                  if ($reports !== FALSE) {
                      print_reports_list($reports, $project);
                  } else { ?> <!-- project not found -->
                      <div><h1 class="text-center"> 404 - Project named <?=$project?> not found</h1></div>
                  <?php }
              }
        else { //project grid
            $projects = get_dirs();
            print_projects_grid($projects);
        }
    } else  { ?> <!-- user not allowed -- login was OK but user does not has SSO unit attribute with value containing: YOURSITE/ITHINKUPC -->
        <div><h1 class="text-center"> 403 - Forbidden user</h1></div>
    <?php }
?>
    </div>
</body>
    <footer class="container-fluid text-center">Powered by
        <a href="https://www.your_site.com/" title="YOURsite" target="_blank" class="w3-hover-opacity">
            <img src="assets/your_sitelogo.png" alt="YOURsite" style="width: 120px; padding-bottom: 12px;"/>
        </a><i class="fa fa-copyright"></i>
        <br style="display:block; margin-bottom:-15px;"/>
        <small>Marc</small>
    </footer>

<script>
    $("#report_new_window").on("click", function () {
       var html = $("#show_report").attr('srcdoc');
       console.log(html);
       var x = window.open('', '_blank');
       x.document.open();
       x.document.write(html);
    });
</script>
</html>
