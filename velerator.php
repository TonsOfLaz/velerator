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
	private $schema;
	private $listqueries;
	private $nestedroutes;
	private $is_api;
	private $demopage_array;

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
			shell_exec("git stash");
			shell_exec("git rebase --onto velerator_fresh_install_packages velerator_generated master");
			shell_exec("git branch -D velerator_generated");
			shell_exec("git checkout velerator_fresh_install_packages");
		}
		shell_exec("git branch velerator_generated");
		shell_exec("git checkout velerator_generated");

		$this->initializeDatabase();
		$this->gitAddAndCommitWithMessage("Initialized Database.");
		shell_exec("cp ".$this->velerator_path."/velerator_files/.env ".$this->full_app_path."/.env");
		shell_exec("cp ".$this->velerator_path."/velerator_files/.gitignore ".$this->full_app_path."/.gitignore");
		
		if (!$this->is_api) {
			$this->bringInFoundationCSS();
			$this->gitAddAndCommitWithMessage("Added Foundation CSS.");
			$this->velerateNAVIGATION();
			$this->gitAddAndCommitWithMessage("Added NAVIGATION.");
		}
		$this->velerateDBMODELS();
		$this->gitAddAndCommitWithMessage("Added DBMODELS and routes.");
		$this->velerateLINKTEXT();
		$this->gitAddAndCommitWithMessage("Added LINKTEXT to models.");
		$this->velerateRELATIONSHIPS();
		$this->gitAddAndCommitWithMessage("Added Model RELATIONSHIPS and pivot tables.");
		$this->velerateCOMMANDS();
		$this->gitAddAndCommitWithMessage("Added COMMANDS.");
		$this->velerateMODELQUERIES();
		$this->gitAddAndCommitWithMessage("Added custom MODELQUERIES.");
		$this->velerateLISTQUERIES();
		$this->gitAddAndCommitWithMessage("Added custom LISTQUERIES.");
		$this->velerateROUTECONTROLLERS();
		$this->gitAddAndCommitWithMessage("Added Model Resource routes and controllers.");
		$this->velerateROUTEVIEWS();
		$this->gitAddAndCommitWithMessage("Added Model default Route views.");
		$this->addModelLinks();
		$this->gitAddAndCommitWithMessage("Added Model Links.");
		$this->addModelDetails();
		$this->gitAddAndCommitWithMessage("Added Model Details or view Tabs.");
		$this->velerateROUTES();
		$this->gitAddAndCommitWithMessage("Added Custom ROUTES.");
		$this->velerateNestedRoutes();
		$this->gitAddAndCommitWithMessage("Added Nested Routes.");
		$this->velerateListParameterFilters();
		$this->gitAddAndCommitWithMessage("Added list parameter filters.");
		shell_exec("php artisan migrate");
		$this->runRealDataSeeders();
		$this->gitAddAndCommitWithMessage("Added real data seeder files.");
		$this->runFakeDataSeeders();
		$this->gitAddAndCommitWithMessage("Added fake data seeder files.");
		$this->createMasterSeederAndRun();
		$this->gitAddAndCommitWithMessage("Added master seeder and ran all seeders.");
		$this->createDemoPage();
		$this->gitAddAndCommitWithMessage("Added Demo page.");

		//print_r($this->nestedroutes);

		shell_exec("git add .");
		shell_exec("git commit -am 'Velerator files generated ".time()."'");

		if ($this->brand_new_install) {
			shell_exec("git branch master");
		}

		shell_exec("git rebase --onto velerator_generated velerator_fresh_install_packages master");

		//print_r($this->singular_models);
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
		$users_default_schema = [];
		$users_default_schema = ['name' 		=> 'string',
								'email' 		=> 'string',
								'password'		=> 'string',
								'remember_token' => 'string'];
		$this->schema['users'] = $users_default_schema;
		$this->velerator_path = getcwd();
		$this->full_app_path = $this->velerator_path."/".$this->project_name;
		
		$this->listqueries = [];
		$this->nestedroutes = [];
		$this->form_models_array = [];
		$this->demopage_array = [];
		$this->demopage_array["APP"] = ucwords($this->project_name);
		$this->demopage_array["FILE"] = $this->project_config_file;
		
		// Optional files for views
		$this->project_files = "";
		$project_file_root = explode(".", $this->project_config_file)[0];
		if (is_dir($project_file_root."_files")) {
			$this->project_files = $this->velerator_path."/".$project_file_root."_files";
		}
		$this->createProjectArray();

		if (isset($this->project_config_array["API"])) {
			$this->is_api = true;
		} else {
			$this->is_api = false;
		}

		echo "full_app_path = "			.$this->full_app_path."\n";
		echo "project_config_file = "	.$this->project_config_file."\n";

	}
	public function gitAddAndCommitWithMessage($message) {
		echo $message."\n";
		shell_exec("git add .");
		shell_exec("git commit -am '$message'");
	}

	public function initializeDatabase() {
		shell_exec("touch ".$this->full_app_path."/storage/database.sqlite");
		$local_db_config = file_get_contents($this->full_app_path."/config/database.php");
		/*
		$new_db_config = str_replace("'host'      => 'localhost'", "'host'      => ".'$_ENV'."['DB_HOST']", $local_db_config);
		$new_db_config = str_replace("'database'  => 'forge'", "'database'  => ".'$_ENV'."['DB_DATABASE']", $new_db_config);
		$new_db_config = str_replace("'username'  => 'forge'", "'username'  => ".'$_ENV'."['DB_USERNAME']", $new_db_config);
		$new_db_config = str_replace("'password'  => ''", "'password'  => ".'$_ENV'."['DB_PASSWORD']", $new_db_config);
		*/

		$new_db_config = str_replace("'default' => 'mysql',", "'default' => 'sqlite',", $local_db_config);
		file_put_contents($this->full_app_path."/config/database.php", $new_db_config);
	}
	public function bringInFoundationCSS() {
		mkdir($this->full_app_path."/public/css/foundation");
		shell_exec("cp ".$this->velerator_path."/velerator_files/foundation/css/*.css ".$this->full_app_path."/public/css/foundation");
		mkdir($this->full_app_path."/public/js");
		mkdir($this->full_app_path."/public/js/foundation");
		shell_exec("cp -R ".$this->velerator_path."/velerator_files/foundation/js/* ".$this->full_app_path."/public/js");
		$appview = file_get_contents($this->velerator_path."/velerator_files/views/app.blade.php");
		
		$replacestr_css = "[CSS]";
		$newappview = str_replace($replacestr_css, "
		<link href=\"/css/foundation/foundation.min.css\" rel=\"stylesheet\">", $appview);

		$replacestr_js = '[JS]';
		$new_js = '
		<script src="/js/foundation.min.js"></script>';
		$newappview = str_replace($replacestr_js, $new_js, $newappview);
		file_put_contents($this->full_app_path."/resources/views/app.blade.php", $newappview);

	}
	public function velerateNAVIGATION() {
		// Any new paths you add here should go into ROUTES as well
		$bothstr = "";
		$authstr = "";
		$gueststr = "";
		mkdir($this->full_app_path."/resources/views/partials");
		$navview = file_get_contents($this->velerator_path."/velerator_files/views/navigation.blade.php");
		$navview = str_replace("[APPNAME]", ucwords($this->project_name), $navview);
		if (isset($this->project_config_array['NAVIGATION'])) {
			foreach ($this->project_config_array['NAVIGATION'] as $filter => $nav_list) {
				foreach ($nav_list as $value) {
					$val_arr = explode("|", $value);
					$text = trim($val_arr[0]);
					$link = trim($val_arr[1]);
					$navstr = "
					<li><a href='/$link'>$text</a></li>";
					switch ($filter) {
						case 'Auth':
							$authstr .= $navstr;
							break;
						case 'Both':
							$bothstr .= $navstr;
							break;
						case 'Guest':
							$gueststr .= $navstr;
							break;
					}
				}
			}
		}
		$new_navview = str_replace("[BOTH]", $bothstr, $navview);
		$new_navview = str_replace("[GUEST]", $gueststr, $new_navview);
		$new_navview = str_replace("[AUTH]", $authstr, $new_navview);
		file_put_contents($this->full_app_path."/resources/views/partials/navigation.blade.php", $new_navview);
	}
	public function addPathToRoutesControllersAndViews($path) {
		// add to routes.php
		// add to controller file
		// create view if it doesnt exist
	}
	public function runRealDataSeeders() {

		$baseseeder = file_get_contents($this->velerator_path."/velerator_files/database/TableSeeder.php");
		if (isset($this->project_config_array['SEEDREAL'])) {
			foreach ($this->project_config_array['SEEDREAL'] as $table_and_fields => $inserts) {
				$table_arr = explode('|', $table_and_fields);
				$table = trim($table_arr[0]);
				$model = $this->singular_models[$table];
				$fields_str = trim($table_arr[1]);
				$fields_arr = array_map('trim', explode(',', $fields_str));
				$realseed_str = "";
		
				foreach ($inserts as $row) {
					$realseed_str .= "
		$model::create([";
					$data_arr = array_map('trim', explode('|', $row));
					foreach ($fields_arr as $fieldkey => $fieldname) {
						$fielddata = $data_arr[$fieldkey];
						$realseed_str .= "
			'$fieldname' => '$fielddata',";
					}
					$realseed_str .= "
		]);";
				}
				
				$newseeder = str_replace('[NAME]', $model, $baseseeder);
				$newseeder = str_replace('// [REAL]', $realseed_str, $newseeder);
				file_put_contents($this->full_app_path."/database/seeds/".$model."TableSeeder.php", $newseeder);

			}
		}

	}
	public function runFakeDataSeeders() {
		echo "Creating fake data seeders...\n";
		
		if (isset($this->project_config_array['SEEDFAKE'])) {

			$fakebase_str = '$faker = Faker\Factory::create();

        foreach(range(1,[COUNT]) as $index)  
        {  
            [NAME]::create([  
                [FAKE_ARRAY]
            ]);  
        }';
        	$fakebase_pivot_str = '$faker = Faker\Factory::create();

        $date = new \DateTime;
        foreach(range(1,[COUNT]) as $index)  
        {  
            DB::table(\'[PIVOTTABLE]\')->insert([ 
                [FAKE_ARRAY]
                \'created_at\' => $date,
                \'updated_at\' => $date,
            ]);  
        }';

			foreach ($this->project_config_array['SEEDFAKE'] as $object_and_count => $fields) {
				$obj_cnt_arr = explode(" ", $object_and_count);
				$object = $obj_cnt_arr[0];
				//echo $object."\n";
				$fakerstr = "";
				$count = $obj_cnt_arr[1];

				foreach ($fields as $field_and_fakergen) {
					$fakergen_arr = explode("|", $field_and_fakergen, 2);
					$faker_field = trim($fakergen_arr[0]);
					$faker_gen = trim($fakergen_arr[1]);
					$fakerstr .= "'$faker_field' => ".'$faker->'.$faker_gen.",
				";
				}
				if (isset($this->singular_models[$object])) {
					$singular = $this->singular_models[$object];
				}
				if (strpos($object, "_") > 0) {
					// this is a pivot table
					$pivot_table = $object;
					$pivot_name = str_replace("_", " ", $object);
					$pivot_name = str_replace(" ", "", ucwords($pivot_name));
					$singular = $pivot_name;
					$baseseeder = file_get_contents($this->velerator_path."/velerator_files/database/PivotTableSeeder.php");
					$newseeder = str_replace('[PIVOTNAME]', $pivot_name, $baseseeder);
					$newseeder = str_replace('[PIVOTTABLE]', $pivot_table, $newseeder);
					$newfakebase = str_replace('[PIVOTNAME]', $singular, $fakebase_pivot_str);

				} else {
					$baseseeder = file_get_contents($this->velerator_path."/velerator_files/database/TableSeeder.php");
					$newseeder = str_replace('[NAME]', $singular, $baseseeder);
					$newfakebase = str_replace('[NAME]', $singular, $fakebase_str);
				}
				$newfakebase = str_replace('[COUNT]', $count, $newfakebase);
				$newfakebase = str_replace('[FAKE_ARRAY]', $fakerstr, $newfakebase);
				
				$newseeder = str_replace('// [FAKE]', $newfakebase, $newseeder);

				file_put_contents($this->full_app_path."/database/seeds/".$singular."TableSeeder.php", $newseeder);
			}
		}

	}
	function createMasterSeederAndRun() {
		$seeded_models = [];
		$table_seeder_calls = "";
		if (isset($this->project_config_array['SEEDREAL'])) {
			foreach ($this->project_config_array['SEEDREAL'] as $table_and_fields => $inserts) {
				$table_arr = explode('|', $table_and_fields);
				$table = trim($table_arr[0]);
				$model = $this->singular_models[$table];
				$seeded_models[$model] = 1;
			}
		}
		if (isset($this->project_config_array['SEEDFAKE'])) {
			foreach ($this->project_config_array['SEEDFAKE'] as $object_and_count => $fields) {
				$obj_cnt_arr = explode(" ", $object_and_count);
				$table = $obj_cnt_arr[0];
				if (strpos($table, "_") > 0) {
					// this is a pivot table
					$pivot_table = $table;
					$pivot_name = str_replace("_", " ", $table);
					$pivot_name = str_replace(" ", "", ucwords($pivot_name));
					$model = $pivot_name;
				} else {
					$model = $this->singular_models[$table];
				}
				
				$seeded_models[$model] = 1;
			}
		}
		if (count($seeded_models) > 1) {
			foreach ($seeded_models as $model => $dud) {
				$table_seeder_calls .= '$this->call('."'".$model."TableSeeder');
		";
			}
		}

		$database_seeder_master = file_get_contents($this->full_app_path."/database/seeds/DatabaseSeeder.php");
		$replacestr = '// $this'."->call('UserTableSeeder');";
		$new_dbseed_master = str_replace($replacestr, $table_seeder_calls, $database_seeder_master);
		file_put_contents($this->full_app_path."/database/seeds/DatabaseSeeder.php", $new_dbseed_master);


		shell_exec("composer dump-autoload");

		echo "Running seeders...\n";
		shell_exec("php artisan db:seed");
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

		shell_exec("composer require laravelcollective/html");
		$this->addProvider("Collective\Html\HtmlServiceProvider");
		$this->addAlias("Form", "Collective\Html\FormFacade");
      	$this->addAlias("Html", "Collective\Html\HtmlFacade");
		
		if (isset($this->project_config_array['SEEDFAKE'])) {
			
			echo "Adding 'Faker' tool...\n";
			shell_exec("composer require fzaninotto/faker");
		}
		if (isset($this->project_config_array['PACKAGES'])) {
			foreach ($this->project_config_array['PACKAGES'] as $name => $package_settings) {
				echo "Adding '$name' tool...\n";
				shell_exec("composer require $name");
				foreach ($package_settings as $key => $type_value) {
					$type_value_arr = explode("|", $type_value);
					$type = trim($type_value_arr[0]);
					$value = trim($type_value_arr[1]);
					switch ($type) {
						case "provider":
							$this->addProvider($value);
							break;
						case "alias":
							$value_arr = explode(" ", $value);
							$name = $value_arr[0];
							$path = $value_arr[1];
							$this->addAlias($name, $path);
							break;
					}
				}
			}
		}
	}

	public function velerateDBMODELS() {
		$fillable_array = [];
		
		// Schema

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
						if (count($thisfield_arr) == 3) {
							$secondarytable = $thisfield_arr[1];
							$fieldfunction = $thisfield_arr[2];
						}
					} else if (strpos($fieldname, "_date") > 0) {
						$fieldtype = "date";
					} else if (count($thisfield_arr) == 2) {
						$fieldtype = $thisfield_arr[1];
					} else if (count($thisfield_arr) == 3) {
						$fieldtype = $thisfield_arr[1];
						$fieldfunction = $thisfield_arr[2];
					}
					if (!$fieldfunction) {
						$fieldfunction = "nullable";
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
			if ($singular != "User") {
				$generic_model = file_get_contents($this->velerator_path."/velerator_files/Model.php");
				$newmodel = str_replace("[NAME]", $singular, $generic_model);
				$newmodel = str_replace("[TABLE]", $object, $newmodel);
				$newmodel = str_replace("[FILLABLE_ARRAY]", "[".$fillable_array[$singular]."]", $newmodel);
				$newmodel = str_replace("[HIDDEN_ARRAY]", '[]', $newmodel);
				file_put_contents($this->full_app_path."/app/".$singular.".php", $newmodel);
			}
		}
	}
	function velerateLINKTEXT() {

		foreach ($this->singular_models as $table => $singular) {
			$newstr = "";
			$replacestr = "";
			$functioncontent = "";
			$singular_lower = strtolower($singular);
			if (isset($this->project_config_array['LINKTEXT'][$singular])) {
				$nameguide = $this->project_config_array['LINKTEXT'][$singular][0];

				preg_match_all('|\$([a-zA-Z_0-9]*)|', $nameguide, $matches);
				$varstr = "";
				foreach ($matches[1] as $varname) {
					$varstr .= '$'.$varname.' = $this->'.$varname.";
		";
				}
				$functioncontent = $varstr.'return "'.$nameguide.'";';

			} else {
				
				if (isset($this->schema[$table]['name']) || $singular == 'User') {
					$functioncontent = 'return $this->name;';
				} else {
					$functioncontent = 'return "'.$singular.' $this->id";';
				}

			}
			$newstr .= "
	public function getLinkTextAttribute() 
	{
		$functioncontent
	}";
			$modelfile = file_get_contents($this->full_app_path."/app/$singular.php");
			if ($singular == "User") {
				$replacestr = "protected ".'$hidden'." = ['password', 'remember_token'];";
			} else {
				$replacestr = "protected ".'$hidden'." = [];";
			}
			$newmodelfile = str_replace($replacestr, $replacestr.$newstr, $modelfile);
			file_put_contents($this->full_app_path."/app/$singular.php", $newmodelfile);
		}
	}

	function addFieldArrayToCreateSchema($edit_table, $field_array) {
		$str = "";
		foreach ($field_array as $field_vals) {
			
			$type = $field_vals['type'];
			$name = $field_vals['name'];
			$secondarytable = $field_vals['secondary'];
			$after_function = "";
			if (isset($field_vals['function'])) {
				if ($function = $field_vals['function']) {
					$after_function = "->$function()";
				}
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
			$this->schema[$edit_table][$name] = $type;
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
		$pivot_tables = [];
		$morph_tables = [];

		foreach ($this->singular_models as $table => $model) {
			$function_str = "";
			if (isset($this->project_config_array['RELATIONSHIPS'][$model])) {
				$relationships = $this->project_config_array['RELATIONSHIPS'][$model];
			} else {
				$relationships = [];
			}
			foreach ($relationships as $relationship_str) {
				$relationship_arr = explode(" ", trim($relationship_str));
				$relationship_type = $relationship_arr[0];
				$relationship_model = $relationship_arr[1];
				$relationship_field = "";
				$function_name = "";
				if (count($relationship_arr) == 4) {
					$function_name = $relationship_arr[2];
					$relationship_field = $relationship_arr[3];
				}

				//echo $relationship_type;
				switch ($relationship_type) {
					case 'hasOne':
						if (!$function_name) {
							$function_name = strtolower($relationship_model);
						}
						$linked_field = $relationship_field ? $relationship_field : strtolower($relationship_model)."_id";
						$this->form_models_array[$model]['hasone'][$linked_field] = $relationship_model;
						$function_str .= $this->getRelationshipFunction($function_name, 
																		$relationship_model, 
																		$relationship_type,
																		$relationship_field);
						break;
					case 'belongsTo':
						$this->nestedroutes[$model][$relationship_model] = 1;	
						$linked_field = $relationship_field ? $relationship_field : strtolower($relationship_model)."_id";
						$this->form_models_array[$model]['belongsto'][$linked_field] = $relationship_model;
						if (!$function_name) {
							$function_name = strtolower($relationship_model);
						}
						$function_str .= $this->getRelationshipFunction($function_name, 
																		$relationship_model, 
																		$relationship_type,
																		$relationship_field);
						break;
					case 'belongsToThrough':
						if (!$function_name) {
							$function_name = strtolower($relationship_model);
						}
						$through = $relationship_arr[2];
						$function_str .= $this->getRelationshipFunctionBelongsToThrough($function_name, 
																		$relationship_model, 
																		$through);
						break;
					case 'hasMany':
						if (!$function_name) {
							$function_name = $this->getTableFromModelName($relationship_model);
						}
						$function_str .= $this->getRelationshipFunction($function_name, 
																		$relationship_model, 
																		$relationship_type,
																		$relationship_field);
						
						break;
					case 'belongsToMany':
						if (!$function_name) {
							$function_name = $this->getTableFromModelName($relationship_model);
						}
						$this->form_models_array[$model]['belongstomany'][$relationship_model] = 1;
						$function_str .= $this->getRelationshipFunction($function_name, 
																		$relationship_model, 
																		$relationship_type,
																		$relationship_field);
						$function_str .= $this->getRelationshipListFunction($function_name, $relationship_model);
						if (strcmp($relationship_model, $model) < 0) {
							$pivot_tables[$relationship_model."_".$model] = 1;
						} else {
							$pivot_tables[$model."_".$relationship_model] = 1;
						}
						break;
					case 'hasManyThrough':
						break;
					case 'morphTo':
						break;
					case 'morphMany':
						break;
					case 'morphToMany':

						$relationship_field = $relationship_arr[2];
						$morph_tables[$relationship_field] = $relationship_model;
						$this->form_models_array[$model]['morphtomany'][$relationship_model] = 1;
						if (!$function_name) {
							$function_name = $this->getTableFromModelName($relationship_model);
						}
						$function_str .= $this->getRelationshipFunction($function_name, 
																		$relationship_model, 
																		$relationship_type,
																		$relationship_field);
						
						break;
					case 'morphedByMany':
						//print_r($relationship_arr);
						$relationship_field = $relationship_arr[2];
						$morph_tables[$relationship_field] = $model;
						if (!$function_name) {
							$function_name = $this->getTableFromModelName($relationship_model);
						}
						$function_str .= $this->getRelationshipFunction($function_name, 
																		$relationship_model, 
																		$relationship_type,
																		$relationship_field);
						
						break;
						
				}

			}
			$model_file = file_get_contents($this->full_app_path."/app/".$model.".php");

			if ($model == "User") {
				$lastfunc = "protected ".'$hidden'." = ['password', 'remember_token'];";
				$new_model_file = str_replace($lastfunc, $lastfunc."
	".$function_str, $model_file);
			} else {
				$new_model_file = str_replace("[RELATIONSHIPS]", $function_str, $model_file);
			}
			file_put_contents($this->full_app_path."/app/".$model.".php", $new_model_file);
			
			

		}
		//print_r($pivot_tables);
		foreach ($pivot_tables as $pivot_table => $dud) {
			$pivot_table_name = strtolower($pivot_table);
			shell_exec("php artisan make:migration --create=$pivot_table_name create_".$pivot_table_name."_table");
			$pivot_arr = explode("_", $pivot_table);
			$model_one = $pivot_arr[0];
			$model_two = $pivot_arr[1];
			$primary_field = strtolower($model_one)."_id";
			$secondary_field = strtolower($model_two)."_id";
			$primary_arr = [];
			$primary_arr['name'] = $primary_field;
			$primary_arr['type'] = "integer";
			$primary_arr['secondary'] = $this->getTableFromModelName($model_one);
			$secondary_arr = [];
			$secondary_arr['name'] = $secondary_field;
			$secondary_arr['type'] = "integer";
			$secondary_arr['secondary'] = $this->getTableFromModelName($model_two);
			$linkfields_arr = [];
			$linkfields_arr[] = $primary_arr;
			$linkfields_arr[] = $secondary_arr;
			$this->addFieldArrayToCreateSchema($pivot_table_name, $linkfields_arr);
		}
		foreach($morph_tables as $morph_singular => $model) {
			$morph_table = $morph_singular."s";
			shell_exec("php artisan make:migration --create=$morph_table create_".$morph_table."_table");
			$morph_id_arr = [	"name" 		=> strtolower($model)."_id",
								"type" 		=> "integer",
								"secondary" => $this->getTableFromModelName($model)];
			$morphable_id_arr = [	"name" 		=> $morph_singular."_id",
									"type" 		=> "integer",
									"secondary" => ''];
			$morphable_type_arr = [	"name" 		=> $morph_singular."_type",
									"type" 		=> "string",
									"secondary" => ''];
			$morphable_table_fields = [];
			$morphable_table_fields[] = $morph_id_arr;
			$morphable_table_fields[] = $morphable_id_arr;
			$morphable_table_fields[] = $morphable_type_arr;

			$this->addFieldArrayToCreateSchema($morph_table, $morphable_table_fields);

		}
	}
	public function addCreateAndEditForm($table, $model) {
		//print_r($this->form_models_array);
		//exit();
		$newcontroller = file_get_contents($this->full_app_path."/app/Http/Controllers/".ucwords($table)."Controller.php");

		// First make sure the controllers load the right data
		$create_str = "";
		$edit_str = "";
		$update_str = "";
		$store_str = "";
		if (isset($this->form_models_array[$model]['belongsto']) ||
			isset($this->form_models_array[$model]['hasone']) || 
			isset($this->form_models_array[$model]['belongstomany']) ||
			isset($this->form_models_array[$model]['morphtomany'])) {

			$return_str = "return view('".$table.".create', compact(";
			$usemodel_str = 'use App\\'.$model.';';
			$once = true;
			if (isset($this->form_models_array[$model]['belongsto'])) {
				foreach ($this->form_models_array[$model]['belongsto'] as $fieldname => $related_model) {
					$addmodel_str = 'use App\\'.$related_model.';';
					if (strpos($newcontroller, $addmodel_str) < 1 && strpos($usemodel_str, $addmodel_str) < 1) {
						$usemodel_str .= "
$addmodel_str";
					}
					$related_table = $this->getTableFromModelName($related_model);
					$create_str .= '$'.$related_table."_collection = $related_model::all();
		$".$related_table." = ['' => ''];
		foreach ($".$related_table."_collection as $".strtolower($related_model).") {
			$".$related_table."[$".strtolower($related_model)."->id] = $".strtolower($related_model)."->link_text;
		}
		";
					if ($once) {
						$return_str .= "'".$related_table."'";
						$once = false;
					} else {
						$return_str .= ", '".$related_table."'";
					}
				}
			}
			if (isset($this->form_models_array[$model]['hasone'])) {
				foreach ($this->form_models_array[$model]['hasone'] as $fieldname => $related_model) {
					$addmodel_str = 'use App\\'.$related_model.';';
					if (strpos($newcontroller, $addmodel_str) < 1 && strpos($usemodel_str, $addmodel_str) < 1) {
						$usemodel_str .= "
$addmodel_str";
					}
					$related_table = $this->getTableFromModelName($related_model);
					$create_str .= '$'.$related_table."_collection = $related_model::all();
		$".$related_table." = ['' => ''];
		foreach ($".$related_table."_collection as $".strtolower($related_model).") {
			$".$related_table."[$".strtolower($related_model)."->id] = $".strtolower($related_model)."->link_text;
		}
		";
					if ($once) {
						$return_str .= "'".$related_table."'";
						$once = false;
					} else {
						$return_str .= ", '".$related_table."'";
					}
				}
			}
			if (isset($this->form_models_array[$model]['belongstomany'])) {
				foreach ($this->form_models_array[$model]['belongstomany'] as $related_model => $dud) {
					$addmodel_str = 'use App\\'.$related_model.';';
					if (strpos($newcontroller, $addmodel_str) < 1 && strpos($usemodel_str, $addmodel_str) < 1) {
						$usemodel_str .= "
$addmodel_str";
					}
					$related_table = $this->getTableFromModelName($related_model);
					$related_list = strtolower($related_model)."_list";
					$update_str .= '$'.strtolower($model).'->'.$related_table.'()->sync($request->input("'.$related_list.'"));
		';
					$store_str .= '$'.strtolower($model).'->'.$related_table.'()->attach($request->input("'.$related_list.'"));
		';
					$create_str .= '$'.$related_table."_collection = $related_model::all();
		$".$related_table." = [];
		foreach ($".$related_table."_collection as $".strtolower($related_model).") {
			$".$related_table."[$".strtolower($related_model)."->id] = $".strtolower($related_model)."->link_text;
		}
		";
					if ($once) {
						$return_str .= "'".$related_table."'";
						$once = false;
					} else {
						$return_str .= ", '".$related_table."'";
					}
				}
			}

			if (isset($this->form_models_array[$model]['morphtomany'])) {
				//echo $model;
				foreach ($this->form_models_array[$model]['morphtomany'] as $related_model => $dud) {
					$addmodel_str = 'use App\\'.$related_model.';';
					if (strpos($newcontroller, $addmodel_str) < 1 && strpos($usemodel_str, $addmodel_str) < 1) {
						$usemodel_str .= "
$addmodel_str";
					}
					$related_table = $this->getTableFromModelName($related_model);
					$related_list = strtolower($related_model)."_list";
					$update_str .= '$'.strtolower($model).'->'.$related_table.'()->sync($request->input("'.$related_list.'"));
		';
					$store_str .= '$'.strtolower($model).'->'.$related_table.'()->attach($request->input("'.$related_list.'"));
		';
					$create_str .= '$'.$related_table."_collection = $related_model::all();
		$".$related_table." = [];
		foreach ($".$related_table."_collection as $".strtolower($related_model).") {
			$".$related_table."[$".strtolower($related_model)."->id] = $".strtolower($related_model)."->link_text;
		}
		";
					if ($once) {
						$return_str .= "'".$related_table."'";
						$once = false;
					} else {
						$return_str .= ", '".$related_table."'";
					}
				}
			}
			$return_str .= "));";

			$singular_lower = strtolower($model);
			$replacestr = 'return view("'.$table.'.create");';
			$newstr = $create_str.$return_str;
			$newcontroller = str_replace($replacestr, $newstr, $newcontroller);

			$replace_edit = "return view('$table.edit', compact('$singular_lower'));";
			$edit_str = str_replace("create", "edit", $newstr);
			$edit_str = str_replace("compact(", "compact('$singular_lower', ", $edit_str);
			$newcontroller = str_replace($replace_edit, $edit_str, $newcontroller);
			$newcontroller = str_replace('use App\\'.$model.';', $usemodel_str, $newcontroller);
		}
		$newcontroller = str_replace("[MANYTOMANY_UPDATE]", $update_str, $newcontroller);
		$newcontroller = str_replace("[MANYTOMANY_STORE]", $store_str, $newcontroller);
		
		file_put_contents($this->full_app_path."/app/Http/Controllers/".ucwords($table)."Controller.php", $newcontroller);
	
		// Now add the view code
		
		// First the fields in the schema
		$singular_lower = strtolower($model);
		$viewpath = $table.'/form';
		$formfields = "";
		//print_r($this->schema);
		foreach ($this->schema[$table] as $fieldname => $fieldtype) {
			$inputtype = "";
			$formfields .= "<div class='row'>
	<div class='small-12 columns'>";
			switch($fieldtype) {
				case 'string':
					$formfields .= "
		{!! Form::label('$fieldname', '".ucwords($fieldname)."') !!}
		{!! Form::text('$fieldname') !!}
";
					break;
				case 'text':
					$formfields .= "
		{!! Form::label('$fieldname', '".ucwords($fieldname)."') !!}
		{!! Form::textarea('$fieldname') !!}
";
					break;
				case 'boolean':
					$formfields .= "
		{!! Form::label('$fieldname', '".ucwords($fieldname)."') !!}
		{!! Form::checkbox('$fieldname') !!}
";
					break;
				case 'unsignedInteger':
					if (isset($this->form_models_array[$model]['belongsto'][$fieldname])) {
						$related_model = $this->form_models_array[$model]['belongsto'][$fieldname];
						$related_table = $this->getTableFromModelName($related_model);
						$formfields .= "
		{!! Form::label('$fieldname', '$related_model') !!}
		{!! Form::select('$fieldname', $".$related_table.", null, []) !!}
";
					} else if (isset($this->form_models_array[$model]['hasone'][$fieldname])) {
						$related_model = $this->form_models_array[$model]['hasone'][$fieldname];
						$related_table = $this->getTableFromModelName($related_model);
						$formfields .= "
		{!! Form::label('$fieldname', '$related_model') !!}
		{!! Form::select('$fieldname', $".$related_table.", null, []) !!}
";
					} else {
						$formfields .= "
		{!! Form::label('$fieldname', '".ucwords($fieldname)."') !!}
		{!! Form::text('$fieldname') !!}
";
					}
					
					break;
				default:
					$inputtype = '';
			}
			$formfields .= "</div>
</div>";
			
			
		}

		// Then the many to many, since they are not in the schema
		if (isset($this->form_models_array[$model]['belongstomany'])) {
			foreach ($this->form_models_array[$model]['belongstomany'] as $related_model => $dud) {
				$related_list = strtolower($related_model)."_list";
				$related_table = $this->getTableFromModelName($related_model);
				$related_caps = ucwords($related_table);
				$formfields .= "<div class='row'>
	<div class='small-12 columns'>
		{!! Form::label('$related_list', '$related_caps') !!}
		{!! Form::select('$related_list"."[]"."', $".$related_table.", null, ['multiple' => 'multiple']) !!}
	</div>
</div>";
			}
		}

		// Then the many to many MORPH tables, since they are not in the schema
		if (isset($this->form_models_array[$model]['morphtomany'])) {
			foreach ($this->form_models_array[$model]['morphtomany'] as $related_model => $dud) {
				$related_list = strtolower($related_model)."_list";
				$related_table = $this->getTableFromModelName($related_model);
				$related_caps = ucwords($related_table);
				$formfields .= "<div class='row'>
	<div class='small-12 columns'>
		{!! Form::label('$related_list', '$related_caps') !!}
		{!! Form::select('$related_list"."[]"."', $".$related_table.", null, ['multiple' => 'multiple']) !!}
	</div>
</div>";
			}
		}

		$formfields .= "
<div class='row'>
	<div class='small-12 columns'>
		{!! Form::submit($"."submit_text".", ['class' => 'button expand']) !!}
	</div>
</div>";
		$formfields .= "
@section('footer')
	<script>
		$('select[multiple]').select2();
	</script>
@endsection";
		$standard_create_form = file_get_contents($this->velerator_path."/velerator_files/views/create.blade.php");
		$new_create_form = str_replace("[MODEL]", $model, $standard_create_form);
		$new_create_form = str_replace("[TABLE]", $table, $new_create_form);
		file_put_contents($this->full_app_path."/resources/views/$table/create.blade.php", $new_create_form);

		$singular_lower = strtolower($model);
		$standard_edit_form = file_get_contents($this->velerator_path."/velerator_files/views/edit.blade.php");
		$new_edit_form = str_replace("[MODEL]", $model, $standard_edit_form);
		$new_edit_form = str_replace("[TABLE]", $table, $new_edit_form);
		$new_edit_form = str_replace("[SINGULAR_VARIABLE]", $singular_lower, $new_edit_form);
		file_put_contents($this->full_app_path."/resources/views/$table/edit.blade.php", $new_edit_form);

		file_put_contents($this->full_app_path."/resources/views/$table/form.blade.php", $formfields);
		//exit();
	}

	public function velerateROUTES() {
		$controller_arr = [];
		$routestr = "";
		$routefile = file_get_contents($this->full_app_path.'/app/Http/routes.php');
		if (isset($this->project_config_array['ROUTES'])) {
			foreach ($this->project_config_array['ROUTES'] as $routebase => $routes) {
				$routebase_arr = explode(" ", trim($routebase));
				if (count($routebase_arr) > 1) {
					$controller = $routebase_arr[1];
				} else {
					$controller = ucwords($routebase)."Controller";
				}

				if (isset($this->singular_models[$routebase])) {
					
					$singular = $this->singular_models[$routebase];
					$singular_lower = strtolower($singular);
					foreach ($routes as $route) {
						$dependency_injection = "";
						$route_arr = explode(" ", trim($route));
						if (count($route_arr) > 1) {
							$str = $route_arr[1];
							$finalroute = "$routebase/".$route_arr[0];
							$this->demopage_array['ROUTES']['GET'][$singular][$finalroute] = "Custom route defined in ".$this->project_config_file." ROUTES, view and controller function are named ".$route_arr[1];
						} else {
							if (strpos("a".$route, "?") > 0) {
								$dependency_injection = "$singular ".'$'."$singular_lower";
							}
							$str = str_replace("?", "", $route);
							$str = str_replace("/", " ", $str);
							$str = ucwords($str);
					        $str = str_replace(" ", "", $str);
					        $str = lcfirst($str);
					        $idroute = str_replace("?", '{id}', $route);
					        $route = str_replace("?", '{'.$singular_lower.'}', $route);
					        $this->demopage_array['ROUTES']['GET'][$singular]["$routebase/$idroute"] = "Custom route defined in ".$this->project_config_file." ROUTES";
							$finalroute = "$routebase/$route";
						}
						
						$routestr .= "
Route::get('$finalroute', '".$controller."@".$str."');";
						$temparr = [];
						$temparr['function'] = $str."($dependency_injection)";
						$compact = "";
						if ($dependency_injection) {
							$compact = "compact('$singular_lower')";
						} 
						if ($this->is_api) {
							if (!$compact) {
								if (isset($this->listqueries[$singular][$str])) {
									$temparr['body'] = "return $singular::$str()->get();";
								} else {
									$temparr['body'] = "return '$str';";	
								}
								
							} else {
								$temparr['body'] = "return $compact;";
							}
							
						} else {
							if ($dependency_injection) {
								$compact = ", ".$compact;
							}
							$temparr['body'] = "return view('$routebase.$str'$compact);";
						}
						$controller_arr[$controller][] = $temparr;
					
					}
					
				} else {
					$routestr .= "
Route::get('".$routebase_arr[0]."', '".$controller."@".$routebase_arr[0]."');";
					$temparr = [];
					$temparr['function'] = $routebase_arr[0]."()";
					$temparr['body'] = "return '".$routebase_arr[0]."';";
					$controller_arr[$controller][] = $temparr;
				}
				
			}
		}
		$replacestr = "Route::get('home', 'HomeController@index');";
		$newfile = str_replace($replacestr, $replacestr.$routestr, $routefile);
		file_put_contents($this->full_app_path.'/app/Http/routes.php', $newfile);
		//print_r($controller_arr);
		// Create functions in controllers as well
		foreach ($controller_arr as $controller => $functions) {
			foreach ($functions as $function) {
				$function_name = $function['function'];
				$function_body = $function['body'];
				$controller_file_path = $this->full_app_path."/app/Http/Controllers/$controller.php";
				if (!file_exists($controller_file_path)) {
					shell_exec("php artisan make:controller $controller");
				}
				$replacestr = "extends Controller {";
				$controller_file = file_get_contents($controller_file_path);
				$newfile = str_replace($replacestr, $replacestr."

	public function $function_name
	{
		$function_body
	}", $controller_file);
				file_put_contents($controller_file_path, $newfile);
			}
		}
		foreach ($this->listqueries as $model => $function_names) {
			foreach ($function_names as $function_name => $function_type) {

				$tablename = $this->getTableFromModelName($model);
				$this->demopage_array["MODELS"][$tablename]['listqueries'][] = $function_name;
				$this->demopage_array['ROUTES']['GET'][$model][$tablename."?scope=".$function_name] = "List Query: $function_name ".ucwords($tablename);
				$controllerpath = $this->full_app_path."/app/Http/Controllers/".ucwords($tablename)."Controller.php";
				$controllerfile = file_get_contents($controllerpath);
				// replaces the plain controller code, from LISTQUERIES
				$replace_func = 'switch ($scope) {';
				$new_func = "
			case '$function_name':
				$"."scope_function = '$function_name';
				break;";
				$newcontrollerfile = str_replace($replace_func, $replace_func.$new_func, $controllerfile);
				file_put_contents($controllerpath, $newcontrollerfile);
				// replaces the plain view code, from LISTQUERIES
				$viewpath = $this->full_app_path."/resources/views/$tablename/$function_name.blade.php";
				if (!file_exists($viewpath)) {
					$title = ucwords($tablename)." ".$function_name;
					$viewcontent = "@extends('$tablename.list')
@section('title')
$title
@endsection";
					file_put_contents($viewpath, $viewcontent);
				}
				$viewfile = file_get_contents($viewpath);
				$replace_view = "@section('content')
$tablename $function_name
@endsection";
				$newview = "@section('content')
	<ul>
	@foreach ($".$tablename." as $".strtolower($model).") 
		<li><a href='/$tablename/{{ $".strtolower($model)."->id }}'>{{ $".strtolower($model)."->link_text }}</a></li>
	@endforeach
	</ul>
@endsection";
				$newviewfile = str_replace($replace_view, $newview, $viewfile);
				file_put_contents($viewpath, $newviewfile);
			}
		}
	}


	public function velerateROUTECONTROLLERS() {
		$oldroutes = file_get_contents($this->full_app_path."/app/Http/routes.php");

		$routestr = "";
		$controllerstr = "";
		foreach ($this->singular_models as $table => $singular) {
			$singular_lower = strtolower($singular);
			$capstable = ucfirst($table);
			shell_exec("php artisan make:controller ".$capstable."Controller");

			$thiscontroller = file_get_contents($this->full_app_path."/app/Http/Controllers/".$capstable."Controller.php");
			$newcontroller = str_replace("use App\Http\Controllers\Controller;", 'use App\Http\Controllers\Controller;
use App\\'.$singular.";", $thiscontroller);
			$newcontroller = str_replace('$id', $singular.' $'.$singular_lower, $newcontroller);
			$newcontroller = str_replace('index()', 'index(Request $request)', $newcontroller);
			$newcontroller = str_replace('store()', 'store(Request $request)', $newcontroller);
			$newcontroller = str_replace('update(', 'update(Request $request, ', $newcontroller);
			file_put_contents($this->full_app_path."/app/Http/Controllers/".$capstable."Controller.php", $newcontroller);

			// Include resource routes
			$routestr .= "Route::model('$table', 'App\\$singular');\n";
			$routestr .= "Route::resource('$table', '$capstable"."Controller');\n";
			$this->demopage_array['ROUTES']['GET'][$singular][$table] = "See all $capstable.";
			$this->demopage_array['ROUTES']['GET'][$singular][$table."/create"] = "Create a new $singular";
			$this->demopage_array['ROUTES']['GET'][$singular][$table."/{id}"] = "View one $singular";
			$this->demopage_array['ROUTES']['GET'][$singular][$table."/{id}/edit"] = "Edit an existing $singular";
			$this->demopage_array['ROUTES']['POST'][$singular][$table."/{id}/store"] = "Save a new $singular";
			$this->demopage_array['ROUTES']['POST'][$singular][$table."/{id}/update"] = "Update an existing $singular";
			$this->demopage_array['ROUTES']['POST'][$singular][$table."/{id}/destroy"] = "Delete an existing $singular";

			// Add resource functions
			if ($this->is_api) {
				$returnformat = 'return $'.$table.';';
			} else {
				$returnformat = 'return view("'.$table.'.index", compact("'.$table.'"));';
			}
			$controllerpath = "./app/Http/Controllers/".$capstable."Controller.php";
			$this->replaceEmptyFunction($controllerpath, "index",  '$scope = $request->input("scope");
		switch ($scope) {
			default:
				$scope_function = "";
				break;
		}
		if ($scope_function) {
			$'.$table.' = '.$singular.'::$scope_function()->get();
		} else {
			$'.$table.' = '.$singular.'::where("id", ">", 0);
		}

		[QUERYPARAMETERS]
		$'.$table.' = $'.$table.'->paginate(15);
		'.$returnformat.'');

			$this->replaceEmptyFunction($controllerpath, "create", 'return view("'.$table.'.create");');
			$this->replaceEmptyFunction($controllerpath, "store",  '
		$input = $request->all();
		$'.$singular_lower.' = '.$singular.'::create($input);
		[MANYTOMANY_STORE]
		return redirect("'.$table.'");');
			if ($this->is_api) {
				$this->replaceEmptyFunction($controllerpath, "show",   "return compact('".$singular_lower."');");
			} else {
				$this->replaceEmptyFunction($controllerpath, "show",   "
		return view('".$table.".show', compact('".$singular_lower."'));");
			}
			$this->replaceEmptyFunction($controllerpath, "edit",   "return view('".$table.".edit', compact('".$singular_lower."'));");
			$this->replaceEmptyFunction($controllerpath, "update", '$'.$singular_lower.'->update($request->all());
		[MANYTOMANY_UPDATE]
		return redirect("'.$table.'/".$'.$singular_lower.'->id);');
			$this->replaceEmptyFunction($controllerpath, "destroy","return view('".$table.".".$singular_lower."_destroy', compact('".$singular_lower."'));");
		}
		$replace = "Route::get('home', 'HomeController@index');";
		$newroutes = str_replace($replace, $replace."\n".$routestr, $oldroutes);
		file_put_contents("./app/Http/routes.php", $newroutes);

	}
	public function velerateNestedRoutes() {
		$routepath = "";
		$routesfile = file_get_contents($this->full_app_path."/app/Http/routes.php");
		foreach ($this->nestedroutes as $model => $belongsto_arr) {
			foreach ($belongsto_arr as $related_model => $dud) {
				$routes = [];
				$table = $this->getTableFromModelName($model);
				$related_table = $this->getTableFromModelName($related_model);
				$capstable = ucwords($table);
				
				if (isset($this->nestedroutes[$related_model])) {
					foreach ($this->nestedroutes[$related_model] as $parent_model => $seconddud) {
						$parent_table = $this->getTableFromModelName($parent_model);
						$oneroute = [];
						$oneroute['path'] = "$parent_table.$related_table.$table";
						$oneroute['nesting_description'] = "associated with both a specific $related_model and its specific $parent_model.";
						$routes[] = $oneroute;
					}
				}
				$oneroute = [];
				$oneroute['path'] = $related_table.".".$table;
				$oneroute['nesting_description'] = "associated with a specific $related_model";
				$routes[] = $oneroute;
				foreach ($routes as $oneroute) {
					$routepath = $oneroute['path'];
					$nesting_description = $oneroute['nesting_description'];
					$replacestr = "Route::resource('$table', '$capstable"."Controller');\n";
					$nestedstr = "Route::resource('$routepath', '$capstable"."Controller');\n";
					$routesfile = str_replace($replacestr, $replacestr.$nestedstr, $routesfile);

					$uripath = str_replace(".", "/{id}/", $routepath);
					$this->demopage_array['ROUTES']['GET'][$model][$uripath."/"] = "View all $capstable $nesting_description";
					$this->demopage_array['ROUTES']['GET'][$model][$uripath."/create"] = "Create a new $model $nesting_description";
					$this->demopage_array['ROUTES']['GET'][$model][$uripath."/{id}"] = "View a $model $nesting_description";
					$this->demopage_array['ROUTES']['GET'][$model][$uripath."/{id}/edit"] = "Edit an existing $model $nesting_description";
					$this->demopage_array['ROUTES']['POST'][$model][$uripath."/{id}/store"] = "Save a new $model $nesting_description";
					$this->demopage_array['ROUTES']['POST'][$model][$uripath."/{id}/update"] = "Update an existing $model $nesting_description";
					$this->demopage_array['ROUTES']['POST'][$model][$uripath."/{id}/destroy"] = "Delete an existing $model $nesting_description";
				}
				
			}
		}
		file_put_contents($this->full_app_path."/app/Http/routes.php", $routesfile);
	}
	public function addModelLinks() {
		$modellinks = [];
		foreach ($this->project_config_array['RELATIONSHIPS'] as $model => $relationships) {
			foreach ($relationships as $relationship_str) {
				$rel_arr = explode(" ", $relationship_str);
				$rel_type = $rel_arr[0];
				$rel_model = $rel_arr[1];
				if ($rel_type == 'hasOne' || $rel_type == 'belongsTo') {
					$rel_table = $this->getTableFromModelName($rel_model);
					if (count($rel_arr) > 2) {
						$func = $rel_arr[2];
					} else {
						$func = strtolower($rel_model);
					}
					$modellinks[$model][$func] = $rel_model;

				}
			}
		}
		//print_r($modellinks);

		foreach ($this->singular_models as $table => $model) {
			$links_html = "";
			if (isset($modellinks[$model])) {
				//$links_html .= $modellinks[$model][$table]['function'];
				foreach ($modellinks[$model] as $func => $rel_model) {
					$links_html .= "@if (count($".strtolower($model)."->".strtolower($rel_model)."))
			";
					$links_html .= $rel_model.': <a href="/'.$this->getTableFromModelName($rel_model).'/{{ $'.strtolower($model)."->".$func."->id }}".'">{{ $'.strtolower($model)."->".$func."->link_text }}</a><br>
		";
					$links_html .= "@endif
		";
				}
			}
			$modelviewpath = $this->full_app_path."/resources/views/".$table."/show.blade.php";
			$modelviewfile = file_get_contents($modelviewpath);
			$newfile = str_replace("[LINKS]", $links_html, $modelviewfile);
			file_put_contents($modelviewpath, $newfile);
		}
	}
	public function addModelDetails() {

		foreach ($this->project_config_array['RELATIONSHIPS'] as $model => $relationships) {
			foreach ($relationships as $relationship_str) {
				$rel_arr = explode(" ", $relationship_str);
				$rel_type = $rel_arr[0];
				$rel_model = $rel_arr[1];
				if ($rel_type == 'hasMany' || $rel_type == 'belongsToMany') {
					$tabname = $this->getTableFromModelName($rel_model);
					$modeldetails[$model][$tabname]['tab_model'] = $rel_model;
					$modeldetails[$model][$tabname]['tab_type'] = "relation";

				}
			}
		}
		if (isset($this->project_config_array['MODELQUERIES'])) {
			foreach ($this->project_config_array['MODELQUERIES'] as $model => $functions) {
				foreach ($functions as $func_str) {
					$func_arr = explode("|", $func_str);
					$func_name = trim($func_arr[0]);
					$func_type = trim($func_arr[1]);
					if ($func_type == 'filter' || $func_type == 'table') {
						$func_table = trim($func_arr[2]);
						$modeldetails[$model][$func_name]['tab_model'] = $this->singular_models[$func_table];
						$modeldetails[$model][$func_name]['tab_type'] = "function";
					}
				}
			}
		}
		//print_r($modeldetails);
		foreach ($this->singular_models as $table => $model) {
			$singular_lower = strtolower($model);
			$show_code = "";
			if ($this->is_api) {
				$show_compact = "
	return compact('".$singular_lower."', ";
			} else {
				$show_compact = "
	return view('".$table.".show', compact('".$singular_lower."', ";
			}
			$tabs_list = "
	";
			$tabs_content = "
			";
			
			if (isset($modeldetails[$model])) {
				$tabs_list .= '<div class="row">
		<div class="columns small-12">
			<ul class="tabs" data-tab data-options="deep_linking:true">';
				$tabs_content .= '<div class="tabs-content">';
				$once = 1;
				foreach ($modeldetails[$model] as $related_function => $related_model_arr) {
					
					$related_model = $related_model_arr['tab_model'];
					$related_type = $related_model_arr['tab_type'];
					$optional_get = "";
					if ($related_type == 'relation') {
						$optional_get = "->get()";
					}
					$related_function_uc = ucwords($related_function);
					if ($once == 1) {
						$activestr = " active";
						$show_compact .= "'$related_function'";
						$once = 0;
					} else {
						$show_compact .= ", '$related_function'";
						$activestr = "";
					}
					
					$show_code .= '$'.$related_function.' = $'.$singular_lower.'->'.$related_function.'()'.$optional_get.';
		';

					$tabs_list .= "
				<li class='tab-title$activestr'>
					<a href='#$related_function'>{{ $".$related_function."->count() }} $related_function_uc</a>
				</li>";

					$tabs_content .= '
				<div class="content'.$activestr.'" id="'.$related_function.'">
					@foreach ($'.$related_function.' as $'.strtolower($related_model).')
						<p><a href="/'.$this->getTableFromModelName($related_model).'/{{ $'.strtolower($related_model).'->id }}">{{ $'.strtolower($related_model).'->link_text }}</a></p>
					@endforeach
				</div>';
				}
				if ($this->is_api) {
					$show_compact .= ");";
				} else {
					//$this->demopage_array['ROUTES']['GET'][$model][$table."/{id}/$related_function"] = "View the $related_function associated with the $model.";
					$show_compact .= "));";
				}
				
				$tabs_list .= "
			</ul>";
				$tabs_content .= "
			</div>";
				$tabs_list .= $tabs_content;
				$tabs_list .= "
		</div>
	</div>";	
				$controllerpath = $this->full_app_path."/app/Http/Controllers/".ucwords($table)."Controller.php";
				$controllerfile = file_get_contents($controllerpath);
				if ($this->is_api) {
					$replacestr = "return compact('".$singular_lower."');";
				} else {
					$replacestr = "return view('".$table.".show', compact('".$singular_lower."'));";
				}
				
				$newcontroller = str_replace($replacestr, $show_code.$show_compact, $controllerfile);
				file_put_contents($controllerpath, $newcontroller);

			} 
			$modelviewpath = $this->full_app_path."/resources/views/".$table."/show.blade.php";
			$modelviewfile = file_get_contents($modelviewpath);
			$newfile = str_replace("[TABS]", $tabs_list, $modelviewfile);
			file_put_contents($modelviewpath, $newfile);
		}
	}
	public function velerateROUTEVIEWS() {
		// The default views for the model resources
		foreach ($this->singular_models as $table => $singular) {
			mkdir($this->full_app_path."/resources/views/$table");
			$mainview = $this->getModelViewMain($singular);
			file_put_contents($this->full_app_path."/resources/views/".$table."/show.blade.php", $mainview);
			$mainlist = $this->getListViewMain($table);
			file_put_contents($this->full_app_path."/resources/views/".$table."/index.blade.php", $mainlist);
			$this->addCreateAndEditForm($table, $singular);
			$this->addModelViewCommand($table, $singular, "store");
			$this->addModelViewCommand($table, $singular, "edit");
			$this->addModelViewCommand($table, $singular, "update");
			$this->addModelViewCommand($table, $singular, "destroy");
		}

		if (isset($this->project_config_array['ROUTES'])) {
			foreach ($this->project_config_array['ROUTES'] as $root => $views) {
				$viewcontent = "@extends('app')
@section('title')
[TITLE]
@endsection
@section('content')
[CONTENT]
@endsection";
				if (count($views) > 0) {
				
					foreach ($views as $view) {
						$newviewcontent = $viewcontent;
						$view_arr = explode(" ", trim($view));
						if (count($view_arr) > 1) {
							$view = $view_arr[1];
						}
						$str = str_replace("?", "", $view);
						$str = str_replace("/", " ", $str);
						$str = ucwords($str);
				        $str = str_replace(" ", "", $str);
				        $str = lcfirst($str);
						$newviewcontent = str_replace("[TITLE]", $root." ".$str, $newviewcontent);
						$newviewcontent = str_replace("[CONTENT]", $root." ".$str, $newviewcontent);
						
						file_put_contents($this->full_app_path."/resources/views/$root/$str.blade.php", $newviewcontent);
					}
				} else {
					$newviewcontent = str_replace("[TITLE]", ucwords($root), $viewcontent);
					$newviewcontent = str_replace("[CONTENT]", ucwords($root), $newviewcontent);
					$root_arr = explode(" ", trim($root));
					if (count($root_arr) > 1) {
						$root = $root_arr[0];
					}
					file_put_contents($this->full_app_path."/resources/views/$root.blade.php", $newviewcontent);
				}
			}
		}

	}
	public function getModelViewMain($model) {
		$table = $this->getTableFromModelName($model);
		return "
@extends('app')

@section('title')
$model
@endsection

@section('content')
	<div class='row'>
		<div class='columns small-10'>
			<h2>{{ $".strtolower($model)."->link_text }}</h2>
		</div>
		<div class='columns small-2'>
			<a class='button' href='/$table/{{ $".strtolower($model)."->id }}/edit'>Edit</a>
		</div>
	</div>
	<div class='row'>
		<div class='columns small-12 large-4 large-push-8'>
			[LINKS]
		</div>
		<div class='columns small-12 large-8 large-pull-4'>
			<table>
				@foreach ($".strtolower($model)."->getAttributes() as ".'$attribute'." => ".'$value'.")
					<tr><td>{{ ".'$attribute'." }}</td><td>{{ ".'$value'." }}</td></tr>
				@endforeach
			</table>
		</div>
	</div>
	[TABS]
@endsection";
	}
		public function getListViewMain($table) {
			$pagename = ucwords($table);
			$model = $this->singular_models[$table];
		return "
@extends('app')

@section('title')
$pagename
@endsection

@section('content')
	<div class='row'>
		<div class='columns small-12'>
			<h2>$pagename</h2>
		</div>
	</div>
	<div class='row'>
		<div class='columns small-12'>
			Displaying items {!! $".$table."->firstItem() !!} to {!! $".$table."->lastItem() !!} of {!! $".$table."->total() !!}
			<table>
				@foreach ($".$table." as $".strtolower($model).")
					<tr>
						<td><a href='/".$table."/{{ $".strtolower($model)."->id }}'>{{ $".strtolower($model)."->link_text }}</a></td>
						@foreach ($".strtolower($model)."->getAttributes() as ".'$attribute'." => ".'$value'.")
							<td>{{ ".'$value'." }}</td>
						@endforeach
					</tr>
				@endforeach
			</table>
			{!! $".$table."->render() !!}
		</div>
	</div>
@endsection";
	}
	public function addModelViewCommand($table, $model, $command) {
		$linktext = "{{ $".strtolower($model)."->link_text }}";
		$command_upper = ucwords($command);
		switch ($command) {
			case 'create':
			$command = "[create]";
				$linktext = "";
				break;
		}
		
		$str = "
@extends('app')

@section('title')
$command_upper $linktext
@endsection

@section('content')
	<div class='row'>
		<div class='columns small-12'>
			$command_upper $linktext
		</div>
	</div>
@endsection";
		file_put_contents($this->full_app_path."/resources/views/$table/".strtolower($model)."_$command.blade.php", $str);
	}
	function replaceEmptyFunction($filepath, $function, $newcode) {
		$originalfile = file_get_contents($filepath);
		$startpos = strpos($originalfile, "function $function");
		$tempstr = substr($originalfile, $startpos);
		$endpos = strpos($tempstr, "}");
		$functionfull = substr($originalfile, $startpos, $endpos+1);
		$newfunc = str_replace("//", $newcode, $functionfull);
		$finalfile = str_replace($functionfull, $newfunc, $originalfile);
		file_put_contents($filepath, $finalfile);
	}
	public function velerateCOMMANDS() {

		if (isset($this->project_config_array['COMMANDS'])) {
			foreach ($this->project_config_array['COMMANDS'] as $command_and_flags => $models) {
				$command_arr = explode(" ", trim($command_and_flags));
				$command_name = $command_arr[0];
				$command_flag = $command_arr[1];
				shell_exec("php artisan make:command $command_name --$command_flag");
				$commandfile = file_get_contents($this->full_app_path."/app/Commands/$command_name.php");
				$modelstr = "";
				$models_arr = explode(" ", trim($models[0]));
				foreach ($models_arr as $key => $model) {
					if (isset(array_flip($this->singular_models)[$model])) {
						$model = "App\\".$model;
					}
					$modelstr .= "use $model;
";
				}
				$newfile = str_replace("use App\Commands\Command;", "use App\Commands\Command;
$modelstr", $commandfile);
				file_put_contents($this->full_app_path."/app/Commands/$command_name.php", $newfile);
			}
		}

	}

	public function velerateMODELQUERIES() {

		if (isset($this->project_config_array['MODELQUERIES'])) {
			foreach ($this->project_config_array['MODELQUERIES'] as $model => $function_arr) {
				$this->addModelFunctions($model, $function_arr, 'instance');
			}
		}

	}
	public function velerateLISTQUERIES() {

		if (isset($this->project_config_array['LISTQUERIES'])) {
			foreach ($this->project_config_array['LISTQUERIES'] as $model => $function_arr) {
				// Make this a query route
				$this->addModelFunctions($model, $function_arr, 'list');
			}
		}

	}
	public function velerateListParameterFilters() {
		// Adds a parameter query for any list
		// based on the fields defined in DBMODELS
		foreach ($this->singular_models as $table => $model) {
			$uppercasetable = ucwords($table);
			$controllerpath = $this->full_app_path."/app/Http/Controllers/".$uppercasetable."Controller.php";
			$newcontroller = file_get_contents($controllerpath);
			$newcontroller = str_replace('[QUERYPARAMETERS]', '// Handles query parameters, i.e. ?field=val$field2=val2
		$static_'.strtolower($model).' = new '.$model.'();
		foreach ($request->input() as $field => $val) {
			if ($static_'.strtolower($model).'->isFillable($field)) {
				$'.$table.' = $'.$table.'->where($field, $val);
			}
		}
		', $newcontroller);
			file_put_contents($controllerpath, $newcontroller);
		}
	}
	public function addModelFunctions($model, $function_arr, $query_type) {
		$modelfile = file_get_contents($this->full_app_path."/app/$model.php");
		$replacestr = 'protected $hidden = [];';
		$function_str = "";
		$tablename = $this->getTableFromModelName($model);
		foreach ($function_arr as $key => $function_params) {

			$function_body = "";
			$function_arr = explode("|", trim($function_params));
			$function_name = trim($function_arr[0]);
			
			$function_type = trim($function_arr[1]);
			if ($query_type == 'list') {
				$this->listqueries[$model][$function_name] = $function_type;
			}
			
			if ($function_type == 'filter') {
				$filter_table = trim($function_arr[2]);
				$filter_logic = trim($function_arr[3]);
				$function_body = $this->getFilterCode($filter_table, $filter_logic);
			} else if ($function_type == 'scope') {
				$function_name = 'scope'.ucfirst($function_name);
				$function_body = "return ".'$this->'.trim($function_arr[2]).";";
			} else if ($function_type == 'table') {
				$function_body = "return ".trim($function_arr[3]).";";
			}
			
			$function_str .= "
	public function $function_name()
	{
		$function_body
	}";
		}
		$newfile = str_replace($replacestr, $replacestr.$function_str, $modelfile);
		file_put_contents($this->full_app_path."/app/$model.php", $newfile);
	}
	public function getFilterCode($filter_table, $filter_logic) {
		$singular = strtolower($this->singular_models[$filter_table]);
		return 'return $this->'.$filter_table.'()->filter(function($'.$singular.') {
	        if ('.$filter_logic.') {
	            return true;
	        }
	    });';
	}

	public function getRelationshipFunction($function_name, $model, $type, $field) {
		$extrafunction = "";
		if ($type == 'belongsToMany') {
			$extrafunction = '->withTimestamps()';
		}
		if ($field) {
			$model = $model."', '".$field;
		}
		$str = "
	public function $function_name()
	{
		return ".'$this'."->$type('App\\$model')$extrafunction;
	}
	";
		return $str;
	}
	public function getRelationshipListFunction($table, $model) {
		$str = "
	public function get".$model."ListAttribute()
	{
		return ".'$this'."->$table"."->lists('id');
	}
	";
		return $str;
	}
	public function getRelationshipFunctionBelongsToThrough($function_name, $model, $through) {
		$str = "
	public function $function_name()
	{
		return ".'$this'."->".$through."->belongsTo('App\\$model');
	}
	";
		return $str;
	}
	public function getTableFromModelName($model) {
		$temparr = array_flip($this->singular_models);
		return $temparr[$model];
	}

	function addProvider($val) {
		$config_app = file_get_contents($this->full_app_path."/config/app.php");
		$new_config_app = str_replace("'providers' => [", "'providers' => [
		'$val',", $config_app);
		file_put_contents($this->full_app_path."/config/app.php", $new_config_app);
	}
	function addAlias($name, $path) {
		$config_app = file_get_contents($this->full_app_path."/config/app.php");
		$new_config_app = str_replace("'aliases' => [", "'aliases' => [
		'$name' 	=> '$path',", $config_app);
		file_put_contents($this->full_app_path."/config/app.php", $new_config_app);
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
	public function createDemoPage() {
		foreach ($this->schema as $model => $schema) {
			$this->demopage_array['MODELS'][$model]['schema'] = $schema;
		}
		//print_r($this->demopage_array);
		$routes = file_get_contents($this->full_app_path."/app/Http/routes.php");
		$newroutes = str_replace("Route::get('/', 'WelcomeController@index');", "Route::get('/', function() {
	return view('velerator_demo');
});", $routes);
		file_put_contents($this->full_app_path."/app/Http/routes.php", $newroutes);
		$demopage = file_get_contents($this->velerator_path."/velerator_files/views/demo.blade.php");
		$newdemopage = str_replace("[APP]", $this->demopage_array['APP'], $demopage);
		$newdemopage = str_replace("[FILE]", $this->demopage_array['FILE'], $newdemopage);
		$routelist = "<table>";
		foreach ($this->demopage_array['ROUTES'] as $routetype => $models) {
			$routelist .= "<tr class='divider'><td colspan=3>".$routetype." Requests</td></trk>";
			$lastmodelname = "";
			foreach ($models as $modelname => $routenames) {
				foreach ($routenames as $routename => $message) {
					$routelist .= "<tr>";
					if ($lastmodelname != $modelname) {
						$routelist .= "<td>$modelname</td>";
					} else {
						$routelist .= "<td></td>";
					}
					$linkroutename = str_replace("{id}", 1, $routename);
					if ($routetype == 'GET' || $routetype == 'GET REDUNDANT') {
						$routelist .= "<td><a href='/$linkroutename'>".$routename."</a></td>";
					} else {
						$routelist .= "<td>$routename</td>";
					}
					$routelist .= "<td>$message</td>";
					$routelist .= "</tr>";
					$lastmodelname = $modelname;
				}
			}
		}
		$routelist .= "</table>";
		$newdemopage = str_replace("[ROUTES]", $routelist, $newdemopage);

		$modelsections = "";
		$newdemopage = str_replace("[MODELSECTIONS]", $modelsections, $newdemopage);
		file_put_contents($this->full_app_path."/resources/views/velerator_demo.blade.php", $newdemopage);
	}

}
new Velerator($argv);