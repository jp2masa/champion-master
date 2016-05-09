<?php
    class RiotAPI
    {
        var $api_key = "";
        var $mastery_titles = array('Assassin' => array("Thug", "Prowler", "Cutthroat", "Reaper", "Slayer"), 'Fighter' => array("Scrapper", "Brawler", "Warrior", "Veteran", "Destroyer"), "Mage" => array("Initiate", "Conjurer", "Invoker", "Magus", "Warlock"), 'Marksman' => array("Tracker", "Strider", "Scout", "Ranger", "Pathfinder"), 'Support' => array("Aide", "Protector", "Keeper", "Defender", "Guardian"), 'Tank' => array("Grunt", "Bruiser", "Bulwark", "Enforcer", "Brute"));
        var $platform_ids = array('BR' => "BR1", 'EUNE' => "EUN1", 'EUW' => "EUW1", 'JP' => "JP1", 'KR' => "KR1", 'LAN' => "LA1", 'LAS' => "LA2", 'NA' => "NA1", 'OCE' => "OC1", 'TR' => "TR1", 'RU' => "RU", 'PBE' => "PBE1");

        function RiotAPI($api_key)
        {
            $this->db_connection = new DBConnection();

            $this->api_key = $api_key;

            $api_key = $this->api_key;
            $url = "https://ddragon.leagueoflegends.com/api/versions.json";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result_json = curl_exec($ch);
            $result = json_decode($result_json, true);

            $version_json = file_get_contents("data/version.json");
            $version_array = json_decode($version_json, true);
            $this->version = $version_array["version"];

            if(!curl_error($ch))
            {
                if($this->version != $result[0])
                {
                    $version_array["version"] = $result[0];
                    $version_file = fopen("data/version.json", "w");
                    fwrite($version_file, json_encode($version_array, JSON_PRETTY_PRINT));

                    $this->version = $result[0];

                    mkdir("data/" . $result[0], 755);

                    $url = "https://global.api.pvp.net/api/lol/static-data/euw/v1.2/champion?dataById=true&champData=tags&api_key=$api_key";
                    $file = fopen("data/" . $result[0] . "/champion.json", "w");

                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                    $result = curl_exec($ch);

                    fwrite($file, $result);

                    curl_close($ch);
                    fclose($file);
                }
            }
        }

        function GetRegions()
        {
            $api_key = $this->api_key;
            $url = "http://status.leagueoflegends.com/shards";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result_json = curl_exec($ch);
            $result = json_decode($result_json, true);

            curl_close($ch);

            return $result;
        }

        function GetSummonerData($summoner_name, $region)
        {
            $api_key = $this->api_key;
            $url = "https://$region.api.pvp.net/api/lol/$region/v1.4/summoner/by-name/$summoner_name?api_key=$api_key";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result_json = curl_exec($ch);
            $result = json_decode($result_json, true);

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if($http_code == 200)
            {
                $summoner_id = current($result)["id"];
                $summoner_state = $this->db_connection->GetSummonerState($summoner_id, $region);

                if($summoner_state != "updated")
                {
                    $mastery_data = self::GetMasteryBySummoner($summoner_id, $region);
                    $league_data = self::GetSummonerLeagueData($summoner_id, $region);

                    if($summoner_state == "needs_update")
                    {
                        $this->db_connection->UpdateSummoner($summoner_id, $region);
                        $this->db_connection->UpdateMastery($summoner_id, $region, $mastery_data, $league_data);
                    }
                    elseif($summoner_state == "needs_insert")
                    {
                        $this->db_connection->InsertSummoner($summoner_id, $region);
                        $this->db_connection->UpdateMastery($summoner_id, $region, $mastery_data, $league_data);
                    }
                }

                return current($result);
            }
            else
            {
                return false;
            }
        }

        function GetSummonerLeagueData($summoner_id, $region)
        {
            $api_key = $this->api_key;

            $url = "https://$region.api.pvp.net/api/lol/$region/v2.5/league/by-summoner/$summoner_id/entry?api_key=$api_key";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result_json = curl_exec($ch);
            $result = json_decode($result_json, true);

            curl_close($ch);

            if(!key_exists($summoner_id, $result))
            {
                $result = array();
                $result[$summoner_id][0]["tier"] = "UNRANKED";
            }

            return $result;
        }

        function GetChampionData($champion_id, $region)
        {
            $api_key = $this->api_key;

            $url = "https://global.api.pvp.net/api/lol/static-data/$region/v1.2/champion/$champion_id?api_key=$api_key";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result_json = curl_exec($ch);
            $result = json_decode($result_json, true);

            curl_close($ch);

            return $result;
        }

        function GetMasteryByChampion($summoner_id, $server_name, $champion_id)
        {
            $api_key = $this->api_key;
            $platform_name = $this->platform_ids[$server_name];

            $url = "https://$server_name.api.pvp.net/championmastery/location/$platform_name/player/$summoner_id/champion/$champion_id?api_key=$api_key";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result_json = curl_exec($ch);
            $result = json_decode($result_json, true);

            curl_close($ch);

            return $result[$summoner_name]["id"];
        }

        function GetMasteryBySummoner($summoner_id, $region)
        {
            $api_key = $this->api_key;
            $platform = strtolower($this->platform_ids[strtoupper($region)]);

            $url = "https://$region.api.pvp.net/championmastery/location/$platform/player/$summoner_id/champions?api_key=$api_key";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result_json = curl_exec($ch);
            $result = json_decode($result_json, true);

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if($http_code == 200)
            {
                return $result;
            }
            else
            {
                return false;
            }
        }
    }
?>
