<?php
namespace Oka\CORSBundle\EventListener;

use Oka\CORSBundle\CorsOptions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 */
class RequestListener implements EventSubscriberInterface
{
	private $maps;
	
	public function __construct(array $maps = [])
	{
		$this->maps = $maps;
	}
	
	public function onKernelResponse(ResponseEvent $event)
	{
	    $request = $event->getRequest();
		
	    if (false === $request->isMethod('OPTIONS') && true === $request->headers->has('Origin')) {
			foreach ($this->maps as $map) {
				if (true === $this->match($request, $map[CorsOptions::ORIGINS], $map[CorsOptions::PATTERN] ?? null)) {
				    $this->apply($request, $event->getResponse(), $map);
					break;
				}
			}
		}
	}
	
	public function onKernelException(ExceptionEvent $event)
	{
		$request = $event->getRequest();
		$exception = $event->getThrowable();
		
		if ($exception instanceof MethodNotAllowedHttpException && true === $request->isMethod('OPTIONS') && true === $request->headers->has('Origin')) {			
			foreach ($this->maps as $map) {
				if (true === $this->match($request, $map[CorsOptions::ORIGINS], $map[CorsOptions::PATTERN])) {
					$response = $this->apply($request, new Response(), $map);
					$event->allowCustomResponseCode();
					$event->setResponse($response);
					break;
				}
			}
		}
	}
	
	public static function getSubscribedEvents()
	{
		return [
		    KernelEvents::RESPONSE => 'onKernelResponse',
		    KernelEvents::EXCEPTION => ['onKernelException', 2048]
		];
	}
	
	private function match(Request $request, array $origins, string $pattern = null): bool
	{
		if (false === empty($origins) && false === in_array($request->headers->get('Origin'), $origins)) {
			return false;
		}
		
		if (true === isset($pattern) && false === (new RequestMatcher($pattern))->matches($request)) {
			return false;
		}
		
		return true;
	}
	
	private function apply(Request $request, Response $response, array $map = []): Response
	{
		// Define CORS allow_origin
		$response->headers->set('Access-Control-Allow-Origin', false === empty($map[CorsOptions::ORIGINS]) ? $request->headers->get('Origin') : '*');
		
		// Define CORS allow_methods
		if (false === empty($map[CorsOptions::ALLOW_METHODS])) {
			$response->headers->set('Access-Control-Allow-Methods', implode(',', $map[CorsOptions::ALLOW_METHODS]));
		} elseif ($request->headers->has('Access-Control-Request-Method')) {
			$response->headers->set('Access-Control-Allow-Methods', $request->headers->get('Access-Control-Request-Method'));
		}
		
		// Define CORS allow_headers
		if (false === empty($map[CorsOptions::ALLOW_HEADERS])) {
			$response->headers->set('Access-Control-Allow-Headers', implode(',', $map[CorsOptions::ALLOW_HEADERS]));
		} elseif ($request->headers->has('Access-Control-Request-Headers')) {
			$response->headers->set('Access-Control-Allow-Headers', $request->headers->get('Access-Control-Request-Headers'));
		}
		
		// Define CORS allow_credentials
		if (true === $map[CorsOptions::ALLOW_CREDENTIALS]) {
			$response->headers->set('Access-Control-Allow-Credentials', 'true');
		}
		
		// Define CORS expose_headers
		$exposeHeaders = array_merge(['Cache-Control, Content-Type, Content-Length, Content-Encoding, X-Server-Time, X-Request-Duration, X-Secure-With'], $map[CorsOptions::EXPOSE_HEADERS]);
		$response->headers->set('Access-Control-Expose-Headers', implode(',', array_unique($exposeHeaders, SORT_REGULAR)));
		
		// Define CORS max_age
		if ($map[CorsOptions::MAX_AGE] > 0) {
			$response->headers->set('Access-Control-Max-Age', $map[CorsOptions::MAX_AGE]);
		}
		
		return $response;
	}
}
