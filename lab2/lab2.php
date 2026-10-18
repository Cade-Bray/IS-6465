<?php

// Lab 2: PHP Expressions and Control Structures

echo '<h1>Lab 2A: Expressions and Control Structures</h1>';

// 1. Expressions and Operators
echo '<h2>1. Expressions and Operators</h2>';

$x = -5;

// TODO: Write an expression that calculates:
// y = 3(abs(2x) + 4)
$y = 3 * (abs(2 * $x) + 4);

echo "The value of y is: $y<br>";

// 2. Boolean Comparisons
echo '<h2>2. Comparisons</h2>';

// TODO: Use var_dump() to compare:
// 2 > 1
echo "2 > 1 <br>";
var_dump(2>1);

// 2 == 3
echo "<br><br>2 == 3<br>";
var_dump(2 == 3);

// "5" == 5
echo "<br><br>\"5\" == 5<br>";
var_dump("5" == 5);

// "5" === 5
echo "<br><br>\"5\" === 5<br>";
var_dump("5" === 5);

// 3. Unary Operators
echo '<h2>3. Unary Operators</h2>';

$c = 5;

// TODO: Create variables that demonstrate unary minus, unary plus,
// and pre-increment. Display the results.
echo "The value of the variable c: $c<br><br>";

echo "Incremented by one: " . +$c . "<br><br>";

echo "Decremented by one: " . -$c . "<br><br>";

echo "";

// 4. Operator Precedence
echo '<h2>4. Operator Precedence</h2>';

// TODO: Compare the results of these two expressions:

// 2 + 3 * 4
$exp1 = 2 + 3 * 4;
// (2 + 3) * 4
$exp2 = (2 + 3) * 4;

echo "Expression one evaluates to: $exp1. Whereas expression two evaluates to: $exp2.<br>
    The reason these expressions evaluated differently is because the system took into account the
    order of operations. Multiplication and division are calculated before the addition and subtraction.
    These expression utilize the same values but one includes paranthesis to change the order of operations.";


// 5. Assignment Associativity
echo '<h2>5. Assignment Associativity</h2>';

// TODO: Assign 0 to $level, $score, and $time in one statement.
// Display each variable.
$level = $score = $time = 0;

echo "Level: $level. Score: $score. Time: $time.";

// 6. Equality and Assignment
echo '<h2>6. Equality and Assignment</h2>';

// TODO: Create variables with equal and unequal values.
// Use var_dump() to compare them with == and ===.
$x = 10;
$y = "10";
$z = 20;

//Equal value, but a different type
echo "10 == \"10\": ";
var_dump($x == $y);

echo "<br><br>10 === \"10\": ";
var_dump($x === $y);

// Unequal values
echo "<br><br>$x == $z: ";
var_dump($x == $z);

echo "<br><br>$x === $z: ";
var_dump($x === $z);

// 7. Logical Operators
echo '<h2>7. Logical Operators</h2>';

$a = 1;
$b = 0;

// TODO: Use var_dump() to demonstrate &&, ||, and !.
echo "$a && $b: "; var_dump($a && $b); echo "<br>";
echo "$a || $b: "; var_dump($a || $b); echo "<br>";
echo "!$a && $b: "; var_dump(!$a || $b); echo "<br>";

// 8. Scholarship Decision with if...else
echo '<h2>8. Scholarship Decision with if...else</h2>';

$gpa = 3.59;

// TODO: Use if...else to display whether the student receives a scholarship.
echo "Scholarship minimum is a 3.7 GPA. Students GPA is $gpa.<br>Scholarship outcome:";
if ($gpa >= 3.7){
    echo " Student qualifies for a scholarship.";
} else {
    echo " Student doesn't qualify for a scholarship";
}

// 9. Scholarship Decision with if...elseif...else
echo '<h2>9. Scholarship Decision with if...elseif...else</h2>';

// TODO: Use if...elseif...else to display:
// Full scholarship for GPA 3.7 or higher
// Partial scholarship for GPA 3.5 through 3.69
// No scholarship otherwise
echo "Scholarship minimum is a 3.7 GPA. Students GPA is $gpa.<br>Scholarship outcome:";
if ($gpa >= 3.7){
    echo " Student qualifies for a full scholarship.";
} elseif ($gpa >= 3.5) { // The first if will catch  above a 3.69 so we don't need redudant definitions.
    echo " Student qualifies for a partial scholarship.";
} else {
    echo " Student doesn't qualify for a scholarship.";
}

// 10. Access Status
echo '<h2>10. Access Status</h2>';

$status = 'pending';

// TODO: Use a switch statement to display a message for:
// approved, pending, and all other statuses
switch ($status) {
    case 'approved':
        echo "Status is $status and can proceed.";
        break;
    
    case 'pending':
        echo "Status is $status and you will get an update shortly.";
        break;
        
    default:
        echo "Status is unknown. Please contact support.";
        break;
}

// 11. While Loop
echo '<h2>11. While Loop</h2>';

$fuel = 10;

// TODO: Create a while loop.
// Display "There is enough fuel" while fuel is greater than 1.
// Decrease fuel during each repetition.

while ($fuel > 1) {
    echo "There is enough fuel.<br>";
    $fuel--;
}

// 12. Multiplication Table
echo '<h2>12. Multiplication Table</h2>';

// TODO: Create a for loop that displays the 12-times table
// from 1 through 12.
for ($i=1; $i < 13 ; $i++) {
    echo "$i * 12 = " . ($i * 12) . "<br>";
}
