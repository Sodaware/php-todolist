<?php
/**
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
 * 
 * PHP version 5.2
 *
 * @author    Phil Newton <phil@sodaware.net>
 * @copyright 2011 Phil Newton
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   1.0.0
 * @link      http://www.sodaware.net/dev/tools/php-todolist/
 */

// TODO: Rename methods to camel case

// Optional PEAR libraries
include_once 'Console/Color.php';


/**
 * Scans source files for TODO style tags in comments, and presents them
 * in an easy to read list.
 *  
 * @author   Phil Newton <phil@sodaware.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD
 * @version  Release: 1.0.0.0
 * @link     http://www.sodaware.net/dev/tools/php-todo/
 */
class ToDoParser
{
	// Array of priorities to colour codes
	private static $_priorities	= array('', '%R', '%r', '%Y', '%y', '%G', '%g');
	
	protected $_options   = array();
	protected $_files     = array();
	protected $_items     = array();
	protected $_fileTypes = array();
	protected $_patterns  = array();

	protected $_scannedFilesCount	= 0;


	// ----------------------------------------------------------------------
	// -- Creation
	// ----------------------------------------------------------------------

	/**
	 * Constructor
	 * @param array $options Optional array of key, value pairs for the options.
	 */
	function __construct($options = null)
	{
		// TODO: Check for allowed options
		if ($options) {
			$this->_options	= $options;
		}

	}


	// ----------------------------------------------------------------------
	// -- Public queries
	// ----------------------------------------------------------------------

	/**
	 * Count the number of registered search patterns.
	 * @return int The number of search patterns.
	 */
	function countPatterns()  {
		return count($this->_patterns);
	}

	function countFileTypes() {
		return count($this->_fileTypes);
	}


	// ----------------------------------------------------------------------
	// -- Setup functions
	// ----------------------------------------------------------------------

	/**
	 * Add a TODO marker pattern with an optional priority.
	 * @param string pattern The pattern to search for.
	 * @param int priority Optional priority for this pattern. Lower number is higher priority.
	 * @return bool True if pattern was added, false if not.
	 */
	function addPattern($pattern, $priority = 1)
	{
		if (!$pattern)       { return false; }

		// Clamp values
		if ( $priority < 1 ) { $priority = 1; }
		if ( $priority > 7 ) { $priority = 7; }
		
		$this->_patterns[$pattern]	= (int)$priority;
		return true;
	}

	/**
	 * Add a list of files for the parser to scan.
	 * @param array files An array of file names to add.
	 */
	function addFiles($files)
	{
		if($files == null) { return false; }
		
		if(is_array($files)) {
			
			if(count($files) < 1) { return false; }
			
			$success = false;			
			foreach ($files as $file) { 
				$success = ($success & $this->AddFile($file));
			}
			return $success;
		} else { // For backwards compatibility
			return $this->addFile($file);
		}
	}
	
	/**
	 * Add a file for the parser to scan.
	 * @param string Name of the file to add.
	 */
	function addFile($fileName)
	{
		if(!file_exists($fileName) ) { return false; }
		$this->_files[] = $fileName;
		return true;
	}

	/**
	 * Add a list of file extensions to the parser.
	 * @param mixed $types Either an array or a single file extension string.
	 */
	function addFileTypes($types)
	{
		if(is_array($types)) {
			$this->_fileTypes = array_merge($this->_fileTypes, $types);
		} else {
			$this->_fileTypes[] = $types;
		}
	}
	/** 
	 * Loads an XML configuration file.
	 * @param string $fileName The name of the config file to load.
	 * @return bool True if loaded, false if not.
	 */
	function loadConfig($fileName)
	{
		
		// Check inputs	
		if(!file_exists($fileName)) { return false; }	
		
		// Load and parse
		$configXml	= @simplexml_load_file($fileName);
		if(!$configXml) { return false; } 
		
		// Add file types
		foreach ($configXml->fileTypes->fileType as $fileType) {
			$this->addFileTypes((string)$fileType);
		}
		
		// Add patterns
		foreach ($configXml->tasks->task as $task) {
			$this->addPattern((string)$task['pattern'], (string)$task['priority']);
		}
		
		return true;
	}


	// ----------------------------------------------------------------------
	// -- Output functions
	// ----------------------------------------------------------------------

	/**
	 * Output the header for this application
	 */
	function outputHeader()
	{
		if(isset($this->_options['queit']) && !$this->_options['quiet']) {
			ToDoParser::echoc('%gTo-Do List Scanner ' . $this->_options['version'] . 
				"\n%Bhttp://sodaware.net/dev/\n%n\n");
		}
	}

	/**
	 * Output all of the todo items found
	 * @param bool $verbose If true, will output statistics
	 * @param array $items Array of files and their todo items.
	 */
	function outputResults()
	{
		// Print usage if no args passed
		if(count($this->_files) < 1) { 
			$this->outputUsage();
			return;
		}

	    // Stats
	    $filesWithTasksCount    = 0;
	    $todoCount              = 0;
		$lineCount				= 0;

		foreach ($this->_items as $fileName => $fileInfo) {

			$lineCount += $fileInfo['lines'];

			if(is_array($fileInfo['tasks']) && count($fileInfo['tasks'])) {
				$filesWithTasksCount++;
				
				$this->echoc('%W' . $fileName . "%n\n");
				
				foreach ($fileInfo['tasks'] as $task) {
					$todoCount++;
					printf("  [%4d, %3d] ", $task['row'], $task['col']);
					$this->echoc(ToDoParser::$_priorities[$task['priority']] . $task['todo'] . "%n\n");
				}
				
				echo "\n";
			}

		}

		// Do stats (if required)
		if($this->_options['verbose']) {
			$this->echoc("%WFiles Scanned     : %n{$this->_scannedFilesCount}%n\n");
			$this->echoc("%WFiles With Tasks  : %n$filesWithTasksCount%n\n");
			$this->echoc("%WLines Scanned     : %n$lineCount%n\n");

			if($todoCount > 0) {
				$this->echoc("%WTask Line Density : %n" . round($lineCount / $todoCount, 2) . "%n\n");
				$this->echoc("%WTask File Density : %n" . round(count($this->_files) / $todoCount, 2) . "%n\n");
			}

			$this->echoc("%WTotal Tasks       : %n$todoCount%n\n");
		}

	}

	/**
	 * Outputs application usage instructions
	 */
	function outputUsage()
	{
		echo "Usage: todo [directory] [FILE]\n";
		echo "Outputs list of todo items in a file or directory\n";
	}

	
	// ----------------------------------------------------------------------
	// -- Scanning functions
	// ----------------------------------------------------------------------

	/**
	 * Parse all files and store results.
	 */
	function parse()
	{
		if(count($this->_files) < 1) { 
			echo "no files found";
			return;
		}

		foreach ($this->_files as $fileToScan) {
			$tasks = array();
			
			switch(filetype($fileToScan)) {
				
				case 'dir':  
					$tasks = $this->scanDir($fileToScan, $this->_options['recurse']);
					break;
				
				case 'file': 
					$tasks = $this->scanFile($fileToScan);
					break;
			}
			
			if (is_array($tasks)) {
				$this->_items = array_merge($this->_items, $tasks);
			} else {
				$this->outputHeader();
				echo "No files found\n";
			}
		}
	}

	function scanDir($path, $recurse = false)
	{
		$items	= array();
		
		if ($handle = opendir($path))
		{
			
			while (false !== ($file = readdir($handle)))
			{
				$nextpath = $path . '/' . $file;
				
				if ($file != '.' && $file != '..' && $file != '.svn' && $file != '.output' && !is_link ($nextpath))
				{
					if (is_dir($nextpath) && $recurse == true)
					{
						$newItems = $this->scanDir($nextpath, $recurse);
						if(is_array($newItems)) {  $items = array_merge($items, $newItems); }
					}
					elseif (is_file ($nextpath))
					{
						$newItems = $this->scanFile($nextpath);
						if(is_array($newItems)) { $items = array_merge($items, $newItems); }
					}
				}
			}
			
			closedir($handle);
			
		}
		return $items;
	}

	/**
	 * Scans a file for task instances.
	 * @param string $srcFile The source file to scan
	 * @return array An associative array of filename -> file info array
	 */
	function scanFile($srcFile)
	{
		$items = array();

		// Check this file can be scanned
		if(in_array(ToDoParser::file_ext($srcFile), $this->_fileTypes)) {

			$this->_scannedFilesCount++;

			// Load contents into array + scan each line
			$contents = file($srcFile);
			$line = 0;
			foreach ($contents as $currentLine) {

				$line++;

				// Check for patterns
				foreach ($this->_patterns as $pattern => $priority) {
						
					$pos = strpos($currentLine, $pattern);
						
					if($pos !== false) {

						$items[] = array(
							'row'       => $line,
							'col'       => $pos,
							'todo'      => trim(substr($currentLine, $pos + strlen($pattern))),
							'priority'  => $priority
						);
						
					}
				}
			}

			$fileInfo = array(
				'lines' => count($contents),
				'tasks'	=> $items
			);

			return array($srcFile => $fileInfo);

		}
		
	}
	
	
	// ----------------------------------------------------------------------
	// -- Helper functions
	// ----------------------------------------------------------------------

	/**
	 * Gets a file extension.
	 * @param string fileName File name to get extension for.
	 * @return string The file's extension. 
	 */
	public static function file_ext($fileName) 
	{
    	$info = pathinfo($fileName);
    	return $info['extension'];
	}

	/**
	 * Outputs a string with colour formatting.
	 * @param string $text The text to output
	 */
	public function echoc($text)
	{
		// Strip colour codes if PEAR module not present
		if (!class_exists('Console_Color')) {
			echo ToDoParser::strip($text);
		}

		// Convert to ANSI codes
		$newText = Console_Color::convert($text);

		// Strip ANSI colour codes for Windows or if formatting disabled
		if (IS_WINDOWS || !$this->_options['format']) {
			$newText = Console_Color::strip($newText);
		}

		echo $newText;
	}

	/**
	 * Strips colour codes from a string.
	 * @param string $text The string to strip.
	 * @return string Cleaned string.
	 */
	public static function strip($text)
	{
		// Allowed codes

		return preg_replace('/%[ygbrpmcwknYGBRPMCWKN0123456789FU_]/', '', $text);
	}

}
