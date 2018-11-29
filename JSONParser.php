#!/usr/bin/php
<?php
// A CGI written in PHP that is meant to decode given JSON files/objects and their respective sub objects.
// Files: JSONParser.php
// Description: This project uses a php script to process GET parameters if present and if not present
// an html form to obtain them. After sanitizing input, the input is then utilized to index through JSON files
// to print corresponding info to the web browser. If a match is found, the whole object related to that data is printed
// to the screen.
// References: json_last_error handling example was pulled from: http://php.net/manual/en/function.json-last-error.php used in json_local_decode.
// README: When a match is found, the whole object associated with it is printed. After each key value pair of the object, a line break is
// put in before the next one. This can kind of make the output a little hard to read so to dillenate between valid matching objects foundmatch
// each new match starts with: "Found Key: #KEY Found Value: #VALUE. Here is the entire object: ". It is bolded and makes it easier to read
// the output in a web browser.
define("ROOTJSON", "DataSources.json"); // defines const of DataSources.json such that this could be changed.
// create_header() creates the opening HTML tags needed for outputting to the browser
function create_header()
{
  echo "
  <html>
  <head>
  <title> CS 316 Project 4 </title>
  </head>
  <body>
  ";
}
// end of create_header()

// create_footer() creates the closing HTML tags needed for outptting to the browser
function create_footer()
{
  echo "
  </body>
  </html>
  ";
}
// end of create_footer()

// json_local_decode() takes a file name as a parameter and returns a decoded json string that can be properly indexed
function json_local_decode($filename)
{
  $file = file_get_contents($filename, true);
  $result = json_decode($file, true);
  switch (json_last_error()) { // switch for json error. if any errors found, php stops execution and exits with specfied error
        case JSON_ERROR_NONE:
            return $result; // if there is no error, return the result
        break;
        case JSON_ERROR_DEPTH:
            exit(' - Maximum stack depth exceeded');
        break;
        case JSON_ERROR_STATE_MISMATCH:
            exit(' - Underflow or the modes mismatch');
        break;
        case JSON_ERROR_CTRL_CHAR:
            exit(' - Unexpected control character found');
        break;
        case JSON_ERROR_SYNTAX:
            exit( ' - Syntax error, malformed JSON');
        break;
        case JSON_ERROR_UTF8:
            exit(' - Malformed UTF-8 characters, possibly incorrectly encoded');
        break;
        default:
            exit( ' - Unknown error');
        break;
    }
}
// end of json_local_decode()

// echoForm() echos the html needed to display the form as well as using foreach to dynamically populate drop down options.
function echoForm($rootJSONString)
{
  echo "
  <form action = 'Hays1McNerney2_p4.php' method = 'get'>
    <fieldset>
      <legend> XELKALAI INDEX </legend>
      <p>
        <label> Categories: </label>
        <select name = 'category'>
        ";
        foreach ($rootJSONString as $key1 => $value1) // index through the initial nest of the JSON object
        {
          foreach ($value1 as $key2 => $value2) // index through the second nest of the JSON object
          {
            if ($key1 == "categories")
            {
              echo "<option value = '$key2'> $key2 </option>";
            }
            else break; // as soon as the key is search terms, break out of the foreach.
          }
        }
        echo "
        </select>
        <label> Search terms: </label>
        <select name = 'whichfield'>
  ";
  foreach ($rootJSONString as $key1 => $value1)
  {
    foreach($value1 as $key2 => $value2)
    {
      if ($key1 == "searchterms")
      {
        echo "<option value = '$value2'> $value2 </option>";
      }
      else break;
    }
  }
  echo "
  </select>
  Text Input: <input type = 'text' name = 'findme'> <br>
  </p>
  <input type = 'submit' value = 'Submit Values'>
  </fieldset>
  </form>
  ";
}
// end of echoForm()

// display_form() displays the HTML form. Upon action of the form, the scirpt invokes itself and returns to the conditional entry point below.
function display_form()
{
  create_header();
  $rootJSONString = json_local_decode(ROOTJSON);
  echoForm($rootJSONString);
  create_footer();
}
// end of display_form()

// display_error() is called by validate_input() in the case that an invalid paaram has been pased via GET. It displays a html file with an error and stops execution of the program
function display_error($userInput, $type)
{
  create_header();
  echo "<p> ERROR: $userInput is not a valid member/was not found in $type </p>";
  create_footer();
  exit();
}
// end of display_error()

// validate_input() takes in the get parameters passed by process_form() and validates that they are valid options/present in the JSON file
function validate_input($getCategory, $getWhichField, $rootJSONString)
{
  $categoryBool = false; // intialize the category bool to false so the program assumes it has not seen it in the valid list of category sub objects yet.
  $whichfieldBool = false; // initialize the whichfield bool to false so the program assumes it has not seen it in the valid list of whichfield sub objects yet.
  foreach ($rootJSONString as $key1 => $value1) // index through the initial nest of the JSON object
  {
    foreach ($value1 as $key2 => $value2) // index through the second nest of the JSON object
    {
      if ($key1 == "categories") // if we are indexing through categories
      {
        if ($key2 == $getCategory) // if the current key in categories is equal to what they passed for a Get parameter, bool is true  and input is valid
        {
          $categoryBool = true; // valid input
        }
      }
      else if ($key1 == "searchterms") // if we are indexing through searchterms
      {
          if ($value2 == $getWhichField) // if the current value in searchterms is equal to what they passed for a Get parameter, bool is true and input is valid
          {
            $whichfieldBool = true; // validate input
          }
      }
    }
  }
  if ($categoryBool == false) // after iterating through the entire json, if the user input was never found
  {
    display_error($getCategory, "category");
  }
  if ($whichfieldBool == false)
  {
    display_error($getWhichField, "whichfield");
  }
}
// end of validate_input()

// print_recursion() is used to recursively print the valid objects of a matched query
function print_recursion($value)
{
  foreach($value as $key1 => $value1)
  {
    if (gettype($value1) == 'string')
    {
      echo "<p>$key1 : $value1</p>";
    }
    else if (gettype($value1) == 'array')
    {
      print_recursion($value1);
    }
  }
}
// end of print_recursion()

// check_if_match() A function that is called after the initial foreach to index through the rest of the JSON objects until a match is found
function check_if_match($value, $chosenField, $getFindMe, &$foundMatch)
{
  foreach($value as $key1 => $value1) // index through the previous value passed in
  {
    if (gettype($value1) == 'string' && $chosenField == $key1 && $getFindMe == $value1) // if the type is a string, and the current key matches the search term, and the current value equals findme
    {
      $foundMatch = true; // change boolean to true
      echo "<p><b> Found Key: $key1 Found Value: $value1. Here is the entire object: </b><br></p>";
      print_recursion($value); // prints all values of object
      break; // break out of loop
    }
    else if (gettype($value1) == 'array') // if the value is an array, it means we have to index further to find strings that could be a potential match
    {
      check_if_match($value1, $chosenField, $getFindMe, $foundMatch); // calls function again. Eventually will stop when there are no further arrays to go through.
    }
  }
}
// end of check_if_match()

// process_form()
function process_form()
{
  $getCategory = $_GET['category'];
  $getWhichField = $_GET['whichfield'];
  $getFindMe = $_GET['findme'];
  $rootJSONString = json_local_decode(ROOTJSON); // decodes root json file
  validate_input($getCategory, $getWhichField,$rootJSONString);
  foreach ($rootJSONString as $key1 => $value1) // reparse through
  {
    foreach ($value1 as $key2 => $value2)
    {
      if ($key2 == $getCategory) // if the current 2nd nested key is equal to what the user passed for category, pull the value at that key for the chosen file name.
        $chosenFileName = $value2;
      if ($value2 == $getWhichField)
        $chosenField = $value2;
    }
  }
  // echo "<p> File Name: $chosenFileName Chosen Field: $chosenField";
  create_header();
  create_footer();
  $foundMatch = false;
  $requestedJSONString = json_local_decode($chosenFileName);
  foreach($requestedJSONString as $key1 => $value1)
  {
    // var_dump($key1);
    // var_dump($value1);
    if (gettype($value1) == 'string' && $chosenField == $key1 && $getFindMe == $value1)
    {
      $foundMatch = true;
      echo "<p> Found Key: $key1 Found Value: $value1";
      break;
    }

    create_header();
    check_if_match($value1, $chosenField, $getFindMe, $foundMatch);
    create_footer();
  }
  if ($foundMatch == false) // if the value of foundmatch was unchanged, it means no match was found so print corresponding message to user
  {
    create_header();
    echo "<p> No match found! Nothing in $chosenFileName with Key: $chosenField and Value: $getFindMe could be found ! </p>";
    create_footer();
  }
}
// end of process_form()

// Entry Point
if (isset($_GET['category']) && isset($_GET['whichfield']) && isset($_GET['findme'])) // if all GET parameters are initialized
{
  process_form(); // if param present, process the data from the form
} else
{
  display_form(); // if params are not present, display the form to acquire the data
}
 ?>
