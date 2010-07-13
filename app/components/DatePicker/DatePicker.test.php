<?php

/**
 * Nette\Extras DatePicker with jQuery example
 */


require_once LIBS_DIR . '/Nette/loader.php';
require_once LIBS_DIR . '/Extras/DatePicker.php';

Debug::enable();

// budoucí metoda Form::addDatePicker()
function Form_addDatePicker(Form $_this, $name, $label, $cols = NULL, $maxLength = NULL)
{
	return $_this[$name] = new DatePicker($label, $cols, $maxLength);
}


Form::extensionMethod('Form::addDatePicker', 'Form_addDatePicker'); // v PHP 5.2
//Form::extensionMethod('addDatePicker', 'Form_addDatePicker'); // v PHP 5.3


// Step 1: Define form with validation rules
$form = new Form;
$form->addDatePicker('datum', 'Kdy to bude?', 10)
	->addRule(Form::FILLED, 'Zadejte prosím datum.');

$form->addSubmit('submit_date', 'Odešli');

// Step 2: Check if form was submitted?
if ($form->isSubmitted()) {

	// Step 2c: Check if form is valid
	if ($form->isValid()) {
		echo '<h2>Form was submitted and successfully validated</h2>';

		$values = $form->getValues();
		Debug::dump($values);

		// this is the end, my friend :-)
		if (empty($disableExit)) exit;
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="content-language" content="en" />

	<title>Nette\Extras DatePicker with jQuery example UI 1.7 example</title>
	<link type="text/css" href="http://jqueryui.com/latest/themes/base/ui.all.css" rel="stylesheet" />
	
	<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
	<script type="text/javascript" src="http://jqueryui.com/latest/ui/ui.core.js"></script>
	<script type="text/javascript" src="http://jqueryui.com/latest/ui/ui.datepicker.js"></script>
	<script type="text/javascript" src="http://jqueryui.com/latest/ui/i18n/ui.datepicker-cs.js"></script>
		
	<script type="text/javascript">
	$(document).ready(function(){
		$('input.datepicker').datepicker({ duration: 'fast' });
	});
	</script>
	
	<style type="text/css">
	<!--
	div.ui-datepicker {
		font-size: 60%;
	}
	
	input.datepicker {
		background: transparent url('calendar.png') no-repeat right;
		border: 1px solid #CCCCCC;
		padding-right: 20px;
		padding: 0.2em
	}
	
	input.button {
		font-size: 90%;
	}

	.required {
		color: darkred
	}

	fieldset {
		padding: .5em;
		margin: .3em 0;
		background: #EAF3FA;
		border: 1px solid #b2d1eb;
	}

	th {
		width: 8em;
		text-align: right;
	}
	-->
	</style>

</head>
<body>

	<h1>Nette\Extras DatePicker with jQuery UI 1.7 example</h1>

	<?php echo $form ?>

</body>
</html>