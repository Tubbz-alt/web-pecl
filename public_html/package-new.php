<?php

require_once "HTML/Form.php";

auth_require();

$display_form = true;
$width = 60;
$errorMsg = "";
$jumpto = "name";

do {
    if (isset($submit)) {
	$required = array("name" => "enter the package name",
			  "summary" => "enter the one-liner description",
			  "desc" => "enter the full description",
			  "license" => "choose a license type",
			  "category" => "choose a category");
	foreach ($required as $field => $_desc) {
	    if (empty($$field)) {
		display_error("Please $_desc!");
		$jumpto = $field;
		break 2;
	    }
	}

	if (!preg_match('/^[A-Z][a-zA-Z0-9_]+$/', $name)) {
	    display_error("Invalid package name, must start with a ".
			  "capital letter and contain only letters, ".
			  "digits and underscores.");
	    break;
	}

	$dbh->expectError(DB_ERROR_ALREADY_EXISTS);
	$pkg = add_package(array(
	    'name'        => $name,
	    'category'    => $category,
	    'license'     => $license,
	    'summary'     => $summary,
	    'description' => $desc,
	));
	$dbh->popExpect();
	if (DB::isError($pkg) && $pkg->getCode() == DB_ERROR_ALREADY_EXISTS) {
	    error_handler("The `$name' package already exists!",
			  "Package already exists");
	    exit;
	}
	$err = add_maintainer($pkg, $PHP_AUTH_USER, 'lead');
	$display_form = false;
	response_header("Package Registered");
	print "The package `$name' has been registered in PEAR.<br />\n";
	print "You have been assigned as lead developer.<br />\n";
    }
} while (false);

if ($display_form) {
    $title = "New Package";
    response_header($title);

    print "<h1>$title</h1>

Use this form to register a new package.

<p />

<b>Before proceeding</b>, make sure you pick the right name for your
package.  This is usually done through \"community consensus\", which
means posting a suggestion to the pear-dev mailing list and have
people agree with you.

<p />

Note that if you don't follow this simple rule and break
established naming conventions, your package will be taken hostage.
So please play nice, that way we can keep the bureaucracy at a
minimum.

";

    if (isset($errorMsg)) {
	print "<TABLE>\n";
	print " <TR>\n";
	print "  <TD>&nbsp;</TD>\n";
	print "  <TD><B>$errorMsg</B></TD>\n";
	print " </TR>\n";
	print "</TABLE>\n";
    }

    $categories = $dbh->getAssoc("SELECT id,name FROM categories");
    $form =& new HTML_Form($PHP_SELF, "POST");
    $form->addText("name", "Package Name", null, 20);
    $form->addText("license", "License", null, 20);
    $form->addSelect("category", "Category", $categories, '', 1,
		     '--Select Category--');
    $form->addText("summary", "One-liner description", null, $width);
    $form->addTextarea("desc", "Full description", null, $width, 3);
    $form->addSubmit("submit", "Submit Request");

    $form->display();

    if ($jumpto) {
	print "\n<SCRIPT LANGUAGE=\"JavaScript\">\n<!--\n";
	print "document.forms[0].$jumpto.focus();\n";
	print "// -->\n</SCRIPT>\n";
    }
}

response_footer();

function display_error($msg)
{
    global $errorMsg;

    $errorMsg .= "<FONT COLOR=\"#CC0000\" SIZE=\"+1\">$msg</FONT><BR>\n";
}

?>