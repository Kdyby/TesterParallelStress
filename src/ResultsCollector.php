<?php

namespace Kdyby\TesterParallelStress;

use Tester;




class ResultsCollector implements Tester\Runner\OutputHandler
{

	public $results;

	/**
	 * @var string
	 */
	private $dir;

	/**
	 * @var string
	 */
	private $testName;



	public function __construct($dir, $testName)
	{
		$this->dir = $dir;
		$this->testName = $testName;
	}



	public function begin()
	{
		$this->results = [];

		if (is_dir($this->dir)) {
			foreach (glob(sprintf('%s/%s.*.actual', $this->dir, urlencode($this->testName))) as $file) {
				@unlink($file);
			}
		}
	}



	public function result($testName, $result, $message)
	{
		$message = Tester\Dumper::removeColors(trim($message));

		if ($result !== Tester\Runner\Runner::PASSED) {
			$this->results[] = [$testName, $message];
		}
	}



	public function end()
	{
		if (!$this->results) {
			return;
		}

		if (!@mkdir($this->dir, 0777, TRUE) && !is_dir($this->dir)) {
			throw new \RuntimeException(sprintf('Cannot create directory %s', $this->dir));
		}

		// write new
		foreach ($this->results as $process) {
			$args = !preg_match('~\\[(.+)\\]$~', trim($process[0]), $m) ? md5(basename($process[0])) : str_replace('=', '_', $m[1]);
			$filename = urlencode($this->testName) . '.' . urlencode($args) . '.actual';
			file_put_contents($this->dir . '/' . $filename, $process[1]);
		}
	}

}
