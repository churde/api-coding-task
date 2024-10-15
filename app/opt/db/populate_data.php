<?php

// XXX Quitar el full path, solo es para pruebas
// require '../../vendor/autoload.php';
require '/var/www/vendor/autoload.php';

use Faker\Factory;

// Parse command line arguments
$options = getopt("", ["factions:", "equipments:", "characters:"]);
$numFactions = isset($options['factions']) ? intval($options['factions']) : 43;
$numEquipments = isset($options['equipments']) ? intval($options['equipments']) : 86;
$numCharacters = isset($options['characters']) ? intval($options['characters']) : 10000;

// Database connection
$host = 'db';
$db = 'lotr'; // Change this to your database name
$user = 'root'; // Change this to your database username
$pass = 'root'; // Change this to your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Create a Faker instance
$faker = Factory::create();

// Custom LOTR-themed data
$lotrFactions = ['Elves', 'Men', 'Dwarves', 'Hobbits', 'Orcs', 'Uruk-hai', 'Ents', 'Wizards'];
$lotrEquipmentTypes = ['Sword', 'Bow', 'Axe', 'Staff', 'Armor', 'Shield', 'Ring'];
$lotrKingdoms = ['Gondor', 'Rohan', 'Rivendell', 'Lothlorien', 'Erebor', 'Moria', 'The Shire', 'Mordor', 'Isengard'];

// Additional faction and equipment name components
$factionAdjectives = ['Northern', 'Southern', 'Eastern', 'Western', 'High', 'Low', 'Dark', 'Light', 'Ancient', 'Noble'];
$equipmentAdjectives = ['Mighty', 'Enchanted', 'Cursed', 'Blessed', 'Legendary', 'Ancient', 'Elven', 'Dwarven', 'Mortal', 'Immortal'];
$equipmentNouns = ['Doom', 'Fate', 'Destiny', 'Glory', 'Honor', 'Power', 'Wisdom', 'Vengeance', 'Justice', 'Peace'];

// Function to generate appropriate birth date based on race
function generateBirthDate($race) {
    $currentYear = 3019; // Year of the War of the Ring
    $minYear = 1; // Minimum year for MySQL DATE type

    switch ($race) {
        case 'Elves':
            $year = $currentYear - rand(100, 3018);
            break;
        case 'Men':
        case 'Hobbits':
            $year = $currentYear - rand(20, 120);
            break;
        case 'Dwarves':
            $year = $currentYear - rand(20, 250);
            break;
        case 'Wizards':
            $year = $minYear;
            break;
        default:
            $year = $currentYear - rand(1, 200);
    }

    $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
    $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT); // Using 28 to avoid issues with February

    return "$year-$month-$day";
}

// Populate Factions
$factionIds = [];
$usedFactionNames = [];
for ($i = 1; $i <= $numFactions; $i++) {
    do {
        if (count($usedFactionNames) >= count($lotrFactions) * count($factionAdjectives)) {
            $factionName = $faker->unique()->word . ' ' . $faker->randomElement($lotrFactions);
        } else {
            $factionName = $faker->randomElement($factionAdjectives) . ' ' . $faker->randomElement($lotrFactions);
        }
    } while (in_array($factionName, $usedFactionNames));

    $usedFactionNames[] = $factionName;

    $stmt = $pdo->prepare("INSERT INTO factions (faction_name, description) VALUES (:faction_name, :description)");
    $stmt->execute([
        'faction_name' => $factionName,
        'description' => $faker->sentence,
    ]);
    $factionIds[] = $pdo->lastInsertId();
}

// Populate Equipments
$equipmentIds = [];
$usedEquipmentNames = [];
for ($i = 1; $i <= $numEquipments; $i++) {
    do {
        if (count($usedEquipmentNames) >= count($equipmentAdjectives) * count($equipmentNouns) * count($lotrEquipmentTypes)) {
            $equipmentName = $faker->unique()->word . ' ' . $faker->randomElement($lotrEquipmentTypes);
        } else {
            $equipmentName = $faker->randomElement($equipmentAdjectives) . ' ' . 
                             $faker->randomElement($equipmentNouns) . ' of ' . 
                             $faker->randomElement($lotrEquipmentTypes);
        }
    } while (in_array($equipmentName, $usedEquipmentNames));

    $usedEquipmentNames[] = $equipmentName;

    $stmt = $pdo->prepare("INSERT INTO equipments (name, type, made_by) VALUES (:name, :type, :made_by)");
    $stmt->execute([
        'name' => $equipmentName,
        'type' => $faker->randomElement($lotrEquipmentTypes),
        'made_by' => $faker->randomElement(['Elves', 'Dwarves', 'Men', 'Orcs']),
    ]);
    $equipmentIds[] = $pdo->lastInsertId();
}

// Populate Characters
for ($i = 1; $i <= $numCharacters; $i++) {
    $faction = $faker->randomElement($lotrFactions);
    $birthDate = generateBirthDate($faction);

    $stmt = $pdo->prepare("INSERT INTO characters (name, birth_date, kingdom, equipment_id, faction_id) VALUES (:name, :birth_date, :kingdom, :equipment_id, :faction_id)");
    $stmt->execute([
        'name' => $faker->firstName . ' ' . $faker->lastName,
        'birth_date' => $birthDate,
        'kingdom' => $faker->randomElement($lotrKingdoms),
        'equipment_id' => $faker->randomElement($equipmentIds),
        'faction_id' => $faker->randomElement($factionIds),
    ]);
}

echo "Database populated with fake data successfully.\n";
echo "Created $numFactions factions, $numEquipments equipments, and $numCharacters characters.\n";
