<?php  namespace Base;

use Base\Support\Pipeline;
use Base\Support\Container;


class TheRequest
{
	public $m1 = 0;
	public $m2 = 0;
}

class Middleware1
{
	public $v = 0;
	public $time = 0;

	public function handle($request, $next, $time)
	{
		$request->m1 = 1;
		$this->v++;
		$this->time = $time;

		return $next($request);
	}
}


class Middleware2
{
	public function handle($request, $next)
	{
		$request->m2 = 1;
		return $next($request);
	}
}

class TheController
{
	public $content = 'This is my content';

	public function index($request)
	{
		return $this->content.' - '.$request->m1.'/'.$request->m2;
	}
}

class TheResponse
{
	public $body = 'Default Content';

	public function setBody($content)
	{
		$this->body = $content;

		return $this;
	}

    public function getBody()
	{
		return $this->body;
	}
}





class PipelineTest extends \PHPUnit\Framework\TestCase
{

    public function testThroughPut()
    {
        $controller = new TheController();
		$response   = new TheResponse();
		$request    = new TheRequest();

        $pipe = (new Pipeline())
		    ->send($request)
		    ->through([
				'Base\Middleware1:60,1',
				'Base\Middleware2'
			])
		    ->via('handle')
		    ->then(function ($request) use ($controller) {
		        return (new TheResponse())->setBody($controller->index($request));
		    });

        $this->assertEquals($pipe->getBody(), 'This is my content - 1/1');
    }


    public function testThroughFail()
    {
        $controller = new TheController();
		$response   = new TheResponse();
		$request    = new TheRequest();

        $pipe = (new Pipeline())
		    ->send($request)
		    ->through([
				'Base\DoesNotExist'
			])
		    ->then(function ($request) use ($controller) {
		        return (new TheResponse())->setBody($controller->index($request));
		    });

        $this->assertEquals($pipe, NULL);
    }



	public function testThroughPutContainer()
    {
		$container  = new Container();
		$container->setAlias([
			'm1' => 'Base\Middleware1',
			'm2' => 'Base\Middleware2'
		]);

        $controller = new TheController();
		$response   = new TheResponse();
		$request    = new TheRequest();

        $pipe = (new Pipeline($container))
		    ->send($request)
		    ->through([
				'm1:60,1',
				'm2'
			])
		    ->via('handle')
		    ->then(function ($request) use ($controller) {
		        return (new TheResponse())->setBody($controller->index($request));
		    });

        $this->assertEquals($pipe->getBody(), 'This is my content - 1/1');

		$m1 = $container->get('m1');

		$this->assertEquals($m1->v, 1);
		$this->assertEquals($m1->time, 60);
    }

}
