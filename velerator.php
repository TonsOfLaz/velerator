<?php

class Velerator {

	private $full_app_path;
	private $velerator_directory;

	private $project_name;
	private $project_files;
	private $project_config_file;
	private $project_config_array;

	public function __construct($argv) {
		// Up to 3 arguments passed to this file
		// 1: project name
		// 2: project config file
		// 3: extra command (clear)

		// Set up the global variables or exit
		if (!isset($argv[1]) || !isset($argv[2])) {
			echo "Please add a project name and genfile, i.e.: \nphp Velerator.php myproject mygenfile.txt";
			exit();
		}

		$extra_command = "";
		if (isset($argv[3])) {
			$extra_command = $argv[3];
		}

		$this->project_name = $argv[1];
		$this->project_config_file = $argv[2];
		$this->velerator_directory = getcwd();
		$this->full_app_path = $this->velerator_directory."/".$this->project_name;
		
		// Optional files for views
		$this->project_files = "";
		$project_file_root = explode(".", $this->project_file)[0];
		if (is_dir($project_file_root."_files")) {
			$this->project_files = $this->velerator_directory."/".$project_file_root."_files";
		}

		$this->createProjectArray();

		$this->velerateProject();

	}

	// ====================================> Procedural Functions

	public function velerateProject() {

		echo "full_app_path = "			.$this->full_app_path."\n";
		echo "velerator_directory = "	.$this->velerator_directory."\n";
		echo "project_name = "			.$this->project_name."\n";
		echo "project_files = "			.$this->project_files."\n";
		echo "project_config_file = "	.$this->project_config_file."\n";

		
	}

	// ====================================> Helper Functions

	public function createProjectArray() {
		$filetext = file_get_contents("./".$this->project_config_file);
		$delimiter = "\n";
		$splitcontents = explode($delimiter, $filetext);
		$finalarray = [];
		$tabcount = 0;
		$currhead = "";
		$currsubhead = "";

		foreach ( $splitcontents as $line ) {
		    $bits = explode("\t", $line);
		    $tabcount = count($bits);
		    $tabtext = $bits[$tabcount - 1];
		    //echo $tabcount." $tabtext\n";
		    if ($tabtext != '') {
			    if ($tabcount == 1) {
			    	
			    	$finalarray[$tabtext] = [];
			    	$currhead = $tabtext;

			    } else if ($tabcount == 2) {
			    	$finalarray[$currhead][$tabtext] = [];
			    	$currsubhead = $tabtext;

			    } else if ($tabcount == 3) {
			    	// End subarray
			    	$finalarray[$currhead][$currsubhead][] = $tabtext;
			    }
			}
		}
		//print_r($finalarray);
		$this->project_array = $finalarray;
	}

}
new Velerator($argv);