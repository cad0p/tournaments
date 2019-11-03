<!doctype html>
<html>
<?php
    $tournament = $_REQUEST['tournament'];
    $matchWeek = $_REQUEST['matchWeek'];
?>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Description" content="Manage Tournaments from all sports with matches between two teams/players">
    <meta name="author" content="Pier Carlo Cadoppi">

    <link rel="stylesheet" type="text/css" href="/css/style.css"/>

    <title>Matches <?php echo $tournament; ?></title>
</head>

<?php

$table = "Game";

require "functions.php";

if(array_key_exists('team1Name',$_REQUEST))
{
    $team1Name = $_REQUEST['team1Name'];
    $tournaments = getFieldElementsFromTable($conn, $table, 'tournament', $conditions);
    if (count($tournaments == 1)) $tournament = $tournaments[0]; // if a team only participates in a tournament
}

function updateLeaderboard($conn, $request)
{
    $tournament = $request['tournament'];
    $sport = getFieldElementsFromTable($conn, 'Tournament', 'sport', "name = '".$tournament."'")[0];
    $matchWeek = $request['matchWeek'];
    $team1Name = $request['team1Name'];
    $team2Name = $request['team2Name'];
    $team1Score = $request['team1Score'];
    $team2Score = $request['team2Score'];
    
    $sql = "SELECT * FROM Sport WHERE name = '".$sport."'";
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    $sportAttributes = $result->fetch_assoc();
    
    if ($team1Score > $team2Score)
    {
        $team1 = $sportAttributes['pointsWinner'];
        $team2 = - $sportAttributes['pointsLoser'];
    }
    else if ($team1Score == $team2Score) $team1 = $team2 = $sportAttributes["pointsDraw"];
    else
    {
        $team1 =  - $sportAttributes['pointsLoser'];
        $team2 = $sportAttributes['pointsWinner'];
    }
    $sql = "UPDATE LeaderboardEntry SET points = points + ".$team1." WHERE 
            tournament = '".$tournament."' AND teamName = '".$team1Name."'";
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;

    $sql = "UPDATE LeaderboardEntry SET points = points + ".$team2." WHERE 
            tournament = '".$tournament."' AND teamName = '".$team2Name."'";
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;

    $sql = "UPDATE LeaderboardEntry SET matchesPlayed = matchesPlayed+1 WHERE 
            tournament = '".$tournament."' AND teamName IN ('".$team1Name."', '".$team2Name."')";
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
}

function submitNewGame($conn, $table, $tournament)
{
    global $URL_MAP;
    if ($tournament != '')
    {
        $numTeamsShouldBe = getFieldElementsFromTable($conn, 'Tournament', 'numTeams', "name = '".$tournament."'")[0];
        $numTeamsNowIs = count(getFieldElementsFromTable($conn, 'LeaderboardEntry', 'teamName', "tournament = '".$tournament."'"));
        $numTeamsToAdd = $numTeamsShouldBe - $numTeamsNowIs;
        // Only allow the user to add a new LeaderboardEntry if Tournament not full
        if ($numTeamsToAdd == 0 && $numTeamsShouldBe > 0);
        else
        { // if is full and it exists
            echo "<form action='".$URL_MAP['LeaderboardEntry']."' method='get' >";
                echo "<b>Tournament not full!";
                echo "<input type='hidden' name='tournament' value='".$tournament."'>";
                echo "<button type='submit' name='showSubmitNewToTable' >Go Back</button>";
                echo "and add ".$numTeamsToAdd." teams!</b>";
            echo "</form>";
            return;
        }
    }
    else
    {
        echo "<form action='".$URL_MAP['Tournament']."' method='get' >";
            echo "<b>Tournament not defined!<b>";
            echo "<button type='submit' >Select One!</button>";
        echo "</form>"; // don't ask me why I used a form here
        return;
    }

?>
    <form method="post">
        <fieldset>
            <legend>Enter your information in the form below:</legend>
            <p>

<?php
                if( $tournament != '') echo "<b>Add new ".$table." for Tournament '".$tournament."':</b>";
                else echo "<b>Add new ".$table." for Tournament:</b>";
?>
                <br>
				<pre>
<?php
                if ($tournament != '')
                {
                    echo "<input type='hidden' name='tournament' value='".$tournament."'>";
                }
                else // might be useless
                {
                    $tournaments = getFieldElementsFromTable($conn, "Tournament", 'name');

                    foreach($tournaments as $name)
                        {
                            $numTeamsShouldBe = getFieldElementsFromTable($conn, 'Tournament', 'numTeams', "name = '".$name."'")[0];
                            $numTeamsNowIs = count(getFieldElementsFromTable($conn, 'LeaderboardEntry', 'teamName', "tournament = '".$name."'"));
                            $numTeamsToAdd = $numTeamsShouldBe - $numTeamsNowIs;
                            // Only allow the user to select the tournament if full!
                            if ($numTeamsToAdd == 0) $tournamentsFull[$name] = $numTeamsToAdd;
                        }

                    if (empty($tournamentsFull)) echo "<br>There are no full Tournaments to add from the database. Go back and fill 'em here!<br>";
                    else
                    {
?>                  
                        Select it from Tournaments already in the database:
                        <select name="tournament">
<?php
                            foreach($tournaments as $name)
                            {
                                $numTeamsShouldBe = getFieldElementsFromTable($conn, 'Tournament', 'numTeams', "name = '".$name."'")[0];
                                $numTeamsNowIs = count(getFieldElementsFromTable($conn, 'LeaderboardEntry', 'teamName', "tournament = '".$name."'"));
                                $numTeamsToAdd = $numTeamsShouldBe - $numTeamsNowIs;
                                // Only allow the user to select the tournament if full!
                                if ($numTeamsToAdd == 0) echo '<option value="'.$name.'">'.$name.'</option>';
                            }
                        echo "</select>";
                        echo "<br>Or if your Tournament isn't here, go back to the Leaderboard and fill it with teams!<br>";
                    }
                    echo "\t\t<a href='/tournaments.php'>Fill Tournament with Teams</a><br><br>";
                }

                $matchWeek = $_REQUEST['matchWeek'];

                if ($matchWeek != '')
                {
                    echo "<br><b>MatchWeek ".$matchWeek."</b><br>";
                    echo "<input type='hidden' name='matchWeek' value='".$matchWeek."'>";
                }
                else if ($tournament != '')
                {
                    $matchWeeks = getFieldElementsFromTable($conn, "LeaderboardEntry", 'matchesPlayed');
                    $lastMatchWeek = 0;
                    foreach($matchWeeks as $matchWeek)
                        { // Number of teams that have yet to play
                            $numTeamsShouldBe = getFieldElementsFromTable($conn, 'Tournament', 'numTeams', "name = '".$tournament."'")[0];
                            $numTeamsNowIs = count(getFieldElementsFromTable($conn, "LeaderboardEntry", 'teamName', "tournament = '".$tournament."' 
                                AND matchesPlayed=".$matchWeek." "));
                            $numTeamsToAdd = $numTeamsShouldBe - $numTeamsNowIs;
                            // Only allow the user to select the matchWeek if not full!
                            if ($numTeamsToAdd > 0) $matchWeeksNotFull[$matchWeek] = $numTeamsToAdd;
                            if ($lastMatchWeek < $matchWeek) $lastMatchWeek = $matchWeek;
                        }

                    if (empty($matchWeeksNotFull)) 
                    {
                        $matchWeek = $lastMatchWeek + 1;
                        echo "<br>New MatchWeek!<br>";
                    }
                    else
                    {
                        /*
?>                  
                        Select it from MatchWeeks already in the database:
                        <select name="matchWeek">
<?php
                            foreach($matchWeeksNotFull as $matchWeek => $numTeamsToAdd)
                            {
                                // Only allow the user to select the matchWeek if not full!
                                if ($numTeamsToAdd > 0) echo '<option value="'.$matchWeek.'">'.$matchWeek.'</option>';
                            }
                        echo "</select>";*/

                        // Lets select all the teams that still have to play
                        $matchWeek = $lastMatchWeek;
                    }
                    echo "<br>MatchWeek ".$matchWeek."<br>";
                    echo "<input type='hidden' name='matchWeek' value='".$matchWeek."'>";
                }
?>
                Team 1 Name: 
<?php
                $team1Name = $_REQUEST['team1Name'];

                if ($team1Name != '')
                {
                    echo " '".$team1Name."'<br>";
                    echo "<input type='hidden' name='team1Name' value='".$team1Name."'>";
                }
                else
                {
                    $teams = getFieldElementsFromTable($conn, "LeaderboardEntry", 'teamName', "tournament = '".$tournament."' AND matchesPlayed < ".$matchWeek);

                    if (empty($teams)) 
                    { // should't happen though
                        echo "<br>There are no teams to add from the database. Insert your team here!";
                        echo'<a href="/teams.php?showSubmitNewToTable">Add New Team</a>';
                    }
                    else
                    {
                        $team1Name = $teams[0]; // the first team that has not played yet
                        unset($teams[0]); // this way you can't select this team
                        echo " '".$team1Name."'<br>";
                        echo "<input type='hidden' name='team1Name' value='".$team1Name."'>";
                    }
                }


?>


                Team 2 Name: 
<?php
                
                if (empty($teams)) 
                { // should't happen though
                    echo "<br>There are no teams to add from the database. Insert your team here!";
                    echo'<a href="/teams.php?showSubmitNewToTable">Add New Team</a>';
                }
                else 
                {
?>                    
                    Select it from teams already in the database:
                    <select name="team2Name">
<?php
                        foreach($teams as $team)
                        {
                            echo '<option value="'.$team.'"';
                            if ($_REQUEST['team2Name'] == $team) echo ' selected';
                            echo '>'.$team.'</option>';
                        }
                    echo "</select>";

                }                
                
?>
                

                <br>
                Score:
                <input type='number' name='team1Score' min='0' value='0' >
                -
                <input type='number' name='team2Score' min='0' value='0' >

                
                </pre>

                
            </p>
        </fieldset>
        <div align='center'>
            <input class="largeButton" type="submit" name="submitNewToTable" value="Create New <?php echo $table ?>" />
        </div>
    </form>

                                

<?php
}

?>
<body>
    <div align="center">
        <h1><a href="<?php echo $URL_MAP[$table] ?>" ><?php echo strtoupper($TABLE_PLURALS[$table]) ?></a></h1>
    </div>
<?php
    if ($tournament != '')
    {
?>
    <h2><a href="<?php echo $URL_MAP['LeaderboardEntry'].'?'.http_build_query(array('tournament' => $tournament)) ?>">Go Back to the Leaderboard</a></h2>
<?php
    } else
    {
?>
    <h2><a href="<?php echo $URL_MAP['LeaderboardEntry'] ?>">Go Back to the Leaderboard</a></h2>
<?php
    }
    if(array_key_exists('showSubmitNewToTable',$_REQUEST))
    {
        if (!$empty) submitNewGame($conn, $table, $tournament);
        $_REQUEST['showSubmitNewToTable'] = null;
    }

    if(array_key_exists('submitNewToTable',$_REQUEST))
    {
        if (saveNewToTable($conn, $table, $_REQUEST))
            updateLeaderboard($conn, $_REQUEST);
        $_REQUEST['submitNewToTable'] = null;
    }
    

    if(array_key_exists('deleteFromTableByKey',$_REQUEST))
    {
        deleteFromTableByKey($conn, $table, $conditions);
        $_REQUEST['deleteFromTableByKey'] = null;
    }

    if(array_key_exists('deleteFromTable',$_REQUEST))
    {
        deleteFromTable($conn, $table, $_REQUEST);
        $_REQUEST['deleteFromTable'] = null;
    }

    if(array_key_exists('restoreDeletedFromTableByKey',$_REQUEST))
    {

        restoreDeletedFromTableByKey($conn, $table, $conditions);
        $_REQUEST['restoreDeletedFromTableByKey'] = null;
    }

    if(array_key_exists('restoreDeletedFromTable',$_REQUEST))
    {
        restoreDeletedFromTable($conn, $table, $_REQUEST);
        $_REQUEST['restoreDeletedFromTable'] = null;
    }

    if(array_key_exists('showDeletedFromTable',$_REQUEST))
    {

        showDeletedFromTable($conn, $table, $conditions);
        $_REQUEST['showDeletedFromTable'] = null;
    }

    if(array_key_exists('clearDeletedFromTable',$_REQUEST))
    {

        clearDeletedFromTable($conn, $table, $conditions);
        $_REQUEST['clearDeletedFromTable'] = null;
    }
?>
    <form method="post">
        <input class="largeButton" type="submit" name="showDeletedFromTable" value="Show Deleted <?php echo $table ?>s" />
    </form>
<?php
    if ($empty)
    { // Submit New Element
        submitNewGame($conn, $table, $tournament);
    }

    /*if ($tournament != '')
    {
        $numTeamsShouldBe = getFieldElementsFromTable($conn, 'Tournament', 'numTeams', "name = '".$tournament."'")[0];
        $numTeamsNowIs = count(getFieldElementsFromTable($conn, 'LeaderboardEntry', 'teamName', "tournament = '".$tournament."'"));
        $numTeamsToAdd = $numTeamsShouldBe - $numTeamsNowIs;
        if ($numTeamsToAdd == 0 && $numTeamsShouldBe > 0)
        {
            echo "<div align='center'>";
                echo "<b>The Tournament '".$tournament."' is full!</b>";
?>
                <br>Start recording matches!
                <form action="matches.php" method="get">
<?php
                    echo "<input type='hidden' name='tournament' value='".$tournament."'>";
?>
                    <input type='hidden' name='matchWeek' value='1'>
                    <button type="submit" >First Match</button>
                </form>
            </div>
<?php
        }
        else if($numTeamsShouldBe > 0) echo "<div align='center'><br>You still have to add <b>".$numTeamsToAdd." Team(s)</b> to the Tournament <b>".$tournament."</b></div><br><br>";
    }*/


    // Clear Table left to review!!
?>
    <form method="post">
        <input class="largeButton" type="submit" name="clearTable" value="Clear Table" />
    </form>

    <form method="post">
        <input class="largeButton" type="submit" name="showSubmitNewToTable" value="Add New <?php echo $table ?>" />
    </form>

    <form method="post">
        <input class="largeButton" type="submit" name="deleteFromTableByKey" value="Delete <?php echo $table ?> by Key" />
    </form>

<?php
    $defaultSorting = array ('tournament' => 'ASC',
                            'matchWeek' => 'DESC');
    printTable($conn, $table, $conditions, $defaultSorting);

    

    $conn->close();
?>
        

</body>
</html>