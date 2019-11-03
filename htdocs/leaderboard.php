<!doctype html>
<html>
<?php
    $tournament = $_REQUEST['tournament'];
?>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Description" content="Manage Tournaments from all sports with matches between two teams/players">
    <meta name="author" content="Pier Carlo Cadoppi">

    <link rel="stylesheet" type="text/css" href="/css/style.css"/>

    <title>Leaderboard <?php echo $tournament; ?></title>
</head>

<?php

$table = "LeaderboardEntry";

require "functions.php";

if(array_key_exists('teamName',$_REQUEST))
{
    $teamName = $_REQUEST['teamName'];
    $tournaments = getFieldElementsFromTable($conn, $table, 'tournament', $conditions);
    if (count($tournaments == 1)) $tournament = $tournaments[0]; // if a team only participates in a tournament
}

function submitNewLeaderboardEntry($conn, $table, $tournament)
{
    if ($tournament != '')
    {
        $numTeamsShouldBe = getFieldElementsFromTable($conn, 'Tournament', 'numTeams', "name = '".$tournament."'")[0];
        $numTeamsNowIs = count(getFieldElementsFromTable($conn, $table, 'teamName', "tournament = '".$tournament."'"));
        $numTeamsToAdd = $numTeamsShouldBe - $numTeamsNowIs;
        // Only allow the user to add a new LeaderboardEntry if Tournament not full
        if ($numTeamsToAdd == 0 && $numTeamsShouldBe > 0)
        { // if is full and it exists
            echo "<b>The Tournament '".$tournament."' is full!</b>";
            return;
        }
    }

?>
    <form method="post">
        <fieldset>
            <legend>Enter your information in the form below:</legend>
            <p>

<?php
                if( $tournament != '') echo "<b>Create new ".$table." for Tournament '".$tournament."':</b>";
                else echo "<b>Create new ".$table." for Tournament:</b>";
?>
                <br>
				<pre>
<?php
                if ($tournament != '')
                {
                    echo "<input type='hidden' name='tournament' value='".$tournament."'>";
                }
                else
                {
                    $tournaments = getFieldElementsFromTable($conn, "Tournament", 'name');

                    foreach($tournaments as $name)
                        {
                            $numTeamsShouldBe = getFieldElementsFromTable($conn, 'Tournament', 'numTeams', "name = '".$name."'")[0];
                            $numTeamsNowIs = count(getFieldElementsFromTable($conn, $table, 'teamName', "tournament = '".$name."'"));
                            $numTeamsToAdd = $numTeamsShouldBe - $numTeamsNowIs;
                            // Only allow the user to select the tournament if not full!
                            if ($numTeamsToAdd > 0) $tournamentsNotFull[$name] = $numTeamsToAdd;
                        }

                    if (empty($tournamentsNotFull)) echo "<br>There are no Tournaments to add from the database. Insert your Tournament here!<br>";
                    else
                    {
?>                  
                        Select it from Tournaments already in the database:
                        <select name="tournament">
<?php
                            foreach($tournaments as $name)
                            {
                                $numTeamsShouldBe = getFieldElementsFromTable($conn, 'Tournament', 'numTeams', "name = '".$name."'")[0];
                                $numTeamsNowIs = count(getFieldElementsFromTable($conn, $table, 'teamName', "tournament = '".$name."'"));
                                $numTeamsToAdd = $numTeamsShouldBe - $numTeamsNowIs;
                                // Only allow the user to select the tournament if not full!
                                if ($numTeamsToAdd > 0) echo '<option value="'.$name.'">'.$name.'</option>';
                            }
                        echo "</select>";
                        echo "<br>Or if your Tournament isn't in the database yet, you can insert it here!<br>";
                    }
                    echo "\t\t<a href='/tournaments.php?showSubmitNewToTable'>Add New Tournament</a><br><br>";
                }
?>
                Team Name: 
<?php
                $teams = getFieldElementsFromTable($conn, "Team LEFT JOIN LeaderboardEntry ON name = teamName", 'name', "tournament != '".$tournament."' OR tournament IS NULL");
                // This one was tricky, but now it only lists teams that aren't yet participating to the Tournament
                //print_r($teams);
                if (empty($teams)) 
                {
                    echo "<br>There are no teams to add from the database. Insert your team here!";
                }
                else 
                {
?>                    
                    Select it from teams already in the database:
                    <select name="teamName">
<?php
                        foreach($teams as $team)
                        {
                            echo '<option value="'.$team.'"';
                            if ($_REQUEST['teamName'] == $team) echo ' selected';
                            echo '>'.$team.'</option>';
                        }
                    echo "</select>";
                    echo "<br>Or if your team isn't in the database yet, you can insert it here!";
                }
                
                
?>
                <a href="/teams.php?showSubmitNewToTable">Add New Team</a>

                <br>
                Points the Team has (if season already started - previous Matchweeks will not be recorded):
                <input type='number' name='points' value='0' >
                <br>
                Number of matches already played by this Team:
                <input type='number' name='matchesPlayed' min='0' value='0' >

                
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
    <h2><a href="<?php echo $URL_MAP['Tournament'] ?>">Go Back to Tournaments</a></h2>
<?php

    if(array_key_exists('showSubmitNewToTable',$_REQUEST))
    {
        if (!$empty) submitNewLeaderboardEntry($conn, $table, $tournament);
        $_REQUEST['showSubmitNewToTable'] = null;
    }

    if(array_key_exists('submitNewToTable',$_REQUEST))
    {
        saveNewToTable($conn, $table, $_REQUEST);
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
        submitNewLeaderboardEntry($conn, $table, $tournament);
    }

    if ($tournament != '')
    {
        $numTeamsShouldBe = getFieldElementsFromTable($conn, 'Tournament', 'numTeams', "name = '".$tournament."'")[0];
        $numTeamsNowIs = count(getFieldElementsFromTable($conn, $table, 'teamName', "tournament = '".$tournament."'"));
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
                    <!--<input type='hidden' name='matchWeek' value='1'>-->
                    <button class="largeButton" type="submit" name="showSubmitNewToTable" >Add Match</button>
                </form>
            </div>
<?php
        }
        else if($numTeamsShouldBe > 0) 
        {
            echo "<div align='center'><br>You still have to add <b>".$numTeamsToAdd." Team(s)</b> to the Tournament <b>".$tournament."</b></div><br><br>";
            // Go to this Tournament
            if($_GET['tournament'] == null) 
                echo '<meta http-equiv="refresh" content="0; url='.$URL_MAP[$table].'?'.http_build_query(array('tournament' => $tournament)).'" />';
        }
    }


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
                            'points' => 'DESC');
    printTable($conn, $table, $conditions, $defaultSorting);

    

    $conn->close();
?>
        

</body>
</html>