<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\TesterParallelStress;

use Tester;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class StressRunner
{

	/** @var \ReflectionClass */
	private $testCaseReflection;

	/** @var string */
	private $tempDir;

	/** @var string */
	private $bootstrapFile;



	/**
	 * @param Tester\TestCase $testCase
	 * @param string $tempDir
	 * @param string $bootstrapFile
	 */
	public function __construct(Tester\TestCase $testCase, $tempDir, $bootstrapFile)
	{
		if (!is_dir($tempDir)) {
			throw new \InvalidArgumentException(sprintf('The $tempDir "%s" must be a writable directory', $tempDir));
		}

		if (!file_exists($bootstrapFile)) {
			throw new \InvalidArgumentException(sprintf('The $bootstrapFile "%s" not found', $bootstrapFile));
		}

		$this->testCaseReflection = new \ReflectionClass($testCase);
		$this->tempDir = $tempDir;
		$this->bootstrapFile = $bootstrapFile;
	}



	/**
	 * @param \Closure $closure
	 * @param int $repeat
	 * @param int $threads
	 * @return array
	 */
	public function run(\Closure $closure, $repeat = 100, $threads = 30)
	{
		$scriptFile = $this->resolveScriptFilename();
		$this->extractClosureToScriptFile($closure, $repeat, $scriptFile);

		$runner = $this->createStressRunner($scriptFile, $threads);
		$runner->run();

		return $runner->getResults();
	}



	/**
	 * @return string
	 */
	protected function resolveScriptFilename()
	{
		$runTest = self::findTrace(debug_backtrace(), 'Tester\TestCase::runTest') ?: ['args' => [0 => 'all']];
		$scriptName = $this->testCaseReflection->getShortName() . '.' . substr(md5(serialize($runTest['args'][0])), 0, 10);

		$scriptFile = $this->tempDir . '/scripts/' . $scriptName . '.php';
		if (!@mkdir($dir = dirname($scriptFile), 0777, TRUE) && !is_dir($dir)) {
			throw new \RuntimeException(sprintf('Cannot create directory %s', $dir));
		}

		return $scriptFile;
	}



	/**
	 * @param \Closure $closure
	 * @param int $repeat
	 * @param string $scriptFile
	 */
	protected function extractClosureToScriptFile(\Closure $closure, $repeat, $scriptFile)
	{
		$extractor = new ClosureExtractor($this->tempDir, $this->bootstrapFile);
		file_put_contents(
			$scriptFile,
			$extractor->buildScript($closure, $this->testCaseReflection, $repeat)
		);
		@chmod($scriptFile, 0755); // intentionally @
	}



	/**
	 * @param string $scriptFile
	 * @param int $threads
	 * @return Tester\Runner\Runner
	 */
	protected function createStressRunner($scriptFile, $threads = 30)
	{
		$runner = new Tester\Runner\Runner($this->createTesterInterpreter());
		$runner->outputHandlers[] = new ResultsCollector(
			dirname($this->testCaseReflection->getFileName()) . '/output',
			basename($scriptFile, '.php')
		);
		$runner->threadCount = $threads;
		$runner->paths = [$scriptFile];
		return $runner;
	}



	/**
	 * @return \Tester\Runner\PhpInterpreter
	 */
	protected function createTesterInterpreter()
	{
		$args = [];
		if ($ini = php_ini_loaded_file()) {
			$args[] = '-c ' . Tester\Helpers::escapeArg($ini);
		}

		if (defined('HHVM_VERSION')) {
			return new Tester\Runner\HhvmPhpInterpreter('hhvm', ' ' . implode(' ', $args));

		} elseif (PHP_SAPI === 'cli') {
			return new Tester\Runner\ZendPhpInterpreter('php', ' ' . implode(' ', $args));

		} elseif (stripos(PHP_SAPI, 'cgi') !== FALSE) {
			return new Tester\Runner\ZendPhpInterpreter('php-cgi', ' ' . implode(' ', $args));
		}

		throw new \RuntimeException('Cannot resolve interpreter for running stress test');
	}



	/**
	 * @author David Grudl
	 * @see https://github.com/nette/tracy/blob/2c59542907492a1fcd034ee9a3e5f4fdd3a5db6d/src/Tracy/Helpers.php#L62-L74
	 */
	protected static function findTrace(array $trace, $method)
	{
		$m = explode('::', $method);
		foreach ($trace as $i => $item) {
			if (isset($item['function']) && $item['function'] === end($m)
				&& isset($item['class']) === isset($m[1])
				&& (!isset($item['class']) || $item['class'] === $m[0] || $m[0] === '*' || is_subclass_of($item['class'], $m[0]))
			) {
				return $item;
			}
		}
	}

}
