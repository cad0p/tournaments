<!DOCTYPE html>
<html>
<body>
<a href="/4.2.1bd.php">Back to Main Page</a>
<br><br>

<?php

$servername = "sql201.epizy.com";
$username = "epiz_23468146";
$password = "hYkGoHbcNDW";
$dbname = "epiz_23468146_1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Test if Leaderbord is empty
$sql = "SELECT * FROM Leaderboard";
if ($conn->query($sql) == TRUE)
{
    $result = $conn->query($sql);
    $empty = ($result->fetch_row() == NULL);
}
else $empty = TRUE;

function print_sql_db($conn, $sql)
{ // This Function prints whatever query you throw at it in a nice table format!
	echo '<table style="width:30%" border="1">';
    echo '<tr>';
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    while ($field = $result->fetch_field()) echo '<th>'.$field->name.'</th>';
    echo '</tr>';
    while ($row = $result->fetch_row())
    {
        echo '<tr>';
        foreach($row as $cellValue) echo '<td>'.$cellValue.'</td>';
        echo '</tr>';
    }
	echo "</table>";
}

function db_save_Leaderboard($table_array, $conn)
{
    foreach ($table_array as $team => $points)
    {
        if (!($team == '' && $points == 0))
        {
            $sql = "INSERT INTO Leaderboard (teamName, points)
                VALUES ('".$team."', '".$points."')";
            
            if ($conn->query($sql) != TRUE) echo $conn->error;
        }
    }
}

function db_save_Matchweek($conn, $first_team, $second_team, $N_TEAMS, $week=1)
{
    for($row = 1; $row < $N_TEAMS; $row++)
    {
        for($col = $row + 1; $col <= $N_TEAMS; $col++)
        {
            if ($first_team[$row][$col] != NULL && $second_team[$row][$col] != NULL)
            {
                $sql = "INSERT INTO Matchweek (weekNumber, 
                                                firstTeam, firstTeamScore, 
                                                secondTeam, secondTeamScore)
                        VALUES ('".$week."', '".$row."', '".$first_team[$row][$col]."', '".$col."', '".$second_team[$row][$col]."')";
                
                if ($conn->query($sql) != TRUE) echo $conn->error;
            }
            else
            if ($first_team[$row][$col] == NULL xor $second_team[$row][$col] == NULL)
                trigger_error("You entered an incomplete match score in 
                    row $row col $col, please go back and try again", E_USER_ERROR);
        }
    }
}

function clean_table(&$table_array)
{
	foreach ($table_array as $team => $points)
	{
		if ($team == '' && $points == 0) 
		{
			unset($table_array[$team]);
		}
	}
}

function print_table($table_array)
{
	echo '<table style="width:30%" border="1">';
	echo '<tr><th>#</th><th>Team</th><th>Points</th></tr>';

	$i = 1;
	foreach ($table_array as $team => $points)
	{
		echo "<tr>";
		echo "<th scope='row'>#$i</th>";
		echo "<td>$team</td><td>$points</td>";
		echo "</tr>";
		$i++;
	}

	echo "</table>";
}


$N_TEAMS = $_POST['N_TEAMS'];
$N_MAX_POINTS = $_POST['N_MAX_POINTS'];


echo "<b>Previous Table Available was:</b>";
echo "<pre>";


$first_team = $_POST['first_team'];
$second_team = $_POST['second_team'];




if ($empty)
{
    $teams = $_POST['teams'];
    $points = $_POST['points'];
    foreach($teams as $i => $team)
    {
        $matchesPlayed[] = 0;
    }
}
else
{
    $sql = "SELECT teamName FROM Leaderboard";
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    for ($teams = array (); $row = $result->fetch_row(); $teams[] = $row[0]);
    
    $sql = "SELECT points FROM Leaderboard";
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    for ($points = array (); $row = $result->fetch_row(); $points[] = $row[0]);

    $sql = "SELECT matchesPlayed FROM Leaderboard";
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    for ($matchesPlayed = array (); $row = $result->fetch_row(); $matchesPlayed[] = $row[0]);
}

$table = array_combine($teams, $points);
if ($empty)
{
    // Now saving results to db
    db_save_Leaderboard($table, $conn);
    //db_save_Matchweek($conn, $first_team, $second_team, $N_TEAMS);
}

clean_table($table);
arsort($table);
print_table($table);

db_save_Matchweek($conn, $first_team, $second_team, $N_TEAMS);

echo "</pre>";


echo "<b>This Matchweek results:</b>";
echo "<pre>";
echo '<table style="width:30%" border="1">';
echo '<tr><th>Team 1</th><th>Team 2</th><th>Score</th></tr>';
// This is how many times a team has played in the last matchweek. it should be 1!
$team_play_count = array();

for($row = 1; $row < $N_TEAMS; $row++)
{
	for($col = $row + 1; $col <= $N_TEAMS; $col++)
	{
		if ($first_team[$row][$col] != NULL && $second_team[$row][$col] != NULL)
		{ // if is valid
			$team_play_count[$row]++; // now registering that first_team has played
			$team_play_count[$col]++; // now registering that second_team has played
			echo "<tr>";
            
			echo "<td>".$teams[$row-1]."</td><td>".$teams[$col-1]."</td><td>".
				$first_team[$row][$col]." - ".$second_team[$row][$col]."</td>";
			echo "</tr>";

			if ($first_team[$row][$col] > $second_team[$row][$col])
				$table[$teams[$row-1]] += 3; // first won

			else 
			if ($first_team[$row][$col] == $second_team[$row][$col])
			{
				$table[$teams[$row-1]] += 1;
				$table[$teams[$col-1]] += 1;
			}
			else $table[$teams[$col-1]] += 3; // second won
		}
		else
		if ($first_team[$row][$col] == NULL xor $second_team[$row][$col] == NULL)
			trigger_error("You entered an incomplete match score in 
				row $row col $col, please go back and try again", E_USER_ERROR);
	}
}
echo "</table>";
echo "</pre>";
// now checking if every team did only one match
foreach ($teams as $i => $team)
{
	$i++; // the array teams starts at 0, meanwhile rows start from 1
	if ($team != '')
	{ // i+1 because 
		if ($team_play_count[$i] != 1)
		{
			if ($team_play_count[$i] == '') $team_play_count[$i] = 0;
			trigger_error("You made team $team either not play or play more than once in a single Matchweek ".
			 "($team_play_count[$i] times), please go back and try again", E_USER_ERROR);
		}
	}
	else
	{
		if ($team_play_count[$i] > 0)
			trigger_error("You made nameless team #$i play ($team_play_count[$i] times),". 
				"please go back and retry if done by error. It could cause some inconsistencies");
	}
}

echo "<b>And the updated Table is...</b>";
echo "<pre>";
arsort($table);
print_table($table);
echo "</pre>";

?>
</body>
</html>