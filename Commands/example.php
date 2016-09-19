<?php

/*
 *  -- Example of command file. --
 *
 *  The file must contain a variable "$command_class" containing an anonymous class which has a main method "main()".
 *  The main method should return a value, but it will be converted to boolean!
 *  Code written outside the classroom can lead to errors and system crash!
 *
 *  The main method is passed an instance of the Bot with some of the available methods and the last api response data.
 *  The list of available constants and methods:
 *   @ const TITLE
 *   @ const DESCRIPTION
 *   @ const AUTHOR
 *   @ const VERSION
 *   @ method Date($format = 'Y-m-d H:i:s')
 *   @ method Logger($string, $add_date = true, $in_file = true)
 *   @ method Logger_Template($title, $d, ...$string)
 *   @ method Requester($method, $parameters = array())
 *   @ method Message_Sender($message, $data)
 *
 */

$command_class = new class()
{
    // START CODE
    public function main($args) // Main Method
    {
        $args[0]->Message_Sender('Example message! And a random number: ' . rand(1, 1000), $args[1]);
        return 1;
    }
    // END CODE
};