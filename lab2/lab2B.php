<?php

// Lab 2B: PHP Functions and Basic Objects

echo '<h1>Lab 2B: Functions and Objects</h1>';

// 1. Function with Parameters and Return Value
echo '<h2>1. Function with Parameters and Return Value</h2>';

function formatName($first, $middle, $last)
{
    // TODO: Clean the capitalization of each name.
    // TODO: Return the complete name as one string.
    return ucwords(strtolower("$first $middle $last"));
}

// TODO: Call formatName() using sample data.
echo formatName("CADE", "BAxtEr", "bray");

// 2. Function That Returns an Array
echo '<h2>2. Function That Returns an Array</h2>';

function cleanNameParts($first, $middle, $last)
{
    // TODO: Return an array containing the cleaned name parts.
    return [
        ucwords(strtolower($first)), 
        ucwords(strtolower($middle)), 
        ucwords(strtolower($last))
    ];
}

// TODO: Call cleanNameParts() and display one item from the array.
echo cleanNameParts("CADE", "BAxtEr", "bray")[1];

// 3. Variable Scope
echo '<h2>3. Variable Scope</h2>';

$first = 'Maria';
$last = 'Chen';

function buildFullName()
{
    // TODO: Use the global keyword to access $first and $last.
    // Return the complete name.
    global $first;
    global $last;
    return "$first $last";
}

// TODO: Call buildFullName() and display the result.
echo "With globals: " . buildFullName();

function buildFullName2($first, $last)
{
    // TODO: Return the complete name using the parameters.
    return "$first $last";
}

// TODO: Call buildFullName2() and display the result.
echo "<br>With parameters: " . buildFullName2($first, $last);

// 4. Static Variable
echo '<h2>4. Static Variable</h2>';

function nextTicketNumber()
{
    // TODO: Use a static variable so this function returns
    // 1 on its first call, 2 on its second call, and so on.
    static $ticket = 0;
    return ++$ticket;
}

// TODO: Call the function at least twice.
for ($i=0; $i < 5; $i++) { 
    echo nextTicketNumber() . "<br>";
}

// 5. User Class
echo '<h2>5. User Class</h2>';

class User
{
    public $name;
    public $email;

    // TODO: Create a display() method that returns the user's name.
    function display(){
        return $this->name;
    }
}

// TODO: Create a User object.
$ex_user = new User();
// TODO: Assign a name and email.
$ex_user->name = "Cade";
$ex_user->email = "cade.bray@utah.edu";
// TODO: Display the result of the display() method.
echo $ex_user->display();

// 6. Constructor and Password Hashing
echo '<h2>6. Constructor and Password Hashing</h2>';

class User2
{
    public $name;
    private $passwordHash;

    // TODO: Create a constructor that receives a name and password.
    function __construct($name, $password){
        $this->name = $name;

        // Store the name and create a password hash using password_hash().
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }
}

// TODO: Create one User2 object and display it using print_r().
$ex_user2 = new User2("Cade Bray", "MyStrongPass123!");
print_r($ex_user2);