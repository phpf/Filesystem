<?php

namespace Phpf\Filesystem;

use Phpf\Util\Path;

class Filesystem {
	
	public static $scan_depth = 10;
	
	protected $path;
	
	protected $groups = array();
	
	protected $working_group;
	
	protected $scans = array();
	
	protected static $instances = array();
	
	public static function instance( $id ){
		if ( !isset(self::$instances[$id]) )
			self::$instances[$id] = new self( $id );
		return self::$instances[$id];
	}
	
	public static function newInstance( $id, $dirpath ){
			
		if ( isset(self::$instances[$id]) ){
			throw new \LogicException("Filesystem instance with id '$id' already exists.");
		}
		
		$instance = self::instance($id);
		
		$instance->setPath($dirpath);
		
		return $instance;
	}
	
	protected function __construct( $id ){
		$this->id = $id;
	}
	
	public function setPath( $path ){
		$this->path = Path::normalize($path);
		return $this;
	}
	
	public function getPath(){
		return $this->path;
	}
	
	public static function setScanDepth( $depth ){
		self::$scan_depth = (int) $depth;
	}
	
	public function scan( $group = null, $force_rescan = false ){
		
		if ( isset($this->working_group) ){
			$group = $this->working_group;
		} elseif ( !isset($group) ){
			throw new \RuntimeException("Must set group via parameter or working group to scan.");
		}
		
		if ( !isset($this->groups[$group]) ){
			throw new \RuntimeException("Unknown filesystem group $group.");
		}
		
		if ( isset($this->files[$group]) && !$force_rescan ){
			return $this->files[$group];
		}
		
		$scan = array();
		
		foreach( $this->groups[$group] as $path){
			glob_deep($path, self::$scan_depth, $scan);
		}
		
		return $this->files[$group] = $scan;
	}
	
	public function locate( $file, $group = null ){
		
		if ( isset($this->working_group) ){
			$group = $this->working_group;
		}
		
		if ( isset($this->found[$group][$file]) ){
			return $this->found[$group][$file];
		}
		
		foreach( $this->scan($group) as $item ){
			if ( false !== strpos($item, $file) ){
				return $this->found[$group][$file] = $item;
			}
		}
		
		return null;
	}
	
	public function add( $path, $group = null ){
		
		if ( isset($this->working_group) ){
			$group = $this->working_group;
		}
		
		if ( !isset($group) ){
			throw new \RuntimeException("Must set group or working group to add directory.");
		}
		
		$path = Path::normalize($path);
		
		if ( !isset($this->groups[$group]) ){
			$this->groups[$group] = array();
		}
		
		$this->groups[$group][$path] = $path;
		
		return $this;
	}
	
	/**
	 * Set the current working group.
	 */
	public function setWorkingGroup( $group ){
		$this->working_group = $group;
		return $this;
	}
	
	/**
	 * Get the current working group.
	 */
	public function getWorkingGroup(){
		return isset($this->working_group) ? $this->working_group : null;
	}
	
	/**
	 * Reset the current working group.
	 */
	public function resetWorkingGroup(){
		unset($this->working_group);
		return $this;
	}
	
}
