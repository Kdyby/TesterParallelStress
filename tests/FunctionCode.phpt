<?php

/**
 * @testCase
 */

namespace KdybyTests\TesterParallelStress;

use Kdyby\TesterParallelStress\FunctionCode;
use Tester;
use Tester\Assert;



require_once __DIR__ . '/bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class FunctionCodeTest extends Tester\TestCase
{

	public function testParse()
	{
		$closure = function ($a, $b) use (&$arg) {
			return $a + $b;
		};

		$parser = new FunctionCode(new \ReflectionFunction($closure));
		$parsed = $parser->parse();

		Assert::same("\n" . 'return $a + $b;' . "\n", $parsed);
	}

	public function testParseWrapped()
	{
		$factory = function () {
			return function ($a, $b) use (&$arg) {
				return $a + $b;
			};
		};

		$closure = $factory();

		$parser = new FunctionCode(new \ReflectionFunction($closure));
		$parsed = $parser->parse();

		Assert::same("\n" . 'return $a + $b;' . "\n", $parsed);
	}

	public function testParseWrappedWithInnerClosure()
	{
		$factory = function () {
			return function ($a, $b) use (&$arg) {
				$c = function () {
					return 1;
				};

				return $a + $b + $c();
			};
		};

		$closure = $factory();

		$parser = new FunctionCode(new \ReflectionFunction($closure));
		$parsed = $parser->parse();

		$expected = <<<'CODE'

$c = function () {
return 1;
};

return $a + $b + $c();

CODE;

		Assert::same($expected, $parsed);
	}

}


\run(new FunctionCodeTest());
