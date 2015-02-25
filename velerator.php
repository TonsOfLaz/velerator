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
			shell_exec("git stash");
			shell_exec("git rebase --onto velerator_fresh_install_packages velerator_generated master");
			shell_exec("git branch -D velerator_generated");
			shell_exec("git checkout velerator_fresh_install_packages");
		}
		shell_exec("git branch velerator_generated");
		shell_exec("git checkout velerator_generated");

		$this->initializeDatabase();
		shell_exec("cp ".$this->velerator_path."/velerator_files/.env ".$this->full_app_path."/.env");
		shell_exec("cp ".$this->velerator_path."/velerator_files/.gitignore ".$this->full_app_path."/.gitignore");
		$this->bringInFoundationCSS();

		$this->velerateDBMODELS();
		$this->velerateRELATIONSHIPS();
		$this->velerateCOMMANDS();
		$this->velerateMODELFUNCTIONS();
		shell_exec("php artisan migrate");
		$this->velerateROUTECONTROLLERS();
		$this->velerateROUTEVIEWS();
		$this->velerateROUTES();
		$this->addModelLinks();
		$this->addModelTabs();
		$this->modifyAppView();
		$this->createAndRunSeedFiles();

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
		$appview = file_get_contents($this->full_app_path."/resources/views/app.blade.php");
		$replacestr_css = "<link href=\"/css/app.css\" rel=\"stylesheet\">";
		$newappview = str_replace($replacestr_css, $replacestr_css."
	<link href=\"/css/foundation/foundation.min.css\" rel=\"stylesheet\">", $appview);

		$replacestr_js = 'bootstrap.min.js"></script>';
		$new_js = '
	<script src="/js/foundation.min.js"></script>
	<script>
		$(document).foundation();
	</script>';
		$newappview = str_replace($replacestr_js, $replacestr_js.$new_js, $newappview);
		file_put_contents($this->full_app_path."/resources/views/app.blade.php", $newappview);

	}
	public function modifyAppView() {
		$appview = file_get_contents($this->full_app_path."/resources/views/app.blade.php");
		$newview = str_replace("Laravel", ucwords($this->project_name), $appview);
		file_put_contents($this->full_app_path."/resources/views/app.blade.php", $newview);
	}
	public function createAndRunSeedFiles() {
		echo "Creating table seeders...\n";
		$table_seeder_calls = "";
		if (isset($this->project_config_array['FAKEDATA'])) {
			foreach ($this->project_config_array['FAKEDATA'] as $object_and_count => $fields) {
				$obj_cnt_arr = explode(" ", $object_and_count);
				$object = $obj_cnt_arr[0];
				echo $object."\n";
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
				} else {
					$baseseeder = file_get_contents($this->velerator_path."/velerator_files/database/TableSeeder.php");
					$newseeder = str_replace('[NAME]', $singular, $baseseeder);
				}
				$newseeder = str_replace('[COUNT]', $count, $newseeder);
				$newseeder = str_replace('[ARRAY]', $fakerstr, $newseeder);

				file_put_contents($this->full_app_path."/database/seeds/".$singular."TableSeeder.php", $newseeder);
				$table_seeder_calls .= '$this'."->call('".$singular."TableSeeder');
		";
			}
		}
		$database_seeder_master = file_get_contents($this->full_app_path."/database/seeds/DatabaseSeeder.php");
		$new_dbseed_master = str_replace('// $this'."->call('UserTableSeeder');", $table_seeder_calls, $database_seeder_master);
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
		if (isset($this->project_config_array['FAKEDATA'])) {
			
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
							//addProvider($value);
							break;
						case "alias":
							$value_arr = explode(" ", $value);
							$name = $value_arr[0];
							$path = $value_arr[1];
							//addFacade($name, $path);
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


				switch ($relationship_type) {
					case 'hasOne':
						if (!$function_name) {
							$function_name = strtolower($relationship_model);
						}
						$function_str .= $this->getRelationshipFunction($function_name, 
																		$relationship_model, 
																		$relationship_type,
																		$relationship_field);
						break;
					case 'belongsTo':
						if (!$function_name) {
							$function_name = strtolower($relationship_model);
						}
						$function_str .= $this->getRelationshipFunction($function_name, 
																		$relationship_model, 
																		$relationship_type,
																		$relationship_field);
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
						$function_str .= $this->getRelationshipFunction($function_name, 
																		$relationship_model, 
																		$relationship_type,
																		$relationship_field);
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
						break;
					case 'morphedByMany':
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
	}

	public function velerateROUTES() {
		$controller_arr = [];
		$routestr = "";
		$routefile = file_get_contents($this->full_app_path.'/app/Http/routes.php');
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
					} else {
						if (strpos("a".$route, "?") > 0) {
							$dependency_injection = "$singular ".'$'."$singular_lower";
						}
						$str = str_replace("?", "", $route);
						$str = str_replace("/", " ", $str);
						$str = ucwords($str);
				        $str = str_replace(" ", "", $str);
				        $str = lcfirst($str);
				        $route = str_replace("?", '{'.$singular_lower.'}', $route);
						$finalroute = "$routebase/$route";
					}
					
					$routestr .= "
Route::get('$finalroute', '".$controller."@".$str."');";
					$temparr = [];
					$temparr['function'] = $str."($dependency_injection)";
					$compact = "";
					if ($dependency_injection) {
						$compact = ", compact('$singular_lower')";
					}
					$temparr['body'] = "return view('$routebase.$str'$compact);";
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
			$newcontroller = str_replace('$id', '$'.$singular_lower, $newcontroller);
			file_put_contents($this->full_app_path."/app/Http/Controllers/".$capstable."Controller.php", $newcontroller);

			// Include resource routes
			$routestr .= "Route::model('$table', 'App\\$singular');\n";
			$routestr .= "Route::resource('$table', '$capstable"."Controller');\n";


			// Add resource functions
			$controllerpath = "./app/Http/Controllers/".$capstable."Controller.php";
			$this->replaceEmptyFunction($controllerpath, "index",  "$".$table." = ".$singular."::all();
		return $".$table.";");
			$this->replaceEmptyFunction($controllerpath, "create", 'return view("'.$table.'.'.$singular_lower.'_create");');
			$this->replaceEmptyFunction($controllerpath, "store",  'return view("'.$table.'.'.$singular_lower.'_store");');
			$this->replaceEmptyFunction($controllerpath, "show",   "return view('".$singular_lower."', compact('".$singular_lower."'));");
			$this->replaceEmptyFunction($controllerpath, "edit",   "return view('".$table.".".$singular_lower."_edit', compact('".$singular_lower."'));");
			$this->replaceEmptyFunction($controllerpath, "update", "return view('".$table.".".$singular_lower."_update', compact('".$singular_lower."'));");
			$this->replaceEmptyFunction($controllerpath, "destroy","return view('".$table.".".$singular_lower."_destroy', compact('".$singular_lower."'));");
		}
		$replace = "Route::get('home', 'HomeController@index');";
		$newroutes = str_replace($replace, $replace."\n".$routestr, $oldroutes);
		file_put_contents("./app/Http/routes.php", $newroutes);

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
					$links_html .= '<a href="/'.$this->getTableFromModelName($rel_model).'/{{ $'.strtolower($model)."->".$func."->id }}".'">'.$rel_model.' {{ $'.strtolower($model)."->".$func."->id }}</a><br>";
				}
			}
			$modelviewpath = $this->full_app_path."/resources/views/".strtolower($model).".blade.php";
			$modelviewfile = file_get_contents($modelviewpath);
			$newfile = str_replace("[LINKS]", $links_html, $modelviewfile);
			file_put_contents($modelviewpath, $newfile);
		}
	}
	public function addModelTabs() {
		$modeltabs = [];
		foreach ($this->project_config_array['RELATIONSHIPS'] as $model => $relationships) {
			foreach ($relationships as $relationship_str) {
				$rel_arr = explode(" ", $relationship_str);
				$rel_type = $rel_arr[0];
				$rel_model = $rel_arr[1];
				if ($rel_type == 'hasMany' || $rel_type == 'belongsToMany') {
					$tabname = $this->getTableFromModelName($rel_model);
					$modeltabs[$model][$tabname]['tab_model'] = $rel_model;
					$modeltabs[$model][$tabname]['tab_type'] = "relation";

				}
			}
		}
		foreach ($this->project_config_array['MODELFUNCTIONS'] as $model => $functions) {
			foreach ($functions as $func_str) {
				$func_arr = explode("|", $func_str);
				$func_name = trim($func_arr[0]);
				$func_type = trim($func_arr[1]);
				if ($func_type == 'filter' || $func_type == 'table') {
					$func_table = trim($func_arr[2]);
					$modeltabs[$model][$func_name]['tab_model'] = $this->singular_models[$func_table];
					$modeltabs[$model][$func_name]['tab_type'] = "function";
				}
			}
		}
		//print_r($modeltabs);
		foreach ($this->singular_models as $table => $model) {
			$tabs_list = "
	";
			$tabs_content = "
			";
			$singular_lower = strtolower($model);
			if (isset($modeltabs[$model])) {
				$tabs_list .= '<div class="row">
		<div class="columns small-12">
			<ul class="tabs" data-tab data-options="deep_linking:true">';
				$tabs_content .= '<div class="tabs-content">';
				$once = 1;
				foreach ($modeltabs[$model] as $related_function => $related_model_arr) {
					$related_model = $related_model_arr['tab_model'];
					$related_type = $related_model_arr['tab_type'];
					$optional_get = "";
					if ($related_type == 'relation') {
						$optional_get = "->get()";
					}
					$related_function_uc = ucwords($related_function);
					if ($once == 1) {
						$activestr = " active";
						$once = 0;
					} else {
						$activestr = "";
					}
					$tabs_list .= "
				<li class='tab-title$activestr'>
					<a href='#$related_function'>{{ $".$singular_lower."->".$related_function."()->count() }} $related_function_uc</a>
				</li>";

					$tabs_content .= '
				<div class="content'.$activestr.'" id="'.$related_function.'">
					@foreach ($'.$singular_lower.'->'.$related_function.'()'.$optional_get.' as $'.strtolower($related_model).')
						<p><a href="/'.$this->getTableFromModelName($related_model).'/{{ $'.strtolower($related_model).'->id }}">'.$related_model.' {{ $'.strtolower($related_model).'->id }}</a></p>
					@endforeach
				</div>';
				}
				$tabs_list .= "
			</ul>";
				$tabs_content .= "
			</div>";
				$tabs_list .= $tabs_content;
				$tabs_list .= "
		</div>
	</div>";
			} 
			$modelviewpath = $this->full_app_path."/resources/views/".strtolower($model).".blade.php";
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
			file_put_contents($this->full_app_path."/resources/views/".strtolower($singular).".blade.php", $mainview);
			$this->addModelViewCommand($table, $singular, "create");
			$this->addModelViewCommand($table, $singular, "store");
			$this->addModelViewCommand($table, $singular, "edit");
			$this->addModelViewCommand($table, $singular, "update");
			$this->addModelViewCommand($table, $singular, "destroy");
		}

	}
	public function getModelViewMain($model) {
		return "
@extends('app')

@section('title')
$model
@endsection

@section('content')
	<div class='row'>
		<div class='columns small-12 large-8'>
			<h2>$model {{ $".strtolower($model)."->id }}</h2>
		</div>
	</div>
	<div class='row'>
		<div class='columns small-12 large-4 large-push-8'>
			[LINKS]
		</div>
		<div class='columns small-12 large-8 large-pull-4'>
			<ul>
				@foreach ($".strtolower($model)."->getAttributes() as ".'$attribute'." => ".'$value'.")
					<li>{{ ".'$attribute'." }}: {{ ".'$value'." }}</li>
				@endforeach
			</ul>
		</div>
	</div>
	[TABS]
@endsection";
	}
	public function addModelViewCommand($table, $model, $command) {
		$modelid_str = "{{ $".strtolower($model)."->id }}";
		switch ($command) {
			case 'create':
				$modelid_str = "";
				break;
		}
		$command_upper = ucwords($command);
		$str = "
@extends('app')

@section('title')
$command_upper $model $modelid_str
@endsection

@section('content')
	<div class='row'>
		<div class='columns small-12'>
			$command_upper $model $modelid_str
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

	public function velerateMODELFUNCTIONS() {

		foreach ($this->project_config_array['MODELFUNCTIONS'] as $model => $function_arr) {
			$modelfile = file_get_contents($this->full_app_path."/app/$model.php");
			$replacestr = 'protected $hidden = [];';
			$function_str = "";
			foreach ($function_arr as $key => $function_params) {
				$function_body = "";
				$function_arr = explode("|", trim($function_params));
				$function_name = trim($function_arr[0]);
				$function_type = trim($function_arr[1]);
				if ($function_type == 'filter') {
					$filter_table = trim($function_arr[2]);
					$filter_logic = trim($function_arr[3]);
					$function_body = $this->getFilterCode($filter_table, $filter_logic);
				} else if ($function_type == 'scope') {
					$function_name = 'scope'.ucfirst($function_name);
					$function_body = "return ".trim($function_arr[2]).";";
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
		if ($field) {
			$model = $model."', '".$field;
		}
		$str = "
	public function $function_name()
	{
		return ".'$this'."->$type('App\\$model');
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
	function addFacade($name, $path) {
		$config_app = file_get_contents($this->full_app_path."/config/app.php");
		$new_config_app = str_replace("'aliases' => [", "'aliases' => [
			'$name' => '$path',", $config_app);
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

}
new Velerator($argv);