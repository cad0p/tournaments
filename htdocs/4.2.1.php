<html>

<head>
	<title>4.2.1</title>
</head>
<?php
$N_TEAMS = 10;
$N_MAX_POINTS = 100;
?>
<body>
	<form action="4.2.2.php" method="post">
		<fieldset>
			<legend>Enter your information in the form below:</legend>
			<p>
				<b>Last league table available?</b>
				<pre>
				<?php
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

					echo "<input type='hidden' name='N_TEAMS' value='$N_TEAMS'>";
					echo "<input type='hidden' name='N_MAX_POINTS' value='$N_MAX_POINTS'>";
				?>
				<br>
				*Nameless teams with 0 points will be deleted!
				</pre>
				<br><br>
				<b>Now enter results from last matchweek!</b>
				<pre>
				*A team can only do one match in a Matchweek
				As an example, the first writable cell on the upper left would represent the score bewteen team #1 and #2
				<table style="width:100%" border="1">
				<?php
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
