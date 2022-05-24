<?php

use Mockery as m;
use PHPUnit\Framework\TestCase;

abstract class MiddlewareTest extends TestCase
{
	public static $abortCode = null;

	public static function setUpBeforeClass(): void
	{
		if (! function_exists('abort')) {
		    /**
		     * Mimicks Laravel5's abort() helper function.
		     *
		     * Instead of calling \Illuminate\Foundation\Application::abort(), this function keeps track of
		     * the last abort called, so the abort can be retrieved for test assertions.
		     *
		     * @see https://github.com/laravel/framework/blob/master/src/Illuminate/Foundation/helpers.php#L7-L23
		     *
		     * @param  int     $code
		     * @param  string  $message
		     * @param  array   $headers
		     * @return void
		     */
		    function abort($code, $message = '', array $headers = [])
		    {
		        MiddlewareTest::$abortCode = $code;
		    }
		}
	}

	protected function tearDown(): void
	{
		parent::tearDown();

        m::close();

		// Reset the abort code every end of test case,
		// so the result of previous test case does not pollute the next one.
        static::$abortCode = null;
	}

	public function assertAbortCode($code)
	{
		return $this->assertEquals($code, $this->getAbortCode());
	}

	public function assertDidNotAbort()
	{
		return $this->assertEquals(null, $this->getAbortCode());
	}

	public function getAbortCode()
	{
		return static::$abortCode;
	}

    protected function mockRequest()
    {
        $user = m::mock('_mockedUser')->makePartial();

        $request = m::mock('Illuminate\Http\Request')
            ->shouldReceive('user')
            ->andReturn($user)
            ->getMock();

        return $request;
    }
}
