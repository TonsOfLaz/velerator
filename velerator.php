<?php 

if (isset($argv[1]) && isset($argv[2])) {

	createProject($argv[1], $argv[2]);

} else {
	echo "Please add a project name and genfile, i.e.: \nphp generator.php myproject mygenfile.txt";
}

function createProject ($projectname, $projectfile) {

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
	//shell_exec("composer require fzaninotto/faker");

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
	$local_db_config = file_get_contents($fullpath."/config/local/database.php");
	$new_db_config = str_replace("localhost", "127.0.0.1:33060", $local_db_config);
	file_put_contents($fullpath."/config/local/database.php", $new_db_config);

	// ===================================> MIGRATIONS
	echo "Creating migrations...\n";
	foreach ($singular_objects as $object => $singular) {
		shell_exec("php artisan make:migration --create=$object create_".$object."_table");
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

	echo "Running seeders...\n";
	//shell_exec("php artisan db:seed");


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


?>