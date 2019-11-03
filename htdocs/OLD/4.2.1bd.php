<html>

<head>
    <title>4.2.1bd</title>
</head>
<?php
$N_TEAMS = 10;
$N_MAX_POINTS = 100;

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


function clearTable($conn, $table)
{
    $sql = "TRUNCATE ".$table; //DROP TABLE
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    echo "<meta http-equiv='refresh' content='1'>";
}

function addTeams($conn, $N_TEAMS, $N_MAX_POINTS)
{
    // getting how many teams are in the database to start from there
    $sql = "SELECT COUNT(*) FROM Leaderboard";
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    $start = $result->fetch_row()[0];
    $start++;
    

    echo "<form method='post'>";
    echo "<fieldset>";
    echo "<pre>";
    echo "Team #\tTeam name\t\tPoints\n";
    for($team = $start; $team <= ($N_TEAMS); $team++)
    {
        echo "#$team\t";
        echo "<input type='text' name='teamsAdded[]' size='20' maxlength='280'>\t";
        echo '<select name="pointsAdded[]">';
        for ($points = 0; $points <= $N_MAX_POINTS; $points++)
            echo '<option value="'.$points.'">'.$points.'</option>';
        echo "</select>\n";
    }
    echo "<br>*Nameless teams with 0 points will be deleted!<br>";
    echo "</pre>";

    echo "</fieldset>";
    echo "<div align='center'>";
    echo '<input type="submit" name="submitTeams" value="Submit teams" />';
    echo "</div>";
    echo "</form>";
}

function deleteTeamByID($conn)
{
    $sql = "SELECT id FROM Leaderboard";
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    for ($ids = array (); $row = $result->fetch_row(); $ids[] = $row[0]);

    echo "<form method='post'>";
    echo "<fieldset>";
    echo "<pre>";
    echo "Team to remove: ";
    echo '<select name="idToRemove">';
    foreach($ids as $id)
    {
        echo '<option value="'.$id.'">'.$id.'</option>';
    }
    echo '</select>';
    
    echo "</pre>";

    echo "</fieldset>";
    echo "<div align='center'>";
    echo '<input type="submit" name="deleteTeam" value="Delete Team" />';
    echo "</div>";
    echo "</form>";
}

function deleteTeam($conn, $id)
{
    $sql = "DELETE FROM Leaderboard WHERE id = ".$id;
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
}

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

?>
<body>
<?php
    if ($empty)
    { ?>
	<form action="4.2.2bd.php" method="post">
		<fieldset>
			<legend>Enter your information in the form below:</legend>
			<p>
				<b>Last league table available?</b>
				<pre>
				<?php      
                    

                        $sql = "CREATE TABLE IF NOT EXISTS Leaderboard
                            (
                                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                teamName VARCHAR(30),
                                points INT(3) DEFAULT 0,
                                matchesPlayed INT(3) UNSIGNED DEFAULT 0
                            )";
                    
                        if ($conn->query($sql) != TRUE)
                        {
                            echo "Error creating table: " . $conn->error;
                        }
                        
                        // Now letting user input leaderboard 
                        echo "Team #\tTeam name\t\tPoints\n";
                        for($team = 1; $team <= $N_TEAMS; $team++)
                    	{
                    		echo "\t\t\t\t#$team\t";
                    		echo "<input type='text' name='teams[]' size='20' maxlength='280'>\t";
                    		echo '<select name="points[]">';
                    		for ($points = 0; $points <= $N_MAX_POINTS; $points++)
                    			echo '<option value="'.$points.'">'.$points.'</option>';
                    		echo "</select>\n";
                        }
                        echo "<br>*Nameless teams with 0 points will be deleted!<br>";
                        echo "<br>You can leave the Matchweek table blank if you just want to enter the teams in the database for now!";
    }
    else 
    {
        echo "Using table from database!";

        // Clear Table Method
        ?>
        <form method="post">
            <input type="submit" name="clearTable" value="Clear Leaderboard" />
        </form>

        <?php
        if(array_key_exists('clearTable',$_POST))
        {
            clearTable($conn, "Leaderboard");
            clearTable($conn, "Matchweek");
            $_POST['clearTable'] = null;
        }?>

        <form method="post">
            <input type="submit" name="addTeams" value="Add other teams" />
        </form>

        <?php
        if(array_key_exists('addTeams',$_POST))
        {
            addTeams($conn, $N_TEAMS, $N_MAX_POINTS);
            $_POST['addTeams'] = null;
        }
        
        if(array_key_exists('submitTeams',$_POST))
        {
            $teams = $_POST['teamsAdded'];
            $points = $_POST['pointsAdded'];

            $table = array_combine($teams, $points);
            clean_table($table);
            db_save_Leaderboard($table, $conn);
            $_POST['submitTeams'] = null;
        }
        ?>

        <form method="post">
            <input type="submit" name="deleteTeamByID" value="Delete Team by ID" />
        </form>

        <?php
        if(array_key_exists('deleteTeamByID',$_POST))
        {
            deleteTeamByID($conn);
            $_POST['deleteTeamByID'] = null;
        }
        
        if(array_key_exists('deleteTeam',$_POST))
        {
            $id = $_POST['idToRemove'];

            deleteTeam($conn, $id);
            $_POST['deleteTeam'] = null;
        }
        ?>


        <pre>
        <?php
        //$sql = "SELECT id, teamName, RANK() OVER(ORDER BY points DESC) Rank FROM Leaderboard ORDER BY points DESC";
        $sql = "SELECT * FROM Leaderboard ORDER BY points DESC";
        print_sql_db($conn, $sql);
        ?>
        </pre>

        <br><br>

        <form action="4.2.2bd.php" method="post">
		<fieldset>
			<legend>Enter your information in the form below:</legend>
			<p>
            
    <?php
    }
    
                
                echo "<input type='hidden' name='N_TEAMS' value='$N_TEAMS'>";
                echo "<input type='hidden' name='N_MAX_POINTS' value='$N_MAX_POINTS'>";
				?>
				
				<b>Now enter results from last matchweek!</b>
				<pre>
				*A team can only do one match in a Matchweek
				As an example, the first writable cell on the upper left would represent the score bewteen team #1 and #2
				<table style="width:100%" border="1">
				<?php
                    
                    $sql = "CREATE TABLE IF NOT EXISTS Matchweek
                        (
                            weekNumber INT(6) NOT NULL,
                            firstTeam INT(6) REFERENCES Leaderboard(id),
                            firstTeamScore INT(2) NOT NULL,
                            secondTeam INT(6) NOT NULL REFERENCES Leaderboard(id),
                            secondTeamScore INT(2) NOT NULL,
                            PRIMARY KEY(weekNumber, firstTeam),
                            UNIQUE(weekNumber, secondTeam)
                        )";
                    
                    if ($conn->query($sql) != TRUE)
                    {
                        echo "Error creating table: " . $conn->error;
                    }
                    
					for($row = 0; $row <= $N_TEAMS; $row++)
					{
						echo '<tr>';
						for($col = 0; $col <= $N_TEAMS; $col++)
						{
							if ($row == 0)
							{
								if ($col == 0) echo "<th>↓VS→</th>";
								else echo "<th>#$col</th>";
							}
							else if ($col == 0) echo "<th scope='row'>#$row</th>";
							else if ($col <= $row) echo "<td></td>";
							else
							{
								echo "<td>";
								echo "<div align='center'>";
								echo "<input type='number' style='width:30%' name='first_team[$row][$col]'>";
								echo " - ";
								echo "<input type='number' style='width:30%' name='second_team[$row][$col]'>";
								echo "</div>";
								echo "</td>";
							}
						}
						echo '</tr>';
					}
                    
                    $conn->close();
				?>
				</table>
				</pre>
			</p>
		</fieldset>
		<div align="center">
			<input type="submit" name="submit" value="Submit My Information" />
		</div>
	</form>
</body>

</html>
