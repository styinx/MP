<?php
    class MediaPlayer
    {
        private $media_list = array();
        private $types = array();

        public function __construct($types = array("mp3"))
        {
            $this->types = $types;
        }

        public function loadMedia($path = "")
        {
            if(file_exists($path))
            {
                require_once(dirname(__FILE__) . "/../include/dir/directory.php");
                $dir = new DIR($path);
                $this->media_list = $dir->getFilesRecursivly($path, $this->types);
            }
        }

        public function getMedia()
        {
            return $this->media_list;
        }
    }
?>