<?php
require "./src/Common.php";
$tools = new Dfer\Tools\Common();
$tools::print($tools::about());
$tools::print($tools->provinceAbbreviation('湖北省'));