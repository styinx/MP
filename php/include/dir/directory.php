<?php
	class DIR
	{
		private $currentPath 	= ".";
		private $dirHandle		= null;

		public function __construct($path = ".")
		{
			$this->currentPath = $path;
		}

		public function open($path)
		{
			$this->dirHandle = opendir($path);
			return $this->dirHandle;
		}

		public function getFilesRecursivly($path, $filter)
		{
			$files = array();
			$h = $this->open($path);

			$files = array_merge($files, $this->getFilesFromPathWithFilter($path, $filter));

			while($handle = readdir($h))
			{
				if(is_dir($path . "/" . $handle) && $handle != "." && $handle != "..")
				{
					$files = array_merge($files, $this->getFilesRecursivly($path . "/" . $handle, $filter));
				}
			}
			$this->close($h);
			return $files;
		}

		public function getFilesFromPathWithFilter($path, $filter)
		{
			$files = array();

			$allFiles = $this->getFilesFromPath($path);

			foreach($allFiles as $file)
			{
				if(in_array(substr($file, strrpos($file, '.') + 1), $filter))
				{
					array_push($files, $file);
				}
			}
			return $files;
		}

		public function getFilesFromPath($path)
		{
			$files = array();
			$h = $this->open($path);
			while($handle = readdir($h))
			{
				if(!is_dir($path . "/" . $handle))
				{
					array_push($files, $path . "/" . $handle);
				}
			}
			$this->close($h);
			return $files;
		}

		public function getFoldersFromPath($path)
		{
			$folders = array();
			$h = $this->open($path);
			while($handle = readdir($h))
			{
				if(is_dir($path . "/" . $handle))
				{
					array_push($folders, $path . "/" . $handle);
				}
			}
			$this->close($h);
			return $folders;
		}

		public function getFileLineCount($file)
		{
			$i = 0;
		}

		public function close($handle)
		{
			return closedir($handle);
		}
	}
?>
