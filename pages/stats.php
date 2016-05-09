<?php
    if(isset($_COOKIE["region"]))
        $default_region = $_COOKIE["region"];
    else
        $default_region = "na";

    $riot_api = new RiotAPI($api_key);

    $regions = $riot_api->GetRegions();

    $stats_data = $riot_api->db_connection->GetStatsMastery();

    $champion_data = json_decode(file_get_contents("data/6.9.1/champion.json"), true);
?>

<div class="container stats-container">
    <h1>Champion Mastery Average Stats</h1>
    <div class="group-btn">
<?php
    foreach ($regions as $region)
    {
        echo "<button class=\"stats-region" . (($region["slug"] == $default_region) ? " active" : "") . "\" data-region = \"" . $region["slug"] . "\"  id=\"stats-region\">" . strtoupper($region["slug"]) . "</button>";
    }
?>
        <button class="stats-region<?php if($default_region == "kr") echo "active"; ?>" data-region="kr"  id="stats-region">KR</button>
    </div>
    <div class="group-btn">
        <button class="stats-tier active" data-tier="unranked">Unranked</button>
        <button class="stats-tier" data-tier="bronze">Bronze</button>
        <button class="stats-tier" data-tier="silver">Silver</button>
        <button class="stats-tier" data-tier="gold">Gold</button>
        <button class="stats-tier" data-tier="platinum">Platinum</button>
        <button class="stats-tier" data-tier="diamond">Diamond</button>
        <button class="stats-tier" data-tier="master">Master</button>
        <button class="stats-tier" data-tier="challenger">Challenger</button>
    </div>
    <div class="stats-table-box">
        <p class="no-stats-available" id="no-stats-available">There are no stats for the selected ranked tier and regions. Try to chose other ranked tier or region.</p>
<?php
        foreach ($stats_data as $region => $by_tier)
        {
            foreach ($by_tier as $tier => $by_champion)
            {
?>
                <table id="<?php echo "$region-" . strtolower($tier); ?>">
                    <thead>
                        <tr>
                            <th colspan="2">Champion</th>
                            <th>Average Mastery Points</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
                foreach ($by_champion as $champion_id => $champion_entry)
                {
                    $champion_key = $champion_data["data"][$champion_id]["key"];
                    $champion_image = "http://ddragon.leagueoflegends.com/cdn/" . $riot_api->version . "/img/champion/$champion_key.png";
                    $champion_name = $champion_data["data"][$champion_id]["name"];
                    $mastery_points = $champion_entry["average_points"]
?>
                        <tr>
                            <td><img src="<?php echo $champion_image; ?>"></img></td>
                            <td><?php echo $champion_name; ?></td>
                            <td><?php echo $mastery_points; ?></td>
                        </tr>
<?php
                }
?>
                    </tbody>
                </table>
<?php
            }
        }
?>
    </div>
</div>

<script>
    $(document).ready(function() {
        var region = "<?php echo $default_region; ?>";
        var tier = "unranked";

        $("[data-tier='unranked']").click();

        $(".stats-region").each(function() {
            $(this).click(function() {
                $("#no-stats-available").css("display", "none");
                var data_region = $(this).data("region");
                $("#" + region + "-" + tier).css("display", "none");
                $("[data-region='" + region + "']").removeClass("active");
                region = data_region;
                $(this).addClass("active");
                if($("#" + region + "-" + tier).length > 0)
                    $("#" + region + "-" + tier).css("display", "table");
                else
                    $("#no-stats-available").css("display", "block");
            });
        });

        $(".stats-tier").each(function() {
            $(this).click(function() {
                $("#no-stats-available").css("display", "none");
                var data_tier = $(this).data("tier");
                $("#" + region + "-" + tier).css("display", "none");
                $("[data-tier='" + tier + "']").removeClass("active");
                tier = data_tier;
                $(this).addClass("active");
                if($("#" + region + "-" + tier).length > 0)
                    $("#" + region + "-" + tier).css("display", "table");
                else
                    $("#no-stats-available").css("display", "block");
            });
        });
    });
</script>
