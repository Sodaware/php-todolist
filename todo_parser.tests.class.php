<?php

// What we're testing
require_once dirname(__FILE__) . '/todo_parser.class.php';

class ToDoParserClassTests extends PHPUnit_Framework_TestCase
{
	
	// ----------------------------------------------------------------------
	// -- Helper Tests
	// ----------------------------------------------------------------------

	function testCanStripColorCodes()
	{
		$this->assertEquals('This is red', ToDoParser::strip('This is %Rred%n'), 'Did not strip colour code characters');
	}

}