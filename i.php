<?php


define('DB_NAME','backend');
define('DB_USER','root');
define('DB_PASS','');
define('DB_CHARSET','utf8mb4'); 
define('DB_PORT', 3306); 
define('DB_HOST', 'localhost'); 

$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=".DB_PORT.";charset=".DB_CHARSET;
$opt = [
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES   => false,
];

$db = new PDO($dsn, DB_USER, DB_PASS, $opt);

if (!isset($_GET['id']))
	die ('ID is not set');

$id = $_GET['id'];

$q = 'SELECT 
	
	items.id,
	items.name,
	items.description,
	items.image,
	items.capterra_id,
	items.image,
	items.website

 from items 
 where items.id = '.$id;

$stm = $db->prepare($q);
$stm->execute();
$item = $stm->fetchAll()[0];

// print_r($item);

$q = 'SELECT 
	
	comments.items_id,
	comments.text,
	comments.rating

from comments where comments.items_id = '.$id;

$stm = $db->prepare($q);
$stm->execute();
$comments = $stm->fetchAll();

// print_r($comments); 

?>


<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
</head>
<body>
<div class="name"><?=$item['name']?></div>
<div class="description"><?=$item['description']?></div>

<?php foreach ($comments as $c): ?>
<div class="review">
	<div class="rating"><?=$c['rating']?></div>
	<div class="text"><?=trim($c['text'])?></div>
</div>

<?php endforeach; ?>

</body>
</html>



