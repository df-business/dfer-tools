<?php
require "./src/Common.php";
require "./src/Address.php";

$common = new Dfer\Tools\Common;
$Address = new Dfer\Tools\Address;

$common::print($common::about());
$common::print($Address->provinceAbbreviation('湖北省'));
$common::print($Address->getChinaChar(5));
$common::print($Address->randAddress(true));

$str='{
    "RECORDS": [
        {
            "id": "110101001",
            "name": "东华门街道",
            "name_traditional": "東華門街道",
            "name_en": "",
            "parent_id": "110101",
            "type": "0",
            "sort": "0",
            "type_name": "",
            "other_name": "",
            "name_format": ""
        }
    ]
}';
$common::print($common->mysqlJsonToArray($str));