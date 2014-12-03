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

	// ===================================> LOCAL HOST
	//addHostsMapping($projectname);
	// ===================================> HOMESTEAD
	//addHomesteadMapping($projectname);

	// ===================================> CREATE DIRECTORIES
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



	// ===================================> ROUTES / SPECIFIC VIEWS
	$routes = [];
	foreach ($project_array['OBJECTS'] as $object => $fields) {
		$object = explode(" ", $object)[0];
		$routes[$object] = $object;
	}
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
		//$oldcontroller = file_get_contents("./app/Http/Controllers/".$capsview."Controller.php");
		
		createNewPageView($view);
		$routestr .= "get('$route', '$capsview"."Controller@index');
get('$route/{id}', '$capsview"."Controller@show');
";
		$temppath = "./app/Http/Controllers/".$capsview."Controller.php";
		replaceEmptyFunction($temppath, "index", "return view('pages.$view');");
		replaceEmptyFunction($temppath, "show", 'return "'.$view.' $id";');
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