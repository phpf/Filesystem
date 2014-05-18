<?php

namespace Phpf\Filesystem;

use RuntimeException;
use InvalidArgumentException;

class Filesystem
{
	
	/**
	 * Base filesystem path.
	 * @var string
	 */
	protected $basepath;
	
	/**
	 * Group names and paths.
	 * @var array
	 */
	protected $groups = array();
	
	/**
	 * Globs of directories.
	 * @var array
	 */
	protected $globs = array();
	
	/**
	 * Located files.
	 * @var array
	 */
	protected $found = array();
	
	/**
	 * Current working group.
	 * @var string|null
	 */
	protected $working_group;
	
	/**
	 * Default recursion depth.
	 * @var int
	 */
	protected $default_search_depth = 3;
	
	/**
	 * Default recursion depths for specific groups.
	 * @var array
	 */
	protected $group_default_depths = array();
	
	/**
	 * DIRECTORY_SEPARATOR alias
	 * @var string
	 */
	protected $ds;
	
	/**
	 * Sets the base path.
	 * 
	 * @param string $path Base filesystem path.
	 */
	public function __construct($path) {
		$this->basepath = $this->cleanpath($path);
		$this->ds = DIRECTORY_SEPARATOR;
	}
	
	/**
	 * Adds a directory path to a group.
	 * 
	 * @param string $path Directory path.
	 * @param string $group Group name to add dirpath to.
	 * @param int $depth Maximum recursion depth to use for this path. Default is 3.
	 * @return $this
	 */
	public function add($path, $group = null, $depth = null) {

		if (isset($this->working_group)) {
			$group = $this->working_group;
		} else if (! isset($group)) {
			throw new RuntimeException("Must set group or working group to add directory.");
		}

		if (! isset($this->groups[$group])) {
			$this->groups[$group] = array();
		}
		
		if (! isset($depth)) {
			if (isset($this->group_default_depths[$group])) {
				$depth = $this->group_default_depths[$group];
			} else {
				$depth = $this->default_search_depth;
			}
		}

		$this->groups[$group][$this->cleanpath($path)] = $depth;

		return $this;
	}
	
	/**
	 * Attempts to locate a file in a given group's directories.
	 * 
	 * @param string $file File name to find (with or without extension).
	 * @param string $group Group name of directories to search within.
	 * @return string|null Filepath if found, otherwise null.
	 */
	public function locate($file, $group = null) {

		if (isset($this->working_group)) {
			$group = $this->working_group;
		} else if (! isset($group)) {
			throw new RuntimeException("Must set group or working group to locate file.");
		}
		
		if (isset($this->found[$group][$file])) {
			return $this->found[$group][$file];
		}

		if (! isset($this->groups[$group])) {
			throw new InvalidArgumentException("Unknown filesystem group $group.");
		}

		foreach ( $this->groups[$group] as $path => $depth ) {
			
			if ($found = $this->search($path, $file, $depth)) {
				return $this->found[$group][$file] = $found;
			}
		}
		
		return null;
	}
	
	/**
	 * Searches for a file by globbing recursively until the file is found,
	 * or until the maximum recusion depth is reached.
	 * 
	 * @param string $dir Path to the directory to search within.
	 * @param string $file File name to find (with or without extension).
	 * @param int $depth Maximum recursion depth for this search.
	 * @param int $depth_now Used interally.
	 * @return string|null Filepath if found, otherwise null.
	 */
	public function search($dir, $file, $depth = null, $depth_now = 0) {
		
		if (! isset($depth)) {
			$depth = $this->default_search_depth;
		}
		
		foreach( $this->glob($dir) as $item ) {
			
			if (false !== strpos($item, $file)) {
			
				// found file
				return $item;
			
			} else if ($depth_now < $depth && $this->ds === substr($item, -1)) {
				
				// recurse some more
				if ($found = $this->search($item, $file, $depth, $depth_now+1)) {
					
					// found in subdir
					return $found;
				}
			}
		}
		
		return null;
	}
	
	/**
	 * Gets a glob of a directory.
	 * 
	 * @param string $dir Directory path to glob.
	 * @return array Glob of directory.
	 */
	public function glob($dir) {
			
		$dir = $this->cleanpath($dir);
		
		if (isset($this->globs[$dir])) {
			return $this->globs[$dir];
		}
		
		return $this->globs[$dir] = glob($dir.'/*', GLOB_MARK|GLOB_NOSORT|GLOB_NOESCAPE);
	}
	
	/**
	 * Sets the recursive search depth for a given group.
	 * 
	 * @param string $group Name of the group.
	 * @param int $depth Default depth to search dirs recursively.
	 * @return $this
	 */
	public function setGroupDefaultSearchDepth($group, $depth) {
		$this->group_default_depths[$group] = (int) $depth;
		return $this;
	}
	
	/**
	 * Sets the recursive search depth to use when none is set.
	 * 
	 * @param int $depth Default depth to search dirs recursively.
	 * @return $this
	 */
	public function setDefaultSearchDepth($depth) {
		$this->default_search_depth = (int) $depth;
		return $this;
	}
	
	/**
	 * Set the current working group. Allows you to omit the 'group'
	 * parameter in add() and locate().
	 * 
	 * @param string $group Group name.
	 * @return $this
	 */
	public function setWorkingGroup($group) {
		$this->working_group = $group;
		return $this;
	}

	/**
	 * Get the current working group.
	 * 
	 * @return string|null Group name, if set, otherwise null.
	 */
	public function getWorkingGroup() {
		return isset($this->working_group) ? $this->working_group : null;
	}

	/**
	 * Reset the current working group.
	 * 
	 * @return $this
	 */
	public function resetWorkingGroup() {
		unset($this->working_group);
		return $this;
	}
	
	/**
	 * Returns the basepath.
	 * 
	 * @return string Basepath of this Filesystem instance.
	 */
	public function getBasepath() {
		return $this->basepath;
	}
	
	/**
	 * Normalizes a file path by converting backslashes to forward
	 * slashes and removing trailing slashes.
	 */
	public function cleanpath($path) {
		return rtrim(str_replace('\\', '/', $path), '/');
	}
	
}
