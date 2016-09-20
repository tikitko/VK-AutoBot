<?php

/*
 *  -- Example of command file. --
 *
 *  The file must contain a variable "$command_class" containing an anonymous class which
 *      has a main method "main()" and optionally, constant TITLE, DESCRIPTION.
 *  The main method should return a value, but it will be converted to boolean!
 *  Code written outside the classroom can lead to errors and system crash!
 *
 *  The main method is passed an instance of the Bot with some of the available methods,
 *      entered command and the last api response data.
 *  The list of available constants and methods:
 *   @ const TITLE (I)
 *   @ const DESCRIPTION (I)
 *   @ const AUTHOR (I)
 *   @ const VERSION (I)
 *   @ const SLEEP (S)
 *   @ const C_SEPARATOR (S)
 *   @ method Date($format = 'Y-m-d H:i:s')
 *   @ method Logger($string, $add_date = true, $in_file = true)
 *   @ method Logger_Template($title, $d, ...$string)
 *   @ method Requester($method, $parameters = array(), $attempts_amount = 3)
 *   @ method Message_Sender($message, $data)
 *
 */

$command_class = new class()
{
    const TITLE = 'Example!';
    const DESCRIPTION = 'Example of command!';

    public function main($args)
    {
        $command_value = !empty($args[1][1]) ? $args[1][1] : 'NULL';
        $string = 'Example message! Value: ' . $command_value . '!';
        $args[0]->Message_Sender($string, $args[2]);
        return 1;
    }
};