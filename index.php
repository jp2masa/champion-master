<?php
    require("include/db.inc.php");
    require("include/images.inc.php");
    require("include/riot-api.inc.php");

    $api_key = "";

    if(isset($_GET["page"]))
        $page = $_GET["page"];
    else
        $page = "index";
?>

<html>
    <head>
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <link href="/css/normalize.css" rel="stylesheet" type="text/css" />
        <link href="/css/styles.css" rel="stylesheet" type="text/css" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=9" />
        <script src="/js/jquery-2.2.3.min.js" type="text/javascript"></script>
        <script src="/js/js.cookie-2.1.1.min.js" type="text/javascript"></script>
        <script src="/js/popup.js" type="text/javascript"></script>
        <title>Champion Master</title>
    </head>
    <body>
<?php
    include("layouts/top_bar.php");

    if(file_exists("pages/$page.php"))
        include("pages/$page.php");
    else
        include("pages/index.php");
?>
        <div class="footer-container">
            <p class="footer">Champion Master isn't endorsed by Riot Games and doesn't reflect the views or opinions of Riot Games or anyone officially involved in producing or managing League of Legends. League of Legends and Riot Games are trademarks or registered trademarks of Riot Games, Inc. League of Legends © Riot Games, Inc.</p>
        </div>

        <script>
            $(document).ready(function() {
                if(navigator.userAgent.toLowerCase().indexOf('firefox') > -1)
                {
                    $(document.body).css("height", "100%");
                }
            });
        </script>
    </body>
</html>
