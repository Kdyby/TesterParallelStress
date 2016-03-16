<?php

namespace Kdyby\TesterParallelStress;

use Kdyby;
use Kdyby\ParseUseStatements\UseStatements;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ClosureExtractor
{

	/**
	 * @var string
	 */
	private $tempDir;

	/**
	 * @var string
	 */
	private $bootstrap;



	/**
	 * @param string $tempDir
	 * @param string $bootstrap
	 */
	public function __construct($tempDir, $bootstrap)
	{
		$this->tempDir = $tempDir;
		$this->bootstrap = $bootstrap;
	}



	/**
	 * @param \Closure $closure
	 * @param \ReflectionClass $class
	 * @param int $repeat
	 * @return string
	 */
	public function buildScript(\Closure $closure, \ReflectionClass $class, $repeat)
	{
		$closureReflection = new \ReflectionFunction($closure);
		$codeParser = new FunctionCode($closureReflection);

		$code = '<?php' . "\n\n";
		$code .= $this->serializeHeadPhpDoc($repeat);
		$code .= "namespace " . $class->getNamespaceName() . ";\n\n";
		$code .= $this->serializeUseStatements($class);

		$code .= Code\Helpers::formatArgs('require_once ?;', [$this->bootstrap]) . "\n";
		$code .= '\Tester\Environment::$checkAssertions = FALSE;' . "\n";
		if (class_exists('Tracy\Debugger')) {
			$code .= Code\Helpers::formatArgs('\Tracy\Debugger::$logDirectory = ?;', [$this->tempDir]) . "\n";
		}
		$code .= "\n\n";

		// script
		$code .= Code\Helpers::formatArgs('extract(?);', [$closureReflection->getStaticVariables()]) . "\n\n";
		$code .= $codeParser->parse() . "\n\n\n";

		return $code;
	}



	/**
	 * @param \ReflectionClass $reflector
	 * @return string
	 */
	protected function serializeUseStatements(\ReflectionClass $reflector)
	{
		$code = '';
		$useStatements = UseStatements::getUseStatements($reflector);
		foreach ($useStatements as $alias => $class) {
			$code .= 'use ' . $alias . ";\n";
		}

		if ($code !== '') {
			$code .= "\n";
		}

		return $code;
	}



	/**
	 * @param int $repeat
	 * @return string
	 */
	protected function serializeHeadPhpDoc($repeat)
	{
		return <<<DOC
/**
 * @multiple $repeat
 */


DOC;
	}

}
