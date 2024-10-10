<?php

// XXX Quitar el full path, solo es para pruebas
// require '../../vendor/autoload.php';
require '/var/www/vendor/autoload.php';

use Faker\Factory;

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

// Populate Factions
for ($i = 1; $i <= 100; $i++) {
    $stmt = $pdo->prepare("INSERT INTO factions (faction_name, description) VALUES (:faction_name, :description)");
    $stmt->execute([
        'faction_name' => $faker->word . ' Faction',
        'description' => $faker->sentence,
    ]);
}

// Populate Equipments
for ($i = 1; $i <= 100; $i++) {
    $stmt = $pdo->prepare("INSERT INTO equipments (name, type, made_by) VALUES (:name, :type, :made_by)");
    $stmt->execute([
        'name' => $faker->word . ' ' . $faker->word,
        'type' => $faker->word,
        'made_by' => $faker->company,
    ]);
}

// Populate Characters
for ($i = 1; $i <= 100; $i++) {
    $stmt = $pdo->prepare("INSERT INTO characters (name, birth_date, kingdom, equipment_id, faction_id) VALUES (:name, :birth_date, :kingdom, :equipment_id, :faction_id)");
    $stmt->execute([
        'name' => $faker->name,
        'birth_date' => $faker->date(),
        'kingdom' => $faker->word,
        'equipment_id' => rand(1, 100), // Assuming you have at least 100 equipments
        'faction_id' => rand(1, 100), // Assuming you have at least 100 factions
    ]);
}

echo "Database populated with fake data successfully.\n";