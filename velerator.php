<?php 

global $fullpath,$startdirectory;

if (isset($argv[1]) && isset($argv[2])) {

	createProject($argv[1], $argv[2]);

} else {
	echo "Please add a project name and genfile, i.e.: \nphp generator.php myproject mygenfile.txt";
}

function createProject ($projectname, $projectfile) {

	global $startdirectory, $fullpath;

	$startdirectory = getcwd();
	$fullpath = $startdirectory."/".$projectname;

	$project_array = createArrayFromFile($projectfile);

	// ===================================> CLEARING OLD VERSION
	if (is_dir($fullpath)) {
		echo "Clearing existing directory...\n";
		shell_exec("rm -rf ".$fullpath);
		echo "Done deleting $projectname\n";
	} 

	// ===================================> LARAVEL
	echo "Creating Laravel project...\n";
	//shell_exec("laravel new ".$projectname);
	shell_exec("composer --no-interaction create-project laravel/laravel $projectname dev-develop");
	echo "Laravel project $projectname created.\n";

	// ===================================> MOVE DIRECTORY INTO APP
	echo "Entering directory $fullpath ...\n";
	chdir($fullpath);

	// ===================================> GIT INIT/FIRST COMMIT
	chdir($fullpath);
	echo "Creating git object...\n";
	shell_exec("git init");
	echo "First git commit...\n";
	shell_exec("git add .");  
	shell_exec("git commit -am 'First git commit for empty project $projectname, before Velerator changes'");
	echo "Added first git commit.\n";

	// ===================================> ADD COMPOSER TOOLS
	echo "Adding 'Faker' tool...\n";
	shell_exec("composer require fzaninotto/faker");

	// ===================================> LOCAL HOST
	//addHostsMapping($projectname);
	// ===================================> HOMESTEAD
	//addHomesteadMapping($projectname);

	// ===================================> CREATE DIRECTORIES
	echo "Creating directories...\n";
	mkdir($fullpath."/resources/views/sections");
	mkdir($fullpath."/resources/views/pages");

	// ===================================> COPY GENERIC FILES
	// Add home, navigation buttons, header, footer views
	// Pull files from folder
	copy($startdirectory."/velerator_files/views/master.blade.php", $fullpath."/resources/views/master.blade.php");
	copy($startdirectory."/velerator_files/views/page.blade.php", $fullpath."/resources/views/pages/page.blade.php");
	copy($startdirectory."/velerator_files/views/header.blade.php", $fullpath."/resources/views/sections/header.blade.php");
	copy($startdirectory."/velerator_files/views/footer.blade.php", $fullpath."/resources/views/sections/footer.blade.php");
	copy($startdirectory."/velerator_files/.env", $fullpath."/.env");



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
	loopOnViewsRoutes($routes);

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
	$local_db_config = file_get_contents($fullpath."/config/database.php");
	$new_db_config = str_replace("'host'      => 'localhost'", "'host'      => ".'$_ENV'."['DB_HOST']", $local_db_config);
	$new_db_config = str_replace("'database'  => 'forge'", "'database'  => ".'$_ENV'."['DB_DATABASE']", $new_db_config);
	$new_db_config = str_replace("'username'  => 'forge'", "'username'  => ".'$_ENV'."['DB_USERNAME']", $new_db_config);
	$new_db_config = str_replace("'password'  => ''", "'password'  => ".'$_ENV'."['DB_PASSWORD']", $new_db_config);
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

		// Create Model
		$generic_model = file_get_contents($startdirectory."/velerator_files/Model.php");
		$newmodel = str_replace("[NAME]", $singular, $generic_model);
		$newmodel = str_replace("[TABLE]", $object, $newmodel);
		$newmodel = str_replace("[FILLABLE_ARRAY]", "[$fillable_str]", $newmodel);
		$newmodel = str_replace("[HIDDEN_ARRAY]", '[]', $newmodel);
		file_put_contents($fullpath."/app/".$singular.".php", $newmodel);
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

function loopOnViewsRoutes($routes_array) {
	$oldroutes = file_get_contents("./app/Http/routes.php");
	

	$routestr = "";
	$controllerstr = "";
	foreach ($routes_array as $route => $view) {
		$capsview = ucfirst($view);
		shell_exec("php artisan make:controller ".$capsview."Controller");
		
		// Adds new main view for each page
		createNewPageView($view);

		// Include resource routes
		$routestr .= '$router'."->resource('$route', '$capsview"."Controller');\n";

		// Add resource functions
		$controllerpath = "./app/Http/Controllers/".$capsview."Controller.php";
		replaceEmptyFunction($controllerpath, "index",  "return view('pages.$view');");
		replaceEmptyFunction($controllerpath, "create", 'return "Create '.$view.'";');
		replaceEmptyFunction($controllerpath, "store",  'return "Store '.$view.'";');
		replaceEmptyFunction($controllerpath, "show",   'return "'.$view.' $id";');
		replaceEmptyFunction($controllerpath, "edit",   'return "Edit '.$view.' $id";');
		replaceEmptyFunction($controllerpath, "update", 'return "Update '.$view.' $id";');
		replaceEmptyFunction($controllerpath, "destroy",'return "Destroy '.$view.' $id";');
	}

	$replace = '$router'."->get('/', 'WelcomeController@index');";
	$newroutes = str_replace($replace, $replace."\n".$routestr, $oldroutes);
	file_put_contents("./app/Http/routes.php", $newroutes);

	

}
function createNavigationFile($navs) {
	$navstr = "";
	foreach ($navs as $name => $path) {
		$navstr .= "<a href='/$path'>$name</a>";
	}
	file_put_contents("./resources/views/sections/navigation.blade.php", $navstr);
}
function createNewPageView($viewname) {
	$baseview = file_get_contents("./resources/views/pages/page.blade.php");
	$oldstr = "@section('title')";
	$newstr = "@section('title')
	$viewname";
	$newview = str_replace($oldstr, $newstr, $baseview);
	$oldstr = "@section('content')";
	$newstr = "@section('content')
	$viewname";
	$newview = str_replace($oldstr, $newstr, $newview);
	file_put_contents("./resources/views/pages/".$viewname.".blade.php", $newview);
}
function createNewView($viewname) {
	$baseview = file_get_contents("./resources/views/pages/page.blade.php");
	$oldstr = "@section('title')";
	$newstr = "@section('title')
	$viewname";
	$newview = str_replace($oldstr, $newstr, $baseview);
	$oldstr = "@section('content')";
	$newstr = "@section('content')
	$viewname";
	$newview = str_replace($oldstr, $newstr, $newview);
	file_put_contents("./resources/views/".$viewname.".blade.php", $newview);
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