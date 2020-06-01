<?php
$array = explode(',', $_GET['array']);
$count = count($array);
// 修正はここから
for ($i = 0; $i < $count - 1; $i++) {
	for($j = 0; $j < $count - 1 - $i ; $j++)
	 if($array[$j] > $array[$j+1]){
		 $temp = $array[$j];
		 $array[$j] = $array[$j+1];
		 $array[$j+1] = $temp;
	 }
}
// 修正はここまで

echo "<pre>";
print_r($array);
echo "</pre>";
