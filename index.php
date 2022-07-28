<?php
require "./src/Common.php";
require "./src/Address.php";

$common = new Dfer\Tools\Common;
$Address = new Dfer\Tools\Address;

$common::print($common::about());
$common::print($Address->provinceAbbreviation('湖北省'));
$common::print($Address->getChinaChar(5));
$common::print($Address->randAddress());