<!doctype html>
<html>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Description" content="Manage Tournaments from all sports with matches between two teams/players">
    <meta name="author" content="Pier Carlo Cadoppi">

    <link rel="stylesheet" type="text/css" href="/css/style.css"/>

    <title>Teams</title>
</head>
<?php

$table = "Team";

require "functions.php";

function submitNewTeam($conn, $table)
{
?>
    <form method="post">
		<fieldset>
			<legend>Enter your information in the form below:</legend>
			<p>
				<b>Create new <?php echo $table ?>:</b>
                <br>
				<pre>
                <?php echo $table ?> Name: 
                <input type='text' name='name' size='20' maxlength='280'>

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
    <h2><a href="<?php echo $URL_MAP['LeaderboardEntry'] ?>">Go Back to the Leaderboard</a></h2>
<?php

    if(array_key_exists('showSubmitNewToTable',$_REQUEST))
    {
        if (!$empty) submitNewTeam($conn, $table);
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
            <input class="largeButton" type="submit" name="showDeletedFromTable" value="Show Deleted <?php echo $table ?>s" />
        </form>
<?php
    }

    if ($empty)
    { // Creating Table
        
        submitNewTeam($conn, $table);


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
    printTable($conn, $table, $conditions);

?>


</body>
</html>