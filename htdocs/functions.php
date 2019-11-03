<?php
$URL_MAP = array 
                (   'Tournament'        => '/tournaments.php'   ,
                    'LeaderboardEntry'  => '/leaderboard.php'   ,
                    'Sport'             => '/sports.php'         ,
                    'Team'              => '/teams.php'         ,
                    'Game'             => '/matches.php'
                );

$TABLE_PLURALS =  array
                (   'Tournament'        => 'Tournaments'        ,
                    'LeaderboardEntry'  => 'Leaderboard'        ,
                    'Sport'             => 'Sports'             ,
                    'Team'              => 'Teams'              ,
                    'Game'              => 'Matches'       
                );

$REFERENCES_MAP =  array
                (   'tournamentName'    => array
                                            (
                                                'Tournament'        => 'name'       ,
                                                'LeaderboardEntry'  => 'tournament' ,
                                                'Game'              => 'tournament'
                                            ),
                    'sportName'         => array
                                            (
                                                'Tournament'        => 'sport'      ,
                                                'Sport'             => 'name'
                                            ),
                    'teamName'          => array
                                            (
                                                'LeaderboardEntry'  => 'teamName'   ,
                                                'Team'              => 'name'       ,
                                                'Game'              => 'team1Name' // also team2Name to be implemented
                                            )  
                );


$servername = "sql201.epizy.com";
$username = "{{USERNAME}}";
$password = "{{PASSWORD}}";
$dbname = "epiz_{{USERNAME}}_1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$createTables = "
            CREATE TABLE IF NOT EXISTS Tournament
            (
                deleted BOOLEAN DEFAULT FALSE,
                name VARCHAR(30) PRIMARY KEY,
                sport VARCHAR(30) NOT NULL,
                numTeams INT(2) NOT NULL
            );
            
            CREATE TABLE IF NOT EXISTS Team
            (
                deleted BOOLEAN DEFAULT FALSE,
                name VARCHAR(30) PRIMARY KEY
            );
            
            CREATE TABLE IF NOT EXISTS Sport
            (
                deleted BOOLEAN DEFAULT FALSE,
                name VARCHAR(30) PRIMARY KEY,
                pointsWinner INT(6) NOT NULL,
                pointsLoser INT(6) NOT NULL,
                pointsDraw INT(6) NOT NULL
            );
            
            CREATE TABLE IF NOT EXISTS LeaderboardEntry
            (
                deleted BOOLEAN DEFAULT FALSE,
                tournament VARCHAR(30)
                    references Tournament(name) ON DELETE CASCADE,
                teamName VARCHAR(30)
                    references Team(name) ON DELETE CASCADE,
                points INT(6) NOT NULL,
                matchesPlayed INT(3) UNSIGNED DEFAULT 0,
                PRIMARY KEY (tournament, teamName)
            );

            CREATE TABLE IF NOT EXISTS Game
            (
                deleted BOOLEAN DEFAULT FALSE,
                tournament VARCHAR(30),
                matchWeek INT(3),
                team1Name VARCHAR(30),
                team2Name VARCHAR(30) NOT NULL,
                team1Score INT(6) NOT NULL,
                team2Score INT(6) NOT NULL,
                PRIMARY KEY (tournament, team1Name, matchWeek),
                UNIQUE (tournament, team2Name, matchWeek)
            )
            
            ";


if ($conn->multi_query($createTables)) {
    do {
        /* store first result set */
        if ($result = $conn->store_result() == TRUE) 
            $result->free();
        else echo $conn->error;
    } while ($conn->more_results() && $conn->next_result());
}

$conditions = tableConditions($conn, $table, $_GET); // only fetch GET, not POST for search result purposes

// Test if Table is empty
$empty = isTableEmpty($conn, $table, $conditions);

/*if(array_key_exists('searchTable',$_REQUEST))
{
    $conditions = tableConditions($conn, $table, $_REQUEST); //IN THEORY A DUPLICATE

    $_REQUEST['searchTable'] = null;
}*/

if(array_key_exists('clearTable',$_REQUEST))
{
    clearTable($conn, "Tournament");
    clearTable($conn, "Team");
    clearTable($conn, "Sport");
    clearTable($conn, "LeaderboardEntry");
    clearTable($conn, "Game");

     // Go Back Home | debug mode
    //echo '<meta http-equiv="refresh" content="0; url='.$URL_MAP['Tournament'].'" />';
    $_REQUEST['clearTable'] = null;
}

if(array_key_exists('sendTableByEmail',$_REQUEST))
{

    $_REQUEST['sendTableByEmail'] = null;
}

/*function searchInTableIsGoingOn($conn, $table)
{
    $fields = getFieldsFromTable($conn, $table);
    
    foreach($fields as $field)
    {
        if ($_GET[$field] != null) return TRUE;
    }
    return FALSE; // no search in page :/
}*/

function translateFieldsToReferencedTable($conn, $tableFrom, $tableTo)
{
    $translatedFields = array();

    $fields = getFieldsFromTable($conn, $tableFrom);
    foreach($fields as $field)
    {
        $fieldReferences = getFieldReferencesFromOtherTables($tableFrom, $field);
        if ($fieldReferences[$tableTo] != null) $translatedFields[$field] = $fieldReferences[$tableTo];
    }

    return $translatedFields;
}

function translatePrimaryKeysToReferencedTable($conn, $tableFrom, $tableTo)
{
    $translatedPrimaryKeys = array();

    $primaryKeys = getPrimaryKeysFromTable($conn, $tableFrom);
    foreach($primaryKeys as $key)
    {
        $fieldReferences = getFieldReferencesFromOtherTables($tableFrom, $key);
        if ($fieldReferences[$tableTo] != null) $translatedPrimaryKeys[] = $fieldReferences[$tableTo];
    }

    return $translatedPrimaryKeys;
}

function getReferenciesCommonFieldName($table, $field)
{ // returns the common field name
    global $REFERENCES_MAP;
    foreach($REFERENCES_MAP as $commonFieldName => $tablesWithReferences)
    {
        foreach($tablesWithReferences as $tableWithReferences => $fieldReferenced)
        {
            if ($tableWithReferences == $table && $fieldReferenced == $field)
            { // if reference exists
                return $commonFieldName;
            }
        }
    }
    return null; // if not found
}

function getFieldReferencesFromOtherTables($table, $field)
{ // returns array with tables as index and the field(s) referenced inside.
    global $REFERENCES_MAP;
    foreach($REFERENCES_MAP as $commonFieldName => $tablesWithReferences)
    {
        foreach($tablesWithReferences as $tableWithReferences => $fieldReferenced)
        {
            if ($tableWithReferences == $table && $fieldReferenced == $field)
            { // if reference exists
                $result = $tablesWithReferences;
                unset($result[$table]); // only return the other tables, not ours
                return $result;
            }
        }
    }
    return array(); // if not found
}




function tableConditions($conn, $table, $request)
{
    if(array_key_exists('submitNewToTable',$request))
    {
        return '';
    }

    //$conditions = $request['conditions'];
    //if ($conditions != '') $conditions.= " AND ";
    $conditions = '';

    foreach(getFieldsFromTable($conn, $table) as $field)
    {
        if(array_key_exists($field,$request))
        {
            $conditions .= $field." = '".$request[$field]."' AND ";
        }
    }
    if ($conditions != '' && $request['conditions'] != '')
    {
        $conditions.= $request['conditions'];
    }
    else if ($conditions == '' && $request['conditions'] != '')
        $conditions = $request['conditions'];
    else if ($conditions != '' && $request['conditions'] == '')
        $conditions = rtrim($conditions, ' AND ');
    if ($conditions != '' && ($orderPos = strpos($conditions, " ORDER BY ")) == 0 && substr($conditions, 0, 1) != '(')
    { // if there's no order by and parenthesys are not already added
        return "(".$conditions.")";
    }
    else if ($conditions != '' && ($orderPos = strpos($conditions, " ORDER BY ")) != 0 && substr($conditions, 0, 1) != '(')
    {
        return "(".substr($conditions, 0, $orderPos).")".substr($conditions, $orderPos);
    }
    else return $conditions;
}

function isTableEmpty($conn, $table, $conditions='')
{
    $sqlTable = "SELECT * FROM ".$table." WHERE deleted IS FALSE";
    if ($conditions != '')
    {
        $sqlTable .= " AND ".$conditions;
        if (isSqlQueryEmpty($conn, $sqlTable)) 
        {
            echo "No ".$table." Results (that are not deleted) for ".$conditions.". <a href='".htmlentities($_SERVER['PHP_SELF'])."'>Clear Search</a><br><br>"; 
            //die(); NOT NECESSARY ANYMORE
        }
    }
    return isSqlQueryEmpty($conn, $sqlTable);
}

function isSqlQueryEmpty($conn, $sql)
{
    if ($conn->query($sql) == TRUE)
    {
        $result = $conn->query($sql);
        $empty = ($result->num_rows == 0);
    }
    else $empty = TRUE;

    return $empty;
}

function clearDeletedFromTable($conn, $table, $conditions='')
{
    $sql = "DELETE FROM ".$table." WHERE deleted IS TRUE";
    if ($conditions != '') $sql .= " AND ".$conditions;

    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
}

function showDeletedFromTable($conn, $table, $conditions='')
{
    global $TABLE_PLURALS;
    echo "<h2>Deleted ".$TABLE_PLURALS[$table].":</h2>";

    $tableFieldsCSV = createStringWithTableFieldsCSV($conn, $table);

    $sqlTable = "SELECT ".$tableFieldsCSV." FROM ".$table." WHERE deleted IS TRUE";

    if ($conditions != '') 
    {
        $sqlTable .= " AND ".$conditions;
        echo " (Showing filtered view) ";
    }

    
    printSqlQuery($conn, $sqlTable);

?>
    <form method="post">
        <input type="submit" name="clearDeletedFromTable" value="Clear Deleted <?php echo $table ?>s" />
    </form>

    <form method="post">
        <input type="submit" name="restoreDeletedFromTableByKey" value="Restore Deleted <?php echo $table ?>s by Key" />
    </form>

<?php
}

function restoreDeletedFromTableByKey($conn, $table, $conditions='')
{
    $primaryKeys = getPrimaryKeysFromTable($conn, $table);

    echo "<form method='post'>";
    echo "<fieldset>";
    echo "<pre>";
    echo $table." to restore: ";

    foreach ($primaryKeys as $key)
    {
        $fieldElements = getDeletedFieldElementsFromTable($conn, $table, $key, $conditions);

        
        echo $key.': <select name="'.$key.'ToRestore">';
        foreach($fieldElements as $fieldElement)
        {
            echo '<option value="'.$fieldElement.'">'.$fieldElement.'</option>';
        }
        echo '</select> ';
        
        
    }
    
    echo "</pre>";

    echo "</fieldset>";
    echo "<div align='center'>";
    echo '<input type="submit" name="restoreDeletedFromTable" value="Restore '.$table.'" />';
    echo "</div>";
    echo "</form>";
}

function restoreReferencesToRestoredFromTable($conn, $table, $sqlPrev, $restoredReferences=array())
{
    global $TABLE_PLURALS;

    $sqlPrev = "SELECT * FROM ".$table." ".substr($sqlPrev, strpos($sqlPrev, "WHERE"));

    
    foreach($TABLE_PLURALS as $tableReferenced => $plural)
    {
        if ($table != $tableReferenced)
        {
            $translatedFields = translateFieldsToReferencedTable($conn, $table, $tableReferenced);

            $sql = "UPDATE ".$tableReferenced." SET deleted = FALSE WHERE deleted IS TRUE AND (";
            $isToRestore = FALSE;
            foreach($translatedFields as $field => $translatedField)
            {
                $commonFieldName = getReferenciesCommonFieldName($table, $field);
                $sqlSelect = "SELECT ".$field." FROM ".$table." ".substr($sqlPrev, strpos($sqlPrev, "WHERE"));
                if (($resultFields = $conn->query($sqlSelect)) != TRUE) echo $conn->error;
                for ($fieldElements = array (); $row = $resultFields->fetch_assoc(); $fieldElements[] = $row[$field])
                {
                    if (!array_key_exists($commonFieldName, $restoredReferences) || !in_array($row[$field], $restoredReferences[$commonFieldName]))
                        $isToRestore = TRUE;
                }
                if ($isToRestore)
                {
                    $restoredReferences[$commonFieldName] = $fieldElements;
                    $sql .= $translatedField." IN ( SELECT ".$field." FROM ".$table." ".substr($sqlPrev, strpos($sqlPrev, "WHERE")).") OR ";
                    
                }
            }
            $sql = rtrim($sql, ' OR ')." )";

            

            // Now restore references to this table!
            if ($isToRestore)
            {
                $sqlSelect = "SELECT * FROM ".$tableReferenced." ".substr($sql, strpos($sql, "WHERE"));
                //echo $table."<br>".$sqlSelect."<br>".isSqlQueryEmpty($conn, $sqlSelect)."<br>";
                //printSqlQuery($conn, $sqlSelect);
                echo "<br>Restoring references in ".$plural."<br>";

                restoreReferencesToRestoredFromTable($conn, $tableReferenced, $sql, $restoredReferences);
                if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
                
            }
        }

    }

}

function restoreDeletedFromTable($conn, $table, $request)
{

    $sql = "UPDATE ".$table." SET deleted = FALSE WHERE deleted IS TRUE AND "; // name = '".$name."'";

    foreach(getPrimaryKeysFromTable($conn, $table) as $key)
    {
        $sql .= $key." = '".$request[$key."ToRestore"]."' AND ";
    }
    $sql = rtrim($sql, ' AND ');

    

    // Now that we have restored the main result, let's restore the references results
    // WOW THIS TOOK A FEW DAYS BY ITSELF
    restoreReferencesToRestoredFromTable($conn, $table, $sql);
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;

    //echo "<meta http-equiv='refresh' content='1'>"; // NOT required anymore to update the view!

}

function deleteFromTableByKey($conn, $table, $conditions='')
{
    $primaryKeys = getPrimaryKeysFromTable($conn, $table);

    echo "<form method='post'>";
    echo "<fieldset>";
    echo "<pre>";
    echo $table." to remove: ";

    foreach ($primaryKeys as $key)
    {
        $fieldElements = getFieldElementsFromTable($conn, $table, $key, $conditions);

        
        echo $key.': <select name="'.$key.'ToRemove">';
        foreach($fieldElements as $fieldElement)
        {
            echo '<option value="'.$fieldElement.'">'.$fieldElement.'</option>';
        }
        echo '</select> ';
        
        
    }

    echo "</pre>";
    echo "</fieldset>";
    echo "<div align='center'>";
    echo '<input type="submit" name="deleteFromTable" value="Delete '.$table.'" />';
    echo "</div>";
    echo "</form>";
}


function deleteFromTable($conn, $table, $request)
{
    global $TABLE_PLURALS;

    $sql = "UPDATE ".$table." SET deleted = TRUE WHERE "; // name = '".$name."'";

    foreach($primaryKeys = getPrimaryKeysFromTable($conn, $table) as $key)
    {
        $sql .= $key." = '".$request[$key."ToRemove"]."' AND ";

    }
    $sql = rtrim($sql, ' AND ');

    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    
    // Now that we have deleted the main result, let's delete the references results

    $fieldReferencesToDeleteOnCascade = array();

    foreach($TABLE_PLURALS as $tableToCheck => $plural)
    {
        $translatedPrimaryKeys = translatePrimaryKeysToReferencedTable($conn, $table, $tableToCheck);
        if ( count($translatedPrimaryKeys) == count($primaryKeys) )
        { // if all the primary keys of the table we are deleting from are in a referenced table,
         // then we have to delete it also from the other table (i believe so at least!)
            $tableReferenced = $tableToCheck;
            echo "<br>Deleting also references in ".$tableReferenced."<br>";
            $sqlTableReferenced = "UPDATE ".$tableReferenced." SET deleted = TRUE WHERE "; // name = '".$name."'";
            $allPrimaryKeys = array_combine($primaryKeys, $translatedPrimaryKeys);
            foreach($allPrimaryKeys as $key => $translatedKey)
            {
                $sqlTableReferenced .= $translatedKey." = '".$request[$key."ToRemove"]."' AND ";
            }
            $sqlTableReferenced = rtrim($sqlTableReferenced, ' AND ');

            if (($result = $conn->query($sqlTableReferenced)) != TRUE) echo $conn->error;
        }
    }


}

function clearTable($conn, $table)
{
    $sql = "TRUNCATE TABLE ".$table; // live mode | deletes the content of the table
    //$sql = "DROP TABLE ".$table;  // debug mode | deletes the table itself!!
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    //echo "<meta http-equiv='refresh' content='1'>"; // in debug mode, to restart
}

function getPrimaryKeysFromTable($conn, $table)
{
    $sql = "SHOW KEYS FROM ".$table." WHERE Key_name = 'PRIMARY'"; // Primary Keys
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;

    for ($primaryKeys = array (); $row = $result->fetch_row(); $primaryKeys[] = $row[4]); // Column_name

    return $primaryKeys;
}

function getDeletedFieldElementsFromTable($conn, $table, $field, $conditions='')
{
    $placeToCut = strpos($table, " ");
    if ($placeToCut != null) $firstTable = substr($table, 0, $placeToCut);
    else $firstTable = $table;
    // This way we solve the "deleted field is ambiguous" when joining tables
    $sql = "SELECT DISTINCT ".$field." FROM ".$table." WHERE ".$firstTable.".deleted IS TRUE";
    if ($conditions != '') $sql .= " AND ".$conditions;

    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    for ($fieldElements = array (); $row = $result->fetch_row(); $fieldElements[] = $row[0]);

    return $fieldElements;
}

function getFieldElementsFromTable($conn, $table, $field, $conditions='')
{
    $placeToCut = strpos($table, " ");
    if ($placeToCut != null) $firstTable = substr($table, 0, $placeToCut);
    else $firstTable = $table;
    // This way we solve the "deleted field is ambiguous" when joining tables
    $sql = "SELECT DISTINCT ".$field." FROM ".$table." WHERE ".$firstTable.".deleted IS FALSE";
    if ($conditions != '') $sql .= " AND ".$conditions;


    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    for ($fieldElements = array (); $row = $result->fetch_row(); $fieldElements[] = $row[0]);

    return $fieldElements;
}

function getFieldsFromTable($conn, $table)
{
    $sql = "SELECT * FROM ".$table;
    if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
    //for ($fields = array (); $field = $result->fetch_field(); $fields[] = $field->name);
    $fields = array();
    while ($field = $result->fetch_field()->name)
    {
        if ($field != 'deleted')
        {
            $fields[] = $field;
        }
    }

    return $fields;
}

function createStringWithTableFieldsCSV($conn, $table)
{ //CSV Stands for Comma Separated Values, returns also array with fields
    $fields = getFieldsFromTable($conn, $table);

    $tableFieldsCSV = "";

    foreach ($fields as $field)
    {
        $tableFieldsCSV .= $field.", ";
    }
    $tableFieldsCSV = rtrim($tableFieldsCSV,', ');
    return $tableFieldsCSV;
}

function printTable($conn, $table, $conditions='', $sorting='')
{ // This function generates the SQL Query needed to print the Table, without the "deleted" column
 // Optional conditions run after the "..WHERE deleted IS FALSE AND " clause, keep this in mind.
    global $TABLE_PLURALS;

    $tableFieldsCSV = createStringWithTableFieldsCSV($conn, $table);

    $sqlTable = "SELECT ".$tableFieldsCSV." FROM ".$table." WHERE deleted IS FALSE";

    echo "<br><h2>".$TABLE_PLURALS[$table]." Table:</h2>";

?>
    <!--<br>
    <form method="get">
        <input type='email' name='emailAddress' maxlength='140'>
        <input type="submit" name="sendTableByEmail" value="Send me this table" />
    </form>
    <br>-->
<?php

    echo "<div align='center' >";
        if ($conditions != '') 
        {
            $sqlTable .= " AND ".$conditions;
            echo "Showing Search Results for ".$conditions.":<br>";
            echo "<a href='".htmlentities($_SERVER['PHP_SELF'])."'>Clear Search</a>"; // using just PHP_SELF is dangerous as it allows XSS injection
        }
        //if ($sorting != '') $sqlTable .= " ".$sorting;
?>
        <br>
        <form method="get">
            Example Query: "field1 = 'Name 1' AND field2 = 2"
            <input type='text' name='conditions' style='width:20%' maxlength='280'>
            <input type="submit" name="searchTable" value="Search Table" />
        </form>
        <br>
<?php
        //echo "<br>".$sqlTable."<br>"; //decomment if you want to see the query being printed!
        printSqlQuery($conn, $sqlTable, $table, $conditions, $sorting);
    echo "</div>";

    //$sql = "SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '".$table."'";

    //printSqlQuery($conn, $sql);

}

function printSqlQuery($conn, $sql, $table='', $conditions='', $sorting='')
{ // This Function prints whatever query you throw at it in a nice table format!
    if ($table != '')
    {
        global $TABLE_PLURALS, $URL_MAP;
        $tablePlural = $TABLE_PLURALS[$table];
        $isTable = TRUE;
        $primaryKeys = getPrimaryKeysFromTable($conn, $table);
        

    }
    else $isTable = FALSE;

    if ($_GET['sorting'] != null) 
    {
        $sorting = unserialize(base64_decode($_GET['sorting']));
    }

    if(strpos($sql, " ORDER BY ") != 0) $sortingString = substr($sql, strpos($sql, "ORDER BY ")+strlen("ORDER BY "));
    else
    {
        $sortingString = '';
        if (is_array($sorting))
        {
            $isSorted = FALSE;
            $sql .= " ORDER BY ";
            foreach($sorting as $fieldSorted => $sortValue)
            {
                if ($sortValue != null) {$sql .= $fieldSorted." ".$sortValue.", "; $isSorted = TRUE;}
            }
            if ($isSorted) $sql = rtrim($sql, ", ");
            else $sql = substr($sql, 0, strpos($sql, " ORDER BY ")); //rtrim didn't work here for some reason
        }
        echo $sql;
    }    

	echo '<table align="center" style="width:75%" border="1">';
        echo '<tr>';
            if (($result = $conn->query($sql)) != TRUE) echo $conn->error;
            while ($field = $result->fetch_field()->name)
            {
                echo '<th>';
                    if ($sortingString == '')
                    {
                        echo '<small style="text-align:right"><form method="get">';
                            echo "Sort:";
                            if ($sorting == '')$sorting[$field] = null;
                            $sortingField = $sorting[$field];
                            if ($sorting[$field] == null) {$sorting[$field] = 'ASC'; $sortingIcon = "-";}
                            else if ($sorting[$field] == 'ASC') {$sorting[$field] = 'DESC'; $sortingIcon = "↓";}
                            else if ($sorting[$field] == 'DESC') {$sorting[$field] = null; $sortingIcon = "↑";}
                            if ($conditions != '') echo '<input type="hidden" name="conditions" value="'.$conditions.'" >';
                            echo "<button type='submit' name='sorting' value='".base64_encode(serialize($sorting))."' >".$sortingIcon."</button>";
                            $sorting[$field] = $sortingField; // bring back to previous state if not clicked
                            
                        echo '</form></small>';
                    }
                    echo $field;

                    if ($isTable && $conditions != '' && $_GET[$field] == null && count($fieldReferences = getFieldReferencesFromOtherTables($table, $field)) > 0)
                    { // if a search is going on, but not on this field, you can do an advanced search at the click of a button.
                     // just as an example, you can see which sports have tournaments in which a team has a certain number of points or other cool stuff!
                        if (strpos($sql, " ORDER BY") != 0) $sqlThisFieldOnly = "SELECT ".$field." ".substr($sql, strpos($sql, "FROM"), strpos($sql, " ORDER BY") - strpos($sql, "FROM"));
                        else $sqlThisFieldOnly = "SELECT ".$field." ".substr($sql, strpos($sql, "FROM"));
                        echo '<small><form method="get" >';
                            echo "Show these Search Results in<br>";
                            foreach($fieldReferences as $tableReferenced => $fieldReferenced)
                            {
                                $conditionsReferenced = $fieldReferenced." IN (".$sqlThisFieldOnly.")";
                                echo '<button type="submit" formaction="'.$URL_MAP[$tableReferenced].'" name="conditions" value="'.$conditionsReferenced.'" >'.$TABLE_PLURALS[$tableReferenced].'</button>';
                            }
                        echo '</form></small>';
                    }
                echo '</th>';
            }
        echo '</tr>';
        while ($row = $result->fetch_assoc())
        {
            echo '<tr>';
                foreach($row as $field => $cellValue) 
                {
                    echo '<td>';
                        
                        if ($isTable && (!in_array($field, $primaryKeys) || count($primaryKeys) > 1) && $_GET[$field] == null)
                        { // show filter button only when field is not primary key or when there are multiple primary keys and is not already requested
                            echo '<small style="text-align:right"><form method="get">';
                                if ($conditions != '' && strpos($conditions, "IN (") != 0) echo "Restrict results by ".$field.":";
                                else echo "Filter ".$tablePlural." by ".$field.":";
                                if ($conditions != '' && strpos($conditions, "IN (") != 0) echo '<input type="hidden" name="conditions" value="'.$conditions.'" >';
                                echo '<input type="submit" name="'.$field.'" value="'.$cellValue.'" />';
                            echo '</form></small>';
                        }
                        echo "<div align='center'>".$cellValue."</div>";
                        // if field has references in other tables
                        $fieldReferences = getFieldReferencesFromOtherTables($table, $field); // returns empty if no match or no table
                        echo '<form method="get" >';
                            foreach($fieldReferences as $tableReferenced => $fieldReferenced)
                            {
                                echo '<button type="submit" formaction="'.$URL_MAP[$tableReferenced].'" name="'.$fieldReferenced.'" value="'.$cellValue.'" >Show it in '.$TABLE_PLURALS[$tableReferenced].'</button>';
                            }
                        echo '</form>';
                    echo '</td>';
                }
            echo '</tr>';
        }
	echo "</table>";
}

function saveNewToTable($conn, $table, $request)
{
    $tableFieldsCSV = createStringWithTableFieldsCSV($conn, $table);
    $fields = getFieldsFromTable($conn, $table);

    $sql = "INSERT INTO ".$table." (".$tableFieldsCSV.")
            VALUES (";
       // .$request['name']."', '".$request['sport']."', '".$request['numTeams']."')";
    
    $doIt = TRUE;
    foreach ($fields as $field)
    {
        $sql .= "'".$request[$field]."', ";
        if ($request[$field] == '')
        {
            echo "<b>".$field."</b> MUST NOT BE BLANK<br>";
            $doIt = FALSE;
        }
    }
    $sql = rtrim($sql,', ');
    $sql .= ")";

    if(!$doIt) return $doIt; // There are fields left to complete!

    if ($conn->query($sql) != TRUE) echo $conn->error;
    //else echo "<meta http-equiv='refresh' content='1'>";
    else
    {
        echo $table." successfully added to database!<br><br>";
        //echo "<meta http-equiv='refresh' content='1'>"; // refresh
        //$primaryKeys = getPrimaryKeysFromTable($conn, $table);
        //foreach($primaryKeys as $key)
        //{
            //echo $key.": ";
            //echo getFieldElementsFromTable($conn, $table
        //}
    }
    return $doIt;
}

?>
