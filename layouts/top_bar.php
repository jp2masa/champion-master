<?php
    if(isset($_POST["summoner_name"]))
        $summoner_name = $_POST["summoner_name"];
    else
        $summoner_name = "";

    $riot_api = new RiotAPI($api_key);

    $regions = $riot_api->GetRegions();
?>

<div class="top-bar">
    <a class="brand" href="/">
        <img class="logo" src="<?php echo Images::GetImage("logo"); ?>"></img>
        <span href="/">Champion Master</span>
    </a>
    <a class="stats-link" href="/stats">Champion Mastery Average Stats</a>
    <div class="search">
        <form action="search" class="search-form" id="search-form" method="POST">
            <input class="search-input" id="search-input" name="summoner-name" placeholder="Summoner Name" value="<?php echo $summoner_name; ?>" type="search-box"></input>
            <button class="region-select" id="region-select" type="button">NA</button>
            <button class="search-button" type="submit"><i class="fa fa-search"></i></button>
            <input id="region" name="region" type="hidden" value="na"></input>
        </form>
    </div>
</div>

<div class="region-box" id="region-box">
    <div class="region-list" id="region-list">
        <button class="close-region-list" id="close-region-list" type="button"><i class="fa fa-close fa-lg"></i></button>
        <h3>Select your region</h3>
        <div>
            <ul>
<?php
    foreach ($regions as $region)
    {
?>
                <li><button class="region-button" data-region="<?php echo $region["slug"];?>" type="button"><?php echo $region["name"]; ?></button></li>
<?php
    }
?>
                <li><button class="region-button" data-region="kr" type="button">Korea</button></li>
            </ul>
        </div>
    </div>
</div>

<script>
    $(document).ready(function()
    {
        $("#region-select").popup("#region-box");

        if(Cookies.get("region"))
        {
            region = Cookies.get("region");

            $("#region").val(region);
            $("#region-select").html(region.toUpperCase());
        }

        $(document).click(function(e) {
            if (e.target.id != 'region-list' && e.target.id != 'region-select' && !$('#region-list').find(e.target).length || e.target.id == "close-region-list") {
                $("#region-box").css("display", "none");
            }
        });

        $(".region-button").each(function() {
            $(this).click(function() {
                $("#region").val($(this).data("region"));
                $("#region-select").html($(this).data("region").toUpperCase());
                $("#region-box").css("display", "none");
                Cookies.set('region', $(this).data("region"), { expires: 90 });
            });
        });

        $("#search-form").submit(function(e) {
            if($("#search-input").val() == "")
            {
                e.preventDefault();
                $("#search-input").css("background-color", "#EE2222");
            }
        });
    });
</script>
