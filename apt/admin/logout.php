<?php
	// J'espère que ce n'est pas trop compliqué :)
	session_start();
	session_destroy();
	header("Location: index.php");
?>