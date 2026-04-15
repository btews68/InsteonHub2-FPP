<?php

$plugin = preg_replace('/.*\/plugins\/(.*)/', '$1', dirname(__FILE__));
$url = 'plugin.php?plugin=' . $plugin . '&page=settings.php';
?>
<li><a href="<?php echo $url; ?>">Insteon Hub 2</a></li>
