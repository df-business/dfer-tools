<?php
require "./src/Common.php";
require "./src/Address.php";
require "./src/Img/Common.php";
require "./src/Img/Compress.php";
require "./src/Files.php";

$common = new Dfer\Tools\Common;
$Address = new Dfer\Tools\Address;

$Files = new Dfer\Tools\Files;


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


$common::print($common->isWeixin());

$common::print($Files->uploadFile('C:\unintall.log'));


