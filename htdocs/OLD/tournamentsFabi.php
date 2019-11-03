<html>

<head>
    <title>Tournaments</title>
    <meta name= "Autore" content= "Pier Carletto Cadoppi"/>
    <link rel="stylesheet" type="text/css" href="/css/style.css"/>
</head>
<?php

$N_MAX_PLAYERS = 10;
$table = "Tournament";

require "functions.php";

function submitNewTournament($conn, $N_MAX_PLAYERS)
{
?>
    <form method="post">
		<fieldset>
			<legend>Enter your information in the form below:</legend>
			<p>
				<b>Create new tournament:</b>
                <br>
				<pre>
                Tournament Name: 
                <input type='text' name='name' size='20' maxlength='280'>
                <br>
                Sport:
<?php
                if (isTableEmpty($conn, 'Sport')) echo "<br>There are no sports in the database yet. Insert your sport here!";
                else 
                {
                    //for ($sports = array (); $row = $result->fetch_row(); $sports[] = $row[0]);
                    $sports = getFieldElementsFromTable($conn, "Sport", 'name');
?>                    
                    Select it from sports already in the database:
                    <select name="sport">
<?php
                        foreach($sports as $sport)
                        {
                            echo '<option value="'.$sport.'">'.$sport.'</option>';
                        }
                    echo "</select>";
                    echo "<br>Or if your sport isn't in the database yet, you can insert it here!";
                }
?>
                <!--<input type='text' name='sportText' size='20' maxlength='280'>-->
                <a href="/sports.php?showSubmitNewToTable">Add New Sport</a>
                <br>
                Number of Teams:
                <select name="numTeams">
<?php
                for($i = 1; $i <= $N_MAX_PLAYERS; $i++) echo '<option value="'.$i.'">'.$i.'</option>';
?>
                </select>

                </pre>
            </p>
        </fieldset>
        <div align='center'>
            <input type="submit" name="submitNewToTable" value="Create New Tournament" />
        </div>
    </form>

                                

<?php
}


?>
<body>

<div align="center">
    <h1>TORNAMENTS</h1>
</div>

<?php
    // Implementing methods from functions.php or other special buttons
    if(array_key_exists('submitNewToTable',$_REQUEST))
    {
        saveNewToTable($conn, $table, $_REQUEST);
        $_REQUEST['submitNewToTable'] = null;
    }

    if(array_key_exists('clearTable',$_REQUEST))
    {
        clearTable($conn, "Tournament");
        clearTable($conn, "Team");
        clearTable($conn, "Matchweek");
        $_REQUEST['clearTable'] = null;
    }

    if(array_key_exists('showSubmitNewToTable',$_REQUEST))
    {
        submitNewTournament($conn, $N_MAX_PLAYERS);
        $_REQUEST['showSubmitNewToTable'] = null;
    }

    if(array_key_exists('deleteFromTableByName',$_REQUEST))
    {
        deleteFromTableByName($conn, $table);
        $_REQUEST['deleteFromTableByName'] = null;
    }

    if(array_key_exists('deleteFromTable',$_REQUEST))
    {
        $name = $_REQUEST['nameToRemove'];

        deleteFromTable($conn, $table, $name);
        $_REQUEST['deleteFromTable'] = null;
    }

    if(array_key_exists('restoreDeletedFromTableByName',$_REQUEST))
    {

        restoreDeletedFromTableByName($conn, $table);
        $_REQUEST['restoreDeletedFromTableByName'] = null;
    }

    if(array_key_exists('restoreDeletedFromTable',$_REQUEST))
    {
        $name = $_REQUEST['nameToRestore'];

        restoreDeletedFromTable($conn, $table, $name);
        $_REQUEST['restoreDeletedFromTable'] = null;
    }

    if(array_key_exists('showDeletedFromTable',$_REQUEST))
    {

        showDeletedFromTable($conn, $table);
        $_REQUEST['showDeletedFromTable'] = null;
    }

    if(array_key_exists('clearDeletedFromTable',$_REQUEST))
    {

        clearDeletedFromTable($conn, $table);
        $_REQUEST['clearDeletedFromTable'] = null;
    }

    if ($empty)
    { // Creating Table
        $sql = "CREATE TABLE IF NOT EXISTS ".$table."
            (
                deleted BOOLEAN DEFAULT FALSE,
                name VARCHAR(30) PRIMARY KEY,
                sport VARCHAR(30) NOT NULL,
                numTeams INT(2) NOT NULL
            )";
    
        if ($conn->query($sql) != TRUE)
        {
            echo "Error creating table: " . $conn->error;
        }
        submitNewTournament($conn, $N_MAX_PLAYERS);


    }
    else
    {
        // Clear Table left to review!!
?>
        <form method="post">
            <button class= "button" type="submit" name="clearTable" value="Clear Table"> Clear Table </button>
            <!--<input type="submit" name="clearTable" value="Clear Table" />-->
        </form>

        <form method="post">
            <button class= "button"type="submit" name="showSubmitNewToTable" >Add New <?php echo $table ?></button>
            <!--<input type="submit" name="showSubmitNewToTable" value="Add New <?php echo $table ?>" />-->
        </form>

        <form method="post">
            <button class= "button"> Delete </button>
            <!--<input type="submit" name="deleteFromTableByName" value="Delete <?php echo $table ?> by Name" />-->
        </form>

        <form method="post">
            <button class= "button"> Show Deleted </button>
            <!--<input type="submit" name="showDeletedFromTable" value="Show Deleted <?php echo $table ?>s" />-->
        </form>

        <b>Tournaments:</b>
<?php

        printTable($conn, $table);

        $tournaments = getFieldElementsFromTable($conn, $table, 'name');

        foreach($tournaments as $tournament)
        {
            echo '<form action="leaderboard.php" method="get">';
                echo '<input type="submit" name="tournamentname" value="'.$tournament.'" />';
            echo '</form>';
        }

        $sports = getFieldElementsFromTable($conn, $table, 'sport');

        foreach($sports as $sport)
        {
            echo '<form action="sports.php" method="get">';
                echo '<input type="submit" name="sportname" value="'.$sport.'" />';
            echo '</form>';
        }



    }
?>


</body>
</html>