<?php

echo 'Loaded File: ' . __FILE__ . '<br />' . PHP_EOL;
echo 'Unix Timestamp: ' . time();
ob_start();
phpinfo();
$phpinfo = ob_get_contents();
ob_end_clean();

$phpinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$phpinfo);
echo '<div id="phpinfo">' . $phpinfo . '</div>';
?>
<style type="text/css">
	#phpinfo { margin-left: 10px; }
	#phpinfo pre {}
	#phpinfo a:link {}
	#phpinfo a:hover {}
	#phpinfo table {}
	#phpinfo .center {}
	#phpinfo .center table {}
	#phpinfo .center th {}
	#phpinfo td, th {}
	#phpinfo h1 {}
	#phpinfo h2 {}
	#phpinfo .p {}
	#phpinfo .e {}
	#phpinfo .h {}
	#phpinfo .v {}
	#phpinfo .vr {}
	#phpinfo img {}
	#phpinfo hr {}
</style>
