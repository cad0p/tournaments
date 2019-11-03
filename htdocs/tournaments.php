<!doctype html>
<html>



<head>
    <!--<meta charset="utf-8">-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Description" content="Manage Tournaments from all sports with matches between two teams/players">
    <meta name="author" content="Pier Carlo Cadoppi">

    <link rel="stylesheet" type="text/css" href="/css/style.css"/>

    <title>Tournaments</title>
    <!--<link href="/css/styles.min.css" rel="stylesheet" type="text/css">-->
</head>
<?php

$N_MAX_PLAYERS = 10;
$table = "Tournament";

require "functions.php";

function submitNewTournament($conn, $N_MAX_PLAYERS)
{
?>
    <form style="width:75%" action="tournaments.php" method="post">
		<fieldset>
			<legend>Enter your information in the form below:</legend>
			<p>
				<b>Create new tournament:</b>
                <br>
				<pre>
                Tournament Name: 
                <input type='text' name='name' size='20' maxlength='280'  <?php if(array_key_exists('name',$_REQUEST)) echo "value='".$_REQUEST['name']."'" ?> >
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
                            echo '<option value="'.$sport.'"';
                            if ($_REQUEST['sport'] == $sport) echo ' selected';
                            echo '>'.$sport.'</option>';
                        }
                    echo "</select>";
                    echo "<br>Or if your sport isn't in the database yet, you can insert it here!";
                }
?>

                <!--<a href="/sports.php?showSubmitNewToTable">Add New Sport</a>-->
                <button type="submit" formaction="sports.php" name="showSubmitNewToTable" value="fromTournament" >Add New Sport</button>

                <br>
                Number of Teams:
                <select name="numTeams">
<?php
                $numTeams = $_REQUEST['numTeams'];
                if ($numTeams == null) $numTeams = 4;
                for($i = 1; $i*2 <= $N_MAX_PLAYERS; $i++)
                { // only even numbers!
                    $n = $i * 2;
                    if ($n == $numTeams) echo '<option value="'.$n.'" selected>'.$n.'</option>';
                    else echo '<option value="'.$n.'">'.$n.'</option>';
                }
?>
                </select>

                </pre>
            </p>
        </fieldset>
        <div align='center'>
            <input class="largeButton" type="submit" name="submitNewToTable" value="Create New Tournament" />
        </div>
    </form>

                                

<?php
}


?>
<body>
    <div align="center">
        <h1><a href="tournaments.php" >TOURNAMENTS</a></h1>
    </div>
<?php
    // Implementing methods from functions.php or other special buttons
    
    if(array_key_exists('showSubmitNewToTable',$_REQUEST))
    {
        if (!$empty) submitNewTournament($conn, $N_MAX_PLAYERS);
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
        $_POST['showingDeletedFromTable'] = TRUE;
        $_REQUEST['showDeletedFromTable'] = null;
    }

    if(array_key_exists('clearDeletedFromTable',$_REQUEST))
    {

        clearDeletedFromTable($conn, $table, $conditions);
        $_REQUEST['clearDeletedFromTable'] = null;
    }

    if ($_POST['showingDeletedFromTable'] != TRUE)
    {
?>
    <form method="post">
        <!--<input type="submit" name="showDeletedFromTable" value="Show Deleted <?php echo $table ?>s" />-->
        <button class="largeButton" type="submit" name="showDeletedFromTable" >Show Deleted <?php echo $table ?>s</button>
    </form>
<?php
    }

    if ($empty)
    { // Creating Table
        
        submitNewTournament($conn, $N_MAX_PLAYERS);
        

    }
    // Clear Table left to review!!
?>
    <form method="post">
        <!--<input type="submit" name="clearTable" value="Clear Table" />-->
        <button class="largeButton" type="submit" name="clearTable" >Clear Table</button>
    </form>

    <form method="post">
        <!--<input type="submit" name="showSubmitNewToTable" value="Add New <?php echo $table ?>" />-->
        <button class="largeButton" type="submit" name="showSubmitNewToTable" >Add New <?php echo $table ?></button>
    </form>

    <form method="post">
        <button class="largeButton" type="submit" name="deleteFromTableByKey" >Delete <?php echo $table ?> by Name</button>
    </form>


<?php
    
    printTable($conn, $table, $conditions);

    $conn->close();
    
?>


</body>
</html>