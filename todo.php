<?php
/**
 * Copyright (c) 2011 Phil Newton
 * Homepage: http://www.sodaware.net/dev/tools/php-todolist/
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

// TODO: Allow patterns to be added to file types, so we can have multiple file 
// TODO: types with different patterns (such as one for PHP, one for JS etc)

// Required Pear libraries
require_once 'Console/Color.php';
require_once 'Console/CommandLine.php';

// Parser core
require 'todo_parser.class.php';

// If the terminal is Windows based, we'll skip the colour formatting.
define('IS_WINDOWS', isset($_SERVER['SystemRoot']) && (strpos(strtolower($_SERVER['SystemRoot']), 'windows') > 0));

// Get command line args
$args   = get_commandline_args();

// Create todo driver & setup
$todo = new ToDoParser($args->options);

// If no external config specified, use the default settings
if ($args->options['config'] == '') {
	if (!$todo->loadConfig(dirname(__FILE__) . '/config/default.config')) {
		// Throw an error if configuration could not load
		throw new Exception('Could not load a configuration file');
	}
} else {
	// Load the specified configuration
	if (!$todo->loadConfig(dirname(__FILE__) . '/config/' . $args->options['config'] . '.config')) {
		throw new Exception('Could not load configuration file: "' . $args->options['config']. '"');
	}
}

// Display the header
$todo->outputHeader();

// Add files
$todo->addFiles($args->args['files']);

// Run parser
$todo->parse();

// Print the results
$todo->outputResults();


// ----------------------------------------------------------------------
// -- Command line functions
// ----------------------------------------------------------------------

/**
 * Sets up and parses the command line.
 * @return array Parsed command line results.
 */ 
function get_commandline_args()
{
    
	// Create parser
	$parser = new Console_CommandLine(array(
		'description' => 'Scan a file or files for todo items.',
		'version'     => '0.1.0'
	));
    
	// Verbose mode prints stats
	$parser->addOption(
		'verbose',
		array(
			'short_name'  => '-v',
			'long_name'   => '--verbose',
			'action'      => 'StoreTrue',
			'description' => 'Turn on verbose output (such as stats)'
		)
	);
		
	// External configuration
	$parser->addOption(
		'config',
		array(
			'short_name'  => '-c',
			'long_name'   => '--config',
			'action'      => 'StoreString',
			'description' => 'Load options from config file'
		)
	);

	// Skip formatting?
	$parser->addOption(
		'format',
		array(
			'short_name'  => '-f',
			'long_name'   => '--format',
			'action'      => 'StoreTrue',
			'description' => 'Turn on colour formatting'
		)
	);

	// Recurse directories
	$parser->addOption(
		'recurse',
		array(
			'short_name'  => '-r',
			'long_name'   => '--recurse',
			'action'      => 'StoreTrue',
			'description' => 'Recurse directories when scanning'
		)
	);

	// add the files argument, the user can specify one or several files
	$parser->addArgument(
		'files',
		array(
			'multiple' => true,
			'description' => 'list of files or directories to scan'
		)
	);

	// Run parser and return result
	try {
		return $parser->parse();
	} catch (Exception $exc) {
		$parser->displayError($exc->getMessage());
	}

}

