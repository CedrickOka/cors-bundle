<?php
namespace Oka\CORSBundle\EventListener;

use Oka\CORSBundle\CorsOptions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * 
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 * 
 */
class RequestListener implements EventSubscriberInterface
{
	/**
	 * @var array $maps
	 */
	protected $maps;
	
	public function __construct(array $maps = [])
	{
		$this->maps = $maps;
	}
	
	/**
	 * @param FilterResponseEvent $event
	 */
	public function onKernelResponse(FilterResponseEvent $event)
	{
		$request = $event->getRequest();
		
		if (false === $request->isMethod('OPTIONS') && true === $request->headers->has('Origin')) {
			foreach ($this->maps as $map) {
				if (true === $this->match($request, $map[CorsOptions::ORIGINS], $map[CorsOptions::PATTERN])) {
					$this->apply($request, $event->getResponse(), $map);
					break;
				}
			}
		}
	}
	
	/**
	 * @param GetResponseForExceptionEvent $event
	 */
	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		$request = $event->getRequest();
		$exception = $event->getException();
		
		if ($exception instanceof MethodNotAllowedHttpException && true === $request->isMethod('OPTIONS') && true === $request->headers->has('Origin')) {			
			foreach ($this->maps as $map) {
				if (true === $this->match($request, $map[CorsOptions::ORIGINS], $map[CorsOptions::PATTERN])) {
					$response = $this->apply($request, new Response(), $map);
					
					if (-1 === version_compare(\Symfony\Component\HttpKernel\Kernel::VERSION, '3.3')) {
						// Overwrite exception status code
						$response->headers->set('X-Status-Code', 200);
					} else {
						$event->allowCustomResponseCode();
					}
					
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
	
	/**
	 * @param Request $request
	 * @param array $origins
	 * @param string $pattern
	 * @return boolean
	 */
	private function match(Request $request, $origins, $pattern)
	{
		if (false === empty($origins) && false === in_array($request->headers->get('Origin'), $origins)) {
			return false;
		}
		
		if (true === isset($pattern) && !preg_match(sprintf('#%s#', strtr($pattern, '#', '\#')), $request->getPathInfo())) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @param Request $request
	 * @param Response $response
	 * @param array $map
	 * @return Response
	 */
	private function apply(Request $request, Response $response, array $map = [])
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
