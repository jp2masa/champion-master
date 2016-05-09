<?php
    if(empty($_POST["summoner-name"]) || empty($_POST["region"]))
        header("Location: /");

    $summoner_name = $_POST["summoner-name"];
    $region = $_POST["region"];

    $riot_api = new RiotAPI($api_key);

    $summoner_data = $riot_api->GetSummonerData($summoner_name, $region);

    if($summoner_data)
    {
        $summoner_id = $summoner_data["id"];

        if(isset($_POST["update"]))
        {
            $riot_api->db_connection->ForceUpdate($summoner_id, $region);
            $summoner_data = $riot_api->GetSummonerData($summoner_name, $region);
        }

        $mastery_data = $riot_api->db_connection->GetMasteryBySummoner($summoner_id, $region);

        if($mastery_data)
        {
            $champion_data = json_decode(file_get_contents("data/6.9.1/champion.json"), true);

            function sort_mastery($a, $b)
            {
                return $b["mastery_points"] - $a["mastery_points"];
            }

            usort($mastery_data, "sort_mastery");
        }
    }

    if($summoner_data)
    {
?>
<div class="container-full">
    <div class="profile-banner">
        <div class="profile">
            <img class="summoner-icon" src="<?php echo Images::GetImageByURL("http://ddragon.leagueoflegends.com/cdn/" . $riot_api->version . "/img/profileicon/" . $summoner_data["profileIconId"] . ".png") ?>"></img>
            <span>
                <p><?php echo $summoner_data["name"]; ?></p>
                <form action="" method="POST">
                    <button class="update-button" name="update" type="submit">Update Data</button>
                    <input name="summoner-name" type="hidden" value="<?php echo $summoner_name; ?>"></input>
                    <input name="region" type="hidden" value="<?php echo $region; ?>"></input>
                </form>
            </span>
        </div>
        <div class="top-mastery-champions">
            <h2>Top Mastery Champions</h2>
<?php
        if(count($mastery_data) > 0)
        {
            $i = 1;

            foreach ($mastery_data as $mastery_entry)
            {
                $champion_id = $mastery_entry["champion_id"];
                $champion_key = $champion_data["data"][$champion_id]["key"];
                $champion_name = $champion_data["data"][$champion_id]["name"];
                $mastery_level = $mastery_entry["mastery_level"];
                $mastery_points = $mastery_entry["mastery_points"];
                $mastery_tier_image = Images::GetImage("mastery_tier/mastery_tier_$mastery_level");

                echo "  <div class=\"mastery-champion-$i" . (($mastery_level >= 5) ? " mastery-max" : "") . "\">
                            <img class=\"champion-image\" src=\"http://ddragon.leagueoflegends.com/cdn/" . $riot_api->version . "/img/champion/$champion_key.png\"></img>
                            <h3>$champion_name</h3>
                            <p>$mastery_points</p>
                            <img src=\"$mastery_tier_image\"></img>
                        </div>";

                if($i == 3)
                    break;

                $i++;

            }
        }
        else
        {
            echo "<center><p>You haven't any champion with mastery points. Try updating.</p></center>";
        }
?>
        </div>
    </div>

    <h1>Played Champions</h1>
<?php
        if(count($mastery_data) > 0)
        {
            echo "  <table class=\"mastery-table\">
                        <thead>
                            <tr>
                                <th colspan=\"2\">Champion</th>
                                <th>Mastery Points</th>
                                <th>Mastery Level</th>
                                <th>Highest Grade</th>
                                <th>Role Title</th>
                                <th>Chest Granted</th>
                            </tr>
                        </thead>
                        <tbody>";
            foreach ($mastery_data as $mastery_entry)
            {
                $champion_id = $mastery_entry["champion_id"];
                $champion_key = $champion_data["data"][$champion_id]["key"];
                $champion_name = $champion_data["data"][$champion_id]["name"];
                $champion_image = "http://ddragon.leagueoflegends.com/cdn/" . $riot_api->version . "/img/champion/$champion_key.png";
                $champion_role = $champion_data["data"][$champion_id]["tags"][0];
                $chest_granted = !!$mastery_entry["chest_granted"] ? "Yes" : "No";
                $highest_grade = !empty($mastery_entry["highest_grade"]) ? $mastery_entry["highest_grade"] : "None";
                $mastery_level = $mastery_entry["mastery_level"];
                $mastery_points = $mastery_entry["mastery_points"];
                $mastery_title = $riot_api->mastery_titles[$champion_role][$mastery_level - 1];

                    echo "  <tr>
                                <td><img class=\"champion-icon\" src=\"$champion_image\"></img></td>
                                <td>$champion_name</td>
                                <td>$mastery_points</td>
                                <td>$mastery_level</td>
                                <td>$highest_grade</td>
                                <td>$mastery_title</td>
                                <td>$chest_granted</td>
                            </tr>";
            }

            echo "      </tbody>
                    </table>";
        }
        else
        {
            echo "You haven't any champion with mastery points. Try updating.";
        }
    }
    else
    {
        echo "<center><p>This summoner doesn't exist for the selected region. Try other region.</p></center>";
    }
?>
</div>
