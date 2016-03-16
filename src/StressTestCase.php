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
trait StressTestCase
{

	/**
	 * @param \Closure $closure
	 * @param int $repeat
	 * @param int $threads
	 * @param string $tempDir
	 * @param string $bootstrapFile
	 * @return array
	 */
	protected function parallelStress(\Closure $closure, $repeat = 100, $threads = 30, $tempDir, $bootstrapFile)
	{
		/** @var Tester\TestCase|StressTestCase $this */
		$runner = new StressRunner($this, $tempDir, $bootstrapFile);
		return $runner->run($closure, $repeat, $threads);
	}

}
