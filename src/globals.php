<?php
// global values

// category names and their respective ids
$categoryNameToId = [
    "terrain" => 0,
    "art" => 1,
    "redstone" => 2,
    "detail" => 3,
    "other" => 4,
];

// category ids and their respective names
$categoryIdToName = array_flip($categoryNameToId);

$categoryMenuArray = [
    0 => [
        "url" => "terrain",
        "name" => "Terrain",
        "selected" => false,
    ],
    1 => [
        "url" => "art",
        "name" => "Art",
        "selected" => false,
    ],
    2 => [
        "url" => "redstone",
        "name" => "Redstone",
        "selected" => false,
    ],
    3 => [
        "url" => "detail",
        "name" => "Detail",
        "selected" => false,
    ],
    4 => [
        "url" => "other",
        "name" => "Other",
        "selected" => false,
    ],
];