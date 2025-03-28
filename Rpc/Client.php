<?php
/*
 * This file is part of the Symfony bundle Seven/Rpc.
 *
 * (c) Sergey Kolodyazhnyy <sergey.kolodyazhnyy@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Seven\RpcBundle\Rpc;
use Seven\RpcBundle\Exception\UnknownMethodResponse;
use Seven\RpcBundle\Rpc\Method\MethodCall;
use Seven\RpcBundle\Rpc\Method\MethodFault;
use Seven\RpcBundle\Rpc\Method\MethodResponse;
use Seven\RpcBundle\Rpc\Method\MethodReturn;
use Seven\RpcBundle\Rpc\Transport\TransportCurl;
use Seven\RpcBundle\Rpc\Transport\TransportInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Client implements ClientInterface
{

    protected $impl = null;
    protected $webServiceUrl = null;
    protected $transport;

    /**
     * @param $webServiceUrl
     * @param Implementation     $impl
     * @param TransportInterface $transport
     */

    public function __construct($webServiceUrl, Implementation $impl, TransportInterface $transport = null)
    {
        $this->impl = $impl;
        $this->webServiceUrl = $webServiceUrl;
        $this->transport = $transport ?: new TransportCurl();
    }

    /**
     * @param $methodName
     * @param  array $parameters
     * @param string $requestMethod
     * @param boolean $validateResponse
     * @return mixed|null|string
     */

    public function call($methodName, $parameters = array(), $requestMethod = 'GET', $validateResponse = true)
    {
        return $this->_call(new MethodCall($methodName, $parameters), $requestMethod, $validateResponse);
    }

    /**
     * @param  MethodCall $call
     * @param string $requestMethod
     * @param boolean $validateResponse
     * @return null|string
     * @throws UnknownMethodResponse
     * @throws \Exception
     */

    protected function _call(MethodCall $call, $requestMethod = 'GET', $validateResponse = true)
    {
        $methodResponse = $this->_handle($call, $requestMethod, $validateResponse);

        if($methodResponse instanceof MethodFault)
            throw $methodResponse->getException();
        if($methodResponse instanceof MethodReturn)

            return $methodResponse->getReturnValue();

        throw new UnknownMethodResponse('Unable to determine method response type');
    }

    /**
     * @param  MethodCall $call
     * @param string $requestMethod
     * @param boolean $validateResponse
     * @return MethodResponse
     */

    protected function _handle(MethodCall $call, $requestMethod = 'GET', $validateResponse = true)
    {
        $request = $this->impl->createHttpRequest($call);
        $contentType = $request->headers->get('content-type');
        $request = Request::create($this->webServiceUrl, $requestMethod, array(), array(), array(), array(), $request->getContent());
        $request->headers->set('content-type', $contentType);
        $response = $this->transport->makeRequest($request);

        return $this->impl->createMethodResponse($response, $validateResponse);
    }

}
