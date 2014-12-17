<?php 

global $fullpath,$startdirectory;

if (isset($argv[1]) && isset($argv[2])) {

	if (isset($argv[3])) {
		createProject($argv[1], $argv[2], $argv[3]);
	} else {
		createProject($argv[1], $argv[2], '');
	}
	

} else {
	echo "Please add a project name and genfile, i.e.: \nphp generator.php myproject mygenfile.txt";
}

function createProject ($projectname, $projectfile, $extra) {

	global $startdirectory, $fullpath;

	$startdirectory = getcwd();
	$fullpath = $startdirectory."/".$projectname;

	$project_array = createArrayFromFile($projectfile);
	// print_r($project_array);
	// exit();

	

	// ===================================> CLEARING OLD VERSION
	$quickreset = false;
	if ($extra == 'clear') {
		echo "Clearing existing directory...\n";
		shell_exec("rm -rf ".$fullpath);
		echo "Done deleting $projectname\n";
	}
	if (is_dir($fullpath)) {
		// Instead of creating a new project, we just reset git to a fresh project install
		// This speeds up running it and stops tasking the composer servers
		echo "Reverting to a clean install...\n";
		$quickreset = true;
		chdir($fullpath);
		$resp = shell_exec("git log --pretty=format:'%h' --reverse | head -1");
		shell_exec("git reset --hard $resp");
		shell_exec("git clean -fd");
	} else {
		// ===================================> LARAVEL
		echo "Creating Laravel project...\n";
		//shell_exec("laravel new ".$projectname);
		shell_exec("composer --no-interaction create-project laravel/laravel $projectname dev-develop");
		echo "Laravel project $projectname created.\n";

		// ===================================> ADD COMPOSER TOOLS
		chdir($fullpath);
		echo "Adding 'Faker' tool...\n";
		shell_exec("composer require fzaninotto/faker");

		// ===================================> ADD NODE TOOLS (GULP, FOR ELIXIR)
		// These are declared in packages.json
		//shell_exec("npm install");
	}


	// ===================================> MOVE DIRECTORY INTO APP
	echo "Entering directory $fullpath ...\n";
	chdir($fullpath);

	

	// ===================================> GIT INIT/FIRST COMMIT
	if (!$quickreset) {
		chdir($fullpath);
		echo "Creating git object...\n";
		shell_exec("git init");
		echo "First git commit...\n";
		shell_exec("git add .");  
		shell_exec("git commit -am 'First git commit for empty project $projectname, before Velerator changes'");
		echo "Added first git commit.\n";
	}


	// ===================================> LOCAL HOST
	//addHostsMapping($projectname);
	// ===================================> HOMESTEAD
	//addHomesteadMapping($projectname);

	// ===================================> CREATE DIRECTORIES
	echo "Creating directories...\n";
	mkdir($fullpath."/resources/templates/pages");
	mkdir($fullpath."/resources/templates/sections");
	mkdir($fullpath."/public/assets");
	mkdir($fullpath."/public/assets/css");
	mkdir($fullpath."/public/assets/js");

	// ===================================> COPY GENERIC FILES
	// Add home, navigation buttons, header, footer views
	// Pull files from folder

	shell_exec("cp $startdirectory/velerator_files/templates/all.blade.php $fullpath/resources/templates/pages/all.blade.php");
	shell_exec("cp $startdirectory/velerator_files/templates/single.blade.php $fullpath/resources/templates/pages/single.blade.php");
	shell_exec("cp $startdirectory/velerator_files/.env $fullpath/.env");
	shell_exec("cp $startdirectory/velerator_files/css/main.css $fullpath/public/assets/css/main.css");
	shell_exec("cp $startdirectory/velerator_files/foundation/css/*.css $fullpath/public/assets/css");
	shell_exec("cp -R $startdirectory/velerator_files/foundation/js/* $fullpath/public/assets/js");

	// ===================================> WELCOME PAGE
	$appview = file_get_contents($startdirectory."/velerator_files/templates/app.blade.php");
	$newappview = str_replace("[APPNAME]", ucwords($projectname), $appview);
	file_put_contents($fullpath."/resources/templates/app.blade.php", $newappview);
	$welcome = file_get_contents($startdirectory."/velerator_files/templates/welcome.blade.php");
	file_put_contents($fullpath."/resources/templates/welcome.blade.php", $welcome);

	// ===================================> REFERENCE ARRAYS - SINGULAR NAMES (i.e. bills, Bill)
	$routes = [];
	$singular_objects = [];
	foreach ($project_array['OBJECTS'] as $object_and_singular => $fields) {
		$obj_sin_arr = explode(" ", $object_and_singular);
		$object = $obj_sin_arr[0];
		$singular = $obj_sin_arr[1];
		$singular_objects[$object] = $singular;
		$routes[$object] = $object;
	}

	// ===================================> ROUTES / SPECIFIC VIEWS
	loopOnViewsRoutes($routes, $singular_objects);

	// ===================================> NAVIGATION
	$navs = [];
	foreach ($project_array['NAVIGATION'] as $path => $subpath) {
		$path_arr = explode(" | ", $path);
		$visible = $path_arr[0];
		$view = "";
		if (isset($path_arr[1])) {
			$view = $path_arr[1];
		}
		$navs[$visible] = $view;
	}
	createNavigationFile($navs);

	

	// ===================================> HOOK UP TO HOMESTEAD MYSQL DB
	shell_exec("touch $fullpath/storage/database.sqlite");
	$local_db_config = file_get_contents($fullpath."/config/database.php");
	/*
	$new_db_config = str_replace("'host'      => 'localhost'", "'host'      => ".'$_ENV'."['DB_HOST']", $local_db_config);
	$new_db_config = str_replace("'database'  => 'forge'", "'database'  => ".'$_ENV'."['DB_DATABASE']", $new_db_config);
	$new_db_config = str_replace("'username'  => 'forge'", "'username'  => ".'$_ENV'."['DB_USERNAME']", $new_db_config);
	$new_db_config = str_replace("'password'  => ''", "'password'  => ".'$_ENV'."['DB_PASSWORD']", $new_db_config);
	*/

	$new_db_config = str_replace("'default' => 'mysql',", "'default' => 'sqlite',", $local_db_config);
	file_put_contents($fullpath."/config/database.php", $new_db_config);

	// ===================================> MIGRATIONS, SCHEMA, MODELS
	echo "Creating migrations...\n";
		
	foreach ($project_array['OBJECTS'] as $object_and_singular => $fields_arr) {
		$obj_sin_arr = explode(" ", $object_and_singular);
		$object = $obj_sin_arr[0];
		$singular = $obj_sin_arr[1];
		// Create Migration
		shell_exec("php artisan make:migration --create=$object create_".$object."_table");
		
		$allfields_arr = [];
		$fillable_str = '';
		$once = 1;
		foreach ($fields_arr as $field_str) {
			$fieldtype = "string";  // Default is string if blank
			$secondarytable = "";
			$thisfield_arr = explode(" ", $field_str);

			$fieldname = $thisfield_arr[0];
			if (substr($fieldname,0,1) == '*') {
				// Ones with * need their own tables
				continue;
			} else {
				if ($once) {
					$fillable_str .= "'$fieldname'";
					$once = 0;
				} else {
					$fillable_str .= ",'$fieldname'";
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
				}
				$tempfield_arr = [];
				$tempfield_arr['name'] = $fieldname;
				$tempfield_arr['type'] = $fieldtype;
				$tempfield_arr['secondary'] = $secondarytable;
				$allfields_arr[] = $tempfield_arr;
			}
			
		}
		// Update Schema file
		addFieldArrayToCreateSchema($object, $allfields_arr);

	}
	echo "Creating Link tables...\n";
	// Link table migrations have to be created AFTER the other tables
	// So the foreign keys will link properly. The order matters here.
	foreach ($project_array['OBJECTS'] as $object_and_singular => $fields_arr) {
		$obj_sin_arr = explode(" ", $object_and_singular);
		$object = $obj_sin_arr[0];
		$singular = $obj_sin_arr[1];

		foreach ($fields_arr as $field_str) {

			$thisfield_arr = explode(" ", $field_str);

			$fieldname = $thisfield_arr[0];
			if (substr($fieldname,0,1) == '*') {
				// Ones with * need their own tables
				$othertable_name = str_replace("*", "", $fieldname);
				$linktable_name = $object."_".$othertable_name;

				// Add these tables to the singular table array
				// So they will get their own objects and seeder data
				$linktable_name_singular = ucfirst($object).ucfirst($othertable_name);
				$singular_objects[$linktable_name] = $linktable_name_singular;

				if (count($thisfield_arr) > 1) {
					$linktable = $thisfield_arr[1];
				} else {
					$linktable = $othertable_name;
				}
				shell_exec("php artisan make:migration --create=".$linktable_name." create_".$linktable_name."_table");
				// Then make schema
				$primary_field = strtolower($singular)."_id";
				$secondary_field = strtolower($singular_objects[$linktable])."_id";
				$primary_arr = [];
				$primary_arr['name'] = $primary_field;
				$primary_arr['type'] = "integer";
				$primary_arr['secondary'] = $object;
				$secondary_arr = [];
				$secondary_arr['name'] = $secondary_field;
				$secondary_arr['type'] = "integer";
				$secondary_arr['secondary'] = $linktable;
				$linkfields_arr = [];
				$linkfields_arr[] = $primary_arr;
				$linkfields_arr[] = $secondary_arr;
				addFieldArrayToCreateSchema($linktable_name, $linkfields_arr);
			} 
			
		}

	}
	echo "Running migrations...\n";
	shell_exec("php artisan migrate");

	// ===================================> CREATING MODELS

	foreach ($singular_objects as $object => $singular) {
		// Create Model
		$generic_model = file_get_contents($startdirectory."/velerator_files/Model.php");
		$newmodel = str_replace("[NAME]", $singular, $generic_model);
		$newmodel = str_replace("[TABLE]", $object, $newmodel);
		$newmodel = str_replace("[FILLABLE_ARRAY]", "[$fillable_str]", $newmodel);
		$newmodel = str_replace("[HIDDEN_ARRAY]", '[]', $newmodel);
		file_put_contents($fullpath."/app/".$singular.".php", $newmodel);
	}

	// ===================================> ADDING NAME FUNCTIONS
	$objects_with_special_names = [];
	if (isset($project_array["NAMES"])) {
		foreach ($project_array['NAMES'] as $namestr => $emptyarray) {
			$name_arr = explode("|", $namestr);
			$object = trim($name_arr[0]);
			$nameguide = trim($name_arr[1]);
			$singular = $singular_objects[$object];
			$objects_with_special_names[$object] = 1;

			preg_match_all('|\$([a-zA-Z_]*)|', $nameguide, $matches);
			$varstr = "";
			foreach ($matches[1] as $varname) {
				$varstr .= '$'.$varname.' = $this->'.$varname.";
			";
			}
			$functioncontent = $varstr."
			".'return "'.$nameguide.'";';
			//echo $functioncontent;
			
			$modelfile = file_get_contents($fullpath."/app/".$singular.".php");
			$newmodelfile = str_replace('protected $hidden = [];', 'protected $hidden = [];

		public function name() {
			'.$functioncontent.'
		}', $modelfile);
			file_put_contents($fullpath."/app/".$singular.".php", $newmodelfile);

		}
	}

	$functioncontent = 'return $this->name;';
	foreach ($singular_objects as $object => $singular) {
		// Fill in name() return .name for remaining objects

		if (!isset($objects_with_special_names[$object])) {
			$modelfile = file_get_contents($fullpath."/app/".$singular.".php");
			$newmodelfile = str_replace('protected $hidden = [];', 'protected $hidden = [];

	public function name() {
		'.$functioncontent.'
	}', $modelfile);
			file_put_contents($fullpath."/app/".$singular.".php", $newmodelfile);
		}
	}

	// ===================================> TABLE SEEDER USING FAKER
	echo "Creating table seeders...\n";
	// we also want access to the singular name of the users table, even though we didnt create it
	$singular_objects['users'] = "User";
	$table_seeder_calls = "";
	$baseseeder = file_get_contents($startdirectory."/velerator_files/database/TableSeeder.php");
	foreach ($project_array['FAKEDATA'] as $object_and_count => $fields) {
		$obj_cnt_arr = explode(" ", $object_and_count);
		$object = $obj_cnt_arr[0];

		$fakerstr = "";
		$count = $obj_cnt_arr[1];
		foreach ($fields as $field_and_fakergen) {
			$fakergen_arr = explode("|", $field_and_fakergen, 2);
			$faker_field = trim($fakergen_arr[0]);
			$faker_gen = trim($fakergen_arr[1]);
			$fakerstr .= "'$faker_field' => ".'$faker->'.$faker_gen.",
				";
		}
		$singular = $singular_objects[$object];
		$newseeder = str_replace('[NAME]', $singular, $baseseeder);
		$newseeder = str_replace('[COUNT]', $count, $newseeder);
		$newseeder = str_replace('[ARRAY]', $fakerstr, $newseeder);

		file_put_contents($fullpath."/database/seeds/".$singular."TableSeeder.php", $newseeder);
		$table_seeder_calls .= '$this'."->call('".$singular."TableSeeder');
		";
	}
	$database_seeder_master = file_get_contents($fullpath."/database/seeds/DatabaseSeeder.php");
	$new_dbseed_master = str_replace('// $this'."->call('UserTableSeeder');", $table_seeder_calls, $database_seeder_master);
	file_put_contents($fullpath."/database/seeds/DatabaseSeeder.php", $new_dbseed_master);


	shell_exec("composer dump-autoload");

	echo "Running seeders...\n";
	shell_exec("php artisan db:seed");


	// ===================================> GIT COMMIT ON COMPLETE, SO YOU CAN SEE VELERATOR CHANGES
	chdir($fullpath);
	echo "Final git commit...\n";
	shell_exec("git add .");  
	shell_exec("git commit -am 'Velerator has run on file ".$projectfile."'");
	
	// ===================================> FINISHED
	echo "Project created.\n";
	

}

function addHostsMapping($projectname) {
	$hosts = file_get_contents("/etc/hosts");
	if (strpos($hosts, $projectname.".local") > 0) {
		// Mapping has already been added
	} else {
		// Add mapping for virtual machine, so you can go to $projectname.local
		$oldstr = "# Laz added sites";
		$newstr = "# Laz added sites
192.168.10.10  homestead.app $projectname.local";
		$new_hosts = str_replace($oldstr, $newstr, $hosts);
		file_put_contents("/etc/hosts", $new_hosts);
	}
	// Restart virtual machine
	//shell_exec("")
}

function addHomesteadMapping($projectname) {
	$homestead_yaml = file_get_contents("../homestead/Homestead.yaml");
	if (strpos($homestead_yaml, $projectname.".local") > 0) {
		// Mapping has already been added
	} else {
		// Add mapping for virtual machine, so you can go to $projectname.local
		$oldstr = "sites:";
		$newstr = "sites:".PHP_EOL."    - map: $projectname.local".PHP_EOL."     to: /home/vagrant/Code/experiments/$projectname/public";
		$new_yaml = str_replace($oldstr, $newstr, $homestead_yaml);
		file_put_contents("../homestead/Homestead.yaml", $new_yaml);
	}
}

function loopOnViewsRoutes($routes_array, $singular_array) {
	$oldroutes = file_get_contents("./app/Http/routes.php");
	global $fullpath, $startpath;

	$routestr = "";
	$controllerstr = "";
	foreach ($routes_array as $route => $view) {
		$singular = $singular_array[$view];
		$capsview = ucfirst($view);
		shell_exec("php artisan make:controller ".$capsview."Controller");

		$thiscontroller = file_get_contents($fullpath."/app/Http/Controllers/".$capsview."Controller.php");
		$newcontroller = str_replace("use App\Http\Controllers\Controller;", 'use App\Http\Controllers\Controller;
use App\\'.$singular.";", $thiscontroller);
		file_put_contents($fullpath."/app/Http/Controllers/".$capsview."Controller.php", $newcontroller);
		
		// Adds new main view for each page
		createNewPageView($view);

		// Include resource routes
		$routestr .= "Route::resource('$route', '$capsview"."Controller');\n";

		// Add resource functions
		$controllerpath = "./app/Http/Controllers/".$capsview."Controller.php";
		replaceEmptyFunction($controllerpath, "index",  "$".$view." = ".$singular."::all();
		return view('pages.$view', array('$view' => $".$view."));");
		replaceEmptyFunction($controllerpath, "create", 'return "Create '.$view.'";');
		replaceEmptyFunction($controllerpath, "store",  'return "Store '.$view.'";');
		replaceEmptyFunction($controllerpath, "show",   'return "'.$view.' $id";');
		replaceEmptyFunction($controllerpath, "edit",   'return "Edit '.$view.' $id";');
		replaceEmptyFunction($controllerpath, "update", 'return "Update '.$view.' $id";');
		replaceEmptyFunction($controllerpath, "destroy",'return "Destroy '.$view.' $id";');
	}

	$replace = "Route::get('home', 'HomeController@index');";
	$newroutes = str_replace($replace, $replace."\n".$routestr, $oldroutes);
	file_put_contents("./app/Http/routes.php", $newroutes);

	

}
function createNavigationFile($navs) {
	$navstr = "";
	foreach ($navs as $name => $path) {
		$navstr .= "<li><a href='/$path'>$name</a></li>\n";
	}
	file_put_contents("./resources/templates/sections/navigation.blade.php", $navstr);
}
function createNewPageView($viewname) {
	$uppercase = ucwords($viewname);
	$baseview = file_get_contents("./resources/templates/pages/all.blade.php");
	$oldstr = "[TITLE]";
	$newstr = $uppercase;
	$newview = str_replace($oldstr, $newstr, $baseview);
	$oldstr = "[CONTENT]";
	$newstr = '<ul>
						@foreach ($'.$viewname.' as $obj)
						<li><a href="/'.$viewname.'/{{$obj->id}}">{{ $obj->name() }}</a></li>
						@endforeach
					</ul>';
	$newview = str_replace($oldstr, $newstr, $newview);
	file_put_contents("./resources/templates/pages/".$viewname.".blade.php", $newview);
}
function createNewView($viewname) {
	$baseview = file_get_contents("./resources/templates/pages/all.blade.php");
	$oldstr = "@section('title')";
	$newstr = "@section('title')
	$viewname";
	$newview = str_replace($oldstr, $newstr, $baseview);
	$oldstr = "@section('content')";
	$newstr = "@section('content')
	$viewname";
	$newview = str_replace($oldstr, $newstr, $newview);
	file_put_contents("./resources/templates/".$viewname.".blade.php", $newview);
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

function createArrayFromFile($projectfile) {
	$filetext = file_get_contents("./$projectfile");
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
	    if ($tabcount == 1) {
	    	if ($tabtext != '') {
		    	$finalarray[$tabtext] = [];
		    	$currhead = $tabtext;
		    }
	    } else if ($tabcount == 2) {
	    	$finalarray[$currhead][$tabtext] = [];
	    	$currsubhead = $tabtext;
	    } else if ($tabcount == 3) {
	    	// End subarray
	    	$finalarray[$currhead][$currsubhead][] = $tabtext;
	    }
	}
	//print_r($finalarray);
	return $finalarray;
}
function addFieldArrayToCreateSchema($edit_table, $field_array) {
	global $startdirectory, $fullpath;
	$str = "";
	foreach ($field_array as $field_vals) {
		
		$type = $field_vals['type'];
		$name = $field_vals['name'];
		$secondarytable = $field_vals['secondary'];
		$after_function = "";

		if ($type == 'integer') {
			$type = 'unsignedInteger';
		}

		$str .= '$table'."->$type('$name')$after_function;
			";

		// if ($secondarytable) {
		// 	$str .= '$table'."->foreign('$name')
		// 			->references('id')->on('$secondarytable');
  //     		";
		// }
	}
	$glob_arr = glob($fullpath."/database/migrations/*_create_".$edit_table."_table.php");
	if (count($glob_arr) > 0) {
		$filename = $glob_arr[0];
		$startmigration = file_get_contents($filename);
		$oldstr = '$table->timestamps();';
		$newfile = str_replace($oldstr, $str.$oldstr, $startmigration);
		file_put_contents($filename, $newfile);
	}
}


?>