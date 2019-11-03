<!doctype html>
<html>

<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Description" content="Manage Tournaments from all sports with matches between two teams/players">
    <meta name="author" content="Pier Carlo Cadoppi">

    <link rel="stylesheet" type="text/css" href="/css/style.css"/>

    <title>Sports</title>
</head>
<?php

$table = "Sport";

require "functions.php";

function submitNewSport($conn, $table, $preFillTournament)
{
?>
    <form action="sports.php" method="post">
		<fieldset>
			<legend>Enter your information in the form below:</legend>
			<p>
				<b>Create new <?php echo $table ?>:</b>
                <br>
				<pre>
                <?php echo $table ?> Name: 
                <input type='text' name='name' size='20' maxlength='280'>
                <br>
                You can enter any kind of Sport that has matches between two Teams!
                <br>
                Stay tuned for updates to support more sports, and please insert your sport only if it's supported!
                <br>
                Now I need some info about your Sport:
                <br>
                Points the Winner of a Match gains:
                <input type='number' name='pointsWinner' min='0' value='3' >
                <br>
                Points the Loser of a Match loses:
                <input type='number' name='pointsLoser' min='0' value='0' >
                <br>
                Points both Teams get when neither of them wins:
                <input type='number' name='pointsDraw' min='0' value='1' >
<?php
                if ($_POST['showSubmitNewToTable'] == 'fromTournament')
                    echo "<input type='hidden' name='preFillTournament' value='".base64_encode(serialize($preFillTournament))."' >";
?>
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

    if ($_POST['showSubmitNewToTable'] == 'fromTournament')
    { // receiving request from Tournament
        echo "<form action='tournaments.php' method='post' >";
        $tournamentFields = getFieldsFromTable($conn, 'Tournament');

        foreach ($tournamentFields as $field)
        {
            if(array_key_exists($field,$_POST)) echo "<input type='hidden' name='".$field."' value='".$_POST[$field]."' >";
            $preFillTournament[$field] = $_POST[$field]; //store values
        }

        echo "<h2><button type='submit' name='showSubmitNewToTable' >Go Back to Tournaments</button></h2>";
        echo "</form>";
    }
    else if (array_key_exists('preFillTournament',$_POST))
    { // user has submitted a new sport, time to go back and complete filling the tournament!
        echo "<form action='tournaments.php' method='post' >";
        $preFillTournament = $_POST['preFillTournament'];
        $preFillTournament = unserialize(base64_decode($preFillTournament));
        $preFillTournament['sport'] = $_POST['name']; // this way when you go back to Tournaments also the sport is prefilled!

        foreach ($preFillTournament as $field => $value)
        {
            echo "<input type='hidden' name='".$field."' value='".$value."' >";
        }

        echo "<h2><button type='submit' name='showSubmitNewToTable' >Go Back to Tournaments - with values prefilled!</button></h2>";
        echo "</form>";
    }
    else
    {
?>
        <h2><a href="<?php echo $URL_MAP['Tournament'] ?>">Go Back to Tournaments</a></h2>
<?php
    }


    if(array_key_exists('showSubmitNewToTable',$_REQUEST))
    {
        if (!$empty) submitNewSport($conn, $table, $preFillTournament); // Solving duplicate view problem when starting from scratch
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
    { // Creating Table
        

        submitNewSport($conn, $table, $preFillTournament);


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