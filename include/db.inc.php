<?php
    class DBConnection
    {
        var $connection;
        var $error;

        function __construct()
        {
            $dbuser = "";
            $dbpass = "";
            $dbname = "";
            $server = "";

            $this->connection = new mysqli($server, $dbuser, $dbpass, $dbname);

            if($this->connection->connect_error)
                self::ShowDatabaseError();
        }

        function __destruct()
        {
            if($this->connection != null)
                $this->connection->close();
        }

        function GetSummonerState($summoner_id, $region)
        {
            $sql = "SELECT last_update_date FROM summoners WHERE summoner_id = '$summoner_id'";

            $result = $this->connection->query($sql);

            if(!$this->connection->error)
            {
                if($result->num_rows > 0)
                {
                    while($row = $result->fetch_assoc())
                    {
                        $last_update_time = strtotime($row["last_update_date"]);
                        $time_difference = time() - $last_update_time;

                        if($time_difference > 24 * 60 * 60)
                        {
                            return "needs_update";
                        }
                        else
                        {
                            return "updated";
                        }
                    }
                }
                else
                {
                    return "needs_insert";
                }
            }
            else
            {
                self::ShowDatabaseError();
            }
        }

        function ForceUpdate($summoner_id, $region)
        {
            $time = time() - 10 * 24 * 60 * 60;
            $date = date("Y-m-d", $time);

            $sql = "UPDATE summoners SET last_update_date = '$date' WHERE summoner_id = '$summoner_id' AND region = '$region'";

            if(!$this->connection->query($sql))
            {
                self::ShowDatabaseError();
            }
        }

        function GetMasteryBySummoner($summoner_id, $region)
        {
            $mastery_data = array();

            $sql = "SELECT * FROM champion_mastery_by_user WHERE summoner_id = '$summoner_id' AND region = '$region' ORDER BY mastery_points DESC";

            $result = $this->connection->query($sql);

            if(!$this->connection->error)
            {
                if($result->num_rows > 0)
                {
                    while($row = $result->fetch_assoc())
                    {
                        $mastery_data[$row["champion_id"]] = $row;
                    }
                }
            }
            else
            {
                self::ShowDatabaseError();
            }

            return $mastery_data;
        }

        function GetStatsMastery()
        {
            $sql = "SELECT * FROM average_champion_mastery_by_tier";

            $result = $this->connection->query($sql);

            $stats_data = array();

            if($this->connection->error)
            {
                self::ShowDatabaseError();
            }
            else
            {
                if($result->num_rows > 0)
                {
                    while($row = $result->fetch_assoc())
                    {
                        $stats_data[$row["region"]][$row["tier"]][$row["champion_id"]] = $row;
                    }
                }
            }

            return $stats_data;
        }

        function InsertSummoner($summoner_id, $region)
        {
            $date = date("Y-m-d");

            $sql = "INSERT INTO summoners (summoner_id, region, last_update_date) VALUES ('$summoner_id', '$region', '$date')";

            if(!$this->connection->query($sql))
            {
                self::ShowDatabaseError();
            }
        }

        function UpdateSummoner($summoner_id, $region)
        {
            $date = date("Y-m-d");

            $sql = "UPDATE summoners SET last_update_date = '$date' WHERE summoner_id = '$summoner_id' AND region = '$region'";

            if(!$this->connection->query($sql))
            {
                self::ShowDatabaseError();
            }
        }

        function UpdateMastery($summoner_id, $region, $mastery_data, $league_data)
        {
            $sql = "SELECT * FROM champion_mastery_by_user WHERE summoner_id = '$summoner_id' AND region = '$region'";

            $result = $this->connection->query($sql);

            $tier = $league_data[$summoner_id][0]["tier"];

            if(!$this->connection->error)
            {
                if($result->num_rows > 0)
                {
                    $needs_insert = array();
                    $needs_update = array();
                    $needs_update_points = array();

                    while($row = $result->fetch_assoc())
                    {
                        $needs_update[] = $row["champion_id"];
                        $needs_update_points[$row["champion_id"]] = $row["mastery_points"];
                    }

                    foreach ($mastery_data as $mastery_entry)
                    {
                        if(!in_array($mastery_entry["championId"], $needs_update))
                        {
                            $needs_insert[] = $mastery_entry["championId"];
                        }
                    }

                    if(count($needs_insert) > 0)
                    {
                        foreach ($mastery_data as $mastery_entry)
                        {
                            if(in_array($mastery_entry["championId"], $needs_insert))
                            {
                                $highest_grade = null;
                                $champion_id = $mastery_entry["championId"];
                                $mastery_level = $mastery_entry["championLevel"];
                                $mastery_points = $mastery_entry["championPoints"];

                                if(array_key_exists("highestGrade", $mastery_entry))
                                    $highest_grade = $mastery_entry["highestGrade"];

                                if($mastery_entry["chestGranted"] == "true")
                                    $chest_granted = 1;
                                else
                                    $chest_granted = 0;

                                $sql = "INSERT INTO champion_mastery_by_user (summoner_id, region, champion_id, mastery_level, mastery_points, highest_grade, chest_granted, tier) VALUES ('$summoner_id', '$region', '$champion_id', '$mastery_level', '$mastery_points', '$highest_grade', '$chest_granted', '$tier')";

                                if(!$this->connection->query($sql))
                                {
                                    self::ShowDatabaseError();
                                }
                            }
                        }
                    }

                    if(count($needs_update) > 0)
                    {
                        foreach ($mastery_data as $mastery_entry)
                        {
                            if(in_array($mastery_entry["championId"], $needs_update))
                            {
                                $highest_grade = null;
                                $champion_id = $mastery_entry["championId"];
                                $mastery_level = $mastery_entry["championLevel"];
                                $mastery_points = $mastery_entry["championPoints"];

                                if(array_key_exists("highestGrade", $mastery_entry))
                                    $highest_grade = $mastery_entry["highestGrade"];

                                if($mastery_entry["chestGranted"] == "true")
                                    $chest_granted = 1;
                                else
                                    $chest_granted = 0;

                                $sql = "UPDATE champion_mastery_by_user SET mastery_level = '$mastery_level', mastery_points = '$mastery_points', highest_grade = '$highest_grade', chest_granted = '$chest_granted', tier = '$tier' WHERE summoner_id = '$summoner_id' AND region = '$region' AND champion_id = '$champion_id'";

                                if(!$this->connection->query($sql))
                                {
                                    self::ShowDatabaseError();
                                }
                            }
                        }
                    }

                    if(!$this->error)
                    {
                        self::UpdateStatsMastery($region, $mastery_data, $tier, $needs_insert, $needs_update, $needs_update_points);
                    }
                }
                else
                {
                    if(count($mastery_data) > 0)
                    {
                        foreach ($mastery_data as $mastery_entry)
                        {
                            $highest_grade = null;
                            $champion_id = $mastery_entry["championId"];
                            $mastery_level = $mastery_entry["championLevel"];
                            $mastery_points = $mastery_entry["championPoints"];

                            if(array_key_exists("highestGrade", $mastery_entry))
                                $highest_grade = $mastery_entry["highestGrade"];

                            if($mastery_entry["chestGranted"] == "true")
                                $chest_granted = 1;
                            else
                                $chest_granted = 0;

                            $needs_insert[] = $champion_id;

                            $sql = "INSERT INTO champion_mastery_by_user (summoner_id, region, champion_id, mastery_level, mastery_points, highest_grade, chest_granted, tier) VALUES ('$summoner_id', '$region', '$champion_id', '$mastery_level', '$mastery_points', '$highest_grade', '$chest_granted', '$tier')";

                            if(!$this->connection->query($sql))
                            {
                                self::ShowDatabaseError();
                            }
                        }

                        self::UpdateStatsMastery($region, $mastery_data, $tier, $needs_insert, array(), array());
                    }
                }
            }
            else
            {
                self::ShowDatabaseError();
            }
        }

        function UpdateStatsMastery($region, $mastery_data, $tier, $needs_insert, $needs_update, $needs_update_points)
        {
            $sql = "SELECT * FROM average_champion_mastery_by_tier WHERE region = '$region' AND tier = '$tier'";

            $db_data = array();

            $result = $this->connection->query($sql);

            if($result->num_rows > 0)
            {
                while($row = $result->fetch_assoc())
                {
                    $db_data[$row["champion_id"]] = $row;
                }
            }

            $sql_insert = "INSERT INTO average_champion_mastery_by_tier (champion_id, region, tier, average_points, num_players) VALUES ";
            $sql_update = "ON DUPLICATE KEY UPDATE champion_id = VALUES(champion_id), region = VALUES(region), tier = VALUES(tier), average_points = VALUES(average_points), num_players = VALUES(num_players)";

            $first = true;

            foreach ($mastery_data as $mastery_entry)
            {
                $champion_id = $mastery_entry["championId"];
                $mastery_points = $mastery_entry["championPoints"];

                if(in_array($champion_id, $needs_insert))
                {
                    if(!$first)
                    {
                        $sql_insert .= ", ";
                    }

                    if(key_exists($champion_id, $db_data))
                    {
                        $num_players = $db_data[$champion_id]["num_players"] + 1;
                        $average_points = ($db_data[$champion_id]["average_points"] * ($num_players - 1) + $mastery_points) / $num_players;
                    }
                    else
                    {
                        $num_players = 1;
                        $average_points = $mastery_points;
                    }

                    $sql_insert .= "('$champion_id', '$region', '$tier', $average_points, $num_players)";

                    $first = false;
                }
                elseif(in_array($champion_id, $needs_update))
                {
                    if(!$first)
                    {
                        $sql_insert .= ", ";
                    }

                    $subtract_points = $needs_update_points[$champion_id];

                    if(key_exists($champion_id, $db_data))
                    {
                        $num_players = $db_data[$champion_id]["num_players"];
                        $average_points = ($db_data[$champion_id]["average_points"] * $num_players - $subtract_points + $mastery_points) / $num_players;
                    }
                    else
                    {
                        $num_players = 1;
                        $average_points = $mastery_points;
                    }

                    $sql_insert .= "('$champion_id', '$region', '$tier', $average_points, $num_players)";

                    $first = false;
                }
            }

            $sql = $sql_insert . $sql_update;

            if(!$this->connection->query($sql))
            {
                echo $this->connection->error;
                self::ShowDatabaseError();
            }
        }

        function ShowDatabaseError()
        {
            if(!$this->error)
            {
                echo "  <div class=\"warning\" id=\"warning\">
                            Database Connection Error
                            <button class=\"close-warning\" id=\"close-warning\"><i class=\"fa fa-close fa-lg\"></i></button>
                        </div>

                        <script>
                            $(\"#close-warning\").click(function() {
                                $(\"#warning\").hide();
                            });
                        </script>";
            }

            $this->error = true;
        }
    }
?>
