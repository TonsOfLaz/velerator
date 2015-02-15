<?php

class Velerator {

	private $full_app_path;
	private $velerator_path;
	private $project_name;
	private $project_files;
	private $project_config_file;
	private $project_config_array;
	private $singular_models;
	private $brand_new_install;
	private $extra_command;

	public function __construct($argv) {
		// 1: project name
		// 2: project config file
		// 3: extra command (clear)

		$this->initializeClassVariablesOrExit($argv);

		if ($this->extra_command == "clear") {
			$this->clearExistingAppDirectory();
		}

		if ($this->appDirectoryDoesntExist()) {
			$this->brand_new_install = true;
			
			//$this->buildFreshLaravelInstallWithPackages();
			shell_exec("laravel new ".$this->project_name);
			chdir($this->full_app_path);
			shell_exec("git init");
			shell_exec("git checkout --orphan velerator_fresh_install");
			shell_exec("git add .");
			shell_exec("git commit -am 'Fresh Laravel install, first commit.'");
			shell_exec("git branch velerator_fresh_install_packages");
			shell_exec("git checkout velerator_fresh_install_packages");
			$this->installPackages();
			shell_exec("git add .");
			shell_exec("git commit -am 'Packages installed.'");
		} else {
			//$this->revertToExistingLaravelInstall();
			chdir($this->full_app_path);
			shell_exec("git rebase --onto velerator_fresh_install_packages velerator_generated master");
			shell_exec("git branch -D velerator_generated");
			shell_exec("git checkout velerator_fresh_install_packages");
		}
		shell_exec("git branch velerator_generated");
		shell_exec("git checkout velerator_generated");

		//print_r($this->project_config_array);
		$this->velerateDBMODELS();
		$this->velerateRELATIONSHIPS();

		shell_exec("git add .");
		shell_exec("git commit -am 'Velerator files generated ".time()."'");

		if ($this->brand_new_install) {
			shell_exec("git branch master");
		}

		shell_exec("git rebase --onto velerator_generated velerator_fresh_install_packages master");

		print_r($this->singular_models);
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
		$this->singular_models = [];
		$this->singular_models['users'] = "User";
		$this->velerator_path = getcwd();
		$this->full_app_path = $this->velerator_path."/".$this->project_name;
		
		// Optional files for views
		$this->project_files = "";
		$project_file_root = explode(".", $this->project_config_file)[0];
		if (is_dir($project_file_root."_files")) {
			$this->project_files = $this->velerator_path."/".$project_file_root."_files";
		}
		$this->createProjectArray();

		echo "full_app_path = "			.$this->full_app_path."\n";
		echo "velerator_path = "		.$this->velerator_path."\n";
		echo "project_name = "			.$this->project_name."\n";
		echo "project_files = "			.$this->project_files."\n";
		echo "project_config_file = "	.$this->project_config_file."\n";

	}

	// ==========================================================
	// ====================================> Helper Functions

	public function appDirectoryDoesntExist() {
		return !is_dir($this->full_app_path);
	}
	public function clearExistingAppDirectory() {
		echo "Clearing existing directory...\n";
		shell_exec("rm -rf ".$this->full_app_path);
		echo "Done deleting ".$this->project_name."\n";
	}

	public function installPackages() {

		// ===================================> ADD COMPOSER TOOLS
		chdir($this->full_app_path);
		if (isset($this->project_config_array['FAKEDATA'])) {
			
			echo "Adding 'Faker' tool...\n";
			shell_exec("composer require fzaninotto/faker");
		}
		foreach ($this->project_config_array['PACKAGES'] as $name => $package_settings) {
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

	public function velerateDBMODELS() {
		$fillable_array = [];
		
		foreach ($this->project_config_array['DBMODELS'] as $object_and_singular => $fields_arr) {
			$obj_sin_arr = explode(" ", $object_and_singular);
			$object = $obj_sin_arr[0];
			$singular = $obj_sin_arr[1];
			$this->singular_models[$object] = $singular;
			$fillable_array[$singular] = "";
			// Create Migration
			shell_exec("php artisan make:migration --create=$object create_".$object."_table");
			
			$allfields_arr = [];
			$once = 1;
			foreach ($fields_arr as $field_str) {
				$fieldtype = "string";  // Default is string if blank
				$fieldfunction = "";
				$secondarytable = "";
				$thisfield_arr = explode(" ", $field_str);

				$fieldname = $thisfield_arr[0];
				if (substr($fieldname,0,1) == '*') {
					// Ones with * need their own tables
					continue;
				} else {
					if ($once) {
						$fillable_array[$singular] .= "'$fieldname'";
						$once = 0;
					} else {
						$fillable_array[$singular] .= ",'$fieldname'";
					}
					
					if (strpos($fieldname, "_id") > 0) {
						$fieldtype = "integer";
						if (count($thisfield_arr) == 2) {
							$secondarytable = $thisfield_arr[1];
						}
					} else if (strpos($fieldname, "_date") > 0) {
						$fieldtype = "date";
					} else if (count($thisfield_arr) == 2) {
						$fieldtype = $thisfield_arr[1];
					} else if (count($thisfield_arr) == 3) {
						$fieldtype = $thisfield_arr[1];
						$fieldfunction = $thisfield_arr[2];
					}
					$tempfield_arr = [];
					$tempfield_arr['name'] = $fieldname;
					$tempfield_arr['type'] = $fieldtype;
					$tempfield_arr['function'] = $fieldfunction;
					$tempfield_arr['secondary'] = $secondarytable;
					$allfields_arr[] = $tempfield_arr;
				}
				
			}
			// Update Schema file
			$this->addFieldArrayToCreateSchema($object, $allfields_arr);
		}
		// ===================================> CREATING MODELS

		foreach ($this->singular_models as $object => $singular) {
			// Create Model
			$generic_model = file_get_contents($this->velerator_path."/velerator_files/Model.php");
			$newmodel = str_replace("[NAME]", $singular, $generic_model);
			$newmodel = str_replace("[TABLE]", $object, $newmodel);
			$newmodel = str_replace("[FILLABLE_ARRAY]", "[".$fillable_array[$singular]."]", $newmodel);
			$newmodel = str_replace("[HIDDEN_ARRAY]", '[]', $newmodel);
			file_put_contents($this->full_app_path."/app/".$singular.".php", $newmodel);
		}
	}

	function addFieldArrayToCreateSchema($edit_table, $field_array) {
		$str = "";
		foreach ($field_array as $field_vals) {
			
			$type = $field_vals['type'];
			$name = $field_vals['name'];
			$secondarytable = $field_vals['secondary'];
			$after_function = "";
			if ($function = $field_vals['function']) {
				$after_function = "->$function()";
			}

			if ($type == 'integer') {
				$type = 'unsignedInteger';
			}

			$str .= '$table'."->$type('$name')$after_function;
			";

			if ($secondarytable) {
				$str .= '$table'."->foreign('$name')
				->references('id')->on('$secondarytable');
	      	";
			}
		}
		$glob_arr = glob($this->full_app_path."/database/migrations/*_create_".$edit_table."_table.php");
		if (count($glob_arr) > 0) {
			$filename = $glob_arr[0];
			$startmigration = file_get_contents($filename);
			$oldstr = '$table->timestamps();';
			$newfile = str_replace($oldstr, $str.$oldstr, $startmigration);
			file_put_contents($filename, $newfile);
		}
	}

	public function velerateRELATIONSHIPS() {
		// Pivot tables and Model relationships
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