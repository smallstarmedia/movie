<?php

if (isset($_GET['del'])) {
	$sql = 'select * from mkcms_vod_class where c_id = ' . $_GET['del'] . '';
	$result = mysqli_query($conn,$sql);
	$row = mysqli_fetch_array($result);
	mysqli_query($conn,'delete from mkcms_vod_class where c_id = ' . $_GET['del'] . '');
	mysqli_query($conn,'delete from mkcms_vod where d_class = ' . $_GET['del'] . '');
	alert_href('删除成功！', 'cms_channel.php');
}
