<?php

////////////////////////////////////////////
// Definitionen
////////////////////////////////////////////


	// MySQL-Verbindungsdaten
	$host = 'localhost';      	// oder IP-Adresse des DB-Servers
	//$db   = 'testdb';         // Name der Datenbank
	$h3ddb   = 'CH3DHeight_2';    // Name der Datenbank für die Höhendaten
	$db   = 'rkrk';         	// Name der Datenbank
	//$user = 'RKRK';           // Benutzername
	//$pass = 'Bambi$2020!';    // Passwort
	$user = 'root';           	// Benutzername
	$pass = 'Comia$1970';     	// Passwort
	$charset = 'utf8mb4';

	$DBConn = null;

////////////////////////////////////////////
// Funktionen
////////////////////////////////////////////
function GetQueryToArray ($sql, $database="") {
	global $host, $db, $user, $pass, $charset;
	// Create connection
	if ($database=="") {
		$conn = new mysqli($host, $user, $pass, $db);
	} else {
		$conn = new mysqli($host, $user, $pass, $database);
	}

	// Check connection
	if ($conn->connect_error) {
		die("<span style='color:red'>Verbindung kann nicht hergestellt werden: " . $conn->connect_error. "</span>");
	}
	//echo "<span style='color:green'>Verbindung erfolgreich!</span><br>";

	$result = mysqli_query($conn, $sql);
	//echo $result->num_rows;

	// fetch results to Array
	if($result){
		 // Cycle through results
		while ($row = $result->fetch_object()){
			$DataArray[] = $row;
		}
		// Free result set
		$result->close();
		$conn->next_result();
	}
	
	// Close connection
	$conn->close();

	return ($DataArray);
}	


function ExecuteQueries ($sql, $database="") {
	global $host, $db, $user, $pass, $charset;
	// Create connection
	if ($database=="") {
		$conn = new mysqli($host, $user, $pass, $db);
	} else {
		$conn = new mysqli($host, $user, $pass, $database);
	}

	// Check connection
	if ($conn->connect_error) {
		die("<span style='color:red'>Verbindung kann nicht hergestellt werden: " . $conn->connect_error. "</span>");
	}
	//echo "<span style='color:green'>Verbindung erfolgreich!</span><br>";
	if (is_array($sql)) {
		foreach ($sql as $key => $query) {
			if ($conn->query($query) === TRUE) {
				//echo "<span style0'color:green'>Query " . $key . " erfoglreich ausgeführt!</span><br>";
			} else {
				echo "<span style='color:red'>Fehler beim Ausführen des Query: " . $key . "/" . $conn->error . "</span><br>";
			}
		}	
	} else {
		if ($conn->query($sql) === TRUE) {
			//echo "<span style='color:green'>Query erfoglreich ausgeführt!</span><br>";
		} else {
			echo "<span style='color:red'>Fehler beim Ausführen des Query " . $conn->error . "</span><br>";
		}
	}

	// Close connection
	$conn->close();
}	


////////////////////////////////////////////
// Test Funktionen
////////////////////////////////////////////

function CallMySQLQuery() {
	global $host, $db, $user, $pass, $charset;

	// Datenbankverbindung herstellen mit PDO
	$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
	$options = [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	];

	try {
		$pdo = new PDO($dsn, $user, $pass, $options);

		// Einfache Abfrage – alle Benutzer abrufen
		$stmt = $pdo->query("SELECT id, name, email FROM users");

		echo "<h2>Benutzerliste</h2><ul>";
		while ($row = $stmt->fetch()) {
			echo "<li>ID: {$row['id']} | Name: {$row['name']} | Email: {$row['email']}</li>";
		}
		echo "</ul>";

	} catch (PDOException $e) {
		echo "Verbindungsfehler: " . $e->getMessage();
	}
}

function CreateMySQLTable () {
	global $host, $db, $user, $pass, $charset;
	// Create connection
	$conn = new mysqli($host, $user, $pass, $db);

	// Check connection
	if ($conn->connect_error) {
		die("Verbindung kann nicht hergestellt werden: " . $conn->connect_error);
	}
	echo "Verbindung erfolgreich!<br>";

	// SQL to create table
	$sql = "CREATE TABLE IF NOT EXISTS animals (
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		name VARCHAR(50) NOT NULL,
		species VARCHAR(50) NOT NULL,
		age INT(3) NOT NULL
	)";

	if ($conn->query($sql) === TRUE) {
		echo "Table 'animals' erfolgreich erstellt!<br>";
	} else {
		echo "Fehler beim Erstellen der Tabelle: " . $conn->error;
	}

	// Insert entries into the table
	$sql = "INSERT INTO animals (name, species, age) VALUES
		('Bella', 'Dog', 3),
		('Milo', 'Cat', 2),
		('Charlie', 'Rabbit', 1),
		('Luna', 'Dog', 4)";

	if ($conn->query($sql) === TRUE) {
		echo "Neue Einträge erfolgreich hinzugefügt!<br>";
	} else {
		echo "Fehler beim Hinzufügen der Einträge: " . $conn->error;
	}

	// Close connection
	$conn->close();
}	

//PHPINFO;
//phpinfo();

?>