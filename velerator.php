<?php

class Velerator {

	private $full_app_path;
	private $velerator_path;

	private $project_name;
	private $project_files;
	private $project_config_file;
	private $project_config_array;

	private $brand_new_install;
	private $extra_command;

	public function __construct($argv) {
		// Up to 3 arguments passed to this file
		// 1: project name
		// 2: project config file
		// 3: extra command (clear)

		$this->initializeClassVariablesOrExit($argv);

		echo "full_app_path = "			.$this->full_app_path."\n";
		echo "velerator_path = "		.$this->velerator_path."\n";
		echo "project_name = "			.$this->project_name."\n";
		echo "project_files = "			.$this->project_files."\n";
		echo "project_config_file = "	.$this->project_config_file."\n";

		if ($this->appDirectoryDoesntExist() || $this->extra_command == "clean") {
			$this->brand_new_install = true;
			$this->buildFreshLaravelInstallWithPackages();
		} else {
			$this->revertToExistingLaravelInstall();
		}


	}


// ==============================================================================
// ==============================================================================
	public function initializeClassVariablesOrExit($argv) {
		$this->brand_new_install = false;
		if (!isset($argv[1]) || !isset($argv[2])) {
			echo "Please add a project name and genfile, i.e.: \nphp Velerator.php myproject mygenfile.txt";
			exit();
		}

		$this->extra_command = "";
		if (isset($argv[3])) {
			$this->extra_command = $argv[3];
		}

		$this->project_name = $argv[1];
		$this->project_config_file = $argv[2];
		$this->velerator_path = getcwd();
		$this->full_app_path = $this->velerator_path."/".$this->project_name;
		
		// Optional files for views
		$this->project_files = "";
		$project_file_root = explode(".", $this->project_file)[0];
		if (is_dir($project_file_root."_files")) {
			$this->project_files = $this->velerator_path."/".$project_file_root."_files";
		}
		$this->createProjectArray();
	}

	// ==========================================================
	// ====================================> Helper Functions

	public function appDirectoryDoesntExist() {
		return !is_dir($this->full_app_path);
	}

	public function buildFreshLaravelInstallWithPackages() {

		echo "Creating Laravel project...\n";
		//shell_exec("laravel new ".$projectname);
		shell_exec("composer --no-interaction create-project laravel/laravel ".$this->project_name." dev-develop");
		echo "Laravel project ".$this->project_name." created.\n";

		// ===================================> ADD COMPOSER TOOLS
		chdir($this->full_app_path);
		if (isset($this->project_config_array['FAKEDATA'])) {
			
			echo "Adding 'Faker' tool...\n";
			shell_exec("composer require fzaninotto/faker");
		}
		foreach ($project_config_array['PACKAGES'] as $name => $package_settings) {
			echo "Adding '$name' tool...\n";
			shell_exec("composer require $name");
			foreach ($package_settings as $key => $type_value) {
				$type_value_arr = explode("|", $type_value);
				$type = trim($type_value_arr[0]);
				$value = trim($type_value_arr[1]);
				switch ($type) {
					case "provider":
						//addProvider($value);
						break;
					case "alias":
						//addFacade($value);
						break;
				}
			}
		}
	}

	public function revertToExistingLaravelInstall() {
		// Instead of creating a new project, we just reset git to a fresh project install
		// This speeds up running it and stops tasking the composer servers
		echo "Reverting to a clean install...\n";
		chdir($this->full_app_path);
		shell_exec("git stash");

		$resp = shell_exec("git log --pretty=format:'%h' --reverse | head -1");
		shell_exec("git reset --hard $resp");
		shell_exec("git clean -fd");
	}

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
		$this->project_config_array = $finalarray;
	}

}
new Velerator($argv);