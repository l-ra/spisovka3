<?php

/**
 * Migration tool from 5.2 to 5.3.
 *
 * This file is part of the Nette Framework (http://nette.org)
 */

if (@!include __DIR__ . '/../vendor/nette/nette/Nette/loader.php') {
	die('Install packages using `composer update`');
}

ini_set('memory_limit', '300M');

use Nette\Utils\Tokenizer;


$options = getopt('d:f');

if (!$options) { ?>

Migrate53 helps you with the migration from PHP 5.2 to PHP 5.3 package
of Nette Framework. It prepends namespaces to Nette Framework class names
used in your source code.

Usage: php migrate-53.php [options]

Options:
	-d <path>  folder to scan (optional)
	-f         fixes files

<?php
}



class ClassUpdater extends Nette\Object
{
	public $readOnly = FALSE;

	public $replaces = array(
		'Nette_MicroPresenter' => 'NetteModule\MicroPresenter',
		'NAbortException' => 'Nette\Application\AbortException',
		'AbortException' => 'Nette\Application\AbortException',
		'Application' => 'Nette\Application\Application',
		'NApplication' => 'Nette\Application\Application',
		'NApplicationException' => 'Nette\Application\ApplicationException',
		'ApplicationException' => 'Nette\Application\ApplicationException',
		'NBadRequestException' => 'Nette\Application\BadRequestException',
		'BadRequestException' => 'Nette\Application\BadRequestException',
		'RoutingDebugger' => 'Nette\Application\Diagnostics\RoutingPanel',
		'NRoutingDebugger' => 'Nette\Application\Diagnostics\RoutingPanel',
		'NForbiddenRequestException' => 'Nette\Application\ForbiddenRequestException',
		'ForbiddenRequestException' => 'Nette\Application\ForbiddenRequestException',
		'IPresenter' => 'Nette\Application\IPresenter',
		'IPresenterFactory' => 'Nette\Application\IPresenterFactory',
		'IRequest' => 'Nette\Application\IRequest',
		'IPresenterResponse' => 'Nette\Application\IResponse',
		'IRouter' => 'Nette\Application\IRouter',
		'InvalidPresenterException' => 'Nette\Application\InvalidPresenterException',
		'NInvalidPresenterException' => 'Nette\Application\InvalidPresenterException',
		'NPresenterFactory' => 'Nette\Application\PresenterFactory',
		'PresenterFactory' => 'Nette\Application\PresenterFactory',
		'NRecursiveIteratorIterator' => 'Nette\Application\RecursiveIteratorIterator',
		'PresenterRequest' => 'Nette\Application\Request',
		'NPresenterRequest' => 'Nette\Application\Request',
		'NFileResponse' => 'Nette\Application\Responses\FileResponse',
		'FileResponse' => 'Nette\Application\Responses\FileResponse',
		'ForwardResponse' => 'Nette\Application\Responses\ForwardResponse',
		'NForwardResponse' => 'Nette\Application\Responses\ForwardResponse',
		'JsonResponse' => 'Nette\Application\Responses\JsonResponse',
		'NJsonResponse' => 'Nette\Application\Responses\JsonResponse',
		'RedirectResponse' => 'Nette\Application\Responses\RedirectResponse',
		'NRedirectResponse' => 'Nette\Application\Responses\RedirectResponse',
		'NTextResponse' => 'Nette\Application\Responses\TextResponse',
		'TextResponse' => 'Nette\Application\Responses\TextResponse',
		'NCliRouter' => 'Nette\Application\Routers\CliRouter',
		'CliRouter' => 'Nette\Application\Routers\CliRouter',
		'Route' => 'Nette\Application\Routers\Route',
		'NRoute' => 'Nette\Application\Routers\Route',
		'RouteList' => 'Nette\Application\Routers\RouteList',
		'NRouteList' => 'Nette\Application\Routers\RouteList',
		'SimpleRouter' => 'Nette\Application\Routers\SimpleRouter',
		'NSimpleRouter' => 'Nette\Application\Routers\SimpleRouter',
		'BadSignalException' => 'Nette\Application\UI\BadSignalException',
		'NBadSignalException' => 'Nette\Application\UI\BadSignalException',
		'NControl' => 'Nette\Application\UI\Control',
		'Control' => 'Nette\Application\UI\Control',
		'AppForm' => 'Nette\Application\UI\Form',
		'NAppForm' => 'Nette\Application\UI\Form',
		'IRenderable' => 'Nette\Application\UI\IRenderable',
		'ISignalReceiver' => 'Nette\Application\UI\ISignalReceiver',
		'IStatePersistent' => 'Nette\Application\UI\IStatePersistent',
		'NInvalidLinkException' => 'Nette\Application\UI\InvalidLinkException',
		'InvalidLinkException' => 'Nette\Application\UI\InvalidLinkException',
		'Link' => 'Nette\Application\UI\Link',
		'NLink' => 'Nette\Application\UI\Link',
		'NMultiplier' => 'Nette\Application\UI\Multiplier',
		'Multiplier' => 'Nette\Application\UI\Multiplier',
		'Presenter' => 'Nette\Application\UI\Presenter',
		'NPresenter' => 'Nette\Application\UI\Presenter',
		'NPresenterComponent' => 'Nette\Application\UI\PresenterComponent',
		'PresenterComponent' => 'Nette\Application\UI\PresenterComponent',
		'NPresenterComponentReflection' => 'Nette\Application\UI\PresenterComponentReflection',
		'PresenterComponentReflection' => 'Nette\Application\UI\PresenterComponentReflection',
		'NArrayHash' => 'Nette\ArrayHash',
		'ArrayHash' => 'Nette\ArrayHash',
		'ArrayList' => 'Nette\ArrayList',
		'NArrayList' => 'Nette\ArrayList',
		'Cache' => 'Nette\Caching\Cache',
		'NCache' => 'Nette\Caching\Cache',
		'ICacheStorage' => 'Nette\Caching\IStorage',
		'NCachingHelper' => 'Nette\Caching\OutputHelper',
		'CachingHelper' => 'Nette\Caching\OutputHelper',
		'DevNullStorage' => 'Nette\Caching\Storages\DevNullStorage',
		'NDevNullStorage' => 'Nette\Caching\Storages\DevNullStorage',
		'NFileJournal' => 'Nette\Caching\Storages\FileJournal',
		'FileJournal' => 'Nette\Caching\Storages\FileJournal',
		'NFileStorage' => 'Nette\Caching\Storages\FileStorage',
		'FileStorage' => 'Nette\Caching\Storages\FileStorage',
		'ICacheJournal' => 'Nette\Caching\Storages\IJournal',
		'MemcachedStorage' => 'Nette\Caching\Storages\MemcachedStorage',
		'NMemcachedStorage' => 'Nette\Caching\Storages\MemcachedStorage',
		'MemoryStorage' => 'Nette\Caching\Storages\MemoryStorage',
		'NMemoryStorage' => 'Nette\Caching\Storages\MemoryStorage',
		'NPhpFileStorage' => 'Nette\Caching\Storages\PhpFileStorage',
		'PhpFileStorage' => 'Nette\Caching\Storages\PhpFileStorage',
		'NCallback' => 'Nette\Callback',
		'Callback' => 'Nette\Callback',
		'NComponent' => 'Nette\ComponentModel\Component',
		'Component' => 'Nette\ComponentModel\Component',
		'ComponentContainer' => 'Nette\ComponentModel\Container',
		'NComponentContainer' => 'Nette\ComponentModel\Container',
		'IComponent' => 'Nette\ComponentModel\IComponent',
		'IComponentContainer' => 'Nette\ComponentModel\IContainer',
		'NRecursiveComponentIterator' => 'Nette\ComponentModel\RecursiveComponentIterator',
		'RecursiveComponentIterator' => 'Nette\ComponentModel\RecursiveComponentIterator',
		'NConfigIniAdapter' => 'Nette\DI\Config\Adapters\IniAdapter',
		'ConfigIniAdapter' => 'Nette\DI\Config\Adapters\IniAdapter',
		'NConfigNeonAdapter' => 'Nette\DI\Config\Adapters\NeonAdapter',
		'ConfigNeonAdapter' => 'Nette\DI\Config\Adapters\NeonAdapter',
		'ConfigPhpAdapter' => 'Nette\DI\Config\Adapters\PhpAdapter',
		'NConfigPhpAdapter' => 'Nette\DI\Config\Adapters\PhpAdapter',
		'NConfigCompiler' => 'Nette\DI\Compiler',
		'ConfigCompiler' => 'Nette\DI\Compiler',
		'ConfigCompilerExtension' => 'Nette\DI\CompilerExtension',
		'NConfigCompilerExtension' => 'Nette\DI\CompilerExtension',
		'Configurator' => 'Nette\Configurator',
		'NConfigurator' => 'Nette\Configurator',
		'ConstantsExtension' => 'Nette\DI\Extensions\ConstantsExtension',
		'NConstantsExtension' => 'Nette\DI\Extensions\ConstantsExtension',
		'NetteExtension' => 'Nette\DI\Extensions\NetteExtension',
		'NNetteExtension' => 'Nette\DI\Extensions\NetteExtension',
		'NPhpExtension' => 'Nette\DI\Extensions\PhpExtension',
		'PhpExtension' => 'Nette\DI\Extensions\PhpExtension',
		'NConfigHelpers' => 'Nette\DI\Config\Helpers',
		'ConfigHelpers' => 'Nette\DI\Config\Helpers',
		'IConfigAdapter' => 'Nette\DI\Config\IAdapter',
		'NConfigLoader' => 'Nette\DI\Config\Loader',
		'ConfigLoader' => 'Nette\DI\Config\Loader',
		'DIContainer' => 'Nette\DI\Container',
		'NDIContainer' => 'Nette\DI\Container',
		'NDIContainerBuilder' => 'Nette\DI\ContainerBuilder',
		'DIContainerBuilder' => 'Nette\DI\ContainerBuilder',
		'ContainerPanel' => 'Nette\DI\Diagnostics\ContainerPanel',
		'NContainerPanel' => 'Nette\DI\Diagnostics\ContainerPanel',
		'NDIHelpers' => 'Nette\DI\Helpers',
		'DIHelpers' => 'Nette\DI\Helpers',
		'IDIContainer' => 'Nette\DI\IContainer',
		'NMissingServiceException' => 'Nette\DI\MissingServiceException',
		'MissingServiceException' => 'Nette\DI\MissingServiceException',
		'DINestedAccessor' => 'Nette\DI\NestedAccessor',
		'NDINestedAccessor' => 'Nette\DI\NestedAccessor',
		'ServiceCreationException' => 'Nette\DI\ServiceCreationException',
		'NServiceCreationException' => 'Nette\DI\ServiceCreationException',
		'NDIServiceDefinition' => 'Nette\DI\ServiceDefinition',
		'DIServiceDefinition' => 'Nette\DI\ServiceDefinition',
		'NDIStatement' => 'Nette\DI\Statement',
		'DIStatement' => 'Nette\DI\Statement',
		'Connection' => 'Nette\Database\Connection',
		'NConnection' => 'Nette\Database\Connection',
		'NDatabasePanel' => 'Nette\Database\Diagnostics\ConnectionPanel',
		'DatabasePanel' => 'Nette\Database\Diagnostics\ConnectionPanel',
		'NMsSqlDriver' => 'Nette\Database\Drivers\MsSqlDriver',
		'MsSqlDriver' => 'Nette\Database\Drivers\MsSqlDriver',
		'MySqlDriver' => 'Nette\Database\Drivers\MySqlDriver',
		'NMySqlDriver' => 'Nette\Database\Drivers\MySqlDriver',
		'NOciDriver' => 'Nette\Database\Drivers\OciDriver',
		'OciDriver' => 'Nette\Database\Drivers\OciDriver',
		'OdbcDriver' => 'Nette\Database\Drivers\OdbcDriver',
		'NOdbcDriver' => 'Nette\Database\Drivers\OdbcDriver',
		'PgSqlDriver' => 'Nette\Database\Drivers\PgSqlDriver',
		'NPgSqlDriver' => 'Nette\Database\Drivers\PgSqlDriver',
		'NSqlite2Driver' => 'Nette\Database\Drivers\Sqlite2Driver',
		'Sqlite2Driver' => 'Nette\Database\Drivers\Sqlite2Driver',
		'NSqliteDriver' => 'Nette\Database\Drivers\SqliteDriver',
		'SqliteDriver' => 'Nette\Database\Drivers\SqliteDriver',
		'DatabaseHelpers' => 'Nette\Database\Helpers',
		'NDatabaseHelpers' => 'Nette\Database\Helpers',
		'IReflection' => 'Nette\Database\IReflection',
		'ISupplementalDriver' => 'Nette\Database\ISupplementalDriver',
		'AmbiguousReferenceKeyException' => 'Nette\Database\Reflection\AmbiguousReferenceKeyException',
		'NAmbiguousReferenceKeyException' => 'Nette\Database\Reflection\AmbiguousReferenceKeyException',
		'NConventionalReflection' => 'Nette\Database\Reflection\ConventionalReflection',
		'ConventionalReflection' => 'Nette\Database\Reflection\ConventionalReflection',
		'DiscoveredReflection' => 'Nette\Database\Reflection\DiscoveredReflection',
		'NDiscoveredReflection' => 'Nette\Database\Reflection\DiscoveredReflection',
		'MissingReferenceException' => 'Nette\Database\Reflection\MissingReferenceException',
		'NMissingReferenceException' => 'Nette\Database\Reflection\MissingReferenceException',
		'Row' => 'Nette\Database\Row',
		'NRow' => 'Nette\Database\Row',
		'SqlLiteral' => 'Nette\Database\SqlLiteral',
		'NSqlLiteral' => 'Nette\Database\SqlLiteral',
		'SqlPreprocessor' => 'Nette\Database\SqlPreprocessor',
		'NSqlPreprocessor' => 'Nette\Database\SqlPreprocessor',
		'NStatement' => 'Nette\Database\Statement',
		'Statement' => 'Nette\Database\Statement',
		'TableRow' => 'Nette\Database\Table\ActiveRow',
		'NTableRow' => 'Nette\Database\Table\ActiveRow',
		'NGroupedTableSelection' => 'Nette\Database\Table\GroupedSelection',
		'GroupedTableSelection' => 'Nette\Database\Table\GroupedSelection',
		'NTableSelection' => 'Nette\Database\Table\Selection',
		'TableSelection' => 'Nette\Database\Table\Selection',
		'SqlBuilder' => 'Nette\Database\Table\SqlBuilder',
		'NSqlBuilder' => 'Nette\Database\Table\SqlBuilder',
		'NDateTime53' => 'Nette\DateTime',
		'DateTime53' => 'Nette\DateTime',
		'DeprecatedException' => 'Nette\DeprecatedException',
		'NDebugBar' => 'Nette\Diagnostics\Bar',
		'DebugBar' => 'Nette\Diagnostics\Bar',
		'DebugBlueScreen' => 'Nette\Diagnostics\BlueScreen',
		'NDebugBlueScreen' => 'Nette\Diagnostics\BlueScreen',
		'NDebugger' => 'Nette\Diagnostics\Debugger',
		'Debugger' => 'Nette\Diagnostics\Debugger',
		'DefaultBarPanel' => 'Nette\Diagnostics\DefaultBarPanel',
		'NDefaultBarPanel' => 'Nette\Diagnostics\DefaultBarPanel',
		'NFireLogger' => 'Nette\Diagnostics\FireLogger',
		'FireLogger' => 'Nette\Diagnostics\FireLogger',
		'DebugHelpers' => 'Nette\Diagnostics\Helpers',
		'NDebugHelpers' => 'Nette\Diagnostics\Helpers',
		'IBarPanel' => 'Nette\Diagnostics\IBarPanel',
		'Logger' => 'Nette\Diagnostics\Logger',
		'NLogger' => 'Nette\Diagnostics\Logger',
		'DirectoryNotFoundException' => 'Nette\DirectoryNotFoundException',
		'Environment' => 'Nette\Environment',
		'NEnvironment' => 'Nette\Environment',
		'FileNotFoundException' => 'Nette\FileNotFoundException',
		'NFormContainer' => 'Nette\Forms\Container',
		'FormContainer' => 'Nette\Forms\Container',
		'NFormGroup' => 'Nette\Forms\ControlGroup',
		'FormGroup' => 'Nette\Forms\ControlGroup',
		'FormControl' => 'Nette\Forms\Controls\BaseControl',
		'NFormControl' => 'Nette\Forms\Controls\BaseControl',
		'NButton' => 'Nette\Forms\Controls\Button',
		'Button' => 'Nette\Forms\Controls\Button',
		'Checkbox' => 'Nette\Forms\Controls\Checkbox',
		'NCheckbox' => 'Nette\Forms\Controls\Checkbox',
		'HiddenField' => 'Nette\Forms\Controls\HiddenField',
		'NHiddenField' => 'Nette\Forms\Controls\HiddenField',
		'ImageButton' => 'Nette\Forms\Controls\ImageButton',
		'NImageButton' => 'Nette\Forms\Controls\ImageButton',
		'NMultiSelectBox' => 'Nette\Forms\Controls\MultiSelectBox',
		'MultiSelectBox' => 'Nette\Forms\Controls\MultiSelectBox',
		'RadioList' => 'Nette\Forms\Controls\RadioList',
		'NRadioList' => 'Nette\Forms\Controls\RadioList',
		'NSelectBox' => 'Nette\Forms\Controls\SelectBox',
		'SelectBox' => 'Nette\Forms\Controls\SelectBox',
		'SubmitButton' => 'Nette\Forms\Controls\SubmitButton',
		'NSubmitButton' => 'Nette\Forms\Controls\SubmitButton',
		'TextArea' => 'Nette\Forms\Controls\TextArea',
		'NTextArea' => 'Nette\Forms\Controls\TextArea',
		'NTextBase' => 'Nette\Forms\Controls\TextBase',
		'TextBase' => 'Nette\Forms\Controls\TextBase',
		'TextInput' => 'Nette\Forms\Controls\TextInput',
		'NTextInput' => 'Nette\Forms\Controls\TextInput',
		'NUploadControl' => 'Nette\Forms\Controls\UploadControl',
		'UploadControl' => 'Nette\Forms\Controls\UploadControl',
		'NForm' => 'Nette\Forms\Form',
		'Form' => 'Nette\Forms\Form',
		'IFormControl' => 'Nette\Forms\IControl',
		'IFormRenderer' => 'Nette\Forms\IFormRenderer',
		'ISubmitterControl' => 'Nette\Forms\ISubmitterControl',
		'DefaultFormRenderer' => 'Nette\Forms\Rendering\DefaultFormRenderer',
		'NDefaultFormRenderer' => 'Nette\Forms\Rendering\DefaultFormRenderer',
		'NRule' => 'Nette\Forms\Rule',
		'Rule' => 'Nette\Forms\Rule',
		'Rules' => 'Nette\Forms\Rules',
		'NRules' => 'Nette\Forms\Rules',
		'NFramework' => 'Nette\Framework',
		'Framework' => 'Nette\Framework',
		'FreezableObject' => 'Nette\FreezableObject',
		'NFreezableObject' => 'Nette\FreezableObject',
		'NHttpContext' => 'Nette\Http\Context',
		'HttpContext' => 'Nette\Http\Context',
		'HttpUploadedFile' => 'Nette\Http\FileUpload',
		'NHttpUploadedFile' => 'Nette\Http\FileUpload',
		'IHttpRequest' => 'Nette\Http\IRequest',
		'IHttpResponse' => 'Nette\Http\IResponse',
		'ISessionStorage' => 'Nette\Http\ISessionStorage',
		'NHttpRequest' => 'Nette\Http\Request',
		'HttpRequest' => 'Nette\Http\Request',
		'NHttpRequestFactory' => 'Nette\Http\RequestFactory',
		'HttpRequestFactory' => 'Nette\Http\RequestFactory',
		'NHttpResponse' => 'Nette\Http\Response',
		'HttpResponse' => 'Nette\Http\Response',
		'Session' => 'Nette\Http\Session',
		'NSession' => 'Nette\Http\Session',
		'NSessionSection' => 'Nette\Http\SessionSection',
		'SessionSection' => 'Nette\Http\SessionSection',
		'NUrl' => 'Nette\Http\Url',
		'Url' => 'Nette\Http\Url',
		'NUrlScript' => 'Nette\Http\UrlScript',
		'UrlScript' => 'Nette\Http\UrlScript',
		'UserStorage' => 'Nette\Http\UserStorage',
		'NUserStorage' => 'Nette\Http\UserStorage',
		'IFreezable' => 'Nette\IFreezable',
		'IOException' => 'Nette\IOException',
		'Image' => 'Nette\Image',
		'NImage' => 'Nette\Image',
		'InvalidStateException' => 'Nette\InvalidStateException',
		'NSmartCachingIterator' => 'Nette\Iterators\CachingIterator',
		'SmartCachingIterator' => 'Nette\Iterators\CachingIterator',
		'NNCallbackFilterIterator' => 'Nette\Iterators\Filter',
		'NCallbackFilterIterator' => 'Nette\Iterators\Filter',
		'NInstanceFilterIterator' => 'Nette\Iterators\InstanceFilter',
		'InstanceFilterIterator' => 'Nette\Iterators\InstanceFilter',
		'NMapIterator' => 'Nette\Iterators\Mapper',
		'MapIterator' => 'Nette\Iterators\Mapper',
		'NNRecursiveCallbackFilterIterator' => 'Nette\Iterators\RecursiveFilter',
		'NRecursiveCallbackFilterIterator' => 'Nette\Iterators\RecursiveFilter',
		'NGenericRecursiveIterator' => 'Nette\Iterators\Recursor',
		'GenericRecursiveIterator' => 'Nette\Iterators\Recursor',
		'NCompileException' => 'Nette\Latte\CompileException',
		'CompileException' => 'Nette\Latte\CompileException',
		'NLatteCompiler' => 'Nette\Latte\Compiler',
		'LatteCompiler' => 'Nette\Latte\Compiler',
		'LatteFilter' => 'Nette\Latte\Engine',
		'NLatteFilter' => 'Nette\Latte\Engine',
		'NHtmlNode' => 'Nette\Latte\HtmlNode',
		'HtmlNode' => 'Nette\Latte\HtmlNode',
		'IMacro' => 'Nette\Latte\IMacro',
		'NMacroNode' => 'Nette\Latte\MacroNode',
		'MacroNode' => 'Nette\Latte\MacroNode',
		'NMacroTokenizer' => 'Nette\Latte\MacroTokenizer',
		'MacroTokenizer' => 'Nette\Latte\MacroTokenizer',
		'CacheMacro' => 'Nette\Latte\Macros\CacheMacro',
		'NCacheMacro' => 'Nette\Latte\Macros\CacheMacro',
		'CoreMacros' => 'Nette\Latte\Macros\CoreMacros',
		'NCoreMacros' => 'Nette\Latte\Macros\CoreMacros',
		'NFormMacros' => 'Nette\Latte\Macros\FormMacros',
		'FormMacros' => 'Nette\Latte\Macros\FormMacros',
		'MacroSet' => 'Nette\Latte\Macros\MacroSet',
		'NMacroSet' => 'Nette\Latte\Macros\MacroSet',
		'NUIMacros' => 'Nette\Latte\Macros\UIMacros',
		'UIMacros' => 'Nette\Latte\Macros\UIMacros',
		'NLatteException' => 'Nette\Latte\ParseException',
		'LatteException' => 'Nette\Latte\ParseException',
		'Parser' => 'Nette\Latte\Parser',
		'NParser' => 'Nette\Latte\Parser',
		'PhpWriter' => 'Nette\Latte\PhpWriter',
		'NPhpWriter' => 'Nette\Latte\PhpWriter',
		'LatteToken' => 'Nette\Latte\Token',
		'NLatteToken' => 'Nette\Latte\Token',
		'AutoLoader' => 'Nette\Loaders\AutoLoader',
		'NAutoLoader' => 'Nette\Loaders\AutoLoader',
		'NetteLoader' => 'Nette\Loaders\NetteLoader',
		'NNetteLoader' => 'Nette\Loaders\NetteLoader',
		'NRobotLoader' => 'Nette\Loaders\RobotLoader',
		'RobotLoader' => 'Nette\Loaders\RobotLoader',
		'ITranslator' => 'Nette\Localization\ITranslator',
		'IMailer' => 'Nette\Mail\IMailer',
		'NMail' => 'Nette\Mail\Message',
		'Mail' => 'Nette\Mail\Message',
		'NMailMimePart' => 'Nette\Mail\MimePart',
		'MailMimePart' => 'Nette\Mail\MimePart',
		'NSendmailMailer' => 'Nette\Mail\SendmailMailer',
		'SendmailMailer' => 'Nette\Mail\SendmailMailer',
		'SmtpException' => 'Nette\Mail\SmtpException',
		'NSmtpException' => 'Nette\Mail\SmtpException',
		'NSmtpMailer' => 'Nette\Mail\SmtpMailer',
		'SmtpMailer' => 'Nette\Mail\SmtpMailer',
		'Object' => 'Nette\Object',
		'NObject' => 'Nette\Object',
		'NObjectMixin' => 'Nette\ObjectMixin',
		'ObjectMixin' => 'Nette\ObjectMixin',
		'Annotation' => 'Nette\Reflection\Annotation',
		'NAnnotation' => 'Nette\Reflection\Annotation',
		'AnnotationsParser' => 'Nette\Reflection\AnnotationsParser',
		'NAnnotationsParser' => 'Nette\Reflection\AnnotationsParser',
		'NClassReflection' => 'Nette\Reflection\ClassType',
		'ClassReflection' => 'Nette\Reflection\ClassType',
		'ExtensionReflection' => 'Nette\Reflection\Extension',
		'NExtensionReflection' => 'Nette\Reflection\Extension',
		'NFunctionReflection' => 'Nette\Reflection\GlobalFunction',
		'FunctionReflection' => 'Nette\Reflection\GlobalFunction',
		'IAnnotation' => 'Nette\Reflection\IAnnotation',
		'NMethodReflection' => 'Nette\Reflection\Method',
		'MethodReflection' => 'Nette\Reflection\Method',
		'ParameterReflection' => 'Nette\Reflection\Parameter',
		'NParameterReflection' => 'Nette\Reflection\Parameter',
		'NPropertyReflection' => 'Nette\Reflection\Property',
		'PropertyReflection' => 'Nette\Reflection\Property',
		'NAuthenticationException' => 'Nette\Security\AuthenticationException',
		'AuthenticationException' => 'Nette\Security\AuthenticationException',
		'UserPanel' => 'Nette\Security\Diagnostics\UserPanel',
		'NUserPanel' => 'Nette\Security\Diagnostics\UserPanel',
		'IAuthenticator' => 'Nette\Security\IAuthenticator',
		'IAuthorizator' => 'Nette\Security\IAuthorizator',
		'IIdentity' => 'Nette\Security\IIdentity',
		'IResource' => 'Nette\Security\IResource',
		'IRole' => 'Nette\Security\IRole',
		'IUserStorage' => 'Nette\Security\IUserStorage',
		'NIdentity' => 'Nette\Security\Identity',
		'Identity' => 'Nette\Security\Identity',
		'Permission' => 'Nette\Security\Permission',
		'NPermission' => 'Nette\Security\Permission',
		'SimpleAuthenticator' => 'Nette\Security\SimpleAuthenticator',
		'NSimpleAuthenticator' => 'Nette\Security\SimpleAuthenticator',
		'NUser' => 'Nette\Security\User',
		'User' => 'Nette\Security\User',
		'StaticClassException' => 'Nette\StaticClassException',
		'NStaticClassException' => 'Nette\StaticClassException',
		'DefaultHelpers' => 'Nette\Templating\DefaultHelpers',
		'NDefaultHelpers' => 'Nette\Templating\DefaultHelpers',
		'FileTemplate' => 'Nette\Templating\FileTemplate',
		'NFileTemplate' => 'Nette\Templating\FileTemplate',
		'TemplateException' => 'Nette\Templating\FilterException',
		'NTemplateException' => 'Nette\Templating\FilterException',
		'TemplateHelpers' => 'Nette\Templating\Helpers',
		'NTemplateHelpers' => 'Nette\Templating\Helpers',
		'IFileTemplate' => 'Nette\Templating\IFileTemplate',
		'ITemplate' => 'Nette\Templating\ITemplate',
		'NTemplate' => 'Nette\Templating\Template',
		'Template' => 'Nette\Templating\Template',
		'NUnknownImageFileException' => 'Nette\UnknownImageFileException',
		'UnknownImageFileException' => 'Nette\UnknownImageFileException',
		'NArrays' => 'Nette\Utils\Arrays',
		'Arrays' => 'Nette\Utils\Arrays',
		'AssertionException' => 'Nette\Utils\AssertionException',
		'NAssertionException' => 'Nette\Utils\AssertionException',
		'Finder' => 'Nette\Utils\Finder',
		'NFinder' => 'Nette\Utils\Finder',
		'Html' => 'Nette\Utils\Html',
		'NHtml' => 'Nette\Utils\Html',
		'Json' => 'Nette\Utils\Json',
		'NJson' => 'Nette\Utils\Json',
		'NJsonException' => 'Nette\Utils\JsonException',
		'JsonException' => 'Nette\Utils\JsonException',
		'NLimitedScope' => 'Nette\Utils\LimitedScope',
		'LimitedScope' => 'Nette\Utils\LimitedScope',
		'NMimeTypeDetector' => 'Nette\Utils\MimeTypeDetector',
		'MimeTypeDetector' => 'Nette\Utils\MimeTypeDetector',
		'Neon' => 'Nette\Utils\Neon',
		'NNeon' => 'Nette\Utils\Neon',
		'NeonEntity' => 'Nette\Utils\NeonEntity',
		'NNeonEntity' => 'Nette\Utils\NeonEntity',
		'NNeonException' => 'Nette\Utils\NeonException',
		'NeonException' => 'Nette\Utils\NeonException',
		'NPaginator' => 'Nette\Utils\Paginator',
		'Paginator' => 'Nette\Utils\Paginator',
		'NPhpClassType' => 'Nette\PhpGenerator\ClassType',
		'PhpClassType' => 'Nette\PhpGenerator\ClassType',
		'NPhpHelpers' => 'Nette\PhpGenerator\Helpers',
		'PhpHelpers' => 'Nette\PhpGenerator\Helpers',
		'PhpMethod' => 'Nette\PhpGenerator\Method',
		'NPhpMethod' => 'Nette\PhpGenerator\Method',
		'NPhpParameter' => 'Nette\PhpGenerator\Parameter',
		'PhpParameter' => 'Nette\PhpGenerator\Parameter',
		'PhpLiteral' => 'Nette\PhpGenerator\PhpLiteral',
		'NPhpLiteral' => 'Nette\PhpGenerator\PhpLiteral',
		'NPhpProperty' => 'Nette\PhpGenerator\Property',
		'PhpProperty' => 'Nette\PhpGenerator\Property',
		'RegexpException' => 'Nette\Utils\RegexpException',
		'NRegexpException' => 'Nette\Utils\RegexpException',
		'NSafeStream' => 'Nette\Utils\SafeStream',
		'SafeStream' => 'Nette\Utils\SafeStream',
		'Strings' => 'Nette\Utils\Strings',
		'NStrings' => 'Nette\Utils\Strings',
		'Tokenizer' => 'Nette\Utils\Tokenizer',
		'NTokenizer' => 'Nette\Utils\Tokenizer',
		'NTokenizerException' => 'Nette\Utils\TokenizerException',
		'TokenizerException' => 'Nette\Utils\TokenizerException',
		'Validators' => 'Nette\Utils\Validators',
		'NValidators' => 'Nette\Utils\Validators',
	);



	public function run($folder)
	{
		set_time_limit(0);
		$this->replaces = array_change_key_case($this->replaces);

		if ($this->readOnly) {
			echo "Running in read-only mode\n";
		}

		echo "Scanning folder $folder...\n";

		$counter = 0;
		foreach (Nette\Utils\Finder::findFiles('*.php')->from($folder)
			->exclude(array('.*', '*.tmp', 'tmp', 'temp', 'log')) as $file)
		{
			echo str_pad(str_repeat('.', $counter++ % 40), 40), "\x0D";
			$name = ltrim(str_replace($folder, '', $file), '/\\');

			try {
				$orig = file_get_contents($file);
				$new = $this->processFile($orig);
				if ($new !== $orig) {
					echo '[' . ($this->readOnly ? 'FOUND' : 'FIX') . "] $name\n";
					if (!$this->readOnly) {
						file_put_contents($file, $new);
					}
				}
			} catch (Exception $e) {
				echo "[SKIP] $name: {$e->getMessage()}\n";
			}
		}

		echo "\nDone.";
	}



	public function processFile($input)
	{
		$parser = new PhpParser($input);
		while ($token = $parser->nextToken()) {

			if ($parser->isCurrent(T_NAMESPACE, T_USE)) {
				throw new Exception('PHP 5.3 file');

			} elseif ($parser->isCurrent(T_INSTANCEOF, T_EXTENDS, T_IMPLEMENTS, T_NEW)) {
				do {
					$parser->nextAll(T_WHITESPACE, T_COMMENT);
					$pos = $parser->position + 1;
					if ($class = $parser->joinAll(T_STRING, T_NS_SEPARATOR)) {
						$parser->replace($this->renameClass($class), $pos);
					}
				} while ($class && $parser->nextToken(','));

			} elseif ($parser->isCurrent(T_STRING, T_NS_SEPARATOR)) { // Class:: or typehint
				$pos = $parser->position;
				$identifier = $token[Tokenizer::VALUE] . $parser->joinAll(T_STRING, T_NS_SEPARATOR);
				if ($parser->isNext(T_DOUBLE_COLON, T_VARIABLE)) {
					$parser->replace($this->renameClass($identifier), $pos);
				}

			} elseif ($parser->isCurrent(T_DOC_COMMENT, T_COMMENT)) {
				// @var Class or \Class or Nm\Class or Class:: (preserves CLASS)
				$that = $this;
				$parser->replace(preg_replace_callback('#((?:@var(?:\s+array of)?|returns?|param|throws|@link|property[\w-]*)\s+)([\w\\\\|]+)#', function($m) use ($that) {
					$parts = array();
					foreach (explode('|', $m[2]) as $part) {
						$parts[] = preg_match('#^\\\\?[A-Z].*[a-z]#', $part) ? $that->renameClass($part) : $part;
					}
					return $m[1] . implode('|', $parts);
				}, $token[Tokenizer::VALUE]));

			} elseif ($parser->isCurrent(T_CONSTANT_ENCAPSED_STRING)) {
				if (preg_match('#(^.)([A-Z]\w*[a-z]\w*)(:.*|.\z)#', $token[Tokenizer::VALUE], $m)) { // 'NObject'
					$class = str_replace('\\\\', '\\', $m[2], $double);
					if (isset($that->replaces[strtolower($class)])) {
						$class = $that->replaces[strtolower($class)];
						$parser->replace($m[1] . str_replace('\\', $double ? '\\\\' : '\\', $class) . $m[3]);
					}
				}
			}
		}

		$parser->reset();
		return $parser->joinAll();
	}



	/**
	 * Renames class.
	 * @param  string class
	 * @return string new class
	 */
	function renameClass($class)
	{
		return isset($this->replaces[strtolower($class)]) ? $this->replaces[strtolower($class)] : $class;
	}

}



/**
 * Simple tokenizer for PHP.
 */
class PhpParser extends Nette\Utils\TokenIterator
{

	function __construct($code)
	{
		$this->ignored = array(T_COMMENT, T_DOC_COMMENT, T_WHITESPACE);
		foreach (token_get_all($code) as $token) {
			$this->tokens[] = array(
				Tokenizer::VALUE => is_array($token) ? $token[1] : $token,
				Tokenizer::TYPE => is_array($token) ? $token[0] : NULL,
			);
		}
	}



	function replace($s, $start = NULL)
	{
		for ($i = ($start === NULL ? $this->position : $start); $i < $this->position; $i++) {
			$this->tokens[$i] = array(Tokenizer::VALUE => '');
		}
		$this->tokens[$this->position] = array(Tokenizer::VALUE => $s);
	}

}



$updater = new ClassUpdater;
$updater->readOnly = !isset($options['f']);
$updater->run(isset($options['d']) ? $options['d'] : getcwd());
